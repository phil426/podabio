/**
 * Theme Property Drawer
 * Centered modal that displays property panels when hotspots are clicked
 */

import { X } from '@phosphor-icons/react';
import * as Dialog from '@radix-ui/react-dialog';
import type { ThemeRecord } from '../../../api/types';
import type { TabColorTheme } from '../../layout/tab-colors';
import { PageBackgroundSection } from './sections/PageBackgroundSection';
import { ProfileImageSection } from './sections/ProfileImageSection';
import { PageTitleSection } from './sections/PageTitleSection';
import { PageDescriptionSection } from './sections/PageDescriptionSection';
import { PodcastPlayerBarSection } from './sections/PodcastPlayerBarSection';
import { PageCustomizationSection } from './sections/PageCustomizationSection';
import { WidgetButtonSection } from './sections/WidgetButtonSection';
import { WidgetTextSection } from './sections/WidgetTextSection';
import { WidgetSettingsSection } from './sections/WidgetSettingsSection';
import { SocialIconsSection } from './sections/SocialIconsSection';
import { sectionRegistry } from './utils/sectionRegistry';
import { ModalPreview } from './preview/ModalPreview';
import styles from './theme-property-drawer.module.css';

interface ThemePropertyDrawerProps {
  isOpen: boolean;
  sectionId: string | null;
  onClose: () => void;
  theme: ThemeRecord | null;
  uiState: Record<string, unknown>;
  onFieldChange: (fieldId: string, value: unknown) => void;
  activeColor: TabColorTheme;
}

export function ThemePropertyDrawer({
  isOpen,
  sectionId,
  onClose,
  theme,
  uiState,
  onFieldChange,
  activeColor
}: ThemePropertyDrawerProps): JSX.Element {
  const section = sectionId ? sectionRegistry.get(sectionId) : null;

  if (!section) {
    return <></>;
  }

  return (
    <Dialog.Root open={isOpen} onOpenChange={(open) => !open && onClose()} modal={true}>
      <Dialog.Portal>
        <Dialog.Overlay 
          className={styles.overlay}
          onClick={(e) => {
            // Only close if clicking directly on overlay (not on Popover above it)
            if (e.target === e.currentTarget) {
              onClose();
            }
          }}
        />
        <Dialog.Content 
          className={styles.modal} 
          aria-label={section.title}
          onPointerDownOutside={(e) => {
            const target = e.target as HTMLElement;
            // Check if the click is inside a Popover Portal or any interactive element
            const isInPopover = target.closest('[data-radix-popover-content]') ||
                               target.closest('[data-radix-portal]') ||
                               target.closest('[class*="backgroundPopover"]') ||
                               target.closest('[class*="react-colorful"]') ||
                               target.closest('[data-radix-slider-thumb]') ||
                               target.closest('[data-radix-slider-track]') ||
                               target.closest('[data-radix-slider-root]');
            
            // If inside a Popover or slider, prevent dialog from closing
            // Radix UI Dialog requires e.preventDefault() to prevent closing
            if (isInPopover) {
              e.preventDefault();
              return;
            }
            
            // Only prevent if truly outside both Dialog and Popover
            // Closing is handled by overlay click
          }}
          onInteractOutside={(e) => {
            const target = e.target as HTMLElement;
            // Check if the interaction is inside a Popover Portal or any interactive element
            const isInPopover = target.closest('[data-radix-popover-content]') ||
                               target.closest('[data-radix-portal]') ||
                               target.closest('[class*="backgroundPopover"]') ||
                               target.closest('[class*="react-colorful"]') ||
                               target.closest('[data-radix-slider-thumb]') ||
                               target.closest('[data-radix-slider-track]') ||
                               target.closest('[data-radix-slider-root]');
            
            // If inside a Popover or slider, prevent dialog from closing
            // Radix UI Dialog requires e.preventDefault() to prevent closing
            if (isInPopover) {
              e.preventDefault();
              return;
            }
            
            // Only prevent if truly outside both Dialog and Popover
          }}
        >
          <header className={styles.header}>
            <div className={styles.headerContent}>
              <Dialog.Title className={styles.title}>{section.title}</Dialog.Title>
              {section.description && (
                <Dialog.Description className={styles.description}>
                  {section.description}
                </Dialog.Description>
              )}
              <ModalPreview 
                sectionId={sectionId}
                theme={theme}
                uiState={uiState}
              />
            </div>
            <Dialog.Close asChild>
              <button
                type="button"
                className={styles.closeButton}
                aria-label="Close modal"
              >
                <X aria-hidden="true" size={20} weight="regular" />
              </button>
            </Dialog.Close>
          </header>

          <div className={styles.body}>
            {sectionId === 'page-background' && (
              <PageBackgroundSection
                uiState={uiState}
                onFieldChange={onFieldChange}
                activeColor={activeColor}
              />
            )}
            {sectionId === 'profile-image' && (
              <ProfileImageSection
                uiState={uiState}
                onFieldChange={onFieldChange}
                activeColor={activeColor}
              />
            )}
            {sectionId === 'page-title' && (
              <PageTitleSection
                uiState={uiState}
                onFieldChange={onFieldChange}
                activeColor={activeColor}
              />
            )}
            {sectionId === 'page-description' && (
              <PageDescriptionSection
                uiState={uiState}
                onFieldChange={onFieldChange}
                activeColor={activeColor}
              />
            )}
            {sectionId === 'podcast-player-bar' && (
              <PodcastPlayerBarSection
                uiState={uiState}
                onFieldChange={onFieldChange}
                activeColor={activeColor}
              />
            )}
            {sectionId === 'page-customization' && (
              <PageCustomizationSection
                uiState={uiState}
                onFieldChange={onFieldChange}
                activeColor={activeColor}
              />
            )}
            {sectionId === 'widget-settings' && (
              <WidgetSettingsSection
                uiState={uiState}
                onFieldChange={onFieldChange}
                activeColor={activeColor}
              />
            )}
            {sectionId === 'widget-buttons' && (
              <WidgetButtonSection
                uiState={uiState}
                onFieldChange={onFieldChange}
                activeColor={activeColor}
              />
            )}
            {sectionId === 'widget-text' && (
              <WidgetTextSection
                uiState={uiState}
                onFieldChange={onFieldChange}
                activeColor={activeColor}
              />
            )}
            {sectionId === 'social-icons' && (
              <SocialIconsSection
                uiState={uiState}
                onFieldChange={onFieldChange}
                activeColor={activeColor}
              />
            )}
          </div>
        </Dialog.Content>
      </Dialog.Portal>
    </Dialog.Root>
  );
}

