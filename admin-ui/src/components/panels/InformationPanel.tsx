import { useMemo } from 'react';
import { motion } from 'framer-motion';
import * as ScrollArea from '@radix-ui/react-scroll-area';
import { Info, Question, Palette, TextT, Sparkle, ApplePodcastsLogo, Plug, TrendUp } from '@phosphor-icons/react';
import type { TabColorTheme } from '../layout/tab-colors';
import styles from './information-panel.module.css';

interface InformationPanelProps {
  activeColor: TabColorTheme;
  activeTab: string;
}

export function InformationPanel({ activeColor, activeTab }: InformationPanelProps): JSX.Element {
  const content = useMemo(() => {
    switch (activeTab) {
      case 'adding-content':
        return {
          title: 'Adding Content',
          icon: <Info size={20} weight="regular" />,
          sections: [
            {
              title: 'Widget Gallery',
              content: 'Browse and add widgets to your page. Click any widget to add it to your page instantly.'
            },
            {
              title: 'Quick Tips',
              content: 'Use the search to quickly find specific widgets. Popular widgets are shown at the top.'
            }
          ]
        };
      case 'layers':
        return {
          title: 'Layers',
          icon: <Info size={20} weight="regular" />,
          sections: [
            {
              title: 'Managing Layers',
              content: 'Drag layers to reorder them. Use the visibility and lock icons to control how layers appear on your page.'
            },
            {
              title: 'Profile & Footer',
              content: 'Edit your profile image, bio, and footer text directly from the Layers tab.'
            }
          ]
        };
      case 'typography':
        return {
          title: 'Typography',
          icon: <TextT size={20} weight="regular" />,
          sections: [
            {
              title: 'Font Settings',
              content: 'Choose fonts and adjust typography settings for headings and body text throughout your page.'
            }
          ]
        };
      case 'special-effects':
        return {
          title: 'Special Effects',
          icon: <Sparkle size={20} weight="regular" />,
          sections: [
            {
              title: 'Featured Blocks',
              content: 'Select a block from the Layers tab to add featured effects that highlight important content.'
            }
          ]
        };
      case 'podcast':
        return {
          title: 'Podcast / rss',
          icon: <ApplePodcastsLogo size={20} weight="regular" />,
          sections: [
            {
              title: 'RSS Feed',
              content: 'Connect your podcast RSS feed to automatically pull episodes and enable the podcast player.'
            },
            {
              title: 'Podlinks',
              content: 'Podlinks are automatically generated platform links that appear in your social icons section.'
            }
          ]
        };
      case 'integration':
        return {
          title: 'Integrations',
          icon: <Plug size={20} weight="regular" />,
          sections: [
            {
              title: 'Third-Party Services',
              content: 'Connect external services like email marketing, analytics, and more to enhance your page.'
            }
          ]
        };
      case 'analytics':
        return {
          title: 'Analytics',
          icon: <TrendUp size={20} weight="regular" />,
          sections: [
            {
              title: 'Page Statistics',
              content: 'View detailed analytics about your page views, link clicks, and visitor engagement.'
            }
          ]
        };
      default:
        return {
          title: 'Information',
          icon: <Info size={20} weight="regular" />,
          sections: [
            {
              title: 'Getting Started',
              content: 'Use the tabs on the left to navigate between different editing sections.'
            }
          ]
        };
    }
  }, [activeTab]);

  return (
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
              <div className={styles.headerIcon}>
                {content.icon}
              </div>
              <h2 className={styles.title}>{content.title}</h2>
            </header>

            <div className={styles.sections}>
              {content.sections.map((section, index) => (
                <div key={index} className={styles.section}>
                  <h3 className={styles.sectionTitle}>{section.title}</h3>
                  <p className={styles.sectionContent}>{section.content}</p>
                </div>
              ))}
            </div>

            <div className={styles.helpSection}>
              <div className={styles.helpHeader}>
                <Question size={16} weight="regular" />
                <h3 className={styles.helpTitle}>Need Help?</h3>
              </div>
              <p className={styles.helpText}>
                Visit our documentation or contact support for assistance.
              </p>
            </div>
          </div>
        </ScrollArea.Viewport>
        <ScrollArea.Scrollbar orientation="vertical" className={styles.scrollbar}>
          <ScrollArea.Thumb className={styles.thumb} />
        </ScrollArea.Scrollbar>
      </ScrollArea.Root>
    </motion.div>
  );
}

