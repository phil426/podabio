/**
 * Theme Editor View
 * Edit theme settings with live preview
 */

import { useState, useRef, useCallback, useEffect } from 'react';
import { ArrowLeft, Eye, EyeSlash, ArrowCounterClockwise, CheckCircle, Circle, XCircle, Spinner, ArrowsDownUp, Plus, MagicWand } from '@phosphor-icons/react';
import * as Tooltip from '@radix-ui/react-tooltip';
import type { ThemeRecord } from '../../../api/types';
import type { TabColorTheme } from '../../layout/tab-colors';
import { ThemePreview } from './preview/ThemePreview';
import { ThemePropertyDrawer } from './ThemePropertyDrawer';
import { ContentEditorModal, type ContentEditorType } from './ContentEditorModal';
import { CombinedStyleContentModal } from './CombinedStyleContentModal';
import { WidgetReorderModal } from '../WidgetReorderModal';
import { WidgetGalleryDrawer } from '../../overlays/WidgetGalleryDrawer';
import { PodcastThemeGeneratorModal } from './PodcastThemeGeneratorModal';
import { usePodcastThemePrompt } from '../../../hooks/usePodcastThemePrompt';
import { useUpdateWidgetMutation, useDeleteWidgetMutation, useAddWidgetMutation, useAvailableWidgetsQuery } from '../../../api/widgets';
import { usePageSnapshot } from '../../../api/page';
import { useQueryClient } from '@tanstack/react-query';
import { queryKeys } from '../../../api/utils';
import { useWidgetSelection } from '../../../state/widgetSelection';
import styles from './theme-editor-view.module.css';

interface StateChange {
  fieldId: string;
  oldValue: unknown;
  newValue: unknown;
  timestamp: number;
}

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
  const [hotspotsVisible, setHotspotsVisible] = useState<boolean>(false);
  const [undoStack, setUndoStack] = useState<StateChange[]>([]);
  const [redoStack, setRedoStack] = useState<StateChange[]>([]);
  const previousUiStateRef = useRef<Record<string, unknown>>(uiState);
  const isUndoRedoRef = useRef<boolean>(false);
  const changeTimeoutRef = useRef<NodeJS.Timeout | null>(null);
  const pendingChangesRef = useRef<StateChange[]>([]);

  // Podcast theme generator
  const {
    openGenerator,
    closeGenerator,
    isGeneratorOpen,
    generatorProps,
  } = usePodcastThemePrompt();

  // Widget mutations for layer actions
  const { data: snapshot } = usePageSnapshot();
  const widgets = snapshot?.widgets || [];
  const updateWidgetMutation = useUpdateWidgetMutation();
  const deleteWidgetMutation = useDeleteWidgetMutation();
  const addWidgetMutation = useAddWidgetMutation();
  const { data: availableWidgets } = useAvailableWidgetsQuery();
  const selectWidget = useWidgetSelection((state) => state.selectWidget);
  const queryClient = useQueryClient();

  // Track changes to uiState for undo/redo
  useEffect(() => {
    // Skip tracking if this is an undo/redo operation
    if (isUndoRedoRef.current) {
      isUndoRedoRef.current = false;
      previousUiStateRef.current = { ...uiState };
      return;
    }

    const previous = previousUiStateRef.current;
    const changes: StateChange[] = [];

    // Find all changed fields
    const allKeys = new Set([...Object.keys(previous), ...Object.keys(uiState)]);
    for (const key of allKeys) {
      const oldValue = previous[key];
      const newValue = uiState[key];
      if (oldValue !== newValue) {
        changes.push({
          fieldId: key,
          oldValue,
          newValue,
          timestamp: Date.now()
        });
      }
    }

    // Only track if there are actual changes (not initial load)
    if (changes.length > 0 && Object.keys(previous).length > 0) {
      // Batch changes that happen within 100ms
      pendingChangesRef.current.push(...changes);

      // Clear existing timeout
      if (changeTimeoutRef.current) {
        clearTimeout(changeTimeoutRef.current);
      }

      // Set timeout to batch changes
      changeTimeoutRef.current = setTimeout(() => {
        if (pendingChangesRef.current.length > 0) {
          // Group changes by timestamp (within the same batch)
          const batchedChanges = pendingChangesRef.current;
          setUndoStack(prev => [...prev, ...batchedChanges]);
          setRedoStack([]); // Clear redo stack on new change
          pendingChangesRef.current = [];
        }
      }, 100);
    }

    previousUiStateRef.current = { ...uiState };
  }, [uiState]);

  const handleHotspotClick = (sectionId: string, widgetId?: string | null) => {
    // For non-widget hotspots, open style editor directly
    if (!widgetId) {
      setOpenModalSection(sectionId);
    }
    // For widget hotspots, context menu will handle the action
  };

  const handleEditContent = (sectionId: string, widgetId?: string | null) => {
    // Close style editor if open
    setOpenModalSection(null);

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
        case 'social-icons':
          editor = { type: 'social-icons' };
          break;
        default:
          // No content editor for this section
          return;
      }
    }

    setContentEditor(editor);
  };

  const handleEditStyle = (sectionId: string, widgetId?: string | null) => {
    // Close content editor if open
    setContentEditor(null);
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

    const confirmDelete = window.confirm(`Delete "${widget.title}"? This cannot be undone.`);
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
              selectWidget(String(widgetId));
            }
            queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
          }
        }
      );
    },
    [addWidgetMutation, selectWidget, queryClient]
  );

  const handleUndo = useCallback(() => {
    if (undoStack.length === 0) return;

    // Get the most recent batch of changes
    // Find the last unique timestamp
    const timestamps = [...new Set(undoStack.map(c => c.timestamp))];
    const lastTimestamp = timestamps[timestamps.length - 1];
    const changesToUndo = undoStack.filter(change => change.timestamp === lastTimestamp);

    // Mark as undo/redo operation to prevent tracking
    isUndoRedoRef.current = true;

    // Apply undo: restore old values
    changesToUndo.forEach(change => {
      onFieldChange(change.fieldId, change.oldValue);
    });

    // Move from undo to redo stack
    setUndoStack(prev => prev.filter(change => change.timestamp !== lastTimestamp));
    setRedoStack(prev => [...prev, ...changesToUndo]);
  }, [undoStack, onFieldChange]);

  const handleRedo = useCallback(() => {
    if (redoStack.length === 0) return;

    // Get the most recent batch of changes
    const timestamps = [...new Set(redoStack.map(c => c.timestamp))];
    const lastTimestamp = timestamps[timestamps.length - 1];
    const changesToRedo = redoStack.filter(change => change.timestamp === lastTimestamp);

    // Mark as undo/redo operation to prevent tracking
    isUndoRedoRef.current = true;

    // Apply redo: restore new values
    changesToRedo.forEach(change => {
      onFieldChange(change.fieldId, change.newValue);
    });

    // Move from redo to undo stack
    setRedoStack(prev => prev.filter(change => change.timestamp !== lastTimestamp));
    setUndoStack(prev => [...prev, ...changesToRedo]);
  }, [redoStack, onFieldChange]);

  // Keyboard shortcuts
  useEffect(() => {
    const handleKeyDown = (e: KeyboardEvent) => {
      if ((e.metaKey || e.ctrlKey) && e.key === 'z' && !e.shiftKey) {
        e.preventDefault();
        handleUndo();
      } else if ((e.metaKey || e.ctrlKey) && (e.key === 'y' || (e.key === 'z' && e.shiftKey))) {
        e.preventDefault();
        handleRedo();
      }
    };

    window.addEventListener('keydown', handleKeyDown);
    return () => window.removeEventListener('keydown', handleKeyDown);
  }, [handleUndo, handleRedo]);

  return (
    <div className={styles.container}>
      {/* Floating Action Buttons - Vertical row to the left of preview */}
      {/* Floating Action Buttons - Vertical row to the left of preview */}
      <div className={styles.floatingActions}>
        <Tooltip.Provider delayDuration={200}>
          <Tooltip.Root>
            <Tooltip.Trigger asChild>
              <button
                type="button"
                className={`${styles.floatingActionButton} ${styles.actionWizard}`}
                onClick={openGenerator}
                aria-label="Theme Wizard"
              >
                <MagicWand aria-hidden="true" size={20} weight="regular" />
              </button>
            </Tooltip.Trigger>
            <Tooltip.Portal>
              <Tooltip.Content
                side="left"
                align="center"
                className={styles.tooltip}
              >
                Theme Wizard
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

        <Tooltip.Provider delayDuration={200}>
          <Tooltip.Root>
            <Tooltip.Trigger asChild>
              <button
                type="button"
                className={`${styles.floatingActionButton} ${styles.actionUndo}`}
                onClick={handleUndo}
                disabled={undoStack.length === 0}
                aria-label="Undo"
              >
                <ArrowCounterClockwise aria-hidden="true" size={20} weight="regular" />
              </button>
            </Tooltip.Trigger>
            <Tooltip.Portal>
              <Tooltip.Content
                side="left"
                align="center"
                className={styles.tooltip}
              >
                Undo (Cmd/Ctrl+Z)
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
                className={`${styles.floatingActionButton} ${styles.actionRedo}`}
                onClick={handleRedo}
                disabled={redoStack.length === 0}
                aria-label="Redo"
              >
                <ArrowCounterClockwise
                  aria-hidden="true"
                  size={20}
                  weight="regular"
                  style={{ transform: 'scaleX(-1)' }}
                />
              </button>
            </Tooltip.Trigger>
            <Tooltip.Portal>
              <Tooltip.Content
                side="left"
                align="center"
                className={styles.tooltip}
              >
                Redo (Cmd/Ctrl+Shift+Z)
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
      </div>

      {/* Full-width Preview Panel */}
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
      <ThemePropertyDrawer
        isOpen={openModalSection !== null}
        sectionId={openModalSection}
        widgetId={openModalWidgetId}
        onClose={handleCloseStyleModal}
        theme={theme}
        uiState={uiState}
        onFieldChange={onFieldChange}
        activeColor={activeColor}
      />

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

      {/* Widget Gallery Drawer */}
      <WidgetGalleryDrawer
        open={isGalleryOpen}
        widgets={availableWidgets ?? []}
        onClose={() => setGalleryOpen(false)}
        onAdd={handleAddWidget}
        isAdding={addWidgetMutation.isPending}
      />

      {/* Podcast Theme Generator Modal */}
      <PodcastThemeGeneratorModal
        coverImageUrl={generatorProps.coverImageUrl}
        isOpen={isGeneratorOpen}
        onClose={closeGenerator}
      />
    </div>
  );
}

