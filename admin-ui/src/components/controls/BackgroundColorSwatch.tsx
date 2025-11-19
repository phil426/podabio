import { useState, useEffect } from 'react';
import * as Popover from '@radix-ui/react-popover';
import * as Tabs from '@radix-ui/react-tabs';
import { Square, Sparkle, Image as ImageIcon } from '@phosphor-icons/react';
import { PageBackgroundPicker } from './PageBackgroundPicker';
import styles from './background-color-swatch.module.css';

interface BackgroundColorSwatchProps {
  value: string;
  backgroundType: 'solid' | 'gradient' | 'image';
  backgroundImage?: string | null;
  onChange: (value: string) => void;
  onTypeChange: (type: 'solid' | 'gradient' | 'image') => void;
  onImageChange?: (url: string) => void;
  label: string;
}

export function BackgroundColorSwatch({ 
  value, 
  backgroundType,
  backgroundImage,
  onChange, 
  onTypeChange,
  onImageChange,
  label
}: BackgroundColorSwatchProps): JSX.Element {
  const [open, setOpen] = useState(false);
  const [pickerKey, setPickerKey] = useState(0);
  
  // Force picker to re-initialize when popover opens
  useEffect(() => {
    if (open) {
      setPickerKey(prev => prev + 1);
    }
  }, [open, value]);

  const getDisplayValue = (): string => {
    if (!value || typeof value !== 'string') {
      return '#FFFFFF';
    }
    
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
  const isGradient = backgroundType === 'gradient' || (value && typeof value === 'string' && value.includes('gradient'));
  const isImage = backgroundType === 'image' && backgroundImage;

  return (
    <Popover.Root open={open} onOpenChange={setOpen}>
      <Popover.Trigger asChild>
        <button
          type="button"
          className={styles.swatchButton}
          title={label}
          aria-label={label}
        >
          <div 
            className={styles.swatch}
            style={{ 
              backgroundColor: isImage ? 'transparent' : (isGradient ? 'transparent' : displayValue),
              backgroundImage: isGradient || isImage ? (isImage ? `url(${displayValue})` : displayValue) : undefined,
              backgroundSize: isImage ? 'cover' : (isGradient ? '100% 100%' : undefined),
              backgroundPosition: isImage ? 'center' : (isGradient ? 'center' : undefined),
              backgroundRepeat: isGradient || isImage ? 'no-repeat' : undefined
            }}
          />
        </button>
      </Popover.Trigger>
      <Popover.Portal>
        <Popover.Content
          className={styles.backgroundPopover}
          sideOffset={5}
          align="end"
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
                        if (file) {
                          const reader = new FileReader();
                          reader.onload = (event) => {
                            const dataUrl = event.target?.result as string;
                            onImageChange?.(dataUrl);
                          };
                          reader.readAsDataURL(file);
                        }
                      }}
                      className={styles.fileInput}
                    />
                  </div>
                ) : (
                  <PageBackgroundPicker
                    key={pickerKey}
                    value={value}
                    onChange={onChange}
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


