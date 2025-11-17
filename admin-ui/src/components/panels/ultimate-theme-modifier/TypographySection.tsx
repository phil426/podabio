import { useState, useMemo } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { TextT, TextAlignLeft, Minus } from '@phosphor-icons/react';
import { TokenControl } from './TokenControl';
import { SectionHeader } from './SectionHeader';
import { SliderInput } from './SliderInput';
import { FontSelect } from './FontSelect';
import type { TokenBundle } from '../../../design-system/tokens';
import styles from './typography-section.module.css';

interface TypographySectionProps {
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

export function TypographySection({ tokens, onTokenChange, searchQuery = '', tokenValues = new Map() }: TypographySectionProps): JSX.Element {
  const [expandedGroups, setExpandedGroups] = useState<Set<string>>(new Set([
    'font', 'scale', 'line_height', 'weight'
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

  // Extract typography values
  const headingFont = useMemo(() => {
    const font = tokens.core?.typography?.font?.heading;
    return typeof font === 'string' ? font : 'Inter';
  }, [tokens]);

  const bodyFont = useMemo(() => {
    const font = tokens.core?.typography?.font?.body;
    return typeof font === 'string' ? font : 'Inter';
  }, [tokens]);

  const metatextFont = useMemo(() => {
    const font = tokens.core?.typography?.font?.metatext;
    return typeof font === 'string' ? font : bodyFont;
  }, [tokens, bodyFont]);

  const scaleXl = useMemo(() => {
    return (tokenValues.get('core.typography.scale.xl') as number) ?? (typeof tokens.core?.typography?.scale?.xl === 'number' ? tokens.core.typography.scale.xl : 2.55);
  }, [tokens, tokenValues]);

  const scaleLg = useMemo(() => {
    return (tokenValues.get('core.typography.scale.lg') as number) ?? (typeof tokens.core?.typography?.scale?.lg === 'number' ? tokens.core.typography.scale.lg : 1.9);
  }, [tokens, tokenValues]);

  const scaleMd = useMemo(() => {
    return (tokenValues.get('core.typography.scale.md') as number) ?? (typeof tokens.core?.typography?.scale?.md === 'number' ? tokens.core.typography.scale.md : 1.32);
  }, [tokens, tokenValues]);

  const scaleSm = useMemo(() => {
    return (tokenValues.get('core.typography.scale.sm') as number) ?? (typeof tokens.core?.typography?.scale?.sm === 'number' ? tokens.core.typography.scale.sm : 1.08);
  }, [tokens, tokenValues]);

  const scaleXs = useMemo(() => {
    return (tokenValues.get('core.typography.scale.xs') as number) ?? (typeof tokens.core?.typography?.scale?.xs === 'number' ? tokens.core.typography.scale.xs : 0.9);
  }, [tokens, tokenValues]);

  const lineHeightTight = useMemo(() => {
    return (tokenValues.get('core.typography.line_height.tight') as number) ?? (typeof tokens.core?.typography?.line_height?.tight === 'number' ? tokens.core.typography.line_height.tight : 1.2);
  }, [tokens, tokenValues]);

  const lineHeightNormal = useMemo(() => {
    return (tokenValues.get('core.typography.line_height.normal') as number) ?? (typeof tokens.core?.typography?.line_height?.normal === 'number' ? tokens.core.typography.line_height.normal : 1.55);
  }, [tokens, tokenValues]);

  const lineHeightRelaxed = useMemo(() => {
    return (tokenValues.get('core.typography.line_height.relaxed') as number) ?? (typeof tokens.core?.typography?.line_height?.relaxed === 'number' ? tokens.core.typography.line_height.relaxed : 1.8);
  }, [tokens, tokenValues]);

  const weightNormal = useMemo(() => {
    return (tokenValues.get('core.typography.weight.normal') as number) ?? (typeof tokens.core?.typography?.weight?.normal === 'number' ? tokens.core.typography.weight.normal : 400);
  }, [tokens, tokenValues]);

  const weightMedium = useMemo(() => {
    return (tokenValues.get('core.typography.weight.medium') as number) ?? (typeof tokens.core?.typography?.weight?.medium === 'number' ? tokens.core.typography.weight.medium : 500);
  }, [tokens, tokenValues]);

  const weightBold = useMemo(() => {
    return (tokenValues.get('core.typography.weight.bold') as number) ?? (typeof tokens.core?.typography?.weight?.bold === 'number' ? tokens.core.typography.weight.bold : 700);
  }, [tokens, tokenValues]);

  // Filter by search query
  const matchesSearch = (label: string, path: string): boolean => {
    if (!searchQuery) return true;
    const query = searchQuery.toLowerCase();
    return label.toLowerCase().includes(query) || path.toLowerCase().includes(query);
  };

  // Available fonts
  const availableFonts = ['Inter', 'Poppins', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Raleway', 'Source Sans Pro'];

  return (
    <div className={styles.section}>
      {/* Font Family Group */}
      {matchesSearch('Font', 'font') && (
        <div className={styles.group}>
          <SectionHeader
            icon={<TextT weight="bold" />}
            title="Font Family"
            isExpanded={expandedGroups.has('font')}
            onToggle={() => toggleGroup('font')}
          />
          <AnimatePresence>
            {expandedGroups.has('font') && (
              <motion.div
                initial={{ height: 0, opacity: 0 }}
                animate={{ height: 'auto', opacity: 1 }}
                exit={{ height: 0, opacity: 0 }}
                transition={{ duration: 0.2, ease: 'easeInOut' }}
                className={styles.groupContent}
              >
                <TokenControl
                  label="Heading"
                  tokenPath="core.typography.font.heading"
                  value={headingFont}
                  onValueChange={(value) => handleTokenChange('core.typography.font.heading', value)}
                >
                  <FontSelect
                    value={headingFont}
                    options={availableFonts}
                    onChange={(value) => handleTokenChange('core.typography.font.heading', value)}
                  />
                  <div className={styles.fontPreview} style={{ fontFamily: headingFont }}>
                    The quick brown fox jumps over the lazy dog
                  </div>
                </TokenControl>

                <TokenControl
                  label="Body"
                  tokenPath="core.typography.font.body"
                  value={bodyFont}
                  onValueChange={(value) => handleTokenChange('core.typography.font.body', value)}
                >
                  <FontSelect
                    value={bodyFont}
                    options={availableFonts}
                    onChange={(value) => handleTokenChange('core.typography.font.body', value)}
                  />
                  <div className={styles.fontPreview} style={{ fontFamily: bodyFont }}>
                    The quick brown fox jumps over the lazy dog
                  </div>
                </TokenControl>

                <TokenControl
                  label="Metatext"
                  tokenPath="core.typography.font.metatext"
                  value={metatextFont}
                  onValueChange={(value) => handleTokenChange('core.typography.font.metatext', value)}
                >
                  <FontSelect
                    value={metatextFont}
                    options={availableFonts}
                    onChange={(value) => handleTokenChange('core.typography.font.metatext', value)}
                  />
                  <div className={styles.fontPreview} style={{ fontFamily: metatextFont }}>
                    The quick brown fox jumps over the lazy dog
                  </div>
                </TokenControl>
              </motion.div>
            )}
          </AnimatePresence>
        </div>
      )}

      {/* Scale Group */}
      {matchesSearch('Scale', 'scale') && (
        <div className={styles.group}>
          <SectionHeader
            icon={<TextAlignLeft weight="bold" />}
            title="Scale"
            isExpanded={expandedGroups.has('scale')}
            onToggle={() => toggleGroup('scale')}
          />
          <AnimatePresence>
            {expandedGroups.has('scale') && (
              <motion.div
                initial={{ height: 0, opacity: 0 }}
                animate={{ height: 'auto', opacity: 1 }}
                exit={{ height: 0, opacity: 0 }}
                transition={{ duration: 0.2, ease: 'easeInOut' }}
                className={styles.groupContent}
              >
              <TokenControl
                label="XL"
                tokenPath="core.typography.scale.xl"
                value={scaleXl}
                onValueChange={(value) => handleTokenChange('core.typography.scale.xl', value)}
              >
                <SliderInput
                  value={scaleXl}
                  min={0.5}
                  max={4.0}
                  step={0.01}
                  onChange={(value) => handleTokenChange('core.typography.scale.xl', value)}
                />
                <div className={styles.scalePreview} style={{ fontSize: `${scaleXl}rem` }}>
                  Sample Text
                </div>
              </TokenControl>

              <TokenControl
                label="LG"
                tokenPath="core.typography.scale.lg"
                value={scaleLg}
                onValueChange={(value) => handleTokenChange('core.typography.scale.lg', value)}
              >
                <SliderInput
                  value={scaleLg}
                  min={0.5}
                  max={4.0}
                  step={0.01}
                  onChange={(value) => handleTokenChange('core.typography.scale.lg', value)}
                />
                <div className={styles.scalePreview} style={{ fontSize: `${scaleLg}rem` }}>
                  Sample Text
                </div>
              </TokenControl>

              <TokenControl
                label="MD"
                tokenPath="core.typography.scale.md"
                value={scaleMd}
                onValueChange={(value) => handleTokenChange('core.typography.scale.md', value)}
              >
                <SliderInput
                  value={scaleMd}
                  min={0.5}
                  max={4.0}
                  step={0.01}
                  onChange={(value) => handleTokenChange('core.typography.scale.md', value)}
                />
                <div className={styles.scalePreview} style={{ fontSize: `${scaleMd}rem` }}>
                  Sample Text
                </div>
              </TokenControl>

              <TokenControl
                label="SM"
                tokenPath="core.typography.scale.sm"
                value={scaleSm}
                onValueChange={(value) => handleTokenChange('core.typography.scale.sm', value)}
              >
                <SliderInput
                  value={scaleSm}
                  min={0.5}
                  max={4.0}
                  step={0.01}
                  onChange={(value) => handleTokenChange('core.typography.scale.sm', value)}
                />
                <div className={styles.scalePreview} style={{ fontSize: `${scaleSm}rem` }}>
                  Sample Text
                </div>
              </TokenControl>

              <TokenControl
                label="XS"
                tokenPath="core.typography.scale.xs"
                value={scaleXs}
                onValueChange={(value) => handleTokenChange('core.typography.scale.xs', value)}
              >
                <SliderInput
                  value={scaleXs}
                  min={0.5}
                  max={4.0}
                  step={0.01}
                  onChange={(value) => handleTokenChange('core.typography.scale.xs', value)}
                />
                <div className={styles.scalePreview} style={{ fontSize: `${scaleXs}rem` }}>
                  Sample Text
                </div>
              </TokenControl>
              </motion.div>
            )}
          </AnimatePresence>
        </div>
      )}

      {/* Line Height Group */}
      {matchesSearch('Line Height', 'line_height') && (
        <div className={styles.group}>
          <SectionHeader
            icon={<Minus weight="bold" />}
            title="Line Height"
            isExpanded={expandedGroups.has('line_height')}
            onToggle={() => toggleGroup('line_height')}
          />
          <AnimatePresence>
            {expandedGroups.has('line_height') && (
              <motion.div
                initial={{ height: 0, opacity: 0 }}
                animate={{ height: 'auto', opacity: 1 }}
                exit={{ height: 0, opacity: 0 }}
                transition={{ duration: 0.2, ease: 'easeInOut' }}
                className={styles.groupContent}
              >
              <TokenControl
                label="Tight"
                tokenPath="core.typography.line_height.tight"
                value={lineHeightTight}
                onValueChange={(value) => handleTokenChange('core.typography.line_height.tight', value)}
              >
                <SliderInput
                  value={lineHeightTight}
                  min={1.0}
                  max={2.5}
                  step={0.01}
                  onChange={(value) => handleTokenChange('core.typography.line_height.tight', value)}
                />
                <div className={styles.lineHeightPreview} style={{ lineHeight: lineHeightTight }}>
                  Sample text with tight line height. Multiple lines to demonstrate the spacing.
                </div>
              </TokenControl>

              <TokenControl
                label="Normal"
                tokenPath="core.typography.line_height.normal"
                value={lineHeightNormal}
                onValueChange={(value) => handleTokenChange('core.typography.line_height.normal', value)}
              >
                <SliderInput
                  value={lineHeightNormal}
                  min={1.0}
                  max={2.5}
                  step={0.01}
                  onChange={(value) => handleTokenChange('core.typography.line_height.normal', value)}
                />
                <div className={styles.lineHeightPreview} style={{ lineHeight: lineHeightNormal }}>
                  Sample text with normal line height. Multiple lines to demonstrate the spacing.
                </div>
              </TokenControl>

              <TokenControl
                label="Relaxed"
                tokenPath="core.typography.line_height.relaxed"
                value={lineHeightRelaxed}
                onValueChange={(value) => handleTokenChange('core.typography.line_height.relaxed', value)}
              >
                <SliderInput
                  value={lineHeightRelaxed}
                  min={1.0}
                  max={2.5}
                  step={0.01}
                  onChange={(value) => handleTokenChange('core.typography.line_height.relaxed', value)}
                />
                <div className={styles.lineHeightPreview} style={{ lineHeight: lineHeightRelaxed }}>
                  Sample text with relaxed line height. Multiple lines to demonstrate the spacing.
                </div>
              </TokenControl>
              </motion.div>
            )}
          </AnimatePresence>
        </div>
      )}

      {/* Weight Group */}
      {matchesSearch('Weight', 'weight') && (
        <div className={styles.group}>
          <SectionHeader
            icon={<TextT weight="bold" />}
            title="Weight"
            isExpanded={expandedGroups.has('weight')}
            onToggle={() => toggleGroup('weight')}
          />
          <AnimatePresence>
            {expandedGroups.has('weight') && (
              <motion.div
                initial={{ height: 0, opacity: 0 }}
                animate={{ height: 'auto', opacity: 1 }}
                exit={{ height: 0, opacity: 0 }}
                transition={{ duration: 0.2, ease: 'easeInOut' }}
                className={styles.groupContent}
              >
              <TokenControl
                label="Normal"
                tokenPath="core.typography.weight.normal"
                value={weightNormal}
                onValueChange={(value) => handleTokenChange('core.typography.weight.normal', value)}
              >
                <SliderInput
                  value={weightNormal}
                  min={100}
                  max={900}
                  step={100}
                  onChange={(value) => handleTokenChange('core.typography.weight.normal', value)}
                />
                <div className={styles.weightPreview} style={{ fontWeight: weightNormal }}>
                  Sample Text
                </div>
              </TokenControl>

              <TokenControl
                label="Medium"
                tokenPath="core.typography.weight.medium"
                value={weightMedium}
                onValueChange={(value) => handleTokenChange('core.typography.weight.medium', value)}
              >
                <SliderInput
                  value={weightMedium}
                  min={100}
                  max={900}
                  step={100}
                  onChange={(value) => handleTokenChange('core.typography.weight.medium', value)}
                />
                <div className={styles.weightPreview} style={{ fontWeight: weightMedium }}>
                  Sample Text
                </div>
              </TokenControl>

              <TokenControl
                label="Bold"
                tokenPath="core.typography.weight.bold"
                value={weightBold}
                onValueChange={(value) => handleTokenChange('core.typography.weight.bold', value)}
              >
                <SliderInput
                  value={weightBold}
                  min={100}
                  max={900}
                  step={100}
                  onChange={(value) => handleTokenChange('core.typography.weight.bold', value)}
                />
                <div className={styles.weightPreview} style={{ fontWeight: weightBold }}>
                  Sample Text
                </div>
              </TokenControl>
              </motion.div>
            )}
          </AnimatePresence>
        </div>
      )}
    </div>
  );
}

