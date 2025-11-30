# Resolving GitHub Secret Scanning Block

## Problem

GitHub detected a Personal Access Token in your git history in these files:
- `QUICK_GIT_DEPLOY.md`
- `deploy_git.sh`

The push is blocked until this is resolved.

## Option 1: Quick Fix (If Token is Safe/Rotated)

If the token in git history is:
- Already rotated/revoked, OR
- Not sensitive (test token that's been disabled)

**Steps:**

1. **Visit GitHub's unblock page:**
   ```
   https://github.com/phil426/podabio/security/secret-scanning/unblock-secret/36AKK5VeQA6hVqktm9Dd1ABU9DH
   ```

2. **Review the detected secret** and click "Allow secret" if it's safe

3. **Push your commits:**
   ```bash
   git push origin main
   ```

**⚠️ Warning:** This only allows the push. The secret remains in git history and could be exposed if the repository becomes public or is cloned.

---

## Option 2: Proper Fix (Recommended) - Remove Secret from History

If the token is still active or could be sensitive, you should remove it from git history.

### Step 1: Rotate/Revoke the Token

1. Go to GitHub Settings → Developer settings → Personal access tokens
2. Revoke the exposed token
3. Create a new token if needed

### Step 2: Remove Secret from Git History

**Using BFG Repo-Cleaner (Recommended):**

1. **Install BFG:**
   ```bash
   brew install bfg  # macOS
   # Or download from: https://rtyley.github.io/bfg-repo-cleaner/
   ```

2. **Create a text file with the token to remove:**
   ```bash
   echo "YOUR_EXPOSED_TOKEN_HERE" > /tmp/tokens.txt
   ```

3. **Remove the token from history:**
   ```bash
   cd /Users/philybarrolaza/.cursor/podinbio
   git clone --mirror . ../podinbio-backup.git
   bfg --replace-text /tmp/tokens.txt ../podinbio-backup.git
   cd ../podinbio-backup.git
   git reflog expire --expire=now --all
   git gc --prune=now --aggressive
   ```

4. **Force push (⚠️ destructive operation):**
   ```bash
   git push --force origin main
   ```

**Alternative: Using git-filter-branch (slower):**

```bash
git filter-branch --force --index-filter \
  "git rm --cached --ignore-unmatch QUICK_GIT_DEPLOY.md deploy_git.sh" \
  --prune-empty --tag-name-filter cat -- --all

git push --force --all
git push --force --tags
```

### Step 3: Add Files to .gitignore

Add these files to `.gitignore` to prevent future commits:

```bash
echo "" >> .gitignore
echo "# Deployment scripts with secrets" >> .gitignore
echo "QUICK_GIT_DEPLOY.md" >> .gitignore
echo "deploy_git.sh" >> .gitignore
echo "*_DEPLOY*.md" >> .gitignore
echo "*deploy*.sh" >> .gitignore
```

### Step 4: Clean Up Existing Files

If these files still exist locally, move them outside the repo or delete them:

```bash
# Check if files exist
ls -la QUICK_GIT_DEPLOY.md deploy_git.sh 2>/dev/null

# If they exist, move to a secure location outside the repo
# mv QUICK_GIT_DEPLOY.md ~/secure-deployment-files/
# mv deploy_git.sh ~/secure-deployment-files/
```

---

## Option 3: Hybrid Approach (Best for Now)

1. **Immediate: Allow the push via GitHub** (Option 1) to unblock your work
2. **Later: Clean git history** (Option 2) when you have time
3. **Prevent future issues:** Add deployment scripts to `.gitignore`

---

## Prevention Steps

### 1. Add Common Secret Patterns to .gitignore

Update `.gitignore`:

```gitignore
# Deployment scripts (may contain tokens)
*_DEPLOY*.md
*deploy*.sh
QUICK_GIT_DEPLOY.md
deploy_git.sh

# Any files with credentials
*CREDENTIALS*.md
*SECRETS*.md
*TOKENS*.md
```

### 2. Use Environment Variables

Instead of hardcoding tokens in files:

```bash
# Use environment variables
export GITHUB_TOKEN="your-token-here"
./deploy.sh

# Or use a .env file (already in .gitignore)
echo "GITHUB_TOKEN=your-token-here" >> .env
```

### 3. Use Git Hooks (Pre-commit Checks)

Create `.git/hooks/pre-commit`:

```bash
#!/bin/bash
# Check for common secret patterns
if git diff --cached --name-only | grep -E "\.(sh|md)$" | xargs grep -l "ghp_\|github_pat_" 2>/dev/null; then
  echo "⚠️  WARNING: Potential GitHub token detected!"
  echo "Please review before committing."
  exit 1
fi
```

Make it executable:
```bash
chmod +x .git/hooks/pre-commit
```

### 4. Use GitHub Secrets (for CI/CD)

For deployment scripts, use GitHub Actions Secrets instead of hardcoded tokens:

```yaml
# .github/workflows/deploy.yml
- name: Deploy
  run: ./deploy.sh
  env:
    GITHUB_TOKEN: ${{ secrets.DEPLOYMENT_TOKEN }}
```

---

## Verification

After resolving:

1. **Check git status:**
   ```bash
   git status
   ```

2. **Attempt push:**
   ```bash
   git push origin main
   ```

3. **If successful, verify commits:**
   ```bash
   git log --oneline -5
   ```

---

## Current Status

- **Commits ahead:** 574
- **Secret detected in:** QUICK_GIT_DEPLOY.md, deploy_git.sh
- **Unblock URL:** https://github.com/phil426/podabio/security/secret-scanning/unblock-secret/36AKK5VeQA6hVqktm9Dd1ABU9DH

---

## Notes

- The files mentioned may no longer exist in your working directory (they're in git history)
- GitHub scans all commits being pushed, not just current files
- Once allowed, the secret is still in git history - consider cleaning history later
- For private repos, the risk is lower but still recommended to rotate tokens

