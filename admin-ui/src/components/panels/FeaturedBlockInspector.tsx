import { useEffect, useState, useMemo } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { LuStar } from 'react-icons/lu';

import { usePageSnapshot } from '../../api/page';
import { useUpdateWidgetMutation } from '../../api/widgets';
import { useWidgetSelection } from '../../state/widgetSelection';
import { queryKeys } from '../../api/utils';

import { type TabColorTheme } from '../layout/tab-colors';

import styles from './featured-block-inspector.module.css';

interface FeaturedBlockInspectorProps {
  activeColor: TabColorTheme;
}

type FeaturedEffect = 'jiggle' | 'burn' | 'rotating-glow' | 'blink' | 'pulse' | 'shake' | 'sparkles' | '';

const FEATURED_EFFECTS: Array<{ value: FeaturedEffect; label: string; emoji: string }> = [
  { value: 'jiggle', label: 'Jiggle', emoji: '🎯' },
  { value: 'burn', label: 'Burn', emoji: '🔥' },
  { value: 'rotating-glow', label: 'Rotating Glow', emoji: '💫' },
  { value: 'blink', label: 'Blink', emoji: '👁️' },
  { value: 'pulse', label: 'Pulse', emoji: '💓' },
  { value: 'shake', label: 'Shake', emoji: '📳' },
  { value: 'sparkles', label: 'Sparkles', emoji: '✨' }
];

export function FeaturedBlockInspector({ activeColor }: FeaturedBlockInspectorProps): JSX.Element {
  const queryClient = useQueryClient();
  const { data: snapshot } = usePageSnapshot();
  const selectedWidgetId = useWidgetSelection((state) => state.selectedWidgetId);
  const { mutateAsync: updateWidget, isPending: isSaving } = useUpdateWidgetMutation();

  const selectedWidget = useMemo(() => {
    if (!selectedWidgetId || !snapshot?.widgets) return undefined;
    return snapshot.widgets.find((widget) => String(widget.id) === selectedWidgetId);
  }, [selectedWidgetId, snapshot?.widgets]);

  const [isFeatured, setIsFeatured] = useState(false);
  const [featuredEffect, setFeaturedEffect] = useState<FeaturedEffect>('');

  useEffect(() => {
    if (selectedWidget) {
      setIsFeatured(selectedWidget.is_featured === 1);
      setFeaturedEffect((selectedWidget.featured_effect as FeaturedEffect) || 'jiggle');
    }
  }, [selectedWidget]);

  const hasChanges = useMemo(() => {
    if (!selectedWidget) return false;
    const currentFeatured = selectedWidget.is_featured === 1;
    const currentEffect = (selectedWidget.featured_effect as FeaturedEffect) || '';
    return isFeatured !== currentFeatured || featuredEffect !== currentEffect;
  }, [selectedWidget, isFeatured, featuredEffect]);

  const handleSave = async () => {
    if (!selectedWidget) return;

    try {
      await updateWidget({
        widget_id: String(selectedWidget.id),
        is_featured: isFeatured ? '1' : '0',
        featured_effect: isFeatured ? featuredEffect : ''
      });
      queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
    } catch (error) {
      console.error('Failed to update featured block', error);
    }
  };

  const handleReset = () => {
    if (selectedWidget) {
      setIsFeatured(selectedWidget.is_featured === 1);
      setFeaturedEffect((selectedWidget.featured_effect as FeaturedEffect) || 'jiggle');
    }
  };

  if (!selectedWidget) {
    return (
      <section className={styles.wrapper} aria-label="Featured block inspector">
        <p className={styles.placeholder}>Select a block to configure featured options.</p>
      </section>
    );
  }

  return (
    <section
      className={styles.wrapper}
      aria-label="Featured block inspector"
      style={{
        '--active-tab-color': activeColor.text,
        '--active-tab-bg': activeColor.primary,
        '--active-tab-light': activeColor.light,
        '--active-tab-border': activeColor.border
      } as React.CSSProperties}
    >
      <header className={styles.header}>
        <div>
          <h3>
            <LuStar aria-hidden="true" />
            Featured Block
          </h3>
          <p>Highlight this block with special effects to draw attention</p>
        </div>
      </header>

      <div className={styles.fieldset}>
        <label className={styles.checkboxLabel}>
          <input
            type="checkbox"
            className={styles.checkbox}
            checked={isFeatured}
            onChange={(e) => {
              setIsFeatured(e.target.checked);
              if (e.target.checked && !featuredEffect) {
                setFeaturedEffect('jiggle');
              }
            }}
          />
          <span>Mark as featured block</span>
        </label>
        <p className={styles.help}>
          When enabled, this block will be highlighted with special effects. Only one block can be featured at a time.
        </p>
      </div>

      {isFeatured && (
        <div className={styles.fieldset}>
          <label className={styles.label}>
            <span>Effect</span>
            <select
              className={styles.select}
              value={featuredEffect}
              onChange={(e) => setFeaturedEffect(e.target.value as FeaturedEffect)}
            >
              {FEATURED_EFFECTS.map((effect) => (
                <option key={effect.value} value={effect.value}>
                  {effect.label} {effect.emoji}
                </option>
              ))}
            </select>
          </label>
          <p className={styles.help}>
            Apply a special effect to your featured block to make it stand out. Movement effects (Jiggle, Shake, Pulse,
            Rotating Glow) animate at random intervals.
          </p>
        </div>
      )}

      <div className={styles.footer}>
        <button
          type="button"
          className={styles.resetButton}
          onClick={handleReset}
          disabled={!hasChanges || isSaving}
        >
          Reset
        </button>
        <button
          type="button"
          className={styles.saveButton}
          onClick={handleSave}
          disabled={!hasChanges || isSaving}
        >
          {isSaving ? 'Saving…' : 'Save changes'}
        </button>
      </div>
    </section>
  );
}

