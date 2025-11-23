#!/usr/bin/env bash

# PodaBio Development Session Wrapper Script
# Simple command interface for session management

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
STOP_SCRIPT="${SCRIPT_DIR}/dev-stop.sh"
START_SCRIPT="${SCRIPT_DIR}/dev-start.sh"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

show_usage() {
    echo "PodaBio Development Session Manager"
    echo ""
    echo "Usage:"
    echo "  ./dev-session.sh start   - Start dev servers and restore work"
    echo "  ./dev-session.sh stop    - Stop dev servers and save work"
    echo "  ./dev-session.sh status  - Show current server status"
    echo "  ./dev-session.sh help    - Show this help message"
    echo ""
    echo "Examples:"
    echo "  ./dev-session.sh stop    # End of work session"
    echo "  ./dev-session.sh start   # Beginning of work session"
    echo ""
}

show_status() {
    echo "📊 PodaBio Development Session Status"
    echo "======================================"
    echo ""
    
    PHP_PORT=8080
    VITE_PORT=5174
    
    # Check PHP server
    PHP_PID=$(lsof -ti:${PHP_PORT} 2>/dev/null || true)
    if [ -n "$PHP_PID" ]; then
        echo -e "   PHP Server (port ${PHP_PORT}): ${GREEN}✅ RUNNING${NC} (PID: $PHP_PID)"
    else
        echo -e "   PHP Server (port ${PHP_PORT}): ${RED}❌ NOT RUNNING${NC}"
    fi
    
    # Check Vite server
    VITE_PID=$(lsof -ti:${VITE_PORT} 2>/dev/null || true)
    if [ -n "$VITE_PID" ]; then
        echo -e "   Vite Server (port ${VITE_PORT}): ${GREEN}✅ RUNNING${NC} (PID: $VITE_PID)"
    else
        echo -e "   Vite Server (port ${VITE_PORT}): ${RED}❌ NOT RUNNING${NC}"
    fi
    
    # Check git stashes
    if [ -d "${SCRIPT_DIR}/.git" ]; then
        echo ""
        STASH_COUNT=$(cd "${SCRIPT_DIR}" && git stash list | wc -l | tr -d ' ')
        echo "   Git Stashes: ${STASH_COUNT}"
        if [ "$STASH_COUNT" -gt 0 ]; then
            echo "   Most recent:"
            cd "${SCRIPT_DIR}" && git stash list | head -3 | sed 's/^/      /'
        fi
    fi
    
    echo ""
}

# Main command handler
case "${1:-}" in
    start)
        if [ ! -f "$START_SCRIPT" ]; then
            echo "❌ Error: Startup script not found at $START_SCRIPT"
            exit 1
        fi
        bash "$START_SCRIPT"
        ;;
    stop)
        if [ ! -f "$STOP_SCRIPT" ]; then
            echo "❌ Error: Shutdown script not found at $STOP_SCRIPT"
            exit 1
        fi
        bash "$STOP_SCRIPT"
        ;;
    status)
        show_status
        ;;
    help|--help|-h)
        show_usage
        ;;
    "")
        echo "❌ Error: No command specified"
        echo ""
        show_usage
        exit 1
        ;;
    *)
        echo "❌ Error: Unknown command '${1}'"
        echo ""
        show_usage
        exit 1
        ;;
esac

