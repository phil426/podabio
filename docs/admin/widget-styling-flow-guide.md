# Widget Styling Flow Guide

## Overview

This document defines how widget styling **SHOULD** work in PodaBio. All widget styling must come from a single source: `ThemeCSSGenerator.php`. No styling should exist in `Page.php` or individual widget renderers.

---

## Styling Flow Architecture

### 1. Source of Truth: Theme Settings

**Location**: Edit Theme Panel → Database

**Settings Stored**:
- `theme.widget_background` (column): Widget background color/gradient/image
- `theme.widget_border_color` (column): Widget border color
- `theme.shape_tokens` (JSON): Border radius, border width, shadow
- `theme.typography_tokens` (JSON): Widget typography colors and fonts
- `theme.spacing_tokens` (JSON): Widget gap spacing

### 2. CSS Generation: ThemeCSSGenerator

**Location**: `classes/ThemeCSSGenerator.php`

**Process**:
1. Reads theme settings from database
2. Resolves values (prioritizes theme columns over tokens)
3. Generates CSS with `!important` flags to ensure precedence
4. Outputs to `<style>` tag in page `<head>`

**Generated CSS Structure**:
```css
/* Widget container spacing */
.widgets-container {
    gap: var(--widget-gap);
}

/* Base widget styling - ALL styling comes from here */
.widget-item {
    background: {resolvedWidgetBackground} !important;
    border: {resolvedBorderWidth} solid {resolvedWidgetBorderColor} !important;
    border-radius: {resolvedBorderRadius} !important;
    box-shadow: {resolvedShadow} !important;
    position: relative;
}

/* Widget typography */
.widget-item h1, .widget-item h2, .widget-item h3, .widget-title {
    font-family: {widgetPrimaryFont};
    color: {headingColor};
}

.widget-item p, .widget-item span, .widget-content {
    font-family: {widgetSecondaryFont};
    color: {bodyColor};
}

/* Widget hover states */
.widget-item:hover {
    /* Hover effects based on border_effect */
}
```

### 3. HTML Structure: WidgetRenderer

**Location**: `classes/WidgetRenderer.php`

**Responsibility**: Generate HTML structure ONLY

**Rules**:
- ✅ Output semantic HTML with class names
- ✅ Use consistent class naming (`widget-item`, `widget-content`, `widget-title`, etc.)
- ❌ NO inline styles
- ❌ NO style attributes
- ❌ NO widget-specific CSS classes for styling (only for structure/functionality)

**Allowed Class Names**:
- `widget-item`: Base widget container (required)
- `widget-content`: Content wrapper
- `widget-title`: Title element
- `widget-description`: Description element
- `widget-thumbnail-wrapper`: Thumbnail container
- `widget-thumbnail`: Thumbnail image
- `widget-icon-wrapper`: Icon container
- `widget-icon`: Icon element
- `widget-{type}`: Widget type identifier (e.g., `widget-video`, `widget-text`) - for structure only, not styling

**Example**:
```php
// ✅ CORRECT: HTML structure only
$html = '<a href="..." class="widget-item">';
$html .= '<div class="widget-thumbnail-wrapper">';
$html .= '<img src="..." class="widget-thumbnail" alt="...">';
$html .= '</div>';
$html .= '<div class="widget-content">';
$html .= '<div class="widget-title">Title</div>';
$html .= '</div>';
$html .= '</a>';

// ❌ WRONG: Inline styles
$html = '<a href="..." class="widget-item" style="background: #fff; border: 1px solid #000;">';
```

### 4. Page Template: Page.php

**Location**: `Page.php`

**Responsibility**: Render page structure and include CSS

**Rules**:
- ✅ Include `ThemeCSSGenerator` output in `<head>`
- ✅ Render widget HTML from `WidgetRenderer`
- ❌ NO widget styling in `<style>` tags
- ❌ NO widget-specific CSS rules
- ❌ NO theme-specific widget overrides

**Allowed**:
- Layout CSS (`.page-container`, `.widgets-container` structure)
- Featured widget wrapper HTML (`.featured-widget` container)
- Featured widget animation keyframes (functionality, not styling)

---

## Styling Categories

### 1. Base Widget Styling

**Source**: `ThemeCSSGenerator::generateCompleteStyleBlock()`

**Properties**:
- `background`: From `theme.widget_background`
- `border`: Width from `shape_tokens.border_width`, color from `theme.widget_border_color`
- `border-radius`: From `shape_tokens.corner`
- `box-shadow`: From `shape_tokens.shadow`
- `position`: `relative` (for featured effects)

**CSS Class**: `.widget-item`

### 2. Widget Typography

**Source**: `ThemeCSSGenerator::generateCompleteStyleBlock()`

**Properties**:
- Heading font: From `typography_tokens.color.heading` and `core.typography.font.heading`
- Body font: From `typography_tokens.color.body` and `core.typography.font.body`
- Font sizes: From typography scale tokens
- Colors: Can be gradients (using `background-clip: text`)

**CSS Classes**:
- `.widget-title`: Widget title styling
- `.widget-description`: Widget description styling
- `.widget-content`: Widget content container

### 3. Widget Layout

**Source**: `ThemeCSSGenerator::generateCSSVariables()`

**Properties**:
- `--widget-gap`: Spacing between widgets (from `spacing_tokens.density`)
- `--page-padding`: Padding around page (from `spacing_tokens.density`)

**CSS Classes**:
- `.widgets-container`: Container for all widgets

### 4. Widget Hover States

**Source**: `ThemeCSSGenerator::generateCompleteStyleBlock()`

**Properties**:
- Transform: Slight lift on hover
- Box-shadow: Enhanced shadow on hover
- Based on `border_effect` setting

**CSS Class**: `.widget-item:hover`

### 5. Featured Widget Effects

**Source**: `Page.php` (functionality only, minimal styling)

**Properties**:
- Animation keyframes: Defined in `Page.php` (functionality)
- Effect classes: `.featured-effect-{name}` (container only)
- Actual styling: Inherits from `.widget-item` base styles

**CSS Classes**:
- `.featured-widget`: Featured widget container
- `.featured-effect-{name}`: Effect-specific container

---

## Data Flow

```
┌─────────────────────────────────────────────────────────────┐
│                    Edit Theme Panel                         │
│  (admin-ui/src/components/panels/ThemeEditorPanel.tsx)     │
└───────────────────────┬─────────────────────────────────────┘
                        │
                        │ Saves to database
                        ▼
┌─────────────────────────────────────────────────────────────┐
│                      Database (themes table)                 │
│  - widget_background (column)                               │
│  - widget_border_color (column)                             │
│  - shape_tokens (JSON)                                      │
│  - typography_tokens (JSON)                                 │
│  - spacing_tokens (JSON)                                    │
└───────────────────────┬─────────────────────────────────────┘
                        │
                        │ Theme.php reads
                        ▼
┌─────────────────────────────────────────────────────────────┐
│              ThemeCSSGenerator.php                          │
│  - Reads theme settings from Theme.php                     │
│  - Resolves values (columns > tokens)                       │
│  - Generates CSS with !important                            │
│  - Outputs to <style> tag                                   │
└───────────────────────┬─────────────────────────────────────┘
                        │
                        │ Injected into <head>
                        ▼
┌─────────────────────────────────────────────────────────────┐
│                      Page.php                                │
│  - Includes ThemeCSSGenerator output                         │
│  - Renders widget HTML from WidgetRenderer                  │
│  - NO widget styling                                        │
└───────────────────────┬─────────────────────────────────────┘
                        │
                        │ HTML + CSS combined
                        ▼
┌─────────────────────────────────────────────────────────────┐
│                    Public Page (Browser)                    │
│  - Widgets styled by ThemeCSSGenerator CSS                  │
│  - All styling from single source                           │
└─────────────────────────────────────────────────────────────┘
```

---

## Class Naming Convention

### Required Classes

All widgets MUST include:
- `widget-item`: Base container (required for all styling)

### Content Structure Classes

- `widget-content`: Content wrapper
- `widget-title`: Title element
- `widget-description`: Description element
- `widget-thumbnail-wrapper`: Thumbnail container
- `widget-thumbnail`: Thumbnail image
- `widget-icon-wrapper`: Icon container
- `widget-icon`: Icon element

### Widget Type Classes

- `widget-{type}`: Widget type identifier (e.g., `widget-video`, `widget-text`)
  - **Purpose**: Structure/functionality only, NOT styling
  - **Usage**: JavaScript targeting, conditional rendering
  - **Styling**: Inherits from `.widget-item` base

### Special Classes

- `widget-link-simple`: Widget without thumbnail/icon (structure only)
- `featured-widget`: Featured widget container
- `featured-effect-{name}`: Featured effect container

---

## Styling Rules

### ✅ Allowed

1. **ThemeCSSGenerator.php**:
   - All widget styling
   - CSS variables for spacing
   - Typography rules
   - Hover states
   - Featured widget base styles

2. **Page.php**:
   - Featured widget animation keyframes (functionality)
   - Featured widget container HTML structure
   - Layout CSS (`.page-container`, `.widgets-container` structure only)

3. **WidgetRenderer.php**:
   - HTML structure with class names
   - Semantic markup
   - Accessibility attributes

### ❌ Not Allowed

1. **Page.php**:
   - Widget styling in `<style>` tags
   - Widget-specific CSS rules
   - Theme-specific widget overrides
   - Inline styles on widget elements

2. **WidgetRenderer.php**:
   - Inline `style` attributes
   - Widget-specific styling classes for visual styling
   - CSS in output

3. **Individual Widget Files**:
   - No widget-specific CSS files
   - No inline styles

---

## Implementation Checklist

### Phase 1: Remove Legacy Styling

- [ ] Remove all `.widget-item` styling from `Page.php`
- [ ] Remove all `.widget-*` styling from `Page.php`
- [ ] Remove inline styles from `WidgetRenderer.php`
- [ ] Remove widget-specific CSS classes used for styling
- [ ] Keep HTML structure and class names (for functionality)
- [ ] Keep featured widget animation keyframes (functionality)

### Phase 2: Verify ThemeCSSGenerator

- [ ] Verify all widget styling comes from `ThemeCSSGenerator.php`
- [ ] Ensure `!important` flags are used for base styles
- [ ] Verify CSS variables are generated correctly
- [ ] Test all widget types render correctly

### Phase 3: Rebuild Styling

- [ ] Add missing styling to `ThemeCSSGenerator.php`
- [ ] Ensure all widget types are styled consistently
- [ ] Test hover states
- [ ] Test featured widget effects
- [ ] Verify responsive behavior

---

## Current Issues

### Legacy Styling Locations

1. **Page.php** (lines 539-1128):
   - Extensive widget styling in `<style>` tag
   - Widget-specific rules (`.widget-video`, `.widget-text`, etc.)
   - Theme-specific overrides (`body.theme-aurora-skies .widget-item`)
   - Hover states
   - Typography rules

2. **WidgetRenderer.php**:
   - Inline styles in some render methods
   - Widget-specific class names used for styling

### Solution

Remove all styling from `Page.php` and `WidgetRenderer.php`, keeping only:
- HTML structure
- Class names (for functionality/targeting)
- Featured widget animation keyframes (functionality)

All styling will come from `ThemeCSSGenerator.php` only.

---

## Testing Checklist

After removing legacy styling:

1. **Visual Verification**:
   - [ ] All widgets display correctly
   - [ ] Background colors/gradients apply
   - [ ] Borders apply correctly
   - [ ] Border radius applies
   - [ ] Shadows apply
   - [ ] Typography styles apply

2. **Functionality Verification**:
   - [ ] Widgets are clickable
   - [ ] Hover states work
   - [ ] Featured widget effects work
   - [ ] Responsive layout works

3. **Theme Verification**:
   - [ ] Theme changes apply to widgets
   - [ ] All theme settings affect widgets
   - [ ] No broken styles

---

*Last Updated: 2024-01-XX*

