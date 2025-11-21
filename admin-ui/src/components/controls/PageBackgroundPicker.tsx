import { useState, useEffect, useMemo, useRef } from 'react';
import { HexColorPicker } from 'react-colorful';

import styles from './page-background-picker.module.css';

interface PageBackgroundPickerProps {
  value?: string;
  onChange?: (value: string) => void;
  mode?: 'solid' | 'gradient' | 'both' | 'vanta'; // Which section(s) to show
  hidePresets?: boolean; // Hide preset color/gradient grids
  presetsOnly?: boolean; // Show only presets, hide custom picker
  lightOnly?: boolean; // Show only light presets
  darkOnly?: boolean; // Show only dark presets
}

function isGradient(value: string): boolean {
  return value.includes('gradient') || value.includes('linear-gradient') || value.includes('radial-gradient');
}

function isVanta(value: string): boolean {
  return value.startsWith('vanta:') || value.startsWith('{"type":"vanta');
}

function parseGradient(gradient: string): { direction: number; color1: string; color2: string } | null {
  // Try to match various gradient formats
  // Format: linear-gradient(135deg, #2563EB 0%, #7C3AED 100%)
  // Format: linear-gradient(135deg, #2563EB, #7C3AED)
  // Format: linear-gradient(148deg, #ffffe4 0%, #f2fce8 100%) - lowercase hex
  // Format: linear-gradient(135deg, rgb(37, 99, 235) 0%, rgb(124, 58, 237) 100%)
  const linearMatch = gradient.match(/linear-gradient\s*\(\s*(\d+)deg[^)]*\)/i);
  if (linearMatch) {
    const fullMatch = linearMatch[0];
    // Extract colors - try hex first (case insensitive, 3 or 6 digits)
    const hexColors = fullMatch.match(/#[0-9a-fA-F]{3,6}/gi);
    if (hexColors && hexColors.length >= 2) {
      // Normalize 3-digit hex to 6-digit
      const normalizeHex = (hex: string) => {
        if (hex.length === 4) {
          return `#${hex[1]}${hex[1]}${hex[2]}${hex[2]}${hex[3]}${hex[3]}`;
        }
        return hex;
      };
      return {
        direction: parseInt(linearMatch[1], 10),
        color1: normalizeHex(hexColors[0]),
        color2: normalizeHex(hexColors[1])
      };
    }
    // Try rgb format
    const rgbMatch = fullMatch.match(/rgb\((\d+),\s*(\d+),\s*(\d+)\)/gi);
    if (rgbMatch && rgbMatch.length >= 2) {
      // Convert first two RGB values to hex
      const rgb1 = rgbMatch[0].match(/\d+/g)?.map(Number) || [0, 0, 0];
      const rgb2 = rgbMatch[1].match(/\d+/g)?.map(Number) || [0, 0, 0];
      const toHex = (r: number, g: number, b: number) => 
        `#${[r, g, b].map(x => x.toString(16).padStart(2, '0')).join('')}`;
      return {
        direction: parseInt(linearMatch[1], 10),
        color1: toHex(rgb1[0], rgb1[1], rgb1[2]),
        color2: toHex(rgb2[0], rgb2[1], rgb2[2])
      };
    }
  }
  return null;
}

function buildGradient(direction: number, color1: string, color2: string): string {
  return `linear-gradient(${direction}deg, ${color1} 0%, ${color2} 100%)`;
}

export function PageBackgroundPicker({ value = '#FFFFFF', onChange, mode = 'both', hidePresets = false, presetsOnly = false, lightOnly = false, darkOnly = false }: PageBackgroundPickerProps): JSX.Element {
  // Initialize solidColor from value prop if it's a solid color, otherwise default
  const getInitialSolidColor = () => {
    if (value && !isGradient(value)) {
      const hexMatch = value.match(/#[0-9a-fA-F]{6}/i);
      return hexMatch ? hexMatch[0] : value;
    }
    return '#FFFFFF';
  };
  
  // Initialize from value on mount
  const getInitialGradient = () => {
    if (value && isGradient(value)) {
      const parsed = parseGradient(value);
      if (parsed) {
        return {
          direction: parsed.direction,
          color1: parsed.color1,
          color2: parsed.color2
        };
      }
    }
    return {
      direction: 135,
      color1: '#2563EB',
      color2: '#7C3AED'
    };
  };

  const initialGradient = getInitialGradient();
  const [solidColor, setSolidColor] = useState(getInitialSolidColor);
  const [gradientDirection, setGradientDirection] = useState(initialGradient.direction);
  const [gradientColor1, setGradientColor1] = useState(initialGradient.color1);
  const [gradientColor2, setGradientColor2] = useState(initialGradient.color2);
  const [activeMode, setActiveMode] = useState<'solid' | 'gradient' | 'vanta'>(() => {
    // Initialize active mode based on value
    if (value && isVanta(value)) return 'vanta';
    if (value && isGradient(value)) return 'gradient';
    return 'solid';
  });
  const [showSolidPicker, setShowSolidPicker] = useState(false);
  const [showGradientPicker1, setShowGradientPicker1] = useState(false);
  const [showGradientPicker2, setShowGradientPicker2] = useState(false);
  const isInternalUpdateRef = useRef(false);
  const lastValueRef = useRef<string | undefined>(value);

  // Solid color presets - 12 light and 12 dark, modern and accessible
  const solidPresets = [
    // Light Colors (12) - Modern, accessible text colors for dark backgrounds
    { label: 'Pure White (light)', color: '#FFFFFF' },
    { label: 'Snow (light)', color: '#FAFAFA' },
    { label: 'Pearl (light)', color: '#F5F5F5' },
    { label: 'Cloud (light)', color: '#F0F0F0' },
    { label: 'Lavender (light)', color: '#F3E8FF' },
    { label: 'Sky (light)', color: '#E0F2FE' },
    { label: 'Mint (light)', color: '#F0FDF4' },
    { label: 'Rose (light)', color: '#FDF2F8' },
    { label: 'Cream (light)', color: '#FFFBEB' },
    { label: 'Azure (light)', color: '#EFF6FF' },
    { label: 'Lilac (light)', color: '#FAF5FF' },
    { label: 'Seafoam (light)', color: '#F0FDFA' },
    
    // Dark Colors (12) - Modern, accessible text colors for light backgrounds
    { label: 'Charcoal (dark)', color: '#1E293B' },
    { label: 'Midnight (dark)', color: '#0F172A' },
    { label: 'Deep Blue (dark)', color: '#1E3A8A' },
    { label: 'Dark Slate (dark)', color: '#334155' },
    { label: 'Forest Green (dark)', color: '#064E3B' },
    { label: 'Deep Purple (dark)', color: '#312E81' },
    { label: 'Rich Teal (dark)', color: '#134E4A' },
    { label: 'Burgundy (dark)', color: '#7F1D1D' },
    { label: 'Dark Plum (dark)', color: '#581C87' },
    { label: 'Navy Blue (dark)', color: '#1E40AF' },
    { label: 'Deep Crimson (dark)', color: '#991B1B' },
    { label: 'Almost Black (dark)', color: '#0A0A0A' }
  ];

  // Gradient presets - 12 light and 12 dark, modern and accessible
  const gradientPresets = [
    // Light Gradients (12) - Subtle, modern gradients for text
    { label: 'Soft Sky (light)', gradient: 'linear-gradient(135deg, #E0F2FE 0%, #DBEAFE 100%)' },
    { label: 'Lavender Mist (light)', gradient: 'linear-gradient(135deg, #F3E8FF 0%, #E9D5FF 100%)' },
    { label: 'Mint Fresh (light)', gradient: 'linear-gradient(135deg, #F0FDF4 0%, #DCFCE7 100%)' },
    { label: 'Rose Quartz (light)', gradient: 'linear-gradient(135deg, #FDF2F8 0%, #FCE7F3 100%)' },
    { label: 'Soft Gray (light)', gradient: 'linear-gradient(135deg, #F8FAFC 0%, #F1F5F9 100%)' },
    { label: 'Azure Breeze (light)', gradient: 'linear-gradient(135deg, #EFF6FF 0%, #DBEAFE 100%)' },
    { label: 'Peach Blush (light)', gradient: 'linear-gradient(135deg, #FFF7ED 0%, #FFEDD5 100%)' },
    { label: 'Lilac Dream (light)', gradient: 'linear-gradient(135deg, #FAF5FF 0%, #F3E8FF 100%)' },
    { label: 'Ocean Mist (light)', gradient: 'linear-gradient(135deg, #F0FDFA 0%, #CCFBF1 100%)' },
    { label: 'Sunset Glow (light)', gradient: 'linear-gradient(135deg, #FFF1F2 0%, #FFE4E6 100%)' },
    { label: 'Icy Blue (light)', gradient: 'linear-gradient(135deg, #F0F9FF 0%, #E0F2FE 100%)' },
    { label: 'Pale Gold (light)', gradient: 'linear-gradient(135deg, #FFFBEB 0%, #FEF3C7 100%)' },
    
    // Dark Gradients (12) - Rich, modern gradients for text
    { label: 'Deep Ocean (dark)', gradient: 'linear-gradient(135deg, #0F172A 0%, #1E293B 100%)' },
    { label: 'Midnight Blue (dark)', gradient: 'linear-gradient(135deg, #1E3A8A 0%, #312E81 100%)' },
    { label: 'Charcoal (dark)', gradient: 'linear-gradient(135deg, #1E293B 0%, #0F172A 100%)' },
    { label: 'Forest Night (dark)', gradient: 'linear-gradient(135deg, #064E3B 0%, #065F46 100%)' },
    { label: 'Navy Storm (dark)', gradient: 'linear-gradient(135deg, #1E40AF 0%, #1E3A8A 100%)' },
    { label: 'Slate Gray (dark)', gradient: 'linear-gradient(135deg, #334155 0%, #1E293B 100%)' },
    { label: 'Royal Purple (dark)', gradient: 'linear-gradient(135deg, #312E81 0%, #581C87 100%)' },
    { label: 'Deep Teal (dark)', gradient: 'linear-gradient(135deg, #134E4A 0%, #064E3B 100%)' },
    { label: 'Burgundy Wine (dark)', gradient: 'linear-gradient(135deg, #7F1D1D 0%, #991B1B 100%)' },
    { label: 'Midnight Plum (dark)', gradient: 'linear-gradient(135deg, #581C87 0%, #312E81 100%)' },
    { label: 'Deep Navy (dark)', gradient: 'linear-gradient(135deg, #1E40AF 0%, #0F172A 100%)' },
    { label: 'Charcoal Blue (dark)', gradient: 'linear-gradient(135deg, #334155 0%, #0F172A 100%)' }
  ];

  // Initialize from value (only when value changes externally)
  useEffect(() => {
    if (isInternalUpdateRef.current) {
      isInternalUpdateRef.current = false;
      return;
    }
    
    // Always sync if value exists and is different, or if we haven't initialized yet
    if (value && (value !== lastValueRef.current || lastValueRef.current === undefined)) {
      lastValueRef.current = value;
      if (isVanta(value)) {
        setActiveMode('vanta');
      } else if (isGradient(value)) {
        setActiveMode('gradient');
        const parsed = parseGradient(value);
        if (parsed) {
          setGradientDirection(parsed.direction);
          setGradientColor1(parsed.color1);
          setGradientColor2(parsed.color2);
        }
      } else {
        setActiveMode('solid');
        // Ensure value is a valid hex color
        const hexMatch = value.match(/#[0-9a-fA-F]{6}/i);
        if (hexMatch) {
          setSolidColor(hexMatch[0]);
        } else {
          setSolidColor(value);
        }
      }
    }
  }, [value]);

  // Handle solid color change
  const handleSolidColorChange = (color: string) => {
    setSolidColor(color);
    setActiveMode('solid');
    isInternalUpdateRef.current = true;
    lastValueRef.current = color;
    onChange?.(color);
  };

  // Handle gradient change
  const handleGradientChange = (direction?: number, color1?: string, color2?: string) => {
    const newDirection = direction ?? gradientDirection;
    const newColor1 = color1 ?? gradientColor1;
    const newColor2 = color2 ?? gradientColor2;
    
    setGradientDirection(newDirection);
    if (color1 !== undefined) setGradientColor1(newColor1);
    if (color2 !== undefined) setGradientColor2(newColor2);
    
    setActiveMode('gradient');
    const gradient = buildGradient(newDirection, newColor1, newColor2);
    isInternalUpdateRef.current = true;
    lastValueRef.current = gradient;
    onChange?.(gradient);
  };

  // Handle preset selection
  const handleSolidPresetClick = (color: string) => {
    handleSolidColorChange(color);
  };

  const handleGradientPresetClick = (gradient: string) => {
    const parsed = parseGradient(gradient);
    if (parsed) {
      handleGradientChange(parsed.direction, parsed.color1, parsed.color2);
    }
  };

  const currentGradient = useMemo(() => {
    return buildGradient(gradientDirection, gradientColor1, gradientColor2);
  }, [gradientDirection, gradientColor1, gradientColor2]);

  const showSolid = mode === 'solid' || mode === 'both';
  const showGradient = mode === 'gradient' || mode === 'both';
  const showVanta = mode === 'vanta' || mode === 'both';

  // Split solid presets into light and dark
  const lightPresets = solidPresets.slice(0, 12);
  const darkPresets = solidPresets.slice(12, 24);

  // Split gradient presets into light and dark
  const lightGradientPresets = gradientPresets.slice(0, 12);
  const darkGradientPresets = gradientPresets.slice(12, 24);

  return (
    <div className={styles.wrapper}>
      {/* Solid Section */}
      {showSolid && (
        <>
          {!hidePresets && (
            <>
              {/* Light Colors Section - Only show if not darkOnly */}
              {!darkOnly && (
                <div className={styles.section}>
                  {!lightOnly && <h4 className={styles.sectionTitle}>Light</h4>}
                  
                  {/* Preset Colors */}
                  <div className={styles.presetGrid}>
                    {lightPresets.map((preset) => {
                      const isSelected = activeMode === 'solid' && solidColor === preset.color;
                      return (
                        <button
                          key={preset.color}
                          type="button"
                          className={`${styles.presetSwatch} ${isSelected ? styles.presetSwatchActive : ''}`}
                          style={{ backgroundColor: preset.color }}
                          onClick={() => handleSolidPresetClick(preset.color)}
                          title={preset.label}
                          aria-label={`Select ${preset.label}`}
                        >
                          {isSelected && (
                            <span className={styles.checkmark} aria-hidden="true">✓</span>
                          )}
                        </button>
                      );
                    })}
                  </div>
                </div>
              )}

              {/* Dark Colors Section - Only show if not lightOnly */}
              {!lightOnly && (
                <div className={styles.section}>
                  {!darkOnly && <h4 className={styles.sectionTitle}>Dark</h4>}
                  
                  {/* Preset Colors */}
                  <div className={styles.presetGrid}>
                    {darkPresets.map((preset) => {
                      const isSelected = activeMode === 'solid' && solidColor === preset.color;
                      return (
                        <button
                          key={preset.color}
                          type="button"
                          className={`${styles.presetSwatch} ${isSelected ? styles.presetSwatchActive : ''}`}
                          style={{ backgroundColor: preset.color }}
                          onClick={() => handleSolidPresetClick(preset.color)}
                          title={preset.label}
                          aria-label={`Select ${preset.label}`}
                        >
                          {isSelected && (
                            <span className={styles.checkmark} aria-hidden="true">✓</span>
                          )}
                        </button>
                      );
                    })}
                  </div>
                </div>
              )}
            </>
          )}

          {/* Custom Color Picker - Only show if not presetsOnly */}
          {!presetsOnly && (
            <div className={styles.customSection}>
              {!hidePresets && (
                <label className={styles.customLabel}>
                  <span>Custom Color</span>
                </label>
              )}
              <div className={styles.colorPickerRow}>
                <div className={styles.colorSwatchRow}>
                  <button
                    type="button"
                    className={styles.colorPreviewButton}
                    onClick={() => setShowSolidPicker(!showSolidPicker)}
                    style={{ backgroundColor: solidColor }}
                    aria-label="Open color picker"
                  >
                    <div className={styles.colorPreview} style={{ backgroundColor: solidColor }} />
                  </button>
                  <input
                    type="text"
                    value={solidColor.toUpperCase()}
                    onChange={(e) => {
                      const hex = e.target.value.startsWith('#') ? e.target.value : `#${e.target.value}`;
                      if (/^#[0-9A-F]{6}$/i.test(hex)) {
                        handleSolidColorChange(hex);
                      }
                    }}
                    className={styles.hexInput}
                    placeholder="#FFFFFF"
                    maxLength={7}
                  />
                </div>
                {showSolidPicker && (
                  <div className={styles.colorPickerContainer}>
                    <HexColorPicker
                      color={solidColor}
                      onChange={handleSolidColorChange}
                      className={styles.colorPicker}
                    />
                  </div>
                )}
              </div>
            </div>
          )}
        </>
      )}

      {/* Gradient Section */}
      {showGradient && (
        <>
          {!hidePresets && (
            <>
              {/* Light Gradients Section - Only show if not darkOnly */}
              {!darkOnly && (
                <div className={styles.section}>
                  {!lightOnly && <h4 className={styles.sectionTitle}>Light</h4>}
                  
                  {/* Preset Gradients */}
                  <div className={styles.presetGrid}>
                    {lightGradientPresets.map((preset) => {
                      const currentGradientValue = activeMode === 'gradient' ? currentGradient : '';
                      const isSelected = activeMode === 'gradient' && (
                        value === preset.gradient || 
                        currentGradientValue === preset.gradient ||
                        (value && isGradient(value) && parseGradient(value) && parseGradient(preset.gradient) &&
                         parseGradient(value)?.direction === parseGradient(preset.gradient)?.direction &&
                         parseGradient(value)?.color1 === parseGradient(preset.gradient)?.color1 &&
                         parseGradient(value)?.color2 === parseGradient(preset.gradient)?.color2)
                      );
                      return (
                        <button
                          key={preset.label}
                          type="button"
                          className={`${styles.presetSwatch} ${styles.presetSwatchGradient} ${isSelected ? styles.presetSwatchActive : ''}`}
                          style={{ backgroundImage: preset.gradient }}
                          onClick={() => handleGradientPresetClick(preset.gradient)}
                          title={preset.label}
                          aria-label={`Select ${preset.label} gradient`}
                        >
                          {isSelected && (
                            <span className={styles.checkmark} aria-hidden="true">✓</span>
                          )}
                        </button>
                      );
                    })}
                  </div>
                </div>
              )}

              {/* Dark Gradients Section - Only show if not lightOnly */}
              {!lightOnly && (
                <div className={styles.section}>
                  {!darkOnly && <h4 className={styles.sectionTitle}>Dark</h4>}
                  
                  {/* Preset Gradients */}
                  <div className={styles.presetGrid}>
                    {darkGradientPresets.map((preset) => {
                      const currentGradientValue = activeMode === 'gradient' ? currentGradient : '';
                      const isSelected = activeMode === 'gradient' && (
                        value === preset.gradient || 
                        currentGradientValue === preset.gradient ||
                        (value && isGradient(value) && parseGradient(value) && parseGradient(preset.gradient) &&
                         parseGradient(value)?.direction === parseGradient(preset.gradient)?.direction &&
                         parseGradient(value)?.color1 === parseGradient(preset.gradient)?.color1 &&
                         parseGradient(value)?.color2 === parseGradient(preset.gradient)?.color2)
                      );
                      return (
                        <button
                          key={preset.label}
                          type="button"
                          className={`${styles.presetSwatch} ${styles.presetSwatchGradient} ${isSelected ? styles.presetSwatchActive : ''}`}
                          style={{ backgroundImage: preset.gradient }}
                          onClick={() => handleGradientPresetClick(preset.gradient)}
                          title={preset.label}
                          aria-label={`Select ${preset.label} gradient`}
                        >
                          {isSelected && (
                            <span className={styles.checkmark} aria-hidden="true">✓</span>
                          )}
                        </button>
                      );
                    })}
                  </div>
                </div>
              )}
            </>
          )}

          {/* Custom Gradient Builder - Only show if not presetsOnly */}
          {!presetsOnly && (
            <div className={styles.customSection}>
              <label className={styles.customLabel}>
                <span>Custom Gradient</span>
              </label>
            
            {/* Direction Control */}
            <div className={styles.gradientControl}>
              <label className={styles.controlLabel}>
                <span>Direction</span>
                <div className={styles.directionRow}>
                  <input
                    type="range"
                    min="0"
                    max="360"
                    value={gradientDirection}
                    onChange={(e) => handleGradientChange(parseInt(e.target.value, 10))}
                    className={styles.directionSlider}
                  />
                  <input
                    type="number"
                    min="0"
                    max="360"
                    value={gradientDirection}
                    onChange={(e) => {
                      const val = Math.max(0, Math.min(360, parseInt(e.target.value, 10) || 0));
                      handleGradientChange(val);
                    }}
                    className={styles.directionInput}
                  />
                  <span className={styles.degreeSymbol}>°</span>
                </div>
              </label>
            </div>

            {/* Color Stops */}
            <div className={styles.colorStopsRow}>
              <div className={styles.colorStop}>
                <label className={styles.colorStopLabel}>
                  <span>Color 1</span>
                </label>
                <div className={styles.colorStopControls}>
                  <div className={styles.colorSwatchRow}>
                    <button
                      type="button"
                      className={styles.colorPreviewButton}
                      onClick={() => setShowGradientPicker1(!showGradientPicker1)}
                      style={{ backgroundColor: gradientColor1 }}
                      aria-label="Open color picker for Color 1"
                    >
                      <div className={styles.colorPreview} style={{ backgroundColor: gradientColor1 }} />
                    </button>
                    <input
                      type="text"
                      value={gradientColor1.toUpperCase()}
                      onChange={(e) => {
                        const hex = e.target.value.startsWith('#') ? e.target.value : `#${e.target.value}`;
                        if (/^#[0-9A-F]{6}$/i.test(hex)) {
                          handleGradientChange(undefined, hex);
                        }
                      }}
                      className={styles.hexInput}
                      placeholder="#2563EB"
                      maxLength={7}
                    />
                  </div>
                  {showGradientPicker1 && (
                    <div className={styles.colorPickerContainer}>
                      <HexColorPicker
                        color={gradientColor1}
                        onChange={(color) => handleGradientChange(undefined, color)}
                        className={styles.colorPicker}
                      />
                    </div>
                  )}
                </div>
              </div>

              <div className={styles.colorStop}>
                <label className={styles.colorStopLabel}>
                  <span>Color 2</span>
                </label>
                <div className={styles.colorStopControls}>
                  <div className={styles.colorSwatchRow}>
                    <button
                      type="button"
                      className={styles.colorPreviewButton}
                      onClick={() => setShowGradientPicker2(!showGradientPicker2)}
                      style={{ backgroundColor: gradientColor2 }}
                      aria-label="Open color picker for Color 2"
                    >
                      <div className={styles.colorPreview} style={{ backgroundColor: gradientColor2 }} />
                    </button>
                    <input
                      type="text"
                      value={gradientColor2.toUpperCase()}
                      onChange={(e) => {
                        const hex = e.target.value.startsWith('#') ? e.target.value : `#${e.target.value}`;
                        if (/^#[0-9A-F]{6}$/i.test(hex)) {
                          handleGradientChange(undefined, undefined, hex);
                        }
                      }}
                      className={styles.hexInput}
                      placeholder="#7C3AED"
                      maxLength={7}
                    />
                  </div>
                  {showGradientPicker2 && (
                    <div className={styles.colorPickerContainer}>
                      <HexColorPicker
                        color={gradientColor2}
                        onChange={(color) => handleGradientChange(undefined, undefined, color)}
                        className={styles.colorPicker}
                      />
                    </div>
                  )}
                </div>
              </div>
            </div>

            {/* Gradient Preview */}
            <div className={styles.gradientPreview}>
              <div
                className={styles.gradientPreviewBox}
                style={{ background: currentGradient }}
              />
            </div>
          </div>
          )}
        </>
      )}

      {/* Vanta.js Section */}
      {showVanta && (
        <div className={styles.customSection}>
          <label className={styles.customLabel}>
            <span>Animated Backgrounds</span>
          </label>
          <div className={styles.vantaOptions}>
            <button
              type="button"
              className={`${styles.vantaOption} ${activeMode === 'vanta' && value === 'vanta:clouds2' ? styles.vantaOptionActive : ''}`}
              onClick={() => {
                setActiveMode('vanta');
                isInternalUpdateRef.current = true;
                lastValueRef.current = 'vanta:clouds2';
                onChange?.('vanta:clouds2');
              }}
            >
              <div className={styles.vantaPreview}>
                <div className={styles.vantaPreviewIcon}>☁️</div>
              </div>
              <span className={styles.vantaLabel}>Clouds 2</span>
            </button>
          </div>
        </div>
      )}
    </div>
  );
}

