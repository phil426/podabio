# Uploads Handling Guide

## Overview

User-uploaded files (profile images, backgrounds, thumbnails, etc.) are stored in the `/uploads` directory. This directory is **gitignored** because:

1. User content should not be in version control
2. Upload files can be large and would bloat the repository
3. Production and development environments have different uploads

## Directory Structure

```
uploads/
├── profiles/      # User profile images
├── backgrounds/   # Page background images
├── thumbnails/    # Widget thumbnails
├── blog/          # Blog post images
└── media/         # Media library files
```

## Deployment Behavior

The deployment script (`deploy_poda_bio.sh`) **preserves the uploads directory** during deployment:

1. Before `git reset --hard`, it backs up the uploads folder
2. After git operations, it restores the uploads folder
3. This ensures production uploads are never lost during deployment

## Syncing Uploads

### Download Production Uploads to Local

For local development with production images:

```bash
./scripts/sync-uploads.sh pull
```

This downloads all production uploads to your local `/uploads` directory using rsync.

### Upload Local Files to Production

**CAUTION**: Only use this if you have files that need to go to production:

```bash
./scripts/sync-uploads.sh push
```

This uploads local files to production (won't delete existing production files).

### Check Upload Status

See file counts and sizes for both local and production:

```bash
./scripts/sync-uploads.sh status
```

## Best Practices

1. **Never commit uploads to git** - The `.gitignore` prevents this, but be careful with force commands

2. **Regularly backup production uploads** - Consider setting up automated backups

3. **Use sync-uploads.sh pull** - When you need production images for local testing

4. **Don't sync unnecessary files** - Only pull what you need for development

## Future Improvements

For larger scale or enterprise deployments, consider:

1. **Cloud Storage (S3/R2)** - Store uploads in cloud storage instead of filesystem
2. **CDN Integration** - Serve uploads through a CDN for better performance
3. **Automated Backups** - Regular automated backups of the uploads directory

## Troubleshooting

### Uploads Missing After Deployment

The deployment script should preserve uploads. If uploads are missing:

1. Check if the backup was created: Look for `/tmp/podabio_uploads_backup_*` on the server
2. Restore from backup if available
3. Check server logs for errors

### Permission Issues

If uploads fail to save:

```bash
# On production server
chmod 755 uploads/
chmod 755 uploads/profiles/
chmod 755 uploads/backgrounds/
chmod 755 uploads/thumbnails/
chmod 755 uploads/blog/
chmod 755 uploads/media/
```

### rsync Not Found

If rsync is not available:

```bash
# On macOS
brew install rsync

# On Ubuntu/Debian
sudo apt-get install rsync
```


