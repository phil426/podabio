import { useState, useMemo, useEffect } from 'react';
import { useLocation } from 'react-router-dom';
import { motion, AnimatePresence } from 'framer-motion';
import * as Popover from '@radix-ui/react-popover';
import {
  Copy,
  ArrowSquareOut,
  CaretDown
} from '@phosphor-icons/react';
import { usePageSnapshot } from '../../api/page';
import { useLeftRailExpanded } from '../../state/leftRailExpanded';
import { trackTelemetry } from '../../services/telemetry';
import { normalizeImageUrl } from '../../api/utils';
import styles from './lefty-app-logo-section.module.css';

const LOGO_PATH = '/uploads/poda4.png';

export function LeftyAppLogoSection(): JSX.Element {
  const { data, isLoading } = usePageSnapshot();
  const isExpanded = useLeftRailExpanded((state) => state.isExpanded);
  const [menuOpen, setMenuOpen] = useState(false);
  const [copyState, setCopyState] = useState<'idle' | 'copied'>('idle');
  const location = useLocation();

  const username = data?.page.username ?? '...';
  const previewUrl = data?.page.username ? `${window.__APP_URL__ ?? ''}/${data.page.username}` : '';
  const timeStamp = useMemo(() => new Date().toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' }), []);
  const friendlyUrl = previewUrl || 'Share your link once you publish';

  useEffect(() => {
    setMenuOpen(false);
  }, [location.pathname]);

  useEffect(() => {
    if (copyState !== 'copied') return;
    const timer = window.setTimeout(() => setCopyState('idle'), 2500);
    return () => window.clearTimeout(timer);
  }, [copyState]);

  const handleOpenLivePage = () => {
    if (!previewUrl) return;
    trackTelemetry({ event: 'lefty.app_logo.open_live_page' });
    window.open(previewUrl, '_blank', 'noopener');
    setMenuOpen(false);
  };

  const handleCopyLink = async () => {
    if (!previewUrl) return;
    try {
      await navigator.clipboard.writeText(previewUrl);
      trackTelemetry({ event: 'lefty.app_logo.copy_live_link' });
      setCopyState('copied');
    } catch (error) {
      console.error('Unable to copy link', error);
      setCopyState('idle');
    }
  };

  return (
    <div className={styles.logoSection}>
      <Popover.Root open={menuOpen} onOpenChange={setMenuOpen}>
        <Popover.Trigger asChild>
          <motion.button
            type="button"
            className={styles.logoButton}
            data-expanded={isExpanded ? 'true' : undefined}
            whileHover={{ scale: 1.02 }}
            whileTap={{ scale: 0.98 }}
            aria-label="App menu"
            aria-expanded={menuOpen}
          >
            <img
              src={normalizeImageUrl(LOGO_PATH)}
              alt="PodaBio"
              className={styles.logo}
              aria-hidden="true"
            />
            {isExpanded && (
              <motion.div
                className={styles.logoInfo}
                initial={{ opacity: 0, x: -10 }}
                animate={{ opacity: 1, x: 0 }}
                exit={{ opacity: 0, x: -10 }}
                transition={{ duration: 0.2 }}
              >
                <p className={styles.logoName}>PodaBio Studio</p>
                <p className={styles.logoMeta}>Link in bio for podcasters</p>
              </motion.div>
            )}
            {isExpanded && (
              <motion.span
                className={styles.chevron}
                animate={{ rotate: menuOpen ? 180 : 0 }}
                transition={{ duration: 0.2 }}
              >
                <CaretDown aria-hidden="true" size={16} weight="regular" />
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
                    <p className={styles.menuTitle}>PodaBio Studio</p>
                    <p className={styles.menuSubtitle}>Link in bio for podcasters</p>
                  </div>
                  <div className={styles.menuBody}>
                    <div className={styles.metaSection}>
                      <div className={styles.metaRow}>
                        <span className={styles.metaLabel}>Page:</span>
                        <span className={styles.metaValue}>/{username}</span>
                      </div>
                      {previewUrl && (
                        <div className={styles.metaRow}>
                          <span className={styles.metaLabel}>URL:</span>
                          <span className={styles.metaValue} title={friendlyUrl}>
                            {friendlyUrl.length > 30 ? `${friendlyUrl.slice(0, 30)}...` : friendlyUrl}
                          </span>
                        </div>
                      )}
                      <div className={styles.metaRow}>
                        <span className={styles.metaLabel}>{isLoading ? 'Loading…' : 'Last synced'}:</span>
                        <span className={styles.metaValue}>{timeStamp}</span>
                      </div>
                    </div>
                    {previewUrl && (
                      <div className={styles.actionsSection}>
                        <button
                          type="button"
                          className={styles.actionButton}
                          onClick={handleOpenLivePage}
                        >
                          <ArrowSquareOut aria-hidden="true" className={styles.actionIcon} size={16} weight="regular" />
                          Open live page
                        </button>
                        <button
                          type="button"
                          className={styles.actionButton}
                          onClick={handleCopyLink}
                          data-state={copyState}
                        >
                          <Copy aria-hidden="true" className={styles.actionIcon} size={16} weight="regular" />
                          {copyState === 'copied' ? 'Link copied!' : 'Copy share link'}
                        </button>
                      </div>
                    )}
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

