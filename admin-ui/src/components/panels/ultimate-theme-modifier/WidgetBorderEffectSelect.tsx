import * as Select from '@radix-ui/react-select';
import { CaretDown, Check } from '@phosphor-icons/react';
import styles from './widget-border-effect-select.module.css';

interface WidgetBorderEffectSelectProps {
  value: 'none' | 'shadow' | 'glow';
  onChange: (value: 'none' | 'shadow' | 'glow') => void;
  disabled?: boolean;
}

export function WidgetBorderEffectSelect({ value, onChange, disabled = false }: WidgetBorderEffectSelectProps): JSX.Element {
  return (
    <Select.Root value={value} onValueChange={(val) => onChange(val as 'none' | 'shadow' | 'glow')} disabled={disabled}>
      <Select.Trigger className={styles.trigger} aria-label="Select border effect">
        <Select.Value />
        <Select.Icon className={styles.icon}>
          <CaretDown weight="regular" />
        </Select.Icon>
      </Select.Trigger>
      <Select.Portal>
        <Select.Content className={styles.content} position="popper" sideOffset={4}>
          <Select.Viewport className={styles.viewport}>
            <Select.Item value="none" className={styles.item}>
              <Select.ItemText>None</Select.ItemText>
              <Select.ItemIndicator className={styles.indicator}>
                <Check weight="bold" />
              </Select.ItemIndicator>
            </Select.Item>
            <Select.Item value="shadow" className={styles.item}>
              <Select.ItemText>Shadow</Select.ItemText>
              <Select.ItemIndicator className={styles.indicator}>
                <Check weight="bold" />
              </Select.ItemIndicator>
            </Select.Item>
            <Select.Item value="glow" className={styles.item}>
              <Select.ItemText>Glow</Select.ItemText>
              <Select.ItemIndicator className={styles.indicator}>
                <Check weight="bold" />
              </Select.ItemIndicator>
            </Select.Item>
          </Select.Viewport>
        </Select.Content>
      </Select.Portal>
    </Select.Root>
  );
}

