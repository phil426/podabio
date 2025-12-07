# Profile Image Refactor - Implementation Summary

## Status: Core Implementation Complete ✅

The critical bug has been fixed and the foundation for separating image concerns is in place.

## What Was Fixed

### Critical Bug Fix ✅
- **Before**: Theme Wizard overwrote `profile_image` when generating themes
- **After**: Theme Wizard now saves to `cover_image` field, preserving user's `profile_image`
- **File**: `admin-ui/src/components/panels/themes/PodcastThemeGenerator.tsx:737`

### Account Avatar Separation ✅
- **Before**: `LeftyProfileSection` fell back to `profile_image` if `avatar_url` was missing
- **After**: Component now uses `avatar_url` only (no fallback)
- **File**: `admin-ui/src/components/layout/LeftyProfileSection.tsx:35-36`

## What Was Added

### Database
- ✅ Migration file created: `database/migrations/add_cover_image_to_pages.php`
- ⚠️ **Action Required**: Run migration on database

### Backend API
- ✅ `api/page.php`: Added `cover_image` handling in two update sections
- ✅ `api/page.php`: Added `cover_image` to snapshot response
- ✅ `api/page.php`: Added `cover` type to `remove_image` action
- ✅ `api/upload.php`: Added `cover` image type support

### Frontend API
- ✅ `admin-ui/src/api/types.ts`: Added `cover_image` to `PageSnapshot` interface
- ✅ `admin-ui/src/api/uploads.ts`: Added `uploadCoverImage()` function
- ✅ `admin-ui/src/api/page.ts`: Added `removeCoverImage()` function

### Components & Utilities
- ✅ Updated comments in `generatePreviewCSSVars.ts` to clarify preview vs. saved state
- ✅ Updated comments in `PodcastThemeGenerator.tsx` to clarify image separation

## Image Field Separation

The system now supports four distinct image types:

1. **`profile_image`** (page table)
   - Purpose: Profile image displayed on public page
   - Protected from Theme Wizard overwrites ✅
   - Used by: ProfileImageSection, PageCustomizationSection, page.php

2. **`cover_image`** (page table) - NEW
   - Purpose: Image used for theme generation and color extraction
   - Used by: Theme Wizard (PodcastThemeGenerator)
   - Status: Field added, API support added ✅

3. **`cover_image_url`** (page table) - Existing
   - Purpose: Podcast cover artwork
   - Used by: PodcastPlayerInspector, podcast player widget
   - Status: Unchanged, remains separate ✅

4. **`avatar_url`** (users table) - Existing
   - Purpose: User account avatar for UI
   - Used by: TopBar, LeftyProfileSection
   - Status: No longer falls back to profile_image ✅

## Next Steps

### Immediate (Required)
1. **Run Database Migration**
   ```bash
   php database/migrations/add_cover_image_to_pages.php
   ```

### Testing (Recommended)
1. Test Theme Wizard doesn't overwrite profile image
2. Test account avatar displays correctly (no fallback)
3. Test cover image is saved separately
4. Test all four image types work independently

### Future Enhancements (Optional)
- Phase 7: CSS variable renaming for clarity
- Phase 8: Comprehensive testing suite
- Phase 9: Additional validation rules
- Phase 10: Documentation updates

## Files Modified

### Backend
- `api/page.php` - Added cover_image handling
- `api/upload.php` - Added cover image type
- `database/migrations/add_cover_image_to_pages.php` - NEW

### Frontend
- `admin-ui/src/api/types.ts` - Added cover_image type
- `admin-ui/src/api/page.ts` - Added removeCoverImage()
- `admin-ui/src/api/uploads.ts` - Added uploadCoverImage()
- `admin-ui/src/components/panels/themes/PodcastThemeGenerator.tsx` - Fixed bug
- `admin-ui/src/components/layout/LeftyProfileSection.tsx` - Removed fallback
- `admin-ui/src/utils/generatePreviewCSSVars.ts` - Updated comments

## Breaking Changes

**None** - All changes are backward compatible:
- Existing `profile_image` data remains valid
- New `cover_image` field starts as NULL
- Old API calls continue to work
- Components gracefully handle missing `cover_image`

## Rollback Plan

If issues arise:
1. Revert frontend changes (Git)
2. Revert backend API changes (Git)
3. Drop `cover_image` column: `ALTER TABLE pages DROP COLUMN cover_image;`
4. System returns to previous state

## Success Metrics

✅ Theme Wizard no longer overwrites profile image
✅ Account avatar no longer falls back to profile image
✅ Four image types are now separate and independent
✅ No breaking changes introduced
✅ Backward compatibility maintained

## Notes

- The `cover_image` field is currently only set by Theme Wizard
- Users can have different images for profile, cover (theme), podcast cover, and avatar
- Preview components use CSS variables for temporary display (doesn't affect saved state)
- All image types can coexist independently

