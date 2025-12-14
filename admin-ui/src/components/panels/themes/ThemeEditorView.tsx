
import { useState, useRef, useCallback, useEffect } from 'react';
import { ArrowLeft, Eye, EyeSlash, CheckCircle, Circle, XCircle, Spinner, ArrowsDownUp, Plus, Sparkle, Link, ArrowSquareOut, Check, Layout, Cube, ArrowsInLineHorizontal, Palette, TextT } from '@phosphor-icons/react';
import * as Tooltip from '@radix-ui/react-tooltip';
import type { ThemeRecord } from '../../../api/types';
import type { TabColorTheme } from '../../layout/tab-colors';
import { ThemePreview } from './preview/ThemePreview';
import { ThemePropertyDrawer } from './ThemePropertyDrawer';
import { ContentEditorModal, type ContentEditorType } from './ContentEditorModal';
import { CombinedStyleContentModal } from './CombinedStyleContentModal';
import { WidgetReorderModal } from '../WidgetReorderModal';
import { WidgetGalleryModal } from '../../overlays/WidgetGalleryModal';
import { useUpdateWidgetMutation, useDeleteWidgetMutation, useAddWidgetMutation, useAvailableWidgetsQuery } from '../../../api/widgets';
import { usePageSnapshot, usePageSettingsMutation } from '../../../api/page';
import { useQueryClient } from '@tanstack/react-query';
import { queryKeys } from '../../../api/utils';
import { useWidgetSelection } from '../../../state/widgetSelection';
import { useEasyModeState } from '../../../state/easyModeState';
import { useEasyMode } from '../../../hooks/useEasyMode';
import { EasyThemeDrawer } from './EasyThemeDrawer';
import styles from './theme-editor-view.module.css';

interface ThemeEditorViewProps {
  theme: ThemeRecord | null;
  uiState: Record<string, unknown>;
  onFieldChange: (fieldId: string, value: unknown) => void;
  onSave: () => void;
  onBack: () => void;
  isSaving: boolean;
  autoSaveStatus: 'idle' | 'saving' | 'saved' | 'error';
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
  autoSaveStatus,
  previewCSSVars,
  activeColor
}: ThemeEditorViewProps): JSX.Element {
  const [openModalSection, setOpenModalSection] = useState<string | null>(null);
  const [openModalWidgetId, setOpenModalWidgetId] = useState<string | null>(null);
  const [contentEditor, setContentEditor] = useState<ContentEditorType | null>(null);
  const [combinedModalSection, setCombinedModalSection] = useState<string | null>(null);
  const [combinedModalWidgetId, setCombinedModalWidgetId] = useState<string | null>(null);
  const [reorderModalOpen, setReorderModalOpen] = useState<boolean>(false);
  const [isGalleryOpen, setGalleryOpen] = useState<boolean>(false);
  const [hotspotsVisible, setHotspotsVisible] = useState<boolean>(true);
  const [linkCopied, setLinkCopied] = useState<boolean>(false);

  // Widget mutations for layer actions
  const { data: snapshot } = usePageSnapshot();
  const widgets = snapshot?.widgets || [];
  const updateWidgetMutation = useUpdateWidgetMutation();
  const deleteWidgetMutation = useDeleteWidgetMutation();
  const addWidgetMutation = useAddWidgetMutation();
  const { data: availableWidgets } = useAvailableWidgetsQuery();
  const selectWidget = useWidgetSelection((state) => state.selectWidget);

  const queryClient = useQueryClient();
  const { isOpen: isEasyModeOpen, setOpen: setEasyModeOpen, view: easyModeView } = useEasyModeState();
  const pageSettingsMutation = usePageSettingsMutation();

  const handleApplyLayout = (layoutId: string) => {
    pageSettingsMutation.mutate({
      layout_option: layoutId
    });
  };

  // Easy Mode State
  const {
    mode,
    setMode,
    activePresets,
    applyShapePreset,
    applyColorPreset,
    applyTypographyPreset,
    applySpacingPreset,
    applyAutoPreset,
    isAutoGenerating,
    presets
  } = useEasyMode({
    uiState,
    onFieldChange,
    profileImageUrl: snapshot?.page?.profile_image
  });

  // State Harmonizer: Ensure social-icons always uses CombinedModal
  useEffect(() => {
    if (contentEditor?.type === 'social-icons') {
      // Force switch to combined modal
      setContentEditor(null);
      setCombinedModalSection('social-icons');
      // If we don't have a widgetId, ensure it's null
      setCombinedModalWidgetId(null);
    }
  }, [contentEditor]);

  const handleHotspotClick = (sectionId: string, widgetId?: string | null) => {
    // Redirect social-icons to combined modal
    if (!widgetId && sectionId === 'social-icons') {
      handleOpenCombinedModal(sectionId, widgetId);
      return;
    }

    // For non-widget hotspots, open style editor directly
    if (!widgetId) {
      setOpenModalSection(sectionId);
    }
    // For widget hotspots, context menu will handle the action
  };

  const handleEditContent = (sectionId: string, widgetId?: string | null) => {
    // Redirect social-icons to combined modal
    if (sectionId === 'social-icons' && !widgetId) {
      handleOpenCombinedModal(sectionId, widgetId);
      return;
    }

    // Close style editor and combined modal if open
    setOpenModalSection(null);
    setCombinedModalSection(null);

    // Map section ID to content editor type
    let editor: ContentEditorType | null = null;

    if (widgetId) {
      // Widget content editing
      editor = { type: 'widget', widgetId };
    } else {
      // Non-widget content editing
      switch (sectionId) {
        case 'profile-image':
          editor = { type: 'profile', focus: 'image' };
          break;
        case 'page-title':
          editor = { type: 'profile', focus: 'profile' };
          break;
        case 'page-description':
          editor = { type: 'profile', focus: 'bio' };
          break;
        case 'podcast-player-bar':
          editor = { type: 'podcast-player' };
          break;
        default:
          // No content editor for this section
          return;
      }
    }

    setContentEditor(editor);
  };

  const handleEditStyle = (sectionId: string, widgetId?: string | null) => {
    // Redirect social-icons to combined modal
    if (sectionId === 'social-icons' && !widgetId) {
      handleOpenCombinedModal(sectionId, widgetId);
      return;
    }

    // Close content editor and combined modal if open
    setContentEditor(null);
    setCombinedModalSection(null);
    // Open style editor
    setOpenModalSection(sectionId);
    // Track which widget is being edited (if any)
    setOpenModalWidgetId(widgetId || null);
  };

  const handleCloseStyleModal = () => {
    setOpenModalSection(null);
    setOpenModalWidgetId(null);
  };

  const handleCloseContentModal = () => {
    setContentEditor(null);
  };

  const handleOpenCombinedModal = (sectionId: string, widgetId?: string | null) => {
    // Close other modals
    setOpenModalSection(null);
    setContentEditor(null);

    setCombinedModalSection(sectionId);
    setCombinedModalWidgetId(widgetId || null);
  };

  const handleCloseCombinedModal = () => {
    setCombinedModalSection(null);
    setCombinedModalWidgetId(null);
  };

  const handleToggleVisibility = (widgetId: string) => {
    const widget = widgets.find((w) => String(w.id) === widgetId);
    if (!widget) return;

    updateWidgetMutation.mutate(
      { widget_id: widgetId, is_active: widget.is_active ? '0' : '1' },
      {
        onSuccess: () => {
          queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
        }
      }
    );
  };

  const handleDeleteWidget = (widgetId: string) => {
    const widget = widgets.find((w) => String(w.id) === widgetId);
    if (!widget) return;

    const confirmDelete = window.confirm(`Delete "${widget.title}" ? This cannot be undone.`);
    if (!confirmDelete) return;

    deleteWidgetMutation.mutate(
      { widget_id: widgetId },
      {
        onSuccess: () => {
          queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
          // Close content editor if it was open for this widget
          if (contentEditor?.type === 'widget' && contentEditor.widgetId === widgetId) {
            setContentEditor(null);
          }
        }
      }
    );
  };

  const handleToggleFeatured = (widgetId: string) => {
    const widget = widgets.find((w) => String(w.id) === widgetId);
    if (!widget) return;

    const isCurrentlyFeatured = widget.is_featured === 1;
    const newFeaturedValue = isCurrentlyFeatured ? '0' : '1';

    // The backend will automatically unfeature other widgets when one is featured
    updateWidgetMutation.mutate(
      { widget_id: widgetId, is_featured: newFeaturedValue },
      {
        onSuccess: () => {
          queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
        }
      }
    );
  };

  const handleToggleLock = (widgetId: string) => {
    const widget = widgets.find((w) => String(w.id) === widgetId);
    if (!widget) return;

    // Assuming is_locked field exists (0 or 1), similar to is_active
    // If the field doesn't exist yet, this will need to be added to the database
    const currentLocked = (widget as any).is_locked ?? 0;
    const newLockedValue = currentLocked ? '0' : '1';
    updateWidgetMutation.mutate(
      { widget_id: widgetId, is_locked: newLockedValue },
      {
        onSuccess: () => {
          queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
        }
      }
    );
  };

  const toggleHotspots = () => {
    setHotspotsVisible(prev => !prev);
  };

  const handleAddWidget = useCallback(
    (widgetType: string, label?: string) => {
      // Find the widget definition to check requirements
      const widgetDef = availableWidgets?.find(w =>
        w.widget_id === widgetType || w.type === widgetType
      );

      // Check if widget requires API configuration and is not configured
      if (widgetDef?.requires_api && (widgetDef.is_configured === false)) {
        // For now, simple alert. Ideally this would be a nice modal.
        if (widgetType.startsWith('shopify')) {
          alert('Please connect your Shopify store in settings before adding this widget.');
          return;
        } else if (widgetType.startsWith('instagram')) {
          alert('Please connect your Instagram account in settings before adding this widget.');
          return;
        }
      }

      addWidgetMutation.mutate(
        {
          widget_type: widgetType,
          title: label ?? widgetType
        },
        {
          onSuccess: (response) => {
            setGalleryOpen(false);
            const typed = (response ?? {}) as { widget_id?: number | string; data?: { widget_id?: number | string } };
            const widgetId = typed.widget_id ?? typed.data?.widget_id;
            if (widgetId) {
              const idString = String(widgetId);
              selectWidget(idString);
              // Open content editor (properties) instead of style editor
              handleEditContent('widget-settings', idString);
            }
            queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
          }
        }
      );
    },
    [addWidgetMutation, selectWidget, queryClient, availableWidgets]
  );


  const handleCopyLink = () => {
    if (!snapshot?.page?.username) return;
    const url = `https://poda.bio/${snapshot.page.username}`;
    navigator.clipboard.writeText(url);
    setLinkCopied(true);
    setTimeout(() => setLinkCopied(false), 2000);
  };

  const handleOpenLivePage = () => {
    if (!snapshot?.page?.username) return;
    const url = `https://poda.bio/${snapshot.page.username}`;
    window.open(url, '_blank');
  };

  const activeLayoutId = snapshot?.page?.layout_option || 'standard';

  return (
    <div className={styles.container}>
      {/* Floating Action Buttons - Vertical row to the left of preview */}
      <div className={styles.floatingActions}>
        <Tooltip.Provider delayDuration={200}>
          <Tooltip.Root>
            <Tooltip.Trigger asChild>
              <button
                type="button"
                className={`${styles.floatingActionButton}`}
                onClick={() => setEasyModeOpen(true, 'all')}
                aria-label="Easy Mode"
                style={isEasyModeOpen && easyModeView === 'all' ? {
                  background: 'var(--admin-primary, #2563eb)',
                  color: 'white',
                  borderColor: 'var(--admin-primary, #2563eb)'
                } : {}}
              >
                <Sparkle aria-hidden="true" size={20} weight={isEasyModeOpen && easyModeView === 'all' ? 'fill' : 'regular'} />
              </button>
            </Tooltip.Trigger>
            <Tooltip.Portal>
              <Tooltip.Content
                side="left"
                align="center"
                className={styles.tooltip}
              >
                Easy Mode
                <Tooltip.Arrow className={styles.tooltipArrow} />
              </Tooltip.Content>
            </Tooltip.Portal>
          </Tooltip.Root>
        </Tooltip.Provider>

        <Tooltip.Provider delayDuration={200}>
          <Tooltip.Root>
            <Tooltip.Trigger asChild>
              <button
                type="button"
                className={`${styles.floatingActionButton} ${styles.actionAdd}`}
                onClick={() => setGalleryOpen(true)}
                aria-label="Add widget"
              >
                <Plus aria-hidden="true" size={20} weight="regular" />
              </button>
            </Tooltip.Trigger>
            <Tooltip.Portal>
              <Tooltip.Content
                side="left"
                align="center"
                className={styles.tooltip}
              >
                Add Widget
                <Tooltip.Arrow className={styles.tooltipArrow} />
              </Tooltip.Content>
            </Tooltip.Portal>
          </Tooltip.Root>
        </Tooltip.Provider>

        {/* Separator */}
        <div style={{ height: '1px', background: 'rgba(255,255,255,0.1)', margin: '4px 0' }} />

        {/* Section Specific Buttons */}
        <Tooltip.Provider delayDuration={200}>
          <Tooltip.Root>
            <Tooltip.Trigger asChild>
              <button
                type="button"
                className={`${styles.floatingActionButton}`}
                onClick={() => setEasyModeOpen(true, 'shape')}
                aria-label="Shape"
                style={isEasyModeOpen && easyModeView === 'shape' ? {
                  background: 'var(--admin-primary, #2563eb)',
                  color: 'white',
                  borderColor: 'var(--admin-primary, #2563eb)'
                } : {}}
              >
                <Cube aria-hidden="true" size={20} weight={isEasyModeOpen && easyModeView === 'shape' ? 'fill' : 'regular'} />
              </button>
            </Tooltip.Trigger>
            <Tooltip.Portal>
              <Tooltip.Content side="left" align="center" className={styles.tooltip}>
                Shape
                <Tooltip.Arrow className={styles.tooltipArrow} />
              </Tooltip.Content>
            </Tooltip.Portal>
          </Tooltip.Root>
        </Tooltip.Provider>

        <Tooltip.Provider delayDuration={200}>
          <Tooltip.Root>
            <Tooltip.Trigger asChild>
              <button
                type="button"
                className={`${styles.floatingActionButton}`}
                onClick={() => setEasyModeOpen(true, 'vibe')}
                aria-label="Vibe"
                style={isEasyModeOpen && easyModeView === 'vibe' ? {
                  background: 'var(--admin-primary, #2563eb)',
                  color: 'white',
                  borderColor: 'var(--admin-primary, #2563eb)'
                } : {}}
              >
                <Palette aria-hidden="true" size={20} weight={isEasyModeOpen && easyModeView === 'vibe' ? 'fill' : 'regular'} />
              </button>
            </Tooltip.Trigger>
            <Tooltip.Portal>
              <Tooltip.Content side="left" align="center" className={styles.tooltip}>
                Vibe
                <Tooltip.Arrow className={styles.tooltipArrow} />
              </Tooltip.Content>
            </Tooltip.Portal>
          </Tooltip.Root>
        </Tooltip.Provider>

        <Tooltip.Provider delayDuration={200}>
          <Tooltip.Root>
            <Tooltip.Trigger asChild>
              <button
                type="button"
                className={`${styles.floatingActionButton}`}
                onClick={() => setEasyModeOpen(true, 'typography')}
                aria-label="Typography"
                style={isEasyModeOpen && easyModeView === 'typography' ? {
                  background: 'var(--admin-primary, #2563eb)',
                  color: 'white',
                  borderColor: 'var(--admin-primary, #2563eb)'
                } : {}}
              >
                <TextT aria-hidden="true" size={20} weight={isEasyModeOpen && easyModeView === 'typography' ? 'fill' : 'regular'} />
              </button>
            </Tooltip.Trigger>
            <Tooltip.Portal>
              <Tooltip.Content side="left" align="center" className={styles.tooltip}>
                Typography
                <Tooltip.Arrow className={styles.tooltipArrow} />
              </Tooltip.Content>
            </Tooltip.Portal>
          </Tooltip.Root>
        </Tooltip.Provider>

        <Tooltip.Provider delayDuration={200}>
          <Tooltip.Root>
            <Tooltip.Trigger asChild>
              <button
                type="button"
                className={`${styles.floatingActionButton}`}
                onClick={() => setEasyModeOpen(true, 'spacing')}
                aria-label="Spacing"
                style={isEasyModeOpen && easyModeView === 'spacing' ? {
                  background: 'var(--admin-primary, #2563eb)',
                  color: 'white',
                  borderColor: 'var(--admin-primary, #2563eb)'
                } : {}}
              >
                <ArrowsInLineHorizontal aria-hidden="true" size={20} weight={isEasyModeOpen && easyModeView === 'spacing' ? 'fill' : 'regular'} />
              </button>
            </Tooltip.Trigger>
            <Tooltip.Portal>
              <Tooltip.Content side="left" align="center" className={styles.tooltip}>
                Spacing
                <Tooltip.Arrow className={styles.tooltipArrow} />
              </Tooltip.Content>
            </Tooltip.Portal>
          </Tooltip.Root>
        </Tooltip.Provider>

        {/* STASHED: Layout Feature
        <Tooltip.Provider delayDuration={200}>
          <Tooltip.Root>
            <Tooltip.Trigger asChild>
              <button
                type="button"
                className={`${styles.floatingActionButton}`}
                onClick={() => setEasyModeOpen(true, 'layout')}
                aria-label="Layouts"
                style={isEasyModeOpen && easyModeView === 'layout' ? {
                  background: 'var(--admin-primary, #2563eb)',
                  color: 'white',
                  borderColor: 'var(--admin-primary, #2563eb)'
                } : {}}
              >
                <Layout aria-hidden="true" size={20} weight={isEasyModeOpen && easyModeView === 'layout' ? 'fill' : 'regular'} />
              </button>
            </Tooltip.Trigger>
            <Tooltip.Portal>
              <Tooltip.Content
                side="left"
                align="center"
                className={styles.tooltip}
              >
                Page Layout
                <Tooltip.Arrow className={styles.tooltipArrow} />
              </Tooltip.Content>
            </Tooltip.Portal>
          </Tooltip.Root>
        </Tooltip.Provider>
        */}

        {/* Separator */}
        <div style={{ height: '1px', background: 'rgba(255,255,255,0.1)', margin: '4px 0' }} />

        <Tooltip.Provider delayDuration={200}>
          <Tooltip.Root>
            <Tooltip.Trigger asChild>
              <button
                type="button"
                className={`${styles.floatingActionButton} ${styles.actionReorder}`}
                onClick={() => setReorderModalOpen(true)}
                aria-label="Reorder widgets"
              >
                <ArrowsDownUp aria-hidden="true" size={20} weight="regular" />
              </button>
            </Tooltip.Trigger>
            <Tooltip.Portal>
              <Tooltip.Content
                side="left"
                align="center"
                className={styles.tooltip}
              >
                Reorder Widgets
                <Tooltip.Arrow className={styles.tooltipArrow} />
              </Tooltip.Content>
            </Tooltip.Portal>
          </Tooltip.Root>
        </Tooltip.Provider>

        <Tooltip.Provider delayDuration={200}>
          <Tooltip.Root>
            <Tooltip.Trigger asChild>
              <button
                type="button"
                className={`${styles.floatingActionButton} ${styles.actionPreview}`}
                onClick={toggleHotspots}
                aria-label={hotspotsVisible ? 'Hide hotspots' : 'Show hotspots'}
              >
                {hotspotsVisible ? (
                  <Eye aria-hidden="true" size={20} weight="regular" />
                ) : (
                  <EyeSlash aria-hidden="true" size={20} weight="regular" />
                )}
              </button>
            </Tooltip.Trigger>
            <Tooltip.Portal>
              <Tooltip.Content
                side="left"
                align="center"
                className={styles.tooltip}
              >
                {hotspotsVisible ? 'Hide hotspots' : 'Show hotspots'}
                <Tooltip.Arrow className={styles.tooltipArrow} />
              </Tooltip.Content>
            </Tooltip.Portal>
          </Tooltip.Root>
        </Tooltip.Provider>

        {/* Separator / Bottom Actions */}
        <div style={{ height: '1px', background: 'rgba(255,255,255,0.1)', margin: '8px 4px' }} />

        <Tooltip.Provider delayDuration={200}>
          <Tooltip.Root>
            <Tooltip.Trigger asChild>
              <button
                type="button"
                className={styles.floatingActionButton}
                onClick={handleCopyLink}
                aria-label="Copy share link"
              >
                {linkCopied ? (
                  <Check aria-hidden="true" size={20} weight="regular" />
                ) : (
                  <Link aria-hidden="true" size={20} weight="regular" />
                )}
              </button>
            </Tooltip.Trigger>
            <Tooltip.Portal>
              <Tooltip.Content
                side="left"
                align="center"
                className={styles.tooltip}
              >
                {linkCopied ? 'Copied!' : 'Copy Share Link'}
                <Tooltip.Arrow className={styles.tooltipArrow} />
              </Tooltip.Content>
            </Tooltip.Portal>
          </Tooltip.Root>
        </Tooltip.Provider>

        <Tooltip.Provider delayDuration={200}>
          <Tooltip.Root>
            <Tooltip.Trigger asChild>
              <button
                type="button"
                className={styles.floatingActionButton}
                onClick={handleOpenLivePage}
                aria-label="Open live page"
                disabled={!snapshot?.page?.username}
              >
                <ArrowSquareOut aria-hidden="true" size={20} weight="regular" />
              </button>
            </Tooltip.Trigger>
            <Tooltip.Portal>
              <Tooltip.Content
                side="left"
                align="center"
                className={styles.tooltip}
              >
                Open Live Page
                <Tooltip.Arrow className={styles.tooltipArrow} />
              </Tooltip.Content>
            </Tooltip.Portal>
          </Tooltip.Root>
        </Tooltip.Provider>
      </div>

      <div className={styles.previewPanel}>

        <ThemePreview
          cssVars={previewCSSVars}
          onHotspotClick={handleHotspotClick}
          onEditContent={handleEditContent}
          onEditStyle={(sectionId, widgetId) => handleEditStyle(sectionId, widgetId)}
          onOpenCombinedModal={handleOpenCombinedModal}
          onToggleVisibility={handleToggleVisibility}
          onDeleteWidget={handleDeleteWidget}
          onToggleFeatured={handleToggleFeatured}
          onToggleLock={handleToggleLock}
          hotspotsVisible={hotspotsVisible}
          activeColor={activeColor}
        />
      </div>

      {/* Style Editor Modal - Opens when style hotspot is clicked */}
      {/* Style Editor Modal - Opens when style hotspot is clicked */}
      {isEasyModeOpen ? (
        <EasyThemeDrawer
          isOpen={true}
          onClose={() => setEasyModeOpen(false)}
          activePresets={{
            ...activePresets,
            activeLayoutId: activeLayoutId // Use derived active layout
          }}
          presets={presets}
          onApplyShapePreset={applyShapePreset}
          onApplyColorPreset={applyColorPreset}
          onApplyTypographyPreset={applyTypographyPreset}
          onApplySpacingPreset={applySpacingPreset}
          onApplyLayoutPreset={handleApplyLayout}
          onApplyAutoPreset={applyAutoPreset}
          isAutoGenerating={isAutoGenerating}
          profileImageUrl={snapshot?.page?.profile_image}
          showOnly={easyModeView}
        />
      ) : (
        <ThemePropertyDrawer
          isOpen={openModalSection !== null && openModalSection !== 'easy'}
          sectionId={openModalSection}
          widgetId={openModalWidgetId}
          onClose={handleCloseStyleModal}
          theme={theme}
          uiState={uiState}
          onFieldChange={onFieldChange}
          activeColor={activeColor}
        />
      )}

      {/* Content Editor Modal - Opens when content edit is clicked */}
      <ContentEditorModal
        activeColor={activeColor}
        editor={contentEditor}
        onClose={handleCloseContentModal}

      />

      {/* Combined Style/Content Modal - Opens directly for hotspots with only content and style */}
      {combinedModalSection && (
        <CombinedStyleContentModal
          isOpen={true}
          sectionId={combinedModalSection}
          widgetId={combinedModalWidgetId}
          onClose={handleCloseCombinedModal}
          theme={theme}
          uiState={uiState}
          onFieldChange={onFieldChange}
          activeColor={activeColor}
        />
      )}

      {/* Widget Reorder Modal */}
      <WidgetReorderModal
        isOpen={reorderModalOpen}
        onClose={() => setReorderModalOpen(false)}
      />

      {/* Widget Gallery Modal */}
      <WidgetGalleryModal
        open={isGalleryOpen}
        widgets={availableWidgets ?? []}
        onClose={() => setGalleryOpen(false)}
        onAdd={handleAddWidget}
        isAdding={addWidgetMutation.isPending}
      />

    </div>
  );
}

