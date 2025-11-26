# Poda Color

A compact, accessible color picker component for React supporting both solid colors and gradients.

## Features

- 🎨 **Solid & Gradient Support**: Switch between solid colors and linear gradients
- 🖱️ **Reliable Dragging**: Built with `react-colorful` and Radix UI for cross-browser dragging support
- 👁️ **Eyedropper Support**: Built-in browser eyedropper API support
- 📱 **Accessible**: Full keyboard navigation and ARIA labels
- 🎯 **Compact Design**: Minimal UI that fits in tight spaces
- 🔄 **Synchronized Colors**: Color 1 and solid color stay in sync

## Installation

```bash
npm install @poda-bio/poda-color
```

## Usage

```tsx
import { PodaColorPicker } from '@poda-bio/poda-color';

function MyComponent() {
  const [color, setColor] = useState('linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)');
  
  return (
    <PodaColorPicker
      value={color}
      onChange={setColor}
    />
  );
}
```

## Props

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `value` | `string` | `'linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)'` | Current color value (hex for solid, gradient string for gradients) |
| `onChange` | `(value: string) => void` | - | Callback when color changes |

## License

MIT

## Attribution

This component is built with the following excellent libraries:

### react-colorful
- **Repository**: https://github.com/omgovich/react-colorful
- **Author**: Vlad Shilov (omgovich@ya.ru)
- **License**: MIT
- **Copyright**: Copyright (c) 2020 Vlad Shilov omgovich@ya.ru

### Radix UI
- **Website**: https://www.radix-ui.com/
- **License**: MIT
- **Copyright**: Copyright (c) 2023 WorkOS

### Phosphor Icons
- **Repository**: https://github.com/phosphor-icons/core
- **License**: MIT

## Repository

This component is part of the PodaBio project. For standalone use, it can be extracted into its own repository at `github.com/poda-bio/poda-color`.

