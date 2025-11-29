import { motion } from 'framer-motion';
import * as ScrollArea from '@radix-ui/react-scroll-area';
import { ApplePodcastsLogo, Rss, Link } from '@phosphor-icons/react';
import { PodcastPlayerInspector } from './PodcastPlayerInspector';
import { usePodcastThemePrompt } from '../../hooks/usePodcastThemePrompt';
import { PodcastThemePromptDialog } from './themes/PodcastThemePromptDialog';
import { PodcastThemeGeneratorModal } from './themes/PodcastThemeGeneratorModal';
import type { TabColorTheme } from '../layout/tab-colors';
import styles from './podcast-panel.module.css';

interface PodcastPanelProps {
  activeColor: TabColorTheme;
}

export function PodcastPanel({ activeColor }: PodcastPanelProps): JSX.Element {
  const {
    showPrompt,
    openGenerator,
    closeGenerator,
    closePrompt,
    isGeneratorOpen,
    generatorProps,
  } = usePodcastThemePrompt();

  return (
    <>
      <motion.div
        className={styles.panel}
        initial={{ opacity: 0, x: 10 }}
        animate={{ opacity: 1, x: 0 }}
        transition={{ duration: 0.25 }}
        style={{
          '--active-tab-color': activeColor.text,
          '--active-tab-bg': activeColor.primary,
        } as React.CSSProperties}
      >
        <ScrollArea.Root className={styles.scrollArea}>
          <ScrollArea.Viewport className={styles.viewport}>
            <div className={styles.content}>
              <header className={styles.header}>
                <h2>Podcast / rss</h2>
                <p>Manage your podcast RSS feed, player, and platform links</p>
              </header>

              <div className={styles.section}>
                <div className={styles.sectionHeader}>
                  <h3>
                    <Rss aria-hidden="true" size={16} weight="regular" />
                    RSS Feed & Podcast Player
                  </h3>
                  <p className={styles.sectionDescription}>
                    Configure your RSS feed URL and enable the top drawer podcast player
                  </p>
                </div>
                <PodcastPlayerInspector activeColor={activeColor} />
              </div>

              <div className={styles.section}>
                <div className={styles.sectionHeader}>
                  <h3>
                    <Link aria-hidden="true" size={16} weight="regular" />
                    Podlinks
                  </h3>
                  <p className={styles.sectionDescription}>
                    Generate platform links automatically from your RSS feed. Podlinks are managed in the Podcast Player section above.
                  </p>
                </div>
                <div className={styles.infoBox}>
                  <p>
                    Podlinks are automatically generated when you set up your RSS feed and enable the podcast player.
                    They will appear in your social icons section automatically.
                  </p>
                </div>
              </div>
            </div>
          </ScrollArea.Viewport>
          <ScrollArea.Scrollbar orientation="vertical" className={styles.scrollbar}>
            <ScrollArea.Thumb className={styles.thumb} />
          </ScrollArea.Scrollbar>
        </ScrollArea.Root>
      </motion.div>

      {/* Podcast Theme Prompt Dialog */}
      <PodcastThemePromptDialog
        coverImageUrl={generatorProps.coverImageUrl}
        podcastName={generatorProps.podcastName}
        isOpen={showPrompt}
        onAccept={openGenerator}
        onDecline={closePrompt}
      />

      {/* Podcast Theme Generator Modal */}
      <PodcastThemeGeneratorModal
        coverImageUrl={generatorProps.coverImageUrl}
        podcastName={generatorProps.podcastName}
        podcastDescription={generatorProps.podcastDescription}
        isOpen={isGeneratorOpen}
        onClose={closeGenerator}
      />
    </>
  );
}

