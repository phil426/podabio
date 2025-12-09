#!/bin/bash

# Start Development Session

echo "🚀 Starting Podabio Admin UI Dev Session..."

# 1. Pull latest changes
echo "📥 Pulling changes..."
git pull origin main

# 2. Install dependencies
echo "📦 Checking dependencies..."
npm install

# 3. Start Server
echo "⚡ Starting Vite server..."
# Check if already running
if lsof -i :5173 > /dev/null; then
    echo "⚠️  Server seems to be already running on port 5173."
else
    npm run dev &
    echo "✅ Server started in background."
fi
