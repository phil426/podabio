import { useMemo, useEffect, useState } from 'react';
import { motion } from 'framer-motion';
import * as ScrollArea from '@radix-ui/react-scroll-area';
import { Star, Sparkle, TextT } from '@phosphor-icons/react';
import { useQueryClient } from '@tanstack/react-query';
import { usePageSnapshot, usePageSettingsMutation } from '../../api/page';
import { useWidgetSelection } from '../../state/widgetSelection';
import { queryKeys } from '../../api/utils';
import { FeaturedBlockInspector } from './FeaturedBlockInspector';
import type { TabColorTheme } from '../layout/tab-colors';
import styles from './special-effects-panel.module.css';

interface SpecialEffectsPanelProps {
  activeColor: TabColorTheme;
}

type PageTitleEffect = 'jiggle' | 'burn' | 'rotating-glow' | 'blink' | 'pulse' | 'shake' | 'sparkles' | 'none' | '';

const PAGE_TITLE_EFFECTS: Array<{ value: PageTitleEffect; label: string; emoji: string }> = [
  { value: 'none', label: 'None', emoji: '' },
  { value: 'jiggle', label: 'Jiggle', emoji: '🎯' },
  { value: 'burn', label: 'Burn', emoji: '🔥' },
  { value: 'rotating-glow', label: 'Rotating Glow', emoji: '💫' },
  { value: 'blink', label: 'Blink', emoji: '👁️' },
  { value: 'pulse', label: 'Pulse', emoji: '💓' },
  { value: 'shake', label: 'Shake', emoji: '📳' },
  { value: 'sparkles', label: 'Sparkles', emoji: '✨' }
];

export function SpecialEffectsPanel({ activeColor }: SpecialEffectsPanelProps): JSX.Element {
  const { data: snapshot } = usePageSnapshot();
  const selectedWidgetId = useWidgetSelection((state) => state.selectedWidgetId);
  const pageSettingsMutation = usePageSettingsMutation();
  const queryClient = useQueryClient();

  const page = snapshot?.page;
  const [pageTitleEffect, setPageTitleEffect] = useState<PageTitleEffect>('none');

  useEffect(() => {
    // Load page_name_effect from snapshot, default to 'none' if not set
    const effect = page?.page_name_effect;
    if (effect && typeof effect === 'string' && effect.trim() !== '') {
      setPageTitleEffect(effect as PageTitleEffect);
    } else {
      setPageTitleEffect('none');
    }
  }, [page?.page_name_effect]);

  const selectedWidget = useMemo(() => {
    if (!selectedWidgetId || !snapshot?.widgets) return undefined;
    return snapshot.widgets.find((widget) => String(widget.id) === selectedWidgetId);
  }, [selectedWidgetId, snapshot?.widgets]);

  const isFeaturedWidget = selectedWidget?.is_featured === 1;

  const handlePageTitleEffectChange = async (effect: PageTitleEffect) => {
    const previousEffect = pageTitleEffect;
    setPageTitleEffect(effect);
    
    // Normalize 'none' to empty string for backend (which converts to NULL)
    const normalizedEffect = effect === 'none' ? '' : effect;
    
    try {
      await pageSettingsMutation.mutateAsync({
        page_name_effect: normalizedEffect
      });
      await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
    } catch (error) {
      console.error('Failed to update page title effect', error);
      // Revert on error
      setPageTitleEffect(previousEffect);
    }
  };

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
              <h2>
                <Sparkle aria-hidden="true" size={20} weight="regular" />
                Special Effects
              </h2>
              <p>Add animations and featured effects to highlight important blocks</p>
            </header>

            <div className={styles.section}>
              <h3>
                <TextT aria-hidden="true" size={16} weight="regular" />
                Page Title Effects
              </h3>
              <p className={styles.sectionDescription}>
                Apply special effects to your page title to make it stand out.
              </p>

              <div className={styles.control}>
                <label htmlFor="page-title-effect">Effect</label>
                <select
                  id="page-title-effect"
                  className={styles.select}
                  value={pageTitleEffect}
                  onChange={(e) => handlePageTitleEffectChange(e.target.value as PageTitleEffect)}
                  disabled={pageSettingsMutation.isPending}
                >
                  {PAGE_TITLE_EFFECTS.map((effect) => (
                    <option key={effect.value} value={effect.value}>
                      {effect.label} {effect.emoji}
                    </option>
                  ))}
                </select>
              </div>
              <p className={styles.help}>
                Apply a special effect to your page title. Movement effects (Jiggle, Shake, Pulse, Rotating Glow) animate at random intervals.
              </p>
            </div>

            <div className={styles.section}>
              <h3>
                <Star aria-hidden="true" size={16} weight="regular" />
                Featured Block Effects
              </h3>
              <p className={styles.sectionDescription}>
                Select a block to configure featured effects that will draw attention to it on your page.
              </p>

              {selectedWidget ? (
                <FeaturedBlockInspector activeColor={activeColor} />
              ) : (
                <div className={styles.emptyState}>
                  <Star aria-hidden="true" className={styles.emptyIcon} size={48} weight="regular" />
                  <p>Select a block from the Layers tab to configure featured effects</p>
                </div>
              )}
            </div>

            <div className={styles.section}>
              <h3>Coming Soon</h3>
              <p className={styles.sectionDescription}>
                More advanced animation and effect options will be available here soon.
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

