#!/bin/bash
# Start Stripe CLI webhook forwarding for local development
# This forwards Stripe webhooks to your local PHP server

LOCAL_PORT=8080
WEBHOOK_PATH="/api/stripe/webhook"

echo "🔗 Starting Stripe CLI webhook forwarding..."
echo "   Forwarding to: http://localhost:${LOCAL_PORT}${WEBHOOK_PATH}"
echo ""
echo "Press Ctrl+C to stop"
echo ""

# Check if Stripe CLI is installed
if ! command -v stripe &> /dev/null; then
    echo "❌ Stripe CLI is not installed"
    echo ""
    echo "To install:"
    echo "  brew tap stripe/stripe-cli"
    echo "  brew install stripe/stripe-cli/stripe"
    echo ""
    echo "Then login:"
    echo "  stripe login"
    exit 1
fi

# Check if already logged in
if ! stripe config --list &> /dev/null; then
    echo "⚠️  Not logged in to Stripe CLI"
    echo ""
    echo "Please login first:"
    echo "  stripe login"
    exit 1
fi

# Start forwarding
stripe listen --forward-to localhost:${LOCAL_PORT}${WEBHOOK_PATH}

