
# Fixing Page Title & Glow/Alignment Reversions

## Problem
Users reported a glitch where:
1. The **Page Title Alignment** would revert to "Left" after saving or refreshing, even if set to Center/Right.
2. The **Page Glow Effect** would revert to "On" (Glow) even if turned off (None).

## Root Cause
The root causes were identified in `ThemesPanel.tsx`:

1.  **Missing Alignment Persistence**: The `handleSave` function in `ThemesPanel.tsx` was extracting various page-level fields (like profile image settings) but **completely omitted** `name_alignment` (`page-title-alignment`) and `bio_alignment` (`page-bio-alignment`). As a result, these changes were applied to the local UI state but never sent to the server. When the page snapshot was re-fetched, the UI reverted to the old server-side values.

2.  **Incorrect Clearing Logic for Effects**: When the "Special Effect" was set to "None", the code was setting the value to `null`.
    ```typescript
    pageFields['page_name_effect'] = pageTitleEffect === 'none' ? null : ...
    ```
    Later, null values were converted to `undefined` in the payload construction:
    ```typescript
    if (value === null) payload[key] = undefined;
    ```
    Finally, the `buildFormData` utility helper **skips** keys with `undefined` values. This meant that "turning off" the effect resulted in sending *nothing* to the server, so the server kept the previous "Glow" value.

## Solution

We modified `src/components/panels/ThemesPanel.tsx` to:

1.  **Add Persistence for Alignments**: Explicitly extract `page-title-alignment` and `page-bio-alignment` from `currentUIState` and add them to the save payload as `name_alignment` and `bio_alignment`.

    ```typescript
    const pageTitleAlignment = currentUIState['page-title-alignment'];
    if (pageTitleAlignment !== undefined) {
      pageFields['name_alignment'] = String(pageTitleAlignment);
    }
    // ... same for bio_alignment
    ```

2.  **Fix Clearing Logic**: Changed the logic for `page_name_effect` to send an **empty string** (`""`) instead of `null`/`undefined` when the value is "none". This ensures the key is included in the Form Data and the backend correctly clears the value.

    ```typescript
    pageFields['page_name_effect'] = pageTitleEffect === 'none' || pageTitleEffect === '' ? '' : String(pageTitleEffect);
    ```

## Verification
1.  **Code Review**: Verified that `ThemesPanel.tsx` now processes these fields correctly.
2.  **Build Verification**: Ran `npm run build` successfully.

## Next Steps
- Verify in the UI that:
    - Setting alignment to "Center" persists after a page refresh.
    - Turning the Glow effect off persists as "None" after a page refresh.
