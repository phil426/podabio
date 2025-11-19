# Archive Directory

This directory contains archived files that are no longer actively used but kept for reference or emergency fallback.

## Files

### `editor.php`

- **Archived Date**: 2025-01-XX
- **Original Purpose**: Legacy admin panel (9,359 lines)
- **Reason for Archive**: Replaced by Lefty dashboard (`admin/userdashboard.php`)
- **Status**: Deprecated - all traffic redirects to Lefty
- **Emergency Access**: Can be restored by moving back to root if needed

#### Technical Details

- **Original File Size**: 9,359 lines
- **Inline CSS**: ~2,840 lines
- **Inline JavaScript**: ~5,210 lines
- **Dependencies**: 
  - Draggable.js (drag-and-drop library)
  - Croppie (image cropping)
  - Font Awesome icons
  - Various PHP helper functions

#### Migration Status

- ✅ All functionality has been migrated to Lefty (React-based admin dashboard)
- ✅ Feature parity verified - see `docs/editor-php-diagnostic-summary.md`
- ✅ Theme management fully migrated
- ✅ Widget management fully migrated
- ✅ Social icon management fully migrated
- ✅ Account management migrated to AccountWorkspace (`/account/profile`)

#### How to Restore (Emergency Only)

If you need to restore `editor.php` for emergency access:

```bash
# Move back to root
mv archive/editor.php editor.php

# Update admin/userdashboard.php to restore fallback:
# Uncomment the fallback redirect:
# if (!feature_flag('admin_new_experience')) {
#     redirect('/editor.php');
#     exit;
# }
```

**Note**: The redirect stub at `editor.php` in root will redirect all traffic to Lefty. To use the full legacy editor, you must restore the original file.

---

## Notes

- Original file contained extensive inline CSS and JavaScript that were candidates for extraction
- All theme-related logic has been migrated to Lefty's API and React components
- The file is kept as a backup in case of critical issues with Lefty
- Documentation of the migration is in `docs/editor-php-diagnostic-report.md`

---

**Last Updated**: 2025-01-XX

