#!/usr/bin/env bash

# PodaBio Development Session Shutdown Script
# Stops dev servers, creates git stash checkpoint, and cleans caches

set -e  # Exit on error

PROJECT_ROOT="/Users/philybarrolaza/.cursor/podinbio"
ADMIN_UI_DIR="${PROJECT_ROOT}/admin-ui"

echo "🛑 PodaBio Development Session Shutdown"
echo "========================================"
echo ""

# Change to project root
cd "${PROJECT_ROOT}"

# Step 1: Stop PHP dev server (localhost:8080)
echo "📡 Stopping PHP dev server (localhost:8080)..."
PHP_PID=$(lsof -ti:8080 2>/dev/null || true)
if [ -n "$PHP_PID" ]; then
    kill $PHP_PID 2>/dev/null || true
    sleep 1
    # Force kill if still running
    if lsof -ti:8080 >/dev/null 2>&1; then
        kill -9 $PHP_PID 2>/dev/null || true
    fi
    echo "   ✅ PHP server stopped"
else
    echo "   ℹ️  PHP server was not running"
fi

# Step 2: Stop Vite dev server (localhost:5174)
echo "📡 Stopping Vite dev server (localhost:5174)..."
VITE_PID=$(lsof -ti:5174 2>/dev/null || true)
if [ -n "$VITE_PID" ]; then
    kill $VITE_PID 2>/dev/null || true
    sleep 1
    # Force kill if still running
    if lsof -ti:5174 >/dev/null 2>&1; then
        kill -9 $VITE_PID 2>/dev/null || true
    fi
    echo "   ✅ Vite server stopped"
else
    echo "   ℹ️  Vite server was not running"
fi

# Also kill any npm/node processes related to vite
pkill -f "vite.*5174" 2>/dev/null || true
pkill -f "npm.*dev" 2>/dev/null || true

# Step 3: Check git status
echo ""
echo "📊 Checking git status..."
if [ -d ".git" ]; then
    # Check if there are uncommitted changes
    if ! git diff-index --quiet HEAD -- 2>/dev/null || [ -n "$(git ls-files --others --exclude-standard)" ]; then
        echo "   ⚠️  Uncommitted changes detected"
        
        # Create timestamp for stash message
        TIMESTAMP=$(date '+%Y-%m-%d %H:%M:%S')
        STASH_MESSAGE="Session checkpoint: ${TIMESTAMP}"
        
        echo "   💾 Creating git stash checkpoint..."
        if git stash save "${STASH_MESSAGE}" 2>/dev/null; then
            echo "   ✅ Changes stashed: '${STASH_MESSAGE}'"
        else
            echo "   ⚠️  Warning: Failed to create stash (may be empty or conflicts)"
        fi
    else
        echo "   ✅ Working directory is clean (no changes to stash)"
    fi
    
    # Show current stash list
    echo ""
    echo "📋 Current stashes:"
    STASH_COUNT=$(git stash list | wc -l | tr -d ' ')
    if [ "$STASH_COUNT" -gt 0 ]; then
        git stash list | head -5
        if [ "$STASH_COUNT" -gt 5 ]; then
            echo "   ... and $((STASH_COUNT - 5)) more"
        fi
    else
        echo "   (no stashes)"
    fi
else
    echo "   ⚠️  Not a git repository, skipping stash"
fi

# Step 4: Clean build caches
echo ""
echo "🧹 Cleaning build caches..."
cd "${ADMIN_UI_DIR}"

# Remove Vite cache
if [ -d "node_modules/.vite" ]; then
    rm -rf node_modules/.vite
    echo "   ✅ Removed node_modules/.vite"
fi

# Remove .vite directory if it exists
if [ -d ".vite" ]; then
    rm -rf .vite
    echo "   ✅ Removed .vite"
fi

# Remove dist folder (optional - comment out if you want to keep production builds)
# if [ -d "dist" ]; then
#     rm -rf dist
#     echo "   ✅ Removed dist folder"
# fi

cd "${PROJECT_ROOT}"

# Step 5: Summary
echo ""
echo "✅ Shutdown complete!"
echo ""
echo "📝 Summary:"
echo "   • Dev servers stopped"
if [ -d ".git" ] && [ -n "$STASH_MESSAGE" ]; then
    echo "   • Changes saved to stash: '${STASH_MESSAGE}'"
fi
echo "   • Build caches cleaned"
echo ""
echo "💡 To resume: run ./dev-start.sh or ./dev-session.sh start"
echo ""

