import React from 'react';
import styles from './segmented-control.module.css';

export interface SegmentedControlOption<T extends string> {
    value: T;
    label: React.ReactNode;
    icon?: React.ReactNode;
    disabled?: boolean;
}

interface SegmentedControlProps<T extends string> {
    options: SegmentedControlOption<T>[];
    value: T;
    onChange: (value: T) => void;
    className?: string;
    'aria-label'?: string;
}

export function SegmentedControl<T extends string>({
    options,
    value,
    onChange,
    className = '',
    'aria-label': ariaLabel,
}: SegmentedControlProps<T>): JSX.Element {
    return (
        <div
            className={`${styles.container} ${className}`}
            role="tablist"
            aria-label={ariaLabel}
        >
            {options.map((option) => {
                const isActive = option.value === value;
                return (
                    <button
                        key={option.value}
                        type="button"
                        role="tab"
                        aria-selected={isActive}
                        aria-disabled={option.disabled}
                        disabled={option.disabled}
                        className={`${styles.tab} ${isActive ? styles.active : ''}`}
                        onClick={() => !option.disabled && onChange(option.value)}
                    >
                        {option.icon}
                        <span>{option.label}</span>
                    </button>
                );
            })}
        </div>
    );
}
