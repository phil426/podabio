# Unified Editor Plan: Combining Layers & Theme Editing

## Vision

Combine layer editing and theme editing on a single page where:
- The live preview (with disabled links) acts as navigation for editors
- Hotspots can initiate either style editing (theme) or content editing (layers)
- All layer functionality (reorder, visibility, delete, lock) is accessible directly from the live preview
- Users edit what they see, reducing context switching

## Current Architecture

### Layers Tab
- **LayersPanel**: List-based widget management
  - Drag-and-drop reordering via `DraggableLayerList`
  - Visibility toggles (eye icon)
  - Lock/unlock functionality
  - Featured status
  - Delete widgets
  - Add new widgets
- **WidgetInspector**: Content editing (title, config fields, thumbnail)
- **PropertiesPanel**: Shows inspector based on selection

### Theme Tab
- **ThemePreview**: Live preview with hotspots
- **ThemePropertyDrawer**: Style editing (colors, fonts, shadows, glow)
- **Hotspots**: Click to open style editor for specific sections

### Separation
- Content editing (layers) vs Style editing (theme) are separate
- Requires switching between tabs
- No unified view

## Recommended Approach: Hybrid Overlay System

### Option 1: Floating Action Menu (RECOMMENDED)

**How it works:**
1. Live preview shows actual page in iframe
2. On hover over widget → floating action menu appears with:
   - **Edit Content** → Opens WidgetInspector
   - **Edit Style** → Opens ThemePropertyDrawer  
   - **Reorder** → Drag handle for reordering
   - **Visibility Toggle** → Show/hide widget
   - **Delete** → Remove widget
   - **Lock/Unlock** → Prevent accidental moves
3. Positioning via postMessage: iframe sends widget positions, parent overlays controls

**Benefits:**
- ✅ Clean separation - live preview stays uncluttered
- ✅ All layer functionality accessible
- ✅ Clear visual feedback
- ✅ Easier to implement than embedding controls in iframe
- ✅ Works with existing iframe architecture

**Implementation:**
```typescript
// In iframe (page.php preview mode):
// Send widget positions to parent
window.parent.postMessage({
  type: 'widget-positions',
  widgets: [
    { id: '123', top: 200, left: 20, width: 560, height: 120 }
  ]
}, '*');

// In parent (ThemePreview):
// Overlay floating menu on hover
<div className="widget-overlay-menu" style={{ top, left }}>
  <button onClick={() => openContentEditor(widgetId)}>Edit Content</button>
  <button onClick={() => openStyleEditor(widgetId)}>Edit Style</button>
  <DragHandle onDrag={handleReorder} />
  <VisibilityToggle />
  <DeleteButton />
</div>
```

### Option 2: Context Menu on Hotspot Click

**How it works:**
- Click hotspot → Show context menu: "Edit Content" or "Edit Style"
- Or: Long-press/right-click for full menu
- Single hotspot can trigger different editors

**Benefits:**
- Simple interaction model
- Less visual clutter
- Familiar pattern (right-click menus)

### Option 3: Dual-Mode Hotspots

**How it works:**
- Single click → Edit Style (ThemePropertyDrawer)
- Double click → Edit Content (WidgetInspector)
- Or: Modifier key (Shift/Cmd) + click for content

**Benefits:**
- Minimal UI changes
- Fast workflow
- Keyboard-friendly

## Addressing Complexity Concerns

### Challenge: Drag-and-Drop in Iframe
**Solution: Overlay Drag Handles**
- Position drag handles over iframe using absolute positioning
- Handle drag in parent window (not iframe)
- Use postMessage to communicate with iframe
- Update order via API, refresh iframe

### Challenge: State Synchronization
**Solution: Unified State Management**
- Use existing `useWidgetSelection` for content editing
- Use existing theme `uiState` for style editing
- Both can coexist and update independently
- Hotspot clicks can set both selections

### Challenge: Widget Identification
**Solution: Data Attributes**
- Add `data-widget-id` to each widget in preview mode
- Map hotspot clicks to widget IDs
- Use widget ID to open correct editor

## Implementation Plan

### Phase 1: Enhanced Hotspots (Foundation)
**Goal:** Enable both content and style editing from hotspots

**Tasks:**
1. Add widget ID mapping to hotspots in `page.php`
   - Add `data-widget-id` attribute to widget elements
   - Map hotspot clicks to widget IDs
2. Update hotspot click handler to show context menu
   - "Edit Style" → Opens ThemePropertyDrawer
   - "Edit Content" → Opens WidgetInspector
3. Integrate WidgetInspector into unified editor view
   - Add to ThemeEditorView or create new UnifiedEditorView
   - Handle both content and style editing modes

**Files to modify:**
- `page.php`: Add widget ID data attributes
- `admin-ui/src/components/panels/themes/ThemeEditorView.tsx`: Add content editing mode
- `admin-ui/src/components/panels/themes/preview/ThemePreview.tsx`: Handle widget ID in hotspot clicks

### Phase 2: Floating Controls (Enhanced UX)
**Goal:** Add hover-based floating action menu

**Tasks:**
1. Add hover detection via postMessage
   - Iframe sends widget hover events
   - Parent shows floating menu
2. Create FloatingActionMenu component
   - Position absolutely over iframe
   - Include all layer actions
3. Implement widget position tracking
   - Iframe sends widget positions on load/scroll
   - Parent calculates menu positions

**Files to create:**
- `admin-ui/src/components/panels/themes/preview/FloatingActionMenu.tsx`
- `admin-ui/src/components/panels/themes/preview/widget-overlay-menu.module.css`

**Files to modify:**
- `page.php`: Add hover detection and position reporting
- `admin-ui/src/components/panels/themes/preview/ThemePreview.tsx`: Handle floating menu

### Phase 3: Drag-and-Drop (Full Functionality)
**Goal:** Enable reordering directly from preview

**Tasks:**
1. Add drag handles to floating menu
2. Implement drag in parent window
   - Use @dnd-kit for drag handling
   - Calculate drop zones from widget positions
3. Update order and refresh iframe
   - Call reorder API
   - Refresh iframe with new order

**Files to modify:**
- `admin-ui/src/components/panels/themes/preview/FloatingActionMenu.tsx`: Add drag handle
- `admin-ui/src/components/panels/themes/preview/ThemePreview.tsx`: Handle drag events

### Phase 4: Additional Layer Features
**Goal:** Complete layer management from preview

**Tasks:**
1. Add visibility toggle to floating menu
2. Add delete button with confirmation
3. Add lock/unlock functionality
4. Add "Add Widget" button (floating or in preview area)

## Technical Architecture

### Unified Editor View Structure
```
UnifiedEditorView
├── Live Preview (iframe)
│   ├── Hotspots (style editing)
│   └── Widget IDs (content editing)
├── Floating Action Menu (on hover)
│   ├── Edit Content → WidgetInspector
│   ├── Edit Style → ThemePropertyDrawer
│   ├── Reorder (drag handle)
│   ├── Visibility toggle
│   ├── Delete
│   └── Lock/Unlock
└── Unified Properties Panel
    ├── Content Editor (when widget selected)
    └── Style Editor (when hotspot clicked)
```

### State Management
- **Widget Selection**: `useWidgetSelection` (existing)
- **Theme State**: `uiState` in ThemeEditorView (existing)
- **Editor Mode**: New state to track "content" vs "style" editing
- **Floating Menu**: Local state for hover/position

### Communication Flow
1. **Iframe → Parent**: Widget positions, hover events, hotspot clicks
2. **Parent → Iframe**: Refresh requests, CSS variable updates
3. **Parent → API**: Widget updates, reordering, deletions
4. **API → Parent**: Updated data triggers iframe refresh

### Widget Identification
- Each widget in preview mode gets `data-widget-id="{widget.id}"`
- Hotspots can include both `data-hotspot` and `data-widget-id`
- Map clicks to correct widget for content editing

## Benefits of This Approach

1. **Single Source of Truth**: Live preview is the navigation
2. **All Functionality Accessible**: Layers + theme in one place
3. **Clean UI**: Controls appear on demand, not cluttered
4. **Scalable**: Easy to add more features
5. **Maintainable**: Clear separation of concerns

## Considerations

### Performance
- Iframe adds overhead but provides accurate preview
- Floating menus only render on hover
- Position calculations cached to avoid reflows

### User Experience
- Clear visual feedback (hover states, animations)
- Keyboard shortcuts for power users
- Touch-friendly for mobile editing

### Security
- Preview mode requires authentication (already implemented)
- Ownership verification (already implemented)
- postMessage origin validation

## Next Steps

1. **Decision**: Choose Option 1 (Floating Menu) for full functionality
2. **Start Small**: Implement Phase 1 (enhanced hotspots) first
3. **Test**: Validate with single widget type
4. **Iterate**: Add features incrementally

## Questions to Resolve

1. Should we keep the layers list panel as a fallback, or fully replace it?
2. How should "Add Widget" work in the unified view?
3. Should drag-and-drop show visual feedback in the iframe?
4. How to handle widgets that aren't visible (hidden/featured)?

## Success Metrics

- Users can edit both content and style from preview
- All layer management features accessible
- Reduced clicks to complete common tasks
- No performance degradation
- Positive user feedback

---

**Status**: Planning Phase  
**Last Updated**: 2025-01-15  
**Related**: Live Preview Implementation (feature/live-preview-iframe branch)

