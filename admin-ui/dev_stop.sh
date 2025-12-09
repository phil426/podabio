#!/bin/bash

# Stop Development Session

echo "🛑 Stopping Podabio Admin UI Dev Session..."

# Method 1: Kill by known ports
PORTS=(5173 5174 5175)
for PORT in "${PORTS[@]}"; do
    if lsof -i :$PORT > /dev/null; then
        PID=$(lsof -ti :$PORT)
        echo "🔪 Killing process $PID on port $PORT..."
        kill -9 $PID 2>/dev/null
    fi
done

# Method 2: Kill by process name (fallback)
# Only kill vite processes started by node
if pgrep -f "vite" > /dev/null; then
    echo "🧹 Cleaning up remaining Vite processes..."
    pkill -f "vite"
fi

echo "✅ Server stopped."

# 2. Git Status Check
echo "📊 Checking git status..."
git status
