# Profile Image Variable Schema Proposal

## Overview

This document defines the proposed schema for separating profile image concerns into distinct, purpose-specific variables.

## Image Roles & Definitions

### 1. Page Profile Image (`pageProfileImage`)

**Purpose**: The profile image displayed on the user's public page (page.php)

**Database Field**: `pages.profile_image` (existing, keep for backward compatibility)

**CSS Variable**: `--page-profile-image-url`

**Styling Variables**:
- `--page-profile-image-radius` (0-50%)
- `--page-profile-image-size` (px)
- `--page-profile-image-border-width` (px)
- `--page-profile-image-border-color` (hex)
- `--page-profile-image-box-shadow` (CSS shadow)

**Database Styling Fields** (existing):
- `profile_image_radius`
- `profile_image_size`
- `profile_image_effect`
- `profile_image_shadow_*`
- `profile_image_glow_*`
- `profile_image_border_*`

**Used By**:
- Public page rendering (`page.php`)
- ProfileImageSection component
- PageCustomizationSection component
- ProfileInspector component
- ThemePreview (when displaying saved page state)
- ModalPreview (when displaying saved page state)

**Update Methods**:
- `updatePageAppearance({ profile_image: url })`
- `uploadProfileImage(file)`
- `removeProfileImage()`

**Rules**:
- Should NOT be overwritten by Theme Wizard
- Should NOT be used as account avatar fallback
- Is the "source of truth" for public page profile image

---

### 2. Cover Image (`coverImage`)

**Purpose**: Image used for theme generation, color extraction, and theme wizard preview

**Database Field**: `pages.cover_image` (NEW - to be added)

**CSS Variable**: `--cover-image-url`

**Styling Variables**: None (cover image is not displayed, only used for extraction)

**Database Styling Fields**: None

**Used By**:
- PodcastThemeGenerator (color extraction)
- Theme generation API
- Theme wizard preview (temporary display)

**Update Methods**:
- `updatePageAppearance({ cover_image: url })`
- `uploadCoverImage(file)` (new function)
- Theme Wizard sets it when user selects image

**Rules**:
- Should NOT affect `profile_image`
- Should NOT be displayed on public page
- Is only used for theme generation workflow
- Can be different from profile image

---

### 3. Podcast Cover Image (`podcastCoverImage`)

**Purpose**: Podcast cover artwork displayed in podcast player and related UI

**Database Field**: `pages.cover_image_url` (existing field, keep for backward compatibility)

**CSS Variable**: `--podcast-cover-image-url`

**Styling Variables**: None (podcast cover is displayed as-is in player)

**Database Styling Fields**: None

**Used By**:
- PodcastPlayerInspector component
- Podcast player widget (`page.php` "Now Playing" section)
- RSS feed parsing (sets `cover_image_url` from feed)
- WidgetRenderer (podcast widget display)

**Update Methods**:
- Set from RSS feed: `api/page.php:419` - `$updateData['cover_image_url'] = $feedData['cover_image']`
- Can be updated via page appearance API
- Included in page snapshot

**Rules**:
- Should NOT be confused with `profile_image`
- Should NOT be confused with theme generation `cover_image` (different field)
- Is the "source of truth" for podcast artwork
- If `profile_image` is currently serving as podcast cover in some cases, migrate to use `cover_image_url` explicitly
- Should be separate from page profile image

**Note**: This is an existing field that should remain separate. The confusion arises when `profile_image` is used as a fallback or substitute for podcast cover image, which should be avoided.

---

### 4. Account Avatar (`accountAvatar`)

**Purpose**: User account avatar displayed in UI elements (TopBar, LeftyProfileSection)

**Database Field**: `users.avatar_url` (existing)

**CSS Variable**: None (UI-only, not page content)

**Styling Variables**: None

**Database Styling Fields**: None

**Used By**:
- TopBar component
- LeftyProfileSection component
- Account profile UI

**Update Methods**:
- Account profile API (`/api/account/profile.php`)
- Account settings UI

**Rules**:
- Should NOT fall back to `profile_image`
- Should NOT fall back to `cover_image`
- Is completely separate from page content
- Used only for admin UI elements

---

### 5. Preview Image (`previewImage`)

**Purpose**: Temporary image URL for preview without affecting saved page data

**Database Field**: None (temporary only)

**CSS Variable**: `--preview-page-profile-image-url` (renamed from `--preview-profile-image-url`)

**Styling Variables**: Uses `pageProfileImage` styling variables

**Database Styling Fields**: None

**Used By**:
- ThemePreview component (iframe updates)
- ModalPreview component (preview mode)
- Theme wizard preview

**Update Methods**:
- Set via `setProfileImageInPreview(url)` hook
- Cleared when preview closes

**Rules**:
- Should NOT be saved to database
- Should NOT persist across page reloads
- Is only for temporary preview display
- Overrides `pageProfileImage` in preview context only

---

## Data Flow Diagrams

### Theme Wizard Flow (Current → Proposed)

**Current (Problematic)**:
```
User selects image in Theme Wizard
  → coverImageUrl state
  → Color extraction
  → Theme generation
  → pageUpdates.profile_image = coverImageUrl ❌ (overwrites user's profile image)
```

**Proposed (Fixed)**:
```
User selects image in Theme Wizard
  → coverImage state
  → Color extraction
  → Theme generation
  → pageUpdates.cover_image = coverImage ✅ (separate field)
  → --preview-profile-image-url = coverImage (temporary preview)
```

### Account Avatar Flow (Current → Proposed)

**Current (Confusing)**:
```
LeftyProfileSection renders:
  → page.profile_image ?? account.avatar_url ❌ (fallback creates confusion)
```

**Proposed (Clear)**:
```
LeftyProfileSection renders:
  → account.avatar_url only ✅ (no fallback)
  
TopBar renders:
  → account.avatar_url only ✅ (already correct)
```

### Preview Flow (Current → Proposed)

**Current (Inconsistent)**:
```
ThemePreview:
  → Reads --preview-profile-image-url ✅
  
ModalPreview:
  → Reads page.profile_image ❌ (should use CSS var)
```

**Proposed (Consistent)**:
```
ThemePreview:
  → Reads --preview-page-profile-image-url ✅
  
ModalPreview:
  → Reads --preview-page-profile-image-url ✅ (consistent)
```

## TypeScript Interfaces

### Updated Page Snapshot Type

```typescript
interface PageSnapshot {
  page: {
    profile_image: string | null;      // Page profile image (public page)
    cover_image: string | null;         // NEW: Cover image (theme generation)
    cover_image_url: string | null;     // Podcast cover artwork (existing field)
    // ... existing fields
    profile_image_radius?: number;
    profile_image_size?: number;
    // ... other styling fields
  };
}
```

### Updated Account Profile Type

```typescript
interface AccountProfile {
  avatar_url: string | null;  // Account avatar (UI only)
  // ... existing fields
}
```

### Theme Wizard State

```typescript
interface ThemeWizardState {
  coverImageUrl: string | null;      // Cover image for theme generation
  // ... existing fields
  // Note: profile_image is NOT in wizard state
}
```

## CSS Variable Naming Convention

### Current Variables (to be updated)
- `--preview-profile-image-url` → `--preview-page-profile-image-url`
- `--profile-image-*` → `--page-profile-image-*` (for clarity)

### New Variables
- `--cover-image-url` (for theme wizard)

### Variable Hierarchy

```
--page-profile-image-url          (saved page profile image)
  ├── --page-profile-image-radius
  ├── --page-profile-image-size
  ├── --page-profile-image-border-*
  └── --page-profile-image-box-shadow

--cover-image-url                  (theme generation only)

--podcast-cover-image-url           (podcast artwork, existing field)

--preview-page-profile-image-url   (temporary preview override)
  └── Uses --page-profile-image-* styling
```

## Migration Path

### Phase 1: Add Cover Image Field
- Database migration adds `cover_image` column
- API accepts `cover_image` in updates
- Frontend can read `cover_image` from snapshot

### Phase 2: Update Theme Wizard
- Stop writing to `profile_image`
- Start writing to `cover_image`
- Update preview to use `cover_image`

### Phase 3: Separate Account Avatar
- Remove fallback logic
- Always use `avatar_url` for account UI

### Phase 4: Rename CSS Variables
- Update all CSS variable names
- Update all component references
- Update preview renderer

### Phase 5: Cleanup & Validation
- Add validation to prevent cross-contamination
- Update documentation
- Remove deprecated code paths

## Backward Compatibility

### Existing Data
- All existing `profile_image` values remain valid
- `cover_image` starts as `NULL` for all pages
- Theme Wizard can migrate: if `cover_image` is NULL and user generates theme, use `profile_image` as initial `cover_image` value

### API Compatibility
- Old API calls that set `profile_image` still work
- New API calls can set `cover_image` separately
- Both fields can coexist

### Component Compatibility
- Components that read `profile_image` continue to work
- New components can read `cover_image` when available
- Preview components use CSS vars (no breaking changes)

## Validation Rules

1. **Theme Wizard**: Must never set `profile_image`, only `cover_image`
2. **Account Avatar**: Must never use `profile_image` or `cover_image` as fallback
3. **Preview**: Must use CSS vars, not direct database fields
4. **Public Page**: Must use `profile_image`, never `cover_image`

## Testing Scenarios

1. **Theme Generation**
   - User has existing profile image
   - User generates theme with cover image
   - Profile image should remain unchanged
   - Cover image should be saved separately

2. **Account Avatar**
   - User has no avatar_url
   - User has profile_image
   - UI should show placeholder, not profile_image

3. **Preview Display**
   - User changes profile image in preview
   - Preview should update without saving
   - Saved page should remain unchanged until user saves

4. **Multiple Images**
   - User can have different images for:
     - Profile (public page)
     - Cover (theme generation)
     - Avatar (account UI)
   - All three should work independently

