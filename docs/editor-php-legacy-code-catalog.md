# Editor.php Legacy Code Catalog

**Date**: 2025-01-XX  
**File**: `editor.php` (9360 lines)  
**Purpose**: Catalog all legacy code patterns for deprecation planning

## Inline CSS Blocks

### Main Style Block (Lines ~262-3100)

**Location**: Starts at line 262  
**Size**: ~2840 lines  
**Content**:
- Global reset styles
- Body and layout styles
- Accordion styles
- Form styles
- Theme card styles
- Widget styles
- Modal/drawer styles
- Mobile responsive styles

**Extraction Status**: ⏳ Pending  
**Extraction Target**: `css/editor-legacy.css` or split into multiple files

### Inline Style Attributes

Multiple inline `style` attributes throughout HTML output:
- Theme card backgrounds (line ~3134)
- Font preview styles (lines ~3137-3141)
- Form input styles (various)

**Extraction Status**: ⏳ Pending - These should be moved to CSS classes

## Inline JavaScript Blocks

### Main Script Block (Lines ~4150-9360)

**Location**: Starts at line 4150  
**Size**: ~5210 lines  
**Content**:
- Global window functions
- Theme change handlers
- Form submission handlers
- Widget management functions
- Social icon management
- Preview iframe updates
- Image upload (Croppie) integration
- DOM manipulation utilities

**Extraction Status**: ⏳ Pending  
**Extraction Target**: `js/editor-legacy.js` or split into modules

### Key JavaScript Functions

#### Global Window Functions (Lines ~4156-4250)
- `window.showSection(sectionName, navElement)` - Tab navigation
- `window.showTab(tabName, evt)` - Tab alias
- `window.toggleAccordion(sectionId)` - Accordion toggle
- `window.toggleUserMenu()` - User menu dropdown
- `window.closeUserMenu()` - Close user menu
- `window.toggleMobileMenu()` - Mobile menu toggle
- `window.copyPageUrl()` - Copy page URL to clipboard
- `window.togglePreview()` - Preview panel toggle
- `window.refreshPreview()` - Refresh preview iframe
- `window.toggleFeaturedWidget(widgetId, currentlyFeatured)` - Featured widget toggle
- `window.applyFeaturedEffect(effect)` - Apply featured widget effect
- `window.handleFeaturedToggle(widgetId, isFeatured)` - Featured toggle handler

#### Theme Management Functions (Lines ~7900-8200)
- `handleThemeChange(triggerContext)` - Theme selection handler
  - Fetches theme data from `/api/themes.php?id=X`
  - Applies all theme properties to form fields
  - Updates DOM elements immediately
  - Triggers auto-save if enabled

#### Form Update Functions (Lines ~8200-8600)
- `updateColorSwatch(type, color)` - Update color picker swatch
- `updateFontPreview()` - Update font preview
- `updatePageFontPreview()` - Update page font preview
- `updateWidgetFontPreview()` - Update widget font preview
- `updatePageBackground()` - Update page background preview
- `updateWidgetBackground()` - Update widget background preview
- `updateGradient()` - Update gradient preview
- `updateWidgetGradient()` - Update widget gradient preview

#### Widget Management Functions (Lines ~8600-9200)
- `addWidget(widgetType)` - Add new widget
- `editWidget(widgetId)` - Edit widget
- `deleteWidget(widgetId)` - Delete widget
- `saveWidget(event, widgetId)` - Save widget form
- `reorderWidgets()` - Reorder widgets via drag-and-drop
- `toggleWidgetVisibility(widgetId, isVisible)` - Toggle widget visibility

#### Social Icon Functions (Lines ~9050-9150)
- `showAddDirectoryForm()` - Show add social icon form
- `editDirectory(directoryId, platformName, url)` - Edit social icon
- `closeDirectoryModal()` - Close social icon modal
- `saveSocialIcon(event, directoryId, isPlaceholder)` - Save social icon

#### Image Upload Functions (Lines ~9150-9300)
- `uploadProfileImage(context)` - Upload profile image
- `removeProfileImage(context)` - Remove profile image
- Uses Croppie library for image cropping

#### Preview Functions (Lines ~4650-4700)
- `refreshPreview()` - Reload preview iframe
- `togglePreview()` - Show/hide preview panel
- Updates iframe `src` with cache-busting query params

#### Auto-Save Functions (Lines ~8224-8300)
- `queueAppearanceSaveFromUser(action)` - Queue appearance save
- `saveAppearanceForm()` - Save appearance form
- `appearanceAutoSaveEnabled` - Auto-save flag
- Debounced save on field changes

## DOM Manipulation Functions

### Accordion Management
- `toggleAccordion(sectionId)` - Toggle accordion expanded/collapsed
- Uses localStorage to persist state
- Sections: theme-selection, page-name-effect, page-background, page-colors, page-fonts, widget-background, widget-border, widget-fonts, widget-structure, spatial-effects

### Tab Navigation
- `showSection(sectionName, navElement)` - Switch active tab
- Updates URL without reload
- Manages active nav item classes
- Auto-loads analytics if analytics tab opened

### Form Field Updates
- `updateColorSwatch(type, color)` - Update color input swatch and hex field
- `updateColorFromHex(type, hexValue)` - Update color from hex input
- `updateFontPreview()` - Load Google Fonts and update preview
- `updatePageFontPreview()` - Update page font preview
- `updateWidgetFontPreview()` - Update widget font preview
- `switchBackgroundType(type, triggerSave)` - Switch solid/gradient background
- `switchWidgetBackgroundType(type, triggerSave)` - Switch widget background type
- `updateGradient()` - Update gradient preview from inputs
- `updateWidgetGradient()` - Update widget gradient preview
- `updatePageBackground()` - Update page background preview
- `updateWidgetBackground()` - Update widget background preview

### Modal/Drawer Management
- `showWidgetSettingsDrawer(themeId)` - Show theme widget settings drawer
- `closeWidgetSettingsDrawer()` - Close drawer
- `showAddDirectoryForm()` - Show social icon form modal
- `closeDirectoryModal()` - Close social icon modal

### Widget Management
- `addWidget(widgetType)` - Show add widget form
- `editWidget(widgetId)` - Populate edit widget form
- `deleteWidget(widgetId)` - Confirm and delete widget
- `saveWidget(event, widgetId)` - Save widget form data
- `toggleWidgetVisibility(widgetId, isVisible)` - Toggle widget visibility
- `reorderWidgets()` - Save new widget order
- `toggleFeaturedWidget(widgetId, currentlyFeatured)` - Toggle featured status

### Preview Management
- `togglePreview()` - Show/hide preview panel
- `refreshPreview()` - Reload preview iframe with cache-busting
- Updates iframe `src` with `?_v=` timestamp

### User Interface
- `toggleUserMenu()` - Toggle user dropdown menu
- `closeUserMenu()` - Close user dropdown (also on outside click)
- `toggleMobileMenu()` - Toggle mobile sidebar
- `copyPageUrl()` - Copy page URL to clipboard with visual feedback

## Event Listeners

### Form Field Auto-Save
**Location**: Lines ~8224-8300  
**Pattern**: Attaches `change` event listeners to appearance form fields
**Fields Monitored**:
- `layout_option`
- `custom_primary_color`, `custom_secondary_color`, `custom_accent_color`
- `custom_heading_font`, `custom_body_font`
- `page_primary_font`, `page_secondary_font`
- `widget_primary_font`, `widget_secondary_font`
- `page_background`
- `widget_background`, `widget_border_color`
- `widget_border_width`, `widget_border_effect`
- `widget_border_shadow_intensity`, `widget_border_glow_intensity`
- `widget_glow_color_hidden`, `widget_spacing`, `widget_shape`
- `spatial_effect`
- `page-name-effect-selector`

**Behavior**: Debounced auto-save on field change

### Font Preview Updates
**Location**: Lines ~8200-8223  
**Pattern**: `change` event listeners on font select fields
**Fields**:
- `custom_heading_font`, `custom_body_font`
- `page_primary_font`, `page_secondary_font`
- `widget_primary_font`, `widget_secondary_font`

**Behavior**: Immediately update preview on change

## API Calls

### Theme API
- `GET /api/themes.php?id=X` - Fetch single theme (used in `handleThemeChange`)

### Page API
- `POST /api/page.php?action=update_appearance` - Save appearance changes
- `POST /api/page.php?action=update_settings` - Save page settings
- `POST /api/page.php?action=update_email_settings` - Save email settings
- `GET /api/page.php?action=get_snapshot` - **NOT USED** (Lefty uses this)

### Widget API
- `POST /api/widgets.php?action=add` - Add widget
- `POST /api/widgets.php?action=update` - Update widget
- `POST /api/widgets.php?action=delete` - Delete widget
- `POST /api/widgets.php?action=reorder` - Reorder widgets
- `POST /api/widgets.php?action=toggle_visibility` - Toggle widget visibility
- `POST /api/widgets.php?action=toggle_featured` - Toggle featured status

### Social Icons API
- `POST /api/page.php?action=add_directory` - Add social icon
- `POST /api/page.php?action=update_directory` - Update social icon
- `POST /api/page.php?action=delete_directory` - Delete social icon
- `POST /api/page.php?action=reorder_directories` - Reorder social icons

### Image Upload API
- `POST /api/upload.php?context=profile` - Upload profile image
- `POST /api/page.php?action=remove_profile_image` - Remove profile image

## React Equivalents

### Accordion Management
**Editor.php**: `toggleAccordion()` with localStorage  
**Lefty**: Radix UI `Accordion` component with React state

### Tab Navigation
**Editor.php**: `showSection()` with DOM manipulation  
**Lefty**: React Router or Radix UI `Tabs` component

### Form Field Updates
**Editor.php**: Direct DOM manipulation (`element.value = ...`)  
**Lefty**: React `useState` hooks and controlled components

### Theme Change Handler
**Editor.php**: `handleThemeChange()` fetches theme and updates DOM  
**Lefty**: `handleApplyTheme()` in React components using React Query mutations

### Preview Management
**Editor.php**: Direct iframe manipulation  
**Lefty**: React component with `useEffect` hooks

### Widget Management
**Editor.php**: Form-based CRUD with DOM manipulation  
**Lefty**: React Query mutations (`useAddWidgetMutation`, `useUpdateWidgetMutation`, etc.)

### Social Icon Management
**Editor.php**: Modal-based forms  
**Lefty**: Drawer components (per user rules) with React state

## Dependencies

### External Libraries
- **Croppie** (`@shopify/draggable`) - Image cropping
- **Font Awesome** (CDN) - Icons
- **Google Fonts** - Dynamic font loading

### Internal Dependencies
- `includes/theme-helpers.php` - Theme helper functions
- `classes/Theme.php` - Theme management
- `classes/WidgetStyleManager.php` - Widget style utilities
- `classes/Page.php` - Page operations

## Migration Strategy

### High Priority (Required for Removal)
1. ✅ Theme initialization and value resolution (DONE)
2. ✅ Theme application with all fields (DONE)
3. ⏳ Widget CRUD operations (Lefty has hooks but verify parity)
4. ⏳ Social icon CRUD operations (Verify Lefty has this)
5. ⏳ Image upload functionality (Verify Lefty has this)

### Medium Priority (Nice to Have)
1. ⏳ Auto-save functionality (Verify if needed in Lefty)
2. ⏳ Preview iframe refresh (Verify if Lefty has preview)
3. ⏳ Accordion state persistence (Verify if needed)

### Low Priority (Can Remove)
1. ⏳ Legacy font mapping logic (Only needed for old themes)
2. ⏳ Feature flag redirect logic (Can remove after editor.php deleted)

## Deprecation Timeline

### Phase 1: Feature Parity Verification (COMPLETE ✅)
- ✅ Verify theme functionality works in Lefty
- ✅ Fix missing theme fields in API (`widget_styles`, `spatial_effect`)
- ✅ Fix TypeScript types (`PageSnapshot`, `ThemeRecord`)
- ✅ Fix theme application to send all fields
- ⏳ Verify widget CRUD parity (Lefty has hooks - verify feature parity)
- ⏳ Verify social icon CRUD parity (Lefty has hooks - verify feature parity)
- ⏳ Verify image upload parity (Check if Lefty has profile image upload)

### Phase 2: Feature Flag Removal (IN PROGRESS)
- ✅ Lefty is now the default admin interface
- ✅ All redirects point to Lefty
- ⏳ Remove feature flag check from editor.php (currently redirects to Lefty)
- ⏳ Update editor.php redirect to show deprecation notice

### Phase 3: Editor.php Removal (FUTURE)
- ⏳ Archive editor.php code to `archive/editor.php` directory
- ⏳ Remove editor.php file from root
- ⏳ Update documentation to remove editor.php references
- ⏳ Remove legacy dependencies (Croppie, Draggable if not used elsewhere)

## Migration Checklist

### Theme System (COMPLETE ✅)
- ✅ `get_snapshot` API includes resolved `widget_styles`
- ✅ `get_snapshot` API includes resolved `spatial_effect`
- ✅ `PageSnapshot` TypeScript interface includes `spatial_effect`
- ✅ `ThemeRecord` TypeScript interface includes `widget_styles`
- ✅ Theme application sends all fields (`widget_styles`, `spatial_effect`, fonts, backgrounds, etc.)
- ✅ Null handling for clearing page overrides works correctly

### Widget Management (VERIFY)
- ⏳ Verify `useAddWidgetMutation()` has same functionality as `addWidget()`
- ⏳ Verify `useUpdateWidgetMutation()` has same functionality as `saveWidget()`
- ⏳ Verify `useDeleteWidgetMutation()` has same functionality as `deleteWidget()`
- ⏳ Verify `useReorderWidgetMutation()` has same functionality as drag-and-drop reorder
- ⏳ Verify widget visibility toggle exists in Lefty
- ⏳ Verify featured widget toggle exists in Lefty

### Social Icon Management (VERIFY)
- ⏳ Verify `useAddSocialIconMutation()` exists
- ⏳ Verify `useUpdateSocialIconMutation()` exists
- ⏳ Verify `useDeleteSocialIconMutation()` exists
- ⏳ Verify `useReorderSocialIconMutation()` exists
- ⏳ Verify social icon CRUD UI exists in Lefty

### Image Upload (VERIFY)
- ⏳ Verify profile image upload exists in Lefty
- ⏳ Verify image cropping (Croppie) is used or replaced in Lefty
- ⏳ Verify image removal functionality exists

### Page Settings (VERIFY)
- ⏳ Verify page settings panel exists in Lefty
- ⏳ Verify email settings panel exists in Lefty
- ⏳ Verify custom domain settings exist in Lefty

### Preview (VERIFY)
- ⏳ Verify preview iframe exists in Lefty
- ⏳ Verify preview refresh functionality exists
- ⏳ Verify preview updates when settings change

### Analytics (VERIFY)
- ⏳ Verify analytics dashboard exists in Lefty
- ⏳ Verify analytics data loading works

### Auto-Save (OPTIONAL)
- ⏳ Determine if auto-save is needed in Lefty (React Query may handle this)
- ⏳ If needed, implement debounced save on field changes

## Notes

- Editor.php uses server-side rendering (SSR) - all data loaded before HTML output
- Lefty uses client-side rendering (CSR) with React - data loaded via API
- Editor.php uses inline styles/scripts - should be extracted for better caching
- Editor.php uses vanilla JS DOM manipulation - Lefty uses React state management
- Editor.php uses form-based submissions - Lefty uses React Query mutations
- Editor.php uses modals - Lefty uses drawers (per user rules)

