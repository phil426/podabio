import { useState, useCallback, useEffect } from 'react';
import { Globe, Check, X, CircleNotch, ArrowSquareOut, Copy, Warning, Info } from '@phosphor-icons/react';

import { usePageSnapshot, useVerifyDomainMutation, useCustomDomainMutation } from '../../api/page';
import { trackTelemetry } from '../../services/telemetry';

import styles from './custom-domain-settings.module.css';

interface CustomDomainSettingsProps {
  /** The username for the current page (for displaying the poda.bio URL) */
  username?: string | null;
}

type DomainStatus = 'idle' | 'checking' | 'verified' | 'unverified' | 'saving' | 'error';

export function CustomDomainSettings({ username }: CustomDomainSettingsProps): JSX.Element {
  const { data: pageData, isLoading: pageLoading } = usePageSnapshot();
  const verifyMutation = useVerifyDomainMutation();
  const customDomainMutation = useCustomDomainMutation();

  // Current saved domain from the database
  const savedDomain = pageData?.page?.custom_domain ?? null;
  
  // Form state
  const [domainInput, setDomainInput] = useState('');
  const [status, setStatus] = useState<DomainStatus>('idle');
  const [statusMessage, setStatusMessage] = useState<string | null>(null);
  const [dnsRecords, setDnsRecords] = useState<Array<{ type: string; value: string }>>([]);
  const [showInstructions, setShowInstructions] = useState(false);

  // Initialize input from saved domain
  useEffect(() => {
    if (savedDomain && !domainInput) {
      setDomainInput(savedDomain);
      // Auto-verify on load if we have a saved domain
      handleVerify(savedDomain);
    }
  // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [savedDomain]);

  const normalizeDomain = useCallback((input: string): string => {
    let domain = input.trim().toLowerCase();
    // Remove protocol
    domain = domain.replace(/^https?:\/\//, '');
    // Remove www prefix
    domain = domain.replace(/^www\./, '');
    // Remove trailing slash and path
    domain = domain.split('/')[0] ?? domain;
    return domain;
  }, []);

  const isValidDomainFormat = useCallback((domain: string): boolean => {
    if (!domain || domain.length > 255) return false;
    // Basic domain validation regex
    return /^([a-z0-9]([a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/i.test(domain);
  }, []);

  const handleVerify = useCallback(async (domainToVerify?: string) => {
    const domain = normalizeDomain(domainToVerify ?? domainInput);
    
    if (!domain) {
      setStatus('error');
      setStatusMessage('Please enter a domain name');
      return;
    }

    if (!isValidDomainFormat(domain)) {
      setStatus('error');
      setStatusMessage('Please enter a valid domain (e.g., mypodcast.com)');
      return;
    }

    // Don't allow poda.bio subdomains
    if (domain.endsWith('poda.bio') || domain.endsWith('getphily.com')) {
      setStatus('error');
      setStatusMessage('You cannot use a PodaBio subdomain as a custom domain');
      return;
    }

    setStatus('checking');
    setStatusMessage('Checking DNS configuration…');
    setDnsRecords([]);

    try {
      const result = await verifyMutation.mutateAsync(domain);
      
      if (result.verified) {
        setStatus('verified');
        setStatusMessage('Domain is correctly configured and pointing to PodaBio!');
        setDnsRecords(result.records ?? []);
        trackTelemetry({ event: 'custom_domain.verified', metadata: { domain } });
      } else {
        setStatus('unverified');
        setStatusMessage(result.message || 'Domain DNS is not pointing to PodaBio. Please check your DNS settings.');
        setDnsRecords(result.records ?? []);
        setShowInstructions(true);
      }
    } catch (err) {
      setStatus('error');
      setStatusMessage(err instanceof Error ? err.message : 'Failed to verify domain');
    }
  }, [domainInput, normalizeDomain, isValidDomainFormat, verifyMutation]);

  const handleSave = useCallback(async () => {
    const domain = normalizeDomain(domainInput);
    
    if (!domain) {
      setStatus('error');
      setStatusMessage('Please enter a domain name');
      return;
    }

    if (status !== 'verified') {
      // Verify first
      await handleVerify();
      return;
    }

    setStatus('saving');
    setStatusMessage('Saving custom domain…');

    try {
      const result = await customDomainMutation.mutateAsync(domain);
      
      if (result.success) {
        setStatusMessage('Custom domain saved successfully!');
        trackTelemetry({ event: 'custom_domain.saved', metadata: { domain } });
      } else {
        setStatus('error');
        setStatusMessage(result.error ?? 'Failed to save custom domain');
      }
    } catch (err) {
      setStatus('error');
      setStatusMessage(err instanceof Error ? err.message : 'Failed to save domain');
    }
  }, [domainInput, normalizeDomain, status, handleVerify, customDomainMutation]);

  const handleRemove = useCallback(async () => {
    if (!savedDomain) return;

    setStatus('saving');
    setStatusMessage('Removing custom domain…');

    try {
      const result = await customDomainMutation.mutateAsync(null);
      
      if (result.success) {
        setDomainInput('');
        setStatus('idle');
        setStatusMessage('Custom domain removed');
        setDnsRecords([]);
        setShowInstructions(false);
        trackTelemetry({ event: 'custom_domain.removed', metadata: { domain: savedDomain } });
      } else {
        setStatus('error');
        setStatusMessage(result.error ?? 'Failed to remove custom domain');
      }
    } catch (err) {
      setStatus('error');
      setStatusMessage(err instanceof Error ? err.message : 'Failed to remove domain');
    }
  }, [savedDomain, customDomainMutation]);

  const handleCopyDNS = useCallback(() => {
    const serverIP = '156.67.73.201';
    navigator.clipboard.writeText(serverIP);
    trackTelemetry({ event: 'custom_domain.copy_dns', metadata: {} });
  }, []);

  const isPending = status === 'checking' || status === 'saving';
  const hasChanges = normalizeDomain(domainInput) !== (savedDomain ?? '');

  if (pageLoading) {
    return (
      <div className={styles.container}>
        <div className={styles.loading}>
          <CircleNotch size={20} weight="regular" className={styles.spinner} />
          <span>Loading domain settings…</span>
        </div>
      </div>
    );
  }

  return (
    <div className={styles.container}>
      <div className={styles.header}>
        <Globe size={24} weight="regular" className={styles.headerIcon} />
        <div>
          <h3>Custom Domain</h3>
          <p>Use your own domain instead of poda.bio/{username ?? 'yourname'}</p>
        </div>
      </div>

      <div className={styles.content}>
        {/* Current poda.bio URL */}
        {username && (
          <div className={styles.currentUrl}>
            <span className={styles.urlLabel}>Your PodaBio URL:</span>
            <a 
              href={`https://poda.bio/${username}`}
              target="_blank"
              rel="noopener noreferrer"
              className={styles.urlLink}
            >
              poda.bio/{username}
              <ArrowSquareOut size={14} weight="regular" />
            </a>
          </div>
        )}

        {/* Domain input */}
        <div className={styles.inputGroup}>
          <label htmlFor="custom-domain-input" className={styles.inputLabel}>
            Custom domain
          </label>
          <div className={styles.inputWrapper}>
            <input
              id="custom-domain-input"
              type="text"
              className={styles.input}
              placeholder="mypodcast.com"
              value={domainInput}
              onChange={(e) => {
                setDomainInput(e.target.value);
                setStatus('idle');
                setStatusMessage(null);
              }}
              disabled={isPending}
            />
            <button
              type="button"
              className={styles.verifyButton}
              onClick={() => handleVerify()}
              disabled={isPending || !domainInput.trim()}
            >
              {status === 'checking' ? (
                <CircleNotch size={16} weight="regular" className={styles.spinner} />
              ) : (
                'Verify'
              )}
            </button>
          </div>
        </div>

        {/* Status message */}
        {statusMessage && (
          <div className={`${styles.statusMessage} ${styles[status]}`}>
            {status === 'verified' && <Check size={16} weight="bold" />}
            {status === 'unverified' && <Warning size={16} weight="regular" />}
            {status === 'error' && <X size={16} weight="bold" />}
            {(status === 'checking' || status === 'saving') && (
              <CircleNotch size={16} weight="regular" className={styles.spinner} />
            )}
            <span>{statusMessage}</span>
          </div>
        )}

        {/* DNS Records found */}
        {dnsRecords.length > 0 && (
          <div className={styles.dnsRecords}>
            <span className={styles.dnsLabel}>Current DNS records:</span>
            <ul>
              {dnsRecords.map((record, idx) => (
                <li key={idx}>
                  <code>{record.type}</code>: {record.value}
                </li>
              ))}
            </ul>
          </div>
        )}

        {/* DNS Instructions */}
        {(showInstructions || status === 'unverified') && (
          <div className={styles.instructions}>
            <div className={styles.instructionsHeader}>
              <Info size={18} weight="regular" />
              <span>How to configure your domain</span>
            </div>
            <ol className={styles.instructionsList}>
              <li>
                <strong>Log in to your domain registrar</strong> (e.g., Namecheap, GoDaddy, Google Domains, Cloudflare)
              </li>
              <li>
                <strong>Find DNS settings</strong> for your domain
              </li>
              <li>
                <strong>Add an A record</strong> pointing to PodaBio's server:
                <div className={styles.dnsValue}>
                  <code>
                    <strong>Type:</strong> A<br />
                    <strong>Host:</strong> @ (or leave blank)<br />
                    <strong>Value:</strong> 156.67.73.201
                  </code>
                  <button
                    type="button"
                    className={styles.copyButton}
                    onClick={handleCopyDNS}
                    title="Copy IP address"
                  >
                    <Copy size={14} weight="regular" />
                  </button>
                </div>
              </li>
              <li>
                <strong>Wait for DNS propagation</strong> (can take up to 48 hours, usually much faster)
              </li>
              <li>
                <strong>Click "Verify"</strong> to check if your domain is configured correctly
              </li>
            </ol>
            <p className={styles.instructionsNote}>
              <strong>Note:</strong> SSL certificates will be provisioned automatically once your domain is verified.
            </p>
          </div>
        )}

        {/* Action buttons */}
        <div className={styles.actions}>
          {savedDomain && (
            <button
              type="button"
              className={styles.removeButton}
              onClick={handleRemove}
              disabled={isPending}
            >
              Remove domain
            </button>
          )}
          
          {(hasChanges || status === 'verified') && (
            <button
              type="button"
              className={styles.saveButton}
              onClick={handleSave}
              disabled={isPending || status !== 'verified'}
            >
              {status === 'saving' ? (
                <>
                  <CircleNotch size={16} weight="regular" className={styles.spinner} />
                  Saving…
                </>
              ) : (
                'Save domain'
              )}
            </button>
          )}
        </div>

        {/* Future Cloudflare upgrade notice */}
        <div className={styles.upgradeNotice}>
          <Info size={16} weight="regular" />
          <span>
            Custom domains require Pro plan. Automatic SSL is included.
          </span>
        </div>
      </div>
    </div>
  );
}


