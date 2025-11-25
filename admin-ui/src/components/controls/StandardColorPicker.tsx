/**
 * Standard Color Picker
 * Uses the same picker as Page Background (PageBackgroundPicker) for consistency
 */

import { useState, useEffect } from 'react';
import * as Popover from '@radix-ui/react-popover';
import { Drop, Sparkle } from '@phosphor-icons/react';
import { PageBackgroundPicker } from './PageBackgroundPicker';
import styles from './background-color-swatch.module.css';

interface StandardColorPickerProps {
  label: string;
  value?: string;
  onChange?: (value: string) => void;
  hideModeToggle?: boolean;
  hideToken?: boolean;
  hideWrapper?: boolean;
}

function isGradient(value: string): boolean {
  return value.includes('gradient') || value.includes('linear-gradient') || value.includes('radial-gradient');
}

export function StandardColorPicker({
  label,
  value = '#2563EB',
  onChange,
  hideModeToggle = false,
  hideToken = false,
  hideWrapper = false
}: StandardColorPickerProps): JSX.Element {
  const [open, setOpen] = useState(false);
  const [pickerKey, setPickerKey] = useState(0);
  const [backgroundType, setBackgroundType] = useState<'solid' | 'gradient'>(() => {
    return value && isGradient(value) ? 'gradient' : 'solid';
  });
  
  // Update background type when value changes
  useEffect(() => {
    if (value && isGradient(value)) {
      setBackgroundType('gradient');
    } else {
      setBackgroundType('solid');
    }
  }, [value]);
  
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
  const isGradientValue = backgroundType === 'gradient' || (value && typeof value === 'string' && value.includes('gradient'));

  const handleTypeChange = (type: 'solid' | 'gradient') => {
    setBackgroundType(type);
    if (type === 'solid') {
      // Extract color from gradient if needed
      if (isGradientValue && value) {
        const hexMatch = value.match(/#[0-9a-fA-F]{6}/i);
        const solidColor = hexMatch ? hexMatch[0] : '#FFFFFF';
        onChange?.(solidColor);
      } else if (value && !value.startsWith('#')) {
        onChange?.('#FFFFFF');
      }
    } else if (type === 'gradient') {
      // Create default gradient if switching from solid
      if (!isGradientValue) {
        onChange?.('linear-gradient(135deg, #2563EB 0%, #7C3AED 100%)');
      }
    }
  };

  const wrapperClass = hideWrapper ? '' : styles.wrapper;

  return (
    <div className={wrapperClass}>
      <header className={styles.header}>
        <div className={styles.headerText}>
          <p className={styles.label}>{label}</p>
        </div>
        <div className={styles.headerActions}>
          {!hideModeToggle && (
            <div className={styles.modeToggle}>
              <button
                type="button"
                className={`${styles.modeButton} ${backgroundType === 'solid' ? styles.modeButtonActive : ''}`}
                onClick={() => handleTypeChange('solid')}
                aria-label="Solid color mode"
                title="Solid color"
              >
                <Drop aria-hidden="true" size={16} weight="regular" />
                <span>Solid</span>
              </button>
              <button
                type="button"
                className={`${styles.modeButton} ${backgroundType === 'gradient' ? styles.modeButtonActive : ''}`}
                onClick={() => handleTypeChange('gradient')}
                aria-label="Gradient mode"
                title="Gradient"
              >
                <Sparkle aria-hidden="true" size={16} weight="regular" />
                <span>Gradient</span>
              </button>
            </div>
          )}
          <Popover.Root open={open} onOpenChange={setOpen}>
            <Popover.Trigger asChild>
              <button
                type="button"
                className={`${styles.swatchButton} ${isGradientValue ? styles.swatchButtonGradient : ''}`}
                style={isGradientValue
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
                    onChange={(newValue) => {
                      onChange?.(newValue);
                      // Update background type based on new value
                      if (isGradient(newValue)) {
                        setBackgroundType('gradient');
                      } else {
                        setBackgroundType('solid');
                      }
                    }}
                    mode={backgroundType}
                    hidePresets={false}
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

