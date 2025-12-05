import { useState, useEffect } from 'react';
import { Envelope, Check, X, CircleNotch, Eye, EyeSlash } from '@phosphor-icons/react';
import { usePageSnapshot, updateEmailSettings } from '../../api/page';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { queryKeys } from '../../api/utils';

import styles from './email-subscription-settings.module.css';

const EMAIL_SERVICES = [
  { value: '', label: 'None (disabled)' },
  { value: 'mailchimp', label: 'Mailchimp' },
  { value: 'convertkit', label: 'ConvertKit' },
  { value: 'mailerlite', label: 'MailerLite' },
  { value: 'brevo', label: 'Brevo (formerly SendinBlue)' }
] as const;

export function EmailSubscriptionSettings(): JSX.Element {
  const { data: snapshot } = usePageSnapshot();
  const queryClient = useQueryClient();
  const page = snapshot?.page;

  const [provider, setProvider] = useState<string>(page?.email_service_provider ?? '');
  const [apiKey, setApiKey] = useState<string>(page?.email_service_api_key ?? '');
  const [listId, setListId] = useState<string>(page?.email_list_id ?? '');
  const [doubleOptIn, setDoubleOptIn] = useState<boolean>(Boolean(page?.email_double_optin));
  const [showApiKey, setShowApiKey] = useState(false);
  const [status, setStatus] = useState<string | null>(null);
  const [statusTone, setStatusTone] = useState<'success' | 'error'>('success');

  const updateMutation = useMutation({
    mutationFn: updateEmailSettings,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
      setStatusTone('success');
      setStatus('Email subscription settings saved successfully.');
    },
    onError: (error: Error) => {
      setStatusTone('error');
      setStatus(error.message || 'Failed to save email subscription settings.');
    }
  });

  useEffect(() => {
    setProvider(page?.email_service_provider ?? '');
    setApiKey(page?.email_service_api_key ?? '');
    setListId(page?.email_list_id ?? '');
    setDoubleOptIn(Boolean(page?.email_double_optin));
  }, [page?.email_service_provider, page?.email_service_api_key, page?.email_list_id, page?.email_double_optin]);

  useEffect(() => {
    if (!status) return;
    const timer = window.setTimeout(() => setStatus(null), 5000);
    return () => window.clearTimeout(timer);
  }, [status]);

  const handleSave = async () => {
    setStatus(null);

    if (provider && (!apiKey || !listId)) {
      setStatusTone('error');
      setStatus('API key and list ID are required when an email service is selected.');
      return;
    }

    try {
      await updateMutation.mutateAsync({
        email_service_provider: provider || null,
        email_service_api_key: apiKey || '',
        email_list_id: listId || '',
        email_double_optin: doubleOptIn ? '1' : '0'
      });
    } catch (error) {
      // Error handled by mutation
    }
  };

  const hasChanges = 
    provider !== (page?.email_service_provider ?? '') ||
    apiKey !== (page?.email_service_api_key ?? '') ||
    listId !== (page?.email_list_id ?? '') ||
    doubleOptIn !== Boolean(page?.email_double_optin);

  return (
    <div className={styles.wrapper}>
      <header className={styles.header}>
        <div>
          <h3>Email Subscription</h3>
          <p>Configure your email marketing service to collect subscribers</p>
        </div>
      </header>

      {status && (
        <div className={statusTone === 'success' ? styles.statusSuccess : styles.statusError}>
          {statusTone === 'success' ? (
            <Check aria-hidden="true" size={16} weight="regular" />
          ) : (
            <X aria-hidden="true" size={16} weight="regular" />
          )}
          <span>{status}</span>
        </div>
      )}

      <div className={styles.fieldset}>
        <div className={styles.control}>
          <label htmlFor="email-service-provider" className={styles.label}>
            Email Service Provider
          </label>
          <select
            id="email-service-provider"
            className={styles.select}
            value={provider}
            onChange={(e) => setProvider(e.target.value)}
            disabled={updateMutation.isPending}
          >
            {EMAIL_SERVICES.map((service) => (
              <option key={service.value} value={service.value}>
                {service.label}
              </option>
            ))}
          </select>
          <p className={styles.helpText}>
            Select your email marketing service. The email subscription widget will only appear when a service is configured.
          </p>
        </div>

        {provider && (
          <>
            <div className={styles.control}>
              <label htmlFor="email-api-key" className={styles.label}>
                API Key
              </label>
              <div className={styles.inputWrapper}>
                <input
                  id="email-api-key"
                  type={showApiKey ? 'text' : 'password'}
                  className={styles.input}
                  value={apiKey}
                  onChange={(e) => setApiKey(e.target.value)}
                  placeholder="Enter your API key"
                  disabled={updateMutation.isPending}
                />
                <button
                  type="button"
                  className={styles.toggleButton}
                  onClick={() => setShowApiKey(!showApiKey)}
                  aria-label={showApiKey ? 'Hide API key' : 'Show API key'}
                  disabled={updateMutation.isPending}
                >
                  {showApiKey ? (
                    <EyeSlash aria-hidden="true" size={16} weight="regular" />
                  ) : (
                    <Eye aria-hidden="true" size={16} weight="regular" />
                  )}
                </button>
              </div>
              <p className={styles.helpText}>
                {provider === 'mailchimp' && 'Your Mailchimp API key (includes datacenter suffix, e.g., "abc123-us6")'}
                {provider === 'convertkit' && 'Your ConvertKit API key (found in Account Settings → Advanced)'}
                {provider === 'mailerlite' && 'Your MailerLite API key (found in Integrations → Developer API)'}
                {provider === 'brevo' && 'Your Brevo API key (found in SMTP & API → API Keys)'}
              </p>
            </div>

            <div className={styles.control}>
              <label htmlFor="email-list-id" className={styles.label}>
                List ID
              </label>
              <input
                id="email-list-id"
                type="text"
                className={styles.input}
                value={listId}
                onChange={(e) => setListId(e.target.value)}
                placeholder="Enter your list/audience ID"
                disabled={updateMutation.isPending}
              />
              <p className={styles.helpText}>
                {provider === 'mailchimp' && 'Your Mailchimp Audience ID (found in Audience → Settings → Audience name and defaults)'}
                {provider === 'convertkit' && 'Your ConvertKit Form ID or Tag ID'}
                {provider === 'mailerlite' && 'Your MailerLite Group ID'}
                {provider === 'brevo' && 'Your Brevo List ID'}
              </p>
            </div>

            <div className={styles.control}>
              <label className={styles.checkboxLabel}>
                <input
                  type="checkbox"
                  checked={doubleOptIn}
                  onChange={(e) => setDoubleOptIn(e.target.checked)}
                  disabled={updateMutation.isPending}
                />
                <span>Enable double opt-in</span>
              </label>
              <p className={styles.helpText}>
                When enabled, subscribers will receive a confirmation email before being added to your list.
              </p>
            </div>
          </>
        )}
      </div>

      <div className={styles.footer}>
        <button
          type="button"
          className={styles.saveButton}
          onClick={handleSave}
          disabled={updateMutation.isPending || !hasChanges}
        >
          {updateMutation.isPending ? (
            <>
              <CircleNotch className={styles.buttonSpinner} aria-hidden="true" size={16} weight="regular" />
              Saving...
            </>
          ) : (
            <>
              <Check aria-hidden="true" size={16} weight="regular" />
              Save Settings
            </>
          )}
        </button>
      </div>

      {provider && (
        <div className={styles.infoBox}>
          <Envelope aria-hidden="true" size={20} weight="regular" />
          <div>
            <p className={styles.infoTitle}>Email Subscription Widget</p>
            <p className={styles.infoText}>
              Once configured, visitors can subscribe to your email list using the email subscription widget on your page.
              Add the widget from the Content tab.
            </p>
          </div>
        </div>
      )}
    </div>
  );
}


