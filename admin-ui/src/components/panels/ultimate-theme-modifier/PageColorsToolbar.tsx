import { useState, useRef, useEffect, useMemo } from 'react';
import * as Popover from '@radix-ui/react-popover';
import * as Tabs from '@radix-ui/react-tabs';
import { TextT, Palette, Square, Sparkle, Image as ImageIcon, TextB, TextItalic, TextUnderline, TextAlignLeft, TextAlignCenter, TextAlignRight } from '@phosphor-icons/react';
import { HexColorPicker } from 'react-colorful';
import { PageBackgroundPicker } from '../../controls/PageBackgroundPicker';
import { FontSelect } from './FontSelect';
import { ALL_FONTS } from './fonts';
import type { TokenBundle } from '../../../design-system/tokens';
import styles from './page-colors-toolbar.module.css';

interface PageColorsToolbarProps {
  tokens: TokenBundle;
  tokenValues: Map<string, unknown>;
  onTokenChange: (path: string, value: unknown, oldValue: unknown) => void;
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

const availableFonts = ALL_FONTS;

// Color Button Component
interface ColorButtonProps {
  label: string;
  value: string;
  onChange: (value: string) => void;
}

function ColorButton({ label, value, onChange }: ColorButtonProps): JSX.Element {
  const [open, setOpen] = useState(false);
  const [tempColor, setTempColor] = useState(value);
  const popoverRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    setTempColor(value);
  }, [value]);

  const getHexColor = (colorValue: string): string => {
    if (colorValue.startsWith('#')) {
      return colorValue;
    }
    const hexMatch = colorValue.match(/#[0-9a-fA-F]{6}/i);
    if (hexMatch) {
      return hexMatch[0];
    }
    return '#111827';
  };

  const hexColor = getHexColor(value);
  const isGradient = value.includes('gradient');

  const handleColorChange = (newColor: string) => {
    setTempColor(newColor);
    onChange(newColor);
  };

  const handleOpenChange = (isOpen: boolean) => {
    setOpen(isOpen);
    if (!isOpen) {
      setTempColor(value);
    }
  };

  return (
    <Popover.Root open={open} onOpenChange={handleOpenChange}>
      <Popover.Trigger asChild>
        <button
          type="button"
          className={styles.colorButton}
          title={label}
          aria-label={label}
        >
          <div className={styles.colorButtonIcon}>
            <TextT size={16} weight="bold" />
            <div 
              className={styles.colorButtonSwatch}
              style={{ 
                backgroundColor: hexColor,
                backgroundImage: isGradient ? value : undefined
              }}
            />
            <div 
              className={styles.colorButtonChip}
              style={{ 
                backgroundColor: hexColor,
                backgroundImage: isGradient ? value : undefined
              }}
            />
          </div>
        </button>
      </Popover.Trigger>
      <Popover.Portal>
        <Popover.Content
          ref={popoverRef}
          className={styles.colorPopover}
          sideOffset={5}
          align="start"
        >
          <div className={styles.colorPopoverContent}>
            <HexColorPicker
              color={hexColor}
              onChange={handleColorChange}
            />
            <div className={styles.colorPopoverInput}>
              <input
                type="text"
                value={tempColor}
                onChange={(e) => {
                  setTempColor(e.target.value);
                  onChange(e.target.value);
                }}
                placeholder="#000000"
                className={styles.colorInput}
              />
            </div>
          </div>
        </Popover.Content>
      </Popover.Portal>
    </Popover.Root>
  );
}

// Background Button Component
interface BackgroundButtonProps {
  label: string;
  value: string;
  backgroundType: 'solid' | 'gradient' | 'image';
  backgroundImage?: string | null;
  onValueChange: (value: string) => void;
  onTypeChange: (type: 'solid' | 'gradient' | 'image') => void;
  onImageChange?: (url: string) => void;
  onImageUpload?: (file: File) => void;
}

function BackgroundButton({ 
  label, 
  value, 
  backgroundType,
  backgroundImage,
  onValueChange, 
  onTypeChange,
  onImageChange,
  onImageUpload
}: BackgroundButtonProps): JSX.Element {
  const [open, setOpen] = useState(false);
  const popoverRef = useRef<HTMLDivElement>(null);

  const getDisplayValue = (): string => {
    if (backgroundType === 'image' && backgroundImage) {
      return backgroundImage;
    }
    if (backgroundType === 'gradient') {
      return value;
    }
    if (value.startsWith('#')) {
      return value;
    }
    const hexMatch = value.match(/#[0-9a-fA-F]{6}/i);
    return hexMatch ? hexMatch[0] : '#FFFFFF';
  };

  const displayValue = getDisplayValue();
  const isGradient = backgroundType === 'gradient' || value.includes('gradient');
  const isImage = backgroundType === 'image' && backgroundImage;

  return (
    <Popover.Root open={open} onOpenChange={setOpen}>
      <Popover.Trigger asChild>
        <button
          type="button"
          className={styles.colorButton}
          title={label}
          aria-label={label}
        >
          <div className={styles.colorButtonIcon}>
            <Palette size={18} weight="bold" />
            <div 
              className={styles.colorButtonSwatch}
              style={{ 
                backgroundColor: isImage ? 'transparent' : (isGradient ? 'transparent' : displayValue),
                backgroundImage: isGradient || isImage ? (isImage ? `url(${displayValue})` : displayValue) : undefined,
                backgroundSize: isImage ? 'cover' : undefined,
                backgroundPosition: isImage ? 'center' : undefined
              }}
            />
            <div 
              className={styles.colorButtonChip}
              style={{ 
                backgroundColor: isImage ? 'transparent' : (isGradient ? 'transparent' : displayValue),
                backgroundImage: isGradient || isImage ? (isImage ? `url(${displayValue})` : displayValue) : undefined,
                backgroundSize: isImage ? 'cover' : undefined,
                backgroundPosition: isImage ? 'center' : undefined
              }}
            />
          </div>
        </button>
      </Popover.Trigger>
      <Popover.Portal>
        <Popover.Content
          ref={popoverRef}
          className={styles.backgroundPopover}
          sideOffset={5}
          align="start"
        >
          <div className={styles.backgroundPopoverContent}>
            <Tabs.Root 
              value={backgroundType}
              onValueChange={(value) => onTypeChange(value as 'solid' | 'gradient' | 'image')}
            >
              <Tabs.List className={styles.backgroundTabsList}>
                <Tabs.Trigger value="solid" className={styles.backgroundTabTrigger}>
                  <Square size={14} />
                  <span>Solid</span>
                </Tabs.Trigger>
                <Tabs.Trigger value="gradient" className={styles.backgroundTabTrigger}>
                  <Sparkle size={14} />
                  <span>Gradient</span>
                </Tabs.Trigger>
                <Tabs.Trigger value="image" className={styles.backgroundTabTrigger}>
                  <ImageIcon size={14} />
                  <span>Image</span>
                </Tabs.Trigger>
              </Tabs.List>
              <Tabs.Content value={backgroundType} className={styles.backgroundTabContent}>
                {backgroundType === 'image' ? (
                  <div className={styles.imageUpload}>
                    <input
                      type="url"
                      placeholder="Image URL"
                      value={backgroundImage || ''}
                      onChange={(e) => {
                        onImageChange?.(e.target.value);
                      }}
                      className={styles.urlInput}
                    />
                    <input
                      type="file"
                      accept="image/*"
                      onChange={(e) => {
                        const file = e.target.files?.[0];
                        if (file) onImageUpload?.(file);
                      }}
                      className={styles.fileInput}
                    />
                  </div>
                ) : (
                  <PageBackgroundPicker
                    value={value}
                    onChange={onValueChange}
                    mode={backgroundType}
                    hidePresets
                    presetsOnly={false}
                  />
                )}
              </Tabs.Content>
            </Tabs.Root>
          </div>
        </Popover.Content>
      </Popover.Portal>
    </Popover.Root>
  );
}

export function PageColorsToolbar({ tokens, tokenValues, onTokenChange }: PageColorsToolbarProps): JSX.Element {
  // Extract values
  const pageHeadingText = useMemo(() => extractColorValue(tokens, 'semantic.text.primary'), [tokens]);
  const pageBodyText = useMemo(() => extractColorValue(tokens, 'semantic.text.secondary'), [tokens]);
  const pageBackground = useMemo(() => extractColorValue(tokens, 'semantic.surface.canvas'), [tokens]);
  
  const headingFont = useMemo(() => {
    const font = tokens.core?.typography?.font?.heading;
    return typeof font === 'string' ? font.split(',')[0].trim() : 'Inter';
  }, [tokens]);

  const bodyFont = useMemo(() => {
    const font = tokens.core?.typography?.font?.body;
    return typeof font === 'string' ? font.split(',')[0].trim() : 'Inter';
  }, [tokens]);

  const backgroundType = useMemo(() => {
    const bg = pageBackground;
    if (bg.includes('gradient') || bg.includes('linear-gradient') || bg.includes('radial-gradient')) {
      return 'gradient';
    }
    if (bg.startsWith('http://') || bg.startsWith('https://') || bg.startsWith('/') || bg.startsWith('data:')) {
      return 'image';
    }
    return 'solid';
  }, [pageBackground]);

  const backgroundImage = useMemo(() => {
    if (backgroundType === 'image') {
      return pageBackground;
    }
    return null;
  }, [backgroundType, pageBackground]);

  const handleColorChange = (path: string, value: string) => {
    const oldValue = resolveToken(tokens, path);
    onTokenChange(path, value, oldValue);
  };

  const handleFontChange = (path: string, value: string) => {
    const oldValue = resolveToken(tokens, path);
    onTokenChange(path, value, oldValue);
  };

  return (
    <div className={styles.toolbar}>
      {/* Heading Row */}
      <div className={styles.toolbarRow}>
        <div className={styles.toolbarGroup}>
          <label className={styles.toolbarLabel}>
            <TextT size={16} weight="bold" />
            <span>Heading</span>
          </label>
          <ColorButton
            label="Heading Color"
            value={pageHeadingText}
            onChange={(value) => handleColorChange('semantic.text.primary', value)}
          />
          <select 
            className={styles.toolbarSelect}
            value={headingFont}
            onChange={(e) => handleFontChange('core.typography.font.heading', e.target.value)}
          >
            {availableFonts.map(font => (
              <option key={font} value={font}>{font}</option>
            ))}
          </select>
        </div>
      </div>

      <div className={styles.toolbarDivider} />

      {/* Body Row */}
      <div className={styles.toolbarRow}>
        <div className={styles.toolbarGroup}>
          <label className={styles.toolbarLabel}>
            <TextT size={16} />
            <span>Body</span>
          </label>
          <ColorButton
            label="Body Color"
            value={pageBodyText}
            onChange={(value) => handleColorChange('semantic.text.secondary', value)}
          />
          <select 
            className={styles.toolbarSelect}
            value={bodyFont}
            onChange={(e) => handleFontChange('core.typography.font.body', e.target.value)}
          >
            {availableFonts.map(font => (
              <option key={font} value={font}>{font}</option>
            ))}
          </select>
        </div>
      </div>

      <div className={styles.toolbarDivider} />

      {/* Formatting and Alignment Row */}
      <div className={styles.toolbarRow}>
        {/* Formatting Buttons */}
        <div className={styles.toolbarGroup}>
          <button
            type="button"
            className={styles.toolbarButton}
            title="Bold"
            aria-label="Bold"
          >
            <TextB size={18} weight="bold" />
          </button>
          <button
            type="button"
            className={styles.toolbarButton}
            title="Italic"
            aria-label="Italic"
          >
            <TextItalic size={18} weight="bold" />
          </button>
          <button
            type="button"
            className={styles.toolbarButton}
            title="Underline"
            aria-label="Underline"
          >
            <TextUnderline size={18} weight="bold" />
          </button>
        </div>

        {/* Alignment Buttons */}
        <div className={styles.toolbarGroup}>
          <button
            type="button"
            className={styles.toolbarButton}
            title="Align Left"
            aria-label="Align Left"
          >
            <TextAlignLeft size={18} weight="bold" />
          </button>
          <button
            type="button"
            className={`${styles.toolbarButton} ${styles.toolbarButtonActive}`}
            title="Align Center"
            aria-label="Align Center"
          >
            <TextAlignCenter size={18} weight="bold" />
          </button>
          <button
            type="button"
            className={styles.toolbarButton}
            title="Align Right"
            aria-label="Align Right"
          >
            <TextAlignRight size={18} weight="bold" />
          </button>
        </div>
      </div>

      <div className={styles.toolbarDivider} />

      {/* Background Button Row */}
      <div className={styles.toolbarRow}>
        <div className={styles.toolbarGroup}>
          <BackgroundButton
            label="Page Background"
            value={pageBackground}
            backgroundType={backgroundType}
            backgroundImage={backgroundImage}
            onValueChange={(value) => handleColorChange('semantic.surface.canvas', value)}
            onTypeChange={(type) => {
              // When type changes, update the value accordingly
              if (type === 'solid') {
                handleColorChange('semantic.surface.canvas', '#FFFFFF');
              } else if (type === 'gradient') {
                // Keep existing gradient or set default
                if (!pageBackground.includes('gradient')) {
                  handleColorChange('semantic.surface.canvas', 'linear-gradient(140deg, #02040d 0%, #0a1331 45%, #1a2151 100%)');
                }
              }
              // For image type, user will provide URL via onImageChange
            }}
            onImageChange={(url) => {
              if (url) {
                handleColorChange('semantic.surface.canvas', url);
              }
            }}
          />
        </div>
      </div>
    </div>
  );
}

