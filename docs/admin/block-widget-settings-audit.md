# Block Widget Settings Audit

## Overview
This document audits how block widget settings flow from Edit Theme Panel → Database → Theme Cards → User Page → Individual Widgets.

## Settings to Audit

1. **Background** - widget_background column
2. **Border** - widget_border_color, widget_styles.border_width
3. **Shadow** - widget_styles.border_shadow_intensity
4. **Typography** - widget typography tokens (heading/body colors, fonts)
5. **Thumbnails** - thumbnail styling
6. **Spacing** - spacing_tokens.density (affects widget gap)
7. **Shape & Effects** - shape_tokens (button_radius, widget_border_width, shadow_level)

## Current Flow Analysis

### 1. Background Flow

#### Edit Theme Panel Saves:
- `widget_background` column: `finalWidgetBackground` (from `blockBackgroundImage || backgroundSurface`)
- `color_tokens.background.surface`: `backgroundSurface` (from `semantic.surface.base`)

#### Theme Card Reads:
- ✅ Reads `theme.widget_background` (Priority 2 in extractThemeColors)

#### User Page Reads:
- ✅ Reads `theme.widget_background` via `getWidgetBackground()` 
- ✅ Falls back to `color_tokens.background.surface` if `widget_background` is empty

#### Widgets Apply:
- ✅ Uses `var(--widget-background)` in `.widget-item` CSS

**Status**: ✅ Working, but may have fallback issues

---

### 2. Border Flow

#### Edit Theme Panel Saves:
- `widget_border_color` column: `borderDefault` (from `semantic.border.default`)
- `widget_styles.border_width`: `widgetStylesBorderWidth` (from `widgetBorderWidth2` preset)

#### Theme Card Reads:
- ❌ **ISSUE**: Doesn't read border settings

#### User Page Reads:
- ✅ Reads `theme.widget_border_color` via `getWidgetBorderColor()`
- ✅ Reads `theme.widget_styles` via `getWidgetStyles()`
- ✅ Falls back to defaults if not set

#### Widgets Apply:
- ✅ Uses `var(--widget-border-color)` and `var(--widget-border-width)` in `.widget-item` CSS

**Status**: ⚠️ Working but theme cards don't show it

---

### 3. Shadow Flow

#### Edit Theme Panel Saves:
- `widget_styles.border_shadow_intensity`: `shadowLevel` (from `shadowLevel2` preset)

#### Theme Card Reads:
- ❌ **ISSUE**: Doesn't read shadow settings

#### User Page Reads:
- ✅ Reads `theme.widget_styles.border_shadow_intensity` via `getWidgetStyles()`
- ✅ Falls back to 'subtle' if not set

#### Widgets Apply:
- ✅ Uses `var(--widget-box-shadow)` in `.widget-item` CSS
- ✅ Shadow level mapped to CSS variables

**Status**: ⚠️ Working but theme cards don't show it

---

### 4. Typography Flow (Block Widget)

#### Edit Theme Panel Saves:
- `typography_tokens.color.heading`: for block heading color (via `semantic.text.primary`)
- `typography_tokens.color.body`: for block body color (via `semantic.text.secondary`)
- `typography_tokens.font.heading`: `headingFont`
- `typography_tokens.font.body`: `bodyFont`

#### Theme Card Reads:
- ✅ Reads fonts from `typography_tokens.font.heading/body`
- ❌ **ISSUE**: Doesn't read typography colors

#### User Page Reads:
- ✅ Reads typography colors from `typography_tokens.color.heading/body`
- ✅ Reads fonts from `typography_tokens.font.heading/body`

#### Widgets Apply:
- ✅ Uses `var(--heading-font-color)` and `var(--body-font-color)` for text
- ✅ Uses `var(--font-family-heading)` and `var(--font-family-body)` for fonts

**Status**: ⚠️ Mostly working, but theme cards don't show colors

---

### 5. Thumbnails Flow

#### Edit Theme Panel Saves:
- ❌ **ISSUE**: No explicit thumbnail settings in Edit Theme Panel
- Thumbnails may use widget background or other theme settings

#### Theme Card Reads:
- ❌ **ISSUE**: Doesn't show thumbnail styling

#### User Page Reads:
- Uses widget background and border settings

#### Widgets Apply:
- Uses `.widget-thumbnail-wrapper` with theme styles

**Status**: ⚠️ Needs investigation

---

### 6. Spacing Flow (Block Widget)

#### Edit Theme Panel Saves:
- `spacing_tokens.density`: `spacingDensity` ('compact' | 'cozy' | 'comfortable')
- `widget_styles.spacing`: mapped from density ('tight' | 'comfortable' | 'spacious')

#### Theme Card Reads:
- ❌ **ISSUE**: Doesn't read spacing density

#### User Page Reads:
- ✅ Reads `spacing_tokens.density` via `getSpacingTokens()`
- ✅ Uses density multipliers to calculate spacing values
- ✅ Applies to `--widget-gap` CSS variable

#### Widgets Apply:
- ✅ Uses `var(--widget-gap)` in `.widgets-container` gap

**Status**: ✅ Working, but theme cards don't show it

---

### 7. Shape & Effects Flow (Block Widget)

#### Edit Theme Panel Saves:
- `shape_tokens.button_radius`: from `buttonRadius2` preset
- `shape_tokens.widget_border_width`: from `widgetBorderWidth2` preset  
- `shape_tokens.shadow_level`: from `shadowLevel2` preset
- `widget_styles.shape`: from `widgetStylesShape` preset
- `widget_styles.border_width`: from `widgetStylesBorderWidth` preset

#### Theme Card Reads:
- ❌ **ISSUE**: Doesn't read shape tokens

#### User Page Reads:
- ✅ Reads `shape_tokens` via `getShapeTokens()`
- ✅ Reads `widget_styles` via `getWidgetStyles()`
- ✅ Applies to CSS variables

#### Widgets Apply:
- ✅ Uses `var(--widget-border-radius)` for border radius
- ✅ Uses `var(--widget-border-width)` for border width
- ✅ Uses `var(--widget-box-shadow)` for shadow

**Status**: ⚠️ Working but theme cards don't show it

---

## Issues Identified

### Critical Issues:
1. ❌ **Theme cards don't show block widget settings** - No visual indication of border, shadow, spacing, shape
2. ❌ **Potential fallback conflicts** - May be using colorTokens instead of widget_background column
3. ❌ **Legacy widget_styles may override** - Old widget_styles JSON may conflict with new token system

### Medium Issues:
4. ⚠️ **Thumbnail settings unclear** - No explicit thumbnail styling in Edit Theme Panel
5. ⚠️ **Multiple sources for same setting** - Both widget_styles and shape_tokens for border_width
6. ⚠️ **Legacy color references** - May still use --primary-color, --secondary-color

### Minor Issues:
7. ℹ️ **Inconsistent naming** - widget_styles vs shape_tokens vs color_tokens

## Priority Order (What Should Be Primary)

1. **Edit Theme Panel** → Primary source of truth
2. **Database Columns** (widget_background, widget_border_color) → Secondary
3. **Token System** (color_tokens, shape_tokens, spacing_tokens) → Tertiary
4. **Legacy widget_styles JSON** → Should be removed/ignored

## Fixes Needed

1. ✅ Remove all legacy widget_styles fallbacks - DONE
2. ✅ Ensure widget_background column is primary (like page_background) - DONE
3. ✅ Remove legacy color variable references (--primary-color, --secondary-color) - DONE
4. ⚠️ Ensure theme cards show block widget settings - TODO
5. ⚠️ Audit each widget type to ensure settings are applied - TODO

## Fixes Applied

### 1. Widget Background & Border Color
- ✅ `getWidgetBackground()` now uses ONLY `theme.widget_background` (no fallbacks)
- ✅ `getWidgetBorderColor()` now uses ONLY `theme.widget_border_color` (no fallbacks)
- ✅ `ThemeCSSGenerator` uses direct values with `!important` (like page background)
- ✅ Removed all legacy fallbacks to `getThemeColors()`

### 2. Widget Styles
- ✅ `getWidgetStyles()` now uses ONLY `theme.widget_styles` (no page-level overrides)
- ✅ `ThemeCSSGenerator` reads from `shape_tokens` and `spacing_tokens` instead of legacy `widget_styles`
- ✅ Widget CSS uses direct values with `!important` for background, border, border-radius, shadow

### 3. Legacy Code Removal
- ✅ Removed legacy color variable references (`--primary-color`, `--secondary-color`, `--accent-color`)
- ✅ Removed legacy font variable references (`--heading-font`, `--body-font`)
- ✅ Removed legacy color fallbacks in `ThemeCSSGenerator`

## Remaining Issues

1. **Theme Cards** - Need to show block widget settings (border, shadow, spacing, shape) in theme preview
2. **Widget Rendering** - Need to verify each widget type applies theme settings correctly
3. **Thumbnail Styling** - Need to clarify how thumbnails use theme settings

