import { useState, useEffect } from 'react';
import { Check, X, CircleNotch, ShoppingBag, TrendUp, Storefront, Ticket, Envelope } from '@phosphor-icons/react';
import * as ScrollArea from '@radix-ui/react-scroll-area';

import { useAuthMethods, useUnlinkGoogleMutation, useRefreshAccountData, useIntegrationsStatus } from '../../api/account';
import { usePageSnapshot, updateSocialIcon } from '../../api/page';
import { useQueryClient } from '@tanstack/react-query';
import { SecurityActionDrawer } from '../overlays/SecurityActionDrawer';
import type { SecurityAction } from '../overlays/SecurityActionDrawer';
import { useIntegrationSelection } from '../../state/integrationSelection';
import { useSocialIconSelection } from '../../state/socialIconSelection';
import { queryKeys } from '../../api/utils';
import { EmailSubscriptionSettings } from './EmailSubscriptionSettings';

import styles from './integrations-panel.module.css';

interface IntegrationPlaceholder {
  id: string;
  name: string;
  description: string;
  icon: JSX.Element;
}

const integrationPlaceholders: IntegrationPlaceholder[] = [
  {
    id: 'shopify',
    name: 'Shopify',
    description: 'Connect your Shopify store to sync products and orders.',
    icon: <ShoppingBag aria-hidden="true" size={20} weight="regular" />
  },
  {
    id: 'facebook-pixel',
    name: 'Facebook Pixel',
    description: 'Track conversions and optimize your Facebook ads.',
    icon: (
      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z" />
      </svg>
    )
  },
  {
    id: 'twitter',
    name: 'X / Twitter',
    description: 'Connect your X (Twitter) account to display your latest tweets.',
    icon: (
      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z" />
      </svg>
    )
  },
  {
    id: 'tiktok',
    name: 'TikTok',
    description: 'Connect your TikTok account to showcase your videos.',
    icon: (
      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
        <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z" />
      </svg>
    )
  },
  {
    id: 'amazon',
    name: 'Amazon',
    description: 'Connect your Amazon account to sync products and affiliate links.',
    icon: (
      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
        <path d="M6.763 12.616c-.19 0-.315-.01-.376-.06-.06-.05-.09-.12-.09-.21 0-.05.01-.09.03-.13.02-.03.05-.05.08-.07.05-.02.1-.03.16-.03.05 0 .1.01.15.02.05.01.09.03.12.05.03.02.05.05.06.08.01.03.02.06.02.09 0 .05-.01.09-.03.12-.02.03-.05.05-.08.06-.03.01-.07.02-.11.02zm-.19-1.05c-.05 0-.09-.01-.13-.02-.04-.01-.07-.03-.09-.05-.02-.02-.03-.05-.03-.08 0-.03.01-.06.02-.08.01-.02.03-.04.05-.05.02-.01.05-.02.08-.02.03 0 .06.01.08.02.02.01.04.03.05.05.01.02.02.05.02.08 0 .03-.01.06-.03.08-.02.02-.05.04-.09.05-.04.01-.08.02-.13.02zm1.24.01c-.05 0-.1-.01-.14-.02-.04-.01-.07-.03-.1-.05-.02-.02-.04-.05-.05-.08-.01-.03-.01-.06 0-.09.01-.03.02-.06.04-.08.02-.02.05-.04.08-.05.03-.01.06-.02.1-.02.03 0 .06.01.09.02.03.01.05.03.07.05.02.02.03.05.04.08.01.03.01.06 0 .09-.01.03-.03.06-.05.08-.02.02-.06.04-.1.05-.04.01-.09.02-.14.02zm11.5-2.13c-.15.05-.3.08-.45.1-.15.02-.3.03-.45.03-.15 0-.3-.01-.45-.03-.15-.02-.3-.05-.45-.1-.15-.05-.28-.11-.4-.19-.12-.08-.22-.17-.3-.27-.08-.1-.14-.21-.19-.33-.05-.12-.08-.24-.1-.37-.02-.13-.03-.26-.03-.4v-2.5c0-.14.01-.27.03-.4.02-.13.05-.25.1-.37.05-.12.11-.23.19-.33.08-.1.18-.19.3-.27.12-.08.25-.14.4-.19.15-.05.3-.08.45-.1.15-.02.3-.03.45-.03.15 0 .3.01.45.03.15.02.3.05.45.1.15.05.28.11.4.19.12.08.22.17.3.27.08.1.14.21.19.33.05.12.08.24.1.37.02.13.03.26.03.4v2.5c0 .14-.01.27-.03.4-.02.13-.05.25-.1.37-.05.12-.11.23-.19.33-.08.1-.18.19-.3.27-.12.08-.25.14-.4.19zm-.5-1.2c.05 0 .1-.01.14-.02.04-.01.07-.03.1-.05.02-.02.04-.05.05-.08.01-.03.01-.06 0-.09-.01-.03-.02-.06-.04-.08-.02-.02-.05-.04-.08-.05-.03-.01-.06-.02-.1-.02-.03 0-.06.01-.09.02-.03.01-.05.03-.07.05-.02.02-.03.05-.04.08-.01.03-.01.06 0 .09.01.03.03.06.05.08.02.02.06.04.1.05.04.01.09.02.14.02z" />
      </svg>
    )
  },
  {
    id: 'printful',
    name: 'Printful',
    description: 'Connect Printful to sync your print-on-demand products.',
    icon: <Storefront aria-hidden="true" size={20} weight="regular" />
  },
  {
    id: 'printfy',
    name: 'Printfy',
    description: 'Connect Printfy to manage your print-on-demand store.',
    icon: <Storefront aria-hidden="true" size={20} weight="regular" />
  },
  {
    id: 'gelato',
    name: 'Gelato',
    description: 'Connect Gelato to sync your print products and orders.',
    icon: <Storefront aria-hidden="true" size={20} weight="regular" />
  },
  {
    id: 'google-analytics',
    name: 'Google Analytics',
    description: 'Connect Google Analytics to track your page performance.',
    icon: <TrendUp aria-hidden="true" size={20} weight="regular" />
  },
  {
    id: 'etsy',
    name: 'Etsy',
    description: 'Connect your Etsy shop to display your products.',
    icon: <ShoppingBag aria-hidden="true" size={20} weight="regular" />
  },
  {
    id: 'eventbrite',
    name: 'Eventbrite',
    description: 'Connect your Eventbrite account to display your upcoming events.',
    icon: <Ticket aria-hidden="true" size={20} weight="regular" />
  }
];

function parseError(error: unknown): string | null {
  if (!error) return null;
  if (error instanceof Error) return error.message;
  if (typeof error === 'object' && 'message' in error) return String(error.message);
  return 'An error occurred';
}

import { ALL_PLATFORMS, PODCAST_KEYS, SOCIAL_KEYS } from './social-platforms';
import { getPlatformIcon } from './social-icons';

export function IntegrationsPanel(): JSX.Element {
  const selectIntegration = useIntegrationSelection((state) => state.selectIntegration);
  const selectedIntegrationId = useIntegrationSelection((state) => state.selectedIntegrationId);
  const selectSocialIcon = useSocialIconSelection((state) => state.selectSocialIcon);
  const selectedSocialIconId = useSocialIconSelection((state) => state.selectedSocialIconId);
  const { data: methods, isLoading: methodsLoading } = useAuthMethods();
  const { data: integrations, isLoading: integrationsLoading } = useIntegrationsStatus();
  const { data: snapshot } = usePageSnapshot();
  const queryClient = useQueryClient();
  const { mutateAsync: unlinkGoogle, isPending: unlinkPending, error: unlinkError, reset: resetUnlink } = useUnlinkGoogleMutation();
  const refreshAccount = useRefreshAccountData();
  const [drawerAction, setDrawerAction] = useState<SecurityAction | null>(null);
  const [status, setStatus] = useState<string | null>(null);

  const isLoading = methodsLoading || integrationsLoading;

  // Handle URL parameters for success/error messages from OAuth callbacks
  useEffect(() => {
    const urlParams = new URLSearchParams(window.location.search);
    const success = urlParams.get('success');
    const error = urlParams.get('error');

    if (success) {
      setStatus(success);
      // Clear URL parameter
      const newUrl = window.location.pathname + window.location.hash;
      window.history.replaceState({}, '', newUrl);
    } else if (error) {
      setStatus(error);
      // Clear URL parameter
      const newUrl = window.location.pathname + window.location.hash;
      window.history.replaceState({}, '', newUrl);
    }

    // Refresh integrations status after OAuth callback
    if (success || error) {
      queryClient.invalidateQueries({ queryKey: ['integrations', 'status'] });
      refreshAccount.mutateAsync();
    }
  }, [queryClient, refreshAccount]);

  if (isLoading) {
    return (
      <div className={styles.panel}>
        <ScrollArea.Root className={styles.scrollArea}>
          <ScrollArea.Viewport className={styles.viewport}>
            <div className={styles.content}>
              <div className={styles.loadingState}>
                <CircleNotch className={styles.spinner} size={20} weight="regular" />
                <p>Loading integrations…</p>
              </div>
            </div>
          </ScrollArea.Viewport>
          <ScrollArea.Scrollbar orientation="vertical" className={styles.scrollbar}>
            <ScrollArea.Thumb className={styles.thumb} />
          </ScrollArea.Scrollbar>
        </ScrollArea.Root>
      </div>
    );
  }

  if (!methods) {
    return (
      <div className={styles.panel}>
        <ScrollArea.Root className={styles.scrollArea}>
          <ScrollArea.Viewport className={styles.viewport}>
            <div className={styles.content}>
              <div className={styles.errorState}>
                <p>We couldn't load your integrations. Try again later.</p>
              </div>
            </div>
          </ScrollArea.Viewport>
          <ScrollArea.Scrollbar orientation="vertical" className={styles.scrollbar}>
            <ScrollArea.Thumb className={styles.thumb} />
          </ScrollArea.Scrollbar>
        </ScrollArea.Root>
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


  const drawerError = drawerAction === 'unlink_google' ? parseError(unlinkError) : null;
  const drawerProcessing = drawerAction === 'unlink_google' ? unlinkPending : false;

  // For the current Studio experience, we want Google to appear connected by default
  // in the UI, even before the user has explicitly linked it. This keeps the flow
  // focused on "you can disconnect or manage Google sign-in" rather than a blank state.
  const hasGoogle = methods.has_google ?? true;

  // Compute social icons map
  const socialIconsMap = new Map();
  snapshot?.social_icons?.forEach(icon => {
    socialIconsMap.set(icon.platform_name, icon);
  });

  const getSortedKeys = (keys: string[]) => {
    return [...keys].sort((keyA, keyB) => {
      const hasA = socialIconsMap.has(keyA);
      const hasB = socialIconsMap.has(keyB);
      if (hasA && !hasB) return -1;
      if (!hasA && hasB) return 1;
      return 0;
    });
  };

  const podcastKeys = getSortedKeys(PODCAST_KEYS);
  const socialKeys = getSortedKeys(SOCIAL_KEYS);

  const renderSocialGrid = (keys: string[]) => (
    <div className={styles.integrationsGrid}>
      {keys.map((platformKey) => {
        const platformName = ALL_PLATFORMS[platformKey];
        const icon = socialIconsMap.get(platformKey);
        const isConfigured = Boolean(icon);
        const isConnected = isConfigured && Boolean(icon.url);
        const isVisible = isConfigured ? icon.is_active !== 0 : false;
        const id = isConfigured ? String(icon.id) : `new:${platformKey}`;

        return (
          <div
            key={platformKey}
            className={`${styles.socialIconCard} ${selectedSocialIconId === id ? styles.socialIconCardSelected : ''}`}
            onClick={() => {
              selectSocialIcon(id);
            }}
            role="button"
            tabIndex={0}
            onKeyDown={(e) => {
              if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                selectSocialIcon(id);
              }
            }}
          >
            <div className={styles.socialIconHeader}>
              <div className={styles.socialIconIcon}>
                {getPlatformIcon(platformKey)}
              </div>
              <div className={styles.socialIconDetails}>
                <p className={styles.socialIconName}>{platformName}</p>
                <p className={styles.socialIconUrl}>
                  {isConfigured && icon.url ? icon.url : <span className={styles.noUrl}>Not configured</span>}
                </p>
              </div>
            </div>

            <div className={styles.socialIconCardFooter}>
              <div className={styles.socialIconStatus}>
                {!isConfigured ? (
                  <>
                    <X className={styles.statusIcon} aria-hidden="true" size={12} weight="regular" />
                    <span>Not configured</span>
                  </>
                ) : !isVisible ? (
                  <>
                    <X className={styles.statusIcon} aria-hidden="true" size={12} weight="regular" />
                    <span style={{ opacity: 0.7 }}>Hidden</span>
                  </>
                ) : isConnected ? (
                  <>
                    <Check className={styles.statusIcon} aria-hidden="true" size={12} weight="regular" style={{ color: '#00FF7F' }} />
                    <span>Connected</span>
                  </>
                ) : (
                  <>
                    <X className={styles.statusIcon} aria-hidden="true" size={12} weight="regular" />
                    <span>Not configured</span>
                  </>
                )}
              </div>
            </div>
          </div>
        );
      })}
    </div>
  );

  return (
    <div className={styles.panel}>
      <ScrollArea.Root className={styles.scrollArea}>
        <ScrollArea.Viewport className={styles.viewport}>
          <div className={styles.content}>
            <header className={styles.header}>
              <h2>Integrations</h2>
              <p>Connect tools that work with your PodaBio page.</p>
            </header>

            {status && (
              <div className={styles.statusBanner} style={{ marginBottom: '1.5rem' }}>
                <Check aria-hidden="true" size={16} weight="regular" />
                <span>{status}</span>
              </div>
            )}

            <div className={styles.wrapper}>
              {/* Social Icons Section */}
              <div className={styles.fieldset}>
                <header className={styles.header}>
                  <h3 className={styles.title}>Social Icons</h3>
                  <p className={styles.description}>
                    Manage your social media links and platform URLs.
                  </p>
                </header>

                <div style={{ marginTop: '1rem' }}>
                  <h4 style={{
                    fontSize: '0.85rem',
                    fontWeight: 600,
                    color: 'var(--admin-text-secondary)',
                    textTransform: 'uppercase',
                    letterSpacing: '0.05em',
                    marginBottom: '0.75rem'
                  }}>
                    Podcast Platforms
                  </h4>
                  {renderSocialGrid(podcastKeys)}
                </div>

                <div style={{ marginTop: '1.5rem' }}>
                  <h4 style={{
                    fontSize: '0.85rem',
                    fontWeight: 600,
                    color: 'var(--admin-text-secondary)',
                    textTransform: 'uppercase',
                    letterSpacing: '0.05em',
                    marginBottom: '0.75rem'
                  }}>
                    Social Media
                  </h4>
                  {renderSocialGrid(socialKeys)}
                </div>
              </div>

              <div className={styles.fieldset}>
                <header className={styles.header}>
                  <h3 className={styles.title}>Active integrations</h3>
                  <p className={styles.description}>Manage your connected tools and services.</p>
                </header>

                <div className={styles.integrationsGrid}>
                  {/* Email Subscription Card */}
                  <div
                    className={`${styles.integrationCard} ${selectedIntegrationId === 'email-subscription' ? styles.integrationCardSelected : ''}`}
                    onClick={() => selectIntegration('email-subscription')}
                    role="button"
                    tabIndex={0}
                    onKeyDown={(e) => {
                      if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        selectIntegration('email-subscription');
                      }
                    }}
                  >
                    <div className={styles.placeholderHeader}>
                      <div className={styles.integrationIcon}>
                        <Envelope aria-hidden="true" size={20} weight="regular" />
                      </div>
                      <div className={styles.integrationDetails}>
                        <p className={styles.integrationName}>Email Subscription</p>
                        <p className={styles.placeholderDescription}>
                          Configure your email marketing service to collect subscribers.
                        </p>
                      </div>
                    </div>

                    <div className={styles.integrationStatus} style={{ marginTop: 'auto', paddingTop: '0.75rem' }}>
                      {snapshot?.page?.email_service_provider ? (
                        <>
                          <Check className={styles.statusIcon} aria-hidden="true" size={16} weight="regular" />
                          <span>Configured ({snapshot.page.email_service_provider})</span>
                        </>
                      ) : (
                        <>
                          <X className={styles.statusIcon} aria-hidden="true" size={16} weight="regular" />
                          <span>Not configured</span>
                        </>
                      )}
                    </div>
                  </div>

                  {/* Google Auth Card */}
                  <div
                    className={`${styles.integrationCard} ${selectedIntegrationId === 'google' ? styles.integrationCardSelected : ''}`}
                    onClick={() => selectIntegration('google')}
                    role="button"
                    tabIndex={0}
                    onKeyDown={(e) => {
                      if (e.key === 'Enter' || e.key === ' ') {
                        e.preventDefault();
                        selectIntegration('google');
                      }
                    }}
                  >
                    <div className={styles.placeholderHeader}>
                      <div className={styles.integrationIcon}>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                          <path d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z" fill="#4285F4" />
                          <path d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z" fill="#34A853" />
                          <path d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z" fill="#FBBC05" />
                          <path d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z" fill="#EA4335" />
                        </svg>
                      </div>
                      <div className={styles.integrationDetails}>
                        <p className={styles.integrationName}>Google</p>
                        <p className={styles.placeholderDescription}>
                          Sign in to your account using your Google credentials.
                        </p>
                      </div>
                    </div>

                    <div style={{ marginTop: 'auto', paddingTop: '0.75rem', display: 'flex', alignItems: 'center', justifyContent: 'space-between', gap: '0.5rem' }}>
                      <div className={styles.integrationStatus}>
                        {hasGoogle ? (
                          <>
                            <Check className={styles.statusIcon} aria-hidden="true" size={16} weight="regular" />
                            <span>Connected</span>
                          </>
                        ) : (
                          <>
                            <X className={styles.statusIcon} aria-hidden="true" size={16} weight="regular" />
                            <span>Not connected</span>
                          </>
                        )}
                      </div>
                      <div className={styles.integrationActions} onClick={(e) => e.stopPropagation()}>
                        {hasGoogle ? (
                          <button
                            type="button"
                            className={styles.disconnectButton}
                            onClick={() => openDrawer('unlink_google')}
                            disabled={unlinkPending}
                            style={{ padding: '0.25rem 0.5rem', fontSize: '0.75rem' }}
                          >
                            Disconnect
                          </button>
                        ) : (
                          <button
                            type="button"
                            className={styles.connectButton}
                            onClick={() => {
                              if (methods.google_link_url) {
                                window.location.href = methods.google_link_url;
                              }
                            }}
                            style={{ padding: '0.25rem 0.5rem', fontSize: '0.75rem' }}
                          >
                            Connect
                          </button>
                        )}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              {/* More Integrations Section */}
              <div className={styles.fieldset}>
                <header className={styles.header}>
                  <h3 className={styles.title}>More integrations</h3>
                  <p className={styles.description}>Additional integrations will be available soon.</p>
                </header>

                <div className={styles.integrationsGrid}>
                  {integrationPlaceholders.map((integration) => (
                    <div
                      key={integration.id}
                      className={`${styles.placeholderCard} ${selectedIntegrationId === integration.id ? styles.placeholderCardSelected : ''}`}
                      onClick={() => selectIntegration(integration.id)}
                      role="button"
                      tabIndex={0}
                      onKeyDown={(e) => {
                        if (e.key === 'Enter' || e.key === ' ') {
                          e.preventDefault();
                          selectIntegration(integration.id);
                        }
                      }}
                    >
                      <div className={styles.placeholderHeader}>
                        <div className={styles.placeholderIcon}>{integration.icon}</div>
                        <div className={styles.placeholderDetails}>
                          <p className={styles.placeholderName}>{integration.name}</p>
                          <p className={styles.placeholderDescription}>{integration.description}</p>
                        </div>
                      </div>
                      <div className={styles.placeholderBadge}>
                        <span>Coming soon</span>
                      </div>
                    </div>
                  ))}
                </div>
              </div>

              <SecurityActionDrawer
                open={drawerAction !== null}
                action={drawerAction ?? 'unlink_google'}
                onClose={closeDrawer}
                onConfirm={handleConfirm}
                isProcessing={drawerProcessing}
                error={drawerError}
              />
            </div>
          </div>
        </ScrollArea.Viewport>
        <ScrollArea.Scrollbar orientation="vertical" className={styles.scrollbar}>
          <ScrollArea.Thumb className={styles.thumb} />
        </ScrollArea.Scrollbar>
      </ScrollArea.Root>
    </div>
  );
}

