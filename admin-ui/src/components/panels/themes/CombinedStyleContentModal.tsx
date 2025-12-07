/**
 * Combined Style and Content Modal
 * Modal with tabs for editing both style and content of page elements
 */

import { useState, useEffect } from 'react';
import { X, Palette, Pencil } from '@phosphor-icons/react';
import * as Dialog from '@radix-ui/react-dialog';
import type { ThemeRecord } from '../../../api/types';
import type { TabColorTheme } from '../../layout/tab-colors';
import type { ContentEditorType } from './ContentEditorModal';
import { PageBackgroundSection } from './sections/PageBackgroundSection';
import { ProfileImageSection } from './sections/ProfileImageSection';
import { PageTitleSection } from './sections/PageTitleSection';
import { PageDescriptionSection } from './sections/PageDescriptionSection';
import { PodcastPlayerBarSection } from './sections/PodcastPlayerBarSection';
import { WidgetSettingsSection } from './sections/WidgetSettingsSection';
import { SocialIconsSection } from './sections/SocialIconsSection';
import { ProfileInspector } from '../ProfileInspector';
import { PodcastPlayerInspector } from '../PodcastPlayerInspector';
import { SocialIconInspector } from '../SocialIconInspector';
import { WidgetInspector } from '../WidgetInspector';
import { useSocialIconSelection } from '../../../state/socialIconSelection';
import { usePageSnapshot } from '../../../api/page';
import { sectionRegistry } from './utils/sectionRegistry';
import styles from './combined-style-content-modal.module.css';

interface CombinedStyleContentModalProps {
  isOpen: boolean;
  sectionId: string;
  widgetId?: string | null;
  onClose: () => void;
  theme: ThemeRecord | null;
  uiState: Record<string, unknown>;
  onFieldChange: (fieldId: string, value: unknown) => void;
  activeColor: TabColorTheme;
}

type TabType = 'style' | 'content';

export function CombinedStyleContentModal({
  isOpen,
  sectionId,
  widgetId,
  onClose,
  theme,
  uiState,
  onFieldChange,
  activeColor
}: CombinedStyleContentModalProps): JSX.Element {
  const [activeTab, setActiveTab] = useState<TabType>('style');
  const { data: snapshot } = usePageSnapshot();
  const selectSocialIcon = useSocialIconSelection((state) => state.selectSocialIcon);
  const selectedSocialIconId = useSocialIconSelection((state) => state.selectedSocialIconId);

  // Reset to style tab when modal opens
  useEffect(() => {
    if (isOpen) {
      setActiveTab('style');
    }
  }, [isOpen]);

  const section = sectionRegistry.get(sectionId);
  if (!section) {
    return <></>;
  }

  // Determine content editor type based on section
  const getContentEditorType = (): ContentEditorType | null => {
    if (widgetId) {
      return { type: 'widget', widgetId };
    }
    
    switch (sectionId) {
      case 'profile-image':
        return { type: 'profile', focus: 'image' };
      case 'page-title':
        return { type: 'profile', focus: 'profile' };
      case 'page-description':
        return { type: 'profile', focus: 'bio' };
      case 'podcast-player-bar':
        return { type: 'podcast-player' };
      case 'social-icons':
        return { type: 'social-icons' };
      default:
        return null;
    }
  };

  const contentEditor = getContentEditorType();

  // Auto-select first social icon if opening social icons editor and none is selected
  useEffect(() => {
    if (contentEditor?.type === 'social-icons' && !selectedSocialIconId && snapshot?.social_icons && snapshot.social_icons.length > 0) {
      selectSocialIcon(String(snapshot.social_icons[0].id));
    }
  }, [contentEditor?.type, selectedSocialIconId, snapshot?.social_icons, selectSocialIcon]);

  const renderContentInspector = (): JSX.Element | null => {
    if (!contentEditor) return null;

    switch (contentEditor.type) {
      case 'widget':
        return <WidgetInspector activeColor={activeColor} widgetId={contentEditor.widgetId} />;
      case 'profile':
        return <ProfileInspector focus={contentEditor.focus || 'profile'} activeColor={activeColor} />;
      case 'podcast-player':
        return <PodcastPlayerInspector activeColor={activeColor} />;
      case 'social-icons':
        return <SocialIconInspector activeColor={activeColor} />;
      default:
        return null;
    }
  };

  return (
    <Dialog.Root open={isOpen} onOpenChange={(open) => !open && onClose()} modal={true}>
      <Dialog.Portal>
        <Dialog.Overlay 
          className={styles.overlay}
          onClick={(e) => {
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
            const isInPopover = target.closest('[data-radix-popover-content]') ||
                               target.closest('[data-radix-portal]') ||
                               target.closest('[class*="backgroundPopover"]') ||
                               target.closest('[class*="react-colorful"]') ||
                               target.closest('[data-radix-slider-thumb]') ||
                               target.closest('[data-radix-slider-track]') ||
                               target.closest('[data-radix-slider-root]') ||
                               target.closest('[aria-label="Media library"]') ||
                               target.closest('._drawer_');
            
            if (isInPopover) {
              e.preventDefault();
              return;
            }
          }}
          onInteractOutside={(e) => {
            const target = e.target as HTMLElement;
            const isInPopover = target.closest('[data-radix-popover-content]') ||
                               target.closest('[data-radix-portal]') ||
                               target.closest('[class*="backgroundPopover"]') ||
                               target.closest('[class*="react-colorful"]') ||
                               target.closest('[data-radix-slider-thumb]') ||
                               target.closest('[data-radix-slider-track]') ||
                               target.closest('[data-radix-slider-root]') ||
                               target.closest('[aria-label="Media library"]') ||
                               target.closest('._drawer_');
            
            if (isInPopover) {
              e.preventDefault();
              return;
            }
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
            </div>
            <Dialog.Close asChild>
              <button
                type="button"
                className={styles.closeButton}
                aria-label="Close modal"
                onClick={onClose}
              >
                <X aria-hidden="true" size={20} weight="regular" />
              </button>
            </Dialog.Close>
          </header>

          {/* Tabs */}
          <div className={styles.tabs}>
            <button
              type="button"
              className={`${styles.tab} ${activeTab === 'style' ? styles.activeTab : ''}`}
              onClick={() => setActiveTab('style')}
              aria-selected={activeTab === 'style'}
            >
              <Palette size={18} weight="regular" />
              <span>Style</span>
            </button>
            {contentEditor && (
              <button
                type="button"
                className={`${styles.tab} ${activeTab === 'content' ? styles.activeTab : ''}`}
                onClick={() => setActiveTab('content')}
                aria-selected={activeTab === 'content'}
              >
                <Pencil size={18} weight="regular" />
                <span>Content</span>
              </button>
            )}
          </div>

          {/* Tab Content */}
          <div className={styles.body}>
            {activeTab === 'style' ? (
              <div className={styles.stylePanel}>
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
                {sectionId === 'widget-settings' && (
                  <WidgetSettingsSection
                    uiState={uiState}
                    onFieldChange={onFieldChange}
                    activeColor={activeColor}
                    widgetId={widgetId}
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
            ) : (
              <div className={styles.contentPanel}>
                {renderContentInspector()}
              </div>
            )}
          </div>
        </Dialog.Content>
      </Dialog.Portal>
    </Dialog.Root>
  );
}

