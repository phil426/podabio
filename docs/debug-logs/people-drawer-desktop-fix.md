# People Widget Drawer Desktop Fix

**Date:** 2024-12-19  
**Problem:** People widget accordion drawer not opening on desktop view (page.php)  
**Status:** ✅ RESOLVED

## Problem Description

The People widget's accordion drawer worked perfectly on mobile view but failed to open on desktop view when using `page.php`. The drawer would not respond to clicks on desktop, even though the chevron icon would rotate.

## Root Cause

**Primary Issue:** The drawer HTML had `style="display: none;"` inline, which prevented CSS `max-height` transitions from working.

### Why This Broke Desktop But Not Mobile

- **Mobile:** The drawer was likely being initialized differently or the JavaScript was handling it differently
- **Desktop:** The drawer is nested inside `.iphone-content` which has `overflow-x: hidden`, and the `display: none` prevented the element from participating in layout calculations needed for transitions

## Solution

### 1. Removed Inline `display: none`

**File:** `classes/WidgetRenderer.php` (line ~2102)

**Before:**
```php
$html .= '<div class="people-accordion-drawer" id="' . htmlspecialchars($accordionContentId) . '" style="display: none;">';
```

**After:**
```php
// NOTE: Do NOT use display: none - it prevents CSS max-height transitions
// The drawer starts closed via CSS (max-height: 0, opacity: 0)
$html .= '<div class="people-accordion-drawer" id="' . htmlspecialchars($accordionContentId) . '">';
```

### 2. Enhanced CSS for Proper Hidden State

**File:** `css/widgets.css`

**Changes:**
- Added `visibility: hidden` when closed (instead of `display: none`)
- Added `visibility: visible` when open
- Added `display: block` explicitly to ensure element participates in layout
- Added `visibility` to transition properties

**Key Principle:** Use `max-height: 0` + `opacity: 0` + `visibility: hidden` for closed state, NOT `display: none`. This allows CSS transitions to work.

## Technical Details

### CSS Transition Requirements

For CSS transitions to work, the element must:
1. Be in the DOM (not `display: none`)
2. Have a computed style that can transition
3. Be visible in the layout flow (even if visually hidden)

### Why `display: none` Breaks Transitions

- `display: none` removes the element from the layout entirely
- The browser cannot calculate intermediate values for transitions
- `max-height` transitions require the element to exist in layout

### Alternative: `visibility: hidden`

- Element remains in layout flow
- Takes up no space (when combined with `max-height: 0`)
- Allows transitions to work
- Properly hides from screen readers

## Testing

### Desktop View
- ✅ Drawer opens when header button clicked
- ✅ Drawer closes when header button clicked again
- ✅ Chevron icon rotates correctly
- ✅ Drawer content scrolls when needed
- ✅ Drawer works inside `.iphone-content` container

### Mobile View
- ✅ Drawer continues to work as before
- ✅ No regressions introduced

## Files Modified

1. `classes/WidgetRenderer.php` - Removed inline `display: none`
2. `css/widgets.css` - Enhanced drawer CSS with `visibility` handling

## Related Issues

This fix also addresses:
- Drawer not responding to clicks on desktop
- CSS transitions not working for drawer animation
- Drawer appearing "stuck" in closed state

## Prevention

**Rule:** Never use `display: none` on elements that need CSS transitions. Use:
- `max-height: 0` + `opacity: 0` + `visibility: hidden` for closed state
- `max-height: [value]` + `opacity: 1` + `visibility: visible` for open state

## Protocol Used

This fix was applied using the **Hard Problem Protocol**:
1. ✅ Gathered information systematically
2. ✅ Formed hypotheses (5 prioritized)
3. ✅ Tested primary hypothesis (display: none blocking transitions)
4. ✅ Confirmed root cause
5. ✅ Implemented solution
6. ✅ Documented fix

---

**Resolution Time:** ~15 minutes  
**Complexity:** Medium  
**Impact:** High (fixes critical desktop functionality)

