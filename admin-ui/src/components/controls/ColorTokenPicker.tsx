import { useEffect, useMemo, useState, useCallback, useRef } from 'react';
import { HexColorPicker } from 'react-colorful';
import { LuSparkles, LuDroplet } from 'react-icons/lu';

import styles from './color-token-picker.module.css';

interface ColorTokenPickerProps {
  label: string;
  token: string;
  value?: string;
  palette?: string[];
  recentColors?: string[];
  onChange?: (value: string) => void;
  hideModeToggle?: boolean; // Hide solid/gradient mode toggle
  hideToken?: boolean; // Hide token name display
  autoClose?: boolean; // Auto-close picker when color is selected
  hideWrapper?: boolean; // Hide wrapper styling (padding, border, background)
}

type ColorMode = 'solid' | 'gradient';

interface GradientStop {
  color: string;
  position: number;
}

export function ColorTokenPicker({
  label,
  token,
  value,
  palette = ['#2563EB', '#7C3AED', '#F97316', '#0EA5E9', '#22C55E', '#111827'],
  recentColors = ['#1E293B', '#F1F5F9'],
  onChange,
  hideModeToggle = false,
  hideToken = false,
  autoClose = false,
  hideWrapper = false
}: ColorTokenPickerProps): JSX.Element {
  const [internalValue, setInternalValue] = useState(value ?? '#2563EB');
  const [isExpanded, setIsExpanded] = useState(false);
  const [mode, setMode] = useState<ColorMode>('solid');
  const [gradientDirection, setGradientDirection] = useState(135);
  const [gradientStops, setGradientStops] = useState<GradientStop[]>([
    { color: '#2563EB', position: 0 },
    { color: '#7C3AED', position: 100 }
  ]);
  const [isManualModeChange, setIsManualModeChange] = useState(false);
  const isUpdatingRef = useRef(false);
  const lastValueRef = useRef<string | undefined>(value);

  const currentValue = value ?? internalValue;
  const isGradientValue = isGradient(currentValue);

  useEffect(() => {
    // Only update from external value changes if not manually changing mode and not in the middle of an update
    if (isUpdatingRef.current) return;
    
    if (value && value !== lastValueRef.current && value !== internalValue && !isManualModeChange) {
      lastValueRef.current = value;
      setInternalValue(value);
      if (isGradient(value)) {
        setMode('gradient');
        parseGradient(value);
      } else {
        setMode('solid');
      }
    }
  }, [value, isManualModeChange, internalValue]);

  useEffect(() => {
    // Only auto-switch mode if it wasn't manually changed and there's a mismatch
    // This handles cases where the value changes externally (not from user interaction)
    if (isUpdatingRef.current || isManualModeChange) return;
    
    if (isGradientValue && mode === 'solid') {
      setMode('gradient');
      parseGradient(currentValue);
    } else if (!isGradientValue && mode === 'gradient') {
      setMode('solid');
    }
  }, [currentValue, isManualModeChange, mode, isGradientValue]);

  function isGradient(val: string): boolean {
    return val.includes('gradient') || val.includes('linear-gradient') || val.includes('radial-gradient');
  }

  function parseGradient(gradient: string): void {
    const linearMatch = gradient.match(/linear-gradient\((\d+)deg,\s*(#[0-9a-fA-F]{6})\s*(\d+)%,\s*(#[0-9a-fA-F]{6})\s*(\d+)%\)/i);
    if (linearMatch) {
      const direction = parseInt(linearMatch[1], 10);
      const stop1Color = linearMatch[2];
      const stop1Pos = parseInt(linearMatch[3], 10);
      const stop2Color = linearMatch[4];
      const stop2Pos = parseInt(linearMatch[5], 10);
      setGradientDirection(direction);
      setGradientStops([
        { color: stop1Color, position: stop1Pos },
        { color: stop2Color, position: stop2Pos }
      ]);
    } else {
      // Default gradient if parsing fails
      setGradientDirection(135);
      setGradientStops([
        { color: '#2563EB', position: 0 },
        { color: '#7C3AED', position: 100 }
      ]);
    }
  }

  const buildGradient = useCallback((): string => {
    return `linear-gradient(${gradientDirection}deg, ${gradientStops[0].color} ${gradientStops[0].position}%, ${gradientStops[1].color} ${gradientStops[1].position}%)`;
  }, [gradientDirection, gradientStops]);

  const rgbString = useMemo(() => {
    if (mode === 'gradient') return '';
    return hexToRgb(mode === 'solid' ? currentValue : gradientStops[0].color);
  }, [mode, currentValue, gradientStops]);

  const hslString = useMemo(() => {
    if (mode === 'gradient') return '';
    return hexToHsl(mode === 'solid' ? currentValue : gradientStops[0].color);
  }, [mode, currentValue, gradientStops]);

  const handleChange = (next: string) => {
    if (mode === 'solid') {
      setInternalValue(next);
      onChange?.(next);
      // Don't auto-close here - let palette/recent color clicks handle that
    }
  };

  const handleGradientChange = useCallback(() => {
    if (mode === 'gradient' && !isUpdatingRef.current) {
      isUpdatingRef.current = true;
      const gradient = buildGradient();
      setInternalValue(gradient);
      lastValueRef.current = gradient;
      onChange?.(gradient);
      // Reset the flag after a short delay
      setTimeout(() => {
        isUpdatingRef.current = false;
      }, 50);
    }
  }, [mode, buildGradient, onChange]);

  useEffect(() => {
    if (mode === 'gradient' && !isUpdatingRef.current) {
      handleGradientChange();
    }
  }, [mode, gradientDirection, gradientStops]);

  const handleModeToggle = (newMode: ColorMode) => {
    // Prevent switching to the same mode
    if (newMode === mode) return;
    
    if (isUpdatingRef.current) return;
    
    isUpdatingRef.current = true;
    setIsManualModeChange(true);
    setMode(newMode);
    
    if (newMode === 'gradient') {
      // If switching to gradient, use current value if it's already a gradient, otherwise build new one
      const gradient = isGradientValue ? currentValue : buildGradient();
      setInternalValue(gradient);
      lastValueRef.current = gradient;
      onChange?.(gradient);
    } else {
      // Convert to solid color
      let solidColor: string;
      if (isGradientValue) {
        // Extract color from gradient - use first stop if parsed, otherwise try to extract from gradient string
        if (gradientStops && gradientStops.length > 0 && gradientStops[0].color) {
          solidColor = gradientStops[0].color;
        } else {
          // Try to extract color from gradient string as fallback
          const colorMatch = currentValue.match(/#[0-9a-fA-F]{6}/);
          solidColor = colorMatch ? colorMatch[0] : '#2563EB';
        }
      } else {
        // Already solid, use current value
        solidColor = currentValue;
      }
      setInternalValue(solidColor);
      lastValueRef.current = solidColor;
      onChange?.(solidColor);
    }
    // Reset flags after a delay to allow value to update and prevent useEffect interference
    setTimeout(() => {
      isUpdatingRef.current = false;
      setIsManualModeChange(false);
    }, 300);
  };

  const handleToggle = () => setIsExpanded((prev) => !prev);
  const handleQuickSelect = (next: string) => {
    if (mode === 'solid') {
      handleChange(next);
      if (!isExpanded) {
        setIsExpanded(true);
      }
    }
  };

  const displayValue = useMemo(() => {
    return mode === 'gradient' ? buildGradient() : currentValue;
  }, [mode, buildGradient, currentValue]);
  
  const displayColor = mode === 'gradient' ? gradientStops[0].color : currentValue;

  return (
    <div className={hideWrapper ? '' : styles.wrapper} aria-label={`${label} color picker`}>
      <header className={styles.header}>
        <div className={styles.headerText}>
          <p className={styles.label}>{label}</p>
          {!hideToken && <p className={styles.token}>{token}</p>}
        </div>
        <div className={styles.headerActions}>
          {!hideModeToggle && (
            <div className={styles.modeToggle}>
              <button
                type="button"
                className={`${styles.modeButton} ${mode === 'solid' ? styles.modeButtonActive : ''}`}
                onClick={() => handleModeToggle('solid')}
                aria-label="Solid color mode"
                title="Solid color"
              >
                <LuDroplet aria-hidden="true" />
                <span>Solid</span>
              </button>
              <button
                type="button"
                className={`${styles.modeButton} ${mode === 'gradient' ? styles.modeButtonActive : ''}`}
                onClick={() => handleModeToggle('gradient')}
                aria-label="Gradient mode"
                title="Gradient"
              >
                <LuSparkles aria-hidden="true" />
                <span>Gradient</span>
              </button>
            </div>
          )}
          <button
            type="button"
            className={`${styles.swatchButton} ${mode === 'gradient' ? styles.swatchButtonGradient : ''}`}
            style={mode === 'gradient' 
              ? { backgroundImage: displayValue }
              : { backgroundColor: displayValue }
            }
            onClick={handleToggle}
            aria-expanded={isExpanded}
            aria-label={`${isExpanded ? 'Hide' : 'Edit'} ${label} color`}
          >
            <span className={styles.swatch} aria-hidden="true" />
          </button>
        </div>
      </header>


      {isExpanded && (
        <>
          {mode === 'solid' ? (
            <>
              <HexColorPicker color={currentValue} onChange={handleChange} className={styles.picker} />

              <div className={styles.inputRow}>
                <label>
                  <span>HEX</span>
                  <input
                    type="text"
                    value={currentValue.toUpperCase()}
                    onChange={(event) => handleChange(normalizeHex(event.target.value))}
                  />
                </label>
                <label>
                  <span>RGB</span>
                  <input type="text" value={rgbString} readOnly aria-readonly="true" />
                </label>
                <label>
                  <span>HSL</span>
                  <input type="text" value={hslString} readOnly aria-readonly="true" />
                </label>
              </div>
            </>
          ) : (
            <div className={styles.gradientEditor}>
              <div className={styles.gradientDirection}>
                <label>
                  <span>Direction</span>
                  <div className={styles.directionControl}>
                    <input
                      type="range"
                      min="0"
                      max="360"
                      value={gradientDirection}
                      onChange={(e) => {
                        setGradientDirection(parseInt(e.target.value, 10));
                      }}
                      className={styles.directionSlider}
                    />
                    <input
                      type="number"
                      min="0"
                      max="360"
                      value={gradientDirection}
                      onChange={(e) => {
                        const val = Math.max(0, Math.min(360, parseInt(e.target.value, 10) || 0));
                        setGradientDirection(val);
                      }}
                      className={styles.directionInput}
                    />
                    <span className={styles.directionUnit}>°</span>
                  </div>
                </label>
              </div>

              <div className={styles.gradientStops}>
                {gradientStops.map((stop, index) => (
                  <div key={index} className={styles.gradientStop}>
                    <label>
                      <span>Color {index + 1}</span>
                      <div className={styles.stopControl}>
                        <HexColorPicker
                          color={stop.color}
                          onChange={(color) => {
                            const newStops = [...gradientStops];
                            newStops[index].color = color;
                            setGradientStops(newStops);
                          }}
                          className={styles.stopPicker}
                        />
                        <input
                          type="text"
                          value={stop.color.toUpperCase()}
                          onChange={(e) => {
                            const newStops = [...gradientStops];
                            newStops[index].color = normalizeHex(e.target.value);
                            setGradientStops(newStops);
                          }}
                          className={styles.stopHexInput}
                        />
                      </div>
                    </label>
                    <label>
                      <span>Position</span>
                      <div className={styles.positionControl}>
                        <input
                          type="range"
                          min="0"
                          max="100"
                          value={stop.position}
                          onChange={(e) => {
                            const newStops = [...gradientStops];
                            newStops[index].position = parseInt(e.target.value, 10);
                            setGradientStops(newStops);
                          }}
                          className={styles.positionSlider}
                        />
                        <input
                          type="number"
                          min="0"
                          max="100"
                          value={stop.position}
                          onChange={(e) => {
                            const newStops = [...gradientStops];
                            newStops[index].position = Math.max(0, Math.min(100, parseInt(e.target.value, 10) || 0));
                            setGradientStops(newStops);
                          }}
                          className={styles.positionInput}
                        />
                        <span className={styles.positionUnit}>%</span>
                      </div>
                    </label>
                  </div>
                ))}
              </div>

              <div className={styles.gradientPreview}>
                <div
                  className={styles.gradientPreviewBox}
                  style={{ background: displayValue }}
                />
                <input
                  type="text"
                  value={displayValue}
                  readOnly
                  className={styles.gradientOutput}
                />
              </div>
            </div>
          )}

        </>
      )}
    </div>
  );
}

function hexToRgb(hex: string): string {
  const normalized = normalizeHex(hex).replace('#', '');
  const bigint = Number.parseInt(normalized, 16);
  const r = (bigint >> 16) & 255;
  const g = (bigint >> 8) & 255;
  const b = bigint & 255;
  return `${r}, ${g}, ${b}`;
}

function hexToHsl(hex: string): string {
  const normalized = normalizeHex(hex).replace('#', '');
  const bigint = Number.parseInt(normalized, 16);
  const r = ((bigint >> 16) & 255) / 255;
  const g = ((bigint >> 8) & 255) / 255;
  const b = (bigint & 255) / 255;

  const max = Math.max(r, g, b);
  const min = Math.min(r, g, b);
  let h = 0;
  let s = 0;
  const l = (max + min) / 2;

  if (max !== min) {
    const d = max - min;
    s = l > 0.5 ? d / (2 - max - min) : d / (max + min);
    switch (max) {
      case r:
        h = (g - b) / d + (g < b ? 6 : 0);
        break;
      case g:
        h = (b - r) / d + 2;
        break;
      case b:
        h = (r - g) / d + 4;
        break;
      default:
        break;
    }
    h /= 6;
  }

  return `${Math.round(h * 360)}°, ${Math.round(s * 100)}%, ${Math.round(l * 100)}%`;
}

function normalizeHex(value: string): string {
  const sanitized = value.replace(/[^A-Fa-f0-9]/g, '').slice(0, 6);
  return `#${sanitized.padEnd(6, '0')}`.toUpperCase();
}
