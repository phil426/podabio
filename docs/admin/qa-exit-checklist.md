# Studio Account Workspace QA Exit Checklist

_Last updated: 2025-11-11_

## 1. Cross-Browser Smoke Matrix
| Area | Chrome 120+ (macOS) | Safari 17+ (macOS) | Firefox 130+ (macOS) | Edge 120+ (Windows) |
| --- | --- | --- | --- | --- |
| Load Studio shell & authenticate | ☐ | ☐ | ☐ | ☐ |
| Account menu opens / closes | ☐ | ☐ | ☐ | ☐ |
| Navigation `/account/profile` | ☐ | ☐ | ☐ | ☐ |
| Navigation `/account/security` | ☐ | ☐ | ☐ | ☐ |
| Navigation `/account/billing` | ☐ | ☐ | ☐ | ☐ |
| Legacy fallback (`/editor.php?tab=account`) | ☐ | ☐ | ☐ | ☐ |
| Telemetry requests (devtools) | ☐ | ☐ | ☐ | ☐ |

## 2. Telemetry Dashboard Requirements
- Source: server logs containing `[telemetry]` prefix (short-term).
- Pipeline:
  1. Forward PHP error log to Logstash.
  2. Create Kibana dashboard: filter `event.keyword` and `metadata.destination`.
  3. Visuals: daily count by event; top paths for `topbar.account_navigate`.
- Exit criteria:
  - Dashboard link shared in #studio-rollout channel.
  - Alert: `topbar.account_navigate_legacy` > 20/day -> Slack alert.

## 3. Support Enablement Sign-off
- ✅ Share `docs/admin/account-support-playbook.md` with CX team.
- ✅ 30-min enablement session recorded and uploaded to knowledge base.
- ✅ Macro snippets added to Zendesk (ticket IDs documented).
- ☐ Collect post-training survey (3+ responses) confirming confidence with Studio account flows.

## 4. Regression Checklist
- Token inspector unaffected by account flag.
- Top bar actions (publish/preview) still accessible during account testing.
- Logout flow returns to `/login.php` and re-enters Studio.

## 5. Sign-off Template
| Role | Name | Date | Notes |
| --- | --- | --- | --- |
| QA Lead | | | |
| Engineering | | | |
| Support | | | |

