# Profile Image Usage Audit

## Executive Summary

The `profile_image` field is currently used for multiple distinct purposes, creating conflicts and confusion:
1. **Page Profile Image** - Displayed on the public page (page.php)
2. **Theme Wizard Cover Image** - Used for color extraction and theme generation
3. **User Account Avatar** - Displayed in UI (TopBar, LeftyProfileSection) - currently uses `avatar_url` but falls back to `profile_image`
4. **Preview/Modal Display** - Used in ThemePreview and ModalPreview components
5. **Podcast Cover Image** - In some circumstances, `profile_image` may serve as the podcast cover image or podcast artwork

This multi-purpose usage causes issues when:
- Theme Wizard sets `profile_image` from a cover image, overwriting the user's intended profile image
- User account avatar needs to be separate from page profile image
- Preview needs temporary image without affecting saved page data
- Podcast cover image (`cover_image_url`) and profile image may be confused or used interchangeably

## Current Usage Inventory

### 1. Database/API Fields

#### `profile_image` (page table)
- **Location**: `api/page.php`, `admin-ui/src/api/page.ts`
- **Purpose**: Stores the profile image URL for the public page
- **Update Methods**:
  - `updatePageAppearance({ profile_image: url })` - `admin-ui/src/api/page.ts:100`
  - `uploadProfileImage()` - `admin-ui/src/api/uploads.ts:11`
  - `removeProfileImage()` - `admin-ui/src/api/page.ts:219`
  - Theme Wizard sets it: `PodcastThemeGenerator.tsx:737` - `pageUpdates.profile_image = coverImageUrl`
- **Styling Fields** (also in page table):
  - `profile_image_radius` (0-50)
  - `profile_image_size` (40-200px or enum: small/medium/large)
  - `profile_image_effect` (none/glow/shadow)
  - `profile_image_shadow_color`, `profile_image_shadow_intensity`, `profile_image_shadow_depth`, `profile_image_shadow_blur`
  - `profile_image_glow_color`, `profile_image_glow_width`
  - `profile_image_border_color`, `profile_image_border_width`
  - `profile_image_shape` (circle/rounded/square) - legacy
  - `profile_image_shadow` (none/subtle/strong) - legacy
  - `profile_image_spacing_top`, `profile_image_spacing_bottom`

#### `avatar_url` (users table)
- **Location**: `api/account/profile.php`, `admin-ui/src/api/types.ts:109`
- **Purpose**: User account avatar (separate from page profile)
- **Update Methods**: Account profile API
- **Usage**: 
  - `TopBar.tsx:37` - `account?.avatar_url`
  - `LeftyProfileSection.tsx:36` - Falls back to `profile_image` if `avatar_url` is null

#### `cover_image_url` (page table)
- **Location**: `api/page.php`, `admin-ui/src/api/types.ts:32`
- **Purpose**: Podcast cover image/artwork (separate field from `profile_image`)
- **Update Methods**:
  - Set from RSS feed: `api/page.php:419` - `$updateData['cover_image_url'] = $feedData['cover_image']`
  - Included in page snapshot: `api/page.php:128`
- **Usage**:
  - `PodcastPlayerInspector.tsx:742-743` - Displays podcast cover
  - `page.php:745-746` - Displays in podcast player "Now Playing" section
  - `usePodcastThemePrompt.ts:29,46,64` - Used to determine if podcast data exists
  - `WidgetRenderer.php:669-670` - Used in podcast widget rendering
- **Relationship to `profile_image`**: 
  - These are separate fields, but in some circumstances `profile_image` may be used as a fallback or serve as the podcast cover image
  - This creates ambiguity about which image should be used for podcast display

### 2. CSS Variables

#### `--preview-profile-image-url`
- **Purpose**: Temporary image URL for preview (doesn't affect saved page)
- **Set By**:
  - `PodcastThemeGenerator.tsx:115` - `setProfileImageInPreview()`
  - `PodcastThemeGenerator.tsx:671` - In `updatePreview()`
  - `generatePreviewCSSVars.ts:137` - Uses `coverImageUrl` parameter
- **Used By**:
  - `ThemePreview.tsx:353-369` - Updates iframe profile images
  - `ModalPreview.tsx:170` - Uses `page?.profile_image` (not the CSS var)

#### `--profile-image-*` (styling variables)
- `--profile-image-radius` (0-50%)
- `--profile-image-size` (px)
- `--profile-image-border-width` (px)
- `--profile-image-border-color` (hex)
- `--profile-image-box-shadow` (CSS shadow)
- **Set By**:
  - `generatePreviewCSSVars.ts:108-112`
  - `PodcastThemeGenerator.tsx:642-646`
  - `previewRenderer.ts:268-330` (from uiState)
- **Used By**:
  - `ThemePreview.tsx:856-870` - Applies to profile image
  - `ModalPreview.tsx:165-187` - Applies to profile image
  - `page.php:613-621, 1099-1108` - Applies to profile image

### 3. React Components

#### `PodcastThemeGenerator.tsx`
- **Lines 66, 473, 565, 671, 737**: Uses `coverImageUrl` (from props) for:
  - Color extraction
  - Theme generation
  - Preview display (`--preview-profile-image-url`)
  - **PROBLEM**: Line 737 sets `pageUpdates.profile_image = coverImageUrl` - overwrites user's profile image

#### `ThemePreview.tsx`
- **Lines 352-369**: Reads `--preview-profile-image-url` to update iframe
- **Lines 804-886**: Displays profile image from `page?.profile_image`
- **Lines 856-870**: Applies CSS variables for styling

#### `ModalPreview.tsx`
- **Lines 164-189**: Uses `page?.profile_image` directly (not CSS var)
- **Lines 165-187**: Applies CSS variables for styling

#### `ProfileImageSection.tsx`
- **Line 38**: Reads `page?.profile_image`
- **Line 43**: Updates via `updatePageAppearance({ profile_image: url })`
- **Lines 78-315**: Full UI for uploading, styling profile image

#### `PageCustomizationSection.tsx`
- **Lines 138-160**: Profile image upload UI
- **Line 148**: Uses `profileImage` from `page?.profile_image`

#### `ProfileInspector.tsx`
- **Line 49**: Reads `page?.profile_image`
- **Lines 110, 128, 143**: Upload/remove/update profile image
- **Lines 273-276**: Updates profile image styling fields

#### `LeftyProfileSection.tsx`
- **Lines 35-36**: **FALLBACK LOGIC**: `profileImage ?? account?.avatar_url`
  - Uses page `profile_image` if available, otherwise account `avatar_url`
  - This creates confusion about which image should be shown

#### `TopBar.tsx`
- **Line 37**: Uses `account?.avatar_url` only (no fallback to profile_image)

### 4. Backend/PHP Files

#### `page.php`
- **Lines 606-623, 1093-1110**: Renders profile image from `$page['profile_image']`
- Applies CSS variables for styling

#### `api/page.php`
- **Lines 277-385, 672-715**: Handles all `profile_image_*` field updates
- **Line 143**: `upload.php` sets `profile_image` when `imageType === 'profile'`

#### `api/upload.php`
- **Line 143**: Determines update field: `profile_image` for `imageType === 'profile'`

### 5. API Functions

#### `admin-ui/src/api/page.ts`
- `updatePageAppearance()` - Updates `profile_image` field
- `removeProfileImage()` - Removes profile image
- `usePageAppearanceMutation()` - React Query hook

#### `admin-ui/src/api/uploads.ts`
- `uploadProfileImage()` - Uploads and sets `profile_image`

#### `admin-ui/src/api/account.ts`
- `fetchAccountProfile()` - Returns `avatar_url`
- `updateAccountProfile()` - Updates account (not avatar_url directly)

### 6. Utility Functions

#### `generatePreviewCSSVars.ts`
- **Line 137**: Sets `--preview-profile-image-url` from `coverImageUrl` parameter
- **Lines 108-112**: Sets profile image styling CSS vars

#### `buildPageUpdates.ts`
- **Lines 77-93**: Maps theme data to `profile_image_*` fields
- **Line 737** (in PodcastThemeGenerator): Sets `profile_image` from cover image

#### `previewRenderer.ts`
- **Lines 266-330**: Generates CSS vars from `uiState['profile-image-*']` values

## Identified Problems

1. **Theme Wizard Overwrites Profile Image**
   - `PodcastThemeGenerator.tsx:737` sets `pageUpdates.profile_image = coverImageUrl`
   - This overwrites the user's intended profile image with the cover image used for color extraction
   - User loses their original profile image

2. **Confusing Fallback Logic**
   - `LeftyProfileSection.tsx:36` uses `profileImage ?? account?.avatar_url`
   - Creates ambiguity: should UI show page profile or account avatar?
   - `TopBar.tsx` only uses `avatar_url`, creating inconsistency

3. **Preview vs. Saved State Confusion**
   - `--preview-profile-image-url` is meant for temporary preview
   - But `ModalPreview.tsx` uses `page?.profile_image` instead of CSS var
   - Inconsistent usage between components

4. **Single Field for Multiple Purposes**
   - `profile_image` used for:
    - Public page display
    - Theme generation (cover image)
    - Account avatar fallback
    - Potentially as podcast cover image in some circumstances
   - No clear separation of concerns

5. **Podcast Cover Image Ambiguity**
   - `cover_image_url` exists as separate field for podcast artwork
   - But `profile_image` may also serve as podcast cover in some cases
   - Creates confusion about which image to use for podcast display
   - `page.php:745` uses `cover_image_url` for podcast player, but there may be fallback logic elsewhere

## Proposed Solution

### New Image Variable Roles

1. **`pageProfileImage`** (renamed from `profile_image`)
   - Purpose: Profile image displayed on the public page
   - Database: `page.profile_image` (keep existing field name for backward compatibility)
   - UI: ProfileImageSection, PageCustomizationSection, ProfileInspector
   - Styling: All `profile_image_*` styling fields apply to this

2. **`coverImage`** (new, separate from profile)
   - Purpose: Image used for theme generation and color extraction
   - Database: `page.cover_image` (new field)
   - UI: Theme Wizard (PodcastThemeGenerator)
   - Styling: None (only used for extraction/preview)

3. **`accountAvatar`** (uses existing `avatar_url`)
   - Purpose: User account avatar for UI elements
   - Database: `users.avatar_url` (existing field)
   - UI: TopBar, LeftyProfileSection (account menu)
   - Styling: None (UI-only, not page content)

4. **`previewImage`** (temporary, CSS var only)
   - Purpose: Temporary image for preview without saving
   - Database: None (CSS var only: `--preview-profile-image-url`)
   - UI: ThemePreview, ModalPreview
   - Styling: Uses `pageProfileImage` styling

### Variable Mapping

| Current Usage | New Role | Database Field | CSS Var | Components |
|--------------|----------|----------------|---------|------------|
| `page.profile_image` (public page) | `pageProfileImage` | `page.profile_image` | `--page-profile-image-url` | ProfileImageSection, page.php |
| `coverImageUrl` (theme wizard) | `coverImage` | `page.cover_image` | `--cover-image-url` | PodcastThemeGenerator |
| `page.cover_image_url` (podcast) | `podcastCoverImage` | `page.cover_image_url` | `--podcast-cover-image-url` | PodcastPlayerInspector, page.php (player) |
| `account.avatar_url` | `accountAvatar` | `users.avatar_url` | None | TopBar, LeftyProfileSection |
| `--preview-profile-image-url` | `previewImage` | None | `--preview-profile-image-url` | ThemePreview, ModalPreview |

**Note on Podcast Cover Image**: The existing `cover_image_url` field should be used for podcast artwork. The new `cover_image` field (for theme generation) is separate. If `profile_image` is currently serving as podcast cover in some cases, this should be migrated to use `cover_image_url` explicitly.

### Migration Strategy

1. **Phase 1: Add New Fields**
   - Add `page.cover_image` column to database
   - Keep `page.profile_image` for backward compatibility

2. **Phase 2: Update Theme Wizard**
   - Stop setting `profile_image` from cover image
   - Set `cover_image` instead
   - Update preview to use `cover_image` for extraction

3. **Phase 3: Separate Account Avatar**
   - Remove fallback logic in `LeftyProfileSection`
   - Always use `avatar_url` for account UI
   - Never use `profile_image` for account avatar

4. **Phase 4: Update CSS Variables**
   - Rename `--preview-profile-image-url` to `--preview-page-profile-image-url`
   - Add `--cover-image-url` for theme wizard
   - Update all components to use correct variables

5. **Phase 5: Cleanup**
   - Remove any remaining cross-contamination
   - Update documentation
   - Add validation to prevent future conflicts

## Implementation Checklist

### Database Changes
- [ ] Add migration: `ALTER TABLE pages ADD COLUMN cover_image VARCHAR(255) NULL`
- [ ] Add index if needed for cover_image lookups

### API Changes
- [ ] Update `api/page.php` to handle `cover_image` field
- [ ] Update `api/upload.php` to support `cover_image` upload type
- [ ] Add `cover_image` to page snapshot response
- [ ] Update `buildPageUpdates.ts` to handle `cover_image` separately

### Frontend State Management
- [ ] Update `PodcastThemeGenerator` to use `coverImage` state (separate from `profileImage`)
- [ ] Update `useThemeWizardState` to track `coverImage` separately
- [ ] Remove `profile_image` assignment from theme generation

### Component Updates
- [ ] `PodcastThemeGenerator.tsx`: Use `coverImage` instead of setting `profile_image`
- [ ] `ThemePreview.tsx`: Use `--cover-image-url` for theme wizard preview
- [ ] `ModalPreview.tsx`: Use correct image source based on context
- [ ] `LeftyProfileSection.tsx`: Remove fallback, always use `avatar_url`
- [ ] `TopBar.tsx`: Already correct (uses `avatar_url` only)

### CSS Variables
- [ ] Rename `--preview-profile-image-url` to `--preview-page-profile-image-url`
- [ ] Add `--cover-image-url` for theme wizard
- [ ] Update `generatePreviewCSSVars.ts` to set both variables appropriately
- [ ] Update `previewRenderer.ts` to handle new variable names

### Utility Functions
- [ ] Update `buildPageUpdates.ts` to map `coverImage` to `cover_image` field
- [ ] Update `generatePreviewCSSVars.ts` to accept both `pageProfileImage` and `coverImage`

### Testing
- [ ] Test theme wizard doesn't overwrite profile image
- [ ] Test account avatar displays correctly
- [ ] Test page profile image displays correctly
- [ ] Test cover image is saved separately
- [ ] Test preview uses correct images

### Documentation
- [ ] Update API documentation for new `cover_image` field
- [ ] Update component documentation
- [ ] Add migration guide for existing users

