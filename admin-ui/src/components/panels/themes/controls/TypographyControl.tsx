import React from 'react';
import { BackgroundColorSwatch } from '../../../controls/BackgroundColorSwatch';
import { FontSelect } from '../../ultimate-theme-modifier/FontSelect';
import { SliderInput } from '../../ultimate-theme-modifier/SliderInput';
import styles from './typography-control.module.css';

import { TextAlignLeft, TextAlignCenter, TextAlignRight, TextB, TextItalic } from '@phosphor-icons/react';

export interface TypographyValue {
    font: string;
    size: number;
    spacing: number;
    color: string;
    weight: { bold: boolean; italic: boolean };
    alignment?: 'left' | 'center' | 'right';
    borderColor: string;
    borderWidth: number;
}

interface TypographyControlProps {
    value: TypographyValue;
    onChange: (updates: Partial<TypographyValue>) => void;
    palette?: string[];
}

export function TypographyControl({
    value,
    onChange,
    palette
}: TypographyControlProps) {
    // Destructure for easier access
    const {
        font,
        size,
        spacing,
        color,
        weight,
        alignment = 'center',
        borderColor,
        borderWidth
    } = value;

    const updateWeight = (key: 'bold' | 'italic') => {
        onChange({
            weight: {
                ...weight,
                [key]: !weight[key]
            }
        });
    };

    return (
        <div className={styles.container}>
            {/* Font Select */}
            <div className={styles.fieldGroup}>
                <label className={styles.label}>Font</label>
                <FontSelect
                    value={font}
                    onChange={(newFont) => onChange({ font: newFont })}
                />
            </div>

            {/* Size & Spacing Row */}
            <div className={styles.controlRow}>
                <div className={styles.fieldGroup}>
                    <label className={styles.label}>Size</label>
                    <SliderInput
                        value={size}
                        min={12}
                        max={96}
                        step={1}
                        unit="px"
                        onChange={(newSize) => onChange({ size: newSize })}
                    />
                </div>
                <div className={styles.fieldGroup}>
                    <label className={styles.label}>Spacing</label>
                    <SliderInput
                        value={spacing}
                        min={0.8}
                        max={3.0}
                        step={0.1}
                        onChange={(newSpacing) => onChange({ spacing: newSpacing })}
                    />
                </div>
            </div>

            {/* Color & Style/Align Row */}
            <div className={styles.controlRow}>
                <div className={styles.fieldGroup}>
                    <label className={styles.label}>Color</label>
                    <BackgroundColorSwatch
                        value={color}
                        onChange={(newColor) => onChange({ color: newColor })}
                        label="Text Color"
                        solidOnly={false} /* Allow gradients */
                        palette={palette}
                    />
                </div>
                <div className={styles.fieldGroup}>
                    <label className={styles.label}>Style & Align</label>
                    <div className={styles.toggleGroup}>
                        <button
                            type="button"
                            className={`${styles.toggleButton} ${weight.bold ? styles.active : ''}`}
                            onClick={() => updateWeight('bold')}
                            title="Bold"
                        >
                            <TextB size={16} />
                        </button>
                        <button
                            type="button"
                            className={`${styles.toggleButton} ${weight.italic ? styles.active : ''}`}
                            onClick={() => updateWeight('italic')}
                            title="Italic"
                        >
                            <TextItalic size={16} />
                        </button>

                        <div style={{ width: 1, background: 'rgba(255,255,255,0.1)', margin: '0 4px' }} />

                        <button
                            type="button"
                            className={`${styles.toggleButton} ${alignment === 'left' ? styles.active : ''}`}
                            onClick={() => onChange({ alignment: 'left' })}
                            title="Align Left"
                        >
                            <TextAlignLeft size={16} />
                        </button>
                        <button
                            type="button"
                            className={`${styles.toggleButton} ${alignment === 'center' ? styles.active : ''}`}
                            onClick={() => onChange({ alignment: 'center' })}
                            title="Align Center"
                        >
                            <TextAlignCenter size={16} />
                        </button>
                        <button
                            type="button"
                            className={`${styles.toggleButton} ${alignment === 'right' ? styles.active : ''}`}
                            onClick={() => onChange({ alignment: 'right' })}
                            title="Align Right"
                        >
                            <TextAlignRight size={16} />
                        </button>
                    </div>
                </div>
            </div>

            {/* Border Controls Row */}
            <div className={styles.controlRow}>
                <div className={styles.fieldGroup}>
                    <label className={styles.label}>Border Color</label>
                    <BackgroundColorSwatch
                        value={borderColor}
                        onChange={(newColor) => onChange({ borderColor: newColor })}
                        label="Border Color"
                        palette={palette}
                    />
                </div>
                <div className={styles.fieldGroup}>
                    <label className={styles.label}>Border Width</label>
                    <SliderInput
                        value={borderWidth}
                        min={0}
                        max={10}
                        step={0.5}
                        unit="px"
                        onChange={(newWidth) => onChange({ borderWidth: newWidth })}
                    />
                </div>
            </div>
        </div>
    );
}
