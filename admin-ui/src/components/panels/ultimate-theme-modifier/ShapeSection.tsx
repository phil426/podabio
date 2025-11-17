import { useState, useMemo } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Shapes, Square } from '@phosphor-icons/react';
import { TokenControl } from './TokenControl';
import { SectionHeader } from './SectionHeader';
import { SliderInput } from './SliderInput';
import type { TokenBundle } from '../../../design-system/tokens';
import styles from './shape-section.module.css';

interface ShapeSectionProps {
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

function parseRem(value: string | number): number {
  if (typeof value === 'number') return value;
  if (typeof value === 'string') {
    const match = value.match(/([\d.]+)rem/);
    if (match) return parseFloat(match[1]);
    const pxMatch = value.match(/([\d.]+)px/);
    if (pxMatch) return parseFloat(pxMatch[1]) / 16; // Convert px to rem
  }
  return 0;
}

function formatRem(value: number): string {
  return `${value}rem`;
}

export function ShapeSection({ tokens, onTokenChange, searchQuery = '', tokenValues = new Map() }: ShapeSectionProps): JSX.Element {
  const [expandedGroups, setExpandedGroups] = useState<Set<string>>(new Set([
    'corner', 'border_width', 'shadow'
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

  // Extract shape values from tokenValues
  const cornerNone = useMemo(() => {
    const value = tokenValues.get('shape_tokens.corner.none') as string;
    return parseRem(value || '0px');
  }, [tokenValues]);
  const cornerSm = useMemo(() => {
    const value = tokenValues.get('shape_tokens.corner.sm') as string;
    return parseRem(value || '0.4rem');
  }, [tokenValues]);
  const cornerMd = useMemo(() => {
    const value = tokenValues.get('shape_tokens.corner.md') as string;
    return parseRem(value || '0.9rem');
  }, [tokenValues]);
  const cornerLg = useMemo(() => {
    const value = tokenValues.get('shape_tokens.corner.lg') as string;
    return parseRem(value || '1.6rem');
  }, [tokenValues]);
  const cornerPill = useMemo(() => {
    const value = tokenValues.get('shape_tokens.corner.pill') as string;
    return parseRem(value || '999px');
  }, [tokenValues]);

  const borderHairline = useMemo(() => {
    const value = tokenValues.get('shape_tokens.border_width.hairline') as string;
    return value ? parseFloat(value.replace('px', '')) : 1;
  }, [tokenValues]);
  const borderRegular = useMemo(() => {
    const value = tokenValues.get('shape_tokens.border_width.regular') as string;
    return value ? parseFloat(value.replace('px', '')) : 2;
  }, [tokenValues]);
  const borderBold = useMemo(() => {
    const value = tokenValues.get('shape_tokens.border_width.bold') as string;
    return value ? parseFloat(value.replace('px', '')) : 4;
  }, [tokenValues]);

  // Filter by search query
  const matchesSearch = (label: string, path: string): boolean => {
    if (!searchQuery) return true;
    const query = searchQuery.toLowerCase();
    return label.toLowerCase().includes(query) || path.toLowerCase().includes(query);
  };

  return (
    <div className={styles.section}>
      {/* Corner Radius Group */}
      {matchesSearch('Corner', 'corner') && (
        <div className={styles.group}>
          <SectionHeader
            icon={<Shapes weight="bold" />}
            title="Corner Radius"
            isExpanded={expandedGroups.has('corner')}
            onToggle={() => toggleGroup('corner')}
          />
          <AnimatePresence>
            {expandedGroups.has('corner') && (
              <motion.div
                initial={{ height: 0, opacity: 0 }}
                animate={{ height: 'auto', opacity: 1 }}
                exit={{ height: 0, opacity: 0 }}
                transition={{ duration: 0.2, ease: 'easeInOut' }}
                className={styles.groupContent}
              >
              <TokenControl
                label="None"
                tokenPath="shape_tokens.corner.none"
                value={cornerNone}
                onValueChange={(value) => handleTokenChange('shape_tokens.corner.none', formatRem(value as number))}
              >
                <SliderInput
                  value={cornerNone}
                  min={0}
                  max={2.5}
                  step={0.01}
                  units={['rem', 'px']}
                  onChange={(value) => handleTokenChange('shape_tokens.corner.none', formatRem(value))}
                />
                <div className={styles.cornerPreview} style={{ borderRadius: formatRem(cornerNone) }} />
              </TokenControl>

              <TokenControl
                label="SM"
                tokenPath="shape_tokens.corner.sm"
                value={cornerSm}
                onValueChange={(value) => handleTokenChange('shape_tokens.corner.sm', formatRem(value as number))}
              >
                <SliderInput
                  value={cornerSm}
                  min={0}
                  max={2.5}
                  step={0.01}
                  units={['rem', 'px']}
                  onChange={(value) => handleTokenChange('shape_tokens.corner.sm', formatRem(value))}
                />
                <div className={styles.cornerPreview} style={{ borderRadius: formatRem(cornerSm) }} />
              </TokenControl>

              <TokenControl
                label="MD"
                tokenPath="shape_tokens.corner.md"
                value={cornerMd}
                onValueChange={(value) => handleTokenChange('shape_tokens.corner.md', formatRem(value as number))}
              >
                <SliderInput
                  value={cornerMd}
                  min={0}
                  max={2.5}
                  step={0.01}
                  units={['rem', 'px']}
                  onChange={(value) => handleTokenChange('shape_tokens.corner.md', formatRem(value))}
                />
                <div className={styles.cornerPreview} style={{ borderRadius: formatRem(cornerMd) }} />
              </TokenControl>

              <TokenControl
                label="LG"
                tokenPath="shape_tokens.corner.lg"
                value={cornerLg}
                onValueChange={(value) => handleTokenChange('shape_tokens.corner.lg', formatRem(value as number))}
              >
                <SliderInput
                  value={cornerLg}
                  min={0}
                  max={2.5}
                  step={0.01}
                  units={['rem', 'px']}
                  onChange={(value) => handleTokenChange('shape_tokens.corner.lg', formatRem(value))}
                />
                <div className={styles.cornerPreview} style={{ borderRadius: formatRem(cornerLg) }} />
              </TokenControl>

              <TokenControl
                label="Pill"
                tokenPath="shape_tokens.corner.pill"
                value={cornerPill}
                onValueChange={(value) => handleTokenChange('shape_tokens.corner.pill', formatRem(value as number))}
              >
                <SliderInput
                  value={cornerPill}
                  min={0}
                  max={2.5}
                  step={0.01}
                  units={['rem', 'px']}
                  onChange={(value) => handleTokenChange('shape_tokens.corner.pill', formatRem(value))}
                />
                <div className={styles.cornerPreview} style={{ borderRadius: formatRem(cornerPill) }} />
              </TokenControl>
              </motion.div>
            )}
          </AnimatePresence>
        </div>
      )}

      {/* Border Width Group */}
      {matchesSearch('Border Width', 'border_width') && (
        <div className={styles.group}>
          <SectionHeader
            icon={<Square weight="bold" />}
            title="Border Width"
            isExpanded={expandedGroups.has('border_width')}
            onToggle={() => toggleGroup('border_width')}
          />
          <AnimatePresence>
            {expandedGroups.has('border_width') && (
              <motion.div
                initial={{ height: 0, opacity: 0 }}
                animate={{ height: 'auto', opacity: 1 }}
                exit={{ height: 0, opacity: 0 }}
                transition={{ duration: 0.2, ease: 'easeInOut' }}
                className={styles.groupContent}
              >
              <TokenControl
                label="Hairline"
                tokenPath="shape_tokens.border_width.hairline"
                value={borderHairline}
                onValueChange={(value) => handleTokenChange('shape_tokens.border_width.hairline', `${value}px`)}
              >
                <SliderInput
                  value={borderHairline}
                  min={0}
                  max={8}
                  step={1}
                  unit="px"
                  onChange={(value) => handleTokenChange('shape_tokens.border_width.hairline', `${value}px`)}
                />
                <div className={styles.borderPreview} style={{ borderWidth: `${borderHairline}px` }} />
              </TokenControl>

              <TokenControl
                label="Regular"
                tokenPath="shape_tokens.border_width.regular"
                value={borderRegular}
                onValueChange={(value) => handleTokenChange('shape_tokens.border_width.regular', `${value}px`)}
              >
                <SliderInput
                  value={borderRegular}
                  min={0}
                  max={8}
                  step={1}
                  unit="px"
                  onChange={(value) => handleTokenChange('shape_tokens.border_width.regular', `${value}px`)}
                />
                <div className={styles.borderPreview} style={{ borderWidth: `${borderRegular}px` }} />
              </TokenControl>

              <TokenControl
                label="Bold"
                tokenPath="shape_tokens.border_width.bold"
                value={borderBold}
                onValueChange={(value) => handleTokenChange('shape_tokens.border_width.bold', `${value}px`)}
              >
                <SliderInput
                  value={borderBold}
                  min={0}
                  max={8}
                  step={1}
                  unit="px"
                  onChange={(value) => handleTokenChange('shape_tokens.border_width.bold', `${value}px`)}
                />
                <div className={styles.borderPreview} style={{ borderWidth: `${borderBold}px` }} />
              </TokenControl>
              </motion.div>
            )}
          </AnimatePresence>
        </div>
      )}

      {/* Shadow Group */}
      {matchesSearch('Shadow', 'shadow') && (
        <div className={styles.group}>
          <SectionHeader
            icon={<Shapes weight="bold" />}
            title="Shadow"
            isExpanded={expandedGroups.has('shadow')}
            onToggle={() => toggleGroup('shadow')}
          />
          <AnimatePresence>
            {expandedGroups.has('shadow') && (
              <motion.div
                initial={{ height: 0, opacity: 0 }}
                animate={{ height: 'auto', opacity: 1 }}
                exit={{ height: 0, opacity: 0 }}
                transition={{ duration: 0.2, ease: 'easeInOut' }}
                className={styles.groupContent}
              >
              <p className={styles.comingSoon}>Shadow controls coming soon...</p>
              </motion.div>
            )}
          </AnimatePresence>
        </div>
      )}
    </div>
  );
}

