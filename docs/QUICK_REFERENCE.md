# Page.php Refactoring - Quick Reference

**Quick lookup guide for the modularization changes**

---

## File Locations

### Main File
- `page.php` - Main entry point (1,032 lines)

### CSS Files
- `css/special-effects.css` - All special effects CSS (668 lines)

### JavaScript Files
- `js/email-subscription.js` - Email drawer (105 lines)
- `js/featured-widget-effects.js` - Widget animations (115 lines)
- `js/spatial-tilt.js` - Tilt effect (112 lines)
- `js/widget-marquee.js` - Marquee scrolling (191 lines)
- `js/podcast-drawer-init.js` - Podcast drawer (186 lines)

---

## Adding a New Special Effect

### 1. Add CSS to `css/special-effects.css`
```css
.page-title-effect-my-effect {
    /* Your CSS here */
}

@keyframes my-effect-animation {
    /* Animation keyframes */
}
```

### 2. Update `api/page.php`
```php
$validEffects = ['', 'none', 'aurora-borealis', ..., 'my-effect'];
```

### 3. Done! ✅
No changes to `page.php` needed.

---

## Adding a New JavaScript Feature

### 1. Create file `js/my-feature.js`
```javascript
(function() {
    'use strict';
    // Your code here
})();
```

### 2. Load in `page.php` (before `</body>`)
```php
<script src="/js/my-feature.js?v=<?php echo filemtime(__DIR__ . '/js/my-feature.js'); ?>"></script>
```

### 3. Pass PHP data if needed
```php
<script>
    window.myFeatureConfig = <?php echo json_encode($config); ?>;
</script>
<script src="/js/my-feature.js?v=<?php echo filemtime(__DIR__ . '/js/my-feature.js'); ?>"></script>
```

---

## Common Tasks

### Finding CSS for Special Effects
**Location**: `css/special-effects.css`  
**Not in**: `page.php` (only layout CSS remains inline)

### Finding JavaScript for Features
**Location**: `js/*.js` files  
**Not in**: `page.php` (all JS extracted)

### Debugging JavaScript Errors
**Check**: Browser console shows file name (e.g., `widget-marquee.js:45`)  
**Fix**: Edit the specific file, not `page.php`

### Updating Widget Animations
**File**: `js/featured-widget-effects.js`  
**CSS**: `css/special-effects.css` (keyframes)

---

## Versioning & Cache Busting

All external files use `filemtime()` for automatic versioning:

```php
<link rel="stylesheet" href="/css/special-effects.css?v=<?php echo filemtime(__DIR__ . '/css/special-effects.css'); ?>">
<script src="/js/my-feature.js?v=<?php echo filemtime(__DIR__ . '/js/my-feature.js'); ?>"></script>
```

**How it works:**
- File modification time added as `?v=timestamp`
- Browser caches with version
- Auto-updates when file changes
- No manual version numbers needed

---

## Data Passing Patterns

### PHP → JavaScript

```php
<!-- In page.php -->
<script>
    window.myFeatureConfig = <?php echo json_encode($config); ?>;
</script>
<script src="/js/my-feature.js"></script>
```

```javascript
// In js/my-feature.js
const config = window.myFeatureConfig;
// Use config...
```

### CSS → JavaScript Integration

```php
<!-- In page.php -->
<body class="<?php echo $cssGenerator->getSpatialEffectClass(); ?>">
```

```javascript
// In js/spatial-tilt.js
if (!document.body.classList.contains('spatial-tilt')) {
    return; // Exit if not enabled
}
```

---

## Performance Notes

### First Page Load
- Downloads HTML + all assets
- Slightly slower (parallel download helps)

### Subsequent Loads
- Only HTML downloaded (assets from cache)
- **~55% faster** than before

### Browser Caching
- CSS/JS cached for days/weeks
- Massive bandwidth savings for repeat visitors

---

## Testing Checklist

### After Making Changes

- [ ] Check browser console for errors
- [ ] Verify CSS loads (Network tab)
- [ ] Verify JS loads (Network tab)
- [ ] Test functionality works
- [ ] Hard refresh (Ctrl+Shift+R / Cmd+Shift+R)
- [ ] Check cache headers (if applicable)

---

## Common Issues

### CSS Not Updating
**Solution**: Clear browser cache or hard refresh (Ctrl+Shift+R)

### JavaScript Error: "window.myConfig is undefined"
**Solution**: Make sure `<script>` tag sets `window.myConfig` before loading the JS file

### File Not Found (404)
**Solution**: Check file path is correct (relative to web root)

---

## Need More Details?

- **Full Documentation**: See `docs/PAGE_PHP_MODULARIZATION.md`
- **Summary**: See `docs/REFACTORING_SUMMARY.md`
- **Original Diagnostic**: See `docs/page-php-diagnostic-report.md`

---

**Last Updated**: 2025-01-XX

