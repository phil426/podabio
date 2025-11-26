#!/usr/bin/env bash

# PodaBio Development Session Startup Script
# Cleans caches, restores git stash, and starts dev servers

set -e  # Exit on error

# Source shell profile to get PATH for php and npm
if [ -f ~/.zshrc ]; then
    source ~/.zshrc
elif [ -f ~/.bash_profile ]; then
    source ~/.bash_profile
elif [ -f ~/.bashrc ]; then
    source ~/.bashrc
fi

# Ensure Homebrew binaries are in PATH
export PATH="/opt/homebrew/bin:/opt/homebrew/sbin:$PATH"

# Get the directory where this script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
PROJECT_ROOT="${SCRIPT_DIR}"
ADMIN_UI_DIR="${PROJECT_ROOT}/admin-ui"
PHP_PORT=8080
VITE_PORT=5174

echo "🚀 PodaBio Development Session Startup"
echo "======================================="
echo ""

# Change to project root
cd "${PROJECT_ROOT}"

# Step 1: Clean build caches first
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

cd "${PROJECT_ROOT}"

# Step 2: Check for existing servers
echo ""
echo "🔍 Checking for running servers..."
PHP_RUNNING=$(lsof -ti:${PHP_PORT} 2>/dev/null || true)
VITE_RUNNING=$(lsof -ti:${VITE_PORT} 2>/dev/null || true)

if [ -n "$PHP_RUNNING" ] || [ -n "$VITE_RUNNING" ]; then
    echo "   ⚠️  Warning: Servers may already be running"
    echo "   • PHP (port ${PHP_PORT}): $([ -n "$PHP_RUNNING" ] && echo "RUNNING (PID: $PHP_RUNNING)" || echo "not running")"
    echo "   • Vite (port ${VITE_PORT}): $([ -n "$VITE_RUNNING" ] && echo "RUNNING (PID: $VITE_RUNNING)" || echo "not running")"
    echo ""
    read -p "   Continue anyway? (y/N) " -n 1 -r
    echo ""
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        echo "   ❌ Aborted"
        exit 1
    fi
fi

# Step 3: Restore git stash
echo ""
echo "📦 Restoring git stash..."
if [ -d ".git" ]; then
    STASH_COUNT=$(git stash list | wc -l | tr -d ' ')
    if [ "$STASH_COUNT" -gt 0 ]; then
        echo "   Found ${STASH_COUNT} stash(es)"
        echo "   Most recent: $(git stash list | head -1)"
        echo ""
        echo "   Applying most recent stash..."
        
        # Try to apply stash
        if git stash pop 2>/dev/null; then
            echo "   ✅ Stash applied successfully"
        else
            # Check if there were conflicts
            if [ -n "$(git diff --name-only --diff-filter=U)" ]; then
                echo "   ⚠️  CONFLICTS DETECTED!"
                echo "   You have merge conflicts that need to be resolved manually."
                echo "   Run 'git status' to see conflicted files."
                echo ""
                read -p "   Continue starting servers anyway? (y/N) " -n 1 -r
                echo ""
                if [[ ! $REPLY =~ ^[Yy]$ ]]; then
                    echo "   ❌ Aborted - resolve conflicts first"
                    exit 1
                fi
            else
                echo "   ⚠️  Warning: Stash may have been empty or already applied"
            fi
        fi
    else
        echo "   ℹ️  No stashes found (working directory is clean or no previous session)"
    fi
else
    echo "   ⚠️  Not a git repository, skipping stash restore"
fi

# Step 4: Start PHP dev server
echo ""
echo "📡 Starting PHP dev server (localhost:${PHP_PORT})..."
cd "${PROJECT_ROOT}"

# Check if port is already in use
if lsof -ti:${PHP_PORT} >/dev/null 2>&1; then
    echo "   ⚠️  Port ${PHP_PORT} is already in use"
else
    # Start PHP server in background with proper environment
    nohup bash -c 'export PATH="/opt/homebrew/bin:/opt/homebrew/sbin:$PATH"; cd '${PROJECT_ROOT}' && php -S localhost:'${PHP_PORT}' router.php' > /tmp/podabio-php-server.log 2>&1 &
    PHP_PID=$!
    sleep 2
    
    # Verify it started
    if lsof -ti:${PHP_PORT} >/dev/null 2>&1; then
        echo "   ✅ PHP server started (PID: $PHP_PID)"
        echo "   📝 Logs: /tmp/podabio-php-server.log"
    else
        echo "   ❌ Failed to start PHP server"
        echo "   Check logs: /tmp/podabio-php-server.log"
    fi
fi

# Step 5: Start Vite dev server
echo ""
echo "📡 Starting Vite dev server (localhost:${VITE_PORT})..."
cd "${ADMIN_UI_DIR}"

# Check if port is already in use
if lsof -ti:${VITE_PORT} >/dev/null 2>&1; then
    echo "   ⚠️  Port ${VITE_PORT} is already in use"
else
    # Start Vite server in background with proper environment
    nohup bash -c 'export PATH="/opt/homebrew/bin:/opt/homebrew/sbin:$PATH"; cd '${ADMIN_UI_DIR}' && npm run dev' > /tmp/podabio-vite-server.log 2>&1 &
    VITE_PID=$!
    sleep 3
    
    # Verify it started
    if lsof -ti:${VITE_PORT} >/dev/null 2>&1; then
        echo "   ✅ Vite server started (PID: $VITE_PID)"
        echo "   📝 Logs: /tmp/podabio-vite-server.log"
    else
        echo "   ⚠️  Vite server may still be starting..."
        echo "   📝 Check logs: /tmp/podabio-vite-server.log"
        echo "   💡 It may take a few more seconds to fully start"
    fi
fi

# Step 6: Summary
echo ""
echo "✅ Startup complete!"
echo ""
echo "📝 Summary:"
echo "   • Build caches cleaned"
if [ -d ".git" ] && [ "$STASH_COUNT" -gt 0 ]; then
    echo "   • Git stash restored"
fi
echo "   • PHP server: http://localhost:${PHP_PORT}"
echo "   • Vite server: http://localhost:${VITE_PORT}"
echo ""
echo "📋 Server Status:"
echo "   • PHP (port ${PHP_PORT}): $([ -n "$(lsof -ti:${PHP_PORT} 2>/dev/null)" ] && echo "✅ RUNNING" || echo "❌ NOT RUNNING")"
echo "   • Vite (port ${VITE_PORT}): $([ -n "$(lsof -ti:${VITE_PORT} 2>/dev/null)" ] && echo "✅ RUNNING" || echo "❌ NOT RUNNING")"
echo ""
echo "💡 To stop: run ./dev-stop.sh or ./dev-session.sh stop"
echo "📝 View logs: tail -f /tmp/podabio-php-server.log or tail -f /tmp/podabio-vite-server.log"
echo ""

