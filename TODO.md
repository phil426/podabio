# TODO List & Notes

## High Priority

- [ ] Review and test recent changes to social icons accordion and drag-and-drop
- [ ] Verify preview alignment with center column horizontal rule
- [ ] Test mobile hamburger menu functionality

## Medium Priority

- [ ] Review toast notification system (recently simplified)
- [ ] Check spatial effects (glass and floating removed)
- [ ] Verify all accordion animations are consistent

## Low Priority

- [ ] Documentation updates
- [ ] Code cleanup and optimization opportunities

## Completed ✓

- [x] Make social icons editable with accordion interface
- [x] Restore drag and drop reorder functionality for social icons
- [x] Remove preview header and align preview top with center column
- [x] Simplify toast notification to rounded square with fade animation
- [x] Fix toast z-index above navbar and add color coding
- [x] Move hamburger menu to navbar (mobile only)
- [x] Make Featured Widget Effect star icon gold in Appearance section
- [x] Fix spacing between Appearance heading and Theme accordion
- [x] Add accelerometer tilt effect to Spatial Effects

---

# Notes

## Architecture Decisions

### Social Icons Management
- Converted to accordion-style interface similar to widgets
- Drag-and-drop reordering implemented with separate system from widgets
- Inline editing form allows editing platform and URL without modal

### Preview Panel
- Removed header to save space
- Aligned top with center column's horizontal rule (editor-header border-bottom)
- Uses same background color (#f3f4f6) as second column

### Toast Notifications
- Simplified to rounded square design
- Fade in/out animation (no sliding)
- Positioned at top center of page
- Color-coded by type (success: green, error: red, info: blue)
- Z-index 10001 to appear above navbar

### Mobile Navigation
- Hamburger menu positioned in navbar (top left)
- Only visible in mobile view (max-width: 768px)
- Logo on left, hamburger on right in mobile view

## Recent Changes

### Social Icons (Latest)
- Added accordion-style editing interface
- Each social icon can be expanded to edit platform and URL
- Drag handle added for reordering
- API endpoints: `update_directory`, `reorder_social_icons`
- Backend methods: `updateSocialIcon()`, `reorderSocialIcons()`

### Preview Panel
- Header removed (Live Preview title and close button)
- Top aligned with center column's horizontal rule
- Padding-top calculated to match editor-header height + padding + border

### Toast System
- Simplified design (removed icons, close buttons, gradients)
- Centered at top of page
- Simple fade animation

## Known Issues / Future Improvements

- Consider adding keyboard shortcuts for common actions
- Review mobile responsiveness across all sections
- Consider adding undo/redo functionality for editor changes
- Analytics improvements (currently basic implementation)

## Code Patterns

### Accordion Pattern
- Uses `toggleAccordion()` function for appearance sections
- Widgets use dropdown pattern (absolute positioning)
- Social icons use inline accordion (relative positioning)
- Only one widget/social icon accordion open at a time

### Drag and Drop
- Widgets: `initWidgetDragAndDrop()` - saves via `saveWidgetOrder()`
- Social Icons: `initSocialIconDragAndDrop()` - saves via `saveSocialIconOrder()`
- Separate systems to avoid conflicts

### API Pattern
- All API calls use POST method
- CSRF token required
- Consistent error handling with toast notifications

