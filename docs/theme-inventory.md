# Themeable Element Inventory

This inventory captures all public-facing styling surfaces in `page.php`, associated widgets, and supporting helpers so we can transition them to a comprehensive theme token system for accessibility.

## Theme Tokens (Current State)
- `ThemeCSSGenerator::generateCSSVariables()` defines color, typography, background, and widget spacing variables but omits many state colors, semantic roles, and spacing tiers beyond widgets.

```170:221:classes/ThemeCSSGenerator.php
        $css .= "    --page-background: " . h($this->pageBackground) . ";\n";
        $css .= "    --widget-background: " . h($this->widgetBackground) . ";\n";
        $css .= "    --widget-border-width: {$borderWidth};\n";
        $css .= "    --widget-border-color: " . h($this->widgetBorderColor) . ";\n";
        $css .= "    --widget-spacing: {$spacing};\n";
        $css .= "    --widget-border-radius: {$borderRadius};\n";
```

- Widget spacing enums map to `tight/comfortable/spacious`; there is no global spacing scale.

```145:154:includes/theme-helpers.php
        'spacing' => [
            'tight' => '0.5rem',
            'comfortable' => '1rem',
            'spacious' => '1.5rem'
        ],
```

## Styled Element Inventory

### Global Layout & Body
- `html`/`body` pull page background and fonts from tokens, but layout spacing (`min-height`, margins, padding) and default text color remain hard-coded.

```319:337:page.php
        $css .= "body {\n";
        $css .= "    font-family: var(--page-secondary-font), var(--body-font), sans-serif;\n";
        $css .= "    background: var(--page-background);\n";
        $css .= "    min-height: 100vh;\n";
        $css .= "    color: var(--text-color);\n";
        $css .= "    margin: 0;\n";
        $css .= "    padding: 0;\n";
        $css .= "}\n";
```

### Page Container & Header
- `.page-container`, `.profile-header`, and layout padding/margins are fixed widths (`max-width: 600px`, `padding: 2rem 1rem`) without theme overrides.
- Vertical rhythm between sections (`margin-bottom: 2rem` etc.) is static, preventing compact/comfortable spacing swaps.

```114:152:page.php
        .page-container {
            max-width: 600px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }
        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
        }
```

### Profile & Identity Elements
- `.profile-image`, `.cover-image`, `.page-title`, `.page-description` mix theme colors with hard-coded sizes and spacing; border radius and image dimensions are fixed.
- Social icon stack uses theme-driven colors but lacks hover contrast logic for accessibility.

```121:151:page.php
        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin-top: 36px;
            margin-bottom: 1rem;
            border: 3px solid var(--primary-color);
        }
```

### Widget Shell & Gaps
- `.widgets-container` relies on `--widget-spacing`, yet outer padding, alignment, and breakpoints are fixed.
- `.widget-item` integrates some theme tokens (background, border, glow/shadow) but typography sizes, padding, and interactive states are hard-coded.

```345:411:page.php
        .widget-item {
            background: var(--widget-background);
            border: var(--widget-border-width) solid var(--widget-border-color);
            border-radius: var(--widget-border-radius);
            position: relative;
            /* padding, typography, hover transforms fixed */
        }
```

- Widget thumbnails/icons introduce custom wrappers with static sizing, shadows, and spacing that ignore theme tokens.

### Widget Variants & Legacy Widgets
- Renderer outputs HTML without consistent wrapper classes for stateful styling (e.g., `.widget-description`, `.widget-note`) leading to inline styles or hard-coded colors such as error red `#dc3545`.

```259:351:classes/WidgetRenderer.php
                return '<div class="widget-item widget-podcast-custom"><div class="widget-content"><div class="widget-note" style="color: #dc3545;">RSS Feed URL is required</div></div></div>';
```

- Additional widget types (text, image, video) introduce embedded `<iframe>` dimensions and layout blocks without theme-aware tokens.

### Podcast Banner & Drawer
- Fixed gradient background and shadow values (`linear-gradient(135deg, var(--primary-color) 0%, rgba(0, 102, 255, 0.95))`) combine theme colors with hard-coded overlays, lacking contrast checks.
- Drawer layout (full-screen fixed positioning, padding) uses absolute pixel values and black backgrounds without tokens.

```155:211:page.php
        .podcast-top-banner {
            background: linear-gradient(135deg, var(--primary-color, #0066ff) 0%, rgba(0, 102, 255, 0.95) 100%);
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.15);
        }
        .podcast-top-drawer {
            background-color: #000000;
            transform: translateY(-100%);
        }
```

### Podcast Player (Widget & Drawer)
- Compact player and drawer controls embed numerous hard-coded colors (`#fff`, `rgba(0,0,0,0.6)`, etc.) and spacing values; button shapes violate “theme dictates as much as possible.”
- Progress bars, tabs, and follow button groups do not leverage tokens for states/spacing.

### Page Title Effects
- All “page-title-effect-*” classes define unique typography, colors, shadows, and animations independent of theme tokens, reducing accessibility control and contrast guarantees.
- Many effects rely on neon/glitch styling with unbounded color choices and text shadows.

```406:788:page.php
        .page-title-effect-sweet-title .sweet-title {
            color: #fde9ff;
            font-weight: 900;
            text-shadow: 3px 1px 1px #4af7ff, ...;
        }
```

### Utility & Animation Blocks
- Drawer interactions, button hover transforms, and glow animations use pixel-based paddings, durations, and opacity values without theme references.
- No spacing utilities exist for global layout adjustments—only widget spacing is tokenized.

## Non-theme Style Sources
- Inline styles in widget renderer responses (errors, placeholder states) use direct color values.
- External CSS (`css/podcast-player.css`, `css/podcast-player-controls.css`) define additional colors and dimensions separate from the theme system; they need audit in later steps.
- Social icons and legacy link markup rely on Font Awesome with hover colors hard-coded to `var(--accent-color)` but no fallback for insufficient contrast.

## Gaps & Opportunities
- **Color roles**: need semantic tokens (background, surface, text-muted, stroke, focus, success/error, overlay) with enforced contrast ratios.
- **Spacing scale**: establish global rhythm tokens (e.g., `space-xs` … `space-xl`) and map compact vs comfortable profiles.
- **Typography**: define scale, weight, and letter-spacing tokens for headings, body, captions, and widget meta text instead of per-class font-size.
- **Component hooks**: ensure renderer outputs semantic class names and data attributes for states to consume theme tokens instead of inline hex values.
- **Effects**: evaluate which page title effects remain or convert to theme-driven presets with accessible color selections.


