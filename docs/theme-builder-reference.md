# Theme Builder Reference

This document consolidates everything required to design, register, and QA a public-facing theme. Use it as the definitive checklist when creating or updating themes so that every page renders consistently, accessibly, and in line with product rules.

---

## 1. Theme Data Model

The public theme system stores configuration in the `themes` table. Every installer or migration should populate these columns:

| Column | Type | Purpose |
| --- | --- | --- |
| `name` | `VARCHAR(100)` | Human-readable theme name displayed in editors. |
| `colors` | `JSON` | Legacy primary/secondary/accent fallback. Still required for compatibility with old pages. |
| `fonts` | `JSON` | Legacy heading/body fallback. |
| `page_background` | `TEXT` | Optional gradient or color for the page shell. |
| `widget_styles` | `JSON` | Enum-style settings (`border_width`, `spacing`, `shape`, etc.) for quick tweaks. |
| `spatial_effect` | `VARCHAR` | Matches CSS spatial effect classes (e.g. `none`, `glass`, `tilt`). |
| `widget_background` | `TEXT` | Surface color/gradient for widgets. |
| `widget_border_color` | `TEXT` | Default widget border color. |
| `widget_primary_font`, `widget_secondary_font` | `VARCHAR` | Widget typography fallbacks. |
| `page_primary_font`, `page_secondary_font` | `VARCHAR` | Page typography fallbacks. |
| **Token Columns** | `JSON` | Rich overrides, one payload per token family: `color_tokens`, `typography_tokens`, `spacing_tokens`, `shape_tokens`, `motion_tokens`. |
| `layout_density` | `VARCHAR(32)` | Theme-wide default (`compact` or `comfortable`). |

All production environments must include the token columns. Run `database/migrate_add_theme_tokens.php` before installing tokenized themes.

### 1.1 Page Overrides

Individual pages may also store `colors`, `fonts`, or token JSON. During rendering `ThemeCSSGenerator` merges: defaults → theme JSON → page overrides. Theme values must therefore supply sensible defaults for any token you rely on.

---

## 2. Token Families & Variable Map

Themes should populate the following semantic buckets. Defaults live in `Theme::getDefault*Tokens()`; Aurora Skies in `database/add_theme_aurora.php` demonstrates custom overrides.

### 2.1 Colors (`color_tokens`)

| Token | Description | Consumed CSS Variables |
| --- | --- | --- |
| `background.base/surface/surface_raised/overlay` | Page shell tiers. | `--color-background-base`, `--page-background`, etc. |
| `text.primary/secondary/inverse` | Main, supporting, inverse text. | `--color-text-primary`, `--color-text-secondary`, `--color-text-inverse`. |
| `border.default/focus` | Standard and focus ring borders. | `--color-border-default`, `--color-border-focus`. |
| `accent.primary/muted` | Accent palette for links/actions. | `--color-accent-primary`, `--color-accent-muted`. |
| `state.success/warning/danger` & `text_state.*` | Status context colors. | `--color-state-*`, `--color-text-state-*`. |
| `shadow.ambient/focus` | Box-shadow presets. | `--color-shadow-ambient`, `--color-shadow-focus`. |
| `gradient.page/accent/widget/podcast` | High-level gradients for backgrounds. | `--gradient-page`, `--gradient-accent`, etc. |
| `glow.primary` | Glow layer accents. | `--aurora-glow-color`. |

`ThemeCSSGenerator` automatically computes `--page-title-color`, `--page-description-color`, and `--color-text-on-*` variables by testing contrast against the background tokens. Supply base colors with enough contrast; the generator only adjusts as a fallback.

### 2.2 Typography (`typography_tokens`)

- `font.heading/body/metatext` → `--font-family-heading`, `--font-family-body`, `--font-family-meta`.
- `scale.{xl,lg,md,sm,xs}` (rem values) → modular scale variables.
- `line_height.{tight,normal,relaxed}` → `--type-line-height-*`.
- `weight.{normal,medium,bold}` → `--type-weight-*`.

### 2.3 Spacing (`spacing_tokens`)

- `base_scale.{2xs…2xl}` base rem values.
- `density_multipliers.compact/comfortable` adjust each token for density modes.
- `density` sets default mode; `ThemeCSSGenerator` derives `--space-*` and `--layout-density`.

### 2.4 Shape (`shape_tokens`)

Provide `corner.{none,sm,md,lg,pill}`, optional `border_width.{hairline,regular,bold}`, and `shadow.level_{1,2}`/`focus`. The generator exposes `--shape-corner-*`, `--border-width-*`, `--shadow-*`.

### 2.5 Motion (`motion_tokens`)

Use `duration.fast/standard`, `easing.standard/decelerate`, and `focus.{ring_width,ring_offset}` to control animation and focus rings. Emitted as `--motion-duration-*`, `--motion-easing-*`, `--focus-ring-*`.

---

## 3. Front-End Mapping

| UI Element | Primary Variables | Notes |
| --- | --- | --- |
| `body`, `html` | `--page-background`, `--shell-background`, `--color-text-on-background` | Prime Rule: page always renders as mobile shell with subtle backdrop. |
| `.page-container` | `--space-*`, `--layout-density`, `--shape-corner-lg` (theme-specific) | No floating containers per Theme Rule 1. |
| `.page-title` | `--page-title-color`, `--font-family-heading`, gradient tokens for effects. |
| `.page-description` | `--page-description-color` (derived from `text.secondary` and contrast checks). |
| Widgets (`.widget-item`, etc.) | `--widget-background`, `--color-text-on-surface`, `--widget-box-shadow`, spacing tokens. |
| Social Icons | `--color-accent-primary`, hover uses `--aurora-glow-color` when available. |
| Podcast Banner & Drawer | `--gradient-podcast`, `--color-text-on-accent`, ensures dark-mode drawer per Theme Rules 2–4. |
| Compact Player | Accent gradient tokens, `--color-text-on-accent`, motion tokens for animations. |

Whenever you add a new component, choose which existing semantic tier it belongs to. If none fit, extend the token set rather than hard-coding colors or sizes.

---

## 4. Theming Rules & Policies

1. **Prime Rule:** Public pages always render the mobile layout inside the framed shell; desktop/tablet sees the same mobile width centered on a subtle contrasting background.
2. **Theme Rules (Aurora baseline):**
   - No floating containers; page content sits flush inside the mobile card.
   - Podcast drawer, compact player, and toggles count as theme content and must be styled accordingly.
   - Podcast drawer always runs in dark mode for readability.
3. **Accessibility:** Maintain ≥4.5 : 1 contrast for normal text (≥3 : 1 for large). Rely on generated `--color-text-on-*` variables or validate custom mixes.
4. **Spacing:** Two density modes (`compact`, `comfortable`). All vertical rhythm derives from spacing tokens so switching density is automatic.
5. **Focus & Keyboard:** Use `--focus-ring-*` tokens. Never remove focus outlines.
6. **Rounded Corners:** Buttons and interactive elements should respect `--shape-corner-*` ensuring rounded corners unless the design spec explicitly calls for an exception.
7. **No Modals:** Use drawers to respect the product rule (“Do not use modal boxes. Use drawer sliders.”).

---

## 5. Theme Creation Workflow

1. **Plan & Tokens**
   - Define palette & typography in design tool.
   - Map colors into token families, verify contrast on the intended backgrounds.
   - Decide density default and corner/shadow style.

2. **Author Installer Script**
   - Duplicate `database/add_theme_aurora.php` as a template.
   - Populate `colors` / `fonts` for legacy fallback.
   - Fill `color_tokens`, `typography_tokens`, `spacing_tokens`, `shape_tokens`, `motion_tokens` arrays.
   - Set `widget_styles` for quick-tune options.
   - Use `BEGIN/COMMIT` transactions to ensure consistency.
   - Script must be idempotent (skip insert if theme already exists) and should log success/errors.

3. **Run Database Migration (if needed)**
   - Execute `php database/migrate_add_theme_tokens.php` once per environment to guarantee JSON columns exist.
   - Then run your installer (`php database/add_theme_<theme>.php`).

4. **Front-End Styling**
   - Add theme-specific CSS blocks inside `page.php` targeting `.theme-{slug}` bodies. Keep overrides token-driven (`color-mix` with theme variables). Avoid hard-coded hex values.
   - Respect Theme Rules (e.g., Aurora’s drawer styling). Document any new rules in this file.

5. **QA Checklist**
   - **Contrast:** Use browser DevTools/Lighthouse to verify text and control contrast (desktop + mobile). Pay special attention to page description, widget text, and podcast controls.
   - **Density:** Toggle compact vs. comfortable (via editor) and confirm spacing updates cleanly.
   - **Responsiveness:** Test at 390×844 (baseline), 768px, and 1280px – page should remain the mobile shell.
   - **Focus:** Tab through all interactive elements; focus rings must remain visible.
   - **Drawer & Player:** Open/close drawers and compact player; confirm dark-mode and glow styles look correct.
   - **Social Icons:** Hover states stay within rounded boundaries, no clipping.

6. **Documentation**
   - Update `docs/theme-guidelines.md` with any new tokens or styling patterns.
   - Record installation instructions and QA notes (similar to the Aurora section) so the team can reproduce the setup.

---

## 6. Adding New Theme Capabilities

1. Update `Theme::getDefault*Tokens()` with the new token (plus defaults).
2. Extend `ThemeCSSGenerator::generateCSSVariables()` to emit the variable.
3. Ensure `Theme::mergeTokens()` handles your structure (arrays should be associative, not numeric).
4. Add database migration if additional columns are required.
5. Document usage and accessibility expectations here.
6. Write or modify installer scripts to set the new token.

---

## 7. Troubleshooting

| Symptom | Likely Cause | Fix |
| --- | --- | --- |
| Page uses gray text instead of theme color | `color_tokens` missing or columns absent. | Run migration script, reinstall theme. |
| Widgets ignore spacing tokens | `spacing_tokens` payload missing `density` or `base_scale`. | Verify JSON structure in installer. |
| Focus rings disappear | Custom CSS overriding outline without using `--focus-ring-*`. | Remove override or restyle using token variables. |
| Desktop shows wide layout | Prime rule CSS missing on page, or theme-specific CSS sets width > mobile. | Review `.page-container` and shell styles. |

---

By following this reference, every theme will ship with consistent data, accessible styling, and comprehensive documentation. Update this guide whenever the theme system gains new capabilities or rules.
