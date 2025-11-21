import * as Tabs from '@radix-ui/react-tabs';
import { Type, TextB, TextItalic, TextUnderline, TextAlignLeft, TextAlignCenter, TextAlignRight } from '@phosphor-icons/react';
import { PageBackgroundPicker } from '../../controls/PageBackgroundPicker';
import profileStyles from '../profile-inspector.module.css';
import styles from '../theme-editor-panel.module.css';

interface TypographySectionProps {
  headingFont: string;
  bodyFont: string;
  headingFontSizePreset: 'small' | 'medium' | 'large' | 'xlarge';
  bodyFontSizePreset: 'small' | 'medium' | 'large' | 'xlarge';
  headingBold: boolean;
  headingItalic: boolean;
  headingUnderline: boolean;
  headingAlignment: 'left' | 'center' | 'right';
  bodyBold: boolean;
  bodyItalic: boolean;
  bodyUnderline: boolean;
  bodyAlignment: 'left' | 'center' | 'right';
  colorMode: 'solid' | 'gradient';
  headingColor: string;
  bodyColor: string;
  fontOptions: string[];
  onFontChange: (path: string, value: string) => void;
  onHeadingPresetChange: (preset: 'small' | 'medium' | 'large' | 'xlarge') => void;
  onBodyPresetChange: (preset: 'small' | 'medium' | 'large' | 'xlarge') => void;
  onHeadingBoldChange: (bold: boolean) => void;
  onHeadingItalicChange: (italic: boolean) => void;
  onHeadingUnderlineChange: (underline: boolean) => void;
  onHeadingAlignmentChange: (alignment: 'left' | 'center' | 'right') => void;
  onBodyBoldChange: (bold: boolean) => void;
  onBodyItalicChange: (italic: boolean) => void;
  onBodyUnderlineChange: (underline: boolean) => void;
  onBodyAlignmentChange: (alignment: 'left' | 'center' | 'right') => void;
  onColorModeChange: (mode: 'solid' | 'gradient') => void;
  onHeadingColorChange: (value: string) => void;
  onBodyColorChange: (value: string) => void;
  headingId?: string;
  bodyId?: string;
}

export function TypographySection({
  headingFont,
  bodyFont,
  headingFontSizePreset,
  bodyFontSizePreset,
  headingBold,
  headingItalic,
  headingUnderline,
  headingAlignment,
  bodyBold,
  bodyItalic,
  bodyUnderline,
  bodyAlignment,
  colorMode,
  headingColor,
  bodyColor,
  fontOptions,
  onFontChange,
  onHeadingPresetChange,
  onBodyPresetChange,
  onHeadingBoldChange,
  onHeadingItalicChange,
  onHeadingUnderlineChange,
  onHeadingAlignmentChange,
  onBodyBoldChange,
  onBodyItalicChange,
  onBodyUnderlineChange,
  onBodyAlignmentChange,
  onColorModeChange,
  onHeadingColorChange,
  onBodyColorChange,
  headingId = 'heading-font',
  bodyId = 'body-font'
}: TypographySectionProps): JSX.Element {
  return (
    <Tabs.Root defaultValue="font" className={styles.themeTabs}>
      <Tabs.List className={styles.themeTabList}>
        <Tabs.Trigger value="font" className={styles.themeTabTrigger}>
          Font
        </Tabs.Trigger>
        <Tabs.Trigger value="colors" className={styles.themeTabTrigger}>
          Colors
        </Tabs.Trigger>
      </Tabs.List>
      
      <Tabs.Content value="font" className={styles.themeTabContent}>
        {/* Heading Font Section */}
        <div className={profileStyles.nameSection}>
          <div className={profileStyles.nameHeader}>
            <label htmlFor={headingId}>Heading Font</label>
          </div>
          <div className={profileStyles.nameEditor}>
            <select
              id={headingId}
              value={headingFont}
              onChange={(e) => onFontChange('core.typography.font.heading', e.target.value)}
              style={{
                width: '100%',
                marginBottom: '0.75rem',
                padding: '0.5rem 0.75rem',
                border: '1px solid rgba(15, 23, 42, 0.12)',
                borderRadius: '6px',
                background: 'rgba(255, 255, 255, 0.8)',
                fontSize: '0.85rem',
                color: 'var(--pod-semantic-text-primary, #111827)',
                cursor: 'pointer'
              }}
            >
              {fontOptions.map((font) => (
                <option key={font} value={font}>
                  {font}
                </option>
              ))}
            </select>
            
            {/* Formatting Toolbar */}
            <div className={profileStyles.formatToolbar}>
              <button
                type="button"
                className={`${profileStyles.formatButton} ${headingBold ? profileStyles.formatButtonActive : ''}`}
                onClick={() => onHeadingBoldChange(!headingBold)}
                title="Bold"
              >
                <LuBold aria-hidden="true" />
              </button>
              <button
                type="button"
                className={`${profileStyles.formatButton} ${headingItalic ? profileStyles.formatButtonActive : ''}`}
                onClick={() => onHeadingItalicChange(!headingItalic)}
                title="Italic"
              >
                <TextItalic aria-hidden="true" size={16} weight="regular" />
              </button>
              <button
                type="button"
                className={`${profileStyles.formatButton} ${headingUnderline ? profileStyles.formatButtonActive : ''}`}
                onClick={() => onHeadingUnderlineChange(!headingUnderline)}
                title="Underline"
              >
                <TextUnderline aria-hidden="true" size={16} weight="regular" />
              </button>
              <div className={profileStyles.formatDivider} />
              <button
                type="button"
                className={`${profileStyles.formatButton} ${headingAlignment === 'left' ? profileStyles.formatButtonActive : ''}`}
                onClick={() => onHeadingAlignmentChange('left')}
                title="Align left"
              >
                <TextAlignLeft aria-hidden="true" size={16} weight="regular" />
              </button>
              <button
                type="button"
                className={`${profileStyles.formatButton} ${headingAlignment === 'center' ? profileStyles.formatButtonActive : ''}`}
                onClick={() => onHeadingAlignmentChange('center')}
                title="Align center"
              >
                <TextAlignCenter aria-hidden="true" size={16} weight="regular" />
              </button>
              <button
                type="button"
                className={`${profileStyles.formatButton} ${headingAlignment === 'right' ? profileStyles.formatButtonActive : ''}`}
                onClick={() => onHeadingAlignmentChange('right')}
                title="Align right"
              >
                <TextAlignRight aria-hidden="true" size={16} weight="regular" />
              </button>
            </div>
            
            {/* Size Buttons */}
            <div className={profileStyles.textSizeGroup} style={{ marginTop: '0.75rem' }}>
              <button
                type="button"
                className={`${profileStyles.textSizeButton} ${headingFontSizePreset === 'small' ? profileStyles.textSizeButtonActive : ''}`}
                onClick={() => onHeadingPresetChange('small')}
                title="Small"
              >
                <span className={profileStyles.textSizeLabel}>S</span>
              </button>
              <button
                type="button"
                className={`${profileStyles.textSizeButton} ${headingFontSizePreset === 'medium' ? profileStyles.textSizeButtonActive : ''}`}
                onClick={() => onHeadingPresetChange('medium')}
                title="Medium"
              >
                <span className={profileStyles.textSizeLabel}>M</span>
              </button>
              <button
                type="button"
                className={`${profileStyles.textSizeButton} ${headingFontSizePreset === 'large' ? profileStyles.textSizeButtonActive : ''}`}
                onClick={() => onHeadingPresetChange('large')}
                title="Large"
              >
                <span className={profileStyles.textSizeLabel}>L</span>
              </button>
              <button
                type="button"
                className={`${profileStyles.textSizeButton} ${headingFontSizePreset === 'xlarge' ? profileStyles.textSizeButtonActive : ''}`}
                onClick={() => onHeadingPresetChange('xlarge')}
                title="Extra Large"
              >
                <span className={profileStyles.textSizeLabel}>XL</span>
              </button>
            </div>
          </div>
        </div>

        {/* Body Font Section */}
        <div className={profileStyles.nameSection} style={{ marginTop: '1rem' }}>
          <div className={profileStyles.nameHeader}>
            <label htmlFor={bodyId}>Body Font</label>
          </div>
          <div className={profileStyles.nameEditor}>
            <select
              id={bodyId}
              value={bodyFont}
              onChange={(e) => onFontChange('core.typography.font.body', e.target.value)}
              style={{
                width: '100%',
                marginBottom: '0.75rem',
                padding: '0.5rem 0.75rem',
                border: '1px solid rgba(15, 23, 42, 0.12)',
                borderRadius: '6px',
                background: 'rgba(255, 255, 255, 0.8)',
                fontSize: '0.85rem',
                color: 'var(--pod-semantic-text-primary, #111827)',
                cursor: 'pointer'
              }}
            >
              {fontOptions.map((font) => (
                <option key={font} value={font}>
                  {font}
                </option>
              ))}
            </select>
            
            {/* Formatting Toolbar */}
            <div className={profileStyles.formatToolbar}>
              <button
                type="button"
                className={`${profileStyles.formatButton} ${bodyBold ? profileStyles.formatButtonActive : ''}`}
                onClick={() => onBodyBoldChange(!bodyBold)}
                title="Bold"
              >
                <LuBold aria-hidden="true" />
              </button>
              <button
                type="button"
                className={`${profileStyles.formatButton} ${bodyItalic ? profileStyles.formatButtonActive : ''}`}
                onClick={() => onBodyItalicChange(!bodyItalic)}
                title="Italic"
              >
                <TextItalic aria-hidden="true" size={16} weight="regular" />
              </button>
              <button
                type="button"
                className={`${profileStyles.formatButton} ${bodyUnderline ? profileStyles.formatButtonActive : ''}`}
                onClick={() => onBodyUnderlineChange(!bodyUnderline)}
                title="Underline"
              >
                <TextUnderline aria-hidden="true" size={16} weight="regular" />
              </button>
              <div className={profileStyles.formatDivider} />
              <button
                type="button"
                className={`${profileStyles.formatButton} ${bodyAlignment === 'left' ? profileStyles.formatButtonActive : ''}`}
                onClick={() => onBodyAlignmentChange('left')}
                title="Align left"
              >
                <TextAlignLeft aria-hidden="true" size={16} weight="regular" />
              </button>
              <button
                type="button"
                className={`${profileStyles.formatButton} ${bodyAlignment === 'center' ? profileStyles.formatButtonActive : ''}`}
                onClick={() => onBodyAlignmentChange('center')}
                title="Align center"
              >
                <TextAlignCenter aria-hidden="true" size={16} weight="regular" />
              </button>
              <button
                type="button"
                className={`${profileStyles.formatButton} ${bodyAlignment === 'right' ? profileStyles.formatButtonActive : ''}`}
                onClick={() => onBodyAlignmentChange('right')}
                title="Align right"
              >
                <TextAlignRight aria-hidden="true" size={16} weight="regular" />
              </button>
            </div>
            
            {/* Size Buttons */}
            <div className={profileStyles.textSizeGroup} style={{ marginTop: '0.75rem' }}>
              <button
                type="button"
                className={`${profileStyles.textSizeButton} ${bodyFontSizePreset === 'small' ? profileStyles.textSizeButtonActive : ''}`}
                onClick={() => onBodyPresetChange('small')}
                title="Small"
              >
                <span className={profileStyles.textSizeLabel}>S</span>
              </button>
              <button
                type="button"
                className={`${profileStyles.textSizeButton} ${bodyFontSizePreset === 'medium' ? profileStyles.textSizeButtonActive : ''}`}
                onClick={() => onBodyPresetChange('medium')}
                title="Medium"
              >
                <span className={profileStyles.textSizeLabel}>M</span>
              </button>
              <button
                type="button"
                className={`${profileStyles.textSizeButton} ${bodyFontSizePreset === 'large' ? profileStyles.textSizeButtonActive : ''}`}
                onClick={() => onBodyPresetChange('large')}
                title="Large"
              >
                <span className={profileStyles.textSizeLabel}>L</span>
              </button>
              <button
                type="button"
                className={`${profileStyles.textSizeButton} ${bodyFontSizePreset === 'xlarge' ? profileStyles.textSizeButtonActive : ''}`}
                onClick={() => onBodyPresetChange('xlarge')}
                title="Extra Large"
              >
                <span className={profileStyles.textSizeLabel}>XL</span>
              </button>
            </div>
          </div>
        </div>
      </Tabs.Content>

      <Tabs.Content value="colors" className={styles.themeTabContent}>
        <div className={styles.typographySection}>
          <div className={styles.controlGroup}>
            <span className={styles.controlLabel}>Text color mode</span>
            <div className={styles.segmentedControl}>
              <button
                type="button"
                className={`${styles.segmentedButton} ${colorMode === 'solid' ? styles.segmentedButtonActive : ''}`}
                onClick={() => onColorModeChange('solid')}
              >
                Solid
              </button>
              <button
                type="button"
                className={`${styles.segmentedButton} ${colorMode === 'gradient' ? styles.segmentedButtonActive : ''}`}
                onClick={() => onColorModeChange('gradient')}
              >
                Gradient
              </button>
            </div>
          </div>

          <div className={styles.controlGroup}>
            <span className={styles.controlLabel}>Heading color</span>
            <PageBackgroundPicker
              value={headingColor}
              onChange={onHeadingColorChange}
              mode={colorMode}
              presetsOnly={false}
            />
          </div>

          <div className={styles.controlGroup}>
            <span className={styles.controlLabel}>Body color</span>
            <PageBackgroundPicker
              value={bodyColor}
              onChange={onBodyColorChange}
              mode={colorMode}
              presetsOnly={false}
            />
          </div>
        </div>
      </Tabs.Content>
    </Tabs.Root>
  );
}

