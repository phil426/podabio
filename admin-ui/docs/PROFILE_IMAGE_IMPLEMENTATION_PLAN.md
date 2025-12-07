# Profile Image Refactor Implementation Plan

## Overview

This document provides a step-by-step implementation plan to separate profile image concerns into distinct variables. Follow this plan sequentially to avoid breaking changes.

**Important Note**: This refactor addresses:
- `profile_image` (page profile image)
- `cover_image` (NEW - for theme generation)
- `cover_image_url` (existing - for podcast artwork, should remain separate)
- `avatar_url` (user account avatar)

The existing `cover_image_url` field for podcast artwork should remain unchanged and separate from both `profile_image` and the new `cover_image` field.

## Prerequisites

- Database backup
- Code review of current profile image usage
- Understanding of the audit findings (see `PROFILE_IMAGE_AUDIT.md`)
- Understanding of the proposed schema (see `PROFILE_IMAGE_SCHEMA_PROPOSAL.md`)

## Phase 1: Database Migration

### Step 1.1: Create Migration File
- [ ] Create `database/migrations/add_cover_image_to_pages.php`
- [ ] Add `cover_image VARCHAR(255) NULL` column to `pages` table
- [ ] Add index if needed: `CREATE INDEX idx_pages_cover_image ON pages(cover_image) WHERE cover_image IS NOT NULL`
- [ ] Test migration on development database

### Step 1.2: Update Database Schema Documentation
- [ ] Document new `cover_image` field in database schema docs
- [ ] Note that `profile_image` remains unchanged
- [ ] Clarify that `cover_image_url` (podcast artwork) is separate from new `cover_image` (theme generation)

**Files to Create/Modify**:
- `database/migrations/add_cover_image_to_pages.php`

---

## Phase 2: Backend API Updates

### Step 2.1: Update Page API to Handle `cover_image`
- [ ] Update `api/page.php` to accept `cover_image` in POST data
- [ ] Add validation for `cover_image` (URL format, max length)
- [ ] Add `cover_image` to page snapshot response
- [ ] Update page update logic to handle `cover_image` separately from `profile_image`

**Files to Modify**:
- `api/page.php` (lines ~252-715, add cover_image handling)

### Step 2.2: Update Upload API
- [ ] Add `cover_image` as valid `imageType` in `api/upload.php`
- [ ] Update logic to set `cover_image` field when `imageType === 'cover'`
- [ ] Ensure `cover_image` uploads don't affect `profile_image`

**Files to Modify**:
- `api/upload.php` (line ~143, add 'cover' type)

### Step 2.3: Update Page Snapshot Response
- [ ] Ensure `cover_image` is included in page snapshot
- [ ] Verify TypeScript types match backend response

**Files to Modify**:
- `api/page.php` (snapshot generation)
- `admin-ui/src/api/types.ts` (add `cover_image` to PageSnapshot type)

---

## Phase 3: Frontend API Client Updates

### Step 3.1: Update TypeScript Types
- [ ] Add `cover_image: string | null` to `PageSnapshot` interface
- [ ] Update `PageSnapshotResponse` type
- [ ] Add `cover_image` to any relevant type definitions

**Files to Modify**:
- `admin-ui/src/api/types.ts`

### Step 3.2: Add Cover Image API Functions
- [ ] Add `uploadCoverImage(file: File)` function to `admin-ui/src/api/uploads.ts`
- [ ] Add `removeCoverImage()` function to `admin-ui/src/api/page.ts`
- [ ] Ensure these functions update `cover_image` field, not `profile_image`

**Files to Modify**:
- `admin-ui/src/api/uploads.ts` (add `uploadCoverImage`)
- `admin-ui/src/api/page.ts` (add `removeCoverImage`)

### Step 3.3: Update Page Snapshot Hook
- [ ] Verify `usePageSnapshot()` returns `cover_image` in response
- [ ] Test that snapshot includes both `profile_image` and `cover_image`

**Files to Verify**:
- `admin-ui/src/api/page.ts` (usePageSnapshot hook)

---

## Phase 4: Theme Wizard Updates

### Step 4.1: Update Theme Wizard State
- [ ] Review `useThemeWizardState` hook
- [ ] Ensure `coverImageUrl` is tracked separately from any profile image state
- [ ] Verify state doesn't reference `profile_image` field

**Files to Review**:
- `admin-ui/src/components/panels/themes/hooks/useThemeWizardState.ts`

### Step 4.2: Fix Theme Generation to Use `cover_image`
- [ ] **CRITICAL**: Update `PodcastThemeGenerator.tsx:737`
  - Change: `pageUpdates.profile_image = coverImageUrl;`
  - To: `pageUpdates.cover_image = coverImageUrl;`
- [ ] Remove any other assignments of `profile_image` from theme generation
- [ ] Verify theme generation only writes to `cover_image`

**Files to Modify**:
- `admin-ui/src/components/panels/themes/PodcastThemeGenerator.tsx` (line 737)

### Step 4.3: Update Preview CSS Variables
- [ ] Update `PodcastThemeGenerator.tsx:671` to set `--cover-image-url` instead of `--preview-profile-image-url`
- [ ] Or keep `--preview-profile-image-url` but ensure it doesn't affect saved `profile_image`
- [ ] Update `setProfileImageInPreview` to use cover image

**Files to Modify**:
- `admin-ui/src/components/panels/themes/PodcastThemeGenerator.tsx` (lines 111-115, 671)

### Step 4.4: Update Build Page Updates
- [ ] Update `buildPageUpdates.ts` to handle `cover_image` separately
- [ ] Ensure theme data maps to `cover_image`, not `profile_image`
- [ ] Verify `profile_image_*` styling fields still map correctly

**Files to Modify**:
- `admin-ui/src/utils/buildPageUpdates.ts`

### Step 4.5: Update Generate Preview CSS Vars
- [ ] Update `generatePreviewCSSVars.ts` to accept `coverImage` parameter
- [ ] Set `--cover-image-url` CSS variable
- [ ] Keep `--preview-profile-image-url` for backward compatibility during transition

**Files to Modify**:
- `admin-ui/src/utils/generatePreviewCSSVars.ts` (line 137)

---

## Phase 5: Account Avatar Separation

### Step 5.1: Remove Fallback Logic in LeftyProfileSection
- [ ] Update `LeftyProfileSection.tsx:35-36`
  - Remove: `const profileImage = snapshot?.page?.profile_image ?? null;`
  - Remove: `const avatarUrl = profileImage ?? account?.avatar_url ?? null;`
  - Change to: `const avatarUrl = account?.avatar_url ?? null;`
- [ ] Verify component only uses `avatar_url`, never `profile_image`

**Files to Modify**:
- `admin-ui/src/components/layout/LeftyProfileSection.tsx` (lines 35-36)

### Step 5.2: Verify TopBar is Correct
- [ ] Confirm `TopBar.tsx:37` only uses `account?.avatar_url`
- [ ] No changes needed if already correct

**Files to Verify**:
- `admin-ui/src/components/layout/TopBar.tsx` (line 37)

### Step 5.3: Add Account Avatar Upload (if needed)
- [ ] If account avatar upload is missing, add it to account profile UI
- [ ] Ensure it updates `users.avatar_url`, not `pages.profile_image`

**Files to Review**:
- `admin-ui/src/components/account/ProfileSettings.tsx` (if exists)
- `api/account/profile.php`

---

## Phase 6: Preview Component Updates

### Step 6.1: Update ThemePreview Component
- [ ] Review `ThemePreview.tsx:352-369` (preview image URL handling)
- [ ] Ensure it reads `--preview-profile-image-url` or `--cover-image-url` correctly
- [ ] Verify preview doesn't affect saved `profile_image`

**Files to Review**:
- `admin-ui/src/components/panels/themes/preview/ThemePreview.tsx` (lines 352-369)

### Step 6.2: Update ModalPreview Component
- [ ] Update `ModalPreview.tsx:170` to use CSS variable instead of `page?.profile_image`
- [ ] Change from: `const profileImage = page?.profile_image;`
- [ ] To: Read from CSS variable `--preview-profile-image-url` or `--cover-image-url`
- [ ] Ensure consistency with ThemePreview

**Files to Modify**:
- `admin-ui/src/components/panels/themes/preview/ModalPreview.tsx` (line 170)

### Step 6.3: Update Preview Renderer
- [ ] Review `previewRenderer.ts:266-330` (profile image CSS var generation)
- [ ] Ensure it handles both `profile_image` and `cover_image` correctly
- [ ] Update to set appropriate CSS variables based on context

**Files to Review**:
- `admin-ui/src/components/panels/themes/utils/previewRenderer.ts` (lines 266-330)

---

## Phase 7: CSS Variable Renaming (Optional)

### Step 7.1: Rename Preview CSS Variable
- [ ] Rename `--preview-profile-image-url` to `--preview-page-profile-image-url`
- [ ] Update all component references
- [ ] Or keep old name for backward compatibility

**Files to Modify** (if renaming):
- `admin-ui/src/components/panels/themes/PodcastThemeGenerator.tsx`
- `admin-ui/src/components/panels/themes/preview/ThemePreview.tsx`
- `admin-ui/src/utils/generatePreviewCSSVars.ts`

### Step 7.2: Rename Profile Image CSS Variables (Optional)
- [ ] Consider renaming `--profile-image-*` to `--page-profile-image-*` for clarity
- [ ] Update all references in components and CSS
- [ ] Update `page.php` template

**Files to Modify** (if renaming):
- `admin-ui/src/components/panels/themes/preview/ThemePreview.tsx`
- `admin-ui/src/components/panels/themes/preview/ModalPreview.tsx`
- `admin-ui/src/utils/generatePreviewCSSVars.ts`
- `page.php`

---

## Phase 8: Testing

### Step 8.1: Test Theme Wizard
- [ ] User has existing profile image
- [ ] User generates theme with cover image
- [ ] Verify profile image remains unchanged
- [ ] Verify cover image is saved separately
- [ ] Verify preview shows cover image correctly

### Step 8.2: Test Account Avatar
- [ ] User has no `avatar_url`
- [ ] User has `profile_image`
- [ ] Verify UI shows placeholder, not `profile_image`
- [ ] Verify account avatar upload works

### Step 8.3: Test Profile Image Updates
- [ ] User updates profile image via ProfileImageSection
- [ ] Verify `profile_image` updates, `cover_image` unchanged
- [ ] Verify public page displays updated profile image

### Step 8.4: Test Preview
- [ ] User changes image in preview
- [ ] Verify preview updates without saving
- [ ] Verify saved page unchanged until user saves

### Step 8.5: Test All Four Images Independently
- [ ] Set different images for:
  - Profile (public page) - `profile_image`
  - Cover (theme generation) - `cover_image` (NEW)
  - Podcast Cover (podcast artwork) - `cover_image_url` (existing, separate)
  - Avatar (account UI) - `avatar_url`
- [ ] Verify all four work independently
- [ ] Verify no cross-contamination
- [ ] Verify podcast player uses `cover_image_url`, not `profile_image` or `cover_image`

---

## Phase 9: Validation & Cleanup

### Step 9.1: Add Validation Rules
- [ ] Add validation in Theme Wizard: prevent setting `profile_image`
- [ ] Add validation in account UI: prevent using `profile_image` as fallback
- [ ] Add validation to ensure podcast player uses `cover_image_url`, not `profile_image` as fallback
- [ ] Add TypeScript types to enforce separation
- [ ] Document that `cover_image_url` (podcast) is separate from `cover_image` (theme generation)

**Files to Modify**:
- `admin-ui/src/components/panels/themes/PodcastThemeGenerator.tsx`
- `admin-ui/src/components/layout/LeftyProfileSection.tsx`

### Step 9.2: Remove Deprecated Code
- [ ] Remove any fallback logic that uses `profile_image` for account avatar
- [ ] Remove any code that sets `profile_image` from cover image
- [ ] Clean up unused CSS variables (if any)

### Step 9.3: Update Documentation
- [ ] Update API documentation for `cover_image` field
- [ ] Update component documentation
- [ ] Update developer guide
- [ ] Add migration notes for existing users

**Files to Update**:
- API documentation
- Component README files
- Developer guide

---

## Phase 10: Deployment

### Step 10.1: Pre-Deployment Checklist
- [ ] All tests passing
- [ ] Database migration tested
- [ ] Backward compatibility verified
- [ ] Code review completed
- [ ] Documentation updated

### Step 10.2: Deployment Steps
1. [ ] Deploy database migration
2. [ ] Deploy backend API changes
3. [ ] Deploy frontend changes
4. [ ] Verify no errors in production logs
5. [ ] Monitor for any issues

### Step 10.3: Post-Deployment Verification
- [ ] Verify theme wizard works correctly
- [ ] Verify profile image updates work
- [ ] Verify account avatar displays correctly
- [ ] Verify no data loss occurred
- [ ] Monitor error logs for 24 hours

---

## Rollback Plan

If issues arise:

1. **Database Rollback**: 
   - `cover_image` column can be dropped if needed
   - `profile_image` remains unchanged

2. **Code Rollback**:
   - Revert frontend changes
   - Revert backend API changes
   - System returns to previous state

3. **Data Migration** (if needed):
   - If `cover_image` was populated, can copy to `profile_image` if needed
   - Or leave `cover_image` for future use

---

## Success Criteria

- [ ] Theme Wizard no longer overwrites `profile_image`
- [ ] Account avatar never uses `profile_image` as fallback
- [ ] All four image types work independently:
  - `profile_image` (page profile)
  - `cover_image` (theme generation - NEW)
  - `cover_image_url` (podcast artwork - existing, separate)
  - `avatar_url` (account avatar)
- [ ] Podcast player uses `cover_image_url`, not `profile_image` as fallback
- [ ] No breaking changes for existing users
- [ ] All tests passing
- [ ] Documentation updated

---

## Notes

- This refactor is designed to be backward compatible
- Existing `profile_image` data remains valid
- New `cover_image` field starts as NULL for all pages
- Gradual migration is possible (users can set `cover_image` over time)
- No data loss should occur

---

## Estimated Timeline

- Phase 1 (Database): 1-2 hours
- Phase 2 (Backend API): 2-3 hours
- Phase 3 (Frontend API): 1-2 hours
- Phase 4 (Theme Wizard): 3-4 hours
- Phase 5 (Account Avatar): 1 hour
- Phase 6 (Preview): 2-3 hours
- Phase 7 (CSS Variables): 1-2 hours (optional)
- Phase 8 (Testing): 2-3 hours
- Phase 9 (Validation): 1-2 hours
- Phase 10 (Deployment): 1 hour

**Total Estimated Time**: 15-22 hours

---

## Risk Assessment

**Low Risk**:
- Database migration (additive, no data loss)
- Backend API (additive, backward compatible)

**Medium Risk**:
- Theme Wizard changes (core functionality)
- Preview component updates (user-facing)

**Mitigation**:
- Thorough testing in development
- Gradual rollout if possible
- Monitor error logs closely
- Have rollback plan ready

