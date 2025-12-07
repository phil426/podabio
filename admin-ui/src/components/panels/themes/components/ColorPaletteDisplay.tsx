import { useRef, useState, type DragEvent, KeyboardEvent } from 'react';
import { CircleNotch, Shuffle, Info, Copy, Check } from '@phosphor-icons/react';
import * as Tooltip from '@radix-ui/react-tooltip';
import { EmptyState } from './EmptyState';
import { copyToClipboard, hexToRgb, hexToHsl } from '../../../../utils/clipboard';
import { trackTelemetry } from '../../../../services/telemetry';
import styles from '../podcast-theme-generator.module.css';

export interface ColorPaletteDisplayProps {
  colors: string[];
  draggedIndex: number | null;
  dragOverIndex: number | null;
  isExtracting: boolean;
  isShuffling: boolean;
  getColorRole: (index: number) => string;
  onDragStart: (index: number) => void;
  onDragOver: (event: DragEvent, index: number) => void;
  onDragLeave: () => void;
  onDrop: (event: DragEvent, index: number) => void;
  onDragEnd: () => void;
  onShuffle: () => void;
  onKeyDown?: (event: KeyboardEvent, index: number) => void;
}

export function ColorPaletteDisplay({
  colors,
  draggedIndex,
  dragOverIndex,
  isExtracting,
  isShuffling,
  getColorRole,
  onDragStart,
  onDragOver,
  onDragLeave,
  onDrop,
  onDragEnd,
  onShuffle,
  onKeyDown
}: ColorPaletteDisplayProps): JSX.Element {
  const [copiedIndex, setCopiedIndex] = useState<number | null>(null);
  const touchDragTimeoutRef = useRef<number | null>(null);

  const handleCopyColor = async (color: string, index: number, format: 'hex' | 'rgb' | 'hsl' = 'hex') => {
    let textToCopy = color;
    
    if (format === 'rgb') {
      const rgb = hexToRgb(color);
      textToCopy = rgb || color;
    } else if (format === 'hsl') {
      const hsl = hexToHsl(color);
      textToCopy = hsl || color;
    }

    const result = await copyToClipboard(textToCopy);
    if (result.success) {
      setCopiedIndex(index);
      setTimeout(() => setCopiedIndex(null), 2000);
      trackTelemetry({
        event: 'theme_wizard.color_copied',
        metadata: { format, success: true }
      });
    } else {
      trackTelemetry({
        event: 'theme_wizard.color_copied',
        metadata: { format, success: false, error: result.error || 'unknown' }
      });
    }
  };

  return (
    <section className={styles.card} aria-busy={isExtracting}>
      <div className={styles.cardHeader}>
        <h3 className={styles.cardTitle}>Color Palette</h3>
        {colors.length === 0 && isExtracting && (
          <div className={styles.extracting}>
            <CircleNotch className={styles.spinner} aria-hidden="true" size={16} weight="regular" />
            Extracting colors...
          </div>
        )}
        {colors.length >= 5 && (
          <div className={styles.infoTextContainer}>
            <p className={styles.infoText}>
              5 colors extracted. Drag to reorder, use arrow keys when focused, or shuffle to rearrange.
            </p>
            <Tooltip.Provider delayDuration={300}>
              <Tooltip.Root>
                <Tooltip.Trigger asChild>
                  <button
                    type="button"
                    className={styles.infoButton}
                    aria-label="Color role information"
                  >
                    <Info aria-hidden="true" size={16} weight="regular" />
                  </button>
                </Tooltip.Trigger>
                <Tooltip.Portal>
                  <Tooltip.Content
                    side="bottom"
                    align="end"
                    className={styles.tooltip}
                  >
                    <div className={styles.tooltipContent}>
                      <strong>Color Roles:</strong>
                      <ul className={styles.tooltipList}>
                        <li>1. Background Gradient Start</li>
                        <li>2. Background Gradient End</li>
                        <li>3. Page Title & Widget Background</li>
                        <li>4. Body Text & Widget Text</li>
                        <li>5. Accents & Borders</li>
                      </ul>
                    </div>
                    <Tooltip.Arrow className={styles.tooltipArrow} />
                  </Tooltip.Content>
                </Tooltip.Portal>
              </Tooltip.Root>
            </Tooltip.Provider>
          </div>
        )}
      </div>

      {isExtracting && (
        <div className={styles.loading} role="status" aria-live="polite" aria-busy="true">
          <CircleNotch className={styles.spinner} aria-hidden="true" size={20} weight="regular" />
          <p>Extracting colors from cover image...</p>
        </div>
      )}

      {colors.length === 0 && !isExtracting && (
        <EmptyState type="no-colors" />
      )}

      {colors.length > 0 && (
        <div className={styles.cardContent}>
          <div
            className={styles.colorSwatches}
            role="list"
            aria-label="Color palette"
            aria-live="polite"
            aria-atomic="false"
          >
            {colors.map((color, index) => {
              const role = getColorRole(index);
              const isCopied = copiedIndex === index;
              return (
                <div
                  key={`${color}-${index}`}
                  className={`${styles.swatch} ${draggedIndex === index ? styles.dragging : ''} ${
                    dragOverIndex === index ? styles.dragOver : ''
                  }`}
                  style={{ backgroundColor: color }}
                  role="listitem"
                  tabIndex={0}
                  aria-label={`${role} color: ${color}. Position ${index + 1} of ${colors.length}. Press arrow keys to reorder.`}
                  title={`${role}: ${color}`}
                  draggable
                  onDragStart={() => {
                    trackTelemetry({
                      event: 'theme_wizard.color_drag_start',
                      metadata: { index }
                    });
                    onDragStart(index);
                  }}
                  onDragOver={(event) => onDragOver(event, index)}
                  onDragLeave={onDragLeave}
                  onDrop={(event) => {
                    trackTelemetry({
                      event: 'theme_wizard.color_drop',
                      metadata: { index }
                    });
                    onDrop(event, index);
                  }}
                  onDragEnd={onDragEnd}
                  onKeyDown={onKeyDown ? (event) => onKeyDown(event, index) : undefined}
                  onTouchStart={(e) => {
                    // Touch support: start drag after a short hold to reduce accidental drags
                    e.preventDefault();
                    if (touchDragTimeoutRef.current) {
                      window.clearTimeout(touchDragTimeoutRef.current);
                    }
                    touchDragTimeoutRef.current = window.setTimeout(() => {
                      onDragStart(index);
                    }, 120);
                  }}
                  onTouchMove={(e) => {
                    // Touch support: handle drag over
                    e.preventDefault();
                    const touch = e.touches[0];
                    const element = document.elementFromPoint(touch.clientX, touch.clientY);
                    if (element) {
                      const swatchElement = element.closest(`.${styles.swatch}`);
                      if (swatchElement) {
                        const swatchIndex = Array.from(swatchElement.parentElement?.children || []).indexOf(swatchElement);
                        if (swatchIndex !== -1 && swatchIndex !== index) {
                          onDragOver({ preventDefault: () => {}, dataTransfer: { dropEffect: 'move' }, isTouchEvent: true } as unknown as React.DragEvent, swatchIndex);
                        }
                      }
                    }
                  }}
                  onTouchEnd={(e) => {
                    // Touch support: handle drop
                    e.preventDefault();
                    if (touchDragTimeoutRef.current) {
                      window.clearTimeout(touchDragTimeoutRef.current);
                      touchDragTimeoutRef.current = null;
                    }
                    const touch = e.changedTouches[0];
                    const element = document.elementFromPoint(touch.clientX, touch.clientY);
                    if (element) {
                      const swatchElement = element.closest(`.${styles.swatch}`);
                      if (swatchElement) {
                        const swatchIndex = Array.from(swatchElement.parentElement?.children || []).indexOf(swatchElement);
                        if (swatchIndex !== -1) {
                          trackTelemetry({
                            event: 'theme_wizard.color_drop_touch',
                            metadata: { index: swatchIndex }
                          });
                          onDrop({ preventDefault: () => {}, isTouchEvent: true } as unknown as React.DragEvent, swatchIndex);
                        }
                      }
                    }
                    onDragEnd();
                  }}
                >
                  <span className={styles.swatchLabel}>{index + 1}</span>
                  <div className={styles.swatchRole}>{role}</div>
                  <div className={styles.swatchColorCode}>{color}</div>
                  <div className={styles.swatchActions}>
                    <Tooltip.Provider delayDuration={200}>
                      <Tooltip.Root>
                        <Tooltip.Trigger asChild>
                          <button
                            type="button"
                            className={styles.copyButton}
                            onClick={(e) => {
                              e.stopPropagation();
                              handleCopyColor(color, index, 'hex');
                            }}
                            aria-label={`Copy ${color} as hex`}
                            title="Copy hex color"
                          >
                            {isCopied ? (
                              <Check aria-hidden="true" size={14} weight="regular" />
                            ) : (
                              <Copy aria-hidden="true" size={14} weight="regular" />
                            )}
                          </button>
                        </Tooltip.Trigger>
                        <Tooltip.Portal>
                          <Tooltip.Content
                            side="top"
                            align="center"
                            className={styles.tooltip}
                          >
                            {isCopied ? 'Copied!' : 'Copy hex color'}
                            <Tooltip.Arrow className={styles.tooltipArrow} />
                          </Tooltip.Content>
                        </Tooltip.Portal>
                      </Tooltip.Root>
                    </Tooltip.Provider>
                    <div className={styles.copyFormatGroup} aria-label="Copy color in alternate formats">
                      <button
                        type="button"
                        className={styles.copyFormatButton}
                        onClick={(e) => {
                          e.stopPropagation();
                          handleCopyColor(color, index, 'rgb');
                        }}
                        aria-label={`Copy ${color} as RGB`}
                        title="Copy RGB color"
                      >
                        RGB
                      </button>
                      <button
                        type="button"
                        className={styles.copyFormatButton}
                        onClick={(e) => {
                          e.stopPropagation();
                          handleCopyColor(color, index, 'hsl');
                        }}
                        aria-label={`Copy ${color} as HSL`}
                        title="Copy HSL color"
                      >
                        HSL
                      </button>
                    </div>
                  </div>
                </div>
              );
            })}
          </div>
          <div className={styles.colorActions}>
            <button
              type="button"
              className={styles.shuffleButton}
              onClick={onShuffle}
              disabled={isShuffling}
              aria-label="Shuffle color order"
              aria-busy={isShuffling}
            >
              {isShuffling ? (
                <>
                  <CircleNotch className={styles.spinner} aria-hidden="true" size={16} weight="regular" />
                  Shuffling...
                </>
              ) : (
                <>
                  <Shuffle aria-hidden="true" size={16} weight="regular" />
                  Shuffle Colors
                </>
              )}
            </button>
          </div>
        </div>
      )}
    </section>
  );
}

