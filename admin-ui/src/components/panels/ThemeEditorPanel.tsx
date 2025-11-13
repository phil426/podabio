import { useEffect, useMemo, useState } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { LuX, LuCheck, LuCircleX, LuPalette, LuType, LuLayoutGrid, LuShapes, LuSave, LuCopy } from 'react-icons/lu';

import { usePageSnapshot } from '../../api/page';
import { useThemeLibraryQuery, useUpdateThemeMutation, useCreateThemeMutation } from '../../api/themes';
import { useTokens } from '../../design-system/theme/TokenProvider';
import { useThemeInspector } from '../../state/themeInspector';
import { queryKeys } from '../../api/utils';
import { ColorTokenPicker } from '../controls/ColorTokenPicker';
import { ColorPaletteEditor } from './ColorPaletteEditor';
import { TokenAccordion, type TokenAccordionItem } from '../system/TokenAccordion';
import type { TabColorTheme } from '../layout/tab-colors';
import type { ThemeRecord } from '../../api/types';
import type { TokenBundle } from '../../design-system/tokens';

import styles from './theme-editor-panel.module.css';

interface ThemeEditorPanelProps {
  activeColor: TabColorTheme;
  theme?: ThemeRecord | null;
  onSave?: () => void;
}

function safeParse(input: string | null | undefined | Record<string, unknown>): Record<string, unknown> | null {
  if (!input) return null;
  if (typeof input === 'object' && !Array.isArray(input)) return input as Record<string, unknown>;
  if (typeof input !== 'string') return null;
  try {
    return JSON.parse(input);
  } catch {
    return null;
  }
}

function resolveToken(bundle: TokenBundle, path: string): unknown {
  const parts = path.split('.');
  let current: any = bundle;
  
  for (const part of parts) {
    if (current && typeof current === 'object' && part in current) {
      current = current[part];
    } else {
      return undefined;
    }
  }
  
  return current;
}

function extractColorValue(tokens: TokenBundle, path: string): string {
  const resolved = resolveToken(tokens, path);
  
  // If it's already a hex color, return it
  if (typeof resolved === 'string' && /^#([0-9a-fA-F]{3}){1,2}$/.test(resolved)) {
    return resolved;
  }
  
  // If it's a token reference, try to resolve it
  if (typeof resolved === 'string' && resolved.startsWith('color.')) {
    // Try to resolve the reference
    const refParts = resolved.split('.');
    let refCurrent: any = tokens;
    for (const part of refParts) {
      if (refCurrent && typeof refCurrent === 'object' && part in refCurrent) {
        refCurrent = refCurrent[part];
      } else {
        break;
      }
    }
    if (typeof refCurrent === 'string' && /^#([0-9a-fA-F]{3}){1,2}$/.test(refCurrent)) {
      return refCurrent;
    }
  }
  
  // Fallback to defaults based on path
  if (path.includes('accent.primary')) return '#2563eb';
  if (path.includes('accent.secondary')) return '#7c3aed';
  if (path.includes('text.primary')) return '#0f172a';
  if (path.includes('text.secondary')) return '#64748b';
  if (path.includes('surface.canvas')) return '#ffffff';
  
  return '#2563eb';
}

function applyTokenUpdate<T extends Record<string, any>>(obj: T, path: string, value: unknown): T {
  const parts = path.split('.');
  const result = { ...obj };
  let current: any = result;
  
  for (let i = 0; i < parts.length - 1; i++) {
    const key = parts[i];
    if (!(key in current) || typeof current[key] !== 'object' || current[key] === null) {
      current[key] = {};
    } else {
      current[key] = { ...current[key] };
    }
    current = current[key];
  }
  
  current[parts[parts.length - 1]] = value;
  return result;
}

export function ThemeEditorPanel({ activeColor, theme, onSave }: ThemeEditorPanelProps): JSX.Element {
  const { data: snapshot } = usePageSnapshot();
  const { data: themeLibrary } = useThemeLibraryQuery();
  const queryClient = useQueryClient();
  const { tokens, setTokens } = useTokens();
  const { setThemeInspectorVisible } = useThemeInspector();
  const updateMutation = useUpdateThemeMutation();
  const createMutation = useCreateThemeMutation();
  
  const [themeName, setThemeName] = useState(theme?.name ?? '');
  const [saveStatus, setSaveStatus] = useState<'idle' | 'saving' | 'success' | 'error'>('idle');
  const [statusMessage, setStatusMessage] = useState<string | null>(null);
  const [hasChanges, setHasChanges] = useState(false);
  
  // Color values from tokens
  const primaryColor = useMemo(() => 
    extractColorValue(tokens, 'semantic.accent.primary'),
    [tokens]
  );
  const secondaryColor = useMemo(() => 
    extractColorValue(tokens, 'semantic.accent.secondary'),
    [tokens]
  );
  const accentColor = useMemo(() => 
    extractColorValue(tokens, 'semantic.accent.primary'),
    [tokens]
  );
  const pageBackground = useMemo(() => 
    extractColorValue(tokens, 'semantic.surface.canvas'),
    [tokens]
  );
  const textPrimary = useMemo(() => 
    extractColorValue(tokens, 'semantic.text.primary'),
    [tokens]
  );
  const textSecondary = useMemo(() => 
    extractColorValue(tokens, 'semantic.text.secondary'),
    [tokens]
  );
  
  // Typography
  const headingFont = useMemo(() => {
    const font = tokens.core?.typography?.font?.heading;
    return typeof font === 'string' ? font : 'Inter';
  }, [tokens]);
  
  const bodyFont = useMemo(() => {
    const font = tokens.core?.typography?.font?.body;
    return typeof font === 'string' ? font : 'Inter';
  }, [tokens]);
  
  const [fontSizePreset, setFontSizePreset] = useState<'small' | 'medium' | 'large' | 'xlarge'>('medium');
  
  // Spacing density
  const [spacingDensity, setSpacingDensity] = useState<'compact' | 'cozy' | 'comfortable'>('cozy');
  
  // Shape & Effects
  const [buttonRadius, setButtonRadius] = useState<'none' | 'small' | 'medium' | 'large' | 'pill'>('medium');
  const [widgetBorderWidth, setWidgetBorderWidth] = useState<'none' | 'thin' | 'thick'>('thin');
  const [shadowLevel, setShadowLevel] = useState<'none' | 'subtle' | 'pronounced'>('subtle');
  
  useEffect(() => {
    if (theme) {
      setThemeName(theme.name);
      
      // Load theme token values and apply to tokens
      const colorTokens = safeParse(theme.color_tokens);
      const typographyTokens = safeParse(theme.typography_tokens);
      const spacingTokens = safeParse(theme.spacing_tokens);
      const shapeTokens = safeParse(theme.shape_tokens);
      
      let updatedTokens = { ...tokens };
      
      // Apply color tokens
      if (colorTokens) {
        Object.entries(colorTokens).forEach(([key, value]) => {
          if (typeof value === 'object' && value !== null) {
            Object.entries(value).forEach(([subKey, subValue]) => {
              if (typeof subValue === 'string') {
                const path = `semantic.${key}.${subKey}`;
                updatedTokens = applyTokenUpdate(updatedTokens, path, subValue);
              }
            });
          }
        });
      }
      
      // Apply typography tokens
      if (typographyTokens && typeof typographyTokens === 'object') {
        const fonts = (typographyTokens as any).font;
        if (fonts) {
          if (fonts.heading && typeof fonts.heading === 'string') {
            updatedTokens = applyTokenUpdate(updatedTokens, 'core.typography.font.heading', fonts.heading);
          }
          if (fonts.body && typeof fonts.body === 'string') {
            updatedTokens = applyTokenUpdate(updatedTokens, 'core.typography.font.body', fonts.body);
          }
        }
      }
      
      // Apply spacing tokens
      if (spacingTokens && typeof spacingTokens === 'object') {
        const density = (spacingTokens as any).density;
        if (density && ['compact', 'cozy', 'comfortable'].includes(density)) {
          setSpacingDensity(density as 'compact' | 'cozy' | 'comfortable');
        }
      }
      
      // Apply shape tokens
      if (shapeTokens && typeof shapeTokens === 'object') {
        const buttonRadius = (shapeTokens as any).button_radius;
        if (buttonRadius) {
          if (buttonRadius === '0' || buttonRadius === '0px') setButtonRadius('none');
          else if (buttonRadius === '0.375rem' || buttonRadius === '6px') setButtonRadius('small');
          else if (buttonRadius === '0.75rem' || buttonRadius === '12px') setButtonRadius('medium');
          else if (buttonRadius === '1.5rem' || buttonRadius === '24px') setButtonRadius('large');
          else if (buttonRadius === '9999px') setButtonRadius('pill');
        }
        
        const borderWidth = (shapeTokens as any).widget_border_width;
        if (borderWidth) {
          if (borderWidth === '0' || borderWidth === '0px') setWidgetBorderWidth('none');
          else if (borderWidth === '1px') setWidgetBorderWidth('thin');
          else if (borderWidth === '2px' || borderWidth === '4px') setWidgetBorderWidth('thick');
        }
        
        const shadow = (shapeTokens as any).shadow_level;
        if (shadow && ['none', 'subtle', 'pronounced'].includes(shadow)) {
          setShadowLevel(shadow as 'none' | 'subtle' | 'pronounced');
        }
      }
      
      setTokens(updatedTokens);
      setHasChanges(false);
    } else {
      // Reset to defaults when no theme
      setThemeName('');
      setHasChanges(false);
    }
  }, [theme?.id, setTokens]); // Only depend on theme ID to avoid infinite loops
  
  useEffect(() => {
    if (saveStatus === 'success' || saveStatus === 'error') {
      const timer = setTimeout(() => {
        setSaveStatus('idle');
        setStatusMessage(null);
      }, 3000);
      return () => clearTimeout(timer);
    }
  }, [saveStatus]);
  
  const handleColorChange = (tokenPath: string, value: string) => {
    setTokens(applyTokenUpdate(tokens, tokenPath, value));
    setHasChanges(true);
  };
  
  const handleFontChange = (tokenPath: string, value: string) => {
    setTokens(applyTokenUpdate(tokens, tokenPath, value));
    setHasChanges(true);
  };
  
  const handlePresetChange = (preset: 'small' | 'medium' | 'large' | 'xlarge') => {
    setFontSizePreset(preset);
    setHasChanges(true);
  };
  
  const handleDensityChange = (density: 'compact' | 'cozy' | 'comfortable') => {
    setSpacingDensity(density);
    setHasChanges(true);
  };
  
  const handleShapeChange = (
    type: 'buttonRadius' | 'widgetBorderWidth' | 'shadowLevel',
    value: any
  ) => {
    if (type === 'buttonRadius') setButtonRadius(value);
    else if (type === 'widgetBorderWidth') setWidgetBorderWidth(value);
    else if (type === 'shadowLevel') setShadowLevel(value);
    setHasChanges(true);
  };
  
  const handleSave = async () => {
    if (!themeName.trim()) {
      setSaveStatus('error');
      setStatusMessage('Theme name is required');
      return;
    }
    
    try {
      setSaveStatus('saving');
      
      // Build theme data from current tokens
      const colorTokens: Record<string, any> = {
        accent: {
          primary: extractColorValue(tokens, 'semantic.accent.primary'),
          secondary: extractColorValue(tokens, 'semantic.accent.secondary')
        },
        text: {
          primary: extractColorValue(tokens, 'semantic.text.primary'),
          secondary: extractColorValue(tokens, 'semantic.text.secondary')
        },
        background: {
          base: extractColorValue(tokens, 'semantic.surface.canvas')
        }
      };
      
      const typographyTokens: Record<string, any> = {
        font: {
          heading: headingFont,
          body: bodyFont
        },
        scale: {
          // Map preset to scale values
          ...(fontSizePreset === 'small' ? { md: 1.1, sm: 0.9 } :
              fontSizePreset === 'medium' ? { md: 1.333, sm: 1.111 } :
              fontSizePreset === 'large' ? { md: 1.5, sm: 1.25 } :
              { md: 1.777, sm: 1.5 })
        }
      };
      
      const spacingTokens: Record<string, any> = {
        density: spacingDensity
      };
      
      const shapeTokens: Record<string, any> = {
        button_radius: buttonRadius === 'none' ? '0' :
                       buttonRadius === 'small' ? '0.375rem' :
                       buttonRadius === 'medium' ? '0.75rem' :
                       buttonRadius === 'large' ? '1.5rem' : '9999px',
        widget_border_width: widgetBorderWidth === 'none' ? '0' :
                            widgetBorderWidth === 'thin' ? '1px' : '2px',
        shadow_level: shadowLevel
      };
      
      const themeData = {
        name: themeName.trim(),
        color_tokens: colorTokens,
        typography_tokens: typographyTokens,
        spacing_tokens: spacingTokens,
        shape_tokens: shapeTokens,
        page_background: pageBackground
      };
      
      if (theme?.id) {
        // Update existing theme
        await updateMutation.mutateAsync({ themeId: theme.id, data: themeData });
        setStatusMessage('Theme updated successfully');
      } else {
        // Create new theme
        await createMutation.mutateAsync(themeData);
        setStatusMessage('Theme created successfully');
      }
      
      setSaveStatus('success');
      setHasChanges(false);
      await queryClient.invalidateQueries({ queryKey: queryKeys.themes() });
      await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
      onSave?.();
    } catch (error) {
      console.error('Failed to save theme', error);
      setSaveStatus('error');
      setStatusMessage(error instanceof Error ? error.message : 'Failed to save theme');
    }
  };
  
  const handleSaveAsNew = async () => {
    if (!themeName.trim()) {
      setSaveStatus('error');
      setStatusMessage('Theme name is required');
      return;
    }
    
    try {
      setSaveStatus('saving');
      
      const colorTokens: Record<string, any> = {
        accent: {
          primary: extractColorValue(tokens, 'semantic.accent.primary'),
          secondary: extractColorValue(tokens, 'semantic.accent.secondary')
        },
        text: {
          primary: extractColorValue(tokens, 'semantic.text.primary'),
          secondary: extractColorValue(tokens, 'semantic.text.secondary')
        },
        background: {
          base: extractColorValue(tokens, 'semantic.surface.canvas')
        }
      };
      
      const typographyTokens: Record<string, any> = {
        font: {
          heading: headingFont,
          body: bodyFont
        },
        scale: {
          ...(fontSizePreset === 'small' ? { md: 1.1, sm: 0.9 } :
              fontSizePreset === 'medium' ? { md: 1.333, sm: 1.111 } :
              fontSizePreset === 'large' ? { md: 1.5, sm: 1.25 } :
              { md: 1.777, sm: 1.5 })
        }
      };
      
      const spacingTokens: Record<string, any> = {
        density: spacingDensity
      };
      
      const shapeTokens: Record<string, any> = {
        button_radius: buttonRadius === 'none' ? '0' :
                       buttonRadius === 'small' ? '0.375rem' :
                       buttonRadius === 'medium' ? '0.75rem' :
                       buttonRadius === 'large' ? '1.5rem' : '9999px',
        widget_border_width: widgetBorderWidth === 'none' ? '0' :
                            widgetBorderWidth === 'thin' ? '1px' : '2px',
        shadow_level: shadowLevel
      };
      
      const themeData = {
        name: `${themeName.trim()} Copy`,
        color_tokens: colorTokens,
        typography_tokens: typographyTokens,
        spacing_tokens: spacingTokens,
        shape_tokens: shapeTokens,
        page_background: pageBackground
      };
      
      await createMutation.mutateAsync(themeData);
      setStatusMessage('Theme saved as new');
      setSaveStatus('success');
      setHasChanges(false);
      await queryClient.invalidateQueries({ queryKey: queryKeys.themes() });
      await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
      onSave?.();
    } catch (error) {
      console.error('Failed to save theme', error);
      setSaveStatus('error');
      setStatusMessage(error instanceof Error ? error.message : 'Failed to save theme');
    }
  };
  
  const handleReset = () => {
    if (theme) {
      // Reload theme data
      const colorTokens = safeParse(theme.color_tokens);
      const typographyTokens = safeParse(theme.typography_tokens);
      const spacingTokens = safeParse(theme.spacing_tokens);
      const shapeTokens = safeParse(theme.shape_tokens);
      
      let updatedTokens = { ...tokens };
      
      if (colorTokens) {
        Object.entries(colorTokens).forEach(([key, value]) => {
          if (typeof value === 'object' && value !== null) {
            Object.entries(value).forEach(([subKey, subValue]) => {
              if (typeof subValue === 'string') {
                const path = `semantic.${key}.${subKey}`;
                updatedTokens = applyTokenUpdate(updatedTokens, path, subValue);
              }
            });
          }
        });
      }
      
      if (typographyTokens && typeof typographyTokens === 'object') {
        const fonts = (typographyTokens as any).font;
        if (fonts) {
          if (fonts.heading && typeof fonts.heading === 'string') {
            updatedTokens = applyTokenUpdate(updatedTokens, 'core.typography.font.heading', fonts.heading);
          }
          if (fonts.body && typeof fonts.body === 'string') {
            updatedTokens = applyTokenUpdate(updatedTokens, 'core.typography.font.body', fonts.body);
          }
        }
      }
      
      if (spacingTokens && typeof spacingTokens === 'object') {
        const density = (spacingTokens as any).density;
        if (density && ['compact', 'cozy', 'comfortable'].includes(density)) {
          setSpacingDensity(density as 'compact' | 'cozy' | 'comfortable');
        }
      }
      
      if (shapeTokens && typeof shapeTokens === 'object') {
        const buttonRadius = (shapeTokens as any).button_radius;
        if (buttonRadius) {
          if (buttonRadius === '0' || buttonRadius === '0px') setButtonRadius('none');
          else if (buttonRadius === '0.375rem' || buttonRadius === '6px') setButtonRadius('small');
          else if (buttonRadius === '0.75rem' || buttonRadius === '12px') setButtonRadius('medium');
          else if (buttonRadius === '1.5rem' || buttonRadius === '24px') setButtonRadius('large');
          else if (buttonRadius === '9999px') setButtonRadius('pill');
        }
        
        const borderWidth = (shapeTokens as any).widget_border_width;
        if (borderWidth) {
          if (borderWidth === '0' || borderWidth === '0px') setWidgetBorderWidth('none');
          else if (borderWidth === '1px') setWidgetBorderWidth('thin');
          else if (borderWidth === '2px' || borderWidth === '4px') setWidgetBorderWidth('thick');
        }
        
        const shadow = (shapeTokens as any).shadow_level;
        if (shadow && ['none', 'subtle', 'pronounced'].includes(shadow)) {
          setShadowLevel(shadow as 'none' | 'subtle' | 'pronounced');
        }
      }
      
      setTokens(updatedTokens);
      setHasChanges(false);
      setStatusMessage('Changes reset');
      setTimeout(() => setStatusMessage(null), 2000);
    }
  };
  
  const fontOptions = ['Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins', 'Raleway', 'Source Sans Pro'];
  
  const accordionItems: TokenAccordionItem[] = [
    {
      id: 'colors',
      trigger: (
        <div className={styles.accordionTrigger}>
          <LuPalette className={styles.accordionIcon} aria-hidden="true" />
          <span>Colors</span>
        </div>
      ),
      description: 'Set your color palette',
      content: (
        <div className={styles.colorSection}>
          <ColorPaletteEditor
            activeColor={activeColor}
            groups={[
              {
                id: 'accent',
                label: 'Accent Colors',
                description: 'Primary colors for buttons and highlights',
                colors: [
                  {
                    id: 'primary-accent',
                    label: 'Primary Accent',
                    token: 'semantic.accent.primary',
                    value: primaryColor,
                    onChange: (value) => handleColorChange('semantic.accent.primary', value)
                  },
                  {
                    id: 'secondary-accent',
                    label: 'Secondary Accent',
                    token: 'semantic.accent.secondary',
                    value: secondaryColor,
                    onChange: (value) => handleColorChange('semantic.accent.secondary', value)
                  }
                ]
              },
              {
                id: 'text',
                label: 'Text Colors',
                description: 'Colors for headings and body text',
                colors: [
                  {
                    id: 'primary-text',
                    label: 'Primary Text',
                    token: 'semantic.text.primary',
                    value: textPrimary,
                    onChange: (value) => handleColorChange('semantic.text.primary', value)
                  },
                  {
                    id: 'secondary-text',
                    label: 'Secondary Text',
                    token: 'semantic.text.secondary',
                    value: textSecondary,
                    onChange: (value) => handleColorChange('semantic.text.secondary', value)
                  }
                ]
              },
              {
                id: 'background',
                label: 'Background',
                description: 'Page and surface colors',
                colors: [
                  {
                    id: 'page-background',
                    label: 'Page Background',
                    token: 'semantic.surface.canvas',
                    value: pageBackground,
                    onChange: (value) => handleColorChange('semantic.surface.canvas', value)
                  }
                ]
              }
            ]}
          />
        </div>
      )
    },
    {
      id: 'typography',
      trigger: (
        <div className={styles.accordionTrigger}>
          <LuType className={styles.accordionIcon} aria-hidden="true" />
          <span>Typography</span>
        </div>
      ),
      description: 'Choose fonts and sizes',
      content: (
        <div className={styles.typographySection}>
          <label className={styles.control}>
            <span>Heading Font</span>
            <select
              value={headingFont}
              onChange={(e) => handleFontChange('core.typography.font.heading', e.target.value)}
            >
              {fontOptions.map((font) => (
                <option key={font} value={font}>
                  {font}
                </option>
              ))}
            </select>
          </label>
          <label className={styles.control}>
            <span>Body Font</span>
            <select
              value={bodyFont}
              onChange={(e) => handleFontChange('core.typography.font.body', e.target.value)}
            >
              {fontOptions.map((font) => (
                <option key={font} value={font}>
                  {font}
                </option>
              ))}
            </select>
          </label>
          <div className={styles.presetGroup}>
            <span className={styles.presetLabel}>Font Size Preset</span>
            <div className={styles.presetButtons}>
              {(['small', 'medium', 'large', 'xlarge'] as const).map((preset) => (
                <button
                  key={preset}
                  type="button"
                  className={`${styles.presetButton} ${fontSizePreset === preset ? styles.presetButtonActive : ''}`}
                  onClick={() => handlePresetChange(preset)}
                >
                  {preset.charAt(0).toUpperCase() + preset.slice(1)}
                </button>
              ))}
            </div>
          </div>
        </div>
      )
    },
    {
      id: 'spacing',
      trigger: (
        <div className={styles.accordionTrigger}>
          <LuLayoutGrid className={styles.accordionIcon} aria-hidden="true" />
          <span>Spacing</span>
        </div>
      ),
      description: 'Adjust density',
      content: (
        <div className={styles.spacingSection}>
          <div className={styles.densityGroup} role="radiogroup" aria-label="Spacing density">
            {(['compact', 'cozy', 'comfortable'] as const).map((density) => (
              <button
                key={density}
                type="button"
                role="radio"
                aria-checked={spacingDensity === density}
                className={`${styles.densityChip} ${spacingDensity === density ? styles.densityChipActive : ''}`}
                onClick={() => handleDensityChange(density)}
              >
                <div>
                  <span>{density.charAt(0).toUpperCase() + density.slice(1)}</span>
                  <p>
                    {density === 'compact' && 'High information density'}
                    {density === 'cozy' && 'Balanced spacing'}
                    {density === 'comfortable' && 'Generous spacing'}
                  </p>
                </div>
              </button>
            ))}
          </div>
        </div>
      )
    },
    {
      id: 'shape',
      trigger: (
        <div className={styles.accordionTrigger}>
          <LuShapes className={styles.accordionIcon} aria-hidden="true" />
          <span>Shape & Effects</span>
        </div>
      ),
      description: 'Border radius, borders, and shadows',
      content: (
        <div className={styles.shapeSection}>
          <div className={styles.controlGroup}>
            <span className={styles.controlLabel}>Button Radius</span>
            <div className={styles.optionButtons}>
              {(['none', 'small', 'medium', 'large', 'pill'] as const).map((radius) => (
                <button
                  key={radius}
                  type="button"
                  className={`${styles.optionButton} ${buttonRadius === radius ? styles.optionButtonActive : ''}`}
                  onClick={() => handleShapeChange('buttonRadius', radius)}
                >
                  {radius.charAt(0).toUpperCase() + radius.slice(1)}
                </button>
              ))}
            </div>
          </div>
          <div className={styles.controlGroup}>
            <span className={styles.controlLabel}>Widget Border Width</span>
            <div className={styles.optionButtons}>
              {(['none', 'thin', 'thick'] as const).map((width) => (
                <button
                  key={width}
                  type="button"
                  className={`${styles.optionButton} ${widgetBorderWidth === width ? styles.optionButtonActive : ''}`}
                  onClick={() => handleShapeChange('widgetBorderWidth', width)}
                >
                  {width.charAt(0).toUpperCase() + width.slice(1)}
                </button>
              ))}
            </div>
          </div>
          <div className={styles.controlGroup}>
            <span className={styles.controlLabel}>Shadow Level</span>
            <div className={styles.optionButtons}>
              {(['none', 'subtle', 'pronounced'] as const).map((level) => (
                <button
                  key={level}
                  type="button"
                  className={`${styles.optionButton} ${shadowLevel === level ? styles.optionButtonActive : ''}`}
                  onClick={() => handleShapeChange('shadowLevel', level)}
                >
                  {level.charAt(0).toUpperCase() + level.slice(1)}
                </button>
              ))}
            </div>
          </div>
        </div>
      )
    }
  ];
  
  return (
    <section
      className={styles.wrapper}
      aria-label="Theme editor"
      style={{
        '--active-tab-color': activeColor.text,
        '--active-tab-bg': activeColor.primary,
        '--active-tab-light': activeColor.light,
        '--active-tab-border': activeColor.border
      } as React.CSSProperties}
    >
      <header className={styles.header}>
        <div className={styles.headerContent}>
          <div>
            <h3>{theme?.id ? 'Edit Theme' : 'New Theme'}</h3>
            <p>Customize colors, typography, spacing, and effects.</p>
          </div>
          <button
            type="button"
            className={styles.closeButton}
            onClick={() => setThemeInspectorVisible(false)}
            aria-label="Close theme editor"
          >
            <LuX aria-hidden="true" />
          </button>
        </div>
      </header>
      
      <div className={styles.nameSection}>
        <label className={styles.nameLabel}>
          <span>Theme Name</span>
          <input
            type="text"
            value={themeName}
            onChange={(e) => {
              setThemeName(e.target.value);
              setHasChanges(true);
            }}
            placeholder="My Custom Theme"
            className={styles.nameInput}
          />
        </label>
      </div>
      
      <TokenAccordion items={accordionItems} type="multiple" defaultValue="colors" />
      
      <div className={styles.actions}>
        <div className={styles.actionButtons}>
          <button
            type="button"
            className={styles.saveButton}
            onClick={handleSave}
            disabled={saveStatus === 'saving' || !themeName.trim() || (!!theme?.id && !hasChanges)}
          >
            <LuSave aria-hidden="true" />
            {saveStatus === 'saving' ? 'Saving...' : theme?.id ? 'Update Theme' : 'Save Theme'}
          </button>
          {theme?.id && (
            <button
              type="button"
              className={styles.saveAsNewButton}
              onClick={handleSaveAsNew}
              disabled={saveStatus === 'saving' || !themeName.trim()}
            >
              <LuCopy aria-hidden="true" />
              Save as New
            </button>
          )}
          {hasChanges && (
            <button
              type="button"
              className={styles.resetButton}
              onClick={handleReset}
              disabled={saveStatus === 'saving'}
            >
              Reset
            </button>
          )}
        </div>
        {statusMessage && (
          <div className={`${styles.statusMessage} ${styles[`statusMessage_${saveStatus}`]}`}>
            {saveStatus === 'success' && <LuCheck aria-hidden="true" />}
            {saveStatus === 'error' && <LuCircleX aria-hidden="true" />}
            <span>{statusMessage}</span>
          </div>
        )}
      </div>
    </section>
  );
}

