import { useEffect, useState } from 'react';
import { X, Check, CircleNotch, Gear } from '@phosphor-icons/react';

import { useAuthMethods, useUnlinkGoogleMutation, useRefreshAccountData, useIntegrationsStatus, useDisconnectInstagramMutation } from '../../api/account';
import { SecurityActionDrawer } from '../overlays/SecurityActionDrawer';
import type { SecurityAction } from '../overlays/SecurityActionDrawer';
import { useIntegrationSelection } from '../../state/integrationSelection';
import { type TabColorTheme } from '../layout/tab-colors';

import styles from './integration-inspector.module.css';

interface IntegrationInspectorProps {
  activeColor: TabColorTheme;
}

function parseError(error: unknown): string | null {
  if (!error) return null;
  if (error instanceof Error) return error.message;
  if (typeof error === 'object' && 'message' in error) return String(error.message);
  return 'An error occurred';
}

export function IntegrationInspector({ activeColor }: IntegrationInspectorProps): JSX.Element {
  const selectedIntegrationId = useIntegrationSelection((state) => state.selectedIntegrationId);
  const selectIntegration = useIntegrationSelection((state) => state.selectIntegration);
  const { data: methods, isLoading: methodsLoading } = useAuthMethods();
  const { data: integrations, isLoading: integrationsLoading } = useIntegrationsStatus();
  const { mutateAsync: unlinkGoogle, isPending: unlinkPending, error: unlinkError, reset: resetUnlink } = useUnlinkGoogleMutation();
  const { mutateAsync: disconnectInstagram, isPending: disconnectInstagramPending, error: disconnectInstagramError } = useDisconnectInstagramMutation();
  const refreshAccount = useRefreshAccountData();
  const [drawerAction, setDrawerAction] = useState<SecurityAction | null>(null);
  const [status, setStatus] = useState<string | null>(null);

  const isLoading = methodsLoading || integrationsLoading;

  useEffect(() => {
    if (!selectedIntegrationId) {
      setDrawerAction(null);
      setStatus(null);
      resetUnlink();
    }
  }, [selectedIntegrationId, resetUnlink]);

  if (!selectedIntegrationId) {
    return (
      <div className={styles.wrapper}>
        <div className={styles.emptyState}>
          <Gear className={styles.emptyIcon} aria-hidden="true" size={48} weight="regular" />
          <p>Select an integration to configure</p>
        </div>
      </div>
    );
  }

  const openDrawer = (action: SecurityAction) => {
    resetUnlink();
    setStatus(null);
    setDrawerAction(action);
  };

  const closeDrawer = () => {
    setDrawerAction(null);
    resetUnlink();
  };

  const handleConfirm = async () => {
    if (!drawerAction || drawerAction !== 'unlink_google') return;
    try {
      await unlinkGoogle();
      setStatus('Google sign-in disconnected successfully.');
      await refreshAccount.mutateAsync();
      setDrawerAction(null);
    } catch {
      // errors surface via mutation state
    }
  };

  const handleDisconnectInstagram = async () => {
    try {
      await disconnectInstagram();
      setStatus('Instagram disconnected successfully.');
      await refreshAccount.mutateAsync();
    } catch {
      // errors surface via mutation state
    }
  };

  const drawerError = drawerAction === 'unlink_google' ? parseError(unlinkError) : null;
  const drawerProcessing = drawerAction === 'unlink_google' ? unlinkPending : false;

  // Google Integration Inspector
  if (selectedIntegrationId === 'google') {
    return (
      <div 
        className={styles.wrapper}
        style={{ 
          '--active-tab-color': activeColor.text,
          '--active-tab-bg': activeColor.primary,
          '--active-tab-light': activeColor.light,
          '--active-tab-border': activeColor.border
        } as React.CSSProperties}
      >
        <header className={styles.header}>
          <div>
            <h3>Google Authentication</h3>
            <p>Manage your Google account connection</p>
          </div>
          <button
            type="button"
            className={styles.closeButton}
            onClick={() => selectIntegration(null)}
            aria-label="Close"
          >
            <X aria-hidden="true" size={20} weight="regular" />
          </button>
        </header>

        {isLoading ? (
          <div className={styles.loadingState}>
            <CircleNotch className={styles.spinner} aria-hidden="true" size={20} weight="regular" />
            <p>Loading…</p>
          </div>
        ) : (
          <>
            {status && (
              <div className={styles.statusBanner}>
                <Check aria-hidden="true" size={16} weight="regular" />
                <span>{status}</span>
              </div>
            )}

            <div className={styles.fieldset}>
              <div className={styles.control}>
                <span className={styles.label}>Connection Status</span>
                <div className={styles.statusCard}>
                  <div className={styles.statusInfo}>
                    <div className={styles.statusIcon}>
                      {methods?.has_google ? (
                        <Check className={styles.statusIconCheck} aria-hidden="true" size={16} weight="regular" />
                      ) : (
                        <X className={styles.statusIconX} aria-hidden="true" size={16} weight="regular" />
                      )}
                    </div>
                    <div className={styles.statusDetails}>
                      <span className={styles.statusText}>
                        {methods?.has_google ? 'Connected' : 'Not Connected'}
                      </span>
                      <span className={styles.statusDescription}>
                        {methods?.has_google 
                          ? 'You can sign in using your Google account'
                          : 'Connect your Google account for quick sign-in'}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              {methods?.has_google && (
                <div className={styles.control}>
                  <span className={styles.label}>Account Actions</span>
                  <button
                    type="button"
                    className={styles.disconnectButton}
                    onClick={() => openDrawer('unlink_google')}
                    disabled={unlinkPending}
                  >
                    {unlinkPending ? (
                      <>
                        <CircleNotch className={styles.buttonSpinner} aria-hidden="true" size={16} weight="regular" />
                        Disconnecting…
                      </>
                    ) : (
                      'Disconnect Google Account'
                    )}
                  </button>
                  <p className={styles.helpText}>
                    Disconnecting will remove Google as a sign-in option. You'll need to use your email and password to sign in.
                  </p>
                </div>
              )}

              {!methods?.has_google && (
                <div className={styles.control}>
                  <span className={styles.label}>Connect Account</span>
                  <button
                    type="button"
                    className={styles.connectButton}
                    onClick={() => {
                      if (methods?.google_link_url) {
                        window.location.href = methods.google_link_url;
                      }
                    }}
                  >
                    Connect Google Account
                  </button>
                  <p className={styles.helpText}>
                    Connect your Google account to enable quick and secure sign-in.
                  </p>
                </div>
              )}
            </div>
          </>
        )}

        <SecurityActionDrawer
          open={drawerAction !== null}
          action={drawerAction ?? 'unlink_google'}
          onClose={closeDrawer}
          onConfirm={handleConfirm}
          isProcessing={drawerProcessing}
          error={drawerError}
        />
      </div>
    );
  }

  // Instagram Integration Inspector
  if (selectedIntegrationId === 'instagram') {
    const instagramData = integrations?.instagram;
    const isConnected = instagramData?.connected ?? false;
    const isExpired = instagramData?.expired ?? false;
    const linkUrl = instagramData?.link_url ?? '';
    const isConfigured = instagramData?.configured !== false;
    
    // Debug logging
    console.log('[IntegrationInspector] Instagram data:', {
      instagramData,
      isConnected,
      isExpired,
      linkUrl: linkUrl ? `${linkUrl.substring(0, 50)}...` : 'EMPTY',
      isConfigured,
      integrationsLoading,
      integrations,
      hasIntegrationsData: !!integrations,
      hasInstagramData: !!instagramData
    });
    
    // Troubleshooting helper
    const troubleshoot = async () => {
      console.group('🔍 Instagram Integration Troubleshooting');
      
      // 1. Check if API call is working
      console.log('1. Testing API endpoint...');
      try {
        const testResponse = await fetch('/api/account/integrations.php?action=get_status', {
          credentials: 'include',
          headers: { 'X-Requested-With': 'XMLHttpRequest' }
        });
        console.log('   Status:', testResponse.status, testResponse.statusText);
        
        // Get the raw response text first to check if it's JSON
        const responseText = await testResponse.text();
        console.log('   Raw response (first 500 chars):', responseText.substring(0, 500));
        
        if (testResponse.status === 401) {
          console.error('   ❌ Authentication failed (401) - You need to log in');
          console.log('   Solution: Go to /login.php and log in again');
        } else if (testResponse.ok) {
          // Try to parse as JSON
          let testData;
          try {
            testData = JSON.parse(responseText);
            console.log('   ✅ API call successful');
            console.log('   Response:', testData);
          } catch (parseError) {
            console.error('   ❌ Response is not valid JSON!');
            console.error('   The API returned HTML/error text instead of JSON');
            console.error('   This usually means there\'s a PHP error in the API endpoint');
            console.error('   Full response:', responseText);
            console.log('   Check PHP error logs for details');
            return; // Exit early
          }
          
          if (testData.success && testData.data?.instagram) {
            const ig = testData.data.instagram;
            console.log('   Instagram config:', {
              configured: ig.configured,
              hasLinkUrl: !!ig.link_url,
              linkUrlLength: ig.link_url?.length || 0,
              connected: ig.connected,
              expired: ig.expired
            });
            
            if (!ig.configured) {
              console.error('   ❌ Instagram not configured on server');
              console.log('   Check: config/meta.php has INSTAGRAM_APP_ID and INSTAGRAM_APP_SECRET');
            } else if (!ig.link_url) {
              console.error('   ❌ link_url is empty even though configured=true');
              console.log('   This means getInstagramAuthUrl() returned empty string');
              console.log('   Check: config/meta.php and config/instagram.php');
            } else {
              console.log('   ✅ Configuration looks good!');
              console.log('   OAuth URL:', ig.link_url.substring(0, 80) + '...');
            }
          }
        } else {
          console.error('   ❌ API call failed:', testResponse.status);
        }
      } catch (error) {
        console.error('   ❌ API call error:', error);
        if (error instanceof SyntaxError && error.message.includes('JSON')) {
          console.error('   The API returned HTML instead of JSON (likely a PHP error)');
          console.log('   Check server error logs: tail -f /path/to/error.log');
          console.log('   Or check PHP error log location in php.ini');
        }
      }
      
      // 2. Check React Query state
      console.log('2. React Query state:');
      console.log('   Loading:', integrationsLoading);
      console.log('   Has data:', !!integrations);
      console.log('   Instagram data:', instagramData);
      
      // 3. Check configuration constants
      console.log('3. Configuration check:');
      console.log('   isConfigured:', isConfigured);
      console.log('   linkUrl exists:', !!linkUrl);
      console.log('   linkUrl length:', linkUrl.length);
      
      console.groupEnd();
    };
    
    // Expose troubleshooting function globally for easy access
    if (typeof window !== 'undefined') {
      (window as any).troubleshootInstagram = troubleshoot;
    }

    return (
      <div 
        className={styles.wrapper}
        style={{ 
          '--active-tab-color': activeColor.text,
          '--active-tab-bg': activeColor.primary,
          '--active-tab-light': activeColor.light,
          '--active-tab-border': activeColor.border
        } as React.CSSProperties}
      >
        <header className={styles.header}>
          <div>
            <h3>Instagram</h3>
            <p>Manage your Instagram integration</p>
          </div>
          <button
            type="button"
            className={styles.closeButton}
            onClick={() => selectIntegration(null)}
            aria-label="Close"
          >
            <X aria-hidden="true" size={20} weight="regular" />
          </button>
        </header>

        {isLoading ? (
          <div className={styles.loadingState}>
            <CircleNotch className={styles.spinner} aria-hidden="true" size={20} weight="regular" />
            <p>Loading…</p>
          </div>
        ) : (
          <>
            {status && (
              <div className={styles.statusBanner}>
                <Check aria-hidden="true" size={16} weight="regular" />
                <span>{status}</span>
              </div>
            )}

            {disconnectInstagramError && (
              <div className={styles.errorBanner}>
                <X aria-hidden="true" size={16} weight="regular" />
                <span>{parseError(disconnectInstagramError)}</span>
              </div>
            )}

            <div className={styles.fieldset}>
              <div className={styles.control}>
                <span className={styles.label}>Connection Status</span>
                <div className={styles.statusCard}>
                  <div className={styles.statusInfo}>
                    <div className={styles.statusIcon}>
                      {isConnected && !isExpired ? (
                        <Check className={styles.statusIconCheck} aria-hidden="true" size={16} weight="regular" />
                      ) : (
                        <X className={styles.statusIconX} aria-hidden="true" size={16} weight="regular" />
                      )}
                    </div>
                    <div className={styles.statusDetails}>
                      <span className={styles.statusText}>
                        {isConnected && !isExpired 
                          ? 'Connected' 
                          : isExpired
                          ? 'Token Expired'
                          : 'Not Connected'}
                      </span>
                      <span className={styles.statusDescription}>
                        {isConnected && !isExpired
                          ? 'Your Instagram posts can be displayed on your page'
                          : isExpired
                          ? 'Your Instagram connection has expired. Please reconnect.'
                          : 'Connect your Instagram account to display your latest posts'}
                      </span>
                    </div>
                  </div>
                </div>
              </div>

              {!isConfigured && !integrationsLoading && (
                <div className={styles.control}>
                  <div className={styles.errorBanner}>
                    <X aria-hidden="true" size={16} weight="regular" />
                    <span>
                      {integrationsLoading 
                        ? 'Loading configuration...' 
                        : instagramData?.configured === false
                        ? 'Instagram integration is not configured on the server. Please check that INSTAGRAM_APP_ID and INSTAGRAM_APP_SECRET are set in config/meta.php'
                        : 'Unable to verify Instagram configuration. Please refresh the page and try again.'}
                    </span>
                  </div>
                  <button
                    type="button"
                    onClick={troubleshoot}
                    style={{
                      marginTop: '12px',
                      padding: '8px 16px',
                      background: 'var(--admin-border-subtle, rgba(15, 23, 42, 0.04))',
                      border: '1px solid var(--admin-border-default, rgba(15, 23, 42, 0.08))',
                      borderRadius: '6px',
                      color: 'var(--admin-text-primary, #0f172a)',
                      cursor: 'pointer',
                      fontSize: '0.875rem'
                    }}
                  >
                    🔍 Run Troubleshooting (Check Console)
                  </button>
                </div>
              )}
              
              {isConfigured && !linkUrl && !integrationsLoading && (
                <div className={styles.control}>
                  <div className={styles.errorBanner}>
                    <X aria-hidden="true" size={16} weight="regular" />
                    <span>
                      Unable to generate Instagram OAuth URL. The configuration may be incomplete. 
                      Please refresh the page or contact support.
                    </span>
                  </div>
                  <button
                    type="button"
                    onClick={troubleshoot}
                    style={{
                      marginTop: '12px',
                      padding: '8px 16px',
                      background: 'var(--admin-border-subtle, rgba(15, 23, 42, 0.04))',
                      border: '1px solid var(--admin-border-default, rgba(15, 23, 42, 0.08))',
                      borderRadius: '6px',
                      color: 'var(--admin-text-primary, #0f172a)',
                      cursor: 'pointer',
                      fontSize: '0.875rem'
                    }}
                  >
                    🔍 Run Troubleshooting (Check Console)
                  </button>
                </div>
              )}

              {isConnected && !isExpired && (
                <div className={styles.control}>
                  <span className={styles.label}>Account Actions</span>
                  <button
                    type="button"
                    className={styles.disconnectButton}
                    onClick={handleDisconnectInstagram}
                    disabled={disconnectInstagramPending}
                  >
                    {disconnectInstagramPending ? (
                      <>
                        <CircleNotch className={styles.buttonSpinner} aria-hidden="true" size={16} weight="regular" />
                        Disconnecting…
                      </>
                    ) : (
                      'Disconnect Instagram Account'
                    )}
                  </button>
                  <p className={styles.helpText}>
                    Disconnecting will remove your Instagram connection. Your Instagram posts will no longer be displayed on your page.
                  </p>
                </div>
              )}

              {(!isConnected || isExpired) && isConfigured && (
                <div className={styles.control}>
                  <span className={styles.label}>Connect Account</span>
                  <button
                    type="button"
                    className={styles.connectButton}
                    onClick={async () => {
                      console.log('[Instagram Connect] Button clicked, linkUrl:', linkUrl);
                      
                      if (linkUrl) {
                        console.log('[Instagram Connect] Redirecting to:', linkUrl);
                        window.location.href = linkUrl;
                        return;
                      }
                      
                      // Try to fetch the link URL directly if it's missing
                      console.log('[Instagram Connect] linkUrl empty, fetching...');
                      try {
                        const response = await fetch('/api/account/integrations.php?action=get_status', {
                          credentials: 'include',
                          headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                          }
                        });
                        
                        console.log('[Instagram Connect] Fetch response status:', response.status);
                        
                        if (response.ok) {
                          const data = await response.json();
                          console.log('[Instagram Connect] Fetch response data:', data);
                          
                          if (data.success && data.data?.instagram?.link_url) {
                            console.log('[Instagram Connect] Found link_url, redirecting to:', data.data.instagram.link_url);
                            window.location.href = data.data.instagram.link_url;
                            return;
                          }
                        } else {
                          const errorText = await response.text();
                          console.error('[Instagram Connect] Fetch failed:', response.status, errorText);
                          
                          if (response.status === 401) {
                            setStatus('Authentication required. Please refresh the page and log in again.');
                            return;
                          }
                        }
                      } catch (error) {
                        console.error('[Instagram Connect] Fetch error:', error);
                        // If it's a JSON parse error, try to get the raw response
                        if (error instanceof SyntaxError && error.message.includes('JSON')) {
                          console.error('[Instagram Connect] API returned non-JSON response (likely PHP error)');
                          console.error('This usually means the API endpoint has a PHP error');
                          console.error('Check the server error logs for details');
                          setStatus('API error: Server returned invalid response. Please check server logs.');
                        }
                      }
                      
                      setStatus('Instagram integration is not configured. Please contact support.');
                    }}
                  >
                    Connect Instagram Account
                  </button>
                  <p className={styles.helpText}>
                    Connect your Instagram account to enable displaying your latest posts on your page.
                  </p>
                </div>
              )}
            </div>
          </>
        )}
      </div>
    );
  }

  // Placeholder for other integrations
  return (
    <div 
      className={styles.wrapper}
      style={{ 
        '--active-tab-color': activeColor.text,
        '--active-tab-bg': activeColor.primary,
        '--active-tab-light': activeColor.light,
        '--active-tab-border': activeColor.border
      } as React.CSSProperties}
    >
      <header className={styles.header}>
        <div>
          <h3>{selectedIntegrationId.charAt(0).toUpperCase() + selectedIntegrationId.slice(1).replace(/-/g, ' ')}</h3>
          <p>Integration settings</p>
        </div>
        <button
          type="button"
          className={styles.closeButton}
          onClick={() => selectIntegration(null)}
          aria-label="Close"
        >
          <X aria-hidden="true" size={16} weight="regular" />
        </button>
      </header>

      <div className={styles.fieldset}>
        <div className={styles.control}>
          <span className={styles.label}>Coming Soon</span>
          <p className={styles.helpText}>
            Settings for this integration will be available here when the feature is enabled.
          </p>
        </div>
      </div>
    </div>
  );
}

