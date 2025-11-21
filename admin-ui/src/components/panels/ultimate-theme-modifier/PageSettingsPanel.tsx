import { useMemo, useState, useEffect } from 'react';
import { BackgroundColorSwatch } from '../../controls/BackgroundColorSwatch';
import { FontSelect } from './FontSelect';
import { SpecialTextSelect } from './SpecialTextSelect';
import { SliderInput } from './SliderInput';
import { usePageSnapshot, usePageAppearanceMutation } from '../../../api/page';
import { useQueryClient } from '@tanstack/react-query';
import { queryKeys } from '../../../api/utils';
import type { TokenBundle } from '../../../design-system/tokens';
import styles from './page-settings-panel.module.css';

interface PageSettingsPanelProps {
  tokens: TokenBundle;
  tokenValues: Map<string, unknown>;
  onTokenChange: (path: string, value: unknown, oldValue: unknown) => void;
  pageBackground?: string | null;
  pageHeadingText?: string | null;
  pageBodyText?: string | null;
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
  
  if (typeof resolved === 'string') {
    if (/^#([0-9a-fA-F]{3}){1,2}$/.test(resolved)) {
      return resolved;
    }
    if (resolved.includes('gradient')) {
      return resolved;
    }
    if (resolved.startsWith('http://') || resolved.startsWith('https://') || resolved.startsWith('/') || resolved.startsWith('data:')) {
      return resolved;
    }
    if (resolved.startsWith('rgba(')) {
      return resolved;
    }
  }
  
  return '#2563eb';
}

function extractNumericValue(tokens: TokenBundle, path: string, defaultValue: number = 0): number {
  const resolved = resolveToken(tokens, path);
  if (typeof resolved === 'number') {
    return resolved;
  }
  if (typeof resolved === 'string') {
    const num = parseFloat(resolved);
    if (!isNaN(num)) {
      return num;
    }
  }
  return defaultValue;
}


// Helper to determine background type from value
function getBackgroundType(value: string): 'solid' | 'gradient' {
  if (!value || typeof value !== 'string') return 'solid';
  if (value.includes('gradient') || value.includes('linear-gradient') || value.includes('radial-gradient')) {
    return 'gradient';
  }
  return 'solid';
}
export function PageSettingsPanel({ tokens, tokenValues, onTokenChange, pageBackground: pageBackgroundProp, pageHeadingText: pageHeadingTextProp, pageBodyText: pageBodyTextProp }: PageSettingsPanelProps): JSX.Element {
  const { data: snapshot } = usePageSnapshot();
  const pageAppearanceMutation = usePageAppearanceMutation();
  const queryClient = useQueryClient();
  const page = snapshot?.page;
  
  const [specialText, setSpecialText] = useState('None');
  
  // Load current page_name_effect from snapshot
  useEffect(() => {
    const effect = page?.page_name_effect;
    // Map all ready-to-use effects (5 impressive new creations)
    const effectMap: Record<string, string> = {
      'aurora-borealis': 'Aurora Borealis',
      'holographic': 'Holographic',
      'liquid-neon': 'Liquid Neon',
      'chrome-metallic': 'Chrome Metallic',
      'energy-pulse': 'Energy Pulse'
    };
    
    if (effect && typeof effect === 'string' && effect.trim() !== '') {
      const mappedEffect = effectMap[effect];
      setSpecialText(mappedEffect || 'None');
    } else {
      setSpecialText('None');
    }
  }, [page?.page_name_effect]);
  
  // Extract values - prioritize tokenValues (unsaved changes), then props from active theme, then tokens
  const pageHeadingText = useMemo(() => {
    // Priority 1: Check tokenValues (changed but not yet saved)
    const colorFromValues = tokenValues.get('semantic.text.primary') as string | undefined;
    if (colorFromValues && typeof colorFromValues === 'string' && colorFromValues.trim() !== '') {
      return colorFromValues;
    }
    // Priority 2: Check props from active theme
    if (pageHeadingTextProp && typeof pageHeadingTextProp === 'string' && pageHeadingTextProp.trim() !== '') {
      return pageHeadingTextProp;
    }
    // Priority 3: Extract from tokens
    return extractColorValue(tokens, 'semantic.text.primary');
  }, [pageHeadingTextProp, tokens, tokenValues]);
  
  const pageBodyText = useMemo(() => {
    // Priority 1: Check tokenValues (changed but not yet saved)
    const colorFromValues = tokenValues.get('semantic.text.secondary') as string | undefined;
    if (colorFromValues && typeof colorFromValues === 'string' && colorFromValues.trim() !== '') {
      return colorFromValues;
    }
    // Priority 2: Check props from active theme
    if (pageBodyTextProp && typeof pageBodyTextProp === 'string' && pageBodyTextProp.trim() !== '') {
      return pageBodyTextProp;
    }
    // Priority 3: Extract from tokens
    return extractColorValue(tokens, 'semantic.text.secondary');
  }, [pageBodyTextProp, tokens, tokenValues]);
  
  // Use page_background from snapshot if available (from active theme), otherwise fall back to tokens
  const pageBackground = useMemo(() => {
    // Priority 1: Check tokenValues (changed but not yet saved)
    const bgFromValues = tokenValues.get('semantic.surface.canvas') as string | undefined;
    if (bgFromValues && typeof bgFromValues === 'string' && bgFromValues.trim() !== '') {
      return bgFromValues;
    }
    // Priority 2: Check props from active theme
    let result: string;
    if (pageBackgroundProp && typeof pageBackgroundProp === 'string' && pageBackgroundProp.trim() !== '') {
      result = pageBackgroundProp;
    } else {
      // Priority 3: Extract from tokens
      result = extractColorValue(tokens, 'semantic.surface.canvas');
    }
    
    // Ensure we always return a valid string (never null/undefined)
    if (!result || typeof result !== 'string') {
      result = '#FFFFFF';
    }
    
    return result;
  }, [pageBackgroundProp, tokens, tokenValues]);
  
  const headingFont = useMemo(() => {
    const font = tokens.core?.typography?.font?.heading;
    return typeof font === 'string' ? font.split(',')[0].trim() : 'Inter';
  }, [tokens]);

  const bodyFont = useMemo(() => {
    const font = tokens.core?.typography?.font?.body;
    return typeof font === 'string' ? font.split(',')[0].trim() : 'Inter';
  }, [tokens]);

  const headingSize = useMemo(() => {
    // Priority 1: Check tokenValues (changed but not yet saved)
    const sizeFromValues = tokenValues.get('core.typography.size.heading') as number | undefined;
    if (typeof sizeFromValues === 'number' && !isNaN(sizeFromValues)) {
      return sizeFromValues;
    }
    // Priority 2: Extract from tokens
    return extractNumericValue(tokens, 'core.typography.size.heading', 24);
  }, [tokens, tokenValues]);
  
  const bodySize = useMemo(() => {
    // Priority 1: Check tokenValues (changed but not yet saved)
    const sizeFromValues = tokenValues.get('core.typography.size.body') as number | undefined;
    if (typeof sizeFromValues === 'number' && !isNaN(sizeFromValues)) {
      return sizeFromValues;
    }
    // Priority 2: Extract from tokens
    return extractNumericValue(tokens, 'core.typography.size.body', 16);
  }, [tokens, tokenValues]);
  
  // Page spacing - extract from tokenValues or default to 1.0 (100%)
  // Note: page_spacing_multiplier is stored in tokenValues by UltimateThemeModifier
  // from spacing_tokens.page_multiplier, so we check tokenValues first
  const pageSpacing = useMemo(() => {
    const spacing = tokenValues.get('page_spacing_multiplier') as number | undefined;
    if (typeof spacing === 'number' && !isNaN(spacing)) {
      return spacing;
    }
    // Default to 1.0 (100%) if not found
    return 1.0;
  }, [tokenValues]);

  const backgroundType = useMemo(() => {
    const bg = pageBackground || '';
    
    // Check for gradient first
    if (bg.includes('gradient') || bg.includes('linear-gradient') || bg.includes('radial-gradient')) {
      return 'gradient';
    }
    // Default to solid
    return 'solid';
  }, [pageBackground]);

  const handleColorChange = (path: string, value: string) => {
    const oldValue = resolveToken(tokens, path);
    onTokenChange(path, value, oldValue);
  };

  const handleFontChange = (path: string, value: string) => {
    const oldValue = resolveToken(tokens, path);
    onTokenChange(path, value, oldValue);
  };

  const handleSizeChange = (path: string, value: number) => {
    const oldValue = resolveToken(tokens, path);
    onTokenChange(path, value, oldValue);
  };

  const handlePageSpacingChange = (value: number) => {
    const oldValue = tokenValues.get('page_spacing_multiplier') ?? 1.0;
    onTokenChange('page_spacing_multiplier', value, oldValue);
  };

  // Determine which effects use gradients (which completely override color)
  const gradientEffects = useMemo(() => ['Aurora Borealis', 'Holographic', 'Chrome Metallic'], []);
  const solidColorEffects = useMemo(() => ['Liquid Neon', 'Energy Pulse'], []);
  
  const isGradientEffect = useMemo(() => 
    specialText !== 'None' && gradientEffects.includes(specialText), 
    [specialText, gradientEffects]
  );
  
  const isSolidColorEffect = useMemo(() => 
    specialText !== 'None' && solidColorEffects.includes(specialText), 
    [specialText, solidColorEffects]
  );

  const handleSpecialTextChange = async (value: string) => {
    const previousValue = specialText;
    setSpecialText(value);
    
    try {
      // Map display names back to effect values (5 impressive new creations)
      const effectValueMap: Record<string, string> = {
        'Aurora Borealis': 'aurora-borealis',
        'Holographic': 'holographic',
        'Liquid Neon': 'liquid-neon',
        'Chrome Metallic': 'chrome-metallic',
        'Energy Pulse': 'energy-pulse'
      };
      
      if (value === 'None') {
        // Clear the effect (set to empty string which becomes null)
        await pageAppearanceMutation.mutateAsync({
          page_name_effect: ''
        });
      } else {
        const effectValue = effectValueMap[value];
        if (effectValue) {
          // Set page_name_effect to override all other page-title settings
          await pageAppearanceMutation.mutateAsync({
            page_name_effect: effectValue
          });
        } else {
          // Unknown effect, clear it
          await pageAppearanceMutation.mutateAsync({
            page_name_effect: ''
          });
        }
      }
      await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
    } catch (error) {
      console.error('Failed to update special text effect', error);
      // Revert on error
      setSpecialText(previousValue);
    }
  };

  return (
    <div className={styles.panel}>
      <div className={styles.content}>
        <div className={styles.controls}>
          {/* Background color */}
          <div className={styles.controlRow}>
            <label className={styles.controlLabel}>Page background color</label>
            <BackgroundColorSwatch
              value={pageBackground}
              backgroundType={backgroundType}
              onChange={(value) => handleColorChange('semantic.surface.canvas', value)}
              onTypeChange={(type) => {
                if (type === 'solid') {
                  handleColorChange('semantic.surface.canvas', '#FFFFFF');
                } else if (type === 'gradient') {
                  if (!pageBackground.includes('gradient')) {
                    handleColorChange('semantic.surface.canvas', 'linear-gradient(140deg, #02040d 0%, #0a1331 45%, #1a2151 100%)');
                  }
                }
              }}
              label="Page background color"
            />
          </div>

          {/* Page title section */}
          <div className={styles.section}>
                <div className={styles.contentArea}>
                  {/* Special Text Effect Selector */}
                  <div className={styles.controlRow}>
                    <label className={styles.controlLabel}>Special Effect</label>
                    <SpecialTextSelect
                      value={specialText}
                      options={['None', 'Aurora Borealis', 'Holographic', 'Liquid Neon', 'Chrome Metallic', 'Energy Pulse']}
                      onChange={handleSpecialTextChange}
                      disabled={pageAppearanceMutation.isPending}
                    />
                  </div>

                  {/* Effect-specific messaging */}
                  {isGradientEffect && (
                    <div style={{ 
                      padding: '0.75rem', 
                      backgroundColor: 'rgba(59, 130, 246, 0.1)', 
                      border: '1px solid rgba(59, 130, 246, 0.3)', 
                      borderRadius: '0.375rem', 
                      marginBottom: '0.875rem',
                      fontSize: '0.75rem',
                      color: 'var(--color-text-secondary, #6b7280)'
                    }}>
                      <strong>{specialText}</strong> uses its own gradient colors. Font and size settings below will apply.
                    </div>
                  )}

                  {isSolidColorEffect && (
                    <div style={{ 
                      padding: '0.75rem', 
                      backgroundColor: 'rgba(168, 85, 247, 0.1)', 
                      border: '1px solid rgba(168, 85, 247, 0.3)', 
                      borderRadius: '0.375rem', 
                      marginBottom: '0.875rem',
                      fontSize: '0.75rem',
                      color: 'var(--color-text-secondary, #6b7280)'
                    }}>
                      <strong>{specialText}</strong> uses a fixed color for the effect. Font and size settings below will apply.
                    </div>
                  )}
                  
                  {/* Page title color - disabled for gradient effects */}
                  <div className={styles.controlRow}>
                    <label className={styles.controlLabel}>
                      Page title color
                      {isGradientEffect && (
                        <span style={{ 
                          fontSize: '0.7rem', 
                          fontWeight: 'normal', 
                          color: 'var(--color-text-secondary, #6b7280)',
                          marginLeft: '0.5rem'
                        }}>
                          (disabled - effect uses gradient)
                        </span>
                      )}
                      {isSolidColorEffect && (
                        <span style={{ 
                          fontSize: '0.7rem', 
                          fontWeight: 'normal', 
                          color: 'var(--color-text-secondary, #6b7280)',
                          marginLeft: '0.5rem'
                        }}>
                          (effect overrides this)
                        </span>
                      )}
                    </label>
                    <BackgroundColorSwatch
                      value={pageHeadingText}
                      backgroundType={getBackgroundType(pageHeadingText)}
                      onChange={(value) => handleColorChange('semantic.text.primary', value)}
                      onTypeChange={(type) => {
                        if (type === 'solid') {
                          handleColorChange('semantic.text.primary', '#111827');
                        } else if (type === 'gradient') {
                          if (!pageHeadingText.includes('gradient')) {
                            handleColorChange('semantic.text.primary', 'linear-gradient(135deg, #111827 0%, #4b5563 100%)');
                          }
                        }
                      }}
                      onImageChange={(url) => {
                        if (url) {
                          handleColorChange('semantic.text.primary', url);
                        }
                      }}
                      label="Page title color"
                    />
                  </div>

                  {/* Page title font - always enabled */}
                  <div className={styles.controlRow}>
                    <label className={styles.controlLabel}>Page title font</label>
                    <FontSelect
                      value={headingFont}
                      onChange={(value) => handleFontChange('core.typography.font.heading', value)}
                      disabled={false}
                    />
                  </div>

                  {/* Page title size - always enabled */}
                  <div className={styles.controlRow}>
                    <label className={styles.controlLabel}>Page title size</label>
                    <SliderInput
                      value={headingSize}
                      min={14}
                      max={48}
                      step={1}
                      onChange={(value) => handleSizeChange('core.typography.size.heading', value)}
                      unit="px"
                      disabled={false}
                    />
                  </div>
                </div>
          </div>

          {/* Body section */}
          <div className={styles.section}>
            <h4 className={styles.sectionTitle}>Page body</h4>
            
            {/* Body color */}
            <div className={styles.controlRow}>
              <label className={styles.controlLabel}>Page body color</label>
              <BackgroundColorSwatch
                value={pageBodyText}
                backgroundType={getBackgroundType(pageBodyText)}
                onChange={(value) => handleColorChange('semantic.text.secondary', value)}
                onTypeChange={(type) => {
                  if (type === 'solid') {
                    handleColorChange('semantic.text.secondary', '#4b5563');
                  } else if (type === 'gradient') {
                    if (!pageBodyText.includes('gradient')) {
                      handleColorChange('semantic.text.secondary', 'linear-gradient(135deg, #4b5563 0%, #6b7280 100%)');
                    }
                  }
                }}
                onImageChange={(url) => {
                  if (url) {
                    handleColorChange('semantic.text.secondary', url);
                  }
                }}
                label="Page body color"
              />
            </div>

            {/* Body font */}
            <div className={styles.controlRow}>
              <label className={styles.controlLabel}>Page body font</label>
              <FontSelect
                value={bodyFont}
                onChange={(value) => handleFontChange('core.typography.font.body', value)}
              />
            </div>

            {/* Body size */}
            <div className={styles.controlRow}>
              <label className={styles.controlLabel}>Text size</label>
              <SliderInput
                value={bodySize}
                min={10}
                max={24}
                step={1}
                onChange={(value) => handleSizeChange('core.typography.size.body', value)}
                unit="px"
              />
            </div>
          </div>

          {/* Page Spacing */}
          <div className={styles.section}>
            <div className={styles.controlRow}>
              <label className={styles.controlLabel}>Page spacing</label>
              <SliderInput
                value={pageSpacing * 100}
                min={50}
                max={200}
                step={5}
                onChange={(value) => handlePageSpacingChange(value / 100)}
                unit="%"
              />
            </div>
          </div>
        </div>
      </div>
    </div>
  );
}

