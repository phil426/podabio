import { useState, useMemo } from 'react';
import * as Tabs from '@radix-ui/react-tabs';
import * as Popover from '@radix-ui/react-popover';
import { X, Palette, TextT, Square, Sparkle, Image as ImageIcon } from '@phosphor-icons/react';
import { HexColorPicker } from 'react-colorful';
import { PageBackgroundPicker } from '../controls/PageBackgroundPicker';
import styles from './page-settings-demo.module.css';

const availableFonts = ['Inter', 'Poppins', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Raleway', 'Source Sans Pro'];

// Color Swatch Component
interface ColorSwatchProps {
  value: string;
  onChange: (value: string) => void;
  label: string;
}

function ColorSwatch({ value, onChange, label }: ColorSwatchProps): JSX.Element {
  const [open, setOpen] = useState(false);
  const [tempColor, setTempColor] = useState(value);

  const getHexColor = (colorValue: string): string => {
    if (colorValue.startsWith('#')) {
      return colorValue;
    }
    const hexMatch = colorValue.match(/#[0-9a-fA-F]{6}/i);
    if (hexMatch) {
      return hexMatch[0];
    }
    return '#111827';
  };

  const hexColor = getHexColor(value);
  const isGradient = value.includes('gradient');

  return (
    <Popover.Root open={open} onOpenChange={setOpen}>
      <Popover.Trigger asChild>
        <button
          type="button"
          className={styles.swatchButton}
          title={label}
          aria-label={label}
        >
          <div 
            className={styles.swatch}
            style={{ 
              backgroundColor: hexColor,
              backgroundImage: isGradient ? value : undefined
            }}
          />
        </button>
      </Popover.Trigger>
      <Popover.Portal>
        <Popover.Content
          className={styles.colorPopover}
          sideOffset={5}
          align="end"
        >
          <div className={styles.colorPopoverContent}>
            <HexColorPicker
              color={hexColor}
              onChange={(newColor) => {
                setTempColor(newColor);
                onChange(newColor);
              }}
            />
            <div className={styles.colorPopoverInput}>
              <input
                type="text"
                value={tempColor}
                onChange={(e) => {
                  setTempColor(e.target.value);
                  onChange(e.target.value);
                }}
                placeholder="#000000"
                className={styles.colorInput}
              />
            </div>
          </div>
        </Popover.Content>
      </Popover.Portal>
    </Popover.Root>
  );
}

// Background Swatch Component
interface BackgroundSwatchProps {
  value: string;
  backgroundType: 'solid' | 'gradient' | 'image';
  backgroundImage?: string | null;
  onChange: (value: string) => void;
  onTypeChange: (type: 'solid' | 'gradient' | 'image') => void;
  onImageChange?: (url: string) => void;
  label: string;
}

function BackgroundSwatch({ 
  value, 
  backgroundType,
  backgroundImage,
  onChange, 
  onTypeChange,
  onImageChange,
  label
}: BackgroundSwatchProps): JSX.Element {
  const [open, setOpen] = useState(false);

  const getDisplayValue = (): string => {
    if (backgroundType === 'image' && backgroundImage) {
      return backgroundImage;
    }
    if (backgroundType === 'gradient') {
      return value;
    }
    if (value.startsWith('#')) {
      return value;
    }
    const hexMatch = value.match(/#[0-9a-fA-F]{6}/i);
    return hexMatch ? hexMatch[0] : '#FFFFFF';
  };

  const displayValue = getDisplayValue();
  const isGradient = backgroundType === 'gradient' || value.includes('gradient');
  const isImage = backgroundType === 'image' && backgroundImage;

  return (
    <Popover.Root open={open} onOpenChange={setOpen}>
      <Popover.Trigger asChild>
        <button
          type="button"
          className={styles.swatchButton}
          title={label}
          aria-label={label}
        >
          <div 
            className={styles.swatch}
            style={{ 
              backgroundColor: isImage ? 'transparent' : (isGradient ? 'transparent' : displayValue),
              backgroundImage: isGradient || isImage ? (isImage ? `url(${displayValue})` : displayValue) : undefined,
              backgroundSize: isImage ? 'cover' : undefined,
              backgroundPosition: isImage ? 'center' : undefined
            }}
          />
        </button>
      </Popover.Trigger>
      <Popover.Portal>
        <Popover.Content
          className={styles.backgroundPopover}
          sideOffset={5}
          align="end"
        >
          <div className={styles.backgroundPopoverContent}>
            <Tabs.Root 
              value={backgroundType}
              onValueChange={(value) => onTypeChange(value as 'solid' | 'gradient' | 'image')}
            >
              <Tabs.List className={styles.backgroundTabsList}>
                <Tabs.Trigger value="solid" className={styles.backgroundTabTrigger}>
                  <Square size={14} />
                  <span>Solid</span>
                </Tabs.Trigger>
                <Tabs.Trigger value="gradient" className={styles.backgroundTabTrigger}>
                  <Sparkle size={14} />
                  <span>Gradient</span>
                </Tabs.Trigger>
                <Tabs.Trigger value="image" className={styles.backgroundTabTrigger}>
                  <ImageIcon size={14} />
                  <span>Image</span>
                </Tabs.Trigger>
              </Tabs.List>
              <Tabs.Content value={backgroundType} className={styles.backgroundTabContent}>
                {backgroundType === 'image' ? (
                  <div className={styles.imageUpload}>
                    <input
                      type="url"
                      placeholder="Image URL"
                      value={backgroundImage || ''}
                      onChange={(e) => {
                        onImageChange?.(e.target.value);
                      }}
                      className={styles.urlInput}
                    />
                    <input
                      type="file"
                      accept="image/*"
                      onChange={(e) => {
                        const file = e.target.files?.[0];
                        if (file) {
                          const reader = new FileReader();
                          reader.onload = (event) => {
                            const dataUrl = event.target?.result as string;
                            onImageChange?.(dataUrl);
                          };
                          reader.readAsDataURL(file);
                        }
                      }}
                      className={styles.fileInput}
                    />
                  </div>
                ) : (
                  <PageBackgroundPicker
                    value={value}
                    onChange={onChange}
                    mode={backgroundType}
                    hidePresets
                    presetsOnly={false}
                  />
                )}
              </Tabs.Content>
            </Tabs.Root>
          </div>
        </Popover.Content>
      </Popover.Portal>
    </Popover.Root>
  );
}

// Slider Input Component
interface SliderInputProps {
  label: string;
  value: number;
  min: number;
  max: number;
  step?: number;
  onChange: (value: number) => void;
  unit?: string;
}

function SliderInput({ label, value, min, max, step = 1, onChange, unit = '' }: SliderInputProps): JSX.Element {
  return (
    <div className={styles.controlRow}>
      <label className={styles.controlLabel}>{label}</label>
      <div className={styles.sliderContainer}>
        <input
          type="range"
          min={min}
          max={max}
          step={step}
          value={value}
          onChange={(e) => onChange(parseFloat(e.target.value))}
          className={styles.slider}
        />
        <input
          type="number"
          min={min}
          max={max}
          step={step}
          value={value}
          onChange={(e) => onChange(parseFloat(e.target.value) || 0)}
          className={styles.numberInput}
        />
        {unit && <span className={styles.unit}>{unit}</span>}
      </div>
    </div>
  );
}

export function PageSettingsDemo(): JSX.Element {
  const [fillColor, setFillColor] = useState('#FFFFFF');
  const [fillType, setFillType] = useState<'solid' | 'gradient' | 'image'>('solid');
  const [fillImage, setFillImage] = useState<string | null>(null);
  const [textColor, setTextColor] = useState('#7c3aed');
  const [textFont, setTextFont] = useState('Poppins');
  const [textSize, setTextSize] = useState(16);
  const [borderColor, setBorderColor] = useState('#FFFFFF');
  const [borderWidth, setBorderWidth] = useState(0);

  return (
    <div className={styles.demoContainer}>
      <div className={styles.panel}>
        <header className={styles.header}>
          <h3 className={styles.title}>Page settings</h3>
          <button
            type="button"
            className={styles.closeButton}
            aria-label="Close"
          >
            <X size={20} />
          </button>
        </header>

        <Tabs.Root value="style" className={styles.tabsRoot}>
          <Tabs.List className={styles.tabsList}>
            <Tabs.Trigger value="style" className={styles.tabTrigger}>
              Style
            </Tabs.Trigger>
          </Tabs.List>

          <Tabs.Content value="style" className={styles.tabContent}>
            <div className={styles.content}>
              <div className={styles.controls}>
                {/* Fill color */}
                <div className={styles.controlRow}>
                  <label className={styles.controlLabel}>Fill color</label>
                  <BackgroundSwatch
                    value={fillColor}
                    backgroundType={fillType}
                    backgroundImage={fillImage}
                    onChange={(value) => {
                      setFillColor(value);
                      if (fillType === 'image') {
                        setFillImage(value);
                      }
                    }}
                    onTypeChange={(type) => {
                      setFillType(type);
                      if (type === 'solid') {
                        setFillColor('#FFFFFF');
                        setFillImage(null);
                      } else if (type === 'gradient') {
                        setFillColor('linear-gradient(140deg, #02040d 0%, #0a1331 45%, #1a2151 100%)');
                        setFillImage(null);
                      }
                    }}
                    onImageChange={(url) => {
                      setFillImage(url);
                      setFillColor(url);
                    }}
                    label="Fill color"
                  />
                </div>

                {/* Text color */}
                <div className={styles.controlRow}>
                  <label className={styles.controlLabel}>Text color</label>
                  <ColorSwatch
                    value={textColor}
                    onChange={setTextColor}
                    label="Text color"
                  />
                </div>

                {/* Text font */}
                <div className={styles.controlRow}>
                  <label className={styles.controlLabel}>Text font</label>
                  <div className={styles.fontControl}>
                    <button className={styles.fontButton}>
                      {textFont}
                    </button>
                    <Popover.Root>
                      <Popover.Trigger asChild>
                        <button className={styles.changeButton}>Change</button>
                      </Popover.Trigger>
                      <Popover.Portal>
                        <Popover.Content className={styles.fontPopover} sideOffset={5}>
                          <select 
                            className={styles.fontSelect}
                            value={textFont}
                            onChange={(e) => setTextFont(e.target.value)}
                          >
                            {availableFonts.map(font => (
                              <option key={font} value={font}>{font}</option>
                            ))}
                          </select>
                        </Popover.Content>
                      </Popover.Portal>
                    </Popover.Root>
                  </div>
                </div>

                {/* Text size */}
                <SliderInput
                  label="Text size"
                  value={textSize}
                  min={8}
                  max={72}
                  step={1}
                  onChange={setTextSize}
                  unit="px"
                />

                {/* Border color */}
                <div className={styles.controlRow}>
                  <label className={styles.controlLabel}>Border color</label>
                  <ColorSwatch
                    value={borderColor}
                    onChange={setBorderColor}
                    label="Border color"
                  />
                </div>

                {/* Border width */}
                <SliderInput
                  label="Border width"
                  value={borderWidth}
                  min={0}
                  max={10}
                  step={1}
                  onChange={setBorderWidth}
                  unit="px"
                />
              </div>
            </div>
          </Tabs.Content>
        </Tabs.Root>
      </div>
    </div>
  );
}

