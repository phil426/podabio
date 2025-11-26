#!/usr/bin/env bash

# PodaBio New Machine Setup Script
# Run this on a new machine to set up the development environment

set -e

echo "🚀 PodaBio New Machine Setup"
echo "============================="
echo ""

# Get the directory where this script is located
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "${SCRIPT_DIR}"

# Check if Homebrew is installed
if ! command -v brew &> /dev/null; then
    echo "❌ Homebrew is not installed."
    echo "   Install it from: https://brew.sh"
    exit 1
fi

echo "✅ Homebrew found"
echo ""

# Install PHP
echo "📦 Installing PHP..."
if ! command -v php &> /dev/null; then
    brew install php
    echo "   ✅ PHP installed"
else
    PHP_VERSION=$(php --version | head -1)
    echo "   ℹ️  PHP already installed: $PHP_VERSION"
fi
echo ""

# Install Node.js
echo "📦 Installing Node.js..."
if ! command -v node &> /dev/null; then
    brew install node
    echo "   ✅ Node.js installed"
else
    NODE_VERSION=$(node --version)
    echo "   ℹ️  Node.js already installed: $NODE_VERSION"
fi
echo ""

# Install npm dependencies
echo "📦 Installing npm dependencies..."
cd admin-ui
if [ ! -d "node_modules" ]; then
    npm install
    echo "   ✅ Dependencies installed"
else
    echo "   ℹ️  node_modules exists, running npm install to update..."
    npm install
fi
cd ..
echo ""

# Check for required config files
echo "🔍 Checking configuration files..."
echo ""

MISSING_CONFIGS=()

if [ ! -f "config/database.php" ]; then
    MISSING_CONFIGS+=("config/database.php")
    echo "   ⚠️  Missing: config/database.php"
    echo "      Create this file with your local database credentials"
    echo "      See docs/MULTI_MACHINE_WORKFLOW.md for template"
fi

if [ ! -f "config/oauth.php" ]; then
    MISSING_CONFIGS+=("config/oauth.php")
    echo "   ⚠️  Missing: config/oauth.php"
    echo "      Create this file with your OAuth credentials"
    echo "      Copy from your other machine or create new credentials"
fi

if [ ${#MISSING_CONFIGS[@]} -eq 0 ]; then
    echo "   ✅ All required config files present"
else
    echo ""
    echo "   📝 You need to create ${#MISSING_CONFIGS[@]} config file(s)"
    echo "      See docs/MULTI_MACHINE_WORKFLOW.md for details"
fi
echo ""

# Create local.php if it doesn't exist
if [ ! -f "config/local.php" ]; then
    echo "📝 Creating config/local.php for local development overrides..."
    cat > config/local.php << 'EOF'
<?php
/**
 * Local Development Configuration Overrides
 * This file is gitignored and specific to this machine
 * 
 * Override constants here for local development
 */

// Override APP_URL for local development
define('APP_URL', 'http://localhost:8080');

// Add any other local-specific overrides here
EOF
    echo "   ✅ Created config/local.php"
    echo "   💡 Edit this file to customize your local environment"
else
    echo "   ℹ️  config/local.php already exists"
fi
echo ""

# Summary
echo "✅ Setup complete!"
echo ""
echo "📋 Next steps:"
echo "   1. Create missing config files (if any listed above)"
echo "   2. Set up your local database (see docs/MULTI_MACHINE_WORKFLOW.md)"
echo "   3. Run ./dev-start.sh to start development servers"
echo ""
echo "📚 For more information, see: docs/MULTI_MACHINE_WORKFLOW.md"
echo ""

