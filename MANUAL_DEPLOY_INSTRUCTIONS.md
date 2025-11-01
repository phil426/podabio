# Manual Deployment Instructions

## Current Issue
- Server has `shell_exec()` disabled (security restriction)
- GitHub HTTPS pull requires authentication
- No GitHub credentials configured on server

## Solution: Manual Deployment via SSH

Run these commands on your local machine:

```bash
ssh -p 65002 u810635266@82.198.236.40
# Enter password: @1318Redwood

cd /home/u810635266/domains/getphily.com/public_html/
git pull origin main

exit
```

## What Changed in This Deployment

1. ✅ Fixed circular buttons from squishing in podcast player
2. ✅ Removed volume picker (user requested)
3. ✅ Fixed audio playback - removed Web Audio API and waveform
4. ✅ Fixed CORS issues by removing `createMediaElementSource`

## After Deployment

Test at: https://getphily.com/phil624

Verify:
- ✅ No CORS errors in browser console
- ✅ Audio plays correctly
- ✅ Circular buttons stay round
- ✅ No waveform visualization (intentionally removed)
