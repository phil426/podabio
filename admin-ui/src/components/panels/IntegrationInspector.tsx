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

  // Instagram Integration Inspector (placeholder for future)
  if (selectedIntegrationId === 'instagram') {
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

        <div className={styles.fieldset}>
          <div className={styles.control}>
            <span className={styles.label}>Coming Soon</span>
            <p className={styles.helpText}>
              Instagram integration settings will be available here when the feature is enabled.
            </p>
          </div>
        </div>
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

