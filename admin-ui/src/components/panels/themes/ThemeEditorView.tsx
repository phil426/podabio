/**
 * Theme Editor View
 * Edit theme settings with live preview
 */

import { useState } from 'react';
import { ArrowLeft, FloppyDisk } from '@phosphor-icons/react';
import * as Tooltip from '@radix-ui/react-tooltip';
import type { ThemeRecord } from '../../../api/types';
import type { TabColorTheme } from '../../layout/tab-colors';
import { sectionRegistry } from './utils/sectionRegistry';
import { PageCustomizationSection } from './sections/PageCustomizationSection';
import { WidgetButtonSection } from './sections/WidgetButtonSection';
import { WidgetTextSection } from './sections/WidgetTextSection';
import { SocialIconsSection } from './sections/SocialIconsSection';
import { ThemePreview } from './preview/ThemePreview';
import styles from './theme-editor-view.module.css';

interface ThemeEditorViewProps {
  theme: ThemeRecord | null;
  uiState: Record<string, unknown>;
  onFieldChange: (fieldId: string, value: unknown) => void;
  onSave: () => void;
  onBack: () => void;
  isSaving: boolean;
  previewCSSVars: Record<string, string>;
  activeColor: TabColorTheme;
}

export function ThemeEditorView({
  theme,
  uiState,
  onFieldChange,
  onSave,
  onBack,
  isSaving,
  previewCSSVars,
  activeColor
}: ThemeEditorViewProps): JSX.Element {
  const [expandedSections, setExpandedSections] = useState<Set<string>>(
    new Set(['page-customization', 'widget-buttons', 'widget-text', 'social-icons'])
  );

  const toggleSection = (sectionId: string) => {
    setExpandedSections(prev => {
      const next = new Set(prev);
      if (next.has(sectionId)) {
        next.delete(sectionId);
      } else {
        next.add(sectionId);
      }
      return next;
    });
  };

  const handleHotspotClick = (sectionId: string) => {
    // Expand the section if it's collapsed
    if (!expandedSections.has(sectionId)) {
      toggleSection(sectionId);
    }
    
    // Scroll to the section after a brief delay to allow expansion
    setTimeout(() => {
      const sectionElement = document.querySelector(`[data-section-id="${sectionId}"]`);
      if (sectionElement) {
        sectionElement.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        // Highlight the section briefly
        sectionElement.classList.add('highlight');
        setTimeout(() => {
          sectionElement.classList.remove('highlight');
        }, 1000);
      }
    }, 100);
  };

  const sections = sectionRegistry.getAllSections();

  return (
    <div className={styles.container}>
      {/* Left Panel: Settings */}
      <div className={styles.settingsPanel}>
        <header className={styles.header}>
          <button
            type="button"
            className={styles.backButton}
            onClick={onBack}
            aria-label="Back to theme library"
          >
            <ArrowLeft aria-hidden="true" size={20} weight="regular" />
          </button>
          <div className={styles.headerContent}>
            <h2>{theme?.name || 'New Theme'}</h2>
            <p>Customize your theme settings</p>
          </div>
        </header>
        
        {/* Fixed Save Button - top right corner of left rail */}
        <div className={styles.saveButtonContainer}>
          <Tooltip.Provider delayDuration={200}>
            <Tooltip.Root>
              <Tooltip.Trigger asChild>
                <button
                  type="button"
                  className={styles.saveButton}
                  onClick={onSave}
                  disabled={isSaving || !theme}
                >
                  <FloppyDisk aria-hidden="true" size={16} weight="regular" />
                  {isSaving ? 'Saving...' : 'Save'}
                </button>
              </Tooltip.Trigger>
              <Tooltip.Portal>
                <Tooltip.Content
                  side="bottom"
                  align="end"
                  className={styles.tooltip}
                >
                  Save your theme changes
                  <Tooltip.Arrow className={styles.tooltipArrow} />
                </Tooltip.Content>
              </Tooltip.Portal>
            </Tooltip.Root>
          </Tooltip.Provider>
        </div>

        <div className={styles.sections}>
          {sections.map(section => {
            const isExpanded = expandedSections.has(section.id);
            
            return (
              <div key={section.id} className={styles.sectionWrapper} data-section-id={section.id}>
                <button
                  type="button"
                  className={styles.sectionHeader}
                  onClick={() => toggleSection(section.id)}
                  aria-expanded={isExpanded}
                >
                  <span className={styles.sectionTitle}>{section.title}</span>
                  <span className={styles.sectionToggle}>
                    {isExpanded ? '−' : '+'}
                  </span>
                </button>
                
                {isExpanded && (
                  <div className={styles.sectionContent}>
                    {section.id === 'page-customization' && (
                      <PageCustomizationSection
                        uiState={uiState}
                        onFieldChange={onFieldChange}
                        activeColor={activeColor}
                      />
                    )}
                    {section.id === 'widget-buttons' && (
                      <WidgetButtonSection
                        uiState={uiState}
                        onFieldChange={onFieldChange}
                        activeColor={activeColor}
                      />
                    )}
                    {section.id === 'widget-text' && (
                      <WidgetTextSection
                        uiState={uiState}
                        onFieldChange={onFieldChange}
                        activeColor={activeColor}
                      />
                    )}
                    {section.id === 'social-icons' && (
                      <SocialIconsSection
                        uiState={uiState}
                        onFieldChange={onFieldChange}
                        activeColor={activeColor}
                      />
                    )}
                  </div>
                )}
              </div>
            );
          })}
        </div>
      </div>

          {/* Right Panel: Preview */}
          <div className={styles.previewPanel}>
            <ThemePreview cssVars={previewCSSVars} onHotspotClick={handleHotspotClick} />
          </div>
    </div>
  );
}

