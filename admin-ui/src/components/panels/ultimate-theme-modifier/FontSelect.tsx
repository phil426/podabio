import * as Select from '@radix-ui/react-select';
import { CaretDown, Check } from '@phosphor-icons/react';
import { FONT_CATEGORIES } from './fonts';
import styles from './font-select.module.css';

interface FontSelectProps {
  value: string;
  options?: string[]; // Optional for backward compatibility
  onChange: (value: string) => void;
  disabled?: boolean;
  categorized?: boolean; // Whether to show categories
}

export function FontSelect({ 
  value, 
  options, 
  onChange, 
  disabled = false,
  categorized = true 
}: FontSelectProps): JSX.Element {
  // Use categorized fonts if options not provided, otherwise use provided options
  const useCategorized = categorized && options === undefined;
  
  return (
    <Select.Root value={value} onValueChange={onChange} disabled={disabled}>
      <Select.Trigger className={styles.trigger} aria-label="Select font">
        <Select.Value />
        <Select.Icon className={styles.icon}>
          <CaretDown weight="regular" />
        </Select.Icon>
      </Select.Trigger>
      <Select.Portal>
        <Select.Content className={styles.content} position="popper" sideOffset={4}>
          <Select.Viewport className={styles.viewport}>
            {useCategorized ? (
              // Render categorized fonts
              FONT_CATEGORIES.map((category) => (
                <div key={category.label}>
                  <Select.Group>
                    <Select.Label className={styles.label}>
                      {category.label}
                    </Select.Label>
                    {category.fonts.map((font) => (
                      <Select.Item key={font} value={font} className={styles.item}>
                        <Select.ItemText>{font}</Select.ItemText>
                        <Select.ItemIndicator className={styles.indicator}>
                          <Check weight="bold" />
                        </Select.ItemIndicator>
                      </Select.Item>
                    ))}
                  </Select.Group>
                </div>
              ))
            ) : (
              // Render flat list for backward compatibility
              options?.map((font) => (
                <Select.Item key={font} value={font} className={styles.item}>
                  <Select.ItemText>{font}</Select.ItemText>
                  <Select.ItemIndicator className={styles.indicator}>
                    <Check weight="bold" />
                  </Select.ItemIndicator>
                </Select.Item>
              ))
            )}
          </Select.Viewport>
        </Select.Content>
      </Select.Portal>
    </Select.Root>
  );
}

