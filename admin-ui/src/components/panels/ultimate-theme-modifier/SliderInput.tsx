import { useState, useEffect, useCallback } from 'react';
import * as Slider from '@radix-ui/react-slider';
import styles from './slider-input.module.css';

interface SliderInputProps {
  value: number;
  min?: number;
  max?: number;
  step?: number;
  unit?: string;
  units?: string[];
  onChange: (value: number) => void;
  disabled?: boolean;
  label?: string;
}

export function SliderInput({
  value,
  min = 0,
  max = 100,
  step = 1,
  unit = '',
  units = [],
  onChange,
  disabled = false,
  label
}: SliderInputProps): JSX.Element {
  const [inputValue, setInputValue] = useState(String(value));
  const [selectedUnit, setSelectedUnit] = useState(unit || (units.length > 0 ? units[0] : ''));

  useEffect(() => {
    setInputValue(String(value));
  }, [value]);

  const handleSliderChange = useCallback(
    (newValue: number[]) => {
      onChange(newValue[0]);
    },
    [onChange]
  );

  const handleInputChange = useCallback(
    (e: React.ChangeEvent<HTMLInputElement>) => {
      const rawValue = e.target.value;
      setInputValue(rawValue);
      const numValue = parseFloat(rawValue);
      if (!isNaN(numValue) && numValue >= min && numValue <= max) {
        onChange(numValue);
      }
    },
    [onChange, min, max]
  );

  const handleBlur = useCallback(() => {
    const numValue = parseFloat(inputValue);
    if (isNaN(numValue)) {
      setInputValue(String(value));
    } else if (numValue < min) {
      setInputValue(String(min));
      onChange(min);
    } else if (numValue > max) {
      setInputValue(String(max));
      onChange(max);
    }
  }, [inputValue, value, min, max, onChange]);

  return (
    <div className={styles.container}>
      {label && <label className={styles.label}>{label}</label>}
      <Slider.Root
        className={styles.sliderRoot}
        value={[value]}
        onValueChange={handleSliderChange}
        min={min}
        max={max}
        step={step}
        disabled={disabled}
      >
        <Slider.Track className={styles.sliderTrack}>
          <Slider.Range className={styles.sliderRange} />
        </Slider.Track>
        <Slider.Thumb className={styles.sliderThumb} aria-label="Value" />
      </Slider.Root>
      <div className={styles.inputWrapper}>
        <input
          type="number"
          min={min}
          max={max}
          step={step}
          value={inputValue}
          onChange={handleInputChange}
          onBlur={handleBlur}
          disabled={disabled}
          className={styles.input}
          aria-label="Value input"
        />
        {units.length > 0 ? (
          <select
            value={selectedUnit}
            onChange={(e) => setSelectedUnit(e.target.value)}
            disabled={disabled}
            className={styles.unitSelect}
          >
            {units.map((u) => (
              <option key={u} value={u}>
                {u}
              </option>
            ))}
          </select>
        ) : unit ? (
          <span className={styles.unit}>{unit}</span>
        ) : null}
      </div>
    </div>
  );
}

