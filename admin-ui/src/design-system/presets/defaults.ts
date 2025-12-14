import { ShapePreset, ColorPreset, TypographyPreset, SpacingPreset } from './types';

export const SHAPE_PRESETS: ShapePreset[] = [
    {
        id: 'soft',
        label: 'Pill',
        description: 'Fully rounded pill shape',
        tokens: {
            'widget-rounding': 50,
            'widget-border-width': 0,
            'widget-border-effect': 'none', // clean look
            'widget-shadow-depth': 1,
        }
    },
    {
        id: 'sharp',
        label: 'Sharp',
        description: 'Modern and brutalist with hard edges',
        tokens: {
            'widget-rounding': 0,
            'widget-border-width': 2,
            'widget-border-effect': 'none',
        }
    },
    {
        id: 'card',
        label: 'Card',
        description: 'Traditional card style with slight rounding',
        tokens: {
            'widget-rounding': 8,
            'widget-border-width': 1,
            'widget-border-effect': 'shadow',
            'widget-shadow-depth': 2,
        }
    }
];

export const COLOR_PRESETS: ColorPreset[] = [
    // --- LIGHT THEMES (12) ---
    {
        id: 'light-clean',
        label: 'Clean',
        category: 'light',
        palette: { primary: '#000000', surface: '#ffffff', background: '#ffffff', text: '#0f172a', border: '#e2e8f0' },
        tokens: { 'page-background': '#ffffff', 'widget-background': '#ffffff', 'widget-border-color': '#e2e8f0', 'widget-heading-color': '#0f172a', 'widget-body-color': '#475569', 'page-title-color': '#0f172a', 'page-bio-color': '#475569', 'social-icon-color': '#0f172a' }
    },
    {
        id: 'writer-parchment',
        label: 'Parchment',
        category: 'light',
        palette: { primary: '#3f2e22', surface: '#f5f1e6', background: '#ebe5ce', text: '#2b2621', border: '#dcd3b8' },
        tokens: { 'page-background': '#ebe5ce', 'widget-background': '#f5f1e6', 'widget-border-color': '#dcd3b8', 'widget-heading-color': '#2b2621', 'widget-body-color': '#4b3e34', 'page-title-color': '#2b2621', 'page-bio-color': '#4b3e34', 'social-icon-color': '#3f2e22' }
    },
    {
        id: 'light-ocean',
        label: 'Ocean',
        category: 'light',
        palette: { primary: '#0ea5e9', surface: '#f0f9ff', background: '#e0f2fe', text: '#0c4a6e', border: '#bae6fd' },
        tokens: { 'page-background': '#e0f2fe', 'widget-background': '#f0f9ff', 'widget-border-color': '#bae6fd', 'widget-heading-color': '#0c4a6e', 'widget-body-color': '#0369a1', 'page-title-color': '#0c4a6e', 'page-bio-color': '#0369a1', 'social-icon-color': '#0c4a6e' }
    },
    {
        id: 'light-mint',
        label: 'Mint',
        category: 'light',
        palette: { primary: '#10b981', surface: '#ecfdf5', background: '#d1fae5', text: '#064e3b', border: '#6ee7b7' },
        tokens: { 'page-background': '#d1fae5', 'widget-background': '#ecfdf5', 'widget-border-color': '#6ee7b7', 'widget-heading-color': '#064e3b', 'widget-body-color': '#047857', 'page-title-color': '#064e3b', 'page-bio-color': '#047857', 'social-icon-color': '#064e3b' }
    },
    {
        id: 'light-rose',
        label: 'Rose',
        category: 'light',
        palette: { primary: '#f43f5e', surface: '#fff1f2', background: '#ffe4e6', text: '#881337', border: '#fda4af' },
        tokens: { 'page-background': '#ffe4e6', 'widget-background': '#fff1f2', 'widget-border-color': '#fda4af', 'widget-heading-color': '#881337', 'widget-body-color': '#be123c', 'page-title-color': '#881337', 'page-bio-color': '#be123c', 'social-icon-color': '#881337' }
    },
    {
        id: 'light-lavender',
        label: 'Lavender',
        category: 'light',
        palette: { primary: '#8b5cf6', surface: '#f5f3ff', background: '#ede9fe', text: '#4c1d95', border: '#c4b5fd' },
        tokens: { 'page-background': '#ede9fe', 'widget-background': '#f5f3ff', 'widget-border-color': '#c4b5fd', 'widget-heading-color': '#4c1d95', 'widget-body-color': '#6d28d9', 'page-title-color': '#4c1d95', 'page-bio-color': '#6d28d9', 'social-icon-color': '#4c1d95' }
    },
    {
        id: 'light-peach',
        label: 'Peach',
        category: 'light',
        palette: { primary: '#f97316', surface: '#fff7ed', background: '#ffedd5', text: '#7c2d12', border: '#fdba74' },
        tokens: { 'page-background': '#ffedd5', 'widget-background': '#fff7ed', 'widget-border-color': '#fdba74', 'widget-heading-color': '#7c2d12', 'widget-body-color': '#c2410c', 'page-title-color': '#7c2d12', 'page-bio-color': '#c2410c', 'social-icon-color': '#7c2d12' }
    },
    {
        id: 'light-coffee',
        label: 'Coffee',
        category: 'light',
        palette: { primary: '#a8a29e', surface: '#fafaf9', background: '#f5f5f4', text: '#44403c', border: '#d6d3d1' },
        tokens: { 'page-background': '#f5f5f4', 'widget-background': '#fafaf9', 'widget-border-color': '#d6d3d1', 'widget-heading-color': '#44403c', 'widget-body-color': '#78716c', 'page-title-color': '#44403c', 'page-bio-color': '#78716c', 'social-icon-color': '#44403c' }
    },
    {
        id: 'light-lemon',
        label: 'Lemon',
        category: 'light',
        palette: { primary: '#eab308', surface: '#fefce8', background: '#fef9c3', text: '#713f12', border: '#fde047' },
        tokens: { 'page-background': '#fef9c3', 'widget-background': '#fefce8', 'widget-border-color': '#fde047', 'widget-heading-color': '#713f12', 'widget-body-color': '#854d0e', 'page-title-color': '#713f12', 'page-bio-color': '#854d0e', 'social-icon-color': '#713f12' }
    },
    {
        id: 'light-sky',
        label: 'Sky',
        category: 'light',
        palette: { primary: '#38bdf8', surface: '#f0f9ff', background: '#e0f2fe', text: '#0c4a6e', border: '#7dd3fc' },
        tokens: { 'page-background': '#e0f2fe', 'widget-background': '#f0f9ff', 'widget-border-color': '#7dd3fc', 'widget-heading-color': '#0c4a6e', 'widget-body-color': '#0284c7', 'page-title-color': '#0c4a6e', 'page-bio-color': '#0284c7', 'social-icon-color': '#0c4a6e' }
    },
    {
        id: 'light-lime',
        label: 'Lime',
        category: 'light',
        palette: { primary: '#84cc16', surface: '#f7fee7', background: '#ecfccb', text: '#365314', border: '#bef264' },
        tokens: { 'page-background': '#ecfccb', 'widget-background': '#f7fee7', 'widget-border-color': '#bef264', 'widget-heading-color': '#365314', 'widget-body-color': '#4d7c0f', 'page-title-color': '#365314', 'page-bio-color': '#4d7c0f', 'social-icon-color': '#365314' }
    },
    {
        id: 'light-indigo',
        label: 'Indigo',
        category: 'light',
        palette: { primary: '#6366f1', surface: '#eef2ff', background: '#e0e7ff', text: '#312e81', border: '#a5b4fc' },
        tokens: { 'page-background': '#e0e7ff', 'widget-background': '#eef2ff', 'widget-border-color': '#a5b4fc', 'widget-heading-color': '#312e81', 'widget-body-color': '#4338ca', 'page-title-color': '#312e81', 'page-bio-color': '#4338ca', 'social-icon-color': '#312e81' }
    },
    {
        id: 'writer-paper',
        label: 'Paper',
        category: 'light',
        palette: { primary: '#44403c', surface: '#fdfbf7', background: '#f5f5f4', text: '#1c1917', border: '#e7e5e4' },
        tokens: { 'page-background': '#f5f5f4', 'widget-background': '#fdfbf7', 'widget-border-color': '#e7e5e4', 'widget-heading-color': '#1c1917', 'widget-body-color': '#44403c', 'page-title-color': '#1c1917', 'page-bio-color': '#44403c', 'social-icon-color': '#44403c' }
    },
    {
        id: 'writer-notepad',
        label: 'Notepad',
        category: 'light',
        palette: { primary: '#525252', surface: '#ffffff', background: '#e5e5e5', text: '#171717', border: '#d4d4d4' },
        tokens: { 'page-background': '#e5e5e5', 'widget-background': '#ffffff', 'widget-border-color': '#d4d4d4', 'widget-heading-color': '#171717', 'widget-body-color': '#525252', 'page-title-color': '#171717', 'page-bio-color': '#525252', 'social-icon-color': '#171717' }
    },
    {
        id: 'writer-sepia',
        label: 'Sepia',
        category: 'light',
        palette: { primary: '#78350f', surface: '#fef3c7', background: '#fde68a', text: '#451a03', border: '#fcd34d' },
        tokens: { 'page-background': '#fde68a', 'widget-background': '#fef3c7', 'widget-border-color': '#fcd34d', 'widget-heading-color': '#451a03', 'widget-body-color': '#92400e', 'page-title-color': '#451a03', 'page-bio-color': '#92400e', 'social-icon-color': '#78350f' }
    },
    {
        id: 'writer-ink',
        label: 'Ink',
        category: 'dark',
        palette: { primary: '#e2e8f0', surface: '#1e293b', background: '#020617', text: '#f8fafc', border: '#334155' },
        tokens: { 'page-background': '#020617', 'widget-background': '#1e293b', 'widget-border-color': '#334155', 'widget-heading-color': '#f8fafc', 'widget-body-color': '#94a3b8', 'page-title-color': '#f8fafc', 'page-bio-color': '#94a3b8', 'social-icon-color': '#e2e8f0' }
    },

    // --- DARK THEMES (12) ---
    {
        id: 'dark-classic',
        label: 'Classic',
        category: 'dark',
        palette: { primary: '#ffffff', surface: '#1e293b', background: '#0f172a', text: '#f8fafc', border: '#334155' },
        tokens: { 'page-background': '#0f172a', 'widget-background': '#1e293b', 'widget-border-color': '#334155', 'widget-heading-color': '#f8fafc', 'widget-body-color': '#cbd5e1', 'page-title-color': '#f8fafc', 'page-bio-color': '#cbd5e1', 'social-icon-color': '#f8fafc' }
    },
    {
        id: 'dark-midnight',
        label: 'Midnight',
        category: 'dark',
        palette: { primary: '#60a5fa', surface: '#0f172a', background: '#020617', text: '#e0f2fe', border: '#1e293b' },
        tokens: { 'page-background': '#020617', 'widget-background': 'rgba(30, 41, 59, 0.5)', 'widget-border-color': '#1e293b', 'widget-heading-color': '#e0f2fe', 'widget-body-color': '#94a3b8', 'page-title-color': '#e0f2fe', 'page-bio-color': '#94a3b8', 'social-icon-color': '#60a5fa' }
    },
    {
        id: 'dark-forest',
        label: 'Forest',
        category: 'dark',
        palette: { primary: '#4ade80', surface: '#14532d', background: '#052e16', text: '#dcfce7', border: '#166534' },
        tokens: { 'page-background': '#052e16', 'widget-background': '#14532d', 'widget-border-color': '#166534', 'widget-heading-color': '#dcfce7', 'widget-body-color': '#bbf7d0', 'page-title-color': '#dcfce7', 'page-bio-color': '#bbf7d0', 'social-icon-color': '#4ade80' }
    },
    {
        id: 'dark-crimson',
        label: 'Crimson',
        category: 'dark',
        palette: { primary: '#fb7185', surface: '#881337', background: '#4c0519', text: '#ffe4e6', border: '#9f1239' },
        tokens: { 'page-background': '#4c0519', 'widget-background': '#881337', 'widget-border-color': '#9f1239', 'widget-heading-color': '#ffe4e6', 'widget-body-color': '#fda4af', 'page-title-color': '#ffe4e6', 'page-bio-color': '#fda4af', 'social-icon-color': '#fb7185' }
    },
    {
        id: 'dark-sunset',
        label: 'Sunset',
        category: 'dark',
        palette: { primary: '#fb923c', surface: '#7c2d12', background: '#431407', text: '#ffedd5', border: '#9a3412' },
        tokens: { 'page-background': '#431407', 'widget-background': '#7c2d12', 'widget-border-color': '#9a3412', 'widget-heading-color': '#ffedd5', 'widget-body-color': '#fdba74', 'page-title-color': '#ffedd5', 'page-bio-color': '#fdba74', 'social-icon-color': '#fb923c' }
    },
    {
        id: 'dark-ocean',
        label: 'Deep Sea',
        category: 'dark',
        palette: { primary: '#38bdf8', surface: '#0c4a6e', background: '#082f49', text: '#e0f2fe', border: '#0369a1' },
        tokens: { 'page-background': '#082f49', 'widget-background': '#0c4a6e', 'widget-border-color': '#0369a1', 'widget-heading-color': '#e0f2fe', 'widget-body-color': '#7dd3fc', 'page-title-color': '#e0f2fe', 'page-bio-color': '#7dd3fc', 'social-icon-color': '#38bdf8' }
    },
    {
        id: 'dark-amethyst',
        label: 'Amethyst',
        category: 'dark',
        palette: { primary: '#a78bfa', surface: '#4c1d95', background: '#2e1065', text: '#ede9fe', border: '#5b21b6' },
        tokens: { 'page-background': '#2e1065', 'widget-background': '#4c1d95', 'widget-border-color': '#5b21b6', 'widget-heading-color': '#ede9fe', 'widget-body-color': '#c4b5fd', 'page-title-color': '#ede9fe', 'page-bio-color': '#c4b5fd', 'social-icon-color': '#a78bfa' }
    },
    {
        id: 'dark-ember',
        label: 'Ember',
        category: 'dark',
        palette: { primary: '#ef4444', surface: '#7f1d1d', background: '#450a0a', text: '#fee2e2', border: '#991b1b' },
        tokens: { 'page-background': '#450a0a', 'widget-background': '#7f1d1d', 'widget-border-color': '#991b1b', 'widget-heading-color': '#fee2e2', 'widget-body-color': '#fca5a5', 'page-title-color': '#fee2e2', 'page-bio-color': '#fca5a5', 'social-icon-color': '#ef4444' }
    },
    {
        id: 'dark-gold',
        label: 'Gold',
        category: 'dark',
        palette: { primary: '#facc15', surface: '#713f12', background: '#422006', text: '#fef9c3', border: '#a16207' },
        tokens: { 'page-background': '#422006', 'widget-background': '#713f12', 'widget-border-color': '#a16207', 'widget-heading-color': '#fef9c3', 'widget-body-color': '#fde047', 'page-title-color': '#fef9c3', 'page-bio-color': '#fde047', 'social-icon-color': '#facc15' }
    },
    {
        id: 'dark-charcoal',
        label: 'Charcoal',
        category: 'dark',
        palette: { primary: '#d6d3d1', surface: '#292524', background: '#1c1917', text: '#fafaf9', border: '#44403c' },
        tokens: { 'page-background': '#1c1917', 'widget-background': '#292524', 'widget-border-color': '#44403c', 'widget-heading-color': '#fafaf9', 'widget-body-color': '#a8a29e', 'page-title-color': '#fafaf9', 'page-bio-color': '#a8a29e', 'social-icon-color': '#d6d3d1' }
    },
    {
        id: 'dark-neon',
        label: 'Cyber',
        category: 'dark',
        palette: { primary: '#d946ef', surface: '#2e1065', background: '#0f172a', text: '#f0abfc', border: '#d946ef' },
        tokens: { 'page-background': '#0f172a', 'widget-background': '#2e1065', 'widget-border-color': '#d946ef', 'widget-heading-color': '#f0abfc', 'widget-body-color': '#e879f9', 'page-title-color': '#f0abfc', 'page-bio-color': '#e879f9', 'social-icon-color': '#d946ef' }
    },
    {
        id: 'dark-terminal',
        label: 'Terminal',
        category: 'dark',
        palette: { primary: '#00ff00', surface: '#000000', background: '#000000', text: '#00ff00', border: '#333333' },
        tokens: { 'page-background': '#000000', 'widget-background': '#000000', 'widget-border-color': '#00ff00', 'widget-heading-color': '#00ff00', 'widget-body-color': '#00cc00', 'page-title-color': '#00ff00', 'page-bio-color': '#00cc00', 'social-icon-color': '#00ff00' }
    },

    // --- NEW ELEGANT EXPANSION (5) ---
    {
        id: 'light-ivory',
        label: 'Ivory',
        category: 'light',
        palette: { primary: '#d4af37', surface: '#fffff0', background: '#f8f5e6', text: '#4a4036', border: '#e6dabb' },
        tokens: { 'page-background': '#f8f5e6', 'widget-background': '#fffff0', 'widget-border-color': '#e6dabb', 'widget-heading-color': '#4a4036', 'widget-body-color': '#786c61', 'page-title-color': '#4a4036', 'page-bio-color': '#786c61', 'social-icon-color': '#d4af37' }
    },
    {
        id: 'light-sage',
        label: 'Sage',
        category: 'light',
        palette: { primary: '#577567', surface: '#f0f4f2', background: '#e1e8e5', text: '#2d3b36', border: '#b8c9c1' },
        tokens: { 'page-background': '#e1e8e5', 'widget-background': '#f0f4f2', 'widget-border-color': '#b8c9c1', 'widget-heading-color': '#2d3b36', 'widget-body-color': '#4d6159', 'page-title-color': '#2d3b36', 'page-bio-color': '#4d6159', 'social-icon-color': '#577567' }
    },
    {
        id: 'light-periwinkle',
        label: 'Periwinkle',
        category: 'light',
        palette: { primary: '#818cf8', surface: '#f5f7ff', background: '#ebedfa', text: '#312e81', border: '#c7cefa' },
        tokens: { 'page-background': '#ebedfa', 'widget-background': '#f5f7ff', 'widget-border-color': '#c7cefa', 'widget-heading-color': '#312e81', 'widget-body-color': '#4f46e5', 'page-title-color': '#312e81', 'page-bio-color': '#4f46e5', 'social-icon-color': '#818cf8' }
    },
    {
        id: 'dark-royal',
        label: 'Royal',
        category: 'dark',
        palette: { primary: '#fbbf24', surface: '#1e1b4b', background: '#0f0e2b', text: '#fffbeb', border: '#312e81' },
        tokens: { 'page-background': '#0f0e2b', 'widget-background': '#1e1b4b', 'widget-border-color': '#312e81', 'widget-heading-color': '#fffbeb', 'widget-body-color': '#ddd6fe', 'page-title-color': '#fffbeb', 'page-bio-color': '#ddd6fe', 'social-icon-color': '#fbbf24' }
    },
    {
        id: 'dark-velvet',
        label: 'Velvet',
        category: 'dark',
        palette: { primary: '#f472b6', surface: '#4a044e', background: '#2e0230', text: '#fce7f3', border: '#701a75' },
        tokens: { 'page-background': '#2e0230', 'widget-background': '#4a044e', 'widget-border-color': '#701a75', 'widget-heading-color': '#fce7f3', 'widget-body-color': '#fbcfe8', 'page-title-color': '#fce7f3', 'page-bio-color': '#fbcfe8', 'social-icon-color': '#f472b6' }
    },

    // --- NEW CREATIVE EXPANSION (3) ---
    {
        id: 'light-berry',
        label: 'Berry',
        category: 'light',
        palette: { primary: '#db2777', surface: '#fdf2f8', background: '#fce7f3', text: '#831843', border: '#fbcfe8' },
        tokens: { 'page-background': '#fce7f3', 'widget-background': '#fdf2f8', 'widget-border-color': '#fbcfe8', 'widget-heading-color': '#831843', 'widget-body-color': '#be185d', 'page-title-color': '#831843', 'page-bio-color': '#be185d', 'social-icon-color': '#db2777' }
    },
    {
        id: 'light-glacier',
        label: 'Glacier',
        category: 'light',
        palette: { primary: '#06b6d4', surface: '#ecfeff', background: '#cffafe', text: '#155e75', border: '#67e8f9' },
        tokens: { 'page-background': '#cffafe', 'widget-background': '#ecfeff', 'widget-border-color': '#67e8f9', 'widget-heading-color': '#155e75', 'widget-body-color': '#0891b2', 'page-title-color': '#155e75', 'page-bio-color': '#0891b2', 'social-icon-color': '#06b6d4' }
    },
    {
        id: 'dark-acid',
        label: 'Acid',
        category: 'dark',
        palette: { primary: '#ccff00', surface: '#1a1a1a', background: '#0a0a0a', text: '#ccff00', border: '#333333' },
        tokens: { 'page-background': '#0a0a0a', 'widget-background': '#1a1a1a', 'widget-border-color': '#333333', 'widget-heading-color': '#ccff00', 'widget-body-color': '#a3e635', 'page-title-color': '#ccff00', 'page-bio-color': '#a3e635', 'social-icon-color': '#ccff00' }
    }
];

export const TYPOGRAPHY_PRESETS: TypographyPreset[] = [
    {
        id: 'modern',
        label: 'Modern',
        fonts: {
            heading: 'Inter',
            body: 'Inter'
        },
        tokens: {
            'widget-heading-font': 'Inter',
            'widget-body-font': 'Inter',
            'page-title-font': 'Inter',
            'page-bio-font': 'Inter',
        }
    },
    {
        id: 'elegant',
        label: 'Elegant',
        fonts: {
            heading: 'Playfair Display',
            body: 'Lato'
        },
        tokens: {
            'widget-heading-font': 'Playfair Display',
            'widget-body-font': 'Lato',
            'page-title-font': 'Playfair Display',
            'page-bio-font': 'Lato',
        }
    },
    {
        id: 'creative',
        label: 'Creative',
        fonts: {
            heading: 'Pacifico',
            body: 'Quicksand'
        },
        tokens: {
            'widget-heading-font': 'Pacifico',
            'widget-body-font': 'Quicksand',
            'page-title-font': 'Pacifico',
            'page-bio-font': 'Quicksand',
        }
    },
    {
        id: 'geometric',
        label: 'Geometric',
        fonts: {
            heading: 'Poppins',
            body: 'Poppins'
        },
        tokens: {
            'widget-heading-font': 'Poppins',
            'widget-body-font': 'Poppins',
            'page-title-font': 'Poppins',
            'page-bio-font': 'Poppins',
        }
    },
    {
        id: 'editorial',
        label: 'Editorial',
        fonts: {
            heading: 'Merriweather',
            body: 'Open Sans'
        },
        tokens: {
            'widget-heading-font': 'Merriweather',
            'widget-body-font': 'Open Sans',
            'page-title-font': 'Merriweather',
            'page-bio-font': 'Open Sans',
        }
    },
    {
        id: 'handwritten',
        label: 'Handwritten',
        fonts: {
            heading: 'Caveat',
            body: 'Open Sans'
        },
        tokens: {
            'widget-heading-font': 'Caveat',
            'widget-body-font': 'Open Sans',
            'page-title-font': 'Caveat',
            'page-bio-font': 'Open Sans',
        }
    },
    {
        id: 'brush',
        label: 'Brush',
        fonts: {
            heading: 'Permanent Marker',
            body: 'Roboto'
        },
        tokens: {
            'widget-heading-font': 'Permanent Marker',
            'widget-body-font': 'Roboto',
            'page-title-font': 'Permanent Marker',
            'page-bio-font': 'Roboto',
        }
    },
    {
        id: 'script',
        label: 'Script',
        fonts: {
            heading: 'Dancing Script',
            body: 'Lato'
        },
        tokens: {
            'widget-heading-font': 'Dancing Script',
            'widget-body-font': 'Lato',
            'page-title-font': 'Dancing Script',
            'page-bio-font': 'Lato',
        }
    },
    {
        id: 'minimal-mono',
        label: 'Minimal Mono',
        fonts: {
            heading: 'Space Mono',
            body: 'Space Mono'
        },
        tokens: {
            'widget-heading-font': 'Space Mono',
            'widget-body-font': 'Space Mono',
            'page-title-font': 'Space Mono',
            'page-bio-font': 'Space Mono',
        }
    },
    {
        id: 'stark',
        label: 'Stark',
        fonts: {
            heading: 'DM Serif Display',
            body: 'Outfit'
        },
        tokens: {
            'widget-heading-font': 'DM Serif Display',
            'widget-body-font': 'Outfit',
            'page-title-font': 'DM Serif Display',
            'page-bio-font': 'Outfit',
        }
    },
    {
        id: 'rounded',
        label: 'Rounded',
        fonts: {
            heading: 'Varela Round',
            body: 'Varela Round'
        },
        tokens: {
            'widget-heading-font': 'Varela Round',
            'widget-body-font': 'Varela Round',
            'page-title-font': 'Varela Round',
            'page-bio-font': 'Varela Round',
        }
    },
    {
        id: 'display',
        label: 'Display',
        fonts: {
            heading: 'Abril Fatface',
            body: 'Raleway'
        },
        tokens: {
            'widget-heading-font': 'Abril Fatface',
            'widget-body-font': 'Raleway',
            'page-title-font': 'Abril Fatface',
            'page-bio-font': 'Raleway',
        }
    },
    {
        id: 'vintage',
        label: 'Vintage',
        fonts: {
            heading: 'Lobster',
            body: 'Roboto Condensed'
        },
        tokens: {
            'widget-heading-font': 'Lobster',
            'widget-body-font': 'Roboto Condensed',
            'page-title-font': 'Lobster',
            'page-bio-font': 'Roboto Condensed',
        }
    },
    {
        id: 'journal',
        label: 'Journal',
        fonts: {
            heading: 'Indie Flower',
            body: 'Comfortaa'
        },
        tokens: {
            'widget-heading-font': 'Indie Flower',
            'widget-body-font': 'Comfortaa',
            'page-title-font': 'Indie Flower',
            'page-bio-font': 'Comfortaa',
        }
    },
    {
        id: 'bold',
        label: 'Bold',
        fonts: {
            heading: 'Oswald',
            body: 'Source Sans Pro'
        },
        tokens: {
            'widget-heading-font': 'Oswald',
            'widget-body-font': 'Source Sans Pro',
            'page-title-font': 'Oswald',
            'page-bio-font': 'Source Sans Pro',
        }
    },
    {
        id: 'corporate',
        label: 'Corporate',
        fonts: {
            heading: 'Work Sans',
            body: 'Roboto'
        },
        tokens: {
            'widget-heading-font': 'Work Sans',
            'widget-body-font': 'Roboto',
            'page-title-font': 'Work Sans',
            'page-bio-font': 'Roboto',
        }
    },
    {
        id: 'tech',
        label: 'Tech',
        fonts: {
            heading: 'Space Grotesk',
            body: 'Inter'
        },
        tokens: {
            'widget-heading-font': 'Space Grotesk',
            'widget-body-font': 'Inter',
            'page-title-font': 'Space Grotesk',
            'page-bio-font': 'Inter',
        }
    },
    {
        id: 'luxe',
        label: 'Luxe',
        fonts: {
            heading: 'Bodoni Moda',
            body: 'Lato'
        },
        tokens: {
            'widget-heading-font': 'Bodoni Moda',
            'widget-body-font': 'Lato',
            'page-title-font': 'Bodoni Moda',
            'page-bio-font': 'Lato',
        }
    },
    {
        id: 'classic',
        label: 'Classic',
        fonts: {
            heading: 'Libre Baskerville',
            body: 'Work Sans'
        },
        tokens: {
            'widget-heading-font': 'Libre Baskerville',
            'widget-body-font': 'Work Sans',
            'page-title-font': 'Libre Baskerville',
            'page-bio-font': 'Work Sans',
        }
    },
    {
        id: 'retro',
        label: 'Retro',
        fonts: {
            heading: 'Righteous',
            body: 'Roboto'
        },
        tokens: {
            'widget-heading-font': 'Righteous',
            'widget-body-font': 'Roboto',
            'page-title-font': 'Righteous',
            'page-bio-font': 'Roboto',
        }
    },
    {
        id: 'quirky',
        label: 'Quirky',
        fonts: {
            heading: 'Dosis',
            body: 'Nunito'
        },
        tokens: {
            'widget-heading-font': 'Dosis',
            'widget-body-font': 'Nunito',
            'page-title-font': 'Dosis',
            'page-bio-font': 'Nunito',
        }
    }
];

export const SPACING_PRESETS: SpacingPreset[] = [
    {
        id: 'tight',
        label: 'Tight',
        tokens: {
            'page-spacing': 16,
            'widget-spacing': 12, // kept for potential future use
            'widget-padding': 12
        }
    },
    {
        id: 'cozy',
        label: 'Cozy',
        tokens: {
            'page-spacing': 24,
            'widget-spacing': 16,
            'widget-padding': 16
        }
    },
    {
        id: 'comfortable',
        label: 'Comfortable',
        tokens: {
            'page-spacing': 32,
            'widget-spacing': 24,
            'widget-padding': 24
        }
    }
];
