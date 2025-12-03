#!/bin/bash
# Install Stripe CLI for local development

set -e

ARCH=$(uname -m)
OS="macos"

echo "🔧 Installing Stripe CLI..."
echo "   Architecture: $ARCH"
echo ""

# Determine correct architecture
if [ "$ARCH" = "arm64" ]; then
    ARCH_NAME="arm64"
elif [ "$ARCH" = "x86_64" ]; then
    ARCH_NAME="x86_64"
else
    echo "❌ Unsupported architecture: $ARCH"
    exit 1
fi

# Get latest version
echo "📦 Fetching latest version..."
LATEST_VERSION=$(curl -s https://api.github.com/repos/stripe/stripe-cli/releases/latest | grep '"tag_name":' | sed -E 's/.*"([^"]+)".*/\1/')

if [ -z "$LATEST_VERSION" ]; then
    echo "❌ Could not fetch latest version"
    exit 1
fi

echo "   Latest version: $LATEST_VERSION"
echo ""

# Download URL
FILENAME="stripe_${LATEST_VERSION}_${OS}_${ARCH_NAME}.tar.gz"
DOWNLOAD_URL="https://github.com/stripe/stripe-cli/releases/download/${LATEST_VERSION}/${FILENAME}"

echo "📥 Downloading Stripe CLI..."
curl -L -o /tmp/stripe-cli.tar.gz "$DOWNLOAD_URL"

echo "📦 Extracting..."
cd /tmp
tar -xzf stripe-cli.tar.gz

echo "📝 Installing to /usr/local/bin..."
sudo mv stripe /usr/local/bin/

# Cleanup
rm stripe-cli.tar.gz

echo ""
echo "✅ Stripe CLI installed successfully!"
echo ""
echo "Version:"
stripe --version
echo ""
echo "Next steps:"
echo "1. Login: stripe login"
echo "2. Start webhook forwarding: ./scripts/start-stripe-webhook.sh"

