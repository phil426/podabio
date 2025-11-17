# Theme Token Inventory

This document provides a comprehensive inventory of all theme tokens, their UI controls, and how they map between the frontend and backend systems.

**Last Updated:** 2025-01-14  
**Version:** 1.0.0

## Overview

The theme system uses a token-based architecture where:
- **Frontend semantic tokens** (e.g., `semantic.surface.canvas`) are used in the React UI
- **Backend token structure** (e.g., `color_tokens.background.base`) is stored in the database
- **Database columns** (e.g., `page_background`, `widget_background`) provide backward compatibility

## Color Tokens

### Background Tokens

| Token Path | Frontend Semantic | UI Control | Editable | Default Value | Database Column |
|------------|------------------|------------|----------|--------------|----------------|
| `color_tokens.background.base` | `semantic.surface.canvas` | Page Background Type > Solid tab > ColorTokenPicker | Yes | `#ffffff` | `page_background` |
| `color_tokens.background.surface` | `semantic.surface.base` | Block Widget Background Type > Solid tab > ColorTokenPicker | Yes | `#ffffff` | `widget_background` |
| `color_tokens.background.surface_raised` | N/A | Derived from `surface` (lightened 22%) | Auto | `#f9fafb` | N/A |
| `color_tokens.background.overlay` | N/A | Default only | No | `rgba(15, 23, 42, 0.6)` | N/A |

### Text Tokens

| Token Path | Frontend Semantic | UI Control | Editable | Default Value | Database Column |
|------------|-------------------|------------|----------|--------------|----------------|
| `color_tokens.text.primary` | `semantic.text.primary` | Not currently in UI | No | `#111827` | N/A |
| `color_tokens.text.secondary` | `semantic.text.secondary` | Not currently in UI | No | `#4b5563` | N/A |
| `color_tokens.text.inverse` | N/A | Default only | No | `#ffffff` | N/A |

### Accent Tokens

| Token Path | Frontend Semantic | UI Control | Editable | Default Value | Database Column |
|------------|-------------------|------------|----------|--------------|----------------|
| `color_tokens.accent.primary` | `semantic.accent.primary` | Not currently in UI | No | `#0066ff` | N/A |
| `color_tokens.accent.secondary` | `semantic.accent.secondary` | Not currently in UI | No | `#3b82f6` | N/A |
| `color_tokens.accent.muted` | N/A | Derived from `primary` (lightened 75%) | Auto | `#e0edff` | N/A |

### Border Tokens

| Token Path | Frontend Semantic | UI Control | Editable | Default Value | Database Column |
|------------|-------------------|------------|----------|--------------|----------------|
| `color_tokens.border.default` | N/A | Derived from `text.primary` (darkened 20%) | Auto | `#d1d5db` | `widget_border_color` |
| `color_tokens.border.focus` | N/A | Derived from `accent.primary` (lightened 25%) | Auto | `#2563eb` | N/A |

### State Tokens

| Token Path | Frontend Semantic | UI Control | Editable | Default Value | Database Column |
|------------|-------------------|------------|----------|--------------|----------------|
| `color_tokens.state.success` | N/A | Default only | No | `#12b76a` | N/A |
| `color_tokens.state.warning` | N/A | Default only | No | `#f59e0b` | N/A |
| `color_tokens.state.danger` | N/A | Default only | No | `#ef4444` | N/A |
| `color_tokens.text_state.success` | N/A | Default only | No | `#0f5132` | N/A |
| `color_tokens.text_state.warning` | N/A | Default only | No | `#7c2d12` | N/A |
| `color_tokens.text_state.danger` | N/A | Default only | No | `#7f1d1d` | N/A |

### Shadow Tokens

| Token Path | Frontend Semantic | UI Control | Editable | Default Value | Database Column |
|------------|-------------------|------------|----------|--------------|----------------|
| `color_tokens.shadow.ambient` | N/A | Default only | No | `rgba(15, 23, 42, 0.12)` | N/A |
| `color_tokens.shadow.focus` | N/A | Default only | No | `rgba(37, 99, 235, 0.35)` | N/A |

### Gradient Tokens

| Token Path | Frontend Semantic | UI Control | Editable | Default Value | Database Column |
|------------|-------------------|------------|----------|--------------|----------------|
| `color_tokens.gradient.page` | `semantic.surface.canvas` | Page Background Type > Gradient tab > ColorTokenPicker | Yes | `null` | `page_background` (when gradient) |
| `color_tokens.gradient.widget` | `semantic.surface.base` | Block Widget Background Type > Gradient tab > ColorTokenPicker | Yes | `null` | `widget_background` (when gradient) |
| `color_tokens.gradient.accent` | N/A | Default only | No | `null` | N/A |
| `color_tokens.gradient.podcast` | N/A | Default only | No | `null` | N/A |

### Glow Tokens

| Token Path | Frontend Semantic | UI Control | Editable | Default Value | Database Column |
|------------|-------------------|------------|----------|--------------|----------------|
| `color_tokens.glow.primary` | N/A | Default only | No | `null` | N/A |

## Typography Tokens

### Font Tokens

| Token Path | Frontend Semantic | UI Control | Editable | Default Value | Database Column |
|------------|-------------------|------------|----------|--------------|----------------|
| `typography_tokens.font.heading` | `core.typography.font.heading` | Page Style > Typography > Heading Font dropdown | Yes | `Inter` | `page_primary_font` |
| `typography_tokens.font.body` | `core.typography.font.body` | Page Style > Typography > Body Font dropdown | Yes | `Inter` | `page_secondary_font` |
| `typography_tokens.font.metatext` | N/A | Defaults to `body` font | Auto | `Inter` | N/A |

### Scale Tokens

| Token Path | Frontend Semantic | UI Control | Editable | Default Value | Database Column |
|------------|-------------------|------------|----------|--------------|----------------|
| `typography_tokens.scale.xl` | N/A | Default only | No | `2.488` | N/A |
| `typography_tokens.scale.lg` | N/A | Default only | No | `1.777` | N/A |
| `typography_tokens.scale.md` | N/A | Page Style > Typography > Font Size Preset buttons | Yes (via preset) | `1.333` (medium) | N/A |
| `typography_tokens.scale.sm` | N/A | Page Style > Typography > Font Size Preset buttons | Yes (via preset) | `1.111` (medium) | N/A |
| `typography_tokens.scale.xs` | N/A | Default only | No | `0.889` | N/A |

**Font Size Preset Mapping:**
- `small`: `md: 1.1`, `sm: 0.9`
- `medium`: `md: 1.333`, `sm: 1.111`
- `large`: `md: 1.5`, `sm: 1.25`
- `xlarge`: `md: 1.777`, `sm: 1.5`

### Line Height Tokens

| Token Path | Frontend Semantic | UI Control | Editable | Default Value | Database Column |
|------------|-------------------|------------|----------|--------------|----------------|
| `typography_tokens.line_height.tight` | N/A | Default only | No | `1.2` | N/A |
| `typography_tokens.line_height.normal` | N/A | Default only | No | `1.5` | N/A |
| `typography_tokens.line_height.relaxed` | N/A | Default only | No | `1.7` | N/A |

### Weight Tokens

| Token Path | Frontend Semantic | UI Control | Editable | Default Value | Database Column |
|------------|-------------------|------------|----------|--------------|----------------|
| `typography_tokens.weight.normal` | N/A | Default only | No | `400` | N/A |
| `typography_tokens.weight.medium` | N/A | Default only | No | `500` | N/A |
| `typography_tokens.weight.bold` | N/A | Default only | No | `600` | N/A |

## Spacing Tokens

### Density Token

| Token Path | Frontend Semantic | UI Control | Editable | Default Value | Database Column |
|------------|-------------------|------------|----------|--------------|----------------|
| `spacing_tokens.density` | N/A | Page Style > Spacing > Density chips (Compact/Cozy/Comfortable) | Yes | `cozy` | N/A |

### Base Scale Tokens

| Token Path | Frontend Semantic | UI Control | Editable | Default Value | Database Column |
|------------|-------------------|------------|----------|--------------|----------------|
| `spacing_tokens.base_scale.2xs` | N/A | Default only | No | `0.25` | N/A |
| `spacing_tokens.base_scale.xs` | N/A | Default only | No | `0.5` | N/A |
| `spacing_tokens.base_scale.sm` | N/A | Default only | No | `0.75` | N/A |
| `spacing_tokens.base_scale.md` | N/A | Default only | No | `1.0` | N/A |
| `spacing_tokens.base_scale.lg` | N/A | Default only | No | `1.5` | N/A |
| `spacing_tokens.base_scale.xl` | N/A | Default only | No | `2.0` | N/A |
| `spacing_tokens.base_scale.2xl` | N/A | Default only | No | `3.0` | N/A |

### Density Multipliers

| Token Path | Frontend Semantic | UI Control | Editable | Default Value | Database Column |
|------------|-------------------|------------|----------|--------------|----------------|
| `spacing_tokens.density_multipliers.compact.*` | N/A | Default only | No | See defaults below | N/A |
| `spacing_tokens.density_multipliers.comfortable.*` | N/A | Default only | No | See defaults below | N/A |

**Default Density Multipliers:**
- `compact`: `2xs: 0.75`, `xs: 0.85`, `sm: 0.9`, `md: 1.0`, `lg: 1.0`, `xl: 1.0`, `2xl: 1.0`
- `comfortable`: `2xs: 1.0`, `xs: 1.0`, `sm: 1.1`, `md: 1.25`, `lg: 1.3`, `xl: 1.35`, `2xl: 1.4`

## Shape Tokens

### Corner Tokens

| Token Path | Frontend Semantic | UI Control | Editable | Default Value | Database Column |
|------------|-------------------|------------|----------|--------------|----------------|
| `shape_tokens.corner.none` | N/A | Page Style > Shape & Effects > Button Radius buttons | Yes (via preset) | `0px` | `widget_styles.shape` |
| `shape_tokens.corner.sm` | N/A | Page Style > Shape & Effects > Button Radius buttons | Yes (via preset) | `0.375rem` | `widget_styles.shape` |
| `shape_tokens.corner.md` | N/A | Page Style > Shape & Effects > Button Radius buttons | Yes (via preset) | `0.75rem` | `widget_styles.shape` |
| `shape_tokens.corner.lg` | N/A | Page Style > Shape & Effects > Button Radius buttons | Yes (via preset) | `1.5rem` | `widget_styles.shape` |
| `shape_tokens.corner.pill` | N/A | Page Style > Shape & Effects > Button Radius buttons | Yes (via preset) | `9999px` | `widget_styles.shape` |

**Button Radius Mapping:**
- `none` → `corner.none: 0px`
- `small` → `corner.sm: 0.375rem`
- `medium` → `corner.md: 0.75rem`
- `large` → `corner.lg: 1.5rem`
- `pill` → `corner.pill: 9999px`

### Border Width Tokens

| Token Path | Frontend Semantic | UI Control | Editable | Default Value | Database Column |
|------------|-------------------|------------|----------|--------------|----------------|
| `shape_tokens.border_width.hairline` | N/A | Page Style > Shape & Effects > Widget Border Width buttons | Yes (via preset) | `1px` | `widget_styles.border_width` |
| `shape_tokens.border_width.regular` | N/A | Page Style > Shape & Effects > Widget Border Width buttons | Yes (via preset) | `2px` | `widget_styles.border_width` |
| `shape_tokens.border_width.bold` | N/A | Default only | No | `4px` | N/A |

**Widget Border Width Mapping:**
- `none` → Omit from tokens
- `thin` → `border_width.hairline: 1px`
- `thick` → `border_width.regular: 2px`

### Shadow Tokens

| Token Path | Frontend Semantic | UI Control | Editable | Default Value | Database Column |
|------------|-------------------|------------|----------|--------------|----------------|
| `shape_tokens.shadow.level_1` | N/A | Page Style > Shape & Effects > Shadow Level buttons | Yes (via preset) | `0 2px 6px rgba(15, 23, 42, 0.12)` | `widget_styles.border_shadow_intensity` |
| `shape_tokens.shadow.level_2` | N/A | Page Style > Shape & Effects > Shadow Level buttons | Yes (via preset) | `0 6px 16px rgba(15, 23, 42, 0.16)` | `widget_styles.border_shadow_intensity` |
| `shape_tokens.shadow.focus` | N/A | Default only | No | `0 0 0 4px rgba(37, 99, 235, 0.35)` | N/A |

**Shadow Level Mapping:**
- `none` → Omit from tokens
- `subtle` → `shadow.level_1`
- `pronounced` → `shadow.level_2`

## Motion Tokens

### Duration Tokens

| Token Path | Frontend Semantic | UI Control | Editable | Default Value | Database Column |
|------------|-------------------|------------|----------|--------------|----------------|
| `motion_tokens.duration.fast` | N/A | Default only | No | `150ms` | N/A |
| `motion_tokens.duration.standard` | N/A | Default only | No | `250ms` | N/A |

### Easing Tokens

| Token Path | Frontend Semantic | UI Control | Editable | Default Value | Database Column |
|------------|-------------------|------------|----------|--------------|----------------|
| `motion_tokens.easing.standard` | N/A | Default only | No | `cubic-bezier(0.4, 0, 0.2, 1)` | N/A |
| `motion_tokens.easing.decelerate` | N/A | Default only | No | `cubic-bezier(0.0, 0, 0.2, 1)` | N/A |

### Focus Tokens

| Token Path | Frontend Semantic | UI Control | Editable | Default Value | Database Column |
|------------|-------------------|------------|----------|--------------|----------------|
| `motion_tokens.focus.ring_width` | N/A | Default only | No | `3px` | N/A |
| `motion_tokens.focus.ring_offset` | N/A | Default only | No | `2px` | N/A |

## Background Images

### Page Background Image

| Source | UI Control | Editable | Database Column |
|--------|------------|----------|----------------|
| Image URL or Upload | Page Background Type > Image tab > URL input or file upload | Yes | `page_background` (when image) |
| Gradient | Page Background Type > Gradient tab > ColorTokenPicker | Yes | `page_background` (when gradient) or `color_tokens.gradient.page` |
| Solid Color | Page Background Type > Solid tab > ColorTokenPicker | Yes | `page_background` (when color) or `color_tokens.background.base` |

### Block Widget Background Image

| Source | UI Control | Editable | Database Column |
|--------|------------|----------|----------------|
| Image URL or Upload | Block Widget Background Type > Image tab > URL input or file upload | Yes | `widget_background` (when image) |
| Gradient | Block Widget Background Type > Gradient tab > ColorTokenPicker | Yes | `widget_background` (when gradient) or `color_tokens.gradient.widget` |
| Solid Color | Block Widget Background Type > Solid tab > ColorTokenPicker | Yes | `widget_background` (when color) or `color_tokens.background.surface` |

## Legacy Fields

The following fields are maintained for backward compatibility:

| Field | Purpose | Mapped From |
|-------|---------|-------------|
| `page_primary_font` | Page heading font | `typography_tokens.font.heading` |
| `page_secondary_font` | Page body font | `typography_tokens.font.body` |
| `widget_primary_font` | Widget heading font | Defaults to `page_primary_font` |
| `widget_secondary_font` | Widget body font | Defaults to `page_secondary_font` |
| `widget_background` | Widget background color/image/gradient | `color_tokens.background.surface` or `color_tokens.gradient.widget` |
| `widget_border_color` | Widget border color | `color_tokens.border.default` |
| `widget_styles` | Legacy widget styling JSON | Derived from shape tokens |
| `colors` | Legacy colors JSON | Derived from color tokens |
| `fonts` | Legacy fonts JSON | Derived from typography tokens |
| `spatial_effect` | Spatial effect class | Default: `none` |

## Token Resolution Priority

When loading tokens, the system checks in this order:

1. **Database columns** (for backward compatibility)
2. **Token JSON fields** (new structure)
3. **Default values** (from `Theme::getDefault*Tokens()`)

When saving tokens, the system:

1. **Saves to token JSON fields** (new structure)
2. **Also saves to database columns** (for backward compatibility)
3. **Preserves existing token values** not edited in the UI

## Notes

- All tokens that are "Default only" can be made editable in the future by adding UI controls
- Derived tokens (e.g., `accent.muted`, `border.default`) are automatically calculated but can be overridden if present in existing theme data
- The `widget_styles` JSON field is maintained for backward compatibility but is derived from the new token structure
- Background images and gradients are stored in both the database column (`page_background`, `widget_background`) and the token structure (`color_tokens.gradient.page`, `color_tokens.gradient.widget`)
- The system preserves all existing token values when saving, only updating tokens that are explicitly edited in the UI


