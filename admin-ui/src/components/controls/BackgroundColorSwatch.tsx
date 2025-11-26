import { useState, useEffect } from 'react';
import * as Popover from '@radix-ui/react-popover';
import { PodaColorPicker } from './PodaColorPicker';
import styles from './background-color-swatch.module.css';

interface BackgroundColorSwatchProps {
  value: string;
  backgroundType?: 'solid' | 'gradient'; // Optional - PodaColorPicker handles mode internally
  onChange: (value: string) => void;
  onTypeChange?: (type: 'solid' | 'gradient') => void; // Optional - kept for backward compatibility
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

  const getDisplayValue = (): string => {
    if (!value || typeof value !== 'string') {
      return '#FFFFFF';
    }
      return value;
  };

  const displayValue = getDisplayValue();
  const isGradient = value && typeof value === 'string' && value.includes('gradient');

  return (
    <div className={styles.wrapper}>
      <header className={styles.header}>
        <div className={styles.headerText}>
          <p className={styles.label}>{label}</p>
        </div>
        <div className={styles.headerActions}>
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
                  <PodaColorPicker
                    value={value}
                    onChange={onChange}
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


