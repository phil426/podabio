/**
 * Widget Settings Section
 * Settings for widget background, border, radius, shadow, and glow
 */

import { BackgroundColorSwatch } from '../../../controls/BackgroundColorSwatch';
import { PodaColorPicker } from '../../../controls/PodaColorPicker';
import { WidgetBorderEffectSelect } from '../../ultimate-theme-modifier/WidgetBorderEffectSelect';
import { SliderInput } from '../../ultimate-theme-modifier/SliderInput';
import type { TabColorTheme } from '../../../layout/tab-colors';
import { usePageSnapshot } from '../../../../api/page';
import { useUpdateWidgetMutation } from '../../../../api/widgets';
import { useQueryClient } from '@tanstack/react-query';
import { queryKeys } from '../../../../api/utils';
import styles from './widget-button-section.module.css';

interface WidgetSettingsSectionProps {
  uiState: Record<string, unknown>;
  onFieldChange: (fieldId: string, value: unknown) => void;
  activeColor: TabColorTheme;
  widgetId?: string | null;
}

export function WidgetSettingsSection({
  uiState,
  onFieldChange,
  activeColor,
  widgetId
}: WidgetSettingsSectionProps): JSX.Element {
  const { data: snapshot } = usePageSnapshot();
  const widgets = snapshot?.widgets || [];
  // Try multiple ways to find the widget (handle both string and number IDs)
  const widget = widgetId ? widgets.find((w) => {
    const wId = String(w.id);
    const searchId = String(widgetId);
    return wId === searchId || wId === widgetId || String(w.id) === widgetId;
  }) : null;
  const isFeatured = widget?.is_featured === 1;
  const updateWidgetMutation = useUpdateWidgetMutation();
  const queryClient = useQueryClient();
  
  // Widget background/border/shadow/glow values
  const widgetBackground = (uiState['widget-background'] as string) ?? '#ffffff';
  const widgetBorderColor = (uiState['widget-border-color'] as string) ?? '#e2e8f0';
  const widgetBorderWidth = (uiState['widget-border-width'] as number) ?? 0;
  const widgetRounding = (uiState['widget-rounding'] as number) ?? 12;
  const widgetBorderEffect = (uiState['widget-border-effect'] as string) ?? 'none';
  const widgetShadowDepth = (uiState['widget-shadow-depth'] as number) ?? 1;
  const widgetShadowColor = (uiState['widget-shadow-color'] as string) ?? 'rgba(15, 23, 42, 0.12)';
  const widgetShadowIntensity = (uiState['widget-shadow-intensity'] as number) ?? 1;
  const widgetGlowWidth = (uiState['widget-glow-width'] as number) ?? 2;
  const widgetGlowColor = (uiState['widget-glow-color'] as string) ?? '#2563eb';
  const widgetGlowIntensity = (uiState['widget-glow-intensity'] as number) ?? 1;
  
  // Featured widget effect
  const featuredEffect = widget?.featured_effect ?? 'jiggle';
  
  const handleFeaturedEffectChange = (effect: string) => {
    if (widgetId) {
      updateWidgetMutation.mutate(
        { widget_id: widgetId, featured_effect: effect },
        {
          onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
          }
        }
      );
    }
  };

  return (
    <div className={styles.section}>
      {/* Widget Background & Border */}
      <div className={styles.subsection}>
        <h4 className={styles.subsectionTitle}>Background & Border</h4>
        
        <div className={styles.fieldGroup}>
          <label className={styles.label}>Background</label>
          <BackgroundColorSwatch
            value={widgetBackground}
            onChange={(value) => onFieldChange('widget-background', value)}
            label="Widget background"
          />
        </div>

        <div className={styles.fieldGroup}>
          <label className={styles.label}>Border Color</label>
          <BackgroundColorSwatch
            value={widgetBorderColor}
            onChange={(value) => onFieldChange('widget-border-color', value)}
            label="Widget border color"
          />
        </div>

        <div className={styles.fieldGroup}>
          <label className={styles.label}>Border Width</label>
          <SliderInput
            value={widgetBorderWidth}
            min={0}
            max={8}
            step={1}
            unit="px"
            onChange={(value) => onFieldChange('widget-border-width', value)}
          />
        </div>

        <div className={styles.fieldGroup}>
          <label className={styles.label}>Border Radius</label>
          <SliderInput
            value={widgetRounding}
            min={0}
            max={50}
            step={1}
            unit="px"
            onChange={(value) => onFieldChange('widget-rounding', value)}
          />
        </div>
      </div>

      {/* Shadow/Glow Effects */}
      <div className={styles.subsection}>
        <h4 className={styles.subsectionTitle}>Shadow & Glow</h4>
        
        <div className={styles.fieldGroup}>
          <label className={styles.label}>Effect Type</label>
          <WidgetBorderEffectSelect
            value={widgetBorderEffect as 'none' | 'shadow' | 'glow'}
            onChange={(value) => onFieldChange('widget-border-effect', value)}
          />
        </div>

        {widgetBorderEffect === 'shadow' && (
          <>
            <div className={styles.fieldGroup}>
              <label className={styles.label}>Shadow Depth</label>
              <SliderInput
                value={widgetShadowDepth}
                min={0}
                max={10}
                step={1}
                onChange={(value) => onFieldChange('widget-shadow-depth', value)}
              />
            </div>

            <div className={styles.fieldGroup}>
              <label className={styles.label}>Shadow Color</label>
              <BackgroundColorSwatch
                value={widgetShadowColor}
                onChange={(value) => onFieldChange('widget-shadow-color', value)}
                label="Shadow color"
              />
            </div>

            <div className={styles.fieldGroup}>
              <label className={styles.label}>Shadow Intensity</label>
              <SliderInput
                value={widgetShadowIntensity}
                min={0}
                max={1}
                step={0.1}
                onChange={(value) => onFieldChange('widget-shadow-intensity', value)}
              />
            </div>
          </>
        )}

        {widgetBorderEffect === 'glow' && (
          <>
            <div className={styles.fieldGroup}>
              <label className={styles.label}>Glow Width</label>
              <SliderInput
                value={widgetGlowWidth}
                min={0}
                max={20}
                step={1}
                unit="px"
                onChange={(value) => onFieldChange('widget-glow-width', value)}
              />
            </div>

            <div className={styles.fieldGroup}>
              <label className={styles.label}>Glow Color</label>
              <BackgroundColorSwatch
                value={widgetGlowColor}
                onChange={(value) => onFieldChange('widget-glow-color', value)}
                label="Glow color"
              />
            </div>

            <div className={styles.fieldGroup}>
              <label className={styles.label}>Glow Intensity</label>
              <SliderInput
                value={widgetGlowIntensity}
                min={0}
                max={1}
                step={0.1}
                onChange={(value) => onFieldChange('widget-glow-intensity', value)}
              />
            </div>
          </>
        )}
      </div>
      
      {/* Featured Widget Properties - Only show if widget is featured */}
      {isFeatured && (
        <div className={styles.subsection}>
          <h4 className={styles.subsectionTitle}>Featured Widget</h4>
          
          <div className={styles.fieldGroup}>
            <label className={styles.label}>Featured Effect</label>
            <select
              value={featuredEffect}
              onChange={(e) => handleFeaturedEffectChange(e.target.value)}
              className={styles.select}
            >
              <option value="jiggle">Jiggle</option>
              <option value="pulse">Pulse</option>
              <option value="bounce">Bounce</option>
              <option value="glow">Glow</option>
              <option value="none">None</option>
            </select>
          </div>
        </div>
      )}
    </div>
  );
}

