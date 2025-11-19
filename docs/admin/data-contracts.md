# Admin Data Contracts Snapshot

_Last updated: 2025-11-10_

## Feature Flags & Token Storage
- `config/feature-flags.php` defines boolean flags (currently `admin_new_experience`, `tokens_api`) with environment overrides. The React admin should check these flags via temporary top-bar notices or future `/api/feature-flags` before enabling new flows.
- `includes/feature-flags.php` exposes `feature_flag($key)` used by `admin/userdashboard.php` to gate the SPA while leaving `editor.php` untouched.
- Design tokens default to `config/tokens.php`. Tokens are grouped (`core`, `semantic`, `component`) and consumed by the React `TokenProvider`. Persistence for overrides is not yet wired; new APIs must merge theme/page overrides with these defaults.

## REST Endpoints Consumed by the Editor

### `/api/page.php`
- **Authentication:** session cookie required.
- **Actions:** `get_snapshot` (GET), `update_settings`, `verify_domain`, `update_appearance`, `update_email_settings`, `add_directory`, `update_directory`, `update_social_icon_visibility`, `delete_directory`, `reorder_social_icons`.
- **Contracts:**
  - `get_snapshot` returns `{ success, page, widgets, social_icons, tokens, token_overrides }` with widget `config_data` parsed into objects.
  - `update_settings` expects sanitized fields (`username`, `podcast_name`, `rss_feed_url`, `custom_domain`). Returns `{ success: bool, error?: string }`.
  - `update_appearance` accepts theme ID, layout, color overrides (`custom_primary_color`, etc.), font fields (`page_primary_font`, `widget_primary_font`), widget style JSON, and spatial/page name effects. Uses `WidgetStyleManager::sanitize`. Response uses `APIResponse::success/error`.
  - Directory/social icon actions operate on `social_icons` table; payloads include `platform_name`, `url`, `is_active`.
- **Dependencies:** `Page` model for DB writes, `RSSParser`, `DomainVerifier`, `WidgetStyleManager`.

### `/api/widgets.php`
- **Authentication:** session + CSRF.
- **Actions:** `add`, `update`, `delete`, `reorder`, `get`, `get_available`.
- **Contracts:**
  - Widget configuration driven by `WidgetRegistry::getWidget($type)['config_fields']`.
  - `config_data` stored as JSON; expects sanitized values per field type (`url`, `textarea`, etc.).
  - `reorder` accepts JSON array of `{ widget_id, display_order }`.
  - Responses use `{ success: bool, widget? | widgets? | error? }`.

### `/api/themes.php`
- **Methods:** `GET`.
- **Contracts:** returns either `{'success': true, 'themes': Theme[], 'count': number}` when no `id` or `{'success': true, 'theme': Theme}` when `id` passed. Theme rows include JSON strings for colors/fonts; React should parse.

### `/api/analytics.php`
- **Actions:** `widget_analytics` (GET/POST parameter).
- **Contracts:** returns `{ success: true, widgets: AnalyticsRow[], page_views: number, total_clicks: number }`.
- **Notes:** requires `period` param (`day|week|month`). Fallback returns empty arrays when data missing.

### `/api/tokens.php`
- **Methods:** `GET`, `POST`.
- **GET Contracts:** returns `{ success: true, tokens: TokenBundle, overrides: array }` where overrides represents persisted page-level customisations merged server-side with defaults.
- **POST Contracts:** accepts JSON body with partial `TokenBundle` overrides and persists them to the current user's page (`pages.token_overrides`). Response mirrors `{ success: bool, error?: string }`.
- **Notes:** Requires feature flag `tokens_api` and the authenticated user must have a page record.

## Account & Authentication (Legacy PHP Forms)

### `login.php`
- **Method:** POST form (`email`, `password`, `csrf_token`).
- **Behaviour:** Validates credentials via `User::login()`, sets session, redirects to `editor.php` on success or renders error message. Includes link to Google OAuth flow (`getGoogleAuthUrl()` with mode `login`).

### `signup.php`
- **Method:** POST form (`email`, `password`, `confirm_password`, `csrf_token`).
- **Behaviour:** Creates account with `User::create()`, sends verification email, renders success/error inline. Redirect (after login) currently points to `editor.php`.

### `auth/google/callback.php`
- **Modes:** `login` (default) and `link` (when connecting Google inside the account tab).
- **Behaviour:** Exchanges code for token, fetches profile, then:
  - Logs in existing Google-linked users.
  - Links Google to current user when `mode=link` and session authenticated.
  - Handles “email already exists” flow via `verify-google-link.php`.
  - Redirects to `editor.php` (or Studio) once the migration flag is enabled.

### `/api/account/security.php`
- **Method:** `POST` (JSON).
- **Actions:**
  - `action: "remove_password"` → `User::removePassword()` (requires linked Google account).
  - `action: "unlink_google"` → `User::unlinkGoogleAccount()` (requires password on file).
- **Response:** `{ success: boolean, error?: string | null }`

### `/api/account/page.php`
- **Method:** `POST` (JSON).
- **Action:** `action: "create_page"`, `username: string`
- **Behaviour:** Calls `Page::create()` to provision the user's first page and default subscription.
- **Response:** `{ success: boolean, error?: string | null, data: { page_id: number | null } }`

### Password recovery
- `forgot-password.php` + `reset-password.php` issue tokens via `User::startPasswordReset()` / `User::completePasswordReset()`; responses are rendered within those pages.

> These account-management flows now live behind `/api/account/security.php` and `/api/account/page.php`, enabling Studio to replace the legacy dashboard actions.

### `/api/telemetry.php`
- **Method:** `POST` (JSON).
- **Payload:** `{ event: string, metadata?: object }`.
- **Behaviour:** Requires authenticated session; logs event, user ID, and metadata via PHP `error_log` for quick instrumentation. Intended for lightweight usage (e.g., account navigation, top-bar interactions) until a dedicated analytics pipeline is in place.

### `/api/tokens.php`
- **Methods:** `GET`, `POST`.
- **GET Contracts:** returns `{ success: true, tokens: TokenBundle, overrides: array }` where overrides represents persisted page-level customisations merged server-side with defaults.
- **History:** `GET /api/tokens.php?action=history` returns `{ success, history: TokenHistoryEntry[] }` (latest 20 snapshots). Each entry includes `created_at`, `created_by_email`, and raw overrides.
- **Save:** `POST` JSON body with partial overrides persists to `pages.token_overrides`. Response `{ success, error? }`. Snapshots are automatically recorded in `page_token_history`.
- **Rollback:** `POST { action: 'rollback', history_id }` restores overrides from a snapshot.
- **Notes:** Requires feature flag `tokens_api` and the authenticated user must have a page record.

### `/api/themes.php`
- **GET scopes:**
  - `?id={themeId}` returns a single active theme.
  - `?scope=all` returns `{ system: Theme[], user: Theme[] }` for the authenticated user.
  - `?scope=user` returns the caller's custom themes only.
- **POST actions (authenticated):**
  - `action=clone`, `theme_id`, optional `name` – duplicates a system or user theme into the caller's library.
  - `action=delete`, `theme_id` – removes a user-owned theme.
  - `action=rename`, `theme_id`, `name` – renames a user-owned theme.

### Feature flags (admin)
- `admin_new_experience`: toggles access to the React Studio shell.
- `tokens_api`: enables `/api/tokens.php` operations.
- `admin_account_workspace`: enables account routes (`/account/*`) inside the Studio shell. When disabled, the top-bar account module links to the classic `/editor.php?tab=account` fallback and the SPA avoids account-specific navigation.

## Models & Helpers Touch Points
- `classes/Page.php`: central for page CRUD, widget/social helpers, applying JSON encoding for `colors`, `fonts`, `widget_styles`.
- `classes/Theme.php`: merges defaults with overrides, exposes `getAllThemes`, `getTheme`, default token getters.
- `classes/WidgetRegistry.php`: authoritative list of widget types + config metadata used on both backend and frontend.
- `classes/Analytics.php`: provides `getWidgetAnalytics(pageId, period)` used in analytics endpoint.

## Legacy Editor Intersections
- `editor.php` bootstraps page data via PHP (Page/Themes/Tokens). Until migration completes, all new endpoints must remain backward compatible with form submissions triggered by this file.
- CSRF tokens provided by `generateCSRFToken()`; React client must either re-use hidden inputs or fetch from dedicated endpoint.

## Open Questions / Follow-ups
- Where to persist page/theme token overrides (likely new JSON columns). Requires DB migration planning.
- Analytics endpoint currently returns widget-level metrics only; confirm if page-level stats required in React dashboard.
- Identify minimal feature flag API for SPA to know when to expose unfinished areas.

---
This snapshot covers all endpoints and feature toggles needed to begin wiring the React admin. Update as contracts evolve during implementation.***

