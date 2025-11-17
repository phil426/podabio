import * as Select from '@radix-ui/react-select';
import { CaretDown, Check } from '@phosphor-icons/react';
import styles from './font-select.module.css';

interface FontSelectProps {
  value: string;
  options: string[];
  onChange: (value: string) => void;
  disabled?: boolean;
}

export function FontSelect({ value, options, onChange, disabled = false }: FontSelectProps): JSX.Element {
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
            {options.map((font) => (
              <Select.Item key={font} value={font} className={styles.item}>
                <Select.ItemText>{font}</Select.ItemText>
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

