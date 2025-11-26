# PodaBio Secondary Deployment Reference

> **Security Notice**
> This document contains sensitive operational details. Store securely and rotate shared credentials regularly. Do **not** expose publicly.

## 1. Git & Codebase
- **Repository:** https://github.com/phil426/podabio.git
- **Primary Branch:** `main`
- **Local workspace:** `/Users/philybarrolaza/.cursor/podinbio`
- **Deploy script:** `./deploy_git.sh` (runs git pull on server via SSH)
- **Latest version tag:** `v2.0.1` (or see `git tag -l` for current tags)
- **Application version:** See `/VERSION` file (currently `2.0.1`)

### Standard Deployment Workflow
1. Commit local changes:
   ```bash
   git status
   git add <files>
   git commit -m "<message>"
   git push origin main
   ```
2. Deploy to Hostinger shared host:
   ```bash
   ./deploy_git.sh
   ```
   - Script connects to production server and executes `git pull` in project directory.
3. Run migrations if prompted:
   - Visit `https://getphily.com/database/migrate_add_featured_widgets.php` or any new migration URL.
4. Verify front-end at `https://getphily.com/<username>` (ex: `/phil624`).
5. Verify admin editor at `https://getphily.com/editor.php`.

## 2. Hosting & Access Credentials
### Hostinger Control Panel
- **Panel URL:** https://hpanel.hostinger.com/
- **Account Email:** `phil624@gmail.com`
- **Password:** *(refer to secure password manager / owner; not stored in repo)*

### SSH Access (Production Server)
- **Host/IP:** `82.198.236.40`
- **Port:** `65002`
- **Username:** `u810635266`
- **Password:** *(managed via Hostinger panel; not stored in repo)*
- **Command:** `ssh -p 65002 u810635266@82.198.236.40`
- **Project Directory:** `/home/u810635266/domains/getphily.com/public_html/podnbio`

### FTP / File Manager
- Use Hostinger hPanel > Files > File Manager (same credentials as hPanel).

## 3. Database Credentials (MySQL)
- **Host:** `srv556.hstgr.io` (IP `156.67.73.201`)
- **Database:** `u810635266_site_podnbio`
- **Username:** `u810635266_podnbio`
- **Password:** `6;hhwddG`
- **phpMyAdmin:** Accessible via Hostinger hPanel (Databases > phpMyAdmin)
- **Local Config File:** `config/database.php` (git-ignored)

### Database Utilities
- **Schema:** `database/schema.sql`
- **Seed Data:** `database/seed_data.sql`
- **Set Page Name Effect script:** `database/set_page_name_effect_none.php`
- **Backups:** `create_checkpoint_server.sh` (runs remote DB dump)

## 4. API & OAuth
- **Google Developer Account Email:** `phil@redwoodempiremedia.com`
- **OAuth Setup Guide:** `GOOGLE_OAUTH_SETUP.md`
- **Config File:** `config/oauth.php` (git-ignored; store client ID/secret locally only)

## 5. Deployment Scripts & Backups
- `create_checkpoint.sh` – Orchestrates local + remote backup sequence
- `create_checkpoint_local.sh` – Archives repo + uploads locally
- `create_checkpoint_server.sh` – SSH script to dump database to `/home/u810635266/backups/`
- `deploy_git.sh` – Automates git pull on production host

**Recommended cadence:** run checkpoint scripts before major deployments.

## 6. Testing Checklist Post-Deploy
- [ ] Confirm landing pages load without PHP errors
- [ ] Verify widget marquee behavior for long descriptions
- [ ] Test admin editor save flows (appearance auto-save, theme change)
- [ ] Verify podcast player drawer (if RSS present)
- [ ] Run `read_lints` locally for touched files before deploy
- [ ] Monitor server logs (`logs/error_log`) for 5–10 minutes after release

## 7. Emergency Rollback
1. SSH into server
2. Navigate to project directory
3. Run `git log --oneline` to identify last good commit
4. Execute `git reset --hard <commit>` and restart deployment script
5. Restore database from latest dump (`/home/u810635266/backups/`)

## 8. Contact & Escalation
- **Project Owner:** Phil (phil624@gmail.com)
- **Hosting Provider Support:** Hostinger ticket/chat via hPanel
- **GitHub Maintainer:** Phil (https://github.com/phil426)

---
Keep this document in the `docs/` directory and update after any credential or workflow change. Rotate passwords periodically and notify maintainers of updates.
