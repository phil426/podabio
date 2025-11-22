import { useState } from 'react';
import { CaretDown, CaretUp, Palette } from '@phosphor-icons/react';
import { ColorTokenPicker } from '../controls/ColorTokenPicker';
import type { TabColorTheme } from '../layout/tab-colors';

import styles from './color-palette-editor.module.css';

interface ColorGroup {
  id: string;
  label: string;
  description: string;
  colors: Array<{
    id: string;
    label: string;
    token: string;
    value: string;
    onChange: (value: string) => void;
  }>;
}

interface ColorPaletteEditorProps {
  activeColor: TabColorTheme;
  groups: ColorGroup[];
}

export function ColorPaletteEditor({ activeColor, groups }: ColorPaletteEditorProps): JSX.Element {
  const [expandedGroup, setExpandedGroup] = useState<string | null>(groups[0]?.id ?? null);
  const [activeColorPicker, setActiveColorPicker] = useState<string | null>(null);

  const toggleGroup = (groupId: string) => {
    const newExpanded = expandedGroup === groupId ? null : groupId;
    setExpandedGroup(newExpanded);
    if (!newExpanded) {
      setActiveColorPicker(null);
    }
  };

  const handleColorClick = (colorId: string, groupId: string) => {
    if (expandedGroup !== groupId) {
      setExpandedGroup(groupId);
    }
    setActiveColorPicker((prev) => (prev === colorId ? null : colorId));
  };

  const isGradient = (value: string): boolean => {
    return value.includes('gradient') || value.includes('linear-gradient') || value.includes('radial-gradient');
  };

  return (
    <div
      className={styles.wrapper}
      style={{
        '--active-tab-color': activeColor.text,
        '--active-tab-bg': activeColor.primary,
        '--active-tab-light': activeColor.light,
        '--active-tab-border': activeColor.border
      } as React.CSSProperties}
    >
      {groups.map((group) => {
        const isExpanded = expandedGroup === group.id;

        return (
          <div key={group.id} className={styles.colorGroup}>
            <button
              type="button"
              className={styles.groupHeader}
              onClick={() => toggleGroup(group.id)}
              aria-expanded={isExpanded}
            >
              <div className={styles.groupHeaderLeft}>
                <div className={styles.groupIcon}>
                  <Palette aria-hidden="true" size={16} weight="regular" />
                </div>
                <div className={styles.groupInfo}>
                  <h4 className={styles.groupLabel}>{group.label}</h4>
                  <p className={styles.groupDescription}>{group.description}</p>
                </div>
              </div>
              <div className={styles.groupHeaderRight}>
                <div className={styles.groupSwatches}>
                  {group.colors.map((color) => (
                    <div
                      key={color.id}
                      className={`${styles.swatchPreview} ${isGradient(color.value) ? styles.swatchPreviewGradient : ''}`}
                      style={{ 
                        background: color.value,
                        backgroundImage: isGradient(color.value) ? color.value : undefined
                      }}
                      title={color.label}
                      onClick={(e) => {
                        e.stopPropagation();
                        handleColorClick(color.id, group.id);
                      }}
                    />
                  ))}
                </div>
                {isExpanded ? (
                  <CaretUp className={styles.chevron} aria-hidden="true" size={16} weight="regular" />
                ) : (
                  <CaretDown className={styles.chevron} aria-hidden="true" size={16} weight="regular" />
                )}
              </div>
            </button>

            {isExpanded && (
              <div className={styles.groupContent}>
                <div className={styles.colorGrid}>
                  {group.colors.map((color) => (
                    <div key={color.id} className={styles.colorItem}>
                      <div className={styles.colorItemHeader}>
                        <div className={styles.colorItemInfo}>
                          <span className={styles.colorItemLabel}>{color.label}</span>
                          <span className={styles.colorItemValue}>
                            {isGradient(color.value) ? 'Gradient' : color.value.toUpperCase()}
                          </span>
                        </div>
                        <button
                          type="button"
                          className={`${styles.colorSwatchButton} ${isGradient(color.value) ? styles.colorSwatchButtonGradient : ''}`}
                          style={{ 
                            background: color.value,
                            backgroundImage: isGradient(color.value) ? color.value : undefined
                          }}
                          onClick={() => handleColorClick(color.id, group.id)}
                          aria-expanded={activeColorPicker === color.id}
                          aria-label={`${activeColorPicker === color.id ? 'Close' : 'Edit'} ${color.label} color`}
                        >
                          <span className={styles.colorSwatch} aria-hidden="true" />
                        </button>
                      </div>
                      {activeColorPicker === color.id && (
                        <div className={styles.colorPickerContainer}>
                          <ColorTokenPicker
                            label={color.label}
                            token={color.token}
                            value={color.value}
                            onChange={(value) => {
                              color.onChange(value);
                            }}
                          />
                        </div>
                      )}
                    </div>
                  ))}
                </div>
              </div>
            )}
          </div>
        );
      })}
    </div>
  );
}
