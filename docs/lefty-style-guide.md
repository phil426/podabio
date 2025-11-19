# Lefty Style Guide

This document defines the design patterns, component standards, and layout guidelines for the Lefty admin interface.

## Table of Contents

1. [Property Panel Layout](#property-panel-layout)
2. [Universal Components](#universal-components)
3. [Field Components](#field-components)
4. [Layout Patterns](#layout-patterns)
5. [Component Specifications](#component-specifications)

---

## Property Panel Layout

The property panel layout is the standard pattern for all settings/inspector panels in Lefty. This layout provides a consistent, responsive interface for editing properties.

### Structure

```
┌─────────────────────────────────┐
│  Panel Container                │
│  ┌───────────────────────────┐ │
│  │  Content Wrapper          │ │
│  │  ┌─────────────────────┐  │ │
│  │  │  Controls Container  │  │ │
│  │  │                     │  │ │
│  │  │  Control Row        │  │ │
│  │  │  [Label] [Control]  │  │ │
│  │  │                     │  │ │
│  │  │  Section            │  │ │
│  │  │  ┌───────────────┐  │  │ │
│  │  │  │ Section Title │  │  │ │
│  │  │  │ Control Rows  │  │  │ │
│  │  │  └───────────────┘  │  │ │
│  │  └─────────────────────┘  │ │
│  └───────────────────────────┘ │
└─────────────────────────────────┘
```

### Key Principles

1. **Responsive Design**: All panels must be responsive and avoid scrollbars when possible
2. **Compressed Layout**: Content should be compact with minimal padding
3. **Consistent Spacing**: Use standardized gaps and padding throughout
4. **Clear Hierarchy**: Use sections to group related controls
5. **Flexible Controls**: Controls should adapt to available space

### CSS Classes

- `.panel` - Main container
- `.content` - Content wrapper with padding
- `.controls` - Controls container with gap spacing
- `.controlRow` - Individual control row (label + control)
- `.controlLabel` - Label for controls (min-width: 100px)
- `.section` - Section container for grouped controls
- `.sectionTitle` - Section heading (h4)

### Spacing Standards

- Panel padding: `1rem` (0.75rem on mobile)
- Controls gap: `1rem`
- Section gap: `0.875rem`
- Control row gap: `0.75rem`
- Label min-width: `100px`

---

## Universal Components

### BackgroundColorSwatch

**Location**: `admin-ui/src/components/controls/BackgroundColorSwatch.tsx`

**Purpose**: Universal background color picker used throughout the project for any background color selection (solid, gradient, or image).

**Usage**:
```tsx
import { BackgroundColorSwatch } from '../../controls/BackgroundColorSwatch';

<BackgroundColorSwatch
  value={pageBackground}
  backgroundType={backgroundType}
  backgroundImage={backgroundImage}
  onChange={(value) => handleColorChange('semantic.surface.canvas', value)}
  onTypeChange={(type) => {
    if (type === 'solid') {
      handleColorChange('semantic.surface.canvas', '#FFFFFF');
    } else if (type === 'gradient') {
      if (!pageBackground.includes('gradient')) {
        handleColorChange('semantic.surface.canvas', 'linear-gradient(...)');
      }
    }
  }}
  onImageChange={(url) => {
    if (url) {
      handleColorChange('semantic.surface.canvas', url);
    }
  }}
  label="Background color"
/>
```

**Props**:
- `value: string` - Current background value (hex color, gradient string, or image URL)
- `backgroundType: 'solid' | 'gradient' | 'image'` - Current background type
- `backgroundImage?: string | null` - Image URL if type is 'image'
- `onChange: (value: string) => void` - Called when background value changes
- `onTypeChange: (type: 'solid' | 'gradient' | 'image') => void` - Called when background type changes
- `onImageChange?: (url: string) => void` - Called when image URL changes
- `label: string` - Accessibility label

**Features**:
- Circular swatch button (32x32px)
- Popover with tabs for Solid/Gradient/Image
- Supports hex colors, CSS gradients, and image URLs
- Visual preview in swatch (solid color, gradient, or image thumbnail)
- Uses `PageBackgroundPicker` for solid and gradient modes
- File upload support for images

**Important**: This component MUST be used for all background color selections throughout Lefty. Do not create alternative background pickers.

---

## Field Components

### ColorSwatch

**Purpose**: Simple color picker for solid colors (text colors, border colors, etc.)

**Usage**:
```tsx
<ColorSwatch
  value={headingColor}
  onChange={(value) => handleColorChange('core.color.text.heading', value)}
  label="Heading color"
/>
```

**Features**:
- Circular swatch button (32x32px)
- Radix UI Popover with HexColorPicker
- Real-time color preview
- Supports hex color input

### FontSelect

**Location**: `admin-ui/src/components/panels/ultimate-theme-modifier/FontSelect.tsx`

**Purpose**: Font family selector dropdown

**Usage**:
```tsx
<FontSelect
  value={headingFont}
  onChange={(value) => handleFontChange('core.typography.font.heading', value)}
  options={availableFonts}
/>
```

**Props**:
- `value: string` - Current font selection
- `onChange: (value: string) => void` - Called when font changes
- `options: string[]` - Array of available font names

**Features**:
- Radix UI Select component
- Compact, responsive design
- Custom styling to match Lefty design system

### ColorSwatch

**Location**: Defined inline in `PageSettingsPanel.tsx` (can be extracted to shared component if needed)

**Purpose**: Simple color picker for solid colors (text colors, border colors, etc.)

**Usage**:
```tsx
<ColorSwatch
  value={headingColor}
  onChange={(value) => handleColorChange('core.color.text.heading', value)}
  label="Heading color"
/>
```

**Props**:
- `value: string` - Current color value (hex)
- `onChange: (value: string) => void` - Called when color changes
- `label: string` - Accessibility label

**Features**:
- Circular swatch button (32x32px)
- Radix UI Popover with HexColorPicker
- Real-time color preview
- Supports hex color input
- Temporary color state for smooth interaction

### SliderInput

**Location**: `admin-ui/src/components/panels/ultimate-theme-modifier/SliderInput.tsx`

**Purpose**: Numeric input with slider control

**Usage**:
```tsx
<SliderInput
  label="Text size"
  value={headingSize}
  min={8}
  max={72}
  step={1}
  unit="px"
  onChange={(value) => handleSizeChange('core.typography.size.heading', value)}
/>
```

**Props**:
- `value: number` - Current numeric value
- `min?: number` - Minimum value (default: 0)
- `max?: number` - Maximum value (default: 100)
- `step?: number` - Step increment (default: 1)
- `unit?: string` - Single unit display (e.g., "px", "rem")
- `units?: string[]` - Array of units for selector dropdown
- `onChange: (value: number) => void` - Called when value changes
- `disabled?: boolean` - Disable the control
- `label?: string` - Optional label (if not using controlRow pattern)

**Features**:
- Radix UI Slider for smooth interaction
- Number input for precise values
- Unit display or selector dropdown
- Input validation (min/max enforcement)
- Responsive layout

---

## Layout Patterns

### Control Row Pattern

Every control in a property panel follows this pattern:

```tsx
<div className={styles.controlRow}>
  <label className={styles.controlLabel}>Label Text</label>
  <ControlComponent {...props} />
</div>
```

**Layout**:
- Flexbox row layout
- Label on left (min-width: 100px)
- Control on right (flexible)
- Gap: 0.75rem

### Section Pattern

Group related controls into sections:

```tsx
<div className={styles.section}>
  <h4 className={styles.sectionTitle}>Section Name</h4>
  <div className={styles.controlRow}>
    {/* Controls */}
  </div>
</div>
```

**Layout**:
- Section title (h4)
- Control rows below
- Gap: 0.875rem between sections

### Example: Page Settings Panel

```tsx
<div className={styles.panel}>
  <div className={styles.content}>
    <div className={styles.controls}>
      {/* Background color */}
      <div className={styles.controlRow}>
        <label className={styles.controlLabel}>Background color</label>
        <BackgroundColorSwatch {...props} />
      </div>

      {/* Heading section */}
      <div className={styles.section}>
        <h4 className={styles.sectionTitle}>Heading</h4>
        <div className={styles.controlRow}>
          <label className={styles.controlLabel}>Heading color</label>
          <ColorSwatch {...props} />
        </div>
        <div className={styles.controlRow}>
          <label className={styles.controlLabel}>Heading font</label>
          <FontSelect {...props} />
        </div>
        <div className={styles.controlRow}>
          <label className={styles.controlLabel}>Text size</label>
          <SliderInput {...props} />
        </div>
      </div>

      {/* Body section */}
      <div className={styles.section}>
        <h4 className={styles.sectionTitle}>Body</h4>
        {/* Similar control rows */}
      </div>
    </div>
  </div>
</div>
```

---

## Component Specifications

### BackgroundColorSwatch Specifications

**Visual**:
- Swatch button: 32x32px circular button
- Border: 1px solid rgba(0, 0, 0, 0.15)
- Border radius: 50% (circular)
- Swatch inner: 24x24px
- Hover: Border color change + subtle shadow

**Popover**:
- Width: min 300px, max 400px
- Padding: 1rem
- Border radius: 0.5rem
- Shadow: Multi-layer shadow for depth
- Tabs: Three tabs (Solid, Gradient, Image) with icons

**Icons** (Phosphor Icons):
- Solid: `Square` (size 14)
- Gradient: `Sparkle` (size 14)
- Image: `Image` (size 14)

### ColorSwatch Specifications

**Visual**:
- Swatch button: 32x32px circular button
- Same styling as BackgroundColorSwatch button
- Popover: Contains HexColorPicker only

### FontSelect Specifications

**Visual**:
- Trigger: Compact dropdown
- Min-width: 100px
- Max-width: 160px
- Padding: 0.3125rem 0.625rem
- Font size: 0.75rem
- Border radius: 0.375rem

### SliderInput Specifications

**Location**: `admin-ui/src/components/panels/ultimate-theme-modifier/slider-input.module.css`

**Layout**:
- Container: Flex column or row (depending on label)
- Slider: Radix UI Slider with custom track/thumb styling
- Input wrapper: Contains number input and unit
- Number input: Compact width
- Unit: Displayed next to input or as selector

**Visual**:
- Uses Radix UI Slider primitives
- Custom track and thumb styling
- Number input with validation
- Unit selector dropdown if multiple units provided

---

## Responsive Design

### Breakpoints

- Mobile: < 768px
- Tablet: 768px - 1024px
- Desktop: > 1024px

### Responsive Adjustments

1. **Padding**: Reduced from 1rem to 0.75rem on mobile
2. **Control Rows**: Can wrap on very small screens if needed
3. **Font Selects**: Adjust min/max width based on available space
4. **Sliders**: Maintain flexibility, never overflow container

### Overflow Prevention

- Set `overflow: visible` on `.panel`, `.content`, `.controls`
- Use `max-height: 100%` on `.panel`
- Ensure controls are flexible and don't force horizontal scroll

---

## Implementation Checklist

When creating a new property panel:

- [ ] Use the standard panel structure (`.panel` > `.content` > `.controls`)
- [ ] Use `.controlRow` for each control (label + control)
- [ ] Use `.section` to group related controls
- [ ] Use `BackgroundColorSwatch` for any background color selection
- [ ] Use `ColorSwatch` for text/border colors
- [ ] Use `FontSelect` for font selections
- [ ] Use `SliderInput` for numeric values with ranges
- [ ] Ensure responsive design (no scrollbars)
- [ ] Test on mobile, tablet, and desktop
- [ ] Verify all controls are accessible (labels, ARIA attributes)

---

## Reference Implementation

See `admin-ui/src/components/panels/ultimate-theme-modifier/PageSettingsPanel.tsx` for a complete reference implementation of the property panel pattern.

---

## Updates

- **2024-01-XX**: Initial style guide created
- Documented property panel layout pattern
- Established `BackgroundColorSwatch` as universal background picker
- Defined field component standards
- Added responsive design guidelines

