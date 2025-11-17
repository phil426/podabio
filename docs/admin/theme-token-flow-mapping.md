# Theme Token Flow Mapping

## Overview
This document maps how theme settings flow from the Edit Theme Panel → Database → Theme Cards → User Page.

## Current Flow Analysis

### 1. Page Background Flow

#### Edit Theme Panel Saves:
- `page_background` column: `finalPageBackground` (from `pageBackgroundImage || backgroundBase`)
- `color_tokens.background.base`: `backgroundBase` (from `semantic.surface.canvas`)
- `color_tokens.gradient.page`: gradient value if it's a gradient

#### Theme Card (ThemeSwatch) Reads:
- ❌ **ISSUE**: Reads `color_tokens.background.base` first (line 49)
- ❌ **ISSUE**: Reads `theme.page_background` but only adds to palette, doesn't prioritize
- Should prioritize `page_background` column like user page does

#### User Page Reads:
- ✅ Reads `theme.page_background` via `getPageBackground()` (prioritized)
- ✅ Falls back to `color_tokens.background.base` if `page_background` is empty

**Problem**: Theme cards don't prioritize `page_background` column, so they show wrong background.

---

### 2. Typography Colors Flow

#### Edit Theme Panel Saves:
- `typography_tokens.color.heading`: `headingColor` (if different from semantic.text.primary)
- `typography_tokens.color.body`: `bodyColor` (if different from semantic.text.primary)

#### Theme Card Reads:
- ✅ Reads from `typography_tokens.font.heading` and `typography_tokens.font.body` for fonts
- ❌ **ISSUE**: Doesn't read typography colors for display
- ThemeLibraryPanel reads from tokens: `core.typography.color.heading` and `core.typography.color.body`

#### User Page Reads:
- ✅ Reads `typography_tokens.color.heading` and `typography_tokens.color.body`
- ✅ Falls back to `core.typography.color.heading` and `core.typography.color.body`

**Status**: Mostly working, but theme cards don't show typography colors in preview.

---

### 3. Widget Background Flow

#### Edit Theme Panel Saves:
- `widget_background` column: `finalWidgetBackground` (from `blockBackgroundImage || backgroundSurface`)
- `color_tokens.background.surface`: `backgroundSurface` (from `semantic.surface.base`)
- `color_tokens.gradient.widget`: gradient value if it's a gradient

#### Theme Card Reads:
- ❌ **ISSUE**: Doesn't read widget background at all

#### User Page Reads:
- ✅ Reads `theme.widget_background` via `getWidgetBackground()` (prioritized)
- ✅ Falls back to `color_tokens.background.surface` if `widget_background` is empty

**Status**: User page works, but theme cards don't show widget background.

---

### 4. Typography Fonts Flow

#### Edit Theme Panel Saves:
- `typography_tokens.font.heading`: `headingFont`
- `typography_tokens.font.body`: `bodyFont`
- `page_primary_font`: `headingFont`
- `page_secondary_font`: `bodyFont`
- `widget_primary_font`: `headingFont`
- `widget_secondary_font`: `bodyFont`

#### Theme Card Reads:
- ✅ Reads `typography_tokens.font.heading` → `theme.page_primary_font` → 'Inter'
- ✅ Reads `typography_tokens.font.body` → `theme.widget_primary_font` → 'Inter'

#### User Page Reads:
- ✅ Reads via `getPageFonts()` and `getWidgetFonts()`
- ✅ Prioritizes `page_primary_font`/`page_secondary_font` columns
- ✅ Falls back to `typography_tokens.font.heading`/`typography_tokens.font.body`

**Status**: ✅ Working correctly

---

### 5. Spacing Density Flow

#### Edit Theme Panel Saves:
- `spacing_tokens.density`: `spacingDensity` ('compact' | 'cozy' | 'comfortable')

#### Theme Card Reads:
- ❌ **ISSUE**: Doesn't read spacing density

#### User Page Reads:
- ✅ Reads `spacing_tokens.density` via `getSpacingTokens()`
- ✅ Uses density multipliers to calculate spacing values
- ✅ Applies to `--page-padding` and `--widget-gap`

**Status**: User page works, theme cards don't show spacing.

---

### 6. Shape Tokens Flow

#### Edit Theme Panel Saves:
- `shape_tokens.button_radius`: from `buttonRadius` preset
- `shape_tokens.widget_border_width`: from `widgetBorderWidth` preset
- `shape_tokens.shadow_level`: from `shadowLevel` preset

#### Theme Card Reads:
- ❌ **ISSUE**: Doesn't read shape tokens

#### User Page Reads:
- ✅ Reads `shape_tokens` via `getShapeTokens()`
- ✅ Applies to CSS variables

**Status**: User page works, theme cards don't show shape.

---

## Priority Order (What Should Be Primary)

1. **Edit Theme Panel** → Primary source of truth
2. **Database Columns** (page_background, widget_background, page_primary_font, etc.) → Secondary (for backward compatibility)
3. **Token System** (color_tokens, typography_tokens, etc.) → Tertiary (for new token-based system)

## Issues Identified

### Critical Issues:
1. ❌ **ThemeSwatch doesn't prioritize `page_background` column** - reads `color_tokens.background.base` instead
2. ❌ **ThemeSwatch doesn't show widget background** - missing from color extraction
3. ❌ **Theme cards don't reflect gradient backgrounds** - `extractThemeColors` doesn't handle gradients properly

### Medium Issues:
4. ⚠️ **Theme cards don't show typography colors** - only show fonts
5. ⚠️ **Theme cards don't show spacing/shape settings** - no visual indication

### Minor Issues:
6. ℹ️ **Inconsistent fallback chains** - some use tokens first, some use columns first

## Fixes Applied ✅

1. ✅ **Updated `extractThemeColors()` in ThemeSwatch** to prioritize `page_background` column
   - Now reads `theme.page_background` FIRST (Priority 1)
   - Then reads `theme.widget_background` (Priority 2)
   - Then reads from `color_tokens` for accent/text colors (Priority 3)
   - Falls back to legacy colors only if nothing found (Priority 4)
   - Properly handles gradients (returns `{ gradient: string }` objects)

2. ✅ **Added widget background to theme card color extraction**
   - Widget background now included in theme card palette
   - Only added if different from page background

3. ✅ **Fixed gradient handling in theme card display**
   - `extractThemeColors()` now returns `Array<string | { gradient: string }>`
   - Theme cards properly display gradients using `background` CSS property

4. ✅ **Updated ThemeLibraryPanel `currentValues`** to prioritize theme data
   - For active theme, reads from theme database (what Edit Panel saved)
   - Falls back to snapshot tokens only if theme data unavailable
   - Ensures theme cards show what's saved, not what's currently on page

5. ✅ **Fixed typography color reading in CSS generator**
   - Now reads from `typography_tokens.color.heading/body` FIRST (what Edit Panel saves)
   - Falls back to token_overrides if not found
   - Ensures typography colors from Edit Panel are applied to user page

6. ✅ **Fixed gradient text CSS generation**
   - Detects gradients in typography colors
   - Uses `background-clip: text` technique for gradient text
   - Applies to both headings and body text

## Current Flow (After Fixes)

### Page Background:
1. **Edit Theme Panel** saves → `theme.page_background` column ✅
2. **Theme Card** reads → `theme.page_background` (Priority 1) ✅
3. **User Page** reads → `theme.page_background` via `getPageBackground()` ✅

### Typography Colors:
1. **Edit Theme Panel** saves → `typography_tokens.color.heading/body` ✅
2. **Theme Card** reads → Not displayed (fonts only) ⚠️
3. **User Page** reads → `typography_tokens.color.heading/body` ✅

### Widget Background:
1. **Edit Theme Panel** saves → `theme.widget_background` column ✅
2. **Theme Card** reads → `theme.widget_background` (Priority 2) ✅
3. **User Page** reads → `theme.widget_background` via `getWidgetBackground()` ✅

## Remaining Issues

1. ⚠️ **Theme cards don't show typography colors** - Only show fonts, not colors
   - This is a display limitation, not a data flow issue
   - Typography colors are saved and applied correctly to user page

2. ⚠️ **Theme cards don't show spacing/shape settings** - No visual indication
   - This is a display limitation, not a data flow issue
   - Settings are saved and applied correctly to user page

