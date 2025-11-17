#!/bin/bash
# Manual SSH Key Setup for poda.bio
# Run this script and enter password when prompted

set -e

SSH_HOST="u925957603@195.179.237.142"
SSH_PORT="65002"
SSH_KEY_FILE="$HOME/.ssh/id_ed25519_podabio.pub"

echo "=========================================="
echo "SSH Key Setup for poda.bio"
echo "=========================================="
echo ""

if [ ! -f "$SSH_KEY_FILE" ]; then
    echo "❌ SSH key not found: $SSH_KEY_FILE"
    echo "   Generating new key..."
    ssh-keygen -t ed25519 -C "poda.bio-deployment" -f ~/.ssh/id_ed25519_podabio -N ""
    echo "✅ SSH key generated"
fi

echo "📋 Your public key:"
cat "$SSH_KEY_FILE"
echo ""
echo "📤 Copying key to server..."
echo "   You will be prompted for password: ?g-2A+mJV&a%KP$"
echo ""

# Try ssh-copy-id with manual password entry
ssh-copy-id -i "$SSH_KEY_FILE" -p $SSH_PORT $SSH_HOST

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ SSH key successfully copied!"
    echo ""
    echo "Testing connection..."
    ssh -i ~/.ssh/id_ed25519_podabio -p $SSH_PORT $SSH_HOST "echo '✅ Connection successful!'"
    
    if [ $? -eq 0 ]; then
        echo ""
        echo "🎉 SSH key authentication is now set up!"
        echo "   You can now run ./deploy_poda_bio.sh without password prompts."
    fi
else
    echo ""
    echo "❌ Failed to copy SSH key automatically."
    echo ""
    echo "Manual setup:"
    echo "1. Copy this public key:"
    cat "$SSH_KEY_FILE"
    echo ""
    echo "2. SSH into server:"
    echo "   ssh -p $SSH_PORT $SSH_HOST"
    echo ""
    echo "3. Run these commands on the server:"
    echo "   mkdir -p ~/.ssh"
    echo "   chmod 700 ~/.ssh"
    echo "   echo '$(cat $SSH_KEY_FILE)' >> ~/.ssh/authorized_keys"
    echo "   chmod 600 ~/.ssh/authorized_keys"
    echo ""
    echo "4. Then test: ssh -i ~/.ssh/id_ed25519_podabio -p $SSH_PORT $SSH_HOST"
fi

