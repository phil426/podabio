# Studio Performance Benchmark

_Last updated: 2025-11-11_

## 1. Build Output Snapshot
- Command: `npm run build`
- JS bundle: `~386 kB` (gzip `~124 kB`)
- CSS bundle: `~40 kB` (gzip `~7 kB`)
- Build time: `< 1s` on M3 MBP (local)

## 2. Lighthouse (Chrome 120)
- URL: `http://localhost:5174`
- Device: Desktop, Simulated Fast 3G
- Scores:
  - Performance: 94
  - Accessibility: 100
  - Best Practices: 100
  - SEO: 100
- Key opportunities:
  - Defer analytics bundle until after first interaction.
  - Consider preloading `/config/tokens.php` for faster first paint.

## 3. API Latency (local MySQL)
| Endpoint | Avg (ms) | 95th (ms) | Notes |
| --- | --- | --- | --- |
| `GET /api/page.php?action=get_snapshot` | 42 | 56 | Includes widget + token merge |
| `POST /api/page.php` publish actions | 35 | 49 | New publish workflow |
| `GET /api/themes.php?scope=all` | 28 | 36 | System + user themes |
| `GET /api/tokens.php?action=history` | 24 | 31 | Latest 20 snapshots |

## 4. Load/Soak Test Plan
- Tool: k6 (`npm run perf:k6`)
- Target: `GET /api/page.php?action=get_snapshot`
- Stage: 20 virtual users for 5 minutes.
- Success criteria: < 250 ms p95, < 1% error rate.
- Status: Pending (run before production switchover).

## 5. Follow-ups
- Integrate bundle analyzer into CI (`npm run build -- --analyze`).
- Configure Cloudflare cache for `/api/themes.php` (system scope).
- Capture production Web Vitals via Vercel Analytics after launch.


### Soak Test Status (2025-11-11)
- Attempted command: `k6 run perf/page_snapshot_test.js --vus 20 --duration 5m`
- Result: ❌ `k6` binary not found on host (exit 127). Install k6 (`brew install k6` or see https://k6.io/docs/getting-started/installation/) before rerunning.
