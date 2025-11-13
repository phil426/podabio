# Design Token Architecture

## Objectives
- Deliver a unified token system that powers both the new React admin and the generated mobile link-in-bio pages.
- Provide deep coverage across primitives (color, typography, spacing, shape, motion) and component-level variants (panels, buttons, tiles, drawers).
- Ensure platform parity: tokens must compile to CSS custom properties for the web app while remaining exportable to future native or design-tool integrations.

## Token Taxonomy
```
token
├─ category (core, semantic, component)
│  ├─ group (e.g., color, typography, space, shape, motion, elevation, z-index, state)
│  │  ├─ key (e.g., background.surface, font.family.heading)
│  │  │  └─ state (base, hover, focus, active, disabled, inverse, critical, success)
```

### Core (Primitives)
- **Color**
  - `color.base.*` – raw palette swatches (e.g., slate-900, indigo-500). Stored as hex.
  - `color.alpha.*` – RGBA overlays for scrims, focus rings, shadows.
- **Typography**
  - `type.font.family.*`, `type.scale.*`, `type.weight.*`, `type.line_height.*`.
  - `type.tracking.*` for letter-spacing presets, `type.transform.*` for case transformations.
- **Space**
  - `space.scale.*` uses an 8px modular scale in rem units.
  - `space.gap.*` abstracts spacing for layout slots (stack, cluster, grid).
- **Shape**
  - `shape.radius.*`, `shape.border.width.*`, `shape.border.style.*`.
- **Motion**
  - `motion.duration.*`, `motion.easing.*`, `motion.delay.*`.
- **Elevation**
  - `elevation.shadow.*`, `elevation.blur.*`, `elevation.zindex.*`.

### Semantic (Aliases)
- Map to primitives so designers and developers speak the same language.
  - `surface.page`, `surface.panel`, `surface.canvas`, `surface.drawer`.
  - `text.primary`, `text.secondary`, `text.inverse`, `text.muted`, `text.accent`.
  - `accent.primary`, `accent.secondary`, `accent.tertiary`.
  - `state.success`, `state.warning`, `state.danger`, with corresponding text/border tokens.
  - `density.compact|cozy|comfortable` remap spacing scale multipliers.
  - `motion.interaction.hover`, `motion.interaction.drawer`, `motion.interaction.drag`.

### Component Tokens
- Organize per widget or layout module. Example structure:
  - `component.button.primary.background.base`
  - `component.button.primary.background.hover`
  - `component.panel.canvas.padding.x`
  - `component.panel.canvas.padding.y`
  - `component.drawer.handle.size`
  - `component.layerlist.item.gap`
  - `component.color-picker.swatch.size`
  - `component.drag-indicator.shadow`
- Each component token resolves to semantic tokens to maintain consistency while allowing per-component overrides when necessary.

## Storage Strategy
### Database schema
- Extend `themes` table with JSON columns per token category:
  - `tokens_core`, `tokens_semantic`, `tokens_component`.
- Extend `pages` table with overrides:
  - `tokens_overrides_core`, `tokens_overrides_semantic`, `tokens_overrides_component`.
- Each column stores partial trees. Absent keys inherit from defaults.
- Add `token_version` column to both tables for migration control.

### Default token registry
- Create `config/tokens.php` exporting base token maps.
- Provide a CLI/utility to generate `tokens.json` for external consumption.

### Delivery pipeline
- Back end: `ThemeCSSGenerator` consumes merged tokens (defaults → theme → page overrides) and produces:
  - Flattened CSS variables (`--pod-color-surface-page`, `--pod-space-gap-stack-md`).
  - JSON payload for API clients.
- Front end: new admin fetches `/api/tokens` meta endpoint to bootstrap the design system and feed token-aware components.

## API Contracts
- `/api/themes.php`
  - `GET /themes/:id/tokens` → full token object.
  - `POST /themes/:id/tokens` → validates and persists updates, enforcing WCAG contrast and allowed value ranges.
- `/api/page.php`
  - Accepts token overrides within dedicated payload objects: `{ tokens: { semantic: {...}, component: {...} } }`.
  - Returns merged tokens in responses for immediate preview rendering.
- `/api/tokens.php` (new)
  - Serves canonical scales (color palette, typography scale, spacing scale) so the admin can populate pickers without hardcoding values.

## Validation Rules
- Color contrast: validate `text` vs `surface` combinations (WCAG 2.1 AA minimum). Provide auto-adjust suggestions.
- Token key validation ensures that updates align with the taxonomy, preventing arbitrary keys.
- Enforce value types: hex strings for color, rem numbers for spacing, enumerated strings for motion easing.
- Component tokens must map to existing component definitions registered in `WidgetRegistry` or the admin component catalog.

## Token Operations in the Admin
- **Inspector**: Right-side panel exposes tokens contextually. Selecting a widget loads the relevant component token group.
- **Batch editing**: Provide a token table view with search/filter to encourage semantic editing without overwhelming the user.
- **Version control**: Allow saving snapshots of token sets for rollback (tie into future admin history service).
- **Localization ready**: Keep tokens purely structural; copy-based settings remain outside the token system.

## Migration Plan
1. Map legacy fields (`colors.primary`, `page_primary_font`, `widget_styles`) into the new taxonomy using a migration service (`TokenLegacyMapper`).
2. Introduce read-through access so `editor.php` continues to work: when legacy fields are requested, derive them from tokens until the old UI is retired.
3. Gradually phase in token-based APIs for the new admin; add feature flags to route requests to token-enabled endpoints.

## Governance
- Establish a token review checklist:
  - Clarity: does the token name communicate intent?
  - Efficiency: does it reduce duplication by referencing semantic tokens?
  - Consistency: does it align with naming conventions?
  - Beauty: does it respect visual polish (contrast, rhythm)?
- Document tokens in `docs/theme-token-spec.md` and surface them via automated Storybook or design system site driven from the same JSON.

