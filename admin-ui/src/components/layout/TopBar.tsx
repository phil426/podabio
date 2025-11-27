import { useMemo, useState, useEffect } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import {
  CaretDown,
  CreditCard,
  Copy,
  ArrowSquareOut,
  SignOut,
  Layout,
  User
} from '@phosphor-icons/react';

import { normalizeImageUrl } from '../../api/utils';

const LOGO_PATH = '/uploads/poda4.png';
import { usePageSnapshot } from '../../api/page';
import { useAccountProfile } from '../../api/account';
import { useFeatureFlag } from '../../store/featureFlags';
import { trackTelemetry } from '../../services/telemetry';
import { normalizeImageUrl } from '../../api/utils';
import styles from './top-bar.module.css';

export function TopBar(): JSX.Element {
  const timeStamp = useMemo(() => new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }), []);
  const { data, isLoading } = usePageSnapshot();
  const { data: account } = useAccountProfile();
  const { accountWorkspaceEnabled } = useFeatureFlag();
  const [menuOpen, setMenuOpen] = useState(false);
  const [copyState, setCopyState] = useState<'idle' | 'copied'>('idle');
  const navigate = useNavigate();
  const location = useLocation();

  const username = data?.page.username ?? '...';
  const previewUrl = data?.page.username ? `${window.__APP_URL__ ?? ''}/${data.page.username}` : '';
  const email = account?.email ?? 'loading…';
  const displayName = account?.name ?? email;
  const plan = formatPlan(account?.plan ?? 'free');
  const avatarUrl = account?.avatar_url ?? null;
  const initials = displayName
    .split(' ')
    .filter(Boolean)
    .map((chunk) => chunk[0])
    .join('')
    .slice(0, 2)
    .toUpperCase();

  useEffect(() => {
    setMenuOpen(false);
  }, [location.pathname]);

  useEffect(() => {
    if (copyState !== 'copied') return;
    const timer = window.setTimeout(() => setCopyState('idle'), 2500);
    return () => window.clearTimeout(timer);
  }, [copyState]);

  const handleToggleMenu = () => {
    setMenuOpen((open) => {
      const next = !open;
      trackTelemetry({ event: 'topbar.account_menu_toggle', metadata: { open: next } });
      return next;
    });
  };

  const handleNavigate = (path: string) => {
    setMenuOpen(false);
    trackTelemetry({ event: 'topbar.account_navigate', metadata: { destination: path } });
    navigate(path);
  };

  const handleOpenLivePage = () => {
    if (!previewUrl) return;
    trackTelemetry({ event: 'topbar.open_live_page' });
    window.open(previewUrl, '_blank', 'noopener');
  };

  const handleCopyLink = async () => {
    if (!previewUrl) return;
    try {
      await navigator.clipboard.writeText(previewUrl);
      trackTelemetry({ event: 'topbar.copy_live_link' });
      setCopyState('copied');
    } catch (error) {
      console.error('Unable to copy link', error);
      setCopyState('idle');
    }
  };

  const friendlyUrl = previewUrl || 'Share your link once you publish';

  return (
    <header className={styles.topbar} aria-label="Studio navigation">
      <div className={styles.brandGroup}>
        <img src={normalizeImageUrl(LOGO_PATH)} alt="PodaBio" className={styles.brandLogo} />
        <div>
          <p className={styles.brandName}>
            PodaBio Studio <span className={styles.brandBadge}>beta</span>
          </p>
          <p className={styles.brandMeta}>Link in bio for podcasters</p>
        </div>
      </div>

      <div className={styles.projectMeta} role="status">
        <div className={styles.metaColumn}>
          {previewUrl ? (
            <a 
              href={previewUrl} 
              target="_blank" 
              rel="noopener noreferrer"
              className={styles.pageTitle}
              onClick={(e) => {
                e.preventDefault();
                window.open(previewUrl, '_blank', 'noopener');
              }}
            >
              /{username}
            </a>
          ) : (
            <p className={styles.pageTitle}>/{username}</p>
          )}
          {previewUrl ? (
            <a 
              href={previewUrl} 
              target="_blank" 
              rel="noopener noreferrer"
              className={styles.pageSubline}
              onClick={(e) => {
                e.preventDefault();
                window.open(previewUrl, '_blank', 'noopener');
              }}
            >
              {friendlyUrl}
            </a>
          ) : (
            <p className={styles.pageSubline}>{friendlyUrl}</p>
          )}
        </div>
        <div className={styles.metaDivider} aria-hidden="true" />
        <div className={styles.metaColumn}>
          <p className={styles.timeLabel}>{isLoading ? 'Loading…' : 'Last synced'}</p>
          <p className={styles.timeValue}>{timeStamp}</p>
        </div>
        <div className={styles.metaActionsCluster}>
          <div className={styles.metaDivider} aria-hidden="true" />
          <div className={styles.metaActions}>
            <button
              type="button"
              className={styles.iconAction}
              onClick={handleOpenLivePage}
              disabled={!previewUrl}
              aria-label="Open live page"
              title="Open live page"
            >
              <ArrowSquareOut aria-hidden="true" size={16} weight="regular" />
            </button>
            <button
              type="button"
              className={styles.iconAction}
              onClick={handleCopyLink}
              disabled={!previewUrl}
              aria-label={copyState === 'copied' ? 'Link copied!' : 'Copy share link'}
              title={copyState === 'copied' ? 'Link copied!' : 'Copy share link'}
              data-state={copyState}
            >
              <Copy aria-hidden="true" size={16} weight="regular" />
            </button>
          </div>
        </div>
      </div>

      <nav aria-label="Primary actions" className={styles.actions}>
        <div className={styles.accountCluster}>
          <button
            type="button"
            className={styles.accountAvatarButton}
            onClick={handleToggleMenu}
            aria-haspopup="menu"
            aria-expanded={menuOpen}
            title="Open account menu"
          >
            {avatarUrl ? <img src={normalizeImageUrl(avatarUrl)} alt="" aria-hidden="true" className={styles.accountAvatarImage} /> : initials}
          </button>
          <div className={styles.accountDetails}>
            <p className={styles.accountPlan}>{plan}</p>
            <p className={styles.accountEmail}>{email}</p>
          </div>
          <button
            type="button"
            className={styles.accountToggle}
            onClick={handleToggleMenu}
            aria-label="Open account menu"
            title="Open account menu"
            data-open={menuOpen ? 'true' : undefined}
          >
            <CaretDown aria-hidden="true" size={16} weight="regular" />
          </button>

          {menuOpen && (
            <div className={styles.accountMenu} role="menu">
              <div className={styles.menuHeader}>
                <p className={styles.menuName}>{displayName}</p>
                <p className={styles.menuEmail}>{email}</p>
              </div>
              <div className={styles.menuBody}>
                {accountWorkspaceEnabled ? (
                  <>
                    <button
                      type="button"
                      className={styles.menuLink}
                      role="menuitem"
                      onClick={() => handleNavigate('/account/profile')}
                    >
                      <span className={styles.menuIcon} aria-hidden="true">
                        <User size={16} weight="regular" />
                      </span>
                      Profile &amp; security
                    </button>
                    <button
                      type="button"
                      className={styles.menuLink}
                      role="menuitem"
                      onClick={() => handleNavigate('/account/billing')}
                    >
                      <span className={styles.menuIcon} aria-hidden="true">
                        <CreditCard size={16} weight="regular" />
                      </span>
                      Plans &amp; billing
                    </button>
                  </>
                ) : (
                  <button
                    type="button"
                    className={styles.menuLink}
                    role="menuitem"
                    onClick={() => {
                      trackTelemetry({ event: 'topbar.account_navigate', metadata: { destination: 'account_workspace' } });
                      handleNavigate('/account/profile');
                    }}
                  >
                    <span className={styles.menuIcon} aria-hidden="true">
                      <User size={16} weight="regular" />
                    </span>
                    Manage account
                  </button>
                )}
                {/* Panel switcher removed - Lefty is now the only admin panel */}
              </div>
              <div className={styles.menuFooter}>
                <button
                  type="button"
                  className={styles.menuLink}
                  role="menuitem"
                  onClick={() => {
                    window.location.href = '/logout.php';
                  }}
                >
                  <span className={styles.menuIcon} aria-hidden="true">
                    <SignOut size={16} weight="regular" />
                  </span>
                  Sign out
                </button>
              </div>
            </div>
          )}
        </div>
      </nav>
    </header>
  );
}

function formatPlan(plan: string): string {
  const normalized = plan?.toString().toLowerCase() ?? 'free';
  switch (normalized) {
    case 'premium':
      return 'Premium plan';
    case 'pro':
      return 'Pro plan';
    case 'team':
      return 'Team plan';
    case 'free':
    default:
      return 'Free plan';
  }
}

