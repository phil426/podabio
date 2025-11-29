import { useState, useEffect } from 'react';
import { Check, X, CircleNotch, ShoppingBag, TrendUp, Storefront, Ticket, Link } from '@phosphor-icons/react';
import * as ScrollArea from '@radix-ui/react-scroll-area';

import { useAuthMethods, useUnlinkGoogleMutation, useRefreshAccountData, useIntegrationsStatus, useDisconnectInstagramMutation } from '../../api/account';
import { usePageSnapshot, updateSocialIcon } from '../../api/page';
import { useQueryClient } from '@tanstack/react-query';
import { SecurityActionDrawer } from '../overlays/SecurityActionDrawer';
import type { SecurityAction } from '../overlays/SecurityActionDrawer';
import { useIntegrationSelection } from '../../state/integrationSelection';
import { useSocialIconSelection } from '../../state/socialIconSelection';
import { queryKeys } from '../../api/utils';

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
    id: 'instagram',
    name: 'Instagram',
    description: 'Connect your Instagram account to display your latest posts.',
    icon: (
      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
      </svg>
    )
  },
  {
    id: 'facebook-pixel',
    name: 'Facebook Pixel',
    description: 'Track conversions and optimize your Facebook ads.',
    icon: (
      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
      </svg>
    )
  },
  {
    id: 'twitter',
    name: 'X / Twitter',
    description: 'Connect your X (Twitter) account to display your latest tweets.',
    icon: (
      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
        <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
      </svg>
    )
  },
  {
    id: 'tiktok',
    name: 'TikTok',
    description: 'Connect your TikTok account to showcase your videos.',
    icon: (
      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
        <path d="M19.59 6.69a4.83 4.83 0 0 1-3.77-4.25V2h-3.45v13.67a2.89 2.89 0 0 1-5.2 1.74 2.89 2.89 0 0 1 2.31-4.64 2.93 2.93 0 0 1 .88.13V9.4a6.84 6.84 0 0 0-1-.05A6.33 6.33 0 0 0 5 20.1a6.34 6.34 0 0 0 10.86-4.43v-7a8.16 8.16 0 0 0 4.77 1.52v-3.4a4.85 4.85 0 0 1-1-.1z"/>
      </svg>
    )
  },
  {
    id: 'amazon',
    name: 'Amazon',
    description: 'Connect your Amazon account to sync products and affiliate links.',
    icon: (
      <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
        <path d="M6.763 12.616c-.19 0-.315-.01-.376-.06-.06-.05-.09-.12-.09-.21 0-.05.01-.09.03-.13.02-.03.05-.05.08-.07.05-.02.1-.03.16-.03.05 0 .1.01.15.02.05.01.09.03.12.05.03.02.05.05.06.08.01.03.02.06.02.09 0 .05-.01.09-.03.12-.02.03-.05.05-.08.06-.03.01-.07.02-.11.02zm-.19-1.05c-.05 0-.09-.01-.13-.02-.04-.01-.07-.03-.09-.05-.02-.02-.03-.05-.03-.08 0-.03.01-.06.02-.08.01-.02.03-.04.05-.05.02-.01.05-.02.08-.02.03 0 .06.01.08.02.02.01.04.03.05.05.01.02.02.05.02.08 0 .03-.01.06-.03.08-.02.02-.05.04-.09.05-.04.01-.08.02-.13.02zm1.24.01c-.05 0-.1-.01-.14-.02-.04-.01-.07-.03-.1-.05-.02-.02-.04-.05-.05-.08-.01-.03-.01-.06 0-.09.01-.03.02-.06.04-.08.02-.02.05-.04.08-.05.03-.01.06-.02.1-.02.03 0 .06.01.09.02.03.01.05.03.07.05.02.02.03.05.04.08.01.03.01.06 0 .09-.01.03-.03.06-.05.08-.02.02-.06.04-.1.05-.04.01-.09.02-.14.02zm11.5-2.13c-.15.05-.3.08-.45.1-.15.02-.3.03-.45.03-.15 0-.3-.01-.45-.03-.15-.02-.3-.05-.45-.1-.15-.05-.28-.11-.4-.19-.12-.08-.22-.17-.3-.27-.08-.1-.14-.21-.19-.33-.05-.12-.08-.24-.1-.37-.02-.13-.03-.26-.03-.4v-2.5c0-.14.01-.27.03-.4.02-.13.05-.25.1-.37.05-.12.11-.23.19-.33.08-.1.18-.19.3-.27.12-.08.25-.14.4-.19.15-.05.3-.08.45-.1.15-.02.3-.03.45-.03.15 0 .3.01.45.03.15.02.3.05.45.1.15.05.28.11.4.19.12.08.22.17.3.27.08.1.14.21.19.33.05.12.08.24.1.37.02.13.03.26.03.4v2.5c0 .14-.01.27-.03.4-.02.13-.05.25-.1.37-.05.12-.11.23-.19.33-.08.1-.18.19-.3.27-.12.08-.25.14-.4.19zm-.5-1.2c.05 0 .1-.01.14-.02.04-.01.07-.03.1-.05.02-.02.04-.05.05-.08.01-.03.01-.06 0-.09-.01-.03-.02-.06-.04-.08-.02-.02-.05-.04-.08-.05-.03-.01-.06-.02-.1-.02-.03 0-.06.01-.09.02-.03.01-.05.03-.07.05-.02.02-.03.05-.04.08-.01.03-.01.06 0 .09.01.03.03.06.05.08.02.02.06.04.1.05.04.01.09.02.14.02z"/>
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

// Platform definitions matching SettingsPanel
const ALL_PLATFORMS: Record<string, string> = {
  apple_podcasts: 'Apple Podcasts',
  spotify: 'Spotify',
  youtube_music: 'YouTube Music',
  iheart_radio: 'iHeart Radio',
  amazon_music: 'Amazon Music',
  pocket_casts: 'Pocket Casts',
  castro: 'Castro',
  overcast: 'Overcast',
  youtube: 'YouTube',
  instagram: 'Instagram',
  twitter: 'Twitter / X',
  tiktok: 'TikTok',
  substack: 'Substack',
  facebook: 'Facebook',
  linkedin: 'LinkedIn',
  reddit: 'Reddit',
  discord: 'Discord',
  twitch: 'Twitch',
  github: 'GitHub',
  dribbble: 'Dribbble',
  medium: 'Medium',
  snapchat: 'Snapchat',
  pinterest: 'Pinterest'
};

export function IntegrationsPanel(): JSX.Element {
  const selectIntegration = useIntegrationSelection((state) => state.selectIntegration);
  const selectedIntegrationId = useIntegrationSelection((state) => state.selectedIntegrationId);
  const selectSocialIcon = useSocialIconSelection((state) => state.selectSocialIcon);
  const { data: methods, isLoading: methodsLoading } = useAuthMethods();
  const { data: integrations, isLoading: integrationsLoading } = useIntegrationsStatus();
  const { data: snapshot } = usePageSnapshot();
  const queryClient = useQueryClient();
  const { mutateAsync: unlinkGoogle, isPending: unlinkPending, error: unlinkError, reset: resetUnlink } = useUnlinkGoogleMutation();
  const { mutateAsync: disconnectInstagram, isPending: disconnectInstagramPending, error: disconnectInstagramError } = useDisconnectInstagramMutation();
  const refreshAccount = useRefreshAccountData();
  const [drawerAction, setDrawerAction] = useState<SecurityAction | null>(null);
  const [status, setStatus] = useState<string | null>(null);
  const [editingIconId, setEditingIconId] = useState<string | null>(null);
  const [editingUrl, setEditingUrl] = useState<string>('');

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

  // For the current Studio experience, we want Google to appear connected by default
  // in the UI, even before the user has explicitly linked it. This keeps the flow
  // focused on "you can disconnect or manage Google sign-in" rather than a blank state.
  const hasGoogle = methods.has_google ?? true;

  const handleSaveSocialIcon = async (iconId: number | string, platformName: string, url: string) => {
    try {
      await updateSocialIcon({
        directory_id: String(iconId),
        platform_name: platformName,
        url: url
      });
      
      setEditingIconId(null);
      setEditingUrl('');
      await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
    } catch (error) {
      console.error('Failed to update social icon:', error);
    }
  };

  return (
    <div className={styles.panel}>
      <ScrollArea.Root className={styles.scrollArea}>
        <ScrollArea.Viewport className={styles.viewport}>
          <div className={styles.content}>
            <header className={styles.header}>
              <h2>Integrations</h2>
              <p>Connect tools that work with your PodaBio page.</p>
            </header>

            <div className={styles.wrapper}>

        <div className={styles.fieldset}>
          <header className={styles.header}>
            <h3 className={styles.title}>Google Authentication</h3>
            <p className={styles.description}>
              Connect your Google account to sign in quickly and securely.
            </p>
          </header>

          {status && (
            <div className={styles.statusBanner}>
              <Check aria-hidden="true" size={16} weight="regular" />
              <span>{status}</span>
            </div>
          )}

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
            <div className={styles.integrationHeader}>
              <div className={styles.integrationInfo}>
                <div className={styles.integrationIcon}>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                  <path
                    d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                    fill="#4285F4"
                  />
                  <path
                    d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                    fill="#34A853"
                  />
                  <path
                    d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                    fill="#FBBC05"
                  />
                  <path
                    d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                    fill="#EA4335"
                  />
                </svg>
                </div>
                <div className={styles.integrationDetails}>
                  <p className={styles.integrationName}>Google</p>
                  <p className={styles.integrationStatus}>
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
                  </p>
                </div>
              </div>
              <div className={styles.integrationActions} onClick={(e) => e.stopPropagation()}>
                {hasGoogle ? (
                  <button
                    type="button"
                    className={styles.disconnectButton}
                    onClick={() => openDrawer('unlink_google')}
                    disabled={unlinkPending}
                  >
                    {unlinkPending ? (
                      <>
                        <CircleNotch className={styles.buttonSpinner} aria-hidden="true" size={16} weight="regular" />
                        <span>Disconnecting…</span>
                      </>
                    ) : (
                      <span>Disconnect</span>
                    )}
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
                  >
                    Connect Google
                  </button>
                )}
              </div>
            </div>
            {hasGoogle && (
              <div className={styles.integrationDescription}>
                <p>You can sign in to your account using your Google credentials.</p>
              </div>
            )}
          </div>
        </div>

        <div className={styles.fieldset}>
          <header className={styles.header}>
            <h3 className={styles.title}>Instagram</h3>
            <p className={styles.description}>
              Connect your Instagram account to display your latest posts.
            </p>
          </header>

          <div 
            className={`${styles.integrationCard} ${selectedIntegrationId === 'instagram' ? styles.integrationCardSelected : ''}`}
            onClick={() => selectIntegration('instagram')}
            role="button"
            tabIndex={0}
            onKeyDown={(e) => {
              if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                selectIntegration('instagram');
              }
            }}
          >
            <div className={styles.integrationHeader}>
              <div className={styles.integrationInfo}>
                <div className={styles.integrationIcon}>
                  <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                  </svg>
                </div>
                <div className={styles.integrationDetails}>
                  <p className={styles.integrationName}>Instagram</p>
                  <p className={styles.integrationStatus}>
                    {integrationsLoading ? (
                      <>
                        <CircleNotch className={styles.statusIcon} aria-hidden="true" size={16} weight="regular" />
                        <span>Loading…</span>
                      </>
                    ) : integrations && integrations.instagram ? (
                      integrations.instagram.connected ? (
                        <>
                          <Check className={styles.statusIcon} aria-hidden="true" size={16} weight="regular" />
                          <span>{integrations.instagram.expired ? 'Token expired' : 'Connected'}</span>
                        </>
                      ) : (
                        <>
                          <X className={styles.statusIcon} aria-hidden="true" size={16} weight="regular" />
                          <span>Not connected</span>
                        </>
                      )
                    ) : (
                      <>
                        <X className={styles.statusIcon} aria-hidden="true" size={16} weight="regular" />
                        <span>Not connected</span>
                      </>
                    )}
                  </p>
                </div>
              </div>
            </div>
            {/* Note: Connect/Disconnect buttons are handled in the IntegrationInspector drawer */}
            {integrations && integrations.instagram && integrations.instagram.connected && (
              <div className={styles.integrationDescription}>
                <p>Your Instagram posts can be displayed on your page.</p>
              </div>
            )}
            {integrations && integrations.instagram && integrations.instagram.configured === false && (
              <div className={styles.integrationDescription}>
                <p style={{ color: '#dc2626', fontSize: '0.8rem' }}>
                  Instagram integration is not configured. Please configure your Instagram App ID and Secret in the server configuration.
                </p>
              </div>
            )}
          </div>

          {disconnectInstagramError && (
            <div className={styles.errorBanner}>
              <X aria-hidden="true" size={16} weight="regular" />
              <span>{parseError(disconnectInstagramError)}</span>
            </div>
          )}
        </div>

        <div className={styles.fieldset}>
        <header className={styles.header}>
          <h3 className={styles.title}>More integrations</h3>
          <p className={styles.description}>Additional integrations will be available soon.</p>
        </header>

        <div className={styles.integrationsGrid}>
          {integrationPlaceholders.filter(p => p.id !== 'instagram').map((integration) => (
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

      {/* Social Icons Section */}
      <div className={styles.fieldset}>
        <header className={styles.header}>
          <h3 className={styles.title}>Social Icons</h3>
          <p className={styles.description}>
            Manage your social media links and platform URLs.
          </p>
        </header>

        {snapshot?.social_icons && snapshot.social_icons.length > 0 ? (
          <div className={styles.socialIconsList}>
            {snapshot.social_icons.map((icon) => {
              const isEditing = editingIconId === String(icon.id);
              const platformName = ALL_PLATFORMS[icon.platform_name] || icon.platform_name;
              
              return (
                <div 
                  key={icon.id}
                  className={`${styles.socialIconCard} ${selectedIntegrationId === `social-${icon.id}` ? styles.socialIconCardSelected : ''}`}
                  onClick={() => {
                    if (!isEditing) {
                      selectIntegration(`social-${icon.id}`);
                      selectSocialIcon(String(icon.id));
                    }
                  }}
                  role="button"
                  tabIndex={0}
                  onKeyDown={(e) => {
                    if ((e.key === 'Enter' || e.key === ' ') && !isEditing) {
                      e.preventDefault();
                      selectIntegration(`social-${icon.id}`);
                      selectSocialIcon(String(icon.id));
                    }
                  }}
                >
                  <div className={styles.socialIconHeader}>
                    <div className={styles.socialIconInfo}>
                      <div className={styles.socialIconIcon}>
                        <Link aria-hidden="true" size={16} weight="regular" />
                      </div>
                      <div className={styles.socialIconDetails}>
                        <p className={styles.socialIconName}>{platformName}</p>
                        {isEditing ? (
                          <div className={styles.socialIconUrlEdit}>
                            <input
                              type="url"
                              value={editingUrl}
                              onChange={(e) => setEditingUrl(e.target.value)}
                              placeholder="https://..."
                              className={styles.urlInput}
                              onClick={(e) => e.stopPropagation()}
                              onKeyDown={(e) => {
                                if (e.key === 'Enter') {
                                  e.preventDefault();
                                  handleSaveSocialIcon(icon.id, icon.platform_name, editingUrl);
                                } else if (e.key === 'Escape') {
                                  e.preventDefault();
                                  setEditingIconId(null);
                                  setEditingUrl('');
                                }
                              }}
                              autoFocus
                            />
                            <div className={styles.urlEditActions}>
                              <button
                                type="button"
                                className={styles.saveButton}
                                onClick={(e) => {
                                  e.stopPropagation();
                                  handleSaveSocialIcon(icon.id, icon.platform_name, editingUrl);
                                }}
                              >
                                <Check aria-hidden="true" size={16} weight="regular" />
                              </button>
                              <button
                                type="button"
                                className={styles.cancelButton}
                                onClick={(e) => {
                                  e.stopPropagation();
                                  setEditingIconId(null);
                                  setEditingUrl('');
                                }}
                              >
                                <X aria-hidden="true" size={16} weight="regular" />
                              </button>
                            </div>
                          </div>
                        ) : (
                          <p className={styles.socialIconUrl}>
                            {icon.url || <span className={styles.noUrl}>No URL set</span>}
                          </p>
                        )}
                      </div>
                    </div>
                    {!isEditing && (
                      <div className={styles.socialIconActions} onClick={(e) => e.stopPropagation()}>
                        <button
                          type="button"
                          className={styles.editButton}
                          onClick={() => {
                            setEditingIconId(String(icon.id));
                            setEditingUrl(icon.url || '');
                          }}
                        >
                          Edit
                        </button>
                      </div>
                    )}
                  </div>
                  {icon.is_active === 0 && (
                    <div className={styles.socialIconStatus}>
                      <X className={styles.statusIcon} aria-hidden="true" size={16} weight="regular" />
                      <span>Hidden</span>
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        ) : (
          <div className={styles.emptyState}>
            <p>No social icons added yet. Add them from the Settings tab.</p>
          </div>
        )}
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

