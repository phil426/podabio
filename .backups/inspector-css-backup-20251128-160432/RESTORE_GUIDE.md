# CSS Inspector Files Restore Guide

## Backup Information
- **Backup Date**: $(date)
- **Backup Location**: $(basename "$BACKUP_DIR")
- **Purpose**: Backup before refactoring inspector CSS to use font-size CSS variables

## Files Backed Up (11 files)
1. `properties-panel.module.css`
2. `blog-post-inspector.module.css`
3. `featured-block-inspector.module.css`
4. `footer-inspector.module.css`
5. `integration-inspector.module.css`
6. `lefty-inspector-drawer.module.css`
7. `podcast-player-inspector.module.css`
8. `profile-inspector.module.css`
9. `social-icon-inspector.module.css`
10. `widget-inspector.module.css`
11. `global.css`

## How to Restore

### Option 1: Restore from Git Stash (Recommended)
```bash
git stash pop
```
This will restore all stashed changes, including these CSS files.

### Option 2: Restore Individual Files
```bash
# From the backup directory, copy files back:
cp .backups/inspector-css-backup-*/properties-panel.module.css admin-ui/src/components/layout/
cp .backups/inspector-css-backup-*/*-inspector.module.css admin-ui/src/components/panels/
cp .backups/inspector-css-backup-*/global.css admin-ui/src/styles/
```

### Option 3: Restore All Files at Once
```bash
BACKUP_DIR=.backups/inspector-css-backup-*
cp "$BACKUP_DIR"/properties-panel.module.css admin-ui/src/components/layout/
cp "$BACKUP_DIR"/*-inspector.module.css admin-ui/src/components/panels/
cp "$BACKUP_DIR"/lefty-inspector-drawer.module.css admin-ui/src/components/panels/lefty/
cp "$BACKUP_DIR"/global.css admin-ui/src/styles/
```

### Option 4: Restore via Git (if files were committed)
```bash
git checkout HEAD -- admin-ui/src/components/layout/properties-panel.module.css
git checkout HEAD -- admin-ui/src/components/panels/*-inspector.module.css
git checkout HEAD -- admin-ui/src/styles/global.css
```

## Verify Restoration
After restoring, check that files match:
```bash
diff admin-ui/src/components/layout/properties-panel.module.css .backups/inspector-css-backup-*/properties-panel.module.css
```

## Git Stash Info
Current stash name: "Backup before refactoring inspector CSS to use font-size variables"
View stash: `git stash list`
Apply stash: `git stash apply stash@{0}`
Pop stash: `git stash pop`
