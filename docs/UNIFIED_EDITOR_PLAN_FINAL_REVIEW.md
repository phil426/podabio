# Unified Editor Plan: Final Review & Recommendations

## Executive Summary

After comprehensive review of the plan against the codebase, I've identified key improvements needed for clarity, simplicity, accessibility, and mobile support. This document consolidates all findings and provides a refined implementation approach.

## Critical Findings

### 1. **Approach Selection: Context Menu > Floating Menu**

**Original Plan**: Recommends Option 1 (Floating Menu) as primary approach.

**Issue**: Over-engineered for initial implementation. Requires:
- Continuous position tracking via postMessage
- Hover event coordination between iframe and parent
- Complex positioning calculations
- Performance overhead on mobile

**Recommendation**: **Use Option 2 (Context Menu) as primary approach** for Phase 1-2:
- Right-click (desktop) or long-press (mobile) → Show context menu
- Simpler, more standard UX pattern
- No position tracking needed initially
- Works immediately with existing hotspot infrastructure
- Better mobile experience (no hover required)

**Floating Menu**: Keep as Phase 3 enhancement if needed after validating context menu approach.

### 2. **State Management: Simpler Than Planned**

**Original Plan**: Proposes new "Editor Mode" state.

**Reality**: Can use existing state with coordination:
- `openModalSection` (ThemeEditorView) → Style editing (already exists)
- New: `contentEditorWidgetId` state → Content editing
- Both can coexist or replace each other based on user action

**Simpler Implementation**:
```typescript
// In ThemeEditorView.tsx
const [openModalSection, setOpenModalSection] = useState<string | null>(null);
const [contentEditorWidgetId, setContentEditorWidgetId] = useState<string | null>(null);

// When context menu "Edit Content" clicked:
setContentEditorWidgetId(widgetId);
// Optionally close style editor:
setOpenModalSection(null);

// When context menu "Edit Style" clicked:
setOpenModalSection(sectionId);
// Optionally close content editor:
setContentEditorWidgetId(null);
```

### 3. **WidgetInspector Integration: Use Existing Modal**

**Original Plan**: Says "integrate WidgetInspector into unified editor view" but unclear.

**Existing Pattern**: `WidgetInspectorModal` already exists and works well.

**Solution**: Simply add `WidgetInspectorModal` to `ThemeEditorView`:
```typescript
// In ThemeEditorView.tsx
<WidgetInspectorModal
  activeColor={activeColor}
  widgetId={contentEditorWidgetId}
  onClose={() => setContentEditorWidgetId(null)}
/>
```

### 4. **Missing: Accessibility & Mobile Considerations**

**Original Plan**: Mentions "touch-friendly" but lacks specifics.

**Critical Additions Needed**:
- Touch target sizes (44x44px minimum)
- Long-press gesture implementation (500ms)
- Keyboard navigation (Arrow keys, Enter, Escape)
- Screen reader support (ARIA labels, live regions)
- Focus management (focus trap, return focus)
- Mobile viewport edge detection for context menu
- Virtual keyboard handling

**See detailed implementation in "Accessibility & Mobile" section below.**

### 5. **Widget ID Mapping: Simple Fix**

**Current State**: Widgets wrapped but ID not passed.

**Fix**: In `page.php` line 962 and 1226:
```php
if ($enablePreviewMode) {
  echo '<div class="widget-wrapper" data-hotspot="widget-settings" data-widget-id="' . htmlspecialchars($widget['id']) . '">';
}
```

**Also**: Update postMessage in `page.php`:
```javascript
window.parent.postMessage({
  type: 'hotspot-click',
  sectionId: hotspot.dataset.hotspot,
  widgetId: hotspot.closest('[data-widget-id]')?.dataset.widgetId || null
}, '*');
```

### 6. **Context Menu Component: Use Radix UI Popover**

**Original Plan**: Mentions creating custom context menu.

**Existing Pattern**: Codebase uses Radix UI Popover (see `LeftyProfileSection.tsx`).

**Recommendation**: Use Radix UI Popover for context menu (similar pattern to existing code):
- Already in dependencies
- Accessible by default
- Handles positioning automatically
- Mobile-friendly

**Alternative**: If need true context menu behavior, consider `@radix-ui/react-context-menu` (not currently in codebase, would need to add).

### 7. **CSS Variable Injection: Server-Side Better**

**Current State**: `ThemePreview.tsx` tries client-side injection (may fail).

**Better Solution**: Inject CSS vars server-side in `page.php` when `preview_mode=1`.

**Implementation**: Pass CSS vars from React to PHP via URL parameter or fetch on iframe load.

### 8. **Phase Ordering: Reorder for Value**

**Original Order**: Phase 1 → Phase 2 (Floating) → Phase 3 (Drag) → Phase 4 (Features)

**Better Order**:
1. **Phase 1**: Enhanced hotspots + context menu (foundation)
2. **Phase 2**: Content editing integration (immediate value)
3. **Phase 3**: Additional layer features (visibility, delete)
4. **Phase 4**: Drag-and-drop (complex, defer if not critical)

## Revised Implementation Plan

### Phase 1: Enhanced Hotspots with Context Menu (Foundation)

**Goal**: Enable widget identification and context menu for content/style editing

**Tasks**:
1. Add `data-widget-id` to widget wrappers in `page.php` (2 locations)
2. Update postMessage in `page.php` to include widget ID
3. Update `ThemePreview.tsx` to receive widget ID in hotspot clicks
4. Create `ContextMenu` component using Radix UI Popover
5. Show context menu on right-click (desktop) or long-press (mobile)
6. Context menu options: "Edit Style" | "Edit Content"

**Files to modify**:
- `page.php`: Add widget ID to wrapper, include in postMessage
- `admin-ui/src/components/panels/themes/preview/ThemePreview.tsx`: Handle widget ID, show context menu
- `admin-ui/src/components/panels/themes/ThemeEditorView.tsx`: Add content editor state

**Files to create**:
- `admin-ui/src/components/panels/themes/preview/ContextMenu.tsx`
- `admin-ui/src/components/panels/themes/preview/context-menu.module.css`

**Accessibility Requirements**:
- Minimum 48px height for menu items
- Keyboard navigation (Arrow keys, Enter, Escape)
- ARIA labels and roles
- Focus management

**Mobile Requirements**:
- Long-press gesture (500ms)
- Touch target sizes (44x44px minimum)
- Viewport edge detection for positioning

### Phase 2: Content Editing Integration

**Goal**: Enable widget content editing from preview

**Tasks**:
1. Add `contentEditorWidgetId` state to `ThemeEditorView`
2. Add `WidgetInspectorModal` to `ThemeEditorView`
3. Handle "Edit Content" action from context menu
4. Coordinate between content and style editors (close one when opening other, or allow both)

**Files to modify**:
- `admin-ui/src/components/panels/themes/ThemeEditorView.tsx`: Add content editor state and modal
- `admin-ui/src/components/panels/themes/preview/ContextMenu.tsx`: Add "Edit Content" option

**Decision Point**: Should both editors be open simultaneously, or should opening one close the other?
- **Recommendation**: Close style editor when opening content editor (and vice versa) for cleaner UX.

### Phase 3: Additional Layer Features

**Goal**: Add visibility, delete, lock from preview

**Tasks**:
1. Add "Toggle Visibility" to context menu
2. Add "Delete Widget" to context menu (with confirmation dialog)
3. Add "Lock/Unlock" to context menu (if needed)
4. Call appropriate mutations (`useUpdateWidgetMutation`, `useDeleteWidgetMutation`)

**Files to modify**:
- `admin-ui/src/components/panels/themes/preview/ContextMenu.tsx`: Add layer actions
- `admin-ui/src/components/panels/themes/ThemeEditorView.tsx`: Handle layer mutations

**Note**: Keep Layers Panel as primary interface for reordering (drag-and-drop). Preview focuses on editing.

### Phase 4: Drag-and-Drop (Optional/Deferred)

**Goal**: Enable reordering directly from preview

**Status**: Defer until after Phase 1-3 validated. Layers Panel works well for reordering.

## Accessibility & Mobile Implementation Details

### Touch Target Sizes (WCAG 2.1 AA)

```css
.contextMenuItem {
  min-height: 48px;
  padding: 12px 16px;
  touch-action: manipulation; /* Prevents double-tap zoom */
  font-size: 16px; /* Prevents iOS zoom on focus */
}

.hotspotIndicator {
  width: 24px;
  height: 24px;
  /* Expand hit area */
  padding: 10px;
  margin: -10px;
}
```

### Mobile Interaction: Long-Press

```typescript
const LONG_PRESS_DURATION = 500;

const handleTouchStart = (e: TouchEvent, widgetId: string) => {
  const touch = e.touches[0];
  const startTime = Date.now();
  const startX = touch.clientX;
  const startY = touch.clientY;
  
  const handleTouchEnd = () => {
    const duration = Date.now() - startTime;
    const moved = Math.abs(touch.clientX - startX) > 10 || 
                  Math.abs(touch.clientY - startY) > 10;
    
    if (duration >= LONG_PRESS_DURATION && !moved) {
      e.preventDefault();
      showContextMenu(touch.clientX, touch.clientY, widgetId);
    }
    
    document.removeEventListener('touchend', handleTouchEnd);
    document.removeEventListener('touchmove', handleTouchMove);
  };
  
  const handleTouchMove = () => {
    // Cancel if user moves finger
    document.removeEventListener('touchend', handleTouchEnd);
    document.removeEventListener('touchmove', handleTouchMove);
  };
  
  document.addEventListener('touchend', handleTouchEnd, { once: true });
  document.addEventListener('touchmove', handleTouchMove, { once: true });
};
```

### Keyboard Navigation

```typescript
// Context menu keyboard navigation
const handleContextMenuKeyDown = (e: KeyboardEvent) => {
  const items = Array.from(menuRef.current?.querySelectorAll('[role="menuitem"]') || []);
  const currentIndex = items.indexOf(document.activeElement as HTMLElement);
  
  switch (e.key) {
    case 'ArrowDown':
      e.preventDefault();
      const nextIndex = (currentIndex + 1) % items.length;
      (items[nextIndex] as HTMLElement)?.focus();
      break;
    case 'ArrowUp':
      e.preventDefault();
      const prevIndex = (currentIndex - 1 + items.length) % items.length;
      (items[prevIndex] as HTMLElement)?.focus();
      break;
    case 'Escape':
      e.preventDefault();
      closeContextMenu();
      break;
    case 'Enter':
    case ' ':
      e.preventDefault();
      (document.activeElement as HTMLElement)?.click();
      break;
  }
};
```

### Screen Reader Support

```typescript
// Context menu ARIA
<div
  role="menu"
  aria-label="Widget actions"
  aria-orientation="vertical"
  onKeyDown={handleContextMenuKeyDown}
>
  <button
    role="menuitem"
    aria-label="Edit widget content"
    onClick={() => openContentEditor(widgetId)}
  >
    Edit Content
  </button>
  <button
    role="menuitem"
    aria-label="Edit widget style"
    onClick={() => openStyleEditor(sectionId)}
  >
    Edit Style
  </button>
</div>

// Live region for announcements
<div
  role="status"
  aria-live="polite"
  aria-atomic="true"
  className="sr-only"
>
  {announcement}
</div>
```

### Mobile Viewport Edge Detection

```typescript
const positionContextMenu = (x: number, y: number) => {
  const menuWidth = 200;
  const menuHeight = 300;
  const viewportWidth = window.innerWidth;
  const viewportHeight = window.innerHeight;
  const safeAreaBottom = 34; // Home indicator height
  
  // Adjust if near right edge
  if (x + menuWidth > viewportWidth) {
    x = viewportWidth - menuWidth - 16;
  }
  
  // Adjust if near bottom edge
  if (y + menuHeight > viewportHeight - safeAreaBottom) {
    y = viewportHeight - menuHeight - safeAreaBottom - 16;
  }
  
  // Ensure minimum distance from edges
  x = Math.max(16, Math.min(x, viewportWidth - menuWidth - 16));
  y = Math.max(16, Math.min(y, viewportHeight - menuHeight - safeAreaBottom - 16));
  
  return { x, y };
};
```

## Questions Resolved

1. **Layers Panel**: Keep as fallback for reordering, focus preview on editing
2. **Add Widget**: Keep in Layers Panel for now, add to preview later if needed
3. **Drag Feedback**: Defer to Phase 4, use Layers Panel for now
4. **Hidden Widgets**: Handle in context menu - show "Show Widget" option if hidden
5. **Both Editors Open**: Close one when opening the other (recommendation)

## Success Criteria

- ✅ Widget hotspots show context menu with "Edit Style" and "Edit Content"
- ✅ Content editing opens WidgetInspectorModal with correct widget
- ✅ Style editing opens ThemePropertyDrawer (existing)
- ✅ Widget IDs properly mapped and passed through
- ✅ Non-widget hotspots only show style editing
- ✅ Context menu works on right-click (desktop) and long-press (mobile)
- ✅ Keyboard navigation works (Arrow keys, Enter, Escape)
- ✅ Screen reader announces menu and actions
- ✅ Touch targets meet WCAG 2.1 AA (44x44px minimum)
- ✅ Context menu positions correctly near viewport edges

## Risks & Mitigations

1. **Risk**: Context menu positioning on mobile near edges
   - **Mitigation**: Implement viewport edge detection with safe area insets

2. **Risk**: Long-press conflicts with native browser actions
   - **Mitigation**: Use 500ms duration, cancel on movement, test on iOS/Android

3. **Risk**: CSS var injection timing
   - **Mitigation**: Server-side injection in PHP, or fetch on iframe load

4. **Risk**: Widget ID not available in all widget types
   - **Mitigation**: Ensure WidgetRenderer always has access to widget ID, add to wrapper

5. **Risk**: Both modals open simultaneously causing confusion
   - **Mitigation**: Close one when opening the other (recommended approach)

## Testing Checklist

**Accessibility**:
- [ ] Screen reader (VoiceOver, TalkBack, NVDA)
- [ ] Keyboard-only navigation
- [ ] Focus indicators visible
- [ ] Focus trap in modals
- [ ] ARIA labels and roles
- [ ] High contrast mode

**Mobile**:
- [ ] iOS Safari (iPhone 12+, iPad)
- [ ] Android Chrome
- [ ] Long-press gesture (500ms)
- [ ] Context menu positioning near edges
- [ ] Virtual keyboard handling
- [ ] Safe area insets (notches)
- [ ] Touch targets (44x44px minimum)
- [ ] Performance on low-end devices

**Desktop**:
- [ ] Right-click context menu
- [ ] Keyboard navigation
- [ ] Mouse interactions
- [ ] Multiple monitor setups

## Next Steps

1. **Approve Approach**: Confirm Option 2 (Context Menu) as primary
2. **Implement Phase 1**: Enhanced hotspots + context menu
3. **Test**: Validate with screen reader and mobile devices
4. **Iterate**: Based on feedback
5. **Proceed to Phase 2**: Content editing integration

---

**Status**: Ready for Implementation  
**Last Updated**: 2025-01-15  
**Reviewer**: AI Assistant  
**Approval**: Pending

