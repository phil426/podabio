# Security Cleanup Guide - Removing Secrets from Git History

**Date:** 2025-11-29  
**Purpose:** Guide to remove exposed credentials from Git history

---

## Overview

This guide explains how to remove sensitive credentials that have been committed to Git history. The following credentials need to be rotated and removed from history:

- SSH Password: `?g-2A+mJV&a%KP$`
- Database Password: `?g-2A+mJV&a%KP$`
- Google OAuth Client Secret: `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`
- Spotify Client ID: `b70f9c5389f44a4f9d2b5f2d54208b85`
- Spotify Client Secret: `8190dd07a00f4f8dbff21726545ee86f`
- Facebook App Secret: `698b50babf1b63b7c5455b5e5d2f0c96`

---

## ⚠️ IMPORTANT: Rotate Credentials First

**BEFORE** cleaning Git history, you **MUST** rotate all exposed credentials:

1. **Rotate SSH Password** (via Hostinger Control Panel)
2. **Rotate Database Password** (via Hostinger Control Panel > Databases)
3. **Rotate Google OAuth Client Secret** (via Google Cloud Console)
4. **Rotate Spotify API Credentials** (via Spotify Developer Dashboard)
5. **Rotate Facebook App Secret** (via Meta Developer Console)

**Why?** Even after removing from Git history, if the repository was ever public or shared, the credentials may already be exposed.

---

## Option 1: Using BFG Repo-Cleaner (Recommended)

BFG is faster and simpler than git filter-branch for this task.

### Step 1: Install BFG Repo-Cleaner

```bash
# macOS
brew install bfg

# Or download from: https://rtyley.github.io/bfg-repo-cleaner/
```

### Step 2: Create a File with Passwords to Replace

```bash
cat > /tmp/secrets-to-remove.txt << 'EOF'
?g-2A+mJV&a%KP$
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
b70f9c5389f44a4f9d2b5f2d54208b85
8190dd07a00f4f8dbff21726545ee86f
698b50babf1b63b7c5455b5e5d2f0c96
EOF
```

### Step 3: Create a Fresh Clone (Required for BFG)

```bash
cd /tmp
git clone --mirror https://github.com/phil426/podabio.git podabio-cleanup.git
cd podabio-cleanup.git
```

### Step 4: Run BFG to Replace Secrets

```bash
# Replace each secret with [REDACTED]
bfg --replace-text /tmp/secrets-to-remove.txt --replace-with '[REDACTED]' podabio-cleanup.git
```

### Step 5: Clean Up and Force Push

```bash
cd podabio-cleanup.git
git reflog expire --expire=now --all
git gc --prune=now --aggressive

# Force push (WARNING: This rewrites history)
git push --force origin main
```

---

## Option 2: Using git filter-branch

### Step 1: Remove Password from All Files

```bash
# Create a script to replace passwords
git filter-branch --force --index-filter \
  "git rm --cached --ignore-unmatch DEPLOYMENT_NEXT_STEPS.md docs/DEPLOYMENT_PODA_BIO.md docs/deployment/DEPLOYMENT_NEXT_STEPS.md docs/deployment/DEPLOYMENT_AUTOMATION_SETUP.md && \
   git checkout HEAD -- DEPLOYMENT_NEXT_STEPS.md docs/DEPLOYMENT_PODA_BIO.md docs/deployment/DEPLOYMENT_NEXT_STEPS.md docs/deployment/DEPLOYMENT_AUTOMATION_SETUP.md && \
   sed -i '' 's/?g-2A+mJV&a%KP\$/[REDACTED]/g' DEPLOYMENT_NEXT_STEPS.md docs/DEPLOYMENT_PODA_BIO.md docs/deployment/DEPLOYMENT_NEXT_STEPS.md docs/deployment/DEPLOYMENT_AUTOMATION_SETUP.md 2>/dev/null || true" \
  --prune-empty --tag-name-filter cat -- --all
```

### Step 2: Clean Up

```bash
git reflog expire --expire=now --all
git gc --prune=now --aggressive
```

### Step 3: Force Push

```bash
git push origin --force --all
git push origin --force --tags
```

---

## Option 3: Manual File Replacement (Simpler, but less thorough)

This approach replaces passwords in current files but doesn't clean Git history:

### Step 1: Remove Files from Git Tracking

```bash
git rm --cached config/podcast-apis.php
git rm --cached config/meta.php
```

These files are now in `.gitignore` and won't be tracked going forward.

### Step 2: Commit Changes

```bash
git add .gitignore
git add DEPLOYMENT_NEXT_STEPS.md
git add docs/DEPLOYMENT_PODA_BIO.md
git add docs/deployment/*.md
git add config/*.php.example
git commit -m "Security: Remove exposed passwords and add config files to .gitignore"
```

### Step 3: Clean Git History (Optional but Recommended)

If you want to remove passwords from history completely, use BFG or git filter-branch (see options above).

---

## Removing Sensitive Config Files from Git

The following files should be removed from Git tracking:

```bash
# Remove from Git (files will remain locally)
git rm --cached config/podcast-apis.php
git rm --cached config/meta.php

# Commit the removal
git commit -m "Security: Remove sensitive config files from Git tracking"
```

**Note:** These files are already in `.gitignore`, so they won't be tracked going forward.

---

## Verify Cleanup

After cleanup, verify secrets are removed:

```bash
# Check if passwords still exist in Git history
git log --all --full-history -p | grep -i "?g-2A" || echo "✅ Password not found in history"
git log --all --full-history -p | grep -i "GOCSPX" || echo "✅ Google Secret not found in history"
git log --all --full-history -p | grep -i "8190dd07a00f4f8dbff21726545ee86f" || echo "✅ Spotify Secret not found in history"
git log --all --full-history -p | grep -i "698b50babf1b63b7c5455b5e5d2f0c96" || echo "✅ Facebook Secret not found in history"
```

---

## Post-Cleanup Checklist

- [ ] All credentials rotated in their respective services
- [ ] Secrets removed from Git history
- [ ] Sensitive config files added to `.gitignore`
- [ ] Template files (`.example`) created
- [ ] Passwords removed from documentation files
- [ ] Force push completed (if history was rewritten)
- [ ] Team members notified to re-clone repository

---

## Important Notes

1. **History Rewriting**: Force pushing rewrites Git history. All team members will need to re-clone the repository.

2. **GitHub Secret Scanning**: After cleanup, GitHub may still detect secrets in old commits. Use GitHub's "Allow secret" feature if it's safe, or the secrets should be removed from history.

3. **Backup**: Consider backing up your repository before rewriting history.

4. **Team Coordination**: Coordinate with your team before force pushing, as it will require everyone to re-clone.

---

## Alternative: Use Environment Variables

Instead of cleaning Git history, you can:

1. Move all secrets to environment variables
2. Update code to read from environment variables
3. Document the required environment variables
4. Add `.env` to `.gitignore` (already done)

This approach is cleaner and doesn't require rewriting history, but you should still rotate the exposed credentials.

---

## Resources

- [BFG Repo-Cleaner Documentation](https://rtyley.github.io/bfg-repo-cleaner/)
- [Git filter-branch Documentation](https://git-scm.com/docs/git-filter-branch)
- [GitHub: Removing sensitive data](https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/removing-sensitive-data-from-a-repository)

