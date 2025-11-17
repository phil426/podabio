import { useState, useMemo } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { Palette, TextT, Square, CheckCircle, Warning, XCircle, Sparkle, Lightning } from '@phosphor-icons/react';
import { ColorTokenPicker } from '../../controls/ColorTokenPicker';
import { TokenControl } from './TokenControl';
import { SectionHeader } from './SectionHeader';
import { SliderInput } from './SliderInput';
import type { TokenBundle } from '../../../design-system/tokens';
import styles from './colors-section.module.css';

interface ColorsSectionProps {
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

function extractColorValue(tokens: TokenBundle, path: string): string {
  const resolved = resolveToken(tokens, path);
  
  if (typeof resolved === 'string') {
    // Check if it's a hex color
    if (/^#([0-9a-fA-F]{3}){1,2}$/.test(resolved)) {
      return resolved;
    }
    // Check if it's a gradient
    if (resolved.includes('gradient')) {
      return resolved;
    }
    // Check if it's an image URL
    if (resolved.startsWith('http://') || resolved.startsWith('https://') || resolved.startsWith('/') || resolved.startsWith('data:')) {
      return resolved;
    }
    // Check if it's rgba
    if (resolved.startsWith('rgba(')) {
      return resolved;
    }
  }
  
  return '#2563eb'; // Default fallback
}

export function ColorsSection({ tokens, onTokenChange, searchQuery = '', tokenValues = new Map() }: ColorsSectionProps): JSX.Element {
  const [expandedGroups, setExpandedGroups] = useState<Set<string>>(new Set([
    'background', 'text', 'accent', 'border', 'state', 'shadow', 'gradient', 'glow'
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

  const handleColorChange = (path: string, value: string) => {
    const oldValue = resolveToken(tokens, path);
    onTokenChange(path, value, oldValue);
  };

  // Extract color values from tokens
  const backgroundBase = useMemo(() => extractColorValue(tokens, 'semantic.surface.canvas'), [tokens]);
  const backgroundSurface = useMemo(() => extractColorValue(tokens, 'semantic.surface.base'), [tokens]);
  const textPrimary = useMemo(() => extractColorValue(tokens, 'semantic.text.primary'), [tokens]);
  const textSecondary = useMemo(() => extractColorValue(tokens, 'semantic.text.secondary'), [tokens]);
  const textInverse = useMemo(() => extractColorValue(tokens, 'semantic.text.inverse'), [tokens]);
  const accentPrimary = useMemo(() => extractColorValue(tokens, 'semantic.accent.primary'), [tokens]);
  const accentMuted = useMemo(() => extractColorValue(tokens, 'semantic.accent.muted'), [tokens]);
  const borderDefault = useMemo(() => extractColorValue(tokens, 'semantic.divider.subtle'), [tokens]);
  const borderFocus = useMemo(() => extractColorValue(tokens, 'semantic.focus.ring'), [tokens]);
  const stateSuccess = useMemo(() => extractColorValue(tokens, 'semantic.state.success'), [tokens]);
  const stateWarning = useMemo(() => extractColorValue(tokens, 'semantic.state.warning'), [tokens]);
  const stateDanger = useMemo(() => extractColorValue(tokens, 'semantic.state.critical'), [tokens]);
  const textStateSuccess = useMemo(() => extractColorValue(tokens, 'semantic.text.success'), [tokens]);
  const textStateWarning = useMemo(() => extractColorValue(tokens, 'semantic.text.warning'), [tokens]);
  const textStateDanger = useMemo(() => extractColorValue(tokens, 'semantic.text.critical'), [tokens]);
  const shadowAmbient = useMemo(() => extractColorValue(tokens, 'semantic.shadow.ambient'), [tokens]);
  const shadowFocus = useMemo(() => extractColorValue(tokens, 'semantic.shadow.focus'), [tokens]);
  const glowPrimary = useMemo(() => extractColorValue(tokens, 'semantic.glow.primary'), [tokens]);
  
  // Gradient tokens - these are stored in color_tokens.gradient.* in the backend
  const gradientPage = useMemo(() => {
    return (tokenValues.get('color_tokens.gradient.page') as string) || 'linear-gradient(140deg, #02040d 0%, #0a1331 45%, #1a2151 100%)';
  }, [tokenValues]);
  const gradientAccent = useMemo(() => {
    return (tokenValues.get('color_tokens.gradient.accent') as string) || 'linear-gradient(120deg, #7affd8 0%, #5b9cff 55%, #a875ff 100%)';
  }, [tokenValues]);
  const gradientWidget = useMemo(() => {
    return (tokenValues.get('color_tokens.gradient.widget') as string) || 'linear-gradient(135deg, rgba(122,255,216,0.18) 0%, rgba(91,156,255,0.14) 50%, rgba(168,117,255,0.24) 100%)';
  }, [tokenValues]);
  const gradientPodcast = useMemo(() => {
    return (tokenValues.get('color_tokens.gradient.podcast') as string) || 'linear-gradient(135deg, #040610 0%, #111b3a 60%, #1c2854 100%)';
  }, [tokenValues]);

  // Filter by search query
  const matchesSearch = (label: string, path: string): boolean => {
    if (!searchQuery) return true;
    const query = searchQuery.toLowerCase();
    return label.toLowerCase().includes(query) || path.toLowerCase().includes(query);
  };

  return (
    <div className={styles.section}>
      {/* Background Group */}
      {matchesSearch('Background', 'background') && (
        <div className={styles.group}>
          <SectionHeader
            icon={<Palette weight="bold" />}
            title="Background"
            isExpanded={expandedGroups.has('background')}
            onToggle={() => toggleGroup('background')}
          />
          <AnimatePresence>
            {expandedGroups.has('background') && (
              <motion.div
                initial={{ height: 0, opacity: 0 }}
                animate={{ height: 'auto', opacity: 1 }}
                exit={{ height: 0, opacity: 0 }}
                transition={{ duration: 0.2, ease: 'easeInOut' }}
                className={styles.groupContent}
              >
              <TokenControl
                label="Base"
                tokenPath="semantic.surface.canvas"
                value={backgroundBase}
                onValueChange={(value) => handleColorChange('semantic.surface.canvas', value as string)}
              >
                <ColorTokenPicker
                  label="Base"
                  token="semantic.surface.canvas"
                  value={backgroundBase}
                  onChange={(value) => handleColorChange('semantic.surface.canvas', value)}
                  hideToken
                />
              </TokenControl>

              <TokenControl
                label="Surface"
                tokenPath="semantic.surface.base"
                value={backgroundSurface}
                onValueChange={(value) => handleColorChange('semantic.surface.base', value as string)}
              >
                <ColorTokenPicker
                  label="Surface"
                  token="semantic.surface.base"
                  value={backgroundSurface}
                  onChange={(value) => handleColorChange('semantic.surface.base', value)}
                  hideToken
                />
              </TokenControl>
              </motion.div>
            )}
          </AnimatePresence>
        </div>
      )}

      {/* Text Group */}
      {matchesSearch('Text', 'text') && (
        <div className={styles.group}>
          <SectionHeader
            icon={<TextT weight="bold" />}
            title="Text"
            isExpanded={expandedGroups.has('text')}
            onToggle={() => toggleGroup('text')}
          />
          <AnimatePresence>
            {expandedGroups.has('text') && (
              <motion.div
                initial={{ height: 0, opacity: 0 }}
                animate={{ height: 'auto', opacity: 1 }}
                exit={{ height: 0, opacity: 0 }}
                transition={{ duration: 0.2, ease: 'easeInOut' }}
                className={styles.groupContent}
              >
              <TokenControl
                label="Primary"
                tokenPath="semantic.text.primary"
                value={textPrimary}
                onValueChange={(value) => handleColorChange('semantic.text.primary', value as string)}
              >
                <ColorTokenPicker
                  label="Primary"
                  token="semantic.text.primary"
                  value={textPrimary}
                  onChange={(value) => handleColorChange('semantic.text.primary', value)}
                  hideToken
                />
              </TokenControl>

              <TokenControl
                label="Secondary"
                tokenPath="semantic.text.secondary"
                value={textSecondary}
                onValueChange={(value) => handleColorChange('semantic.text.secondary', value as string)}
              >
                <ColorTokenPicker
                  label="Secondary"
                  token="semantic.text.secondary"
                  value={textSecondary}
                  onChange={(value) => handleColorChange('semantic.text.secondary', value)}
                  hideToken
                />
              </TokenControl>

              <TokenControl
                label="Inverse"
                tokenPath="semantic.text.inverse"
                value={textInverse}
                onValueChange={(value) => handleColorChange('semantic.text.inverse', value as string)}
              >
                <ColorTokenPicker
                  label="Inverse"
                  token="semantic.text.inverse"
                  value={textInverse}
                  onChange={(value) => handleColorChange('semantic.text.inverse', value)}
                  hideToken
                />
              </TokenControl>
              </motion.div>
            )}
          </AnimatePresence>
        </div>
      )}

      {/* Accent Group */}
      {matchesSearch('Accent', 'accent') && (
        <div className={styles.group}>
          <SectionHeader
            icon={<Sparkle weight="bold" />}
            title="Accent"
            isExpanded={expandedGroups.has('accent')}
            onToggle={() => toggleGroup('accent')}
          />
          <AnimatePresence>
            {expandedGroups.has('accent') && (
              <motion.div
                initial={{ height: 0, opacity: 0 }}
                animate={{ height: 'auto', opacity: 1 }}
                exit={{ height: 0, opacity: 0 }}
                transition={{ duration: 0.2, ease: 'easeInOut' }}
                className={styles.groupContent}
              >
              <TokenControl
                label="Primary"
                tokenPath="semantic.accent.primary"
                value={accentPrimary}
                onValueChange={(value) => handleColorChange('semantic.accent.primary', value as string)}
              >
                <ColorTokenPicker
                  label="Primary"
                  token="semantic.accent.primary"
                  value={accentPrimary}
                  onChange={(value) => handleColorChange('semantic.accent.primary', value)}
                  hideToken
                />
              </TokenControl>

              <TokenControl
                label="Muted"
                tokenPath="semantic.accent.muted"
                value={accentMuted}
                onValueChange={(value) => handleColorChange('semantic.accent.muted', value as string)}
              >
                <ColorTokenPicker
                  label="Muted"
                  token="semantic.accent.muted"
                  value={accentMuted}
                  onChange={(value) => handleColorChange('semantic.accent.muted', value)}
                  hideToken
                />
              </TokenControl>
              </motion.div>
            )}
          </AnimatePresence>
        </div>
      )}

      {/* Border Group */}
      {matchesSearch('Border', 'border') && (
        <div className={styles.group}>
          <SectionHeader
            icon={<Square weight="bold" />}
            title="Border"
            isExpanded={expandedGroups.has('border')}
            onToggle={() => toggleGroup('border')}
          />
          <AnimatePresence>
            {expandedGroups.has('border') && (
              <motion.div
                initial={{ height: 0, opacity: 0 }}
                animate={{ height: 'auto', opacity: 1 }}
                exit={{ height: 0, opacity: 0 }}
                transition={{ duration: 0.2, ease: 'easeInOut' }}
                className={styles.groupContent}
              >
              <TokenControl
                label="Default"
                tokenPath="semantic.divider.subtle"
                value={borderDefault}
                onValueChange={(value) => handleColorChange('semantic.divider.subtle', value as string)}
              >
                <ColorTokenPicker
                  label="Default"
                  token="semantic.divider.subtle"
                  value={borderDefault}
                  onChange={(value) => handleColorChange('semantic.divider.subtle', value)}
                  hideToken
                />
              </TokenControl>

              <TokenControl
                label="Focus"
                tokenPath="semantic.focus.ring"
                value={borderFocus}
                onValueChange={(value) => handleColorChange('semantic.focus.ring', value as string)}
              >
                <ColorTokenPicker
                  label="Focus"
                  token="semantic.focus.ring"
                  value={borderFocus}
                  onChange={(value) => handleColorChange('semantic.focus.ring', value)}
                  hideToken
                />
              </TokenControl>
              </motion.div>
            )}
          </AnimatePresence>
        </div>
      )}

      {/* State Group */}
      {matchesSearch('State', 'state') && (
        <div className={styles.group}>
          <SectionHeader
            icon={<CheckCircle weight="bold" />}
            title="State"
            isExpanded={expandedGroups.has('state')}
            onToggle={() => toggleGroup('state')}
          />
          <AnimatePresence>
            {expandedGroups.has('state') && (
              <motion.div
                initial={{ height: 0, opacity: 0 }}
                animate={{ height: 'auto', opacity: 1 }}
                exit={{ height: 0, opacity: 0 }}
                transition={{ duration: 0.2, ease: 'easeInOut' }}
                className={styles.groupContent}
              >
              <TokenControl
                label="Success"
                tokenPath="semantic.state.success"
                value={stateSuccess}
                onValueChange={(value) => handleColorChange('semantic.state.success', value as string)}
              >
                <ColorTokenPicker
                  label="Success"
                  token="semantic.state.success"
                  value={stateSuccess}
                  onChange={(value) => handleColorChange('semantic.state.success', value)}
                  hideToken
                />
              </TokenControl>

              <TokenControl
                label="Warning"
                tokenPath="semantic.state.warning"
                value={stateWarning}
                onValueChange={(value) => handleColorChange('semantic.state.warning', value as string)}
              >
                <ColorTokenPicker
                  label="Warning"
                  token="semantic.state.warning"
                  value={stateWarning}
                  onChange={(value) => handleColorChange('semantic.state.warning', value)}
                  hideToken
                />
              </TokenControl>

              <TokenControl
                label="Danger"
                tokenPath="semantic.state.critical"
                value={stateDanger}
                onValueChange={(value) => handleColorChange('semantic.state.critical', value as string)}
              >
                <ColorTokenPicker
                  label="Danger"
                  token="semantic.state.critical"
                  value={stateDanger}
                  onChange={(value) => handleColorChange('semantic.state.critical', value)}
                  hideToken
                />
              </TokenControl>

              <div className={styles.subGroup}>
                <h4 className={styles.subGroupTitle}>Text State Colors</h4>
                <TokenControl
                  label="Success Text"
                  tokenPath="semantic.text.success"
                  value={textStateSuccess}
                  onValueChange={(value) => handleColorChange('semantic.text.success', value as string)}
                >
                  <ColorTokenPicker
                    label="Success Text"
                    token="semantic.text.success"
                    value={textStateSuccess}
                    onChange={(value) => handleColorChange('semantic.text.success', value)}
                    hideToken
                  />
                </TokenControl>

                <TokenControl
                  label="Warning Text"
                  tokenPath="semantic.text.warning"
                  value={textStateWarning}
                  onValueChange={(value) => handleColorChange('semantic.text.warning', value as string)}
                >
                  <ColorTokenPicker
                    label="Warning Text"
                    token="semantic.text.warning"
                    value={textStateWarning}
                    onChange={(value) => handleColorChange('semantic.text.warning', value)}
                    hideToken
                  />
                </TokenControl>

                <TokenControl
                  label="Danger Text"
                  tokenPath="semantic.text.critical"
                  value={textStateDanger}
                  onValueChange={(value) => handleColorChange('semantic.text.critical', value as string)}
                >
                  <ColorTokenPicker
                    label="Danger Text"
                    token="semantic.text.critical"
                    value={textStateDanger}
                    onChange={(value) => handleColorChange('semantic.text.critical', value)}
                    hideToken
                  />
                </TokenControl>
              </div>
              </motion.div>
            )}
          </AnimatePresence>
        </div>
      )}

      {/* Shadow Group */}
      {matchesSearch('Shadow', 'shadow') && (
        <div className={styles.group}>
          <SectionHeader
            icon={<Lightning weight="bold" />}
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
              <TokenControl
                label="Ambient"
                tokenPath="semantic.shadow.ambient"
                value={shadowAmbient}
                onValueChange={(value) => handleColorChange('semantic.shadow.ambient', value as string)}
              >
                <ColorTokenPicker
                  label="Ambient"
                  token="semantic.shadow.ambient"
                  value={shadowAmbient}
                  onChange={(value) => handleColorChange('semantic.shadow.ambient', value)}
                  hideToken
                />
              </TokenControl>

              <TokenControl
                label="Focus"
                tokenPath="semantic.shadow.focus"
                value={shadowFocus}
                onValueChange={(value) => handleColorChange('semantic.shadow.focus', value as string)}
              >
                <ColorTokenPicker
                  label="Focus"
                  token="semantic.shadow.focus"
                  value={shadowFocus}
                  onChange={(value) => handleColorChange('semantic.shadow.focus', value)}
                  hideToken
                />
              </TokenControl>
              </motion.div>
            )}
          </AnimatePresence>
        </div>
      )}

      {/* Gradient Group */}
      {matchesSearch('Gradient', 'gradient') && (
        <div className={styles.group}>
          <SectionHeader
            icon={<Sparkle weight="bold" />}
            title="Gradient"
            isExpanded={expandedGroups.has('gradient')}
            onToggle={() => toggleGroup('gradient')}
          />
          <AnimatePresence>
            {expandedGroups.has('gradient') && (
              <motion.div
                initial={{ height: 0, opacity: 0 }}
                animate={{ height: 'auto', opacity: 1 }}
                exit={{ height: 0, opacity: 0 }}
                transition={{ duration: 0.2, ease: 'easeInOut' }}
                className={styles.groupContent}
              >
              <TokenControl
                label="Page"
                tokenPath="color_tokens.gradient.page"
                value={gradientPage}
                onValueChange={(value) => handleColorChange('color_tokens.gradient.page', value as string)}
              >
                <ColorTokenPicker
                  label="Page"
                  token="color_tokens.gradient.page"
                  value={gradientPage}
                  onChange={(value) => handleColorChange('color_tokens.gradient.page', value)}
                  hideToken
                  hideModeToggle={false}
                />
              </TokenControl>

              <TokenControl
                label="Accent"
                tokenPath="color_tokens.gradient.accent"
                value={gradientAccent}
                onValueChange={(value) => handleColorChange('color_tokens.gradient.accent', value as string)}
              >
                <ColorTokenPicker
                  label="Accent"
                  token="color_tokens.gradient.accent"
                  value={gradientAccent}
                  onChange={(value) => handleColorChange('color_tokens.gradient.accent', value)}
                  hideToken
                  hideModeToggle={false}
                />
              </TokenControl>

              <TokenControl
                label="Widget"
                tokenPath="color_tokens.gradient.widget"
                value={gradientWidget}
                onValueChange={(value) => handleColorChange('color_tokens.gradient.widget', value as string)}
              >
                <ColorTokenPicker
                  label="Widget"
                  token="color_tokens.gradient.widget"
                  value={gradientWidget}
                  onChange={(value) => handleColorChange('color_tokens.gradient.widget', value)}
                  hideToken
                  hideModeToggle={false}
                />
              </TokenControl>

              <TokenControl
                label="Podcast"
                tokenPath="color_tokens.gradient.podcast"
                value={gradientPodcast}
                onValueChange={(value) => handleColorChange('color_tokens.gradient.podcast', value as string)}
              >
                <ColorTokenPicker
                  label="Podcast"
                  token="color_tokens.gradient.podcast"
                  value={gradientPodcast}
                  onChange={(value) => handleColorChange('color_tokens.gradient.podcast', value)}
                  hideToken
                  hideModeToggle={false}
                />
              </TokenControl>
              </motion.div>
            )}
          </AnimatePresence>
        </div>
      )}

      {/* Glow Group */}
      {matchesSearch('Glow', 'glow') && (
        <div className={styles.group}>
          <SectionHeader
            icon={<Lightning weight="bold" />}
            title="Glow"
            isExpanded={expandedGroups.has('glow')}
            onToggle={() => toggleGroup('glow')}
          />
          <AnimatePresence>
            {expandedGroups.has('glow') && (
              <motion.div
                initial={{ height: 0, opacity: 0 }}
                animate={{ height: 'auto', opacity: 1 }}
                exit={{ height: 0, opacity: 0 }}
                transition={{ duration: 0.2, ease: 'easeInOut' }}
                className={styles.groupContent}
              >
              <TokenControl
                label="Primary"
                tokenPath="semantic.glow.primary"
                value={glowPrimary}
                onValueChange={(value) => handleColorChange('semantic.glow.primary', value as string)}
              >
                <ColorTokenPicker
                  label="Primary"
                  token="semantic.glow.primary"
                  value={glowPrimary}
                  onChange={(value) => handleColorChange('semantic.glow.primary', value)}
                  hideToken
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

