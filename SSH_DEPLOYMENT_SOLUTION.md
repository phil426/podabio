# SSH Deployment Solution - How We Overcame Credential Issues

## Previous Solution Found

I found in the git history (commit `ca1a194`) that previous deployments used **`sshpass`** to automate SSH password authentication.

The previous deployment script (`deploy_git.sh`) used:
```bash
sshpass -p "$SSH_PASS" ssh -p $SSH_PORT -o StrictHostKeyChecking=no $SSH_HOST
```

## Current Status

✅ **`sshpass` is installed** on your system: `/opt/homebrew/bin/sshpass`

✅ **Deployment script updated** (`deploy_poda_bio.sh`) to use `sshpass` like before

## Solutions Available

### Option 1: Use sshpass (Updated Script)

The deployment script now uses `sshpass` automatically:
```bash
./deploy_poda_bio.sh
```

**Note:** If this still fails, you may need to:
1. Manually connect once to accept the host key:
   ```bash
   ssh -p 65002 u925957603@195.179.237.142
   # Enter password: [REDACTED]
   ```
2. Then the script should work

### Option 2: Set Up SSH Keys (Recommended for Future)

I've created a helper script to set up SSH keys for passwordless authentication:

```bash
./setup_ssh_key_poda_bio.sh
```

This will:
- Use your existing `~/.ssh/id_rsa_hostinger` key (or generate a new one)
- Copy it to the server using `sshpass`
- Enable passwordless authentication for future deployments

After setting up SSH keys, you can update `deploy_poda_bio.sh` to use key-based authentication instead of `sshpass`.

### Option 3: Manual First Connection

If automated methods fail, connect manually first:

```bash
ssh -p 65002 u925957603@195.179.237.142
# Enter password when prompted: [REDACTED]
# Type 'yes' to accept host key
# Then exit: exit
```

After this, `sshpass` should work in the deployment script.

## Troubleshooting

### Password Authentication Failing

If `sshpass` still fails, check:

1. **Password is correct**: Verify in Hostinger hPanel > SSH Access
2. **Host key accepted**: Manually connect once to accept it
3. **Server allows password auth**: Some servers require SSH keys only

### SSH Key Setup

If you want to use SSH keys (recommended):

1. **Check if key exists for Hostinger**:
   ```bash
   ls -la ~/.ssh/id_rsa_hostinger*
   ```

2. **Copy key to new server**:
   ```bash
   ./setup_ssh_key_poda_bio.sh
   ```

3. **Update deployment script** to use key instead of password:
   ```bash
   ssh -i ~/.ssh/id_rsa_hostinger -p $SSH_PORT $SSH_HOST
   ```

## Files Updated

- ✅ `deploy_poda_bio.sh` - Now uses `sshpass` like previous deployments
- ✅ `setup_ssh_key_poda_bio.sh` - Helper script to set up SSH keys

## Next Steps

1. **Try the updated deployment script**:
   ```bash
   ./deploy_poda_bio.sh
   ```

2. **If it fails**, manually connect once to accept host key:
   ```bash
   ssh -p 65002 u925957603@195.179.237.142
   ```

3. **For long-term**, set up SSH keys:
   ```bash
   ./setup_ssh_key_poda_bio.sh
   ```

