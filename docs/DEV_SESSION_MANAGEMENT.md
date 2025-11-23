# Development Session Management

This guide explains how to use the development session management scripts to safely checkpoint your work and manage dev servers.

## Overview

The session management system provides a simple way to:
- **Save your work** without committing incomplete features
- **Stop/start dev servers** automatically
- **Clean build caches** to avoid stale artifacts
- **Resume exactly where you left off** when returning to work

## Quick Start

### End of Work Session (Shutdown)
```bash
./dev-session.sh stop
```

This will:
1. Stop PHP dev server (localhost:8080)
2. Stop Vite dev server (localhost:5174)
3. Save all uncommitted changes to a git stash with timestamp
4. Clean build caches
5. Show you what was saved

### Start of Work Session (Startup)
```bash
./dev-session.sh start
```

This will:
1. Clean build caches
2. Restore your most recent stash (if any)
3. Start PHP dev server in background
4. Start Vite dev server in background
5. Show server status and URLs

### Check Status
```bash
./dev-session.sh status
```

Shows which servers are running and how many stashes you have.

## How It Works

### Git Stash Checkpoints

The system uses **git stash** to save your work. This is different from commits:

- **Stash**: Temporary storage for work-in-progress (not in git history)
- **Commit**: Permanent record in git history

When you run `dev-session.sh stop`, it creates a stash with a message like:
```
Session checkpoint: 2024-01-15 14:30:45
```

When you run `dev-session.sh start`, it automatically applies the most recent stash back to your working directory.

### Workflow Example

**Monday - End of day:**
```bash
$ ./dev-session.sh stop
🛑 PodaBio Development Session Shutdown
========================================
📡 Stopping PHP dev server...
   ✅ PHP server stopped
📡 Stopping Vite dev server...
   ✅ Vite server stopped
💾 Creating git stash checkpoint...
   ✅ Changes stashed: 'Session checkpoint: 2024-01-15 17:30:00'
✅ Shutdown complete!
```

**Tuesday - Start of day:**
```bash
$ ./dev-session.sh start
🚀 PodaBio Development Session Startup
=======================================
🧹 Cleaning build caches...
   ✅ Removed node_modules/.vite
📦 Restoring git stash...
   Found 1 stash(es)
   ✅ Stash applied successfully
📡 Starting PHP dev server...
   ✅ PHP server started
📡 Starting Vite dev server...
   ✅ Vite server started
✅ Startup complete!
```

Your uncommitted changes are now back in your working directory, ready to continue.

## Managing Stashes

### View All Stashes
```bash
git stash list
```

Shows all your saved checkpoints:
```
stash@{0}: Session checkpoint: 2024-01-15 17:30:00
stash@{1}: Session checkpoint: 2024-01-14 18:00:00
stash@{2}: Session checkpoint: 2024-01-13 16:45:00
```

### Apply a Specific Stash
If you want to restore an older stash instead of the most recent one:
```bash
git stash apply stash@{1}  # Apply stash@{1} but keep it in the list
# OR
git stash pop stash@{1}     # Apply stash@{1} and remove it from the list
```

### Delete a Stash
```bash
git stash drop stash@{0}    # Delete the most recent stash
git stash clear              # Delete ALL stashes (use carefully!)
```

### When Ready to Commit
After applying a stash and completing your work:
```bash
git add .
git commit -m "Complete feature X"
```

The stash will be automatically removed when you use `git stash pop` (which the startup script does).

## Handling Conflicts

If the codebase changed while your stash was saved, you might get conflicts when starting:

```bash
$ ./dev-session.sh start
...
⚠️  CONFLICTS DETECTED!
You have merge conflicts that need to be resolved manually.
Run 'git status' to see conflicted files.
```

**To resolve:**
1. Check conflicted files: `git status`
2. Open the files and look for conflict markers (`<<<<<<<`, `=======`, `>>>>>>>`)
3. Manually resolve the conflicts
4. Mark as resolved: `git add <resolved-file>`
5. Continue working

## Setting Up Aliases (Optional)

For even simpler commands, add these to your `~/.zshrc` or `~/.bashrc`:

```bash
alias dev-stop='cd /Users/philybarrolaza/.cursor/podinbio && ./dev-session.sh stop'
alias dev-start='cd /Users/philybarrolaza/.cursor/podinbio && ./dev-session.sh start'
alias dev-status='cd /Users/philybarrolaza/.cursor/podinbio && ./dev-session.sh status'
```

Then you can just run:
```bash
dev-stop    # Instead of ./dev-session.sh stop
dev-start   # Instead of ./dev-session.sh start
dev-status  # Instead of ./dev-session.sh status
```

After adding aliases, reload your shell:
```bash
source ~/.zshrc  # or source ~/.bashrc
```

## Troubleshooting

### Servers Won't Start
- Check if ports are already in use: `lsof -ti:8080` or `lsof -ti:5174`
- Kill existing processes: `kill $(lsof -ti:8080)` or `kill $(lsof -ti:5174)`
- Check logs: `tail -f /tmp/podabio-php-server.log` or `tail -f /tmp/podabio-vite-server.log`

### Stash Not Found
- If you see "No stashes found", it means either:
  - You haven't run `dev-stop` yet (nothing to restore)
  - All stashes were already applied
  - Stashes were manually cleared

### Build Cache Issues
- The scripts automatically clean caches, but if you still have issues:
  - Manually remove: `rm -rf admin-ui/node_modules/.vite`
  - Reinstall dependencies: `cd admin-ui && npm install`

### Git Repository Not Found
- If you see "Not a git repository", make sure you're in the project root
- The scripts should handle this automatically, but check you're in the right directory

## What Gets Cleaned

On **startup**, these are cleaned:
- `admin-ui/node_modules/.vite` - Vite build cache
- `admin-ui/.vite` - Vite cache directory (if exists)

On **shutdown**, these are cleaned:
- Same as startup

**Note:** The `admin-ui/dist` folder is NOT automatically removed (to preserve production builds). If you need to clean it, do so manually.

## Server Logs

Both servers run in the background and log to:
- PHP server: `/tmp/podabio-php-server.log`
- Vite server: `/tmp/podabio-vite-server.log`

View logs in real-time:
```bash
tail -f /tmp/podabio-php-server.log
tail -f /tmp/podabio-vite-server.log
```

## Best Practices

1. **Always use `dev-stop` before closing your terminal** - This ensures your work is saved
2. **Use `dev-start` when beginning work** - This restores your environment
3. **Commit completed features** - Don't let stashes accumulate indefinitely
4. **Review stashes periodically** - Clean up old stashes you no longer need
5. **Resolve conflicts immediately** - Don't let them pile up

## Scripts Reference

- `dev-stop.sh` - Shutdown script (called by `dev-session.sh stop`)
- `dev-start.sh` - Startup script (called by `dev-session.sh start`)
- `dev-session.sh` - Wrapper script with simple commands

All scripts are in the project root: `/Users/philybarrolaza/.cursor/podinbio/`

