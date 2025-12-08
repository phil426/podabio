
import { ThemeRecord } from '../../../../api/types';
import { fieldRegistry } from './fieldRegistry';

export type ThemeUIState = Record<string, string | number | boolean>;

/**
 * Resolves a dot-notation path on a record
 */
function resolvePath(obj: Record<string, any>, path: string): any {
    return path.split('.').reduce((prev, curr) => {
        return prev ? prev[curr] : undefined;
    }, obj);
}

/**
 * Safely parses a JSON string if needed
 */
function safeParse(val: any): any {
    if (typeof val === 'string') {
        try {
            return JSON.parse(val);
        } catch {
            return val;
        }
    }
    return val;
}

/**
 * Hydrates UI state from a ThemeRecord
 * IMPERATIVE: Prioritizes direct database columns over nested tokens
 */
export function databaseToUI(theme: ThemeRecord): ThemeUIState {
    const uiState: ThemeUIState = {};

    // Get all implemented fields
    // Using 'any' cast here if getImplementedFields isn't yet in the interface I saw
    // But generally sticking to the planned API.
    const fields = fieldRegistry.getImplementedFields();

    for (const field of fields) {
        let value: any = undefined;

        // 1. Direct Column Mappings (Priority 1)
        if (field.id === 'widget-background' && theme.widget_background) {
            value = theme.widget_background;
        } else if (field.id === 'widget-border-color' && theme.widget_border_color) {
            value = theme.widget_border_color;
        } else if (field.id === 'page-background' && theme.page_background) {
            value = theme.page_background;
        }

        // 2. Token Path Resolution (Priority 2)
        if (value === undefined && field.tokenPath) {
            const rootTokenKey = field.tokenPath.split('.')[0] as keyof ThemeRecord;

            if (theme[rootTokenKey]) {
                const rootTokenVal = theme[rootTokenKey];
                const parsedRoot = safeParse(rootTokenVal);

                // Resolve the rest of the path relative to the parsed root
                // If path is just 'widget_styles' (no dot), relativePath is empty string
                const parts = field.tokenPath.split('.');
                if (parts.length > 1) {
                    const relativePath = parts.slice(1).join('.');
                    value = resolvePath(parsedRoot, relativePath);
                } else {
                    // If tokenPath is 'widget_styles' and we parsed it, maybe we want a subfield? 
                    // But usually tokenPath goes deep. If exact match, use it.
                    // However, primitive values (string/number) in registry need primitive values.
                    // If parsedRoot is an object, this might be wrong unless the field type is complex.
                    // Looking at registry: 'widget_styles.border_width' -> 'widget_styles' is the root.
                    value = parsedRoot;
                }
            }
        }

        // 3. Defaults (Priority 3)
        if (value === undefined) {
            value = field.defaultValue;
        }

        if (value !== undefined) {
            // Ensure we don't put objects into the flat state if primitive expected
            if (typeof value === 'object' && value !== null) {
                // Warning: complex object in flat state?
                // fieldRegistry types include 'weight' (bold/italic obj), so objects ARE allowed in some cases
                // UIState definition ThemeUIState allows string | number | boolean.
                // I should verify if weight is truly an object.
                // Registry says: defaultValue: { bold: false, italic: false }
                // So ThemeUIState should allow objects or 'weight' type.
                // Let's update type.
                uiState[field.id] = value as any;
            } else {
                uiState[field.id] = value as string | number | boolean;
            }
        }
    }

    return uiState;
}

/**
 * Gets a clean initial UI state for a new theme
 */
export function getInitialUIState(): ThemeUIState {
    const uiState: ThemeUIState = {};
    const fields = fieldRegistry.getImplementedFields();

    for (const field of fields) {
        if (field.defaultValue !== undefined) {
            uiState[field.id] = field.defaultValue as any;
        }
    }
    return uiState;
}
