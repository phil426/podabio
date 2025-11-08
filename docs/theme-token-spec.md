# Theme Token Specification

This specification defines the expanded theme token set required to deliver accessible, research-backed public page themes. The schema balances flexibility with enforceable contrast, typography, and spacing standards.

## 1. Color Tokens

All colors store as hex strings. Tokens marked *computed* are derived at runtime to enforce contrast.

| Token | Description | Contrast Target |
| --- | --- | --- |
| `color.background.base` | Primary page background. | — |
| `color.background.surface` | Default card/widget surface. | ≥4.5:1 against `text.primary` |
| `color.background.surface-raised` | Elevated surface (drawers, modals). | ≥4.5:1 against `text.primary` |
| `color.background.overlay` | Scrim behind drawers. | Opacity ≤0.6 |
| `color.text.primary` | Primary body text. | ≥4.5:1 against `background.surface` |
| `color.text.secondary` | Muted/caption text. | ≥3:1 against `background.surface` |
| `color.text.inverse` | Text on top of accent/surface-raised backgrounds. | ≥4.5:1 |
| `color.border.default` | Default border for cards. | ≥3:1 against adjacent surface |
| `color.border.focus` | Focus ring color. | ≥3:1 against background |
| `color.accent.primary` | Primary brand accent used for CTAs. | ≥3:1 against `background.surface` |
| `color.accent.muted` | Subtle accent backgrounds (badges, hover). | ≥4.5:1 with `text.primary` or fallback to `text.inverse`* |
| `color.state.success` | Success background. | ≥3:1 with `text.state` tokens |
| `color.state.warning` | Warning background. | ≥3:1 with `text.state` tokens |
| `color.state.danger` | Error background. | ≥4.5:1 with `text.state.danger` |
| `color.text.state.success` | Text/icon on success backgrounds. | ≥4.5:1 |
| `color.text.state.warning` | Text/icon on warning backgrounds. | ≥4.5:1 |
| `color.text.state.danger` | Text/icon on danger backgrounds. | ≥4.5:1 |
| `color.shadow.ambient` | RGBA for soft shadows. | 12–24% opacity |
| `color.shadow.focus` | High-contrast focus shadow. | ≤20% opacity accent |

### Derived Contrast Helpers
- `color.text.on-background`*, `color.text.on-surface`*, `color.text.on-accent`*, `color.text.on-image`* calculate highest-contrast text color (usually black/white variants) using WCAG formula.

## 2. Typography Tokens

Define typography on a 1.125 modular scale (supporting accessibility guidelines for visual hierarchy).

| Token | Value (rem) | Use |
| --- | --- | --- |
| `type.font.family.heading` | configurable (default `'Inter'`) |
| `type.font.family.body` | configurable |
| `type.font.family.metatext` | inherits body or optional secondary |
| `type.scale.xl` | 2.488 | Page title |
| `type.scale.lg` | 1.777 | Section heading |
| `type.scale.md` | 1.333 | Widget title |
| `type.scale.sm` | 1.111 | Body text |
| `type.scale.xs` | 0.889 | Captions, metadata |
| `type.line-height.tight` | 1.2 |
| `type.line-height.normal` | 1.5 |
| `type.line-height.relaxed` | 1.7 |
| `type.weight.normal` | 400 |
| `type.weight.medium` | 500 |
| `type.weight.bold` | 600 |

Typography defaults pair with spacing tokens (below) to achieve 1.5–1.8 ratio between font size and line height for legibility.

## 3. Spacing Tokens

Spacing follows an 8px base scale. Layout presets `compact` and `comfortable` remap density-critical tokens.

| Token | Base Size | Compact Multiplier | Comfortable Multiplier |
| --- | --- | --- | --- |
| `space.2xs` | 0.25rem | ×0.75 | ×1 |
| `space.xs` | 0.5rem | ×0.85 | ×1 |
| `space.sm` | 0.75rem | ×0.9 | ×1.1 |
| `space.md` | 1rem | ×1 | ×1.25 |
| `space.lg` | 1.5rem | ×1 | ×1.3 |
| `space.xl` | 2rem | ×1 | ×1.35 |
| `space.2xl` | 3rem | ×1 | ×1.4 |

### Layout Application Guide
- Container padding uses `space.md` (compact) or `space.lg` (comfortable).
- Widget gaps use `space.sm`/`space.md` depending on density.
- Vertical rhythm between sections uses multiples of the base scale to maintain consistent flow.

## 4. Radius, Border, and Shadow Tokens

| Token | Value |
| --- | --- |
| `shape.corner.none` | 0 |
| `shape.corner.sm` | 0.375rem |
| `shape.corner.md` | 0.75rem |
| `shape.corner.lg` | 1.5rem |
| `shape.corner.pill` | 9999px |
| `border.width.hairline` | 1px |
| `border.width.regular` | 2px |
| `border.width.bold` | 4px |
| `shadow.level.1` | `0 2px 6px rgba(0,0,0,0.1)` |
| `shadow.level.2` | `0 6px 16px rgba(0,0,0,0.16)` |
| `shadow.level.focus` | `0 0 0 4px rgba(accent,0.3)` (computed) |

## 5. Interaction Tokens

| Token | Description |
| --- | --- |
| `motion.duration.fast` (150ms) | Hover & tap feedback |
| `motion.duration.standard` (250ms) | Drawer open/close |
| `motion.easing.standard` (`cubic-bezier(0.4,0,0.2,1)`) |
| `motion.easing.decelerate` (`cubic-bezier(0.0,0,0.2,1)`) |
| `focus.ring.width` (3px) |
| `focus.ring.offset` (2px) |

## 6. Token Storage Schema

1. **Database**: add JSON columns `color_tokens`, `typography_tokens`, `spacing_tokens`, `shape_tokens`, `motion_tokens` to `themes` table. Each column stores overrides; absent tokens fall back to defaults.
2. **Defaults**: store canonical defaults in `config/constants.php` and expose via `Theme` class fallback methods.
3. **Computed Values**: `ThemeCSSGenerator` derives contrast helpers and density multipliers at runtime to keep database values minimal.

## 7. Accessibility Enforcement

- When the user saves a theme, validate all color pairings using WCAG 2.1 contrast ratios. If a token fails, auto-adjust via closest accessible shade or reject with actionable error.
- Focus and hover states must maintain ≥3:1 contrast with both background and adjacent elements.
- In compact mode, ensure interactive targets remain ≥44px tall by pairing spacing tokens with minimum height rules.

## 8. Migration Guidance

- Legacy tokens (`primary`, `secondary`, `accent`, widget spacing enums) map into the new schema via defaults.
- Widgets with inline error colors adopt `color.state.danger` and corresponding text tokens.
- Page title effects should be audited; keep only accessible variants and rework to reference `color`/`type` tokens or flag for deprecation.


