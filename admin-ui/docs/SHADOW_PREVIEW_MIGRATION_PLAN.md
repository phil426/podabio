# Shadow Preview Migration Plan

## Overview
Replace all iframe-based previews with Shadow DOM-based React previews for better performance, easier debugging, and no cross-origin issues.

## Current State Analysis

### Iframe Usage Locations

1. **`ThemePreview.tsx`** (Primary theme editor preview)
   - Loads: `/page.php?username=...&preview_mode=1&preview_width=...`
   - Injects CSS variables after iframe loads
   - Handles hotspot clicks via `postMessage` from iframe
   - Supports device presets and scaling
   - Handles auth errors (login page detection)

2. **`CanvasViewport.tsx`** (Preview overlay)
   - Loads: `/page.php?username=...&preview_width=...`
   - Used in `PreviewOverlay.tsx` for full-screen preview
   - Simpler than ThemePreview (no hotspots, no CSS injection)

3. **`PreviewOverlay.tsx`** (Full-screen preview modal)
   - Wraps `CanvasViewport`
   - Blocks interactions (clicks, forms) in preview mode

### What `page.php` Renders

The PHP page (`page.php`) renders:
- Profile image (with QR code support)
- Page title (podcast name or username)
- Page description (podcast description or bio)
- Podcast player bar (if podcast enabled)
- Widgets (links, people, text blocks, etc.)
- Social icons
- All styled with CSS variables from theme

### Current CSS Variable Injection

The iframe preview injects:
- All CSS variables from `cssVars` prop
- Override CSS with `!important` to force theme changes
- Profile image URL updates (for Theme Wizard preview)
- Hotspot visibility toggles
- Page title effect classes
- Text shadow updates

### Hotspot Click Handling

- `page.php` sends `postMessage` with `{ type: 'hotspot-click', sectionId, widgetId, x, y }`
- `ThemePreview` receives and converts iframe coordinates to parent window coordinates
- Opens modals or triggers callbacks

## Compatibility Requirements

### Must Support

1. **All CSS Variables** (from `generatePreviewCSSVars.ts`)
   - Page background, spacing
   - Profile image (size, radius, effects, shadows, glow, border)
   - Page title (color, font, size, effects, shadows, glow, text-shadow)
   - Page description (color, font, size, alignment)
   - Widgets (background, border, radius, shadows, glow)
   - Widget text (heading/body colors, fonts, sizes)
   - Social icons (color, size, spacing)
   - Podcast player (background, border, text color)
   - All font family variables (`--font-family-heading`, `--font-family-body`)

2. **All Page Elements** (must match `page.php` structure exactly)
   - Profile image with QR code toggle (two `<img>` tags, toggle visibility)
   - Page title with effects (glow, shadow classes like `page-title-effect-glow`)
   - Page description with alignment and size classes
   - Podcast player bar (if enabled) - complex component with tabs, controls, artwork
   - All widget types from `WidgetRenderer.php`:
     - `custom_link` - Links with thumbnails/icons
     - `youtube_video` - YouTube embeds
     - `text_html` - HTML content blocks
     - `heading_block` - Heading widgets (h1, h2, h3)
     - `text_note` - Italic note text
     - `divider_rule` - Divider lines
     - `profile_carousel` - Profile carousels
     - `image` - Image widgets
     - `podcast_player_custom` - Custom podcast players
     - `email_subscription` - Email forms
     - `shopify_product` - Shopify products
     - `shopify_product_list` - Shopify product lists
     - `shopify_collection` - Shopify collections
     - `giphy_search` - Giphy search
     - `giphy_trending` - Giphy trending
     - `giphy_random` - Giphy random
     - `people` - People widgets
     - `rolodex` - Legacy rolodex
   - Social icons (all platforms from `getPlatformIcon()`)
   - Widget featured effects (glow, shadow, etc.)
   - Widget visibility toggles
   - Widget locked states

3. **Device Presets & Scaling**
   - Multiple device sizes (iPhone, Android, etc.) - from `DEVICE_PRESETS` in `ThemePreview.tsx`
   - Scale factor (0.7 for ThemePreview, 0.75 for CanvasViewport)
   - Phone frame styling (rounded corners, 8px border, shadow)
   - Inner content radius (14px = 22px outer - 8px border)

4. **Hotspot Interactions**
   - Click detection on editable sections (profile-image, page-title, page-description, podcast-player-bar, social-icons, widget-settings, page-background)
   - Context menu on right-click
   - Visual hotspot indicators (glow effects via `::after` pseudo-elements)
   - Coordinate calculation (simpler - no iframe conversion needed)
   - `data-hotspot` attributes on elements

5. **Theme Wizard Integration**
   - Temporary profile image preview (`--preview-profile-image-url`)
   - Live CSS variable updates without iframe reload
   - Page title effect classes (dynamically add/remove based on CSS var)

6. **PHP-Specific Features to Replicate**
   - QR code toggle (two images, toggle visibility)
   - HTML sanitization for widget content
   - Link click tracking (`/click.php?link_id=...&page_id=...`)
   - Podcast player JavaScript functionality (if needed for preview)
   - Font Awesome icon rendering
   - Image error handling (hide broken images, show fallback)

## Implementation Plan

### Phase 1: Create Shadow Preview Component

**File**: `admin-ui/src/components/panels/themes/preview/ShadowThemePreview.tsx`

**Features**:
- Shadow DOM host element
- React portal to render content in Shadow DOM
- CSS variable injection via Shadow DOM styles
- Device preset support
- Scaling support

**Structure**:
```typescript
interface ShadowThemePreviewProps {
  cssVars: Record<string, string>;
  page: PageSnapshot;
  widgets: Widget[];
  socialIcons: SocialIcon[];
  selectedDevice: DevicePreset;
  previewScale?: number;
  hotspotsVisible?: boolean;
  onHotspotClick?: (sectionId: string, widgetId?: string) => void;
}
```

**Components to Create**:
1. `ShadowPageContent` - Main content renderer
2. `ShadowProfileImage` - Profile image with QR code
3. `ShadowPageTitle` - Title with effects
4. `ShadowPageDescription` - Description text
5. `ShadowPodcastPlayer` - Podcast player bar
6. `ShadowWidget` - Widget renderer (supports all widget types)
7. `ShadowSocialIcons` - Social icons grid

### Phase 2: Widget Rendering Components

**Files**:
- `admin-ui/src/components/panels/themes/preview/shadow/ShadowWidget.tsx`
- `admin-ui/src/components/panels/themes/preview/shadow/ShadowLinkWidget.tsx`
- `admin-ui/src/components/panels/themes/preview/shadow/ShadowPeopleWidget.tsx`
- `admin-ui/src/components/panels/themes/preview/shadow/ShadowTextWidget.tsx`

**Requirements**:
- Match `page.php` widget rendering exactly
- Support all widget styles (background, border, radius, shadows, glow)
- Support widget text styles (heading/body colors, fonts, sizes)
- Support widget visibility toggles
- Support widget featured/locked states

### Phase 3: Hotspot System

**File**: `admin-ui/src/components/panels/themes/preview/shadow/ShadowHotspot.tsx`

**Features**:
- Overlay divs on editable sections
- Click handlers that call `onHotspotClick`
- Visual glow indicators (CSS `::after` pseudo-elements)
- Right-click context menu support
- Coordinate calculation (no iframe conversion needed)

### Phase 4: Replace ThemePreview iframe

**File**: `admin-ui/src/components/panels/themes/preview/ThemePreview.tsx`

**Changes**:
1. Add feature flag or prop to switch between iframe and Shadow preview
2. Replace iframe rendering with `ShadowThemePreview`
3. Remove iframe-specific code:
   - `iframeRef`, `iframeLoading`, `iframeError`
   - `injectCSSVars()` function
   - `postMessage` listener
   - Auth error detection
4. Update hotspot click handling (direct React events instead of postMessage)
5. Keep device preset selector
6. Keep phone frame styling

### Phase 5: Replace CanvasViewport iframe

**File**: `admin-ui/src/components/layout/CanvasViewport.tsx`

**Changes**:
1. Replace iframe with `ShadowThemePreview`
2. Remove iframe-specific code:
   - `iframeRef`, `iframeLoading`, `iframeError`
   - `dataVersion` state (not needed - direct React updates)
   - `publicPageUrl` construction
3. Keep device preset support
4. Keep scaling support

### Phase 6: Update PreviewOverlay

**File**: `admin-ui/src/components/layout/PreviewOverlay.tsx`

**Changes**:
1. Remove iframe click blocking (not needed - direct React control)
2. Keep preview mode badge and exit button
3. Ensure `ShadowThemePreview` respects preview mode (no navigation)

### Phase 7: Testing & Validation

**Test Scenarios**:
1. ✅ All CSS variables apply correctly
2. ✅ All page elements render correctly
3. ✅ All widget types render correctly
4. ✅ Hotspot clicks work correctly
5. ✅ Device presets work correctly
6. ✅ Scaling works correctly
7. ✅ Theme Wizard preview image works
8. ✅ Profile image QR code toggle works
9. ✅ Podcast player renders and functions
10. ✅ Social icons render and link correctly
11. ✅ Widget visibility toggles work
12. ✅ Widget featured/locked states work
13. ✅ Page title effects render correctly
14. ✅ Widget shadows/glows render correctly
15. ✅ Performance is better than iframe

## Technical Considerations

### Shadow DOM Styling

- All styles must be injected into Shadow DOM
- CSS variables cascade into Shadow DOM
- Need to include font imports in Shadow DOM
- Need to include any external CSS (Font Awesome, etc.)

### React Portal to Shadow DOM

```typescript
const shadowRoot = hostRef.current?.attachShadow({ mode: 'open' });
if (shadowRoot) {
  createPortal(<ShadowPageContent />, shadowRoot as unknown as Element);
}
```

### CSS Variable Application

- Apply CSS variables to Shadow DOM `:host` element
- Use same variable names as iframe injection
- Ensure `!important` overrides work (may need inline styles for some)

### Widget Data Structure

- Use `usePageSnapshot()` to get widgets, social icons, page data
- Match PHP widget rendering logic exactly
- Support all widget types from `WidgetRenderer.php`

### Performance

- Shadow DOM should be faster (no iframe overhead)
- No network requests for `page.php`
- Direct React state updates
- No cross-origin restrictions

### Backward Compatibility

- Keep iframe code commented or behind feature flag initially
- Allow switching back to iframe if issues arise
- Remove iframe code after validation

## Migration Steps

1. **Create branch**: `git checkout -b shadow-preview-migration`
2. **Phase 1**: Create `ShadowThemePreview` component with basic structure
3. **Phase 2**: Create widget rendering components
4. **Phase 3**: Implement hotspot system
5. **Phase 4**: Replace `ThemePreview` iframe (behind feature flag)
6. **Phase 5**: Replace `CanvasViewport` iframe
7. **Phase 6**: Update `PreviewOverlay`
8. **Phase 7**: Test all scenarios
9. **Phase 8**: Remove feature flag and iframe code
10. **Phase 9**: Merge to main

## Risk Assessment

### Low Risk
- Shadow DOM is well-supported in modern browsers
- React portals work with Shadow DOM
- CSS variables work in Shadow DOM

### Medium Risk
- Widget rendering must match PHP exactly (visual parity)
- Hotspot coordinate calculation (but simpler than iframe conversion)
- Font loading in Shadow DOM

### High Risk
- None identified - Shadow DOM is a standard web API

## Success Criteria

1. ✅ Visual parity with iframe preview
2. ✅ All interactions work (hotspots, clicks, context menus)
3. ✅ Performance improvement (faster updates, no iframe reload)
4. ✅ No regression in functionality
5. ✅ Easier debugging (React DevTools work)
6. ✅ No cross-origin issues

## Timeline Estimate

- Phase 1: 2-3 hours (Shadow preview structure)
- Phase 2: 4-6 hours (Widget components)
- Phase 3: 2-3 hours (Hotspot system)
- Phase 4: 2-3 hours (ThemePreview replacement)
- Phase 5: 1-2 hours (CanvasViewport replacement)
- Phase 6: 1 hour (PreviewOverlay update)
- Phase 7: 3-4 hours (Testing)
- **Total: 15-22 hours**

## Compatibility Checklist

### ✅ Verified Compatible

1. **Shadow DOM Support**
   - ✅ All modern browsers support Shadow DOM
   - ✅ React portals work with Shadow DOM
   - ✅ CSS variables cascade into Shadow DOM
   - ✅ Event handling works in Shadow DOM

2. **CSS Variables**
   - ✅ CSS variables work in Shadow DOM
   - ✅ Can apply via `:host` selector or inline styles
   - ✅ `!important` overrides work in Shadow DOM
   - ✅ Font imports work in Shadow DOM

3. **React Components**
   - ✅ Can render React components in Shadow DOM via portals
   - ✅ State management works (useState, useEffect, etc.)
   - ✅ Event handlers work (onClick, etc.)
   - ✅ React DevTools can inspect Shadow DOM content

4. **Data Access**
   - ✅ `usePageSnapshot()` hook available for page data
   - ✅ Widgets, social icons, page data all accessible
   - ✅ CSS variables generated by `generatePreviewCSSVars.ts`

### ⚠️ Requires Implementation

1. **Widget Rendering**
   - ⚠️ Need React components for all widget types
   - ⚠️ Must match `WidgetRenderer.php` output exactly
   - ⚠️ Need to handle widget visibility/featured/locked states

2. **Podcast Player**
   - ⚠️ Complex component with tabs, controls, artwork
   - ⚠️ May need JavaScript functionality for preview
   - ⚠️ Need to handle player state (playing, paused, etc.)

3. **Hotspot System**
   - ⚠️ Need to implement click detection
   - ⚠️ Need to implement glow effects (CSS `::after`)
   - ⚠️ Need to implement context menu
   - ⚠️ Simpler than iframe (no coordinate conversion needed)

4. **Font Loading**
   - ⚠️ Need to inject font imports into Shadow DOM
   - ⚠️ Need to include Font Awesome in Shadow DOM
   - ⚠️ May need to load fonts dynamically

### ❌ Potential Issues

1. **None Identified**
   - Shadow DOM is a standard web API
   - React portals are well-tested
   - CSS variables are well-supported
   - No known compatibility issues

## Notes

- Start with `ShadowPreviewDemo` as a reference
- Use `ThemePreview.tsx` React rendering code (social icons, widgets) as a starting point
- Match `page.php` HTML structure exactly for visual parity
- Test on multiple devices/browsers
- Consider keeping iframe as fallback for older browsers (if needed)
- Widget rendering is the biggest challenge - need to match PHP output exactly
- Podcast player may need simplified version for preview (or full implementation)

