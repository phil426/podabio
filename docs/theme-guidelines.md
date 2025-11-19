# Public Theme System Guidelines

This guide summarizes the accessibility and UX rules that now govern the public theme system. Use it whenever you create or adjust theme-driven UI on `page.php`.

## Core Token Families

- **Colors:** Semantic roles (base/surface/surface-raised/overlay) plus text, border, accent, and state palettes now ship as CSS variables (e.g., `--color-background-base`, `--color-text-primary`, `--color-accent-primary`, `--color-state-danger`). Theme overrides are validated and merged in `Theme::getColorTokens()`.
- **Typography:** Modular scale tokens (`--type-scale-xl` … `--type-scale-xs`), line heights, and font families map to headings, body, and meta text. Defaults use Inter but can be overridden per theme.
- **Spacing:** Density-aware scale (`--space-2xs` … `--space-2xl`) resolves to either compact or comfortable measurements via the spacing token set.
- **Shape & Shadow:** Corner radii (`--shape-corner-*`), border widths, and shadow levels provide consistent rounding and depth across widgets and drawers.
- **Motion & Focus:** Transition durations/easing, plus focus ring width/offset/color ensure keyboard visibility matches WCAG recommendations.

## Accessibility Expectations

1. **Contrast:**
   - Regular text must achieve ≥4.5:1, large text ≥3:1. Use the derived `--color-text-on-*` variables (`background`, `surface`, `accent`) that already run luminance checks.
   - Interactive surfaces (buttons, toggles, progress fills) should use `--color-accent-primary` with `--color-text-on-accent` or a verified alternative.
   - Status/error states rely on `--color-state-*` + corresponding text tokens; never hard-code red/green.
2. **Focus & Hover:**
   - Always expose `:focus-visible` using `--focus-ring-width`, `--focus-ring-offset`, and `--focus-ring-color`.
   - Hover elevation should reuse `--shadow-level-1`/`--shadow-level-2` rather than bespoke box-shadow values.
3. **Typography & Readability:**
   - Map components to the scale: headlines (`--type-scale-xl/lg`), widget titles (`--type-scale-md`), body copy (`--type-scale-sm`), captions (`--type-scale-xs`).
   - Maintain line height tokens (`--type-line-height-*`) to preserve 1.5–1.8 line-height ratios.
4. **Spacing Rhythm:**
   - Container padding: comfortable ⇒ `--space-lg`, compact ⇒ `--space-md`.
   - Vertical gaps between major sections: `--space-lg` or multiples for comfortable mode; compact uses `--space-md`.
   - Widget stacks leverage `--widget-spacing` (now pointing at the spacing scale) so density switches automatically.

## Component Mapping

- **Global Layout:** `body`/`html` consume `--color-background-base`; `page-container` uses spacing tokens for padding and the type scale for titles/description.
- **Profile Header:** Border shapes use `--shape-corner-pill`; borders draw from `--color-border-default`. Social icons now inherit `--color-accent-primary`.
- **Widgets:**
  - Surfaces reference `--widget-background`, `--color-text-on-surface`, and `--widget-box-shadow`.
  - Typography uses widget font tokens, while icon/thumbnail slots respect shape/spacing variables.
  - Hover/active states reapply spacing and shadow tokens instead of hard-coded transforms.
- **Podcast Controls:** Buttons, sliders, and compact player elements adopt accent tokens for fills and text; progress bars rely on `--color-accent-primary` plus spacing tokens.
- **Drawers & Overlays:** Backgrounds use surface-raised tokens, edges rely on accent borders, and close buttons adopt focus/hover motion tokens.
- **Page Title Effects:** Each effect is now themed via accent/text tokens. If you introduce a new variant, express colors with `color-mix` + theme variables and keep contrast ≥4.5:1.

## Working With Tokens

1. Prefer CSS variables from `:root` when styling components; fall back to defaults only if a token is unavailable.
2. When introducing a new UI element, decide which semantic role it plays (surface vs. overlay) and pick the matching background and text tokens.
3. For new spacing needs, extend the spacing token set in `Theme::getSpacingTokens()` rather than adding raw values.
4. If a component needs additional semantic colors (e.g., warning badge), add a derived token to the color token set and document its contrast requirement here.
5. To expose new tokens to themes, update:
   - `Theme::getDefault*Tokens()` for defaults and validation.
   - `ThemeCSSGenerator::generateCSSVariables()` to emit CSS.
   - Documentation in this file after implementation.

## Theming Workflow Checklist

1. **Design Reference:** Start from these tokens—avoid authoring new hex/RGB values.
2. **Contrast Verification:** Run automated tools (axe, Lighthouse, or the built-in browser contrast checker) against the generated page for both comfortable and compact density.
3. **Density Audit:** Toggle compact/comfortable to confirm spacing scales update consistently and touch targets remain ≥44px.
4. **Keyboard/Screen Reader Test:** Ensure focus rings appear, skip controls accessible, and drawer overlays trap focus.
5. **Performance:** All token-driven transitions should use the defined motion tokens to keep animation durations consistent and accessible.

## Extending The Token System

If a new component demands additional visual primitives:

1. Extend the relevant default token method in `Theme.php`.
2. Keep token names semantic (`color.badge.info`) rather than presentation-based (`color.blue.medium`).
3. Document contrast expectations and intended usage here.
4. Update API/storage so theme authors can override the new tokens.

Following these practices keeps theming centralized, accessible, and maintainable as the design system evolves. Always review this guideline before merging style changes to ensure new code aligns with the accessible token architecture.

## Theme Rules & Policies

1. **Prime Rule:** Public pages always render the mobile layout inside the framed shell; desktop/tablet sees the same mobile width centered on a subtle contrasting background.
2. **Theme Rules:**
   - No floating containers; page content sits flush inside the mobile card.
   - Podcast drawer, compact player, and toggles count as theme content and must be styled accordingly.
   - Podcast drawer always runs in dark mode for readability.
   - Tap-to-listen/podcast banner toggles must **avoid pill shapes**—use theme corner tokens (e.g. `--shape-corner-md`) so the control aligns with other buttons. Contrast must be ≥4.5:1 against the banner and text should default to `#FFFFFF` or a verified `color-mix` with a white majority, paired with a subtle text shadow for clarity against gradients.
   - When theming the podcast banner ensure the toggle sits flush against the player (no overlap). Keep banner padding minimal, and if you add glow effects (`::before` layers) confirm they do not obscure the toggle text.

