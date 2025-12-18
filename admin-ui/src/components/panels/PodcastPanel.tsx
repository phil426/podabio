import { motion } from 'framer-motion';
import * as ScrollArea from '@radix-ui/react-scroll-area';
// import { usePodcastThemePrompt } from '../../hooks/usePodcastThemePrompt';
// import { PodcastThemePromptDialog } from './themes/PodcastThemePromptDialog';
// import { PodcastThemeGeneratorModal } from './themes/PodcastThemeGeneratorModal';
import { PodcastInspector } from './PodcastInspector';
import { usePageSnapshot } from '../../api/page';
import type { TabColorTheme } from '../layout/tab-colors';
import styles from './podcast-panel.module.css';

interface PodcastPanelProps {
  activeColor: TabColorTheme;
}

export function PodcastPanel({ activeColor }: PodcastPanelProps): JSX.Element {
  // const {
  //   showPrompt,
  //   openGenerator,
  //   closeGenerator,
  //   closePrompt,
  //   isGeneratorOpen,
  //   generatorProps,
  // } = usePodcastThemePrompt();

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

              <PodcastInspector activeColor={activeColor} />

            </div>
          </ScrollArea.Viewport>
          <ScrollArea.Scrollbar orientation="vertical" className={styles.scrollbar}>
            <ScrollArea.Thumb className={styles.thumb} />
          </ScrollArea.Scrollbar>
        </ScrollArea.Root>
      </motion.div>

      {/* Legacy Theme Prompts */}
      {/* Legacy Theme Prompts removed */}
    </>
  );
}

