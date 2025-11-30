# BFG Repo-Cleaner Token Removal

## Exposed Token
```
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
```

## BFG Replacement File Format

### Method 1: Replace Specific Token

Create file `replacements.txt`:
```
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==>REMOVED
```

### Method 2: Replace ALL GitHub PATs (Regex)

Create file `replacements.txt`:
```
ghp_[A-Za-z0-9]{36}==>REMOVED
```

## Complete BFG Workflow

### 1. Create Replacement File
```bash
echo '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==>REMOVED' > /tmp/bfg-replacements.txt
```

### 2. Clone Repository as Mirror
```bash
cd /tmp
git clone --mirror https://github.com/phil426/podabio.git podabio-bfg.git
```

### 3. Run BFG
```bash
cd /tmp/podabio-bfg.git
bfg --replace-text /tmp/bfg-replacements.txt
```

### 4. Clean Up Git History
```bash
git reflog expire --expire=now --all
git gc --prune=now --aggressive
```

### 5. Force Push (⚠️ Destructive)
```bash
git push --force
```

### 6. Update Local Repository
```bash
cd /Users/philybarrolaza/.cursor/podinbio
git fetch origin
git reset --hard origin/main
```

## Alternative: Single Command Workflow

If you want to do it in your current directory:

```bash
# 1. Create replacement file
echo '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==>REMOVED' > /tmp/bfg-replacements.txt

# 2. Run BFG on current repo (BFG requires a clean repo)
cd /Users/philybarrolaza/.cursor/podinbio
bfg --replace-text /tmp/bfg-replacements.txt

# 3. Clean up
git reflog expire --expire=now --all
git gc --prune=now --aggressive

# 4. Force push
git push origin main --force
```

## Notes

- **BFG Format**: `OLD_TEXT==>NEW_TEXT` (note the double equals sign)
- **Install BFG**: `brew install bfg` (macOS) or download from https://rtyley.github.io/bfg-repo-cleaner/
- **⚠️ Warning**: Force pushing rewrites history and affects all collaborators
- The token will be replaced with "REMOVED" in all commits
- Make sure to revoke the token on GitHub first: https://github.com/settings/tokens

