/**
 * Themes Panel
 * Main component for theme management with extensible architecture
 */

import { useState, useCallback, useMemo, useEffect, useRef } from 'react';
import * as ScrollArea from '@radix-ui/react-scroll-area';
import { usePageSnapshot, usePageAppearanceMutation, updatePageThemeId } from '../../api/page';
import { useThemeLibraryQuery, useUpdateThemeMutation } from '../../api/themes';
import { useQueryClient } from '@tanstack/react-query';
import { queryKeys } from '../../api/utils';
import type { ThemeRecord } from '../../api/types';
import type { TabColorTheme } from '../layout/tab-colors';
import { databaseToUI, uiToDatabase, mergeThemeWithUIState, getDefaultUIState } from './themes/utils/themeMapper';
import { sectionRegistry } from './themes/utils/sectionRegistry';
import { previewRenderer } from './themes/utils/previewRenderer';
import { ThemeLibraryView } from './themes/ThemeLibraryView';
import { ThemeEditorView } from './themes/ThemeEditorView';
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
  const updatePageMutation = usePageAppearanceMutation();
  const queryClient = useQueryClient();

  const [viewMode, setViewMode] = useState<ViewMode>('editor');
  const [selectedTheme, setSelectedTheme] = useState<ThemeRecord | null>(null);
  const [uiState, setUIState] = useState<Record<string, unknown>>({});
  const [isSaving, setIsSaving] = useState(false);
  const [autoSaveStatus, setAutoSaveStatus] = useState<'idle' | 'saving' | 'saved' | 'error'>('idle');
  const [status, setStatus] = useState<StatusMessage | null>(null);
  const autoSaveTimeoutRef = useRef<NodeJS.Timeout | null>(null);
  // Ref to store latest uiState to avoid circular dependency in handleSave
  const uiStateRef = useRef<Record<string, unknown>>(uiState);
  // Ref to store latest selectedTheme to avoid stale closure in autosave timeout
  const selectedThemeRef = useRef<ThemeRecord | null>(selectedTheme);

  // Derive active theme from theme library (user themes retired - only system themes)
  const activeTheme = useMemo(() => {
    if (!themeLibrary) return null;
    const themeId = snapshot?.page?.theme_id ?? null;
    if (themeId == null) {
      return themeLibrary.system?.[0] ?? null;
    }
    return themeLibrary.system?.find(theme => theme.id === themeId) ?? themeLibrary.system?.[0] ?? null;
  }, [themeLibrary, snapshot?.page?.theme_id]);

  // Initialize UI state when theme changes
  useEffect(() => {
    if (activeTheme) {
      const page = snapshot?.page ?? null;
      const initialState = databaseToUI(activeTheme, page);
      setUIState(initialState);
      uiStateRef.current = initialState; // Update ref
      setSelectedTheme(activeTheme);
      selectedThemeRef.current = activeTheme; // Update ref
      // Ensure editor view is shown when theme is available
      setViewMode('editor');
    } else {
      const defaultState = getDefaultUIState();
      setUIState(defaultState);
      uiStateRef.current = defaultState; // Update ref
      setSelectedTheme(null);
      selectedThemeRef.current = null; // Update ref
      // Show library if no theme is available
      setViewMode('library');
    }
  }, [activeTheme?.id, snapshot?.page]);

  // Keep selectedThemeRef in sync with selectedTheme (safety net)
  useEffect(() => {
    selectedThemeRef.current = selectedTheme;
  }, [selectedTheme]);

  // Auto-dismiss status messages
  useEffect(() => {
    if (!status) return;
    const timer = window.setTimeout(() => setStatus(null), 3500);
    return () => window.clearTimeout(timer);
  }, [status]);

  // Save theme (autosave if isAutoSave is true)
  const handleSave = useCallback(async (isAutoSave = false) => {
    // Use refs to get latest values to avoid stale closure issues
    const currentSelectedTheme = selectedThemeRef.current;
    const currentUIState = uiStateRef.current;
    
    if (!currentSelectedTheme || isSaving) {
      return;
    }

    try {
      setIsSaving(true);
      setStatus(null); // Clear any existing status

      // Convert UI state to database format
      const dbState = uiToDatabase(currentUIState);
      
      // Debug: Log what we're trying to save
      console.log('Saving theme:', {
        themeId: currentSelectedTheme.id,
        themeName: currentSelectedTheme.name,
        userId: currentSelectedTheme.user_id,
        isUserTheme: currentSelectedTheme.user_id !== null && currentSelectedTheme.user_id !== undefined,
        dbStateKeys: Object.keys(dbState),
        spacingTokens: dbState.spacing_tokens,
        uiStatePageSpacing: currentUIState['page-spacing'],
        pageSpacingValue: dbState.spacing_tokens?.page_spacing,
        fullDbState: JSON.stringify(dbState, null, 2)
      });

      // Merge with existing theme data to preserve fields not in UI state
      const existingThemeData = currentSelectedTheme ? {
        color_tokens: typeof currentSelectedTheme.color_tokens === 'string' 
          ? JSON.parse(currentSelectedTheme.color_tokens) 
          : currentSelectedTheme.color_tokens,
        typography_tokens: typeof currentSelectedTheme.typography_tokens === 'string'
          ? JSON.parse(currentSelectedTheme.typography_tokens)
          : currentSelectedTheme.typography_tokens,
        spacing_tokens: typeof currentSelectedTheme.spacing_tokens === 'string'
          ? JSON.parse(currentSelectedTheme.spacing_tokens)
          : currentSelectedTheme.spacing_tokens,
        shape_tokens: typeof currentSelectedTheme.shape_tokens === 'string'
          ? JSON.parse(currentSelectedTheme.shape_tokens)
          : currentSelectedTheme.shape_tokens,
        motion_tokens: typeof currentSelectedTheme.motion_tokens === 'string'
          ? JSON.parse(currentSelectedTheme.motion_tokens)
          : currentSelectedTheme.motion_tokens,
        iconography_tokens: typeof currentSelectedTheme.iconography_tokens === 'string'
          ? JSON.parse(currentSelectedTheme.iconography_tokens)
          : currentSelectedTheme.iconography_tokens,
        widget_styles: typeof currentSelectedTheme.widget_styles === 'string'
          ? JSON.parse(currentSelectedTheme.widget_styles)
          : currentSelectedTheme.widget_styles,
        page_background: currentSelectedTheme.page_background,
        widget_background: currentSelectedTheme.widget_background,
        widget_border_color: currentSelectedTheme.widget_border_color,
        page_primary_font: currentSelectedTheme.page_primary_font,
        page_secondary_font: currentSelectedTheme.page_secondary_font,
        widget_primary_font: currentSelectedTheme.widget_primary_font,
        widget_secondary_font: currentSelectedTheme.widget_secondary_font,
      } : {};

      // Deep merge: UI state overrides existing theme data
      const themeData: any = {
        name: currentSelectedTheme.name,
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
      if (currentSelectedTheme.user_id === null || currentSelectedTheme.user_id === undefined) {
        // System theme - check if custom version exists
        const customName = `Custom - ${currentSelectedTheme.name}`;
        const existingCustom = themeLibrary?.user?.find(t => t.name === customName);

        if (existingCustom) {
          // Update existing custom theme
          const updateResult = await updateMutation.mutateAsync({
            themeId: existingCustom.id,
            data: themeData
          });
          console.log('Updated custom theme:', updateResult);
          
          // Refresh the selected theme data
          await queryClient.refetchQueries({ queryKey: queryKeys.themes() });
          const refreshedLibrary = await queryClient.fetchQuery({ queryKey: queryKeys.themes() });
          const updatedTheme = refreshedLibrary?.user?.find(t => t.id === existingCustom.id) ||
                              refreshedLibrary?.system?.find(t => t.id === existingCustom.id);
          if (updatedTheme) {
            setSelectedTheme(updatedTheme);
            selectedThemeRef.current = updatedTheme; // Update ref
          }
        } else {
          // User themes retired - only system themes can be customized
          throw new Error('Cannot create new themes. Please select a system theme to customize.');
        }
      } else {
        // User theme - update directly
        console.log('Updating user theme:', currentSelectedTheme.id, currentSelectedTheme.name);
        const updateResult = await updateMutation.mutateAsync({
          themeId: currentSelectedTheme.id,
          data: themeData
        });
        console.log('Update result:', updateResult);
        
        // Refresh the selected theme data
        await queryClient.refetchQueries({ queryKey: queryKeys.themes() });
        const refreshedLibrary = await queryClient.fetchQuery({ queryKey: queryKeys.themes() });
        const updatedTheme = refreshedLibrary?.user?.find(t => t.id === currentSelectedTheme.id) ||
                            refreshedLibrary?.system?.find(t => t.id === currentSelectedTheme.id);
        if (updatedTheme) {
          setSelectedTheme(updatedTheme);
          selectedThemeRef.current = updatedTheme; // Update ref
        }
      }

      // Save page-level fields (profile image styling and page title effects)
      const pageFields: Record<string, string | number | boolean | null> = {};
      
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
        const value = currentUIState[fieldId];
        if (value !== undefined && value !== null) {
          const dbFieldName = fieldId.replace('profile-image-', 'profile_image_').replace(/-/g, '_');
          pageFields[dbFieldName] = typeof value === 'number' ? value : String(value);
        }
      });

      // Page title effect (page-level field)
      // Note: This field can be null (for 'none' or empty), which is valid in the database
      const pageTitleEffect = currentUIState['page-title-effect'];
      if (pageTitleEffect !== undefined) {
        pageFields['page_name_effect'] = pageTitleEffect === 'none' || pageTitleEffect === '' ? null : String(pageTitleEffect);
      }

      // Page background animation (page-level field)
      const pageBackgroundAnimate = currentUIState['page-background-animate'];
      if (pageBackgroundAnimate !== undefined) {
        pageFields['page_background_animate'] = Boolean(pageBackgroundAnimate);
      }

      if (Object.keys(pageFields).length > 0) {
        await updatePageMutation.mutateAsync(pageFields);
      }

      // Invalidate queries - this will trigger refetch and update UI state via useEffect
      await queryClient.invalidateQueries({ queryKey: queryKeys.themes() });
      await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
      
      // Update autosave status
      setAutoSaveStatus('saved');
      
      // Show success message only for manual saves
      if (!isAutoSave) {
        setStatus({ tone: 'success', message: 'Theme saved successfully!' });
      }
      
      // Reset saved status after 2 seconds
      setTimeout(() => {
        setAutoSaveStatus(prev => prev === 'saved' ? 'idle' : prev);
      }, 2000);
    } catch (error) {
      console.error('Failed to save theme:', error);
      setAutoSaveStatus('error');
      setStatus({ 
        tone: 'error', 
        message: error instanceof Error ? error.message : 'Failed to save theme. Please try again.' 
      });
      
      // Reset error status after 3 seconds
      setTimeout(() => {
        setAutoSaveStatus('idle');
      }, 3000);
    } finally {
      setIsSaving(false);
    }
  }, [isSaving, updateMutation, updatePageMutation, queryClient, themeLibrary]);

  // Handle field change
  const handleFieldChange = useCallback((fieldId: string, value: unknown) => {
    setUIState(prev => {
      const newState = {
        ...prev,
        [fieldId]: value
      };
      // Update ref immediately with new state
      uiStateRef.current = newState;
      return newState;
    });
    
    // Trigger autosave after a delay (debounce)
    // Use ref to check current theme (handles theme changes during debounce)
    if (selectedThemeRef.current) {
      // Clear existing timeout
      if (autoSaveTimeoutRef.current) {
        clearTimeout(autoSaveTimeoutRef.current);
      }
      
      // Set status to saving (will be updated after save completes)
      setAutoSaveStatus('saving');
      
      // Schedule autosave after 1 second of inactivity
      autoSaveTimeoutRef.current = setTimeout(() => {
        handleSave(true); // Pass true to indicate it's an autosave
      }, 1000);
    }
  }, [handleSave]);
  
  // Cleanup autosave timeout on unmount
  useEffect(() => {
    return () => {
      if (autoSaveTimeoutRef.current) {
        clearTimeout(autoSaveTimeoutRef.current);
      }
    };
  }, []);

  // Handle theme selection (opens editor)
  const handleSelectTheme = useCallback((theme: ThemeRecord) => {
    setSelectedTheme(theme);
    selectedThemeRef.current = theme; // Update ref
    const page = snapshot?.page ?? null;
    const initialState = databaseToUI(theme, page);
    setUIState(initialState);
    uiStateRef.current = initialState; // Update ref
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
  }, [queryClient, snapshot?.page]);


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
          onSave={() => handleSave(false)}
          onBack={() => setViewMode('library')}
          isSaving={isSaving}
          autoSaveStatus={autoSaveStatus}
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

    </div>
  );
}

