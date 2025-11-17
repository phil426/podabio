import { useState, useMemo } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { GridFour, Minus } from '@phosphor-icons/react';
import { TokenControl } from './TokenControl';
import { SectionHeader } from './SectionHeader';
import { SliderInput } from './SliderInput';
import type { TokenBundle } from '../../../design-system/tokens';
import styles from './spacing-section.module.css';

interface SpacingSectionProps {
  tokens: TokenBundle;
  onTokenChange: (path: string, value: unknown, oldValue: unknown) => void;
  searchQuery?: string;
  tokenValues?: Map<string, unknown>;
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

export function SpacingSection({ tokens, onTokenChange, searchQuery = '', tokenValues = new Map() }: SpacingSectionProps): JSX.Element {
  const [expandedGroups, setExpandedGroups] = useState<Set<string>>(new Set([
    'density', 'base_scale'
  ]));

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

  const handleTokenChange = (path: string, value: unknown) => {
    const oldValue = resolveToken(tokens, path);
    onTokenChange(path, value, oldValue);
  };

  // Extract spacing values from tokenValues
  const density = useMemo(() => {
    return (tokenValues.get('spacing_tokens.density') as string) || 'comfortable';
  }, [tokenValues]);

  const baseScale2xs = useMemo(() => {
    return (tokenValues.get('spacing_tokens.base_scale.2xs') as number) ?? 0.25;
  }, [tokenValues]);
  const baseScaleXs = useMemo(() => {
    return (tokenValues.get('spacing_tokens.base_scale.xs') as number) ?? 0.5;
  }, [tokenValues]);
  const baseScaleSm = useMemo(() => {
    return (tokenValues.get('spacing_tokens.base_scale.sm') as number) ?? 0.85;
  }, [tokenValues]);
  const baseScaleMd = useMemo(() => {
    return (tokenValues.get('spacing_tokens.base_scale.md') as number) ?? 1.1;
  }, [tokenValues]);
  const baseScaleLg = useMemo(() => {
    return (tokenValues.get('spacing_tokens.base_scale.lg') as number) ?? 1.6;
  }, [tokenValues]);
  const baseScaleXl = useMemo(() => {
    return (tokenValues.get('spacing_tokens.base_scale.xl') as number) ?? 2.2;
  }, [tokenValues]);
  const baseScale2xl = useMemo(() => {
    return (tokenValues.get('spacing_tokens.base_scale.2xl') as number) ?? 3.2;
  }, [tokenValues]);

  // Filter by search query
  const matchesSearch = (label: string, path: string): boolean => {
    if (!searchQuery) return true;
    const query = searchQuery.toLowerCase();
    return label.toLowerCase().includes(query) || path.toLowerCase().includes(query);
  };

  return (
    <div className={styles.section}>
      {/* Density Group */}
      {matchesSearch('Density', 'density') && (
        <div className={styles.group}>
          <SectionHeader
            icon={<GridFour weight="bold" />}
            title="Density"
            isExpanded={expandedGroups.has('density')}
            onToggle={() => toggleGroup('density')}
          />
          <AnimatePresence>
            {expandedGroups.has('density') && (
              <motion.div
                initial={{ height: 0, opacity: 0 }}
                animate={{ height: 'auto', opacity: 1 }}
                exit={{ height: 0, opacity: 0 }}
                transition={{ duration: 0.2, ease: 'easeInOut' }}
                className={styles.groupContent}
              >
              <TokenControl
                label="Density"
                tokenPath="spacing_tokens.density"
                value={density}
                onValueChange={(value) => handleTokenChange('spacing_tokens.density', value)}
              >
                <div className={styles.radioGroup}>
                  <label className={styles.radioLabel}>
                    <input
                      type="radio"
                      name="density"
                      value="compact"
                      checked={density === 'compact'}
                      onChange={(e) => handleTokenChange('spacing_tokens.density', e.target.value)}
                      className={styles.radio}
                    />
                    <span>Compact</span>
                  </label>
                  <label className={styles.radioLabel}>
                    <input
                      type="radio"
                      name="density"
                      value="cozy"
                      checked={density === 'cozy'}
                      onChange={(e) => handleTokenChange('spacing_tokens.density', e.target.value)}
                      className={styles.radio}
                    />
                    <span>Cozy</span>
                  </label>
                  <label className={styles.radioLabel}>
                    <input
                      type="radio"
                      name="density"
                      value="comfortable"
                      checked={density === 'comfortable'}
                      onChange={(e) => handleTokenChange('spacing_tokens.density', e.target.value)}
                      className={styles.radio}
                    />
                    <span>Comfortable</span>
                  </label>
                </div>
                <div className={styles.densityPreview}>
                  <div className={styles.densityBox} data-density="compact">
                    <div className={styles.densityItem} />
                    <div className={styles.densityItem} />
                    <div className={styles.densityItem} />
                  </div>
                  <div className={styles.densityBox} data-density="cozy">
                    <div className={styles.densityItem} />
                    <div className={styles.densityItem} />
                    <div className={styles.densityItem} />
                  </div>
                  <div className={styles.densityBox} data-density="comfortable">
                    <div className={styles.densityItem} />
                    <div className={styles.densityItem} />
                    <div className={styles.densityItem} />
                  </div>
                </div>
              </TokenControl>
              </motion.div>
            )}
          </AnimatePresence>
        </div>
      )}

      {/* Base Scale Group */}
      {matchesSearch('Base Scale', 'base_scale') && (
        <div className={styles.group}>
          <SectionHeader
            icon={<Minus weight="bold" />}
            title="Base Scale"
            isExpanded={expandedGroups.has('base_scale')}
            onToggle={() => toggleGroup('base_scale')}
          />
          <AnimatePresence>
            {expandedGroups.has('base_scale') && (
              <motion.div
                initial={{ height: 0, opacity: 0 }}
                animate={{ height: 'auto', opacity: 1 }}
                exit={{ height: 0, opacity: 0 }}
                transition={{ duration: 0.2, ease: 'easeInOut' }}
                className={styles.groupContent}
              >
              <TokenControl
                label="2XS"
                tokenPath="spacing_tokens.base_scale.2xs"
                value={baseScale2xs}
                onValueChange={(value) => handleTokenChange('spacing_tokens.base_scale.2xs', value)}
              >
                <SliderInput
                  value={baseScale2xs}
                  min={0}
                  max={5.0}
                  step={0.01}
                  onChange={(value) => handleTokenChange('spacing_tokens.base_scale.2xs', value)}
                />
                <div className={styles.scalePreview} style={{ width: `${baseScale2xs * 40}px`, height: `${baseScale2xs * 40}px` }} />
              </TokenControl>

              <TokenControl
                label="XS"
                tokenPath="spacing_tokens.base_scale.xs"
                value={baseScaleXs}
                onValueChange={(value) => handleTokenChange('spacing_tokens.base_scale.xs', value)}
              >
                <SliderInput
                  value={baseScaleXs}
                  min={0}
                  max={5.0}
                  step={0.01}
                  onChange={(value) => handleTokenChange('spacing_tokens.base_scale.xs', value)}
                />
                <div className={styles.scalePreview} style={{ width: `${baseScaleXs * 40}px`, height: `${baseScaleXs * 40}px` }} />
              </TokenControl>

              <TokenControl
                label="SM"
                tokenPath="spacing_tokens.base_scale.sm"
                value={baseScaleSm}
                onValueChange={(value) => handleTokenChange('spacing_tokens.base_scale.sm', value)}
              >
                <SliderInput
                  value={baseScaleSm}
                  min={0}
                  max={5.0}
                  step={0.01}
                  onChange={(value) => handleTokenChange('spacing_tokens.base_scale.sm', value)}
                />
                <div className={styles.scalePreview} style={{ width: `${baseScaleSm * 40}px`, height: `${baseScaleSm * 40}px` }} />
              </TokenControl>

              <TokenControl
                label="MD"
                tokenPath="spacing_tokens.base_scale.md"
                value={baseScaleMd}
                onValueChange={(value) => handleTokenChange('spacing_tokens.base_scale.md', value)}
              >
                <SliderInput
                  value={baseScaleMd}
                  min={0}
                  max={5.0}
                  step={0.01}
                  onChange={(value) => handleTokenChange('spacing_tokens.base_scale.md', value)}
                />
                <div className={styles.scalePreview} style={{ width: `${baseScaleMd * 40}px`, height: `${baseScaleMd * 40}px` }} />
              </TokenControl>

              <TokenControl
                label="LG"
                tokenPath="spacing_tokens.base_scale.lg"
                value={baseScaleLg}
                onValueChange={(value) => handleTokenChange('spacing_tokens.base_scale.lg', value)}
              >
                <SliderInput
                  value={baseScaleLg}
                  min={0}
                  max={5.0}
                  step={0.01}
                  onChange={(value) => handleTokenChange('spacing_tokens.base_scale.lg', value)}
                />
                <div className={styles.scalePreview} style={{ width: `${baseScaleLg * 40}px`, height: `${baseScaleLg * 40}px` }} />
              </TokenControl>

              <TokenControl
                label="XL"
                tokenPath="spacing_tokens.base_scale.xl"
                value={baseScaleXl}
                onValueChange={(value) => handleTokenChange('spacing_tokens.base_scale.xl', value)}
              >
                <SliderInput
                  value={baseScaleXl}
                  min={0}
                  max={5.0}
                  step={0.01}
                  onChange={(value) => handleTokenChange('spacing_tokens.base_scale.xl', value)}
                />
                <div className={styles.scalePreview} style={{ width: `${baseScaleXl * 40}px`, height: `${baseScaleXl * 40}px` }} />
              </TokenControl>

              <TokenControl
                label="2XL"
                tokenPath="spacing_tokens.base_scale.2xl"
                value={baseScale2xl}
                onValueChange={(value) => handleTokenChange('spacing_tokens.base_scale.2xl', value)}
              >
                <SliderInput
                  value={baseScale2xl}
                  min={0}
                  max={5.0}
                  step={0.01}
                  onChange={(value) => handleTokenChange('spacing_tokens.base_scale.2xl', value)}
                />
                <div className={styles.scalePreview} style={{ width: `${baseScale2xl * 40}px`, height: `${baseScale2xl * 40}px` }} />
              </TokenControl>
              </motion.div>
            )}
          </AnimatePresence>
        </div>
      )}
    </div>
  );
}

