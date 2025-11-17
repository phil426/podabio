# Widget Shape Debugging Guide

## Problem
Block widget shape (border-radius) is not working despite multiple fix attempts.

## Systematic Debugging Approach

An experienced developer would tackle this by:

1. **Tracing the complete data flow** from UI → Save → Database → Load → CSS → Render
2. **Adding comprehensive logging** at every transformation point
3. **Verifying each step independently** to find where the data is lost or corrupted
4. **Checking for conflicts** (CSS specificity, merge logic, defaults overriding)

## Data Flow

```
UI (ThemeEditorPanel.tsx)
  ↓ buttonRadius2 = 'square' | 'rounded' | 'pill'
  ↓ Maps to cornerMap: { square: 'none', rounded: 'md', pill: 'pill' }
  ↓ cleanCorner = { [cornerKey]: cornerValue }
  ↓ shapeTokens = { corner: cleanCorner }
  ↓
API (api/themes.php)
  ↓ Receives shape_tokens in theme_data JSON
  ↓ Passes to Theme::updateUserTheme()
  ↓
Database (themes table)
  ↓ shape_tokens column (JSON)
  ↓
Theme Class (Theme.php)
  ↓ getTheme() loads from database
  ↓ getShapeTokens() merges: defaults + themeTokens + pageTokens
  ↓ mergeTokens() recursively merges arrays
  ↓
CSS Generator (ThemeCSSGenerator.php)
  ↓ Reads shapeTokens from tokens['shape']
  ↓ Resolves border-radius from shapeTokens.corner
  ↓ Applies to .widget-item { border-radius: ... }
  ↓
Rendered Page
  ↓ CSS is applied to .widget-item elements
```

## Potential Issues

### 1. Merge Logic Problem
`getShapeTokens()` merges defaults (which has ALL corner values) with theme tokens (which should have ONLY ONE). The merge might be keeping all default values, causing the CSS generator to pick the wrong one.

**Fix**: Ensure theme tokens completely replace defaults, not merge with them.

### 2. Key Mismatch
Theme saves `corner: { md: '0.75rem' }` but CSS generator might be looking for a different key structure.

**Fix**: Verify the keys match exactly between save and read.

### 3. Page Tokens Override
If the page has `shape_tokens`, it will override theme tokens in the merge.

**Fix**: Check if page has shape_tokens that might be overriding.

### 4. CSS Specificity
Other CSS rules might be overriding `.widget-item` border-radius.

**Fix**: Check browser DevTools to see what CSS is actually applied.

## Debugging Steps

### Step 1: Check Browser Console
1. Open browser DevTools → Console
2. Save theme with widget shape change
3. Look for `WIDGET SHAPE SAVE DEBUG` log
4. Verify `buttonRadius2`, `cornerKey`, `cornerValue`, `cleanCorner` values

### Step 2: Check PHP Error Log
1. Save theme
2. Check PHP error log for:
   - `THEME API UPDATE: shape_tokens=`
   - `THEME UPDATE DEBUG: Saving shape_tokens=`
   - `THEME getShapeTokens DEBUG:`
   - `THEME CSS GENERATOR CONSTRUCTOR: shapeTokens.corner=`
   - `THEME CSS GENERATOR: Applying border-radius to .widget-item:`

### Step 3: Run Debug Script
```bash
php debug-widget-shape.php [theme_id]
```

This will show:
- Database values
- Theme class values
- CSS generator values
- Generated CSS

### Step 4: Check Browser DevTools
1. Inspect a `.widget-item` element
2. Check Computed styles for `border-radius`
3. See which CSS rule is applying it
4. Check if `--button-corner-radius` variable is set

### Step 5: Verify Database
```sql
SELECT id, name, shape_tokens FROM themes WHERE id = [theme_id];
```

Check if `shape_tokens` JSON has the correct `corner` structure.

## Expected Values

- **Square**: `corner: { none: '0px' }`
- **Rounded**: `corner: { md: '0.75rem' }`
- **Pill**: `corner: { pill: '9999px' }`

## Next Steps

1. Run the debug script to see where the data is lost
2. Check the logs to see what's being saved vs. what's being read
3. Verify the merge logic isn't keeping unwanted default values
4. Check if page has shape_tokens that override theme

