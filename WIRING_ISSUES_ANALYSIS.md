# Wiring Issues Analysis & Recommendations

## Issue 1: Page Title Special Effects

### Problem
- **Editor Field**: `page-title-effect` 
- **Field Registry**: Maps to `tokenPath: 'typography_tokens.effect.heading'` (theme-level)
- **page.php Expects**: `$page['page_name_effect']` (page-level database column)
- **Result**: Effects saved to theme JSON, but `page.php` reads from page column → mismatch

### Root Cause
The field is registered as a theme-level field (`typography_tokens.effect.heading`), but `page.php` reads from the page-level database column `page_name_effect`. These are two different storage locations.

### Recommendation
**Option A (Recommended)**: Change field registry to use page-level path
```typescript
fieldRegistry.register({
  id: 'page-title-effect',
  tokenPath: 'page.page_name_effect', // Changed from 'typography_tokens.effect.heading'
  // ... rest of config
});
```

**Option B**: Update `page.php` to read from theme tokens instead of page column
- More complex, requires parsing theme JSON
- Less consistent with current architecture

### Implementation Steps
1. Update `fieldRegistry.ts` to change `page-title-effect` tokenPath to `'page.page_name_effect'`
2. Update `themeMapper.ts` to handle `page.page_name_effect` correctly (it should skip theme save, load from page)
3. Update `previewRenderer.ts` to read effect from `uiState['page-title-effect']` directly (not from theme tokens)
4. Verify theme save doesn't include page-level fields
5. Test that effects persist when saving themes

---

## Issue 2: Bio Text Color/Gradient

### Problem
- **Editor Field**: `page-bio-color` (supports gradients via PodaColorPicker)
- **Field Registry**: Maps to `tokenPath: 'typography_tokens.color.body'` (theme-level)
- **page.php**: Line 516 doesn't apply color - only alignment, size class, font-family
- **CSS**: `css/typography.css` line 15 uses `var(--page-description-color, ...)` for color
- **Issue**: Gradients can't be applied via CSS `color` property - need `background-image` with `-webkit-background-clip: text`

### Root Cause
1. Bio text in `page.php` doesn't have inline color style (relies on CSS variable)
2. CSS uses `color` property which doesn't support gradients
3. When gradient is selected, it's saved as gradient string but can't be rendered

### Recommendation
**Update `page.php` to apply color/gradient inline:**

```php
<?php if ($page['podcast_description']): 
    $bioAlignment = $page['bio_alignment'] ?? 'center';
    $bioTextSize = $page['bio_text_size'] ?? 'medium';
    $alignmentStyle = 'text-align: ' . h($bioAlignment) . '; ';
    $sizeClass = 'bio-size-' . h($bioTextSize);
    
    // Get bio color from theme tokens
    $bioColor = null;
    if (!empty($theme) && !empty($theme['typography_tokens'])) {
        $typographyTokens = is_string($theme['typography_tokens']) 
            ? json_decode($theme['typography_tokens'], true) 
            : $theme['typography_tokens'];
        $bioColor = $typographyTokens['color']['body'] ?? null;
    }
    
    // Build style with color/gradient support
    $colorStyle = '';
    if ($bioColor) {
        if (strpos($bioColor, 'gradient') !== false) {
            // Gradient: use background-image with text clipping
            $colorStyle = 'background-image: ' . h($bioColor) . '; ';
            $colorStyle .= '-webkit-background-clip: text; ';
            $colorStyle .= 'background-clip: text; ';
            $colorStyle .= '-webkit-text-fill-color: transparent; ';
            $colorStyle .= 'color: transparent; '; // Fallback
        } else {
            // Solid color: use color property
            $colorStyle = 'color: ' . h($bioColor) . '; ';
        }
    }
    
    $bioContent = $page['podcast_description'];
    $bioContent = nl2br($bioContent);
    $bioContent = strip_tags($bioContent, '<strong><em><u><br>');
?>
    <p class="page-description <?php echo $sizeClass; ?>" 
       style="<?php echo $alignmentStyle . $colorStyle; ?> font-family: var(--font-family-body, inherit);">
       <?php echo $bioContent; ?>
    </p>
<?php endif; ?>
```

**Alternative**: Update CSS to support gradients
- Modify `css/typography.css` to detect gradient and apply background-clip
- More complex, requires CSS variable inspection

### Implementation Steps
1. Update `page.php` line 516 to apply bio color/gradient inline (as shown above)
2. Update `page-preview.php` similarly (if it has bio text)
3. Test with solid colors
4. Test with gradients
5. Verify gradient renders correctly in browser

---

## Summary

### Priority
1. **High**: Bio text color/gradient (affects user experience immediately)
2. **High**: Page title effects (core feature not working)

### Files to Modify
1. `admin-ui/src/components/panels/themes/utils/fieldRegistry.ts` - Change `page-title-effect` tokenPath
2. `page.php` - Add bio color/gradient inline styling (2 locations: main content + drawer)
3. `page-preview.php` - Add bio color/gradient inline styling (if applicable)
4. `admin-ui/src/components/panels/themes/utils/previewRenderer.ts` - Update to read effect from uiState directly

### Testing Checklist
- [ ] Page title effects save and load correctly
- [ ] Page title effects persist when saving themes
- [ ] Bio text solid colors work
- [ ] Bio text gradients work
- [ ] Bio text gradients render correctly in all browsers
- [ ] Preview matches actual page rendering

