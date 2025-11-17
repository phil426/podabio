#!/bin/bash
# One-time script to add SSH key to server
# Run this ONCE manually - it will prompt for password, then future deployments will be automated

echo "Adding SSH key to poda.bio server..."
echo "You will be prompted for password once: *1318Redwood"
echo ""

PUBLIC_KEY="ssh-ed25519 AAAAC3NzaC1lZDI1NTE5AAAAINByOAUm+7uY9JLMixCgxCLUo7Rj9m1FcDQvZbvHZLbb poda.bio-deployment"

ssh -p 65002 u925957603@195.179.237.142 "mkdir -p ~/.ssh && chmod 700 ~/.ssh && echo '$PUBLIC_KEY' >> ~/.ssh/authorized_keys && chmod 600 ~/.ssh/authorized_keys && echo 'SSH key added successfully!'"

if [ $? -eq 0 ]; then
    echo ""
    echo "✅ SSH key added! Testing connection..."
    ssh -i ~/.ssh/id_ed25519_podabio -p 65002 u925957603@195.179.237.142 "echo '✅ Passwordless SSH works! Future deployments will be automated.'"
    
    if [ $? -eq 0 ]; then
        echo ""
        echo "🎉 Setup complete! You can now run ./deploy_poda_bio.sh without passwords."
    fi
else
    echo ""
    echo "❌ Failed to add SSH key. Please run manually:"
    echo "   ssh -p 65002 u925957603@195.179.237.142"
    echo "   Then run: mkdir -p ~/.ssh && echo '$PUBLIC_KEY' >> ~/.ssh/authorized_keys"
fi

