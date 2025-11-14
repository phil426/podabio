import { useMemo, useState, useEffect } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import {
  LuChevronDown,
  LuCreditCard,
  LuCopy,
  LuExternalLink,
  LuLogOut,
  LuUser
} from 'react-icons/lu';

const LOGO_DATA_URL =
  'data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAACwAAAAsCAYAAAAehFoBAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAExWlUWHRYTUw6Y29tLmFkb2JlLnhtcAAAAAAAPD94cGFja2V0IGJlZ2luPSfvu78nIGlkPSdXNU0wTXBDZWhpSHpyZVN6TlRjemtjOWQnPz4KPHg6eG1wbWV0YSB4bWxuczp4PSdhZG9iZTpuczptZXRhLyc+CjxyZGY6UkRGIHhtbG5zOnJkZj0naHR0cDovL3d3dy53My5vcmcvMTk5OS8wMi8yMi1yZGYtc3ludGF4LW5zIyc+CgogPHJkZjpEZXNjcmlwdGlvbiByZGY6YWJvdXQ9JycKICB4bWxuczpBdHRyaWI9J2h0dHA6Ly9ucy5hdHRyaWJ1dGlvbi5jb20vYWRzLzEuMC8nPgogIDxBdHRyaWI6QWRzPgogICA8cmRmOlNlcT4KICAgIDxyZGY6bGkgcmRmOnBhcnNlVHlwZT0nUmVzb3VyY2UnPgogICAgIDxBdHRyaWI6Q3JlYXRlZD4yMDI1LTExLTExPC9BdHRyaWI6Q3JlYXRlZD4KICAgICA8QXR0cmliOkV4dElkPmZiNTlmMzMwLTNiNzctNDJhMy05M2JkLTgxM2UzOTJiMTUyNDwvQXR0cmliOkV4dElkPgogICAgIDxBdHRyaWI6RmJJZD41MjUyNjU5MTQxNzk1ODA8L0F0dHJpYjpGYklkPgogICAgIDxBdHRyaWI6VG91Y2hUeXBlPjI8L0F0dHJpYjpUb3VjaFR5cGU+CiAgICA8L3JkZjpsaT4KICAgPC9yZGY6U2VxPgogIDwvQXR0cmliOkFkcz4KIDwvcmRmOkRlc2NyaXB0aW9uPgoKIDxyZGY6RGVzY3JpcHRpb24gcmRmOmFib3V0PScnCiAgeG1sbnM6ZGM9J2h0dHA6Ly9wdXJsLm9yZy9kYy9lbGVtZW50cy8xLjEvJz4KICA8ZGM6dGl0bGU+CiAgIDxyZGY6QWx0PgogICAgPHJkZjpsaSB4bWw6bGFuZz0neC1kZWZhdWx0Jz5Qb2QgaW4gQmlvIExvZ28gKDQ0IHggNDQgcHgpIC0gbG9nb19waWI8L3JkZjpsaT4KICAgPC9yZGY6QWx0PgogIDwvZGM6dGl0bGU+CiA8L3JkZjpEZXNjcmlwdGlv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';
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
        <img src={LOGO_DATA_URL} alt="PodaBio" className={styles.brandLogo} />
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
              <LuExternalLink aria-hidden="true" />
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
              <LuCopy aria-hidden="true" />
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
            data-open={menuOpen ? 'true' : undefined}
          >
            <LuChevronDown aria-hidden="true" />
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
                        <LuUser />
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
                        <LuCreditCard />
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
                      trackTelemetry({ event: 'topbar.account_navigate_legacy', metadata: { destination: 'editor_account_tab' } });
                      window.location.href = '/editor.php?tab=account';
                    }}
                  >
                    <span className={styles.menuIcon} aria-hidden="true">
                      <LuUser />
                    </span>
                    Manage account (classic)
                  </button>
                )}
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
                    <LuLogOut />
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

