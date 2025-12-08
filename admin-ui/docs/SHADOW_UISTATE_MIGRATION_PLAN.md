# Shadow UI State Migration Plan (Refined)

**Objective**: Unify theme state management into a single, flat `uiState` object keyed by Field IDs. This eliminates the "Shadow State" problem where `TokenBundle` (deeply nested object) fights with Direct Database Columns (e.g., `widget_background`).

**Core Principle**: The `fieldRegistry` is the single source of truth for *how* a value maps to the database. The `uiState` is the single source of truth for *what* the current value is.

---

## 📅 Phased Execution Plan

### Phase 1: Foundations & Types (Estimated: 2h)
**Goal**: Define the new state shape and audit the registry.
1.  **Define `ThemeUIState` Interface**: A flat record: `Record<string, string | number | boolean>`.
2.  **Audit `fieldRegistry`**: Ensure EVERY editable setting in the UI has a corresponding Field ID in `src/components/panels/themes/utils/fieldRegistry.ts`.
    *   *Critical Check*: Ensure `widget_background`, `widget_border_color`, `page_background` are registered.
3.  **Utility Functions**: Create `src/components/panels/themes/utils/stateUtils.ts`.
    *   `getInitialUIState(theme: ThemeRecord): ThemeUIState` (See Phase 2).

### Phase 2: The "Hydrator" (Adapter Pattern) (Estimated: 3h)
**Goal**: Correctly load the theme into `uiState`.
**Crucial Logic**: This function **MUST** replicate the fix we just implemented:
1.  **Priority 1**: Direct Database Column (e.g., `theme.widget_background`).
2.  **Priority 2**: Nested Token Value (e.g., `theme.color_tokens.background.surface`).
3.  **Priority 3**: Default Fallback.

*Deliverable*: A robust `databaseToUI(theme)` function that passes all "Shadow State" unit tests.

### Phase 3: Component Migration (Iterative) (Estimated: 8-10h)
**Goal**: Switch components to read/write `uiState` one by one, minimizing breakage.

*   **Phase 3a: `PageSettingsPanel`**
    *   Refactor to accept `uiState` and `onChange(fieldId, value)`.
    *   Remove `TokenBundle` props.
    *   Bind inputs to Field IDs (e.g., 'page-background', 'page-title-color').

*   **Phase 3b: `WidgetColorsPanel`**
    *   Refactor to accept `uiState` and `onChange`.
    *   Bind inputs to Field IDs (e.g., 'widget-background', 'widget-border-color').
    *   *Check*: This panel was the source of the recent bugs. Verify hydration works perfectly here.

*   **Phase 3c: `UltimateThemeModifier` (The Container)**
    *   Lift `uiState` to this parent level.
    *   Replace `tokenValues` map with `uiState`.
    *   Pass `uiState` down to 3a and 3b.

*   **Phase 3d: The "Tabs" (Typography, Spacing, Shapes)**
    *   Migrate the remaining sections in the `UltimateThemeModifier` tabs.

### Phase 4: The "Dehydrator" (Save Logic) (Estimated: 3h)
**Goal**: Convert `uiState` back to the Database format.
1.  Create `uiToDatabase(uiState, originalTheme): UpdateThemeData`.
2.  Iterate through `fieldRegistry`.
3.  For each field with a `tokenPath`, set the value in the nested token object.
4.  For each field with a direct column mapping (e.g., 'widget-background'), set the direct property.
5.  *Validation*: Prevent saving empty strings for colors (fallback to defaults or previous values).

### Phase 5: Cleanup & Preview Sync (Estimated: 2h)
1.  **Shadow DOM Preview**: Update `generateCSSVariables` to read from `uiState` instead of `TokenBundle`.
2.  **Delete Legacy Code**: Remove `TokenBundle` processing logic, `resolveToken`, and unused types.

---

## 🛡️ Risk Mitigation
*   **Regression Test**: After Phase 2, verify that loading a theme results in the exact same distinct values as the current system.
*   **Dual-State Sanity Check**: During Phase 3c, logging both the old `tokenValues` and new `uiState` to ensure they stay in sync before fully switching.

## ✅ Definition of Done
1.  All theme settings are editable.
2.  `widget_background` and `page_background` persist correctly after save.
3.  Shadow DOM preview updates in real-time.
4.  No references to `TokenBundle` exist in the mutable editor code.
