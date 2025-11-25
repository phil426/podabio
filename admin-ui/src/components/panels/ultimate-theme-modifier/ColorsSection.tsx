import { useState, useMemo } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Palette, Square, Sparkle, PaintBrush } from '@phosphor-icons/react';
import { SectionHeader } from './SectionHeader';
import { PageSettingsPanel } from './PageSettingsPanel';
import { WidgetColorsPanel } from './WidgetColorsPanel';
import { ThemePreviewCard } from '../ThemePreviewCard';
import { useThemeLibraryQuery } from '../../../api/themes';
import { usePageSnapshot } from '../../../api/page';
import { updatePageThemeId } from '../../../api/page';
import { useQueryClient } from '@tanstack/react-query';
import { queryKeys } from '../../../api/utils';
import type { ThemeRecord } from '../../../api/types';
import type { TokenBundle } from '../../../design-system/tokens';
import styles from './colors-section.module.css';

interface ColorsSectionProps {
  tokens: TokenBundle;
  onTokenChange: (path: string, value: unknown, oldValue: unknown) => void;
  searchQuery?: string;
  tokenValues?: Map<string, unknown>;
  pageBackground?: string | null;
  pageHeadingText?: string | null;
  pageBodyText?: string | null;
  widgetBackground?: string | null;
  widgetBorder?: string | null;
  widgetShadow?: string | null;
  widgetHeadingText?: string | null;
  widgetBodyText?: string | null;
  socialIconColor?: string | null;
}

export function ColorsSection({ tokens, onTokenChange, searchQuery = '', tokenValues = new Map(), pageBackground, pageHeadingText, pageBodyText, widgetBackground, widgetBorder, widgetShadow, widgetHeadingText, widgetBodyText, socialIconColor }: ColorsSectionProps): JSX.Element {
  const [expandedGroups, setExpandedGroups] = useState<Set<string>>(new Set([
    'themes', 'customization', 'page-colors', 'widget-colors', 'podcast-colors'
  ]));
  const { data: themeLibrary } = useThemeLibraryQuery();
  const { data: snapshot } = usePageSnapshot();
  const queryClient = useQueryClient();

  const currentThemeId = snapshot?.page?.theme_id ?? null;
  const systemThemes: ThemeRecord[] = themeLibrary?.system ?? [];
  const userThemes: ThemeRecord[] = themeLibrary?.user ?? [];
  const allThemes = [...systemThemes, ...userThemes];

  const toggleGroup = (group: string) => {
    setExpandedGroups(prev => {
      const next = new Set(prev);
      if (next.has(group)) {
        next.delete(group);
      } else {
        next.add(group);
      }
      return next;
    });
  };

  const handleApplyTheme = async (theme: ThemeRecord) => {
    try {
      // Extract page background from theme
      let pageBackground: string | null | undefined = theme.page_background;
      
      // If page_background is not set, try to extract from color_tokens
      if (!pageBackground && theme.color_tokens) {
        try {
          const colorTokens = typeof theme.color_tokens === 'string' 
            ? JSON.parse(theme.color_tokens) 
            : theme.color_tokens;
          
          // Try semantic.surface.canvas path
          if (colorTokens?.semantic?.surface?.canvas) {
            pageBackground = colorTokens.semantic.surface.canvas as string;
          }
          // Try semantic.surface.background path
          else if (colorTokens?.semantic?.surface?.background) {
            pageBackground = colorTokens.semantic.surface.background as string;
          }
          // Try gradient.page path
          else if (colorTokens?.gradient?.page) {
            pageBackground = colorTokens.gradient.page as string;
          }
        } catch (e) {
          // If parsing fails, use null to let theme value be used
          console.warn('Failed to parse color_tokens:', e);
        }
      }
      
      // Parse widget_styles if it's a string
      let widgetStyles: Record<string, unknown> | string | null = null;
      if (theme.widget_styles) {
        if (typeof theme.widget_styles === 'string') {
          try {
            widgetStyles = JSON.parse(theme.widget_styles);
          } catch (e) {
            console.warn('Failed to parse widget_styles:', e);
            widgetStyles = theme.widget_styles;
          }
        } else {
          widgetStyles = theme.widget_styles;
        }
      }
      
      // Extract widget background (prioritize direct column over color_tokens)
      const widgetBackground = theme.widget_background ?? null;
      
      // Use updatePageThemeId with all theme fields
      // Pass null to clear page-level overrides (so theme values are used)
      await updatePageThemeId(theme.id, {
        page_background: pageBackground ?? null,
        widget_background: widgetBackground,
        widget_border_color: theme.widget_border_color ?? null,
        page_primary_font: theme.page_primary_font ?? null,
        page_secondary_font: theme.page_secondary_font ?? null,
        widget_primary_font: theme.widget_primary_font ?? null,
        widget_secondary_font: theme.widget_secondary_font ?? null,
        widget_styles: widgetStyles,
        spatial_effect: theme.spatial_effect ?? null
      });
      
      // Invalidate and refetch queries to update the UI
      await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
      await queryClient.refetchQueries({ queryKey: queryKeys.pageSnapshot() });
    } catch (err) {
      console.error('Failed to apply theme:', err);
    }
  };

  // Filter by search query
  const matchesSearch = (label: string, path: string): boolean => {
    if (!searchQuery) return true;
    const query = searchQuery.toLowerCase();
    return label.toLowerCase().includes(query) || path.toLowerCase().includes(query);
  };

  return (
    <div className={styles.section}>
      {/* Themes Section */}
      {matchesSearch('Themes', 'themes') && (
        <div className={styles.group}>
          <SectionHeader
            icon={<PaintBrush weight="bold" />}
            title="Themes"
            isExpanded={expandedGroups.has('themes')}
            onToggle={() => toggleGroup('themes')}
          />
          <AnimatePresence>
            {expandedGroups.has('themes') && (
              <motion.div
                initial={{ height: 0, opacity: 0 }}
                animate={{ height: 'auto', opacity: 1 }}
                exit={{ height: 0, opacity: 0 }}
                transition={{ duration: 0.2, ease: 'easeInOut' }}
                className={styles.groupContent}
              >
                <div className={styles.themesGrid}>
                  {allThemes.map((theme) => (
                    <ThemePreviewCard
                      key={theme.id}
                      theme={theme}
                      selected={currentThemeId === theme.id}
                      onSelect={() => handleApplyTheme(theme)}
                    />
                  ))}
                </div>
              </motion.div>
            )}
          </AnimatePresence>
        </div>
      )}

      {/* Customization Section */}
      {matchesSearch('Customization', 'customization') && (
        <div className={styles.group}>
          <SectionHeader
            icon={<Palette weight="bold" />}
            title="Customization"
            isExpanded={expandedGroups.has('customization')}
            onToggle={() => toggleGroup('customization')}
          />
          <AnimatePresence>
            {expandedGroups.has('customization') && (
              <motion.div
                initial={{ height: 0, opacity: 0 }}
                animate={{ height: 'auto', opacity: 1 }}
                exit={{ height: 0, opacity: 0 }}
                transition={{ duration: 0.2, ease: 'easeInOut' }}
                className={styles.groupContent}
              >
                {/* Page Settings */}
                {matchesSearch('Page Settings', 'page-colors') && (
                  <div className={styles.nestedGroup}>
                    <SectionHeader
                      icon={<Palette weight="bold" />}
                      title="Page Settings"
                      isExpanded={expandedGroups.has('page-colors')}
                      onToggle={() => toggleGroup('page-colors')}
                    />
                    <AnimatePresence>
                      {expandedGroups.has('page-colors') && (
                        <motion.div
                          initial={{ height: 0, opacity: 0 }}
                          animate={{ height: 'auto', opacity: 1 }}
                          exit={{ height: 0, opacity: 0 }}
                          transition={{ duration: 0.2, ease: 'easeInOut' }}
                          className={styles.groupContent}
                        >
                          <PageSettingsPanel
                            tokens={tokens}
                            tokenValues={tokenValues}
                            onTokenChange={onTokenChange}
                            pageBackground={pageBackground}
                            pageHeadingText={pageHeadingText}
                            pageBodyText={pageBodyText}
                          />
                        </motion.div>
                      )}
                    </AnimatePresence>
                  </div>
                )}

                {/* Block & Widgets Settings */}
                {matchesSearch('Block & Widgets Settings', 'widget') && (
                  <div className={styles.nestedGroup}>
                    <SectionHeader
                      icon={<Square weight="bold" />}
                      title="Block & Widgets Settings"
                      isExpanded={expandedGroups.has('widget-colors')}
                      onToggle={() => toggleGroup('widget-colors')}
                    />
                    <AnimatePresence>
                      {expandedGroups.has('widget-colors') && (
                        <motion.div
                          initial={{ height: 0, opacity: 0 }}
                          animate={{ height: 'auto', opacity: 1 }}
                          exit={{ height: 0, opacity: 0 }}
                          transition={{ duration: 0.2, ease: 'easeInOut' }}
                          className={styles.groupContent}
                        >
                          <WidgetColorsPanel
                            tokens={tokens}
                            tokenValues={tokenValues}
                            onTokenChange={onTokenChange}
                            widgetBackground={widgetBackground}
                            widgetBorder={widgetBorder}
                            widgetShadow={widgetShadow}
                            widgetHeadingText={widgetHeadingText}
                            widgetBodyText={widgetBodyText}
                            socialIconColor={socialIconColor}
                          />
                        </motion.div>
                      )}
                    </AnimatePresence>
                  </div>
                )}

                {/* Podcast Player Colors */}
                {matchesSearch('Podcast Player Colors', 'podcast') && (
                  <div className={styles.nestedGroup}>
                    <SectionHeader
                      icon={<Sparkle weight="bold" />}
                      title="Podcast Player Colors"
                      isExpanded={expandedGroups.has('podcast-colors')}
                      onToggle={() => toggleGroup('podcast-colors')}
                    />
                    <AnimatePresence>
                      {expandedGroups.has('podcast-colors') && (
                        <motion.div
                          initial={{ height: 0, opacity: 0 }}
                          animate={{ height: 'auto', opacity: 1 }}
                          exit={{ height: 0, opacity: 0 }}
                          transition={{ duration: 0.2, ease: 'easeInOut' }}
                          className={styles.groupContent}
                        >
                          <div className={styles.comingSoon}>
                            Coming Soon
                          </div>
                        </motion.div>
                      )}
                    </AnimatePresence>
                  </div>
                )}
              </motion.div>
            )}
          </AnimatePresence>
        </div>
      )}
    </div>
  );
}

