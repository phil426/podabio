# PodaBio Studio – Account & Billing Support Playbook

_Last updated: 2025-11-11_

## 1. Feature Flags & Access
- `admin_account_workspace` (config/feature-flags.php)
  - **On:** Studio account tabs (`/account/*`) available.
  - **Off:** Top bar routes fall back to the classic editor account tab (`/editor.php?tab=account`); advise users to manage account there.
- `admin_new_experience` controls access to Studio overall.

## 2. Primary User Flows
1. **Profile updates**
   - Navigate to Studio → Account → Profile.
   - Editing is currently read-only; capture requests via support and note the upcoming profile edit rollout.
2. **Security**
   - Shows password + Google linking status.
   - `Link Google` triggers OAuth flow; `Reset password` opens legacy reset page; `Remove password` and `Unlink Google` now run inside Studio via confirmation drawer.
   - 2FA currently “Coming soon” – capture interest via support ticket.
3. **Billing**
   - Displays plan, renewal, payment method, and invoice history (last 12).
   - `Upgrade plan` → `/payment/checkout.php` (opens in new tab).
   - `Contact support` → `/payment/support.php`.

## 3. Telemetry & Monitoring
- Client events: `topbar.account_menu_toggle`, `topbar.account_navigate`, `account.workspace_tab_change`, `account.workspace_view`, `topbar.account_navigate_legacy`.
- Backend collector: `/api/telemetry.php` (logs to PHP error log). Monitor via server log aggregation.
- Alerting: set up log-based alerts for spikes in `topbar.account_navigate_legacy` to catch feature-flag regressions.

## 4. QA Checklist Before Releases
- ✅ Account menu loads name + plan
- ✅ `/account/profile` renders data
- ✅ `/account/security` buttons link correctly
- ✅ `/account/billing` shows invoices or empty state
- ✅ Feature flag off path tested (menu links to `/editor.php?tab=account`)
- ✅ Telemetry endpoint returns success

## 5. Known Gaps / Follow-ups
- Profile edits remain read-only; plan dedicated API/UX sprint.
- Two-factor auth placeholder.
- Telemetry currently log-based; consider piping into analytics pipeline.

## 6. Support Responses (Macros)
- **General access:** "Launch PodaBio Studio, open the top-right avatar menu, and choose Account → …"
- **Classic fallback:** “If you don’t see the new account tabs yet, the Account button in the top bar will open the classic account tab inside the legacy editor.”
- **Billing issues:** “Use the `Upgrade plan` button in Studio billing tab; if payment fails, contact us via the `Contact support` link which opens the billing help form.”

# PodaBio Studio – Account & Billing Support Playbook

_Last updated: 2025-11-11_

## 1. Feature Flags & Access
- `admin_account_workspace` (config/feature-flags.php)
  - **On:** Studio account tabs (`/account/*`) available.
  - **Off:** Top bar routes fall back to the classic editor account tab (`/editor.php?tab=account`); advise users to manage account there.
- `admin_new_experience` controls access to Studio overall.

## 2. Primary User Flows
1. **Profile updates**
   - Navigate to Studio → Account → Profile.
   - Editing is currently read-only; capture requests via support and note the upcoming profile edit rollout.
2. **Security**
   - Shows password + Google linking status.
   - `Link Google` triggers OAuth flow; `Reset password` opens legacy reset page; `Remove password` and `Unlink Google` now run inside Studio via confirmation drawer.
   - 2FA currently “Coming soon” – capture interest via support ticket.
3. **Billing**
   - Displays plan, renewal, payment method, and invoice history (last 12).
   - `Upgrade plan` → `/payment/checkout.php` (opens in new tab).
   - `Contact support` → `/payment/support.php`.

## 3. Telemetry & Monitoring
- Client events: `topbar.account_menu_toggle`, `topbar.account_navigate`, `account.workspace_tab_change`, `account.workspace_view`, `topbar.account_navigate_legacy`.
- Backend collector: `/api/telemetry.php` (logs to PHP error log). Monitor via server log aggregation.
- Alerting: set up log-based alerts for spikes in `topbar.account_navigate_legacy` to catch feature-flag regressions.

## 4. QA Checklist Before Releases
- ✅ Account menu loads name + plan
- ✅ `/account/profile` renders data
- ✅ `/account/security` buttons link correctly
- ✅ `/account/billing` shows invoices or empty state
- ✅ Feature flag off path tested (menu links to `/editor.php?tab=account`)
- ✅ Telemetry endpoint returns success

## 5. Known Gaps / Follow-ups
- Profile edits remain read-only; plan dedicated API/UX sprint.
- Two-factor auth placeholder.
- Telemetry currently log-based; consider piping into analytics pipeline.

## 6. Support Responses (Macros)
- **General access:** "Launch PodaBio Studio, open the top-right avatar menu, and choose Account → …"
- **Classic fallback:** “If you don’t see the new account tabs yet, the Account button in the top bar will open the classic account tab inside the legacy editor.”
- **Billing issues:** “Use the `Upgrade plan` button in Studio billing tab; if payment fails, contact us via the `Contact support` link which opens the billing help form.”

