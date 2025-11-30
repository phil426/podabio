#!/bin/bash
# Security Cleanup Script
# Removes sensitive config files from Git tracking
# Run this script to complete the security fixes

set -e

echo "🔒 Security Cleanup Script"
echo "=========================="
echo ""

# Check if we're in a git repository
if ! git rev-parse --git-dir > /dev/null 2>&1; then
    echo "❌ Error: Not in a Git repository"
    exit 1
fi

echo "Step 1: Removing sensitive config files from Git tracking..."
echo ""

# Remove config files from Git (but keep them locally)
if git ls-files --error-unmatch config/podcast-apis.php > /dev/null 2>&1; then
    echo "  - Removing config/podcast-apis.php from Git tracking"
    git rm --cached config/podcast-apis.php
else
    echo "  ⚠️  config/podcast-apis.php not tracked in Git"
fi

if git ls-files --error-unmatch config/meta.php > /dev/null 2>&1; then
    echo "  - Removing config/meta.php from Git tracking"
    git rm --cached config/meta.php
else
    echo "  ⚠️  config/meta.php not tracked in Git"
fi

echo ""
echo "Step 2: Checking status..."
echo ""
git status --short | grep -E "config/(podcast-apis|meta)\.php" || echo "  ✅ Files removed from tracking"

echo ""
echo "=========================="
echo "✅ Cleanup complete!"
echo ""
echo "Next steps:"
echo "1. Review the changes: git status"
echo "2. Commit the changes:"
echo "   git add .gitignore"
echo "   git add *.md docs/**/*.md"
echo "   git add config/*.php.example"
echo "   git commit -m 'Security: Remove sensitive files from Git tracking and redact passwords'"
echo ""
echo "3. IMPORTANT: Rotate all exposed credentials before pushing!"
echo "   - SSH password"
echo "   - Database password"
echo "   - API keys and secrets"
echo ""
echo "4. For Git history cleanup, see: docs/SECURITY_CLEANUP_GUIDE.md"
echo ""

