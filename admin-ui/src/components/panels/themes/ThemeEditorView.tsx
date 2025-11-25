/**
 * Theme Editor View
 * Edit theme settings with live preview
 */

import { useState, useRef, useCallback, useEffect } from 'react';
import { ArrowLeft, FloppyDisk, Eye, EyeSlash, ArrowCounterClockwise } from '@phosphor-icons/react';
import * as Tooltip from '@radix-ui/react-tooltip';
import type { ThemeRecord } from '../../../api/types';
import type { TabColorTheme } from '../../layout/tab-colors';
import { ThemePreview } from './preview/ThemePreview';
import { ThemePropertyDrawer } from './ThemePropertyDrawer';
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
  const [openModalSection, setOpenModalSection] = useState<string | null>(null);
  const [hotspotsVisible, setHotspotsVisible] = useState<boolean>(true);
  const [undoStack, setUndoStack] = useState<StateChange[]>([]);
  const [redoStack, setRedoStack] = useState<StateChange[]>([]);
  const previousUiStateRef = useRef<Record<string, unknown>>(uiState);
  const isUndoRedoRef = useRef<boolean>(false);
  const changeTimeoutRef = useRef<NodeJS.Timeout | null>(null);
  const pendingChangesRef = useRef<StateChange[]>([]);

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

  const handleHotspotClick = (sectionId: string) => {
    // Open the modal with the relevant property panel
    setOpenModalSection(sectionId);
  };

  const handleCloseModal = () => {
    setOpenModalSection(null);
  };

  const toggleHotspots = () => {
    setHotspotsVisible(prev => !prev);
  };

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
      {/* Header Bar - Minimal with back button and save */}
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
          <p>Click hotspots on the preview to edit properties</p>
        </div>
        
        {/* Hotspot Toggle, Undo/Redo, and Save Buttons */}
        <div className={styles.headerActions}>
          <Tooltip.Provider delayDuration={200}>
            <Tooltip.Root>
              <Tooltip.Trigger asChild>
                <button
                  type="button"
                  className={styles.hotspotToggleButton}
                  onClick={toggleHotspots}
                  aria-label={hotspotsVisible ? 'Hide hotspots' : 'Show hotspots'}
                >
                  {hotspotsVisible ? (
                    <Eye aria-hidden="true" size={16} weight="regular" />
                  ) : (
                    <EyeSlash aria-hidden="true" size={16} weight="regular" />
                  )}
                </button>
              </Tooltip.Trigger>
              <Tooltip.Portal>
                <Tooltip.Content
                  side="bottom"
                  align="end"
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
                  className={styles.undoButton}
                  onClick={handleUndo}
                  disabled={undoStack.length === 0}
                  aria-label="Undo"
                >
                  <ArrowCounterClockwise aria-hidden="true" size={16} weight="regular" />
                </button>
              </Tooltip.Trigger>
              <Tooltip.Portal>
                <Tooltip.Content
                  side="bottom"
                  align="end"
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
                  className={styles.redoButton}
                  onClick={handleRedo}
                  disabled={redoStack.length === 0}
                  aria-label="Redo"
                >
                  <ArrowCounterClockwise 
                    aria-hidden="true" 
                    size={16} 
                    weight="regular" 
                    style={{ transform: 'scaleX(-1)' }}
                  />
                </button>
              </Tooltip.Trigger>
              <Tooltip.Portal>
                <Tooltip.Content
                  side="bottom"
                  align="end"
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
      </header>

      {/* Full-width Preview Panel */}
      <div className={styles.previewPanel}>
        <ThemePreview 
          cssVars={previewCSSVars} 
          onHotspotClick={handleHotspotClick}
          hotspotsVisible={hotspotsVisible}
        />
      </div>

      {/* Property Modal - Opens when hotspot is clicked */}
      <ThemePropertyDrawer
        isOpen={openModalSection !== null}
        sectionId={openModalSection}
        onClose={handleCloseModal}
        theme={theme}
        uiState={uiState}
        onFieldChange={onFieldChange}
        activeColor={activeColor}
      />
    </div>
  );
}

