#!/bin/bash
# Setup SSH Key for poda.bio Server
# This will copy your SSH key to the server for passwordless authentication

set -e

SSH_HOST="u925957603@195.179.237.142"
SSH_PORT="65002"
SSH_PASS='[REDACTED]'

echo "=========================================="
echo "SSH Key Setup for poda.bio"
echo "=========================================="
echo ""

# Check if sshpass is installed
if ! command -v sshpass &> /dev/null; then
    echo "❌ sshpass not found. Please install it:"
    if [[ "$OSTYPE" == "darwin"* ]]; then
        echo "   brew install hudochenkov/sshpass/sshpass"
    else
        echo "   sudo apt-get install sshpass"
    fi
    exit 1
fi

# Check if SSH key exists
if [ -f ~/.ssh/id_rsa_hostinger.pub ]; then
    SSH_KEY_FILE=~/.ssh/id_rsa_hostinger.pub
    echo "✅ Found existing Hostinger SSH key: $SSH_KEY_FILE"
elif [ -f ~/.ssh/id_rsa.pub ]; then
    SSH_KEY_FILE=~/.ssh/id_rsa.pub
    echo "✅ Found SSH key: $SSH_KEY_FILE"
else
    echo "❌ No SSH public key found."
    echo "   Generate one with: ssh-keygen -t ed25519 -C 'your_email@example.com'"
    exit 1
fi

echo ""
echo "📋 Copying SSH key to server..."
echo "   This will allow passwordless authentication in the future."
echo ""

# Copy SSH key using sshpass
sshpass -p "$SSH_PASS" ssh-copy-id -p $SSH_PORT -o StrictHostKeyChecking=accept-new $SSH_HOST

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ SSH key successfully copied!"
    echo ""
    echo "You can now use SSH without entering a password:"
    echo "  ssh -p $SSH_PORT $SSH_HOST"
    echo ""
    echo "The deployment script will also work without password prompts."
else
    echo ""
    echo "❌ Failed to copy SSH key."
    echo "   You may need to manually connect first:"
    echo "   ssh -p $SSH_PORT $SSH_HOST"
    echo "   (Enter password: $SSH_PASS)"
    exit 1
fi











