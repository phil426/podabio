import { useState, useMemo, useEffect } from 'react';
import { useLocation, useNavigate } from 'react-router-dom';
import { motion, AnimatePresence } from 'framer-motion';
import * as Popover from '@radix-ui/react-popover';
import {
  User,
  CreditCard,
  SignOut,
  Layout,
  CaretUp
} from '@phosphor-icons/react';
import { useAccountProfile } from '../../api/account';
import { usePageSnapshot } from '../../api/page';
import { useFeatureFlag } from '../../store/featureFlags';
import { normalizeImageUrl } from '../../api/utils';
import { useLeftRailExpanded } from '../../state/leftRailExpanded';
import { trackTelemetry } from '../../services/telemetry';
import styles from './lefty-profile-section.module.css';

export function LeftyProfileSection(): JSX.Element {
  const { data: account } = useAccountProfile();
  const { data: snapshot } = usePageSnapshot();
  const { accountWorkspaceEnabled } = useFeatureFlag();
  const isExpanded = useLeftRailExpanded((state) => state.isExpanded);
  const [menuOpen, setMenuOpen] = useState(false);
  const navigate = useNavigate();
  const location = useLocation();

  const email = account?.email ?? 'loading…';
  const displayName = account?.name ?? email;
  // Use page profile_image if available, otherwise fall back to account avatar_url
  const profileImage = snapshot?.page?.profile_image ?? null;
  const avatarUrl = profileImage ?? account?.avatar_url ?? null;
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

  const handleNavigate = (path: string) => {
    setMenuOpen(false);
    trackTelemetry({ event: 'lefty.profile_navigate', metadata: { destination: path } });
    navigate(path);
  };

  const handleSignOut = () => {
    setMenuOpen(false);
    window.location.href = '/logout.php';
  };

  return (
    <div className={styles.profileSection}>
      <Popover.Root open={menuOpen} onOpenChange={setMenuOpen}>
        <Popover.Trigger asChild>
          <motion.button
            type="button"
            className={styles.profileButton}
            data-expanded={isExpanded ? 'true' : undefined}
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
            aria-label="Account menu"
            aria-expanded={menuOpen}
          >
            {avatarUrl ? (
              <img
                src={normalizeImageUrl(avatarUrl)}
                alt=""
                className={styles.avatar}
                aria-hidden="true"
              />
            ) : (
              <div className={styles.avatarPlaceholder} aria-hidden="true">
                {initials}
              </div>
            )}
            {isExpanded && (
              <motion.div
                className={styles.profileInfo}
                initial={{ opacity: 0, x: -10 }}
                animate={{ opacity: 1, x: 0 }}
                exit={{ opacity: 0, x: -10 }}
                transition={{ duration: 0.2 }}
              >
                <p className={styles.profileName}>{displayName}</p>
                <p className={styles.profileEmail}>{email}</p>
              </motion.div>
            )}
            {isExpanded && (
              <motion.span
                className={styles.chevron}
                animate={{ rotate: menuOpen ? 180 : 0 }}
                transition={{ duration: 0.2 }}
              >
                <CaretUp aria-hidden="true" size={16} weight="regular" />
              </motion.span>
            )}
          </motion.button>
        </Popover.Trigger>
        <Popover.Portal>
          <Popover.Content
            className={styles.popoverContent}
            side="right"
            sideOffset={8}
            align="start"
            alignOffset={-16}
          >
            <AnimatePresence>
              {menuOpen && (
                <motion.div
                  initial={{ opacity: 0, y: -10 }}
                  animate={{ opacity: 1, y: 0 }}
                  exit={{ opacity: 0, y: -10 }}
                  transition={{ duration: 0.2 }}
                >
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
                          onClick={() => handleNavigate('/account/profile')}
                        >
                          <User aria-hidden="true" className={styles.menuIcon} size={16} weight="regular" />
                          Profile &amp; security
                        </button>
                        <button
                          type="button"
                          className={styles.menuLink}
                          onClick={() => handleNavigate('/account/billing')}
                        >
                          <CreditCard aria-hidden="true" className={styles.menuIcon} size={16} weight="regular" />
                          Plans &amp; billing
                        </button>
                      </>
                    ) : (
                      <button
                        type="button"
                        className={styles.menuLink}
                        onClick={() => {
                          trackTelemetry({ event: 'lefty.profile_navigate', metadata: { destination: 'account_workspace' } });
                          handleNavigate('/account/profile');
                        }}
                      >
                        <User aria-hidden="true" className={styles.menuIcon} size={16} weight="regular" />
                        Manage account
                      </button>
                    )}
                    {/* Panel switcher removed - Lefty is now the only admin panel */}
                  </div>
                  <div className={styles.menuFooter}>
                    <button
                      type="button"
                      className={styles.menuLink}
                      onClick={handleSignOut}
                    >
                      <SignOut aria-hidden="true" className={styles.menuIcon} size={16} weight="regular" />
                      Sign out
                    </button>
                  </div>
                </motion.div>
              )}
            </AnimatePresence>
            <Popover.Arrow className={styles.popoverArrow} />
          </Popover.Content>
        </Popover.Portal>
      </Popover.Root>
    </div>
  );
}

