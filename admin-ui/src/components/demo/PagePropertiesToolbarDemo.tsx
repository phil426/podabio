import { useState, useRef, useEffect, useMemo } from 'react';
import { 
  TextT, 
  Palette, 
  TextB, 
  TextItalic, 
  TextUnderline, 
  TextAlignLeft, 
  TextAlignCenter, 
  TextAlignRight,
  Image as ImageIcon,
  Sparkle,
  Square
} from '@phosphor-icons/react';
import * as Tabs from '@radix-ui/react-tabs';
import * as Popover from '@radix-ui/react-popover';
import { HexColorPicker } from 'react-colorful';
import { ColorTokenPicker } from '../controls/ColorTokenPicker';
import { PageBackgroundPicker } from '../controls/PageBackgroundPicker';
import styles from './page-properties-toolbar-demo.module.css';

interface PagePropertiesToolbarDemoProps {
  // Typography
  headingFont?: string;
  bodyFont?: string;
  headingColor?: string;
  bodyColor?: string;
  headingBold?: boolean;
  headingItalic?: boolean;
  headingUnderline?: boolean;
  bodyBold?: boolean;
  bodyItalic?: boolean;
  bodyUnderline?: boolean;
  textAlignment?: 'left' | 'center' | 'right';
  
  // Background
  pageBackground?: string;
  pageBackgroundType?: 'solid' | 'gradient' | 'image';
  pageBackgroundImage?: string | null;
  
  // Callbacks
  onHeadingFontChange?: (font: string) => void;
  onBodyFontChange?: (font: string) => void;
  onHeadingColorChange?: (color: string) => void;
  onBodyColorChange?: (color: string) => void;
  onHeadingBoldChange?: (bold: boolean) => void;
  onHeadingItalicChange?: (italic: boolean) => void;
  onHeadingUnderlineChange?: (underline: boolean) => void;
  onBodyBoldChange?: (bold: boolean) => void;
  onBodyItalicChange?: (italic: boolean) => void;
  onBodyUnderlineChange?: (underline: boolean) => void;
  onTextAlignmentChange?: (alignment: 'left' | 'center' | 'right') => void;
  onPageBackgroundChange?: (value: string) => void;
  onPageBackgroundTypeChange?: (type: 'solid' | 'gradient' | 'image') => void;
  onPageBackgroundImageChange?: (url: string) => void;
  onPageBackgroundImageUpload?: (file: File) => void;
  onPageBackgroundImageRemove?: () => void;
}

const fontOptions = ['Inter', 'Roboto', 'Open Sans', 'Lato', 'Montserrat', 'Poppins', 'Raleway', 'Source Sans Pro'];

export function PagePropertiesToolbarDemo({
  headingFont = 'Inter',
  bodyFont = 'Inter',
  headingColor = '#111827',
  bodyColor = '#6b7280',
  headingBold = false,
  headingItalic = false,
  headingUnderline = false,
  bodyBold = false,
  bodyItalic = false,
  bodyUnderline = false,
  textAlignment = 'center',
  pageBackground = '#FFFFFF',
  pageBackgroundType = 'solid',
  pageBackgroundImage = null,
  onHeadingFontChange,
  onBodyFontChange,
  onHeadingColorChange,
  onBodyColorChange,
  onHeadingBoldChange,
  onHeadingItalicChange,
  onHeadingUnderlineChange,
  onBodyBoldChange,
  onBodyItalicChange,
  onBodyUnderlineChange,
  onTextAlignmentChange,
  onPageBackgroundChange,
  onPageBackgroundTypeChange,
  onPageBackgroundImageChange,
  onPageBackgroundImageUpload,
  onPageBackgroundImageRemove
}: PagePropertiesToolbarDemoProps): JSX.Element {
  const [localHeadingFont, setLocalHeadingFont] = useState(headingFont);
  const [localBodyFont, setLocalBodyFont] = useState(bodyFont);
  const [localHeadingColor, setLocalHeadingColor] = useState(headingColor);
  const [localBodyColor, setLocalBodyColor] = useState(bodyColor);
  const [localHeadingBold, setLocalHeadingBold] = useState(headingBold);
  const [localHeadingItalic, setLocalHeadingItalic] = useState(headingItalic);
  const [localHeadingUnderline, setLocalHeadingUnderline] = useState(headingUnderline);
  const [localBodyBold, setLocalBodyBold] = useState(bodyBold);
  const [localBodyItalic, setLocalBodyItalic] = useState(bodyItalic);
  const [localBodyUnderline, setLocalBodyUnderline] = useState(bodyUnderline);
  const [localTextAlignment, setLocalTextAlignment] = useState(textAlignment);
  const [localPageBackground, setLocalPageBackground] = useState(pageBackground);
  const [localPageBackgroundType, setLocalPageBackgroundType] = useState(pageBackgroundType);
  const [localPageBackgroundImage, setLocalPageBackgroundImage] = useState(pageBackgroundImage);

  return (
    <div className={styles.container}>
      <header className={styles.header}>
        <h1>Page Properties Toolbar Demo</h1>
        <p>Unified toolbar combining typography, colors, formatting, and background controls</p>
      </header>

      <div className={styles.content}>
        {/* Option 1: Compact Horizontal Toolbar */}
        <section className={styles.section}>
          <div className={styles.sectionHeader}>
            <h2>Option 1: Compact Horizontal Toolbar</h2>
            <p className={styles.description}>
              All controls in a single horizontal toolbar row. Space-efficient, similar to a rich text editor toolbar.
              Best for when you want everything visible at once in a compact space.
            </p>
          </div>
          <div className={styles.toolbarContainer}>
            <div className={styles.toolbar}>
              {/* Font Selectors with Color Buttons */}
              <div className={styles.toolbarGroup}>
                <label className={styles.toolbarLabel}>
                  <TextT size={16} weight="bold" />
                  <span>Heading</span>
                </label>
                <ColorButton
                  label="Heading Color"
                  value={localHeadingColor}
                  onChange={(value) => {
                    setLocalHeadingColor(value);
                    onHeadingColorChange?.(value);
                  }}
                />
                <select 
                  className={styles.toolbarSelect}
                  value={localHeadingFont}
                  onChange={(e) => {
                    setLocalHeadingFont(e.target.value);
                    onHeadingFontChange?.(e.target.value);
                  }}
                >
                  {fontOptions.map(font => (
                    <option key={font} value={font}>{font}</option>
                  ))}
                </select>
              </div>

              <div className={styles.toolbarDivider} />

              <div className={styles.toolbarGroup}>
                <label className={styles.toolbarLabel}>
                  <TextT size={16} />
                  <span>Body</span>
                </label>
                <ColorButton
                  label="Body Color"
                  value={localBodyColor}
                  onChange={(value) => {
                    setLocalBodyColor(value);
                    onBodyColorChange?.(value);
                  }}
                />
                <select 
                  className={styles.toolbarSelect}
                  value={localBodyFont}
                  onChange={(e) => {
                    setLocalBodyFont(e.target.value);
                    onBodyFontChange?.(e.target.value);
                  }}
                >
                  {fontOptions.map(font => (
                    <option key={font} value={font}>{font}</option>
                  ))}
                </select>
              </div>

              <div className={styles.toolbarDivider} />

              {/* Formatting Buttons */}
              <div className={styles.toolbarGroup}>
                <button
                  type="button"
                  className={`${styles.toolbarButton} ${localHeadingBold ? styles.toolbarButtonActive : ''}`}
                  onClick={() => {
                    setLocalHeadingBold(!localHeadingBold);
                    onHeadingBoldChange?.(!localHeadingBold);
                  }}
                  title="Bold"
                >
                  <TextB size={18} weight="bold" />
                </button>
                <button
                  type="button"
                  className={`${styles.toolbarButton} ${localHeadingItalic ? styles.toolbarButtonActive : ''}`}
                  onClick={() => {
                    setLocalHeadingItalic(!localHeadingItalic);
                    onHeadingItalicChange?.(!localHeadingItalic);
                  }}
                  title="Italic"
                >
                  <TextItalic size={18} weight="bold" />
                </button>
                <button
                  type="button"
                  className={`${styles.toolbarButton} ${localHeadingUnderline ? styles.toolbarButtonActive : ''}`}
                  onClick={() => {
                    setLocalHeadingUnderline(!localHeadingUnderline);
                    onHeadingUnderlineChange?.(!localHeadingUnderline);
                  }}
                  title="Underline"
                >
                  <TextUnderline size={18} weight="bold" />
                </button>
              </div>

              <div className={styles.toolbarDivider} />

              {/* Alignment Buttons */}
              <div className={styles.toolbarGroup}>
                <button
                  type="button"
                  className={`${styles.toolbarButton} ${localTextAlignment === 'left' ? styles.toolbarButtonActive : ''}`}
                  onClick={() => {
                    setLocalTextAlignment('left');
                    onTextAlignmentChange?.('left');
                  }}
                  title="Align Left"
                >
                  <TextAlignLeft size={18} weight="bold" />
                </button>
                <button
                  type="button"
                  className={`${styles.toolbarButton} ${localTextAlignment === 'center' ? styles.toolbarButtonActive : ''}`}
                  onClick={() => {
                    setLocalTextAlignment('center');
                    onTextAlignmentChange?.('center');
                  }}
                  title="Align Center"
                >
                  <TextAlignCenter size={18} weight="bold" />
                </button>
                <button
                  type="button"
                  className={`${styles.toolbarButton} ${localTextAlignment === 'right' ? styles.toolbarButtonActive : ''}`}
                  onClick={() => {
                    setLocalTextAlignment('right');
                    onTextAlignmentChange?.('right');
                  }}
                  title="Align Right"
                >
                  <TextAlignRight size={18} weight="bold" />
                </button>
              </div>

              <div className={styles.toolbarDivider} />

              {/* Background Picker - Icon Style */}
              <div className={styles.toolbarGroup}>
                <BackgroundButton
                  label="Page Background"
                  value={localPageBackground}
                  backgroundType={localPageBackgroundType}
                  backgroundImage={localPageBackgroundImage}
                  onValueChange={(value) => {
                    setLocalPageBackground(value);
                    onPageBackgroundChange?.(value);
                  }}
                  onTypeChange={(type) => {
                    setLocalPageBackgroundType(type);
                    onPageBackgroundTypeChange?.(type);
                  }}
                  onImageChange={(url) => {
                    setLocalPageBackgroundImage(url);
                    onPageBackgroundImageChange?.(url);
                  }}
                  onImageUpload={(file) => {
                    onPageBackgroundImageUpload?.(file);
                  }}
                />
              </div>
            </div>
          </div>
        </section>

        {/* Option 2: Grouped Toolbar with Sections */}
        <section className={styles.section}>
          <div className={styles.sectionHeader}>
            <h2>Option 2: Grouped Toolbar with Sections</h2>
            <p className={styles.description}>
              Controls organized into logical groups with visual separators. More organized but takes more vertical space.
              Better for clarity and grouping related controls together.
            </p>
          </div>
          <div className={styles.toolbarContainer}>
            <div className={styles.groupedToolbar}>
              {/* Typography Group */}
              <div className={styles.toolbarSection}>
                <div className={styles.sectionLabel}>Typography</div>
                <div className={styles.sectionControls}>
                  <div className={styles.controlPair}>
                    <label className={styles.controlLabel}>Heading</label>
                    <ColorButton
                      label="Heading Color"
                      value={localHeadingColor}
                      onChange={(value) => {
                        setLocalHeadingColor(value);
                        onHeadingColorChange?.(value);
                      }}
                    />
                    <select 
                      className={styles.controlSelect}
                      value={localHeadingFont}
                      onChange={(e) => {
                        setLocalHeadingFont(e.target.value);
                        onHeadingFontChange?.(e.target.value);
                      }}
                    >
                      {fontOptions.map(font => (
                        <option key={font} value={font}>{font}</option>
                      ))}
                    </select>
                  </div>
                  <div className={styles.controlPair}>
                    <label className={styles.controlLabel}>Body</label>
                    <ColorButton
                      label="Body Color"
                      value={localBodyColor}
                      onChange={(value) => {
                        setLocalBodyColor(value);
                        onBodyColorChange?.(value);
                      }}
                    />
                    <select 
                      className={styles.controlSelect}
                      value={localBodyFont}
                      onChange={(e) => {
                        setLocalBodyFont(e.target.value);
                        onBodyFontChange?.(e.target.value);
                      }}
                    >
                      {fontOptions.map(font => (
                        <option key={font} value={font}>{font}</option>
                      ))}
                    </select>
                  </div>
                </div>
              </div>

              <div className={styles.sectionDivider} />

              {/* Formatting Group */}
              <div className={styles.toolbarSection}>
                <div className={styles.sectionLabel}>Formatting</div>
                <div className={styles.sectionControls}>
                  <div className={styles.buttonGroup}>
                    <button
                      type="button"
                      className={`${styles.formatButton} ${localHeadingBold ? styles.formatButtonActive : ''}`}
                      onClick={() => {
                        setLocalHeadingBold(!localHeadingBold);
                        onHeadingBoldChange?.(!localHeadingBold);
                      }}
                      title="Bold"
                    >
                      <TextB size={18} weight="bold" />
                    </button>
                    <button
                      type="button"
                      className={`${styles.formatButton} ${localHeadingItalic ? styles.formatButtonActive : ''}`}
                      onClick={() => {
                        setLocalHeadingItalic(!localHeadingItalic);
                        onHeadingItalicChange?.(!localHeadingItalic);
                      }}
                      title="Italic"
                    >
                      <TextItalic size={18} weight="bold" />
                    </button>
                    <button
                      type="button"
                      className={`${styles.formatButton} ${localHeadingUnderline ? styles.formatButtonActive : ''}`}
                      onClick={() => {
                        setLocalHeadingUnderline(!localHeadingUnderline);
                        onHeadingUnderlineChange?.(!localHeadingUnderline);
                      }}
                      title="Underline"
                    >
                      <TextUnderline size={18} weight="bold" />
                    </button>
                  </div>
                </div>
              </div>

              <div className={styles.sectionDivider} />

              {/* Alignment Group */}
              <div className={styles.toolbarSection}>
                <div className={styles.sectionLabel}>Alignment</div>
                <div className={styles.sectionControls}>
                  <div className={styles.buttonGroup}>
                    <button
                      type="button"
                      className={`${styles.formatButton} ${localTextAlignment === 'left' ? styles.formatButtonActive : ''}`}
                      onClick={() => {
                        setLocalTextAlignment('left');
                        onTextAlignmentChange?.('left');
                      }}
                      title="Align Left"
                    >
                      <TextAlignLeft size={18} weight="bold" />
                    </button>
                    <button
                      type="button"
                      className={`${styles.formatButton} ${localTextAlignment === 'center' ? styles.formatButtonActive : ''}`}
                      onClick={() => {
                        setLocalTextAlignment('center');
                        onTextAlignmentChange?.('center');
                      }}
                      title="Align Center"
                    >
                      <TextAlignCenter size={18} weight="bold" />
                    </button>
                    <button
                      type="button"
                      className={`${styles.formatButton} ${localTextAlignment === 'right' ? styles.formatButtonActive : ''}`}
                      onClick={() => {
                        setLocalTextAlignment('right');
                        onTextAlignmentChange?.('right');
                      }}
                      title="Align Right"
                    >
                      <TextAlignRight size={18} weight="bold" />
                    </button>
                  </div>
                </div>
              </div>

              <div className={styles.sectionDivider} />

              {/* Background Group */}
              <div className={styles.toolbarSection}>
                <div className={styles.sectionLabel}>Background</div>
                <div className={styles.sectionControls}>
                  <BackgroundButton
                    label="Page Background"
                    value={localPageBackground}
                    backgroundType={localPageBackgroundType}
                    backgroundImage={localPageBackgroundImage}
                    onValueChange={(value) => {
                      setLocalPageBackground(value);
                      onPageBackgroundChange?.(value);
                    }}
                    onTypeChange={(type) => {
                      setLocalPageBackgroundType(type);
                      onPageBackgroundTypeChange?.(type);
                    }}
                    onImageChange={(url) => {
                      setLocalPageBackgroundImage(url);
                      onPageBackgroundImageChange?.(url);
                    }}
                    onImageUpload={(file) => {
                      onPageBackgroundImageUpload?.(file);
                    }}
                    variant="full"
                  />
                </div>
              </div>
            </div>
          </div>
        </section>

        {/* Option 3: Vertical Stack with Expandable Sections */}
        <section className={styles.section}>
          <div className={styles.sectionHeader}>
            <h2>Option 3: Vertical Stack with Expandable Sections</h2>
            <p className={styles.description}>
              Controls organized vertically with collapsible sections. Most space-efficient when collapsed,
              but requires expansion to access controls. Best for when you want to minimize vertical space.
            </p>
          </div>
          <div className={styles.toolbarContainer}>
            <div className={styles.verticalToolbar}>
              {/* Typography Section */}
              <details className={styles.expandableSection} open>
                <summary className={styles.sectionSummary}>
                  <TextT size={18} weight="bold" />
                  <span>Typography</span>
                </summary>
                <div className={styles.sectionContent}>
                  <div className={styles.controlRow}>
                    <label>Heading Font</label>
                    <select 
                      className={styles.controlSelect}
                      value={localHeadingFont}
                      onChange={(e) => {
                        setLocalHeadingFont(e.target.value);
                        onHeadingFontChange?.(e.target.value);
                      }}
                    >
                      {fontOptions.map(font => (
                        <option key={font} value={font}>{font}</option>
                      ))}
                    </select>
                  </div>
                  <div className={styles.controlRow}>
                    <label>Body Font</label>
                    <select 
                      className={styles.controlSelect}
                      value={localBodyFont}
                      onChange={(e) => {
                        setLocalBodyFont(e.target.value);
                        onBodyFontChange?.(e.target.value);
                      }}
                    >
                      {fontOptions.map(font => (
                        <option key={font} value={font}>{font}</option>
                      ))}
                    </select>
                  </div>
                  <div className={styles.controlRow}>
                    <label>Heading Color</label>
                    <ColorButton
                      label="Heading Color"
                      value={localHeadingColor}
                      onChange={(value) => {
                        setLocalHeadingColor(value);
                        onHeadingColorChange?.(value);
                      }}
                    />
                  </div>
                  <div className={styles.controlRow}>
                    <label>Body Color</label>
                    <ColorButton
                      label="Body Color"
                      value={localBodyColor}
                      onChange={(value) => {
                        setLocalBodyColor(value);
                        onBodyColorChange?.(value);
                      }}
                    />
                  </div>
                </div>
              </details>

              {/* Formatting Section */}
              <details className={styles.expandableSection} open>
                <summary className={styles.sectionSummary}>
                  <TextB size={18} weight="bold" />
                  <span>Formatting & Alignment</span>
                </summary>
                <div className={styles.sectionContent}>
                  <div className={styles.controlRow}>
                    <label>Text Formatting</label>
                    <div className={styles.buttonGroup}>
                      <button
                        type="button"
                        className={`${styles.formatButton} ${localHeadingBold ? styles.formatButtonActive : ''}`}
                        onClick={() => {
                          setLocalHeadingBold(!localHeadingBold);
                          onHeadingBoldChange?.(!localHeadingBold);
                        }}
                        title="Bold"
                      >
                        <TextB size={18} weight="bold" />
                      </button>
                      <button
                        type="button"
                        className={`${styles.formatButton} ${localHeadingItalic ? styles.formatButtonActive : ''}`}
                        onClick={() => {
                          setLocalHeadingItalic(!localHeadingItalic);
                          onHeadingItalicChange?.(!localHeadingItalic);
                        }}
                        title="Italic"
                      >
                        <TextItalic size={18} weight="bold" />
                      </button>
                      <button
                        type="button"
                        className={`${styles.formatButton} ${localHeadingUnderline ? styles.formatButtonActive : ''}`}
                        onClick={() => {
                          setLocalHeadingUnderline(!localHeadingUnderline);
                          onHeadingUnderlineChange?.(!localHeadingUnderline);
                        }}
                        title="Underline"
                      >
                        <TextUnderline size={18} weight="bold" />
                      </button>
                    </div>
                  </div>
                  <div className={styles.controlRow}>
                    <label>Text Alignment</label>
                    <div className={styles.buttonGroup}>
                      <button
                        type="button"
                        className={`${styles.formatButton} ${localTextAlignment === 'left' ? styles.formatButtonActive : ''}`}
                        onClick={() => {
                          setLocalTextAlignment('left');
                          onTextAlignmentChange?.('left');
                        }}
                        title="Align Left"
                      >
                        <TextAlignLeft size={18} weight="bold" />
                      </button>
                      <button
                        type="button"
                        className={`${styles.formatButton} ${localTextAlignment === 'center' ? styles.formatButtonActive : ''}`}
                        onClick={() => {
                          setLocalTextAlignment('center');
                          onTextAlignmentChange?.('center');
                        }}
                        title="Align Center"
                      >
                        <TextAlignCenter size={18} weight="bold" />
                      </button>
                      <button
                        type="button"
                        className={`${styles.formatButton} ${localTextAlignment === 'right' ? styles.formatButtonActive : ''}`}
                        onClick={() => {
                          setLocalTextAlignment('right');
                          onTextAlignmentChange?.('right');
                        }}
                        title="Align Right"
                      >
                        <TextAlignRight size={18} weight="bold" />
                      </button>
                    </div>
                  </div>
                </div>
              </details>

              {/* Background Section */}
              <details className={styles.expandableSection} open>
                <summary className={styles.sectionSummary}>
                  <Palette size={18} weight="bold" />
                  <span>Page Background</span>
                </summary>
                <div className={styles.sectionContent}>
                  <div className={styles.controlRow}>
                    <label>Page Background</label>
                    <BackgroundButton
                      label="Page Background"
                      value={localPageBackground}
                      backgroundType={localPageBackgroundType}
                      backgroundImage={localPageBackgroundImage}
                      onValueChange={(value) => {
                        setLocalPageBackground(value);
                        onPageBackgroundChange?.(value);
                      }}
                      onTypeChange={(type) => {
                        setLocalPageBackgroundType(type);
                        onPageBackgroundTypeChange?.(type);
                      }}
                      onImageChange={(url) => {
                        setLocalPageBackgroundImage(url);
                        onPageBackgroundImageChange?.(url);
                      }}
                      onImageUpload={(file) => {
                        onPageBackgroundImageUpload?.(file);
                      }}
                      variant="full"
                    />
                  </div>
                </div>
              </details>
            </div>
          </div>
        </section>
      </div>
    </div>
  );
}

// Compact Color Button Component - Icon style like formatting buttons
interface ColorButtonProps {
  label: string;
  value: string;
  onChange: (value: string) => void;
}

function ColorButton({ label, value, onChange }: ColorButtonProps): JSX.Element {
  const [open, setOpen] = useState(false);
  const [tempColor, setTempColor] = useState(value);
  const popoverRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    setTempColor(value);
  }, [value]);

  // Extract hex color from value (handle gradients by using first color)
  const getHexColor = (colorValue: string): string => {
    if (colorValue.startsWith('#')) {
      return colorValue;
    }
    // Try to extract hex from gradient
    const hexMatch = colorValue.match(/#[0-9a-fA-F]{6}/i);
    if (hexMatch) {
      return hexMatch[0];
    }
    return '#111827'; // Default
  };

  const hexColor = getHexColor(value);
  const isGradient = value.includes('gradient');

  const handleColorChange = (newColor: string) => {
    setTempColor(newColor);
    onChange(newColor);
  };

  const handleOpenChange = (isOpen: boolean) => {
    setOpen(isOpen);
    if (!isOpen) {
      setTempColor(value);
    }
  };

  return (
    <Popover.Root open={open} onOpenChange={handleOpenChange}>
      <Popover.Trigger asChild>
        <button
          type="button"
          className={styles.colorButton}
          title={label}
          aria-label={label}
        >
          <div className={styles.colorButtonIcon}>
            <TextT size={16} weight="bold" />
            <div 
              className={styles.colorButtonSwatch}
              style={{ 
                backgroundColor: hexColor,
                backgroundImage: isGradient ? value : undefined
              }}
            />
            <div 
              className={styles.colorButtonChip}
              style={{ 
                backgroundColor: hexColor,
                backgroundImage: isGradient ? value : undefined
              }}
            />
          </div>
        </button>
      </Popover.Trigger>
      <Popover.Portal>
        <Popover.Content
          ref={popoverRef}
          className={styles.colorPopover}
          sideOffset={5}
          align="start"
        >
          <div className={styles.colorPopoverContent}>
            <HexColorPicker
              color={hexColor}
              onChange={handleColorChange}
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

// Background Button Component - Icon style with swatch
interface BackgroundButtonProps {
  label: string;
  value: string;
  backgroundType: 'solid' | 'gradient' | 'image';
  backgroundImage?: string | null;
  onValueChange: (value: string) => void;
  onTypeChange: (type: 'solid' | 'gradient' | 'image') => void;
  onImageChange?: (url: string) => void;
  onImageUpload?: (file: File) => void;
  variant?: 'compact' | 'full';
}

function BackgroundButton({ 
  label, 
  value, 
  backgroundType,
  backgroundImage,
  onValueChange, 
  onTypeChange,
  onImageChange,
  onImageUpload,
  variant = 'compact'
}: BackgroundButtonProps): JSX.Element {
  const [open, setOpen] = useState(false);
  const popoverRef = useRef<HTMLDivElement>(null);

  // Get display color/swatch for the button
  const getDisplayValue = (): string => {
    if (backgroundType === 'image' && backgroundImage) {
      return backgroundImage;
    }
    if (backgroundType === 'gradient') {
      return value;
    }
    // Solid color
    if (value.startsWith('#')) {
      return value;
    }
    // Try to extract hex from gradient
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
          className={styles.colorButton}
          title={label}
          aria-label={label}
        >
          <div className={styles.colorButtonIcon}>
            <Palette size={18} weight="bold" />
            <div 
              className={styles.colorButtonSwatch}
              style={{ 
                backgroundColor: isImage ? 'transparent' : (isGradient ? 'transparent' : displayValue),
                backgroundImage: isGradient || isImage ? (isImage ? `url(${displayValue})` : displayValue) : undefined,
                backgroundSize: isImage ? 'cover' : undefined,
                backgroundPosition: isImage ? 'center' : undefined
              }}
            />
            <div 
              className={styles.colorButtonChip}
              style={{ 
                backgroundColor: isImage ? 'transparent' : (isGradient ? 'transparent' : displayValue),
                backgroundImage: isGradient || isImage ? (isImage ? `url(${displayValue})` : displayValue) : undefined,
                backgroundSize: isImage ? 'cover' : undefined,
                backgroundPosition: isImage ? 'center' : undefined
              }}
            />
          </div>
        </button>
      </Popover.Trigger>
      <Popover.Portal>
        <Popover.Content
          ref={popoverRef}
          className={styles.backgroundPopover}
          sideOffset={5}
          align="start"
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
                        if (file) onImageUpload?.(file);
                      }}
                      className={styles.fileInput}
                    />
                  </div>
                ) : (
                  <PageBackgroundPicker
                    value={value}
                    onChange={onValueChange}
                    mode={backgroundType}
                    hidePresets={variant === 'compact'}
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

