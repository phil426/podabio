
/**
 * Utility to extract used colors from the current theme state
 */

export function getThemeColors(uiState: Record<string, unknown>): string[] {
    const colors = new Set<string>();

    // Iterate through all values in uiState
    Object.values(uiState).forEach(value => {
        if (typeof value !== 'string') return;

        // Check for hex colors (e.g. #ffffff, #fff)
        if (value.match(/^#[0-9a-fA-F]{3,8}$/)) {
            colors.add(value.toLowerCase());
        }
        // Check for gradients
        else if (value.includes('gradient(')) {
            colors.add(value);
        }
        // Check for rgb/rgba
        else if (value.startsWith('rgb')) {
            colors.add(value);
        }
    });

    return Array.from(colors).sort();
}

