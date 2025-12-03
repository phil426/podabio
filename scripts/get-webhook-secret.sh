#!/bin/bash
# Get the webhook secret from Stripe CLI
# This script will start stripe listen briefly to capture the webhook secret

export PATH="$HOME/.local/bin:$PATH"

echo "🔑 Getting webhook secret from Stripe CLI..."
echo ""

# Start stripe listen in background and capture output
stripe listen --print-secret 2>&1 | while IFS= read -r line; do
    if [[ $line == *"whsec_"* ]]; then
        SECRET=$(echo "$line" | grep -o 'whsec_[^ ]*' | head -1)
        if [ -n "$SECRET" ]; then
            echo "✅ Webhook secret found:"
            echo ""
            echo "$SECRET"
            echo ""
            echo "📝 Update config/local.php with:"
            echo "   define('STRIPE_WEBHOOK_SECRET', '$SECRET');"
            echo ""
            exit 0
        fi
    fi
    echo "$line"
done

