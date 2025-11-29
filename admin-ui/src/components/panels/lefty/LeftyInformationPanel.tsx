import { useMemo } from 'react';
import { motion } from 'framer-motion';
import * as ScrollArea from '@radix-ui/react-scroll-area';
import { Info, Question, TextT, Sparkle, ApplePodcastsLogo, Plug, TrendUp, ArrowSquareOut, Palette } from '@phosphor-icons/react';
import type { TabColorTheme } from '../../layout/tab-colors';
import type { LeftyTabValue } from '../../layout/tab-colors';
import styles from './lefty-information-panel.module.css';

interface LeftyInformationPanelProps {
  activeColor: TabColorTheme;
  activeTab: LeftyTabValue;
}

interface DocumentationLink {
  title: string;
  url: string;
  description?: string;
}

export function LeftyInformationPanel({ activeColor, activeTab }: LeftyInformationPanelProps): JSX.Element {
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
          ],
          documentation: [
            {
              title: 'Widget Guide',
              url: '/docs/widgets',
              description: 'Learn about all available widgets and how to use them'
            },
            {
              title: 'Adding Blocks',
              url: '/studio-docs.php#adding-content',
              description: 'Step-by-step guide to adding content blocks'
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
          ],
          documentation: [
            {
              title: 'Layer Management',
              url: '/studio-docs.php#layers',
              description: 'Complete guide to managing and organizing your page layers'
            },
            {
              title: 'Profile Setup',
              url: '/docs/profile',
              description: 'How to set up and customize your profile'
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
          ],
          documentation: [
            {
              title: 'Featured Effects',
              url: '/studio-docs.php#special-effects',
              description: 'How to use featured effects to highlight important blocks'
            },
            {
              title: 'Animations',
              url: '/docs/animations',
              description: 'Guide to adding animations and special effects'
            }
          ]
        };
      case 'podcast':
        return {
          title: 'Podcast',
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
          ],
          documentation: [
            {
              title: 'Podcast Setup',
              url: '/studio-docs.php#podcast',
              description: 'Complete guide to setting up your podcast RSS feed and player'
            },
            {
              title: 'Podlinks Guide',
              url: '/docs/podlinks',
              description: 'Learn about podlinks and how they work'
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
          ],
          documentation: [
            {
              title: 'Integrations Guide',
              url: '/studio-docs.php#integrations',
              description: 'How to connect third-party services and integrations'
            },
            {
              title: 'API Documentation',
              url: '/docs/api',
              description: 'Technical documentation for API integrations'
            }
          ]
        };
      case 'themes':
        return {
          title: 'Themes',
          icon: <Palette size={20} weight="regular" />,
          sections: [
            {
              title: 'Theme Library',
              content: 'Browse and apply pre-designed themes to instantly change the look and feel of your page.'
            },
            {
              title: 'Custom Themes',
              content: 'Create custom themes with your own colors, fonts, and styling. Save and reuse themes across your pages.'
            },
            {
              title: 'Theme Editor',
              content: 'Fine-tune every aspect of your theme including colors, typography, spacing, and special effects.'
            }
          ],
          documentation: [
            {
              title: 'Themes Guide',
              url: '/studio-docs.php#themes',
              description: 'Complete guide to using and customizing themes'
            },
            {
              title: 'Creating Custom Themes',
              url: '/docs/themes',
              description: 'Learn how to create and save your own custom themes'
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
          ],
          documentation: [
            {
              title: 'Analytics Guide',
              url: '/studio-docs.php#analytics',
              description: 'Understanding your page analytics and metrics'
            },
            {
              title: 'Tracking Setup',
              url: '/docs/analytics',
              description: 'How to set up and configure analytics tracking'
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
          ],
          documentation: [
            {
              title: 'Getting Started Guide',
              url: '/studio-docs.php',
              description: 'Complete guide to using PodaBio Studio'
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
              {content.documentation && content.documentation.length > 0 && (
                <div className={styles.documentationLinks}>
                  {content.documentation.map((doc, index) => (
                    <a
                      key={index}
                      href={doc.url}
                      target="_blank"
                      rel="noopener noreferrer"
                      className={styles.docLink}
                    >
                      <ArrowSquareOut size={16} weight="regular" />
                      <div className={styles.docLinkContent}>
                        <span className={styles.docLinkTitle}>{doc.title}</span>
                        {doc.description && (
                          <span className={styles.docLinkDescription}>{doc.description}</span>
                        )}
                      </div>
                    </a>
                  ))}
                </div>
              )}
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

