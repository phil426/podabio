import * as Select from '@radix-ui/react-select';
import { CaretDown, Check } from '@phosphor-icons/react';
import styles from './special-text-select.module.css';

interface SpecialTextSelectProps {
  value: string;
  options: string[];
  onChange: (value: string) => void;
  disabled?: boolean;
}

export function SpecialTextSelect({ value, options, onChange, disabled = false }: SpecialTextSelectProps): JSX.Element {
  return (
    <Select.Root value={value} onValueChange={onChange} disabled={disabled}>
      <Select.Trigger className={styles.trigger} aria-label="Select special text">
        <Select.Value />
        <Select.Icon className={styles.icon}>
          <CaretDown weight="regular" />
        </Select.Icon>
      </Select.Trigger>
      <Select.Portal>
        <Select.Content className={styles.content} position="popper" sideOffset={4}>
          <Select.Viewport className={styles.viewport}>
            {options.map((option) => (
              <Select.Item key={option} value={option} className={styles.item}>
                <Select.ItemText>{option}</Select.ItemText>
                <Select.ItemIndicator className={styles.indicator}>
                  <Check weight="bold" />
                </Select.ItemIndicator>
              </Select.Item>
            ))}
          </Select.Viewport>
        </Select.Content>
      </Select.Portal>
    </Select.Root>
  );
}

