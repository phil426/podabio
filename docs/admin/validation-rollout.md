# Validation & Rollout Strategy

## 1. Validation Milestones
- **Design token parity**: verify that legacy pages rendered via `ThemeCSSGenerator` match the new token schema. Run automated snapshot tests comparing rendered CSS variables for a representative set of themes.
- **API contract stability**: use Postman/Newman suites against `/api/tokens.php`, `/api/page.php`, and `/api/widgets.php` to ensure the new admin payloads remain backward compatible.
- **Accessibility**: integrate axe-core in Cypress for critical flows (token editing, drag-and-drop, publishing). Include manual keyboard-only runs and screen-reader smoke tests (VoiceOver/NVDA).
- **Responsiveness**: exercise desktop breakpoints (1024, 1280, 1440) and confirm mobile preview scales to 320–414 widths inside the canvas.
- **Performance**: target <150ms interaction response for token edits and <2s initial load on cold start. Capture Web Vitals through Vite build reports and production RUM once deployed.
- **Cross-browser account QA**: execute the matrix outlined in `docs/admin/qa-exit-checklist.md` across Chrome, Safari, Firefox, and Edge before sign-off.
- **Telemetry visibility**: set up Kibana dashboard (see `docs/admin/qa-exit-checklist.md`) and verify alert thresholds for `topbar.account_navigate_legacy`.

## 2. Testing Playbook
- **Unit tests**: add Vitest suites for token utilities (flattening, css-var generation) and UI components (Accordion, ColorPicker, DnD list) with React Testing Library.
- **Integration tests**: Cypress workflows for:
  - Creating a page clone and editing tokens end-to-end.
  - Reordering layers via drag-and-drop and verifying persisted order.
  - Publishing preview that consumes `/api/tokens.php`.
- **Regression guardrails**: nightly GitHub Action runs hitting both the legacy editor and new admin smoke tests to detect token regressions early.

## 3. Rollout Phases
1. **Internal Alpha (week 0–1)**  
   - Feature flag `admin_new_experience` enabled for internal accounts.  
   - Collect feedback via shared Notion board and inline survey.
2. **Creator Beta (week 2–4)**  
   - Allow opt-in from dashboard banner.  
   - Monitor key metrics: average session length, token save errors, bounce rate between editors.  
   - Run weekly office hours to capture qualitative insights.
3. **Gradual Default (week 5–6)**  
   - Set new admin as default for 25%, 50%, then 100% of traffic while keeping legacy `/editor.php` accessible via fallback link.  
   - Track support ticket volume and rollback thresholds (e.g., >3 blocking bugs/day).
4. **Legacy Sunset (week 7+)**  
   - Communicate final migration date.  
   - Archive `editor.php` code path after two weeks of zero critical findings.

## 4. Telemetry & Instrumentation
- Instrument REACT admin with analytics events for panel resize, token save, color picker use, drag-and-drop reorder, and publishing actions.
- Add server-side logging for `/api/tokens.php` to detect unexpected payload sizes or missing tokens.
- Build dashboards (e.g., Metabase) to visualize adoption progress and key metrics.

## 5. Support & Documentation
- Update Help Center with new admin guides, annotated screenshots, and FAQs on tokens.
- Produce migration checklist for enterprise users (custom themes, embedded widgets).
- Train support team—create macro responses referencing feature flag toggles and known workarounds.

## 6. Validation & Handoff

- Smoke-test full flows (load, edit, reorder, publish) plus regression checks on legacy editor.
- Prepare rollout checklist with monitoring, QA sign-off, and documentation updates.

### Verification Log (2025-11-10)
- `npm run build` (Vite 7.2.2) — ✅
- `npm run lint` — ✅
- Token override save flow (Design tab → save, reset) — ✅
- Widget inspector (select block → edit fields → save) — ✅

### Verification Log (2025-11-11)
- `npm run lint` — ✅
- `npm run build` — ✅
- Account data hooks (`useAccountProfile`, `useAuthMethods`, `useSubscriptionStatus`) — smoke-tested via Studio account workspace — ✅
- Account workspace navigation (`/account/profile`, `/account/security`, `/account/billing`) — ✅
- Feature flag fallback (`admin_account_workspace=false`) — menu links fall back to `/editor.php?tab=account` — ✅
- Telemetry endpoint `/api/telemetry.php` — verified 200 response and log entry — ✅
- Created QA exit matrix + support enablement checklist — `docs/admin/qa-exit-checklist.md` — ✅
- Theme library operations (`clone`, `delete`, `rename`, `apply`) — via `ThemeLibraryPanel` — ✅
- Token history snapshot → rollback — `TokenHistoryPanel` — ✅
- Publishing workflow (draft → publish → schedule → rollback) — `CanvasViewport` — ✅
- Legacy editor redirect to Studio (`editor.php` guard) — ✅

