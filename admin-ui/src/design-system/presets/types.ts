/**
 * Easy Mode Preset Types
 * Defines the contract for high-level style presets that map to granular tokens.
 */

export interface ShapePreset {
    id: string;
    label: string;
    description: string;
    tokens: Record<string, unknown>;
}

export interface ColorPreset {
    id: string;
    label: string;
    category: 'light' | 'dark';
    palette: {
        primary: string;           // Main brand color
        surface: string;           // Card/Panel background
        background: string;        // Page background
        text: string;              // Primary text color
        border: string;            // Widget border color
    };
    // The specific tokens this maps to
    tokens: Record<string, string>;
}

export interface TypographyPreset {
    id: string;
    label: string;
    fonts: {
        heading: string;           // Font family for headers
        body: string;              // Font family for content
    };
    tokens: Record<string, unknown>;
}

export interface SpacingPreset {
    id: string;
    label: string;
    tokens: Record<string, unknown>;
}

export interface EasyModeConfig {
    activeShapeId?: string;
    activeColorId?: string;
    activeTypographyId?: string;
    activeSpacingId?: string;
    activeLayoutId?: string;
}
