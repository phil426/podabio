/**
 * Poda Color - Gradient & Solid Color Picker
 * 
 * A compact, accessible color picker component supporting both solid colors and gradients.
 * Built with react-colorful and Radix UI for reliable cross-browser dragging support.
 * 
 * @package poda-color
 * @license MIT
 * 
 * Attribution:
 * - react-colorful: https://github.com/omgovich/react-colorful
 *   Copyright (c) 2020 Vlad Shilov omgovich@ya.ru
 *   Licensed under MIT License
 * 
 * - Radix UI: https://www.radix-ui.com/
 *   Copyright (c) 2023 WorkOS
 *   Licensed under MIT License
 */

import { useState, useEffect } from 'react';
import { HexColorPicker } from 'react-colorful';
import * as Slider from '@radix-ui/react-slider';
import * as Popover from '@radix-ui/react-popover';
import { Eyedropper } from '@phosphor-icons/react';
import styles from './poda-color-picker.module.css';

interface PodaColorPickerProps {
  value?: string;
  onChange?: (value: string) => void;
  solidOnly?: boolean; // If true, only show solid color mode (hide gradient tab)
}

function isGradient(value: string): boolean {
  return value.includes('gradient');
}

function parseGradient(value: string): { direction: number; color1: string; color2: string } | null {
  const match = value.match(/linear-gradient\((\d+)deg,\s*(#[0-9a-fA-F]{6})\s+0%,\s*(#[0-9a-fA-F]{6})\s+100%\)/);
  if (match) {
    return {
      direction: parseInt(match[1], 10),
      color1: match[2],
      color2: match[3]
    };
  }
  return null;
}

function buildGradient(direction: number, color1: string, color2: string): string {
  return `linear-gradient(${direction}deg, ${color1} 0%, ${color2} 100%)`;
}

export function PodaColorPicker({ 
  value = 'linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)',
  onChange,
  solidOnly = false
}: PodaColorPickerProps): JSX.Element {
  const isGrad = isGradient(value);
  const parsed = isGrad ? (parseGradient(value) || { direction: 135, color1: '#6366f1', color2: '#4f46e5' }) : null;
  const solidColor = isGrad ? '#6366f1' : (value.startsWith('#') ? value : '#6366f1');
  
  // Force solid mode if solidOnly is true
  const [mode, setMode] = useState<'solid' | 'gradient'>(solidOnly ? 'solid' : (isGrad ? 'gradient' : 'solid'));
  const [direction, setDirection] = useState(parsed?.direction ?? 135);
  const [color1, setColor1] = useState(parsed?.color1 ?? '#6366f1');
  const [color2, setColor2] = useState(parsed?.color2 ?? '#4f46e5');
  const [solid, setSolid] = useState(solidColor);
  const [picker1Open, setPicker1Open] = useState(false);
  const [picker2Open, setPicker2Open] = useState(false);
  const [solidPickerOpen, setSolidPickerOpen] = useState(false);
  const [isEyedropperSupported, setIsEyedropperSupported] = useState(false);

  // Check if EyeDropper API is supported
  useEffect(() => {
    setIsEyedropperSupported(typeof window !== 'undefined' && 'EyeDropper' in window);
  }, []);

  // Update mode when value changes externally (only if not solidOnly)
  useEffect(() => {
    if (solidOnly) {
      // If solidOnly, always extract solid color and stay in solid mode
      const newIsGrad = isGradient(value);
      if (newIsGrad) {
        const newParsed = parseGradient(value);
        if (newParsed) {
          setSolid(newParsed.color1);
          onChange?.(newParsed.color1);
        } else {
          setSolid('#6366f1');
          onChange?.('#6366f1');
        }
      } else {
        setSolid(value.startsWith('#') ? value : '#6366f1');
      }
      setMode('solid');
      return;
    }
    
    const newIsGrad = isGradient(value);
    if (newIsGrad && mode === 'solid') {
      const newParsed = parseGradient(value);
      if (newParsed) {
        setDirection(newParsed.direction);
        setColor1(newParsed.color1);
        setColor2(newParsed.color2);
        setMode('gradient');
      }
    } else if (!newIsGrad && mode === 'gradient') {
      setSolid(value.startsWith('#') ? value : '#6366f1');
      setMode('solid');
    }
  }, [value, mode, solidOnly, onChange]);


  const handleModeChange = (newMode: 'solid' | 'gradient') => {
    setMode(newMode);
    if (newMode === 'solid') {
      // When switching to solid, use Color 1 as the solid color
      setSolid(color1);
      onChange?.(color1);
    } else {
      // When switching to gradient, use solid color as Color 1
      setColor1(solid);
      onChange?.(buildGradient(direction, solid, color2));
    }
  };

  const handleDirectionChange = (newDirection: number) => {
    setDirection(newDirection);
    const newGradient = buildGradient(newDirection, color1, color2);
    onChange?.(newGradient);
  };

  const handleColor1Change = (newColor: string) => {
    setColor1(newColor);
    // Keep solid color in sync with Color 1
    setSolid(newColor);
    const newGradient = buildGradient(direction, newColor, color2);
    onChange?.(newGradient);
  };

  const handleColor2Change = (newColor: string) => {
    setColor2(newColor);
    const newGradient = buildGradient(direction, color1, newColor);
    onChange?.(newGradient);
  };

  const handleSolidChange = (newColor: string) => {
    setSolid(newColor);
    // Keep Color 1 in sync with solid color
    setColor1(newColor);
    onChange?.(newColor);
  };

  const handleEyedropper = async (colorNumber: 1 | 2 | 'solid') => {
    if (!isEyedropperSupported) {
      alert('Eyedropper is not supported in this browser. Please use Chrome, Edge, or Safari 18+.');
      return;
    }

    try {
      // @ts-expect-error - EyeDropper API is not in TypeScript types yet
      const eyeDropper = new window.EyeDropper();
      const result = await eyeDropper.open();
      
      if (result.sRGBHex) {
        if (colorNumber === 'solid') {
          handleSolidChange(result.sRGBHex);
        } else if (colorNumber === 1) {
          handleColor1Change(result.sRGBHex);
        } else {
          handleColor2Change(result.sRGBHex);
        }
      }
    } catch (error) {
      // User cancelled or error occurred
      if (error instanceof Error && error.name !== 'AbortError') {
        console.error('Eyedropper error:', error);
      }
    }
  };

  const previewStyle = mode === 'gradient' 
    ? { background: buildGradient(direction, color1, color2) }
    : { backgroundColor: solid };

  return (
    <div className={styles.gradientPicker}>
      {!solidOnly && (
        <div className={styles.tabs}>
          <button
            type="button"
            className={`${styles.tab} ${mode === 'solid' ? styles.tabActive : ''}`}
            onClick={() => handleModeChange('solid')}
          >
            Solid
          </button>
          <button
            type="button"
            className={`${styles.tab} ${mode === 'gradient' ? styles.tabActive : ''}`}
            onClick={() => handleModeChange('gradient')}
          >
            Gradient
          </button>
        </div>
      )}
      <div className={styles.preview} style={previewStyle} />
      
      <div className={styles.controls}>
        {mode === 'gradient' && (
          <div className={styles.colorStops}>
            <div className={styles.colorStop}>
              <label className={styles.label}>Color 1</label>
              <div className={styles.colorStopControls}>
                <div className={styles.colorSwatchRow}>
                  <Popover.Root open={picker1Open} onOpenChange={setPicker1Open}>
                    <Popover.Trigger asChild>
                      <button
                        type="button"
                        className={styles.colorSwatch}
                        style={{ backgroundColor: color1 }}
                        aria-expanded={picker1Open}
                        aria-label="Open color picker for Color 1"
                      />
                    </Popover.Trigger>
                    <Popover.Portal>
                      <Popover.Content
                        className={styles.colorPickerPopover}
                        sideOffset={5}
                        align="start"
                      >
                        <HexColorPicker
                          color={color1}
                          onChange={handleColor1Change}
                          className={styles.colorPicker}
                        />
                      </Popover.Content>
                    </Popover.Portal>
                  </Popover.Root>
                  {isEyedropperSupported && (
                    <button
                      type="button"
                      className={styles.eyedropperButton}
                      onClick={() => handleEyedropper(1)}
                      aria-label="Pick color from screen"
                      title="Eyedropper"
                    >
                      <Eyedropper size={14} weight="regular" />
                    </button>
                  )}
                </div>
              </div>
            </div>

            <div className={styles.colorStop}>
              <label className={`${styles.label} ${styles.labelRight}`}>Color 2</label>
              <div className={styles.colorStopControls}>
                <div className={styles.colorSwatchRow}>
                  {isEyedropperSupported && (
                    <button
                      type="button"
                      className={styles.eyedropperButton}
                      onClick={() => handleEyedropper(2)}
                      aria-label="Pick color from screen"
                      title="Eyedropper"
                    >
                      <Eyedropper size={14} weight="regular" />
                    </button>
                  )}
                  <Popover.Root open={picker2Open} onOpenChange={setPicker2Open}>
                    <Popover.Trigger asChild>
                      <button
                        type="button"
                        className={styles.colorSwatch}
                        style={{ backgroundColor: color2 }}
                        aria-expanded={picker2Open}
                        aria-label="Open color picker for Color 2"
                      />
                    </Popover.Trigger>
                    <Popover.Portal>
                      <Popover.Content
                        className={styles.colorPickerPopover}
                        sideOffset={5}
                        align="start"
                      >
                        <HexColorPicker
                          color={color2}
                          onChange={handleColor2Change}
                          className={styles.colorPicker}
                        />
                      </Popover.Content>
                    </Popover.Portal>
                  </Popover.Root>
                </div>
              </div>
            </div>
          </div>
        )}
        
        {mode === 'solid' ? (
          <div className={styles.colorStop}>
            <label className={styles.label}>Color</label>
            <div className={styles.colorStopControls}>
              <div className={styles.colorSwatchRow}>
                <Popover.Root open={solidPickerOpen} onOpenChange={setSolidPickerOpen}>
                  <Popover.Trigger asChild>
                    <button
                      type="button"
                      className={styles.colorSwatch}
                      style={{ backgroundColor: solid }}
                      aria-expanded={solidPickerOpen}
                      aria-label="Open color picker"
                    />
                  </Popover.Trigger>
                  <Popover.Portal>
                    <Popover.Content
                      className={styles.colorPickerPopover}
                      sideOffset={5}
                      align="start"
                    >
                      <HexColorPicker
                        color={solid}
                        onChange={handleSolidChange}
                        className={styles.colorPicker}
                      />
                    </Popover.Content>
                  </Popover.Portal>
                </Popover.Root>
                {isEyedropperSupported && (
                  <button
                    type="button"
                    className={styles.eyedropperButton}
                    onClick={() => handleEyedropper('solid')}
                    aria-label="Pick color from screen"
                    title="Eyedropper"
                  >
                    <Eyedropper size={14} weight="regular" />
                  </button>
                )}
              </div>
            </div>
          </div>
        ) : (
          <>
        <div className={styles.controlGroup}>
          <label className={styles.label}>Direction</label>
          <div className={styles.sliderContainer}>
            <Slider.Root
              className={styles.sliderRoot}
              value={[direction]}
              onValueChange={(values) => handleDirectionChange(values[0])}
              min={0}
              max={360}
              step={1}
            >
              <Slider.Track className={styles.sliderTrack}>
                <Slider.Range className={styles.sliderRange} />
              </Slider.Track>
              <Slider.Thumb className={styles.sliderThumb} aria-label="Gradient direction" />
            </Slider.Root>
          </div>
        </div>
          </>
        )}
      </div>
    </div>
  );
}

