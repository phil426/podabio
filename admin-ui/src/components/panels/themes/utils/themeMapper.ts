/**
 * Theme Mapper
 * Complete data transformation between UI state and database JSON tokens
 * Handles ALL fields (implemented + deferred) - architecture supports everything
 */

import { fieldRegistry } from './fieldRegistry';
import { sectionRegistry } from './sectionRegistry';
import type { ThemeRecord } from '../../../api/types';

export interface ThemeUIState {
  [fieldId: string]: unknown;
}

export interface ThemeDatabaseState {
  typography_tokens?: Record<string, unknown>;
  widget_styles?: Record<string, unknown>;
  iconography_tokens?: Record<string, unknown>;
  spacing_tokens?: Record<string, unknown>;
  color_tokens?: Record<string, unknown>;
  shape_tokens?: Record<string, unknown>;
  motion_tokens?: Record<string, unknown>;
}

/**
 * Convert UI state to database format (JSON tokens + direct columns)
 * Handles ALL fields from fieldRegistry, even if UI not implemented
 */
export function uiToDatabase(uiState: ThemeUIState): ThemeDatabaseState & { 
  page_background?: string | null;
  widget_background?: string | null;
  widget_border_color?: string | null;
} {
  const dbState: ThemeDatabaseState & { 
    page_background?: string | null;
    widget_background?: string | null;
    widget_border_color?: string | null;
  } = {
    typography_tokens: {},
    widget_styles: {},
    iconography_tokens: {},
    spacing_tokens: {},
    color_tokens: {},
    shape_tokens: {},
    motion_tokens: {}
  };

  // Process all fields from registry
  const allFields = fieldRegistry.getAllFields();

  for (const field of allFields) {
    const value = uiState[field.id];
    
    // Skip if value is undefined (not set in UI)
    // Note: 0, false, and empty string are valid values and should be saved
    if (value === undefined) continue;

    // Skip page-level fields (they're saved separately to the page, not theme)
    if (field.tokenPath.startsWith('page.')) {
      continue;
    }

    // Handle direct columns
    if (field.tokenPath === 'page_background') {
      dbState.page_background = value as string;
      continue;
    }
    if (field.tokenPath === 'widget_background') {
      dbState.widget_background = value as string;
      continue;
    }
    if (field.tokenPath === 'widget_styles.border_color') {
      dbState.widget_border_color = value as string;
      // Also set in widget_styles
      if (!dbState.widget_styles) dbState.widget_styles = {};
      setNestedValue(dbState.widget_styles, 'border_color', value);
      continue;
    }

    // Handle JSON token paths
    if (field.tokenPath.startsWith('typography_tokens.')) {
      const path = field.tokenPath.replace('typography_tokens.', '');
      if (!dbState.typography_tokens) dbState.typography_tokens = {};
      setNestedValue(dbState.typography_tokens, path, value);
    } else if (field.tokenPath.startsWith('widget_styles.')) {
      const path = field.tokenPath.replace('widget_styles.', '');
      if (!dbState.widget_styles) dbState.widget_styles = {};
      setNestedValue(dbState.widget_styles, path, value);
      // Debug: Log widget_styles saves
      if (field.id === 'widget-border-width') {
        console.log('Saving widget-border-width:', { fieldId: field.id, value, path, widget_styles: dbState.widget_styles });
      }
    } else if (field.tokenPath.startsWith('iconography_tokens.')) {
      const path = field.tokenPath.replace('iconography_tokens.', '');
      if (!dbState.iconography_tokens) dbState.iconography_tokens = {};
      // Format size and spacing with units if they're numbers
      let formattedValue = value;
      if (path === 'size' && typeof value === 'number') {
        formattedValue = `${value}px`;
      } else if (path === 'spacing' && typeof value === 'number') {
        formattedValue = `${value}rem`;
      }
      setNestedValue(dbState.iconography_tokens, path, formattedValue);
    } else if (field.tokenPath.startsWith('spacing_tokens.')) {
      const path = field.tokenPath.replace('spacing_tokens.', '');
      if (!dbState.spacing_tokens) dbState.spacing_tokens = {};
      setNestedValue(dbState.spacing_tokens, path, value);
    } else if (field.tokenPath.startsWith('podcast_player.')) {
      // Store podcast player fields in color_tokens
      const path = field.tokenPath.replace('podcast_player.', '');
      if (!dbState.color_tokens) dbState.color_tokens = {};
      if (!dbState.color_tokens.podcast_player) {
        dbState.color_tokens.podcast_player = {};
      }
      setNestedValue(dbState.color_tokens.podcast_player as Record<string, unknown>, path, value);
    }
  }

  // Clean up empty objects
  Object.keys(dbState).forEach(key => {
    if (key === 'page_background' || key === 'widget_background' || key === 'widget_border_color') {
      return; // Skip direct columns
    }
    const tokenKey = key as keyof ThemeDatabaseState;
    if (dbState[tokenKey] && typeof dbState[tokenKey] === 'object' && Object.keys(dbState[tokenKey] as Record<string, unknown>).length === 0) {
      delete dbState[tokenKey];
    }
  });

  return dbState;
}

/**
 * Convert database format (JSON tokens) to UI state
 * Handles ALL fields from fieldRegistry, provides defaults for missing
 * @param theme - Theme record (for theme-level fields)
 * @param page - Page record (for page-level fields like profile_image_*)
 * @param existingUIState - Existing UI state to merge with
 */
export function databaseToUI(
  theme: ThemeRecord | null, 
  page?: Record<string, unknown> | null,
  existingUIState?: ThemeUIState
): ThemeUIState {
  const uiState: ThemeUIState = { ...existingUIState };

  // Get all fields from registry
  const allFields = fieldRegistry.getAllFields();

  for (const field of allFields) {
    // If already in UI state, keep it (unsaved changes take priority)
    if (uiState[field.id] !== undefined) continue;

    let value: unknown;

    // Handle page-level fields (prefixed with 'page.')
    if (field.tokenPath.startsWith('page.')) {
      const pageField = field.tokenPath.replace('page.', '');
      value = page && pageField in page ? page[pageField] : undefined;
    } else {
      // Theme-level fields
      value = getNestedValue(theme, field.tokenPath);
    }

    // Debug: Log widget-border-width loading
    if (field.id === 'widget-border-width') {
      console.log('Loading widget-border-width:', { fieldId: field.id, tokenPath: field.tokenPath, rawValue: value, fieldType: field.type });
    }

    // Parse value based on field type - extract numeric values from strings with units
    let parsedValue: unknown = value;
    if (value !== undefined && (field.type === 'number' || field.type === 'border-width')) {
      // If value is a string with units (e.g., "34px", "0.1rem"), extract the number
      if (typeof value === 'string') {
        // Skip non-numeric strings like "none", "medium", etc.
        if (value === 'none' || value === 'medium' || value === 'auto' || value === 'inherit' || value === 'initial' || value === 'unset') {
          parsedValue = field.defaultValue;
        } else {
          // Remove common units and parse as float
          const numericValue = parseFloat(value.replace(/px|rem|%|em|pt|pc|ex|ch|vw|vh|vmin|vmax|deg|rad|turn|s|ms|Hz|kHz|dpi|dpcm|dppx/gi, '').trim());
          if (!isNaN(numericValue)) {
            parsedValue = numericValue;
          } else {
            // If parsing fails, try to parse the whole string
            const fallbackValue = parseFloat(value);
            parsedValue = !isNaN(fallbackValue) ? fallbackValue : field.defaultValue;
          }
        }
      } else if (typeof value === 'number') {
        parsedValue = value;
      }
    }

    // Use parsed value from theme/page, or default value
    uiState[field.id] = parsedValue !== undefined ? parsedValue : field.defaultValue;
  }

  return uiState;
}

/**
 * Set a nested value in an object using dot notation path
 */
function setNestedValue(obj: Record<string, unknown>, path: string, value: unknown): void {
  const parts = path.split('.');
  let current: any = obj;

  for (let i = 0; i < parts.length - 1; i++) {
    const part = parts[i];
    if (!(part in current) || typeof current[part] !== 'object' || current[part] === null) {
      current[part] = {};
    }
    current = current[part];
  }

  const lastPart = parts[parts.length - 1];
  current[lastPart] = value;
}

/**
 * Get a nested value from an object using dot notation path
 */
function getNestedValue(obj: Record<string, unknown> | null | undefined, path: string): unknown {
  if (!obj) return undefined;

  // Handle direct columns
  if (path === 'page_background' && 'page_background' in obj) {
    return obj.page_background;
  }
  if (path === 'widget_background' && 'widget_background' in obj) {
    return obj.widget_background;
  }
  if (path === 'widget_styles.border_color') {
    // Try direct column first
    if ('widget_border_color' in obj) {
      return obj.widget_border_color;
    }
    // Then try widget_styles JSON
    const widgetStyles = typeof obj.widget_styles === 'string' 
      ? JSON.parse(obj.widget_styles) 
      : obj.widget_styles;
    if (widgetStyles && typeof widgetStyles === 'object' && 'border_color' in widgetStyles) {
      return (widgetStyles as Record<string, unknown>).border_color;
    }
    return undefined;
  }

  // Handle JSON token paths
  if (path.startsWith('typography_tokens.')) {
    const typographyTokens = typeof obj.typography_tokens === 'string'
      ? JSON.parse(obj.typography_tokens)
      : obj.typography_tokens;
    if (!typographyTokens || typeof typographyTokens !== 'object') return undefined;
    const subPath = path.replace('typography_tokens.', '');
    return getNestedValue(typographyTokens as Record<string, unknown>, subPath);
  }
  if (path.startsWith('podcast_player.')) {
    // Podcast player fields are stored in color_tokens.podcast_player
    const colorTokens = typeof obj.color_tokens === 'string'
      ? JSON.parse(obj.color_tokens)
      : obj.color_tokens;
    if (!colorTokens || typeof colorTokens !== 'object') return undefined;
    const podcastPlayer = (colorTokens as Record<string, unknown>).podcast_player;
    if (!podcastPlayer || typeof podcastPlayer !== 'object') return undefined;
    const subPath = path.replace('podcast_player.', '');
    return getNestedValue(podcastPlayer as Record<string, unknown>, subPath);
  }
  if (path.startsWith('widget_styles.')) {
    const widgetStyles = typeof obj.widget_styles === 'string'
      ? JSON.parse(obj.widget_styles)
      : obj.widget_styles;
    if (!widgetStyles || typeof widgetStyles !== 'object') return undefined;
    const subPath = path.replace('widget_styles.', '');
    return getNestedValue(widgetStyles as Record<string, unknown>, subPath);
  }
  if (path.startsWith('iconography_tokens.')) {
    const iconographyTokens = typeof obj.iconography_tokens === 'string'
      ? JSON.parse(obj.iconography_tokens)
      : obj.iconography_tokens;
    if (!iconographyTokens || typeof iconographyTokens !== 'object') return undefined;
    const subPath = path.replace('iconography_tokens.', '');
    return getNestedValue(iconographyTokens as Record<string, unknown>, subPath);
  }
  if (path.startsWith('spacing_tokens.')) {
    const spacingTokens = typeof obj.spacing_tokens === 'string'
      ? JSON.parse(obj.spacing_tokens)
      : obj.spacing_tokens;
    if (!spacingTokens || typeof spacingTokens !== 'object') return undefined;
    const subPath = path.replace('spacing_tokens.', '');
    return getNestedValue(spacingTokens as Record<string, unknown>, subPath);
  }

  // Default: treat as nested path
  const parts = path.split('.');
  let current: any = obj;

  for (const part of parts) {
    if (current === null || current === undefined || typeof current !== 'object') {
      return undefined;
    }
    current = current[part];
  }

  return current;
}

/**
 * Merge theme data with UI state
 * Used when loading a theme - merges theme defaults with any unsaved UI changes
 */
export function mergeThemeWithUIState(
  theme: ThemeRecord | null,
  uiState: ThemeUIState
): ThemeUIState {
  // Start with database values
  const merged = databaseToUI(theme);

  // Override with UI state (unsaved changes take priority)
  Object.assign(merged, uiState);

  return merged;
}

/**
 * Validate a field value
 */
export function validateFieldValue(fieldId: string, value: unknown): boolean {
  const field = fieldRegistry.get(fieldId);
  if (!field) return false;

  // Use custom validation if provided
  if (field.validation) {
    return field.validation(value);
  }

  // Default validation based on type
  switch (field.type) {
    case 'color':
      return typeof value === 'string' && /^#([0-9a-fA-F]{3}){1,2}$/.test(value);
    case 'number':
    case 'size':
    case 'spacing':
    case 'border-width':
    case 'shadow':
    case 'glow':
      if (typeof value !== 'number') return false;
      if (field.min !== undefined && value < field.min) return false;
      if (field.max !== undefined && value > field.max) return false;
      return true;
    case 'select':
      if (!field.options) return true;
      return field.options.some(opt => opt.value === value);
    case 'font':
      return typeof value === 'string' && value.length > 0;
    case 'weight':
      return typeof value === 'object' && value !== null && 
             ('bold' in value || 'italic' in value);
    default:
      return true;
  }
}

/**
 * Get default values for all fields
 */
export function getDefaultUIState(): ThemeUIState {
  const uiState: ThemeUIState = {};
  const allFields = fieldRegistry.getAllFields();

  for (const field of allFields) {
    uiState[field.id] = field.defaultValue;
  }

  return uiState;
}

/**
 * Extract specific token values from theme for preview
 */
export function extractTokenValues(theme: ThemeRecord | null): Record<string, unknown> {
  if (!theme) return {};

  const tokens: Record<string, unknown> = {};

  // Extract all token types
  if (theme.typography_tokens) {
    const parsed = typeof theme.typography_tokens === 'string' 
      ? JSON.parse(theme.typography_tokens)
      : theme.typography_tokens;
    Object.assign(tokens, flattenObject(parsed, 'typography_tokens'));
  }

  if (theme.widget_styles) {
    const parsed = typeof theme.widget_styles === 'string'
      ? JSON.parse(theme.widget_styles)
      : theme.widget_styles;
    Object.assign(tokens, flattenObject(parsed, 'widget_styles'));
  }

  if (theme.iconography_tokens) {
    const parsed = typeof theme.iconography_tokens === 'string'
      ? JSON.parse(theme.iconography_tokens)
      : theme.iconography_tokens;
    Object.assign(tokens, flattenObject(parsed, 'iconography_tokens'));
  }

  if (theme.spacing_tokens) {
    const parsed = typeof theme.spacing_tokens === 'string'
      ? JSON.parse(theme.spacing_tokens)
      : theme.spacing_tokens;
    Object.assign(tokens, flattenObject(parsed, 'spacing_tokens'));
  }

  return tokens;
}

/**
 * Flatten nested object with prefix
 */
function flattenObject(obj: Record<string, unknown>, prefix: string): Record<string, unknown> {
  const result: Record<string, unknown> = {};

  function flatten(current: unknown, path: string): void {
    if (current === null || current === undefined) return;

    if (typeof current === 'object' && !Array.isArray(current)) {
      for (const [key, value] of Object.entries(current)) {
        flatten(value, path ? `${path}.${key}` : key);
      }
    } else {
      result[path ? `${prefix}.${path}` : prefix] = current;
    }
  }

  flatten(obj, '');
  return result;
}

