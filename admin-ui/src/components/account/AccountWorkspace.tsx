import * as Tabs from '@radix-ui/react-tabs';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';

import {
  useAccountProfile,
  useAuthMethods,
  useSubscriptionStatus,
  useCreatePageMutation,
  useRemovePasswordMutation,
  useUnlinkGoogleMutation
} from '../../api/account';
import { usePageSnapshot } from '../../api/page';
import { ApiError } from '../../api/http';
import type { AccountProfile } from '../../api/types';
import { MigrationEducationBanner } from './MigrationEducationBanner';
import { BillingPanel } from './BillingPanel';
import { trackTelemetry } from '../../services/telemetry';
import { CreatePageDrawer } from '../overlays/CreatePageDrawer';
import { SecurityActionDrawer, type SecurityAction } from '../overlays/SecurityActionDrawer';
import styles from './account-workspace.module.css';

const TAB_DEFAULT = 'profile';
const VALID_TABS = new Set(['profile', 'security', 'billing']);

interface ProfileTabProps {
  profile?: AccountProfile | null;
  isLoading: boolean;
  pageMissing: boolean;
  isPageLoading: boolean;
  pageUsername: string | null;
  onCreatePage: () => void;
  createStatus?: string | null;
}

export function AccountWorkspace(): JSX.Element {
  const location = useLocation();
  const navigate = useNavigate();

  const activeTab = useMemo(() => {
    const segments = location.pathname.split('/').filter(Boolean);
    if (segments[0] !== 'account') {
      return TAB_DEFAULT;
    }
    const candidate = segments[1] ?? TAB_DEFAULT;
    return VALID_TABS.has(candidate) ? candidate : TAB_DEFAULT;
  }, [location.pathname]);

  useEffect(() => {
    if (location.pathname === '/account' || location.pathname === '/account/') {
      navigate('/account/profile', { replace: true });
    }
  }, [location.pathname, navigate]);

  useEffect(() => {
    if (location.pathname.startsWith('/account')) {
      trackTelemetry({ event: 'account.workspace_view', metadata: { tab: activeTab } });
    }
  }, [activeTab, location.pathname]);

  const profileQuery = useAccountProfile();
  const pageQuery = usePageSnapshot();
  const pageMissing = isNotFound(pageQuery.error);
  const pageUsername = pageQuery.data?.page?.username ?? null;

  const suggestedUsername = useMemo(() => {
    const profile = profileQuery.data;
    if (!profile) {
      return null;
    }
    const nameCandidate = (profile.name ?? '').trim();
    const emailCandidate =
      profile.email && profile.email.includes('@') ? profile.email.split('@')[0] ?? '' : '';
    const baseSource = nameCandidate !== '' ? nameCandidate : emailCandidate;
    if (!baseSource) {
      return null;
    }
    const slug = baseSource
      .toLowerCase()
      .replace(/[^a-z0-9-_]/g, '-')
      .replace(/-{2,}/g, '-')
      .replace(/^-|-$/g, '');
    return slug.length >= 3 ? slug : null;
  }, [profileQuery.data]);

  const [createDrawerOpen, setCreateDrawerOpen] = useState(false);
  const { mutateAsync: createPage, isPending: createPending, error: createError, reset: resetCreate } = useCreatePageMutation();
  const [createStatus, setCreateStatus] = useState<string | null>(null);
  const createErrorMessage = parseError(createError);

  const handleOpenCreateDrawer = useCallback(() => {
    resetCreate();
    setCreateStatus(null);
    setCreateDrawerOpen(true);
  }, [resetCreate]);

  const handleCloseCreateDrawer = useCallback(() => {
    setCreateDrawerOpen(false);
    resetCreate();
  }, [resetCreate]);

  const handleCreatePageSubmit = useCallback(
    async (username: string) => {
      try {
        await createPage(username);
        setCreateStatus('Page created successfully. You can now customize it in the Studio.');
        setCreateDrawerOpen(false);
        trackTelemetry({ event: 'account.create_page', metadata: { source: 'account_workspace' } });
      } catch {
        // handled via mutation error state
      }
    },
    [createPage]
  );

  const handleTabChange = (value: string) => {
    if (!VALID_TABS.has(value)) return;
    trackTelemetry({ event: 'account.workspace_tab_change', metadata: { tab: value } });
    navigate(`/account/${value}`);
  };

  return (
    <div className={styles.container}>
      <header className={styles.header}>
        <div>
          <h1>Account settings</h1>
          <p>Keep your profile, security, and billing details up to date.</p>
        </div>
      </header>
      <MigrationEducationBanner />

      <Tabs.Root className={styles.tabsRoot} value={activeTab} onValueChange={handleTabChange}>
        <Tabs.List className={styles.tabList} aria-label="Account sections">
          <Tabs.Trigger value="profile">Profile</Tabs.Trigger>
          <Tabs.Trigger value="security">Security</Tabs.Trigger>
          <Tabs.Trigger value="billing">Billing</Tabs.Trigger>
        </Tabs.List>

        <Tabs.Content className={styles.tabContent} value="profile">
          <ProfileTab
            profile={profileQuery.data}
            isLoading={profileQuery.isLoading}
            pageMissing={pageMissing}
            isPageLoading={pageQuery.isLoading}
            pageUsername={pageUsername}
            onCreatePage={handleOpenCreateDrawer}
            createStatus={createStatus}
          />
        </Tabs.Content>

        <Tabs.Content className={styles.tabContent} value="security">
          <SecurityTab />
        </Tabs.Content>

        <Tabs.Content className={styles.tabContent} value="billing">
          <BillingPanel />
        </Tabs.Content>
      </Tabs.Root>

      <CreatePageDrawer
        open={createDrawerOpen}
        onClose={handleCloseCreateDrawer}
        onSubmit={handleCreatePageSubmit}
        isProcessing={createPending}
        error={createErrorMessage}
        suggestedUsername={suggestedUsername}
      />
    </div>
  );
}

function ProfileTab({
  profile,
  isLoading,
  pageMissing,
  isPageLoading,
  pageUsername,
  onCreatePage,
  createStatus
}: ProfileTabProps): JSX.Element {
  if (isLoading) {
    return <p className={styles.emptyState}>Loading profile…</p>;
  }

  if (!profile) {
    return <p className={styles.emptyState}>We couldn’t load your profile right now. Please try again shortly.</p>;
  }

  const livePageUrl = pageUsername ? `${window.__APP_URL__ ?? ''}/${pageUsername}` : null;

  return (
    <div className={styles.sectionStack}>
      {createStatus && <div className={styles.statusBanner}>{createStatus}</div>}

      <section className={styles.card} data-account-section="profile">
        <header>
          <h2>Basics</h2>
          <p>Update how your account appears across PodaBio Studio.</p>
        </header>
        <div className={styles.fieldGrid}>
          <div className={styles.fieldRow}>
            <span className={styles.fieldLabel}>Display name</span>
            <span className={styles.fieldValue}>{profile.name || '—'}</span>
          </div>
          <div className={styles.fieldRow}>
            <span className={styles.fieldLabel}>Email</span>
            <span className={styles.fieldValue}>{profile.email}</span>
            <button
              type="button"
              className={styles.fieldAction}
              onClick={() => copyToClipboard(profile.email)}
              title="Copy your account email address"
            >
              Copy
            </button>
          </div>
        </div>
        <p className={styles.helperNote}>Display name and email edits will arrive in an upcoming release.</p>
      </section>

      <section className={styles.card}>
        <header>
          <h2>{pageMissing ? 'Launch your PodaBio page' : 'Public presence'}</h2>
          <p>
            {pageMissing
              ? 'Create a page to claim your public username and unlock the Studio editor.'
              : 'Your username powers the URL listeners use to reach your page.'}
          </p>
        </header>
        <div className={styles.fieldGrid}>
          <div className={styles.fieldRow}>
            <span className={styles.fieldLabel}>Username</span>
            <span className={styles.fieldValue}>
              {pageMissing ? (
                'No page yet'
              ) : isPageLoading ? (
                'Loading…'
              ) : pageUsername ? (
                livePageUrl ? (
                  <a
                    href={livePageUrl}
                    target="_blank"
                    rel="noopener noreferrer"
                    className={styles.usernameLink}
                    onClick={(e) => {
                      e.preventDefault();
                      window.open(livePageUrl, '_blank', 'noopener');
                    }}
                  >
                    /{pageUsername}
                  </a>
                ) : (
                  `/${pageUsername}`
                )
              ) : (
                '—'
              )}
            </span>
          </div>
        </div>
        <footer className={styles.cardFooter}>
          {pageMissing ? (
            <button
              type="button"
              className={styles.primaryButton}
              onClick={onCreatePage}
              title="Create your first public PodaBio page"
            >
              Create page
            </button>
          ) : (
            <button
              type="button"
              className={styles.primaryButton}
              onClick={() => {
                if (livePageUrl) {
                  window.open(livePageUrl, '_blank', 'noopener');
                }
              }}
              disabled={!livePageUrl}
              title="Open your live PodaBio page in a new tab"
            >
              View live page
            </button>
          )}
        </footer>
        {!pageMissing && (
          <p className={styles.helperNote}>Manage advanced page settings from the Structure tab in Studio.</p>
        )}
      </section>
    </div>
  );
}

function SecurityTab(): JSX.Element {
  const { data: methods, isLoading } = useAuthMethods();
  const { mutateAsync: unlinkGoogle, isPending: unlinkPending, error: unlinkError, reset: resetUnlink } = useUnlinkGoogleMutation();
  const { mutateAsync: removePassword, isPending: removePending, error: removeError, reset: resetRemove } = useRemovePasswordMutation();
  const [drawerAction, setDrawerAction] = useState<SecurityAction | null>(null);
  const [status, setStatus] = useState<string | null>(null);

  if (isLoading) {
    return <p className={styles.emptyState}>Checking your login methods…</p>;
  }

  if (!methods) {
    return <p className={styles.emptyState}>We couldn’t load your security settings. Try again later.</p>;
  }

  const openDrawer = (action: SecurityAction) => {
    resetUnlink();
    resetRemove();
    setStatus(null);
    setDrawerAction(action);
  };

  const closeDrawer = () => {
    setDrawerAction(null);
    resetUnlink();
    resetRemove();
  };

  const handleConfirm = async () => {
    if (!drawerAction) return;
    try {
      if (drawerAction === 'unlink_google') {
        await unlinkGoogle();
        setStatus('Google sign-in removed. You can relink it at any time.');
      } else {
        await removePassword();
        setStatus('Password removed. Google is now your active login method.');
      }
      setDrawerAction(null);
    } catch {
      // errors surface via mutation state
    }
  };

  const drawerError =
    drawerAction === 'unlink_google'
      ? parseError(unlinkError)
      : drawerAction === 'remove_password'
      ? parseError(removeError)
      : null;
  const drawerProcessing = drawerAction === 'unlink_google' ? unlinkPending : drawerAction === 'remove_password' ? removePending : false;

  return (
    <div className={styles.sectionStack}>
      <section className={styles.card} data-account-section="security">
        <header>
          <h2>Login methods</h2>
          <p>Ensure you always have a way back into your account.</p>
        </header>

        {status && <div className={styles.statusBanner}>{status}</div>}

        <div className={styles.authList}>
          <div className={styles.authRow}>
            <div>
              <p className={styles.authTitle}>Email &amp; password</p>
              <p className={styles.authMeta}>
                {methods.has_password ? 'Password is set' : 'No password configured'}
              </p>
            </div>
            <div className={styles.authActions}>
              {methods.has_password ? (
                <>
                  <button
                    type="button"
                    className={styles.secondaryButton}
                    onClick={() => window.open('/forgot-password.php', '_blank')}
                    title="Open the password reset flow in a new tab"
                  >
                    Reset password
                  </button>
                  {methods.has_google && (
                    <button
                      type="button"
                      className={styles.destructiveButton}
                      onClick={() => openDrawer('remove_password')}
                      disabled={removePending}
                      title="Remove your password and rely on Google sign-in only"
                    >
                      Remove password
                    </button>
                  )}
                </>
              ) : (
                <button
                  type="button"
                  className={styles.primaryButton}
                  onClick={() => window.open('/forgot-password.php', '_blank')}
                  title="Set a password so you can log in with email and password"
                >
                  Set password
                </button>
              )}
            </div>
          </div>

          <div className={styles.authRow}>
            <div>
              <p className={styles.authTitle}>Google sign-in</p>
              <p className={styles.authMeta}>{methods.has_google ? 'Linked to Google' : 'Not linked yet'}</p>
            </div>
            <div className={styles.authActions}>
              {methods.has_google ? (
                <button
                  type="button"
                  className={styles.secondaryButton}
                  onClick={() => openDrawer('unlink_google')}
                  disabled={unlinkPending}
                  title="Disconnect Google from this account"
                >
                  Unlink Google
                </button>
              ) : (
                <button
                  type="button"
                  className={styles.primaryButton}
                  onClick={() => {
                    if (methods.google_link_url) {
                      window.location.href = methods.google_link_url;
                    }
                  }}
                  title="Link your Google account so you can sign in with Google"
                >
                  Link Google
                </button>
              )}
            </div>
          </div>
        </div>
      </section>

      <section className={styles.card}>
        <header>
          <h2>Recovery</h2>
          <p>Make sure you can regain access if you ever lose your login.</p>
        </header>
        <div className={styles.fieldGrid}>
          <div className={styles.fieldRow}>
            <span className={styles.fieldLabel}>Two-factor authentication</span>
            <span className={styles.fieldValue}>Coming soon</span>
          </div>
        </div>
      </section>

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


function copyToClipboard(value: string) {
  if (!value) return;
  try {
    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(value);
    } else {
      const textarea = document.createElement('textarea');
      textarea.value = value;
      textarea.setAttribute('readonly', '');
      textarea.style.position = 'absolute';
      textarea.style.left = '-9999px';
      document.body.appendChild(textarea);
      textarea.select();
      document.execCommand('copy');
      document.body.removeChild(textarea);
    }
  } catch (error) {
    console.error('Unable to copy to clipboard', error);
  }
}

function parseError(error: unknown): string | null {
  if (!error) {
    return null;
  }
  if (error instanceof ApiError || error instanceof Error) {
    return error.message;
  }
  if (typeof error === 'string') {
    return error;
  }
  return 'Something went wrong. Please try again.';
}

function isNotFound(error: unknown): boolean {
  return error instanceof ApiError && error.status === 404;
}

