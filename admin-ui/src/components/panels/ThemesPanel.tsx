/**
 * Themes Panel
 * Main component for theme management with extensible architecture
 */

import { useState, useCallback, useMemo, useEffect } from 'react';
import * as ScrollArea from '@radix-ui/react-scroll-area';
import { usePageSnapshot, usePageAppearanceMutation, updatePageThemeId } from '../../api/page';
import { useThemeLibraryQuery, useUpdateThemeMutation, useCreateThemeMutation, useDeleteThemeMutation } from '../../api/themes';
import { useQueryClient } from '@tanstack/react-query';
import { queryKeys } from '../../api/utils';
import type { ThemeRecord } from '../../api/types';
import type { TabColorTheme } from '../layout/tab-colors';
import { databaseToUI, uiToDatabase, mergeThemeWithUIState, getDefaultUIState } from './themes/utils/themeMapper';
import { sectionRegistry } from './themes/utils/sectionRegistry';
import { previewRenderer } from './themes/utils/previewRenderer';
import { ThemeLibraryView } from './themes/ThemeLibraryView';
import { ThemeEditorView } from './themes/ThemeEditorView';
import { ConfirmDeleteDialog } from './themes/ConfirmDeleteDialog';
import styles from './themes-panel.module.css';

interface ThemesPanelProps {
  activeColor: TabColorTheme;
}

type ViewMode = 'library' | 'editor';

interface StatusMessage {
  tone: 'success' | 'error';
  message: string;
}

export function ThemesPanel({ activeColor }: ThemesPanelProps): JSX.Element {
  const { data: snapshot } = usePageSnapshot();
  const { data: themeLibrary, isLoading: themesLoading } = useThemeLibraryQuery();
  const updateMutation = useUpdateThemeMutation();
  const createMutation = useCreateThemeMutation();
  const deleteMutation = useDeleteThemeMutation();
  const updatePageMutation = usePageAppearanceMutation();
  const queryClient = useQueryClient();

  const [viewMode, setViewMode] = useState<ViewMode>('library');
  const [selectedTheme, setSelectedTheme] = useState<ThemeRecord | null>(null);
  const [uiState, setUIState] = useState<Record<string, unknown>>({});
  const [isSaving, setIsSaving] = useState(false);
  const [status, setStatus] = useState<StatusMessage | null>(null);
  const [deleteDialogOpen, setDeleteDialogOpen] = useState(false);
  const [themeToDelete, setThemeToDelete] = useState<ThemeRecord | null>(null);

  // Derive active theme from theme library
  const activeTheme = useMemo(() => {
    if (!themeLibrary) return null;
    const themeId = snapshot?.page?.theme_id ?? null;
    if (themeId == null) {
      return themeLibrary.system?.[0] ?? themeLibrary.user?.[0] ?? null;
    }
    const combined = [...(themeLibrary.user ?? []), ...(themeLibrary.system ?? [])];
    return combined.find(theme => theme.id === themeId) ?? themeLibrary.system?.[0] ?? themeLibrary.user?.[0] ?? null;
  }, [themeLibrary, snapshot?.page?.theme_id]);

  // Initialize UI state when theme changes
  useEffect(() => {
    if (activeTheme) {
      const page = snapshot?.page ?? null;
      const initialState = databaseToUI(activeTheme, page);
      setUIState(initialState);
      setSelectedTheme(activeTheme);
    } else {
      setUIState(getDefaultUIState());
      setSelectedTheme(null);
    }
  }, [activeTheme?.id, snapshot?.page]);

  // Auto-dismiss status messages
  useEffect(() => {
    if (!status) return;
    const timer = window.setTimeout(() => setStatus(null), 3500);
    return () => window.clearTimeout(timer);
  }, [status]);

  // Handle field change
  const handleFieldChange = useCallback((fieldId: string, value: unknown) => {
    setUIState(prev => ({
      ...prev,
      [fieldId]: value
    }));
  }, []);

  // Save theme
  const handleSave = useCallback(async () => {
    if (!selectedTheme || isSaving) return;

    try {
      setIsSaving(true);
      setStatus(null); // Clear any existing status

      // Convert UI state to database format
      const dbState = uiToDatabase(uiState);
      
      // Debug: Log what we're trying to save
      console.log('Saving theme:', {
        themeId: selectedTheme.id,
        themeName: selectedTheme.name,
        userId: selectedTheme.user_id,
        isUserTheme: selectedTheme.user_id !== null && selectedTheme.user_id !== undefined,
        dbStateKeys: Object.keys(dbState)
      });

      // Merge with existing theme data to preserve fields not in UI state
      const existingThemeData = selectedTheme ? {
        color_tokens: typeof selectedTheme.color_tokens === 'string' 
          ? JSON.parse(selectedTheme.color_tokens) 
          : selectedTheme.color_tokens,
        typography_tokens: typeof selectedTheme.typography_tokens === 'string'
          ? JSON.parse(selectedTheme.typography_tokens)
          : selectedTheme.typography_tokens,
        spacing_tokens: typeof selectedTheme.spacing_tokens === 'string'
          ? JSON.parse(selectedTheme.spacing_tokens)
          : selectedTheme.spacing_tokens,
        shape_tokens: typeof selectedTheme.shape_tokens === 'string'
          ? JSON.parse(selectedTheme.shape_tokens)
          : selectedTheme.shape_tokens,
        motion_tokens: typeof selectedTheme.motion_tokens === 'string'
          ? JSON.parse(selectedTheme.motion_tokens)
          : selectedTheme.motion_tokens,
        iconography_tokens: typeof selectedTheme.iconography_tokens === 'string'
          ? JSON.parse(selectedTheme.iconography_tokens)
          : selectedTheme.iconography_tokens,
        widget_styles: typeof selectedTheme.widget_styles === 'string'
          ? JSON.parse(selectedTheme.widget_styles)
          : selectedTheme.widget_styles,
        page_background: selectedTheme.page_background,
        widget_background: selectedTheme.widget_background,
        widget_border_color: selectedTheme.widget_border_color,
        page_primary_font: selectedTheme.page_primary_font,
        page_secondary_font: selectedTheme.page_secondary_font,
        widget_primary_font: selectedTheme.widget_primary_font,
        widget_secondary_font: selectedTheme.widget_secondary_font,
      } : {};

      // Deep merge: UI state overrides existing theme data
      const themeData: any = {
        name: selectedTheme.name,
        color_tokens: { ...(existingThemeData.color_tokens || {}), ...(dbState.color_tokens || {}) },
        typography_tokens: { ...(existingThemeData.typography_tokens || {}), ...(dbState.typography_tokens || {}) },
        spacing_tokens: { ...(existingThemeData.spacing_tokens || {}), ...(dbState.spacing_tokens || {}) },
        shape_tokens: { ...(existingThemeData.shape_tokens || {}), ...(dbState.shape_tokens || {}) },
        motion_tokens: { ...(existingThemeData.motion_tokens || {}), ...(dbState.motion_tokens || {}) },
        iconography_tokens: { ...(existingThemeData.iconography_tokens || {}), ...(dbState.iconography_tokens || {}) },
        widget_styles: { ...(existingThemeData.widget_styles || {}), ...(dbState.widget_styles || {}) },
        page_background: dbState.page_background ?? existingThemeData.page_background,
        widget_background: dbState.widget_background ?? existingThemeData.widget_background,
        widget_border_color: dbState.widget_border_color ?? existingThemeData.widget_border_color,
        page_primary_font: existingThemeData.page_primary_font,
        page_secondary_font: existingThemeData.page_secondary_font,
        widget_primary_font: existingThemeData.widget_primary_font,
        widget_secondary_font: existingThemeData.widget_secondary_font,
      };

      // Check if it's a system theme (user_id is null)
      if (selectedTheme.user_id === null || selectedTheme.user_id === undefined) {
        // System theme - check if custom version exists
        const customName = `Custom - ${selectedTheme.name}`;
        const existingCustom = themeLibrary?.user?.find(t => t.name === customName);

        if (existingCustom) {
          // Update existing custom theme
          const updateResult = await updateMutation.mutateAsync({
            themeId: existingCustom.id,
            data: themeData
          });
          console.log('Updated custom theme:', updateResult);
        } else {
          // Create new custom theme
          const response = await createMutation.mutateAsync({
            name: customName,
            ...themeData
          });

          console.log('Created custom theme:', response);
          
          if (response.theme_id) {
            // Update page to use new theme
            const newThemeId = typeof response.theme_id === 'string' 
              ? parseInt(response.theme_id, 10) 
              : response.theme_id;
            
            // Refresh theme library to get new theme
            await queryClient.invalidateQueries({ queryKey: queryKeys.themes() });
            
            // Update selected theme to the new custom theme
            await queryClient.refetchQueries({ queryKey: queryKeys.themes() });
            const refreshedLibrary = await queryClient.fetchQuery({ queryKey: queryKeys.themes() });
            const newTheme = refreshedLibrary?.user?.find(t => t.id === newThemeId);
            if (newTheme) {
              setSelectedTheme(newTheme);
            }
          }
        }
      } else {
        // User theme - update directly
        console.log('Updating user theme:', selectedTheme.id, selectedTheme.name);
        const updateResult = await updateMutation.mutateAsync({
          themeId: selectedTheme.id,
          data: themeData
        });
        console.log('Update result:', updateResult);
        
        // Refresh the selected theme data
        await queryClient.refetchQueries({ queryKey: queryKeys.themes() });
        const refreshedLibrary = await queryClient.fetchQuery({ queryKey: queryKeys.themes() });
        const updatedTheme = refreshedLibrary?.user?.find(t => t.id === selectedTheme.id) ||
                            refreshedLibrary?.system?.find(t => t.id === selectedTheme.id);
        if (updatedTheme) {
          setSelectedTheme(updatedTheme);
        }
      }

      // Save page-level fields (profile image styling and page title effects)
      const pageFields: Record<string, string | number> = {};
      
      // Profile image fields
      const profileImageFields = [
        'profile-image-size',
        'profile-image-radius',
        'profile-image-effect',
        'profile-image-shadow-color',
        'profile-image-shadow-intensity',
        'profile-image-shadow-depth',
        'profile-image-shadow-blur',
        'profile-image-glow-color',
        'profile-image-glow-width',
        'profile-image-border-color',
        'profile-image-border-width'
      ];

      profileImageFields.forEach(fieldId => {
        const value = uiState[fieldId];
        if (value !== undefined && value !== null) {
          const dbFieldName = fieldId.replace('profile-image-', 'profile_image_').replace(/-/g, '_');
          pageFields[dbFieldName] = typeof value === 'number' ? value : String(value);
        }
      });

      // Page title effect (page-level field)
      const pageTitleEffect = uiState['page-title-effect'];
      if (pageTitleEffect !== undefined) {
        pageFields['page_name_effect'] = pageTitleEffect === 'none' || pageTitleEffect === '' ? null : String(pageTitleEffect);
      }

      if (Object.keys(pageFields).length > 0) {
        console.log('Saving page fields:', pageFields);
        await updatePageMutation.mutateAsync(pageFields);
      }

      // Invalidate queries - this will trigger refetch and update UI state via useEffect
      await queryClient.invalidateQueries({ queryKey: queryKeys.themes() });
      await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
      
      // Show success message
      setStatus({ tone: 'success', message: 'Theme saved successfully!' });
    } catch (error) {
      console.error('Failed to save theme:', error);
      setStatus({ 
        tone: 'error', 
        message: error instanceof Error ? error.message : 'Failed to save theme. Please try again.' 
      });
    } finally {
      setIsSaving(false);
    }
  }, [selectedTheme, uiState, isSaving, updateMutation, createMutation, updatePageMutation, queryClient, themeLibrary]);

  // Handle theme selection (opens editor)
  const handleSelectTheme = useCallback((theme: ThemeRecord) => {
    setSelectedTheme(theme);
    const page = snapshot?.page ?? null;
    const initialState = databaseToUI(theme, page);
    setUIState(initialState);
    setViewMode('editor');
  }, [snapshot?.page]);

  // Handle applying theme (sets as active without opening editor)
  const handleApplyTheme = useCallback(async (theme: ThemeRecord) => {
    // Skip if theme is already active
    const currentThemeId = snapshot?.page?.theme_id ?? null;
    if (currentThemeId !== null && currentThemeId === theme.id) {
      // Theme is already active, no need to apply again
      return;
    }

    try {
      // Extract page background from theme
      let pageBackground: string | null | undefined = theme.page_background;
      
      // If page_background is not set, try to extract from color_tokens
      if (!pageBackground && theme.color_tokens) {
        try {
          const colorTokens = typeof theme.color_tokens === 'string' 
            ? JSON.parse(theme.color_tokens) 
            : theme.color_tokens;
          
          if (colorTokens?.semantic?.surface?.canvas) {
            pageBackground = colorTokens.semantic.surface.canvas as string;
          } else if (colorTokens?.semantic?.surface?.background) {
            pageBackground = colorTokens.semantic.surface.background as string;
          } else if (colorTokens?.gradient?.page) {
            pageBackground = colorTokens.gradient.page as string;
          }
        } catch (e) {
          console.warn('Failed to parse color_tokens:', e);
        }
      }
      
      // Parse widget_styles if it's a string
      let widgetStyles: Record<string, unknown> | string | null = null;
      if (theme.widget_styles) {
        if (typeof theme.widget_styles === 'string') {
          try {
            widgetStyles = JSON.parse(theme.widget_styles);
          } catch (e) {
            widgetStyles = theme.widget_styles;
          }
        } else {
          widgetStyles = theme.widget_styles;
        }
      }
      
      // Use updatePageThemeId to set theme as active
      await updatePageThemeId(theme.id, {
        page_background: pageBackground ?? null,
        widget_background: theme.widget_background ?? null,
        widget_border_color: theme.widget_border_color ?? null,
        page_primary_font: theme.page_primary_font ?? null,
        page_secondary_font: theme.page_secondary_font ?? null,
        widget_primary_font: theme.widget_primary_font ?? null,
        widget_secondary_font: theme.widget_secondary_font ?? null,
        widget_styles: widgetStyles,
        spatial_effect: theme.spatial_effect ?? null
      });
      
      // Invalidate and refetch queries to update the UI
      await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
      await queryClient.refetchQueries({ queryKey: queryKeys.pageSnapshot() });
      setStatus({ tone: 'success', message: `Theme "${theme.name}" applied successfully.` });
    } catch (error) {
      console.error('Failed to apply theme:', error);
      setStatus({ tone: 'error', message: 'Failed to apply theme. Please try again.' });
    }
  }, [queryClient, snapshot?.page?.theme_id]);

  // Handle create new theme
  const handleCreateNew = useCallback(() => {
    const newTheme: ThemeRecord = {
      id: 0,
      name: 'New Theme',
      user_id: null
    };
    setSelectedTheme(newTheme);
    setUIState(getDefaultUIState());
    setViewMode('editor');
  }, []);

  // Handle delete theme (opens confirmation dialog)
  const handleDeleteTheme = useCallback((theme: ThemeRecord) => {
    // Only allow deleting user themes (not system themes)
    if (!theme.user_id || theme.user_id === null) {
      return;
    }

    setThemeToDelete(theme);
    setDeleteDialogOpen(true);
  }, []);

  // Confirm delete theme (called from dialog)
  const handleConfirmDelete = useCallback(async () => {
    if (!themeToDelete) return;

    try {
      await deleteMutation.mutateAsync(themeToDelete.id);
      
      // If the deleted theme was selected, go back to library view
      if (selectedTheme?.id === themeToDelete.id) {
        setViewMode('library');
        setSelectedTheme(null);
      }
      
      // Refresh theme library
      await queryClient.invalidateQueries({ queryKey: queryKeys.themes() });
      await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
      setStatus({ tone: 'success', message: `Theme "${themeToDelete.name}" deleted successfully.` });
    } catch (error) {
      console.error('Failed to delete theme:', error);
      setStatus({ tone: 'error', message: 'Failed to delete theme. Please try again.' });
    } finally {
      setThemeToDelete(null);
      setDeleteDialogOpen(false);
    }
  }, [deleteMutation, queryClient, selectedTheme, themeToDelete]);

  // Generate CSS variables for preview
  const previewCSSVars = useMemo(() => {
    return previewRenderer.generateCSSVariables(selectedTheme, uiState);
  }, [selectedTheme, uiState]);

  if (themesLoading) {
    return (
      <div className={styles.loading}>
        <p>Loading themes...</p>
      </div>
    );
  }

  return (
    <div className={styles.panel}>
      {viewMode === 'library' ? (
        <ScrollArea.Root className={styles.scrollArea}>
          <ScrollArea.Viewport className={styles.viewport}>
            <ThemeLibraryView
              themeLibrary={themeLibrary}
              activeTheme={activeTheme}
              onSelectTheme={handleSelectTheme}
              onApplyTheme={handleApplyTheme}
              onCreateNew={handleCreateNew}
              onDeleteTheme={handleDeleteTheme}
              activeColor={activeColor}
            />
          </ScrollArea.Viewport>
          <ScrollArea.Scrollbar orientation="vertical" className={styles.scrollbar}>
            <ScrollArea.Thumb className={styles.thumb} />
          </ScrollArea.Scrollbar>
        </ScrollArea.Root>
      ) : (
        <ThemeEditorView
          theme={selectedTheme}
          uiState={uiState}
          onFieldChange={handleFieldChange}
          onSave={handleSave}
          onBack={() => setViewMode('library')}
          isSaving={isSaving}
          previewCSSVars={previewCSSVars}
          activeColor={activeColor}
        />
      )}

      {/* Status Message */}
      {status && (
        <div className={`${styles.statusMessage} ${styles[`statusMessage_${status.tone}`]}`}>
          {status.message}
        </div>
      )}

      {/* Delete Confirmation Dialog */}
      <ConfirmDeleteDialog
        isOpen={deleteDialogOpen}
        onClose={() => {
          setDeleteDialogOpen(false);
          setThemeToDelete(null);
        }}
        onConfirm={handleConfirmDelete}
        title="Delete Theme"
        message={themeToDelete ? `Are you sure you want to delete "${themeToDelete.name}"? This action cannot be undone.` : ''}
        confirmLabel="Delete"
        cancelLabel="Cancel"
      />
    </div>
  );
}

