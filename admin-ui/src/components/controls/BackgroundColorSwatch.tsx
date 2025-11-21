import { useState, useEffect } from 'react';
import * as Popover from '@radix-ui/react-popover';
import { Drop, Sparkle } from '@phosphor-icons/react';
import { PageBackgroundPicker } from './PageBackgroundPicker';
import styles from './background-color-swatch.module.css';

interface BackgroundColorSwatchProps {
  value: string;
  backgroundType: 'solid' | 'gradient';
  onChange: (value: string) => void;
  onTypeChange: (type: 'solid' | 'gradient') => void;
  label: string;
}

export function BackgroundColorSwatch({ 
  value, 
  backgroundType,
  onChange, 
  onTypeChange,
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

  return (
    <div className={styles.wrapper}>
      <header className={styles.header}>
        <div className={styles.headerText}>
          <p className={styles.label}>{label}</p>
        </div>
        <div className={styles.headerActions}>
          <div className={styles.modeToggle}>
            <button
              type="button"
              className={`${styles.modeButton} ${backgroundType === 'solid' ? styles.modeButtonActive : ''}`}
              onClick={() => onTypeChange('solid')}
              aria-label="Solid color mode"
              title="Solid color"
            >
              <Drop aria-hidden="true" size={16} weight="regular" />
              <span>Solid</span>
            </button>
            <button
              type="button"
              className={`${styles.modeButton} ${backgroundType === 'gradient' ? styles.modeButtonActive : ''}`}
              onClick={() => onTypeChange('gradient')}
              aria-label="Gradient mode"
              title="Gradient"
            >
              <Sparkle aria-hidden="true" size={16} weight="regular" />
              <span>Gradient</span>
            </button>
          </div>
          <Popover.Root open={open} onOpenChange={setOpen}>
            <Popover.Trigger asChild>
              <button
                type="button"
                className={`${styles.swatchButton} ${isGradient ? styles.swatchButtonGradient : ''}`}
                style={isGradient
                  ? { backgroundImage: displayValue }
                  : { backgroundColor: displayValue }
                }
                aria-expanded={open}
                aria-label={`${open ? 'Hide' : 'Edit'} ${label}`}
              >
                <span className={styles.swatch} aria-hidden="true" />
              </button>
            </Popover.Trigger>
            <Popover.Portal>
              <Popover.Content
                className={styles.backgroundPopover}
                sideOffset={5}
                align="end"
              >
                <div className={styles.backgroundPopoverContent}>
                  <PageBackgroundPicker
                    key={pickerKey}
                    value={value}
                    onChange={onChange}
                    mode={backgroundType}
                    hidePresets
                    presetsOnly={false}
                  />
                </div>
              </Popover.Content>
            </Popover.Portal>
          </Popover.Root>
        </div>
      </header>
    </div>
  );
}


