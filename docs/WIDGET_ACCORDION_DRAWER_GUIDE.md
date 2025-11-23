# Widget Accordion Drawer Build Guide

This guide documents the pattern for creating widgets with accordion drawers that slide down from the bottom, keeping the main widget content (including thumbnails) fixed in place.

## Overview

The accordion drawer pattern allows widgets to have expandable content that slides down from the bottom without affecting the main widget layout. This is perfect for widgets that need to show additional content on demand while keeping the primary widget elements (thumbnail, title, button) visible and fixed.

## Key Principles

1. **Thumbnail stays fixed** - Thumbnail is outside `widget-content` and doesn't move when drawer opens
2. **Button area stays intact** - The header button with title remains visible and doesn't break
3. **Drawer is separate** - Drawer is a sibling of `widget-content`, not inside it
4. **Flex wrap layout** - Uses flexbox with wrapping to allow drawer on new line
5. **Smooth animation** - Uses `max-height` transition for smooth slide-down effect

## HTML Structure

```php
$html = '<div class="widget-item widget-{widget-type}" id="' . htmlspecialchars($containerId) . '">';
    
    // Widget thumbnail (outside widget-content, on the left - stays fixed)
    if ($thumbnail) {
        $html .= '<div class="widget-thumbnail-wrapper">';
        $html .= '<img src="' . htmlspecialchars(normalizeImageUrl($thumbnail)) . '" alt="..." class="widget-thumbnail" />';
        $html .= '</div>';
    }
    
    // Widget content area (right side - fixed height)
    $html .= '<div class="widget-content">';
        
        // Header button with accordion toggle
        $accordionId = $containerId . '-accordion';
        $accordionContentId = $accordionId . '-content';
        $html .= '<button type="button" class="{widget-type}-widget-header" data-accordion-content="' . htmlspecialchars($accordionContentId) . '" aria-expanded="false">';
        $html .= '<div class="widget-title">' . htmlspecialchars($title) . '</div>';
        $html .= '<span class="{widget-type}-accordion-arrow"><i class="fas fa-chevron-down"></i></span>';
        $html .= '</button>';
        
        // Other always-visible content (paragraphs, etc.)
        if ($paragraph) {
            $html .= '<div class="{widget-type}-widget-paragraph">' . nl2br(htmlspecialchars($paragraph)) . '</div>';
        }
        
    $html .= '</div>'; // Close widget-content
    
    // Accordion drawer - separate bottom drawer (outside widget-content)
    $html .= '<div class="{widget-type}-accordion-drawer" id="' . htmlspecialchars($accordionContentId) . '" style="display: none;">';
        
        // Drawer content (search, lists, etc.)
        $html .= '<div class="{widget-type}-drawer-content">';
            // ... drawer content here
        $html .= '</div>';
        
    $html .= '</div>'; // Close accordion drawer
    
$html .= '</div>'; // Close widget-item
```

## CSS Structure

### Widget Container

```css
/* Widget base styles */
.widget-{widget-type} {
    width: 100%;
    position: relative;
    flex-wrap: wrap; /* CRITICAL: Allows drawer to wrap to new line */
    align-items: flex-start; /* Prevents vertical centering issues */
}

/* Thumbnail positioning */
.widget-{widget-type} > .widget-thumbnail-wrapper {
    flex-shrink: 0;
    align-self: flex-start; /* Keeps thumbnail at top, doesn't center */
}

/* Widget content area */
.widget-{widget-type} > .widget-content {
    flex: 1;
    min-width: 0;
    width: 100%;
    box-sizing: border-box;
    overflow-x: hidden;
    position: relative;
    z-index: 1;
    align-self: flex-start; /* Prevents vertical centering */
}
```

### Header Button

```css
.{widget-type}-widget-header {
    width: 100%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 0;
    background: transparent;
    border: none;
    cursor: pointer;
    text-align: left;
    color: inherit;
    transition: opacity 0.2s ease;
}

.{widget-type}-widget-header:hover {
    opacity: 0.8;
}

.{widget-type}-widget-header .widget-title {
    flex: 1;
    margin: 0;
}
```

### Accordion Arrow Button

```css
.{widget-type}-accordion-arrow {
    margin-left: auto;
    flex-shrink: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    width: 24px;
    height: 24px;
    min-width: 24px;
    min-height: 24px;
    border: 1px solid rgba(0, 0, 0, 0.2);
    border-radius: 4px;
    background: rgba(255, 255, 255, 0.8);
    transition: all 0.2s ease;
    color: rgba(0, 0, 0, 0.6);
    padding: 0;
}

.{widget-type}-widget-header:hover .{widget-type}-accordion-arrow {
    background: rgba(0, 0, 0, 0.05);
    border-color: rgba(0, 0, 0, 0.3);
}

.{widget-type}-accordion-arrow i {
    transition: transform 0.3s ease;
    font-size: 0.75rem;
    display: block;
}
```

### Accordion Drawer

```css
/* Accordion drawer - slides down from bottom of widget */
.{widget-type}-accordion-drawer {
    width: 100%;
    box-sizing: border-box;
    overflow-x: hidden;
    overflow-y: auto;
    max-height: 0;
    transition: max-height 0.3s ease, padding 0.3s ease, border-top 0.3s ease, margin-top 0.3s ease, opacity 0.3s ease;
    border-top: 0 solid rgba(0, 0, 0, 0.1);
    padding: 0;
    margin-top: 0;
    background: transparent;
    opacity: 0;
    order: 3; /* CRITICAL: Ensures it appears after widget-content */
    flex-basis: 100%; /* CRITICAL: Forces it to full width on new line */
    align-self: stretch; /* Stretches to full width */
}

.{widget-type}-accordion-drawer.open {
    max-height: 600px; /* Adjust based on expected content height */
    padding: 1rem;
    border-top-width: 1px;
    margin-top: 0.5rem;
    opacity: 1;
}
```

## JavaScript Implementation

```javascript
(function() {
    function initAccordion() {
        const container = document.getElementById("{containerId}");
        if (!container) return;
        
        const headerButton = container.querySelector(".{widget-type}-widget-header");
        const drawer = document.getElementById("{accordionContentId}");
        
        if (headerButton && drawer) {
            // Remove any existing listeners by cloning the button
            const newButton = headerButton.cloneNode(true);
            headerButton.parentNode.replaceChild(newButton, headerButton);
            
            newButton.addEventListener("click", function(e) {
                e.preventDefault();
                e.stopPropagation();
                
                // Toggle drawer using class instead of display
                const isOpen = drawer.classList.contains("open");
                if (isOpen) {
                    drawer.classList.remove("open");
                    drawer.style.display = "none";
                    newButton.setAttribute("aria-expanded", "false");
                } else {
                    drawer.style.display = "block";
                    // Force reflow to ensure display is set before adding class
                    drawer.offsetHeight;
                    drawer.classList.add("open");
                    newButton.setAttribute("aria-expanded", "true");
                }
                
                // Rotate arrow icon
                const icon = newButton.querySelector(".{widget-type}-accordion-arrow i");
                if (icon) {
                    icon.style.transform = isOpen ? "rotate(0deg)" : "rotate(180deg)";
                }
            });
        }
    }
    
    // Run when DOM is ready
    if (document.readyState === "loading") {
        document.addEventListener("DOMContentLoaded", initAccordion);
    } else {
        initAccordion();
    }
})();
```

## Key CSS Properties Explained

### `flex-wrap: wrap`
Allows the drawer to wrap to a new line below the thumbnail and content, rather than trying to fit on the same line.

### `flex-basis: 100%`
Forces the drawer to take the full width of the container, ensuring it appears on its own line.

### `order: 3`
Ensures the drawer appears after the thumbnail and content in the visual order, even though it's last in the HTML.

### `align-self: flex-start`
Prevents vertical centering. Without this, `align-items: center` on the parent would center all children vertically, causing the thumbnail to move when the drawer opens.

### `max-height` transition
Provides smooth animation. The drawer starts at `max-height: 0` and expands to `max-height: 600px` (or appropriate value) when opened.

## Common Pitfalls to Avoid

1. **Don't put drawer inside `widget-content`** - This causes the content area to expand and pushes the thumbnail down
2. **Don't forget `flex-wrap: wrap`** - Without it, the drawer will try to fit on the same line
3. **Don't use `display: none` for animation** - Use `max-height` transition instead for smooth animation
4. **Don't forget `align-self: flex-start`** - Prevents unwanted vertical centering
5. **Don't forget `flex-basis: 100%`** - Ensures drawer takes full width on its own line
6. **Always use `normalizeImageUrl()`** - For thumbnails to work in both dev and production

## Example: People Widget

See `classes/WidgetRenderer.php` - `renderPeople()` method (lines ~2048-2238) for a complete working example.

## Testing Checklist

- [ ] Thumbnail stays fixed on left when drawer opens
- [ ] Button/title area remains visible and intact
- [ ] Drawer slides down smoothly from bottom
- [ ] Arrow icon rotates correctly
- [ ] Drawer content is scrollable if it exceeds max-height
- [ ] Works on both localhost and production
- [ ] Thumbnail URLs work in both environments
- [ ] No layout shifts or content disappearing

## Variations

### Drawer with Different Max Height
Adjust `max-height` in the `.open` state based on expected content:
```css
.{widget-type}-accordion-drawer.open {
    max-height: 400px; /* Smaller drawer */
    /* or */
    max-height: 800px; /* Larger drawer */
}
```

### Drawer with Background Color
Add background to make drawer more distinct:
```css
.{widget-type}-accordion-drawer {
    background: rgba(255, 255, 255, 0.95);
}

.{widget-type}-accordion-drawer.open {
    background: rgba(255, 255, 255, 0.98);
}
```

### Drawer with Rounded Bottom Corners
```css
.{widget-type}-accordion-drawer {
    border-radius: 0 0 8px 8px;
}
```

## Related Files

- `classes/WidgetRenderer.php` - Widget rendering logic
- `css/widgets.css` - Widget-specific styles
- `includes/helpers.php` - `normalizeImageUrl()` function for environment-aware URLs

