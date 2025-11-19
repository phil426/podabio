# Border Effect Shadow Fix

## Problem
The border effect shadow was being saved correctly in the Edit Theme page (`widget_styles.border_effect = 'shadow'` and `widget_styles.border_shadow_intensity = 'subtle'|'pronounced'`), but it was not being reflected on the user page.

## Root Cause
The `ThemeCSSGenerator` class was not using the `border_shadow_intensity` value from `widget_styles` to determine which shadow level to apply. Instead, it was always using `level_1` if available, then falling back to `level_2`, regardless of the user's selection.

### Data Flow
1. **Theme Editor** saves:
   - `widget_styles.border_effect = 'shadow'`
   - `widget_styles.border_shadow_intensity = 'subtle' | 'pronounced' | 'none'`
   - `shape_tokens.shadow.level_1 = '0 2px 6px rgba(15, 23, 42, 0.12)'` (subtle)
   - `shape_tokens.shadow.level_2 = '0 8px 24px rgba(15, 23, 42, 0.25)'` (pronounced)

2. **ThemeCSSGenerator** was reading:
   - `border_effect` correctly
   - But ignoring `border_shadow_intensity`
   - Always using `level_1` if present, then `level_2`

## Solution
Updated `ThemeCSSGenerator.php` to:
1. Read `border_shadow_intensity` from `widget_styles`
2. Map it to the correct shadow level:
   - `'subtle'` → use `shape_tokens.shadow.level_1`
   - `'pronounced'` → use `shape_tokens.shadow.level_2`
   - `'none'` → no shadow
3. Apply the correct shadow value in both base and hover states

## Changes Made

### File: `classes/ThemeCSSGenerator.php`

#### Base Widget Shadow (lines 1008-1030)
- **Before**: Always used `level_1` if available, then `level_2`
- **After**: Uses `border_shadow_intensity` to select the correct level

```php
// Get shadow intensity from widget_styles to determine which level to use
$shadowIntensity = $this->widgetStyles['border_shadow_intensity'] ?? 'subtle';

// Map shadow intensity to shadow level
// 'subtle' → level_1, 'pronounced' → level_2, 'none' → no shadow
$shadowValue = null;
if ($shadowIntensity === 'none') {
    $shadowValue = null; // No shadow
} elseif ($shadowIntensity === 'pronounced') {
    // Use level_2 for pronounced shadow
    $shadowValue = $this->shapeTokens['shadow']['level_2'] ?? null;
} else {
    // Default to level_1 for subtle or any other value
    $shadowValue = $this->shapeTokens['shadow']['level_1'] ?? null;
}
```

#### Hover State Shadow (lines 1321-1348)
- **Before**: Used hardcoded shadow values
- **After**: Uses the same `border_shadow_intensity` logic, with enhanced shadows on hover

```php
// Enhanced shadow on hover - use the same shadow intensity logic
$shadowIntensity = $this->widgetStyles['border_shadow_intensity'] ?? 'subtle';

// Get base shadow value using the same mapping as the base state
$baseShadowValue = null;
if ($shadowIntensity === 'none') {
    $baseShadowValue = null;
} elseif ($shadowIntensity === 'pronounced') {
    $baseShadowValue = $this->shapeTokens['shadow']['level_2'] ?? null;
} else {
    $baseShadowValue = $this->shapeTokens['shadow']['level_1'] ?? null;
}

if ($baseShadowValue) {
    // Increase shadow intensity on hover
    if ($shadowIntensity === 'pronounced') {
        $css .= "    box-shadow: 0 8px 24px rgba(15, 23, 42, 0.3) !important;\n";
    } else {
        // For subtle, enhance to level_2 on hover
        $hoverShadowValue = $this->shapeTokens['shadow']['level_2'] ?? '0 8px 24px rgba(15, 23, 42, 0.25)';
        $css .= "    box-shadow: " . h($hoverShadowValue) . " !important;\n";
    }
}
```

## Testing
1. Set border effect to "Shadow" in Edit Theme
2. Set shadow intensity to "Subtle" → should show `level_1` shadow
3. Set shadow intensity to "Pronounced" → should show `level_2` shadow
4. Set shadow intensity to "None" → should show no shadow
5. Verify shadows appear correctly on user pages

## Hard Problem Protocol Applied
This fix followed the Hard Problem Protocol methodology:
1. **Phase 1**: Gathered information about border effect saving and rendering
2. **Phase 2**: Formed hypothesis that `border_shadow_intensity` was being ignored
3. **Phase 3**: Tested hypothesis by examining code flow
4. **Phase 4**: Confirmed root cause - CSS generator not using `border_shadow_intensity`
5. **Phase 5**: Implemented fix to use `border_shadow_intensity` for shadow level selection
6. **Phase 6**: Documented solution

## Related Files
- `classes/ThemeCSSGenerator.php` - CSS generation logic
- `classes/Theme.php` - Theme data retrieval
- `admin-ui/src/components/panels/ThemeEditorPanel.tsx` - Theme editor UI
- `api/themes.php` - Theme save API





