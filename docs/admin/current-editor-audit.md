# Current Editor Audit

## Overview
- `editor.php` renders the entire authoring experience as a single PHP script that outputs the HTML, CSS, and JavaScript for the dashboard. All data loading happens server-side before the HTML response is sent; subsequent user actions rely on inline `fetch` calls to the PHP API endpoints.
- Authentication and authorization are enforced via `includes/session.php` and `includes/auth.php`. Pages redirect unauthenticated users before any editor UI renders.
- The layout is a bespoke single-page view: fixed top navbar, a two-column main area with scrollable sections, and numerous modal-like overlays built directly in the DOM (e.g., Croppie image uploader). There is no component abstraction—DOM nodes are manipulated with vanilla JS utilities embedded in the file.

## Server-Side Data Flow
- Page bootstrap loads the primary entities the editor depends on:
  - `Page::getByUserId($userId)` supplies page metadata, linked social directories, widget collections, RSS configuration, and theme references.
  - `Page::getAllLinks($pageId)` and `Page::getSocialIcons($pageId)` populate legacy link and platform lists that still drive parts of the UI.
  - `Theme::getAllThemes(true)` merges system and user-created themes for theme selection.
  - `WidgetStyleManager::getDefaults()` seeds widget styling when pages lack overrides.
- PHP helpers translate database JSON (`colors`, `fonts`, `widget_styles`) into associative arrays before embedding them as inline JSON in the page markup. This is where tokenization currently happens (limited to primary/secondary/accent colors and typography pairs).
- The page URL and CSRF token are computed server-side and injected into the DOM for reuse by client code.

## API Surface Consumed by the Editor
- `/api/page.php`
  - `update_settings`: updates username, podcast metadata, RSS feed, and custom domain. Validates uniqueness for usernames/domains, parses RSS feeds via `RSSParser`, and normalizes JSON columns.
  - `update_appearance`: central endpoint for theme overrides. Accepts `theme_id`, `layout_option`, `colors`, `fonts`, `widget_styles`, spatial and page name effects. Uses `WidgetStyleManager::sanitize` and `Page::update`.
  - `update_email_settings`: manages ESP configuration fields on the `pages` table.
  - Social directory actions (`add_directory`, `update_directory`, `update_social_icon_visibility`, `delete_directory`, `reorder_social_icons`) operate on `social_icons`.
  - Additional actions handle analytics and verification flows (domain verification, etc.).
- `/api/widgets.php`
  - CRUD plus reorder for widgets, with schema derived from `WidgetRegistry`. Config data is stored as JSON per widget and sanitized server-side.
  - `get_available` returns the widget catalog, currently used to render selection modals.
- `/api/analytics.php`
  - Aggregates chart data for the analytics panel (period parameterized requests).
- `/api/themes.php`
  - Provides theme lookup and user theme persistence (currently used for theme gallery dialogs).
- `/api/upload.php`, `/api/podcast-image-proxy.php`, `/api/rss-proxy.php` handle asset uploads and remote feed parsing for podcasts.

All endpoints expect `POST` with a CSRF token; responses follow a simple `{ success: bool, ... }` signature supplied by `APIResponse`.

## Front-End Interaction Patterns
- Global inline scripts define dozens of DOM-manipulation helpers. Examples include `initWidgetDragDrop` (native drag-and-drop reordering), `openPanel` for tab navigation, and dedicated handlers for each modal.
- State is maintained in-memory via large objects (e.g., `window.widgetLibrary`, `window.availableWidgets`), with no centralized store.
- Validation logic is duplicated client-side and server-side. For instance, social URL validation happens both in JS before fetch and in `/api/page.php`.
- Progressive disclosure is approximated via manual CSS toggles (`classList.add('hidden')`) rather than reusable components.
- Tokens are injected as CSS custom properties in `<style>` blocks but are not abstracted into a reusable design system; each feature area writes its own CSS using the same variables.

## Theme & Token Usage Snapshot
- Theme tokens currently live in:
  - `themes.colors` JSON (canonical `primary`, `secondary`, `accent`).
  - `pages.colors`, `pages.fonts`, and the `page_primary_font` / `page_secondary_font` columns.
  - `pages.widget_styles` JSON, cleaned with `WidgetStyleManager`.
  - `ThemeCSSGenerator` computes CSS variables per theme but still works with ad-hoc token names.
- Documentation in `docs/theme-token-spec.md` outlines a richer token model (color, typography, spacing, motion), but the bootstrap code does not yet persist those structures. The new admin must bridge from the current three-token set to the full taxonomy.

## Pain Points Identified
- Monolithic PHP + inline JS architecture limits maintainability, reusability, and modern accessibility requirements.
- Token handling is inconsistent: some values use CSS variables (`var(--primary-color)`), others store raw hex strings. There is no server-side enforcement of the expanded token spec (contrast ratios, density variants).
- API endpoints are tightly coupled to form field naming conventions from `editor.php`, complicating migration to a componentized front end without an abstraction layer.
- Progressive disclosure, contextual help, and accessibility support are manual and inconsistent across sections.
- Running the legacy editor alongside the new React admin will require feature flags at the routing layer and careful API versioning because current endpoints assume legacy payload shapes.

## Opportunities for the New Admin
- Introduce an API abstraction layer (REST or GraphQL) that exposes page, theme, widget, token, and analytics resources with normalized schemas to decouple from the PHP form payloads.
- Formalize design tokens as first-class entities: persist token groups (`color`, `type`, `space`, `motion`) separately in the database, expose them via `/api/themes` and `/api/page` endpoints, and generate CSS variables through a shared build step.
- Replace bespoke JS utilities with React components backed by an application state manager (e.g., Redux Toolkit, Zustand) to support the three-panel layout, drag-and-drop, and contextual drawers.
- Leverage the audited endpoints and classes (Page, Theme, WidgetRegistry, WidgetStyleManager) to guide migration strategy—either by extending them with token-aware methods or by creating new service classes consumed by both the legacy and modern interfaces during the transition.

