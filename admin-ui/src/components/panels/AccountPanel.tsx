import * as Tabs from '@radix-ui/react-tabs';
import { useCallback, useEffect, useMemo, useState } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';

import {
  useAccountProfile,
  useAuthMethods,
  useSubscriptionStatus,
  useCreatePageMutation,
  useRemovePasswordMutation,
  useUnlinkGoogleMutation,
  useUpdateProfileMutation,
  updateAccountProfile,
  removeAvatar
} from '../../api/account';
import { uploadAvatarImage as uploadAvatar } from '../../api/uploads';
import { MediaLibraryModal } from '../overlays/MediaLibraryModal';
import { normalizeImageUrl } from '../../api/utils';
import { UploadSimple, Images, X } from '@phosphor-icons/react';
import { useRef } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { queryKeys } from '../../api/utils';
import { usePageSnapshot } from '../../api/page';
import { ApiError } from '../../api/http';
import type { AccountProfile } from '../../api/types';
import { MigrationEducationBanner } from '../account/MigrationEducationBanner';
import { BillingPanel } from '../account/BillingPanel';
import { CustomDomainSettings } from '../account/CustomDomainSettings';
import { MediaLibraryTab } from './MediaLibraryTab';
import { trackTelemetry } from '../../services/telemetry';
import { CreatePageDrawer } from '../overlays/CreatePageDrawer';
import { SecurityActionDrawer, type SecurityAction } from '../overlays/SecurityActionDrawer';
import { TwoFactorSetupModal } from '../overlays/TwoFactorSetupModal';
import { use2FAStatus, useDisable2FAMutation } from '../../api/account';
import type { TabColorTheme } from '../layout/tab-colors';
import styles from './account-panel.module.css';

const TAB_DEFAULT = 'profile';
const VALID_TABS = new Set(['profile', 'media', 'security', 'billing']);

interface ProfileTabProps {
  profile?: AccountProfile | null;
  isLoading: boolean;
  pageMissing: boolean;
  isPageLoading: boolean;
  pageUsername: string | null;
  onCreatePage: () => void;
  createStatus?: string | null;
}

interface AccountPanelProps {
  activeColor: TabColorTheme;
}

export function AccountPanel({ activeColor }: AccountPanelProps): JSX.Element {
  const location = useLocation();
  const navigate = useNavigate();

  const activeTab = useMemo(() => {
    // Check URL hash first for tab navigation within account panel
    const hash = location.hash.replace('#', '');
    if (hash && VALID_TABS.has(hash)) {
      return hash;
    }
    return TAB_DEFAULT;
  }, [location.hash]);

  useEffect(() => {
    if (!location.hash) {
      navigate({ hash: TAB_DEFAULT }, { replace: true });
    }
  }, [location.hash, navigate]);

  useEffect(() => {
    trackTelemetry({ event: 'account.panel_view', metadata: { tab: activeTab } });
  }, [activeTab]);

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
        trackTelemetry({ event: 'account.create_page', metadata: { source: 'account_panel' } });
      } catch {
        // handled via mutation error state
      }
    },
    [createPage]
  );

  const handleTabChange = (value: string) => {
    if (!VALID_TABS.has(value)) return;
    trackTelemetry({ event: 'account.panel_tab_change', metadata: { tab: value } });
    navigate({ hash: value }, { replace: true });
  };

  return (
    <div 
      className={styles.panel}
      style={{ 
        '--active-tab-color': activeColor.text,
        '--active-tab-bg': activeColor.primary,
        '--active-tab-light': activeColor.light,
        '--active-tab-border': activeColor.border
      } as React.CSSProperties}
    >
      <div className={styles.header}>
        <div>
          <h1>Account settings</h1>
          <p>Keep your profile, security, and billing details up to date.</p>
        </div>
      </div>

      <div className={styles.content}>
        <MigrationEducationBanner />

        <Tabs.Root className={styles.tabsRoot} value={activeTab} onValueChange={handleTabChange}>
          <Tabs.List className={styles.tabList} aria-label="Account sections">
            <Tabs.Trigger value="profile">Profile</Tabs.Trigger>
            <Tabs.Trigger value="media">Media library</Tabs.Trigger>
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

          <Tabs.Content className={styles.tabContent} value="media">
            <MediaLibraryTab />
          </Tabs.Content>

          <Tabs.Content className={styles.tabContent} value="security">
            <SecurityTab />
          </Tabs.Content>

          <Tabs.Content className={styles.tabContent} value="billing">
            <BillingPanel />
          </Tabs.Content>
        </Tabs.Root>
      </div>

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
  const [isEditing, setIsEditing] = useState(false);
  const [displayName, setDisplayName] = useState('');
  const [email, setEmail] = useState('');
  const [saveStatus, setSaveStatus] = useState<string | null>(null);
  const [error, setError] = useState<string | null>(null);
  const [isUploadingAvatar, setIsUploadingAvatar] = useState(false);
  const [avatarStatus, setAvatarStatus] = useState<string | null>(null);
  const [mediaLibraryOpen, setMediaLibraryOpen] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);
  const queryClient = useQueryClient();
  
  const { mutateAsync: updateProfile, isPending: isUpdating } = useUpdateProfileMutation();

  // Initialize form values when profile loads
  useEffect(() => {
    if (profile) {
      setDisplayName(profile.name || '');
      setEmail(profile.email || '');
    }
  }, [profile]);

  if (isLoading) {
    return <p className={styles.emptyState}>Loading profile…</p>;
  }

  if (!profile) {
    return <p className={styles.emptyState}>We couldn't load your profile right now. Please try again shortly.</p>;
  }

  const livePageUrl = pageUsername ? `${window.__APP_URL__ ?? ''}/${pageUsername}` : null;

  const handleStartEdit = () => {
    setDisplayName(profile.name || '');
    setEmail(profile.email || '');
    setError(null);
    setSaveStatus(null);
    setIsEditing(true);
  };

  const handleCancelEdit = () => {
    setDisplayName(profile.name || '');
    setEmail(profile.email || '');
    setError(null);
    setSaveStatus(null);
    setIsEditing(false);
  };

  const handleSave = async () => {
    setError(null);
    setSaveStatus(null);

    // Validate
    if (!email.trim()) {
      setError('Email is required');
      return;
    }

    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.trim())) {
      setError('Please enter a valid email address');
      return;
    }

    if (displayName.trim().length > 100) {
      setError('Display name must be 100 characters or less');
      return;
    }

    try {
      await updateProfile({
        name: displayName.trim() || undefined,
        email: email.trim()
      });
      setSaveStatus('Profile updated successfully');
      setIsEditing(false);
      trackTelemetry({ event: 'account.profile_updated', metadata: {} });
    } catch (err) {
      setError(err instanceof Error ? err.message : 'Failed to update profile. Please try again.');
    }
  };

  const handleChooseAvatarFile = () => {
    fileInputRef.current?.click();
  };

  const handleAvatarFileChange = async (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;

    try {
      setIsUploadingAvatar(true);
      setAvatarStatus(null);
      const result = await uploadAvatar(file, true);
      await queryClient.invalidateQueries({ queryKey: queryKeys.accountProfile() });
      setAvatarStatus('Avatar updated successfully');
      trackTelemetry({ event: 'account.avatar_uploaded', metadata: {} });
    } catch (err) {
      setAvatarStatus(err instanceof Error ? err.message : 'Upload failed. Please try again.');
    } finally {
      setIsUploadingAvatar(false);
      if (fileInputRef.current) {
        fileInputRef.current.value = '';
      }
    }
  };

  const handleRemoveAvatar = async () => {
    try {
      setIsUploadingAvatar(true);
      setAvatarStatus(null);
      await removeAvatar();
      await queryClient.invalidateQueries({ queryKey: queryKeys.accountProfile() });
      setAvatarStatus('Avatar removed successfully');
      trackTelemetry({ event: 'account.avatar_removed', metadata: {} });
    } catch (err) {
      setAvatarStatus(err instanceof Error ? err.message : 'Unable to remove avatar.');
    } finally {
      setIsUploadingAvatar(false);
    }
  };

  const handleSelectAvatarFromLibrary = async (mediaItem: { file_url: string; id: number; filename: string }) => {
    try {
      setIsUploadingAvatar(true);
      setAvatarStatus(null);
      await updateAccountProfile({ avatar_url: mediaItem.file_url });
      await queryClient.invalidateQueries({ queryKey: queryKeys.accountProfile() });
      setMediaLibraryOpen(false);
      setAvatarStatus('Avatar updated successfully');
      trackTelemetry({ event: 'account.avatar_updated_from_library', metadata: {} });
    } catch (err) {
      setAvatarStatus(err instanceof Error ? err.message : 'Unable to update avatar.');
    } finally {
      setIsUploadingAvatar(false);
    }
  };

  const avatarUrl = profile?.avatar_url ?? null;
  const initials = (profile?.name ?? profile?.email ?? '')
    .split(' ')
    .filter(Boolean)
    .map((chunk) => chunk[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();

  return (
    <div className={styles.sectionStack}>
      {createStatus && <div className={styles.statusBanner}>{createStatus}</div>}
      {saveStatus && <div className={styles.statusBanner}>{saveStatus}</div>}
      {error && <div className={styles.errorBanner}>{error}</div>}

      <section className={styles.card} data-account-section="profile">
        <header>
          <h2>Basics</h2>
          <p>Update how your account appears across PodaBio Studio.</p>
        </header>
        <div className={styles.fieldGrid}>
          <div className={styles.fieldRow}>
            <label className={styles.fieldLabel} htmlFor="display-name-input">
              Display name
            </label>
            {isEditing ? (
              <input
                id="display-name-input"
                type="text"
                className={styles.input}
                value={displayName}
                onChange={(e) => setDisplayName(e.target.value)}
                placeholder="Enter your display name"
                maxLength={100}
              />
            ) : (
              <span className={styles.fieldValue}>{profile.name || '—'}</span>
            )}
          </div>
          <div className={styles.fieldRow}>
            <label className={styles.fieldLabel} htmlFor="email-input">
              Email
            </label>
            {isEditing ? (
              <input
                id="email-input"
                type="email"
                className={styles.input}
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="Enter your email address"
              />
            ) : (
              <>
                <span className={styles.fieldValue}>{profile.email}</span>
                <button
                  type="button"
                  className={styles.fieldAction}
                  onClick={() => copyToClipboard(profile.email)}
                  title="Copy your account email address"
                >
                  Copy
                </button>
              </>
            )}
          </div>
        </div>
        <footer className={styles.cardFooter}>
          {isEditing ? (
            <>
              <button
                type="button"
                className={styles.secondaryButton}
                onClick={handleCancelEdit}
                disabled={isUpdating}
              >
                Cancel
              </button>
              <button
                type="button"
                className={styles.primaryButton}
                onClick={handleSave}
                disabled={isUpdating}
              >
                {isUpdating ? 'Saving…' : 'Save changes'}
              </button>
            </>
          ) : (
            <button
              type="button"
              className={styles.primaryButton}
              onClick={handleStartEdit}
            >
              Edit profile
            </button>
          )}
        </footer>
      </section>

      <section className={styles.card}>
        <header>
          <h2>Avatar</h2>
          <p>Your avatar appears in the Studio interface and account menus.</p>
        </header>
        {avatarStatus && (
          <div className={avatarStatus.includes('success') ? styles.statusBanner : styles.errorBanner}>
            {avatarStatus}
          </div>
        )}
        <div className={styles.avatarSection}>
          <div className={styles.avatarPreview} data-has-avatar={avatarUrl ? 'true' : 'false'}>
            {avatarUrl ? (
              <img src={normalizeImageUrl(avatarUrl)} alt="Avatar" />
            ) : (
              <div className={styles.avatarPlaceholder}>{initials}</div>
            )}
            <div className={styles.avatarOverlay}>
              <div className={styles.avatarActions}>
                <button
                  type="button"
                  className={styles.avatarActionButton}
                  onClick={handleChooseAvatarFile}
                  disabled={isUploadingAvatar}
                  title={isUploadingAvatar ? 'Uploading…' : avatarUrl ? 'Replace avatar' : 'Upload avatar'}
                >
                  <UploadSimple size={16} weight="regular" aria-hidden="true" />
                </button>
                <div className={styles.avatarActionDivider} />
                <button
                  type="button"
                  className={styles.avatarActionButton}
                  onClick={() => setMediaLibraryOpen(true)}
                  disabled={isUploadingAvatar}
                  title="Choose from library"
                >
                  <Images size={16} weight="regular" aria-hidden="true" />
                </button>
                {avatarUrl && (
                  <>
                    <div className={styles.avatarActionDivider} />
                    <button
                      type="button"
                      className={`${styles.avatarActionButton} ${styles.avatarActionButtonDanger}`}
                      onClick={handleRemoveAvatar}
                      disabled={isUploadingAvatar}
                      title="Remove avatar"
                    >
                      <X size={16} weight="regular" aria-hidden="true" />
                    </button>
                  </>
                )}
              </div>
            </div>
          </div>
          <input
            ref={fileInputRef}
            type="file"
            accept="image/*"
            onChange={handleAvatarFileChange}
            style={{ display: 'none' }}
            aria-label="Upload avatar image"
          />
        </div>
        <p className={styles.helperNote}>
          Upload a profile picture to personalize your account. This is separate from your page profile image.
        </p>
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

      {/* Custom Domain Settings - only show if user has a page */}
      {!pageMissing && pageUsername && (
        <CustomDomainSettings username={pageUsername} />
      )}

      <MediaLibraryModal
        open={mediaLibraryOpen}
        onClose={() => setMediaLibraryOpen(false)}
        onSelect={handleSelectAvatarFromLibrary}
      />
    </div>
  );
}

function SecurityTab(): JSX.Element {
  const { data: methods, isLoading } = useAuthMethods();
  const { data: twoFactorStatus, isLoading: twoFactorLoading } = use2FAStatus();
  const { mutateAsync: unlinkGoogle, isPending: unlinkPending, error: unlinkError, reset: resetUnlink } = useUnlinkGoogleMutation();
  const { mutateAsync: removePassword, isPending: removePending, error: removeError, reset: resetRemove } = useRemovePasswordMutation();
  const { mutateAsync: disable2FA, isPending: disable2FAPending, error: disable2FAError } = useDisable2FAMutation();
  const [drawerAction, setDrawerAction] = useState<SecurityAction | null>(null);
  const [status, setStatus] = useState<string | null>(null);
  const [show2FASetup, setShow2FASetup] = useState(false);
  const [showDisable2FADrawer, setShowDisable2FADrawer] = useState(false);
  const [disable2FAPassword, setDisable2FAPassword] = useState('');

  if (isLoading) {
    return <p className={styles.emptyState}>Checking your login methods…</p>;
  }

  if (!methods) {
    return <p className={styles.emptyState}>We couldn't load your security settings. Try again later.</p>;
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
            <span className={styles.fieldValue}>
              {twoFactorLoading ? (
                'Loading...'
              ) : twoFactorStatus?.enabled ? (
                <>
                  <span className={styles.enabledBadge}>Enabled</span>
                  <span className={styles.methodBadge}>
                    {twoFactorStatus.method === 'totp' && 'Authenticator App'}
                    {twoFactorStatus.method === 'email' && 'Email Codes'}
                    {twoFactorStatus.method === 'both' && 'Both Methods'}
                  </span>
                </>
              ) : (
                'Not enabled'
              )}
            </span>
          </div>
        </div>
        <footer className={styles.cardFooter}>
          {twoFactorLoading ? null : twoFactorStatus?.enabled ? (
            <button
              type="button"
              className={styles.destructiveButton}
              onClick={() => setShowDisable2FADrawer(true)}
              disabled={disable2FAPending}
            >
              {disable2FAPending ? 'Disabling...' : 'Disable 2FA'}
            </button>
          ) : (
            <button
              type="button"
              className={styles.primaryButton}
              onClick={() => setShow2FASetup(true)}
            >
              Enable 2FA
            </button>
          )}
        </footer>
      </section>

      <TwoFactorSetupModal
        open={show2FASetup}
        onClose={() => setShow2FASetup(false)}
        onSuccess={() => {
          setShow2FASetup(false);
          setStatus('Two-factor authentication enabled successfully!');
        }}
        userEmail={twoFactorStatus?.email}
      />

      {showDisable2FADrawer && (
        <div className={styles.passwordConfirmDrawer}>
          <div className={styles.passwordConfirmContent}>
            <h3>Disable Two-Factor Authentication</h3>
            <p>Enter your password to confirm:</p>
            <input
              type="password"
              value={disable2FAPassword}
              onChange={(e) => setDisable2FAPassword(e.target.value)}
              placeholder="Enter your password"
              className={styles.passwordInput}
              autoFocus
            />
            {disable2FAError && (
              <div className={styles.errorBanner}>
                {parseError(disable2FAError)}
              </div>
            )}
            <div className={styles.passwordConfirmActions}>
              <button
                type="button"
                className={styles.destructiveButton}
                onClick={async () => {
                  try {
                    await disable2FA(disable2FAPassword);
                    setShowDisable2FADrawer(false);
                    setDisable2FAPassword('');
                    setStatus('Two-factor authentication disabled successfully.');
                  } catch {
                    // errors surface via mutation state
                  }
                }}
                disabled={!disable2FAPassword || disable2FAPending}
              >
                {disable2FAPending ? 'Disabling...' : 'Disable 2FA'}
              </button>
              <button
                type="button"
                className={styles.secondaryButton}
                onClick={() => {
                  setShowDisable2FADrawer(false);
                  setDisable2FAPassword('');
                }}
              >
                Cancel
              </button>
            </div>
          </div>
        </div>
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

