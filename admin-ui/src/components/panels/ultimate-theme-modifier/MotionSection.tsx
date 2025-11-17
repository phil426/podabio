import { useState, useMemo } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Lightning, Gauge } from '@phosphor-icons/react';
import { TokenControl } from './TokenControl';
import { SectionHeader } from './SectionHeader';
import { SliderInput } from './SliderInput';
import type { TokenBundle } from '../../../design-system/tokens';
import styles from './motion-section.module.css';

interface MotionSectionProps {
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

function parseMs(value: string | number): number {
  if (typeof value === 'number') return value;
  if (typeof value === 'string') {
    const match = value.match(/([\d.]+)ms/);
    if (match) return parseFloat(match[1]);
  }
  return 160;
}

function formatMs(value: number): string {
  return `${value}ms`;
}

export function MotionSection({ tokens, onTokenChange, searchQuery = '', tokenValues = new Map() }: MotionSectionProps): JSX.Element {
  const [expandedGroups, setExpandedGroups] = useState<Set<string>>(new Set([
    'duration', 'easing', 'focus'
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

  // Extract motion values from tokenValues
  const durationFast = useMemo(() => {
    const value = tokenValues.get('motion_tokens.duration.fast') as string;
    return parseMs(value || '160ms');
  }, [tokenValues]);
  const durationStandard = useMemo(() => {
    const value = tokenValues.get('motion_tokens.duration.standard') as string;
    return parseMs(value || '260ms');
  }, [tokenValues]);

  const easingStandard = useMemo(() => {
    return (tokenValues.get('motion_tokens.easing.standard') as string) || 'cubic-bezier(0.4, 0, 0.2, 1)';
  }, [tokenValues]);
  const easingDecelerate = useMemo(() => {
    return (tokenValues.get('motion_tokens.easing.decelerate') as string) || 'cubic-bezier(0.0, 0, 0.2, 1)';
  }, [tokenValues]);

  const focusRingWidth = useMemo(() => {
    const value = tokenValues.get('motion_tokens.focus.ring_width') as string;
    return value ? parseFloat(value.replace('px', '')) : 3;
  }, [tokenValues]);
  const focusRingOffset = useMemo(() => {
    const value = tokenValues.get('motion_tokens.focus.ring_offset') as string;
    return value ? parseFloat(value.replace('px', '')) : 2;
  }, [tokenValues]);

  // Filter by search query
  const matchesSearch = (label: string, path: string): boolean => {
    if (!searchQuery) return true;
    const query = searchQuery.toLowerCase();
    return label.toLowerCase().includes(query) || path.toLowerCase().includes(query);
  };

  return (
    <div className={styles.section}>
      {/* Duration Group */}
      {matchesSearch('Duration', 'duration') && (
        <div className={styles.group}>
          <SectionHeader
            icon={<Gauge weight="bold" />}
            title="Duration"
            isExpanded={expandedGroups.has('duration')}
            onToggle={() => toggleGroup('duration')}
          />
          <AnimatePresence>
            {expandedGroups.has('duration') && (
              <motion.div
                initial={{ height: 0, opacity: 0 }}
                animate={{ height: 'auto', opacity: 1 }}
                exit={{ height: 0, opacity: 0 }}
                transition={{ duration: 0.2, ease: 'easeInOut' }}
                className={styles.groupContent}
              >
              <TokenControl
                label="Fast"
                tokenPath="motion_tokens.duration.fast"
                value={durationFast}
                onValueChange={(value) => handleTokenChange('motion_tokens.duration.fast', formatMs(value as number))}
              >
                <SliderInput
                  value={durationFast}
                  min={0}
                  max={1000}
                  step={10}
                  unit="ms"
                  onChange={(value) => handleTokenChange('motion_tokens.duration.fast', formatMs(value))}
                />
                <button
                  className={styles.previewButton}
                  onClick={() => {
                    // Animation preview
                  }}
                >
                  Preview
                </button>
              </TokenControl>

              <TokenControl
                label="Standard"
                tokenPath="motion_tokens.duration.standard"
                value={durationStandard}
                onValueChange={(value) => handleTokenChange('motion_tokens.duration.standard', formatMs(value as number))}
              >
                <SliderInput
                  value={durationStandard}
                  min={0}
                  max={1000}
                  step={10}
                  unit="ms"
                  onChange={(value) => handleTokenChange('motion_tokens.duration.standard', formatMs(value))}
                />
                <button
                  className={styles.previewButton}
                  onClick={() => {
                    // Animation preview
                  }}
                >
                  Preview
                </button>
              </TokenControl>
              </motion.div>
            )}
          </AnimatePresence>
        </div>
      )}

      {/* Easing Group */}
      {matchesSearch('Easing', 'easing') && (
        <div className={styles.group}>
          <SectionHeader
            icon={<Lightning weight="bold" />}
            title="Easing"
            isExpanded={expandedGroups.has('easing')}
            onToggle={() => toggleGroup('easing')}
          />
          <AnimatePresence>
            {expandedGroups.has('easing') && (
              <motion.div
                initial={{ height: 0, opacity: 0 }}
                animate={{ height: 'auto', opacity: 1 }}
                exit={{ height: 0, opacity: 0 }}
                transition={{ duration: 0.2, ease: 'easeInOut' }}
                className={styles.groupContent}
              >
              <TokenControl
                label="Standard"
                tokenPath="motion_tokens.easing.standard"
                value={easingStandard}
                onValueChange={(value) => handleTokenChange('motion_tokens.easing.standard', value as string)}
              >
                <input
                  type="text"
                  className={styles.easingInput}
                  value={easingStandard}
                  onChange={(e) => handleTokenChange('motion_tokens.easing.standard', e.target.value)}
                />
              </TokenControl>

              <TokenControl
                label="Decelerate"
                tokenPath="motion_tokens.easing.decelerate"
                value={easingDecelerate}
                onValueChange={(value) => handleTokenChange('motion_tokens.easing.decelerate', value as string)}
              >
                <input
                  type="text"
                  className={styles.easingInput}
                  value={easingDecelerate}
                  onChange={(e) => handleTokenChange('motion_tokens.easing.decelerate', e.target.value)}
                />
              </TokenControl>
              </motion.div>
            )}
          </AnimatePresence>
        </div>
      )}

      {/* Focus Ring Group */}
      {matchesSearch('Focus Ring', 'focus') && (
        <div className={styles.group}>
          <SectionHeader
            icon={<Lightning weight="bold" />}
            title="Focus Ring"
            isExpanded={expandedGroups.has('focus')}
            onToggle={() => toggleGroup('focus')}
          />
          <AnimatePresence>
            {expandedGroups.has('focus') && (
              <motion.div
                initial={{ height: 0, opacity: 0 }}
                animate={{ height: 'auto', opacity: 1 }}
                exit={{ height: 0, opacity: 0 }}
                transition={{ duration: 0.2, ease: 'easeInOut' }}
                className={styles.groupContent}
              >
              <TokenControl
                label="Ring Width"
                tokenPath="motion_tokens.focus.ring_width"
                value={focusRingWidth}
                onValueChange={(value) => handleTokenChange('motion_tokens.focus.ring_width', `${value}px`)}
              >
                <SliderInput
                  value={focusRingWidth}
                  min={0}
                  max={8}
                  step={0.5}
                  unit="px"
                  onChange={(value) => handleTokenChange('motion_tokens.focus.ring_width', `${value}px`)}
                />
              </TokenControl>

              <TokenControl
                label="Ring Offset"
                tokenPath="motion_tokens.focus.ring_offset"
                value={focusRingOffset}
                onValueChange={(value) => handleTokenChange('motion_tokens.focus.ring_offset', `${value}px`)}
              >
                <SliderInput
                  value={focusRingOffset}
                  min={0}
                  max={8}
                  step={0.5}
                  unit="px"
                  onChange={(value) => handleTokenChange('motion_tokens.focus.ring_offset', `${value}px`)}
                />
              </TokenControl>
              </motion.div>
            )}
          </AnimatePresence>
        </div>
      )}
    </div>
  );
}

