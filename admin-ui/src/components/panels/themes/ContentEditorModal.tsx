/**
 * Content Editor Modal
 * Unified modal for editing content of various page elements
 */

import * as Dialog from '@radix-ui/react-dialog';
import { X } from '@phosphor-icons/react';
import { useEffect } from 'react';
import { ProfileInspector } from '../ProfileInspector';
import { PodcastPlayerInspector } from '../PodcastPlayerInspector';
import { SocialIconInspector } from '../SocialIconInspector';
import { WidgetInspector } from '../WidgetInspector';
import { useSocialIconSelection } from '../../../state/socialIconSelection';
import { usePageSnapshot } from '../../../api/page';
import type { TabColorTheme } from '../../layout/tab-colors';
import styles from './content-editor-modal.module.css';

export type ContentEditorType = 
  | { type: 'widget'; widgetId: string }
  | { type: 'profile'; focus?: 'profile' | 'image' | 'bio' }
  | { type: 'podcast-player' }
  | { type: 'social-icons' };

interface ContentEditorModalProps {
  activeColor: TabColorTheme;
  editor: ContentEditorType | null;
  onClose: () => void;
}

export function ContentEditorModal({ activeColor, editor, onClose }: ContentEditorModalProps): JSX.Element {
  const isOpen = editor !== null;
  const { data: snapshot } = usePageSnapshot();
  const selectSocialIcon = useSocialIconSelection((state) => state.selectSocialIcon);
  const selectedSocialIconId = useSocialIconSelection((state) => state.selectedSocialIconId);

  // Auto-select first social icon if opening social icons editor and none is selected
  useEffect(() => {
    if (editor?.type === 'social-icons' && !selectedSocialIconId && snapshot?.social_icons && snapshot.social_icons.length > 0) {
      selectSocialIcon(String(snapshot.social_icons[0].id));
    }
  }, [editor?.type, selectedSocialIconId, snapshot?.social_icons, selectSocialIcon]);

  const getTitle = (): string => {
    if (!editor) return 'Edit Content';
    switch (editor.type) {
      case 'widget':
        return 'Edit Widget';
      case 'profile':
        return 'Edit Profile';
      case 'podcast-player':
        return 'Edit Podcast Player';
      case 'social-icons':
        return 'Edit Social Icons';
      default:
        return 'Edit Content';
    }
  };

  const getDescription = (): string => {
    if (!editor) return 'Configure content settings';
    switch (editor.type) {
      case 'widget':
        return 'Configure widget content and settings';
      case 'profile':
        return 'Edit profile image, name, and bio';
      case 'podcast-player':
        return 'Configure podcast RSS feed and player settings';
      case 'social-icons':
        return 'Manage social media links';
      default:
        return 'Configure content settings';
    }
  };

  const renderInspector = (): JSX.Element | null => {
    if (!editor) return null;

    switch (editor.type) {
      case 'widget':
        return <WidgetInspector activeColor={activeColor} widgetId={editor.widgetId} />;
      case 'profile':
        return <ProfileInspector focus={editor.focus || 'profile'} activeColor={activeColor} />;
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
          aria-label={getTitle()}
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
              <Dialog.Title className={styles.title}>{getTitle()}</Dialog.Title>
              <Dialog.Description className={styles.description}>
                {getDescription()}
              </Dialog.Description>
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

          <div className={styles.body}>
            {renderInspector()}
          </div>
        </Dialog.Content>
      </Dialog.Portal>
    </Dialog.Root>
  );
}

