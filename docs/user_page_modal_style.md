# User Page Modal Style Guide (High-End Charcoal)

This document outlines the styling specification for "User Page Modals" (e.g., Contact Form, Newsletter Signup). Use this guide when creating new widgets to ensure a consistent, premium aesthetic.

## 1. Core Aesthetic Principles

The "High-End Charcoal" design is defined by:
-   **Dark & Immersive:** A rich charcoal background (`#1F1F1F`) that feels premium and solid.
-   **Soft & Readable:** High-contrast Soft White text (`#E5E5E5`) using the **Inter** typeface.
-   **Dense & Maximized:** Layouts are packed efficiently with minimal wasted space, using wide input fields.
-   **Mobile-First but Desktop-Optimized:** Styles defaults to mobile-width behavior but must be confined to the `iphone-content` frame on desktop.

## 2. Global Tokens

| Token | Value | Description |
| :--- | :--- | :--- |
| **Background** | `#1F1F1F` | The main modal container background. |
| **Text Primary** | `#E5E5E5` | Main headings, body text, and links. |
| **Text Secondary** | `#A3A3A3` | Labels, metadata, and placeholder text. |
| **Input Background** | `#2A2A2A` | Slightly lighter charcoal for form fields. |
| **Input Text** | `#FFFFFF` | Pure white text inside inputs for max contrast. |
| **Button Background** | `#E5E5E5` | Soft white primary action (high contrast). |
| **Button Text** | `#1F1F1F` | Charcoal text on buttons. |
| **Font Family** | `Inter, sans-serif` | Enforced globally for all modal elements. |
| **Global Padding** | `1.25rem 0.85rem` | Vertical / Horizontal padding for specific density. |

## 3. Component Styling

### Modal Container & Overlay
Structure your modal with a wrapper overlay and a main container.

```css
/* Overlay */
.contact-overlay {
    background: rgba(0, 0, 0, 0.5); /* Dimmed backdrop */
    /* ... positioning properties ... */
}

/* Container */
.contact-modal {
    background: #1F1F1F;
    color: #E5E5E5;
    border-radius: 0; /* Square edges if filling frame */
    display: flex;
    flex-direction: column;
}
```

### Header
Headers should be concise, uppercase, and uppercase tracked.

```css
.modal-header {
    padding: 1.25rem 0.85rem;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    background: #1F1F1F;
}

.modal-header h3 {
    font-family: 'Inter', sans-serif;
    color: #E5E5E5;
    text-transform: uppercase;
    font-weight: 700;
    letter-spacing: -0.02em;
    font-size: 1.1rem;
}
```

### Form Fields (Dense & Wide)
Inputs should stretch to fill the width. Labels are small and uppercase.

```css
/* Label */
label {
    display: block;
    margin-bottom: 0.35rem;
    font-size: 0.7rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #A3A3A3;
    font-weight: 600;
}

/* Input / Textarea */
.form-control {
    width: 100%;
    background: #2A2A2A;
    color: #FFFFFF;
    border: 1px solid transparent;
    padding: 0.85rem 1rem;
    border-radius: 10px;
    font-family: 'Inter', sans-serif;
    font-size: 0.95rem;
}

.form-control:focus {
    background: #333333;
    border-color: rgba(255, 255, 255, 0.2);
    /* Soft white glow */
    box-shadow: 0 0 0 4px rgba(255, 255, 255, 0.05); 
    outline: none;
}
```

### Buttons
Primary actions should anchor to the bottom if possible and contrast heavily.

```css
.btn-primary {
    width: 100%;
    padding: 1.125rem;
    background: #E5E5E5; /* Soft White */
    color: #1F1F1F;    /* Charcoal Text */
    font-weight: 600;
    border-radius: 12px;
    margin-top: auto; /* Push to bottom */
    box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.3);
}

.btn-primary:hover {
    background: #FFFFFF;
    box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.4);
}
```

## 4. Implementation Best Practices

1.  **Global Scope:** Do **not** wrap these styles in desktop-only media queries (`min-width: 1024px`). The mobile preview frame on desktop often mimics a mobile width, so styles must be global or scoped to `.desktop-frame-container`.
2.  **Flexbox Layout:** Use `display: flex; flex-direction: column; height: 100%;` on the modal container to ensure it fills the screen/frame.
3.  **Variable Override:** If using theme variables, ensure you have a robust fallback or use `!important` to enforce this specific "Charcoal" look if the user requests it specifically, otherwise aim for the "Theme-Adaptive" approach (see Widget Styles).

## 5. Standard CSS Class References

Use these standard classes to inherit the High-End Charcoal design automatically (once added to `widget-styles.css`):

*   `.contact-modal` (Container)
*   `.modal-header`
*   `.modal-content`
*   `.form-group`
*   `.form-control`
*   `.btn-primary`

---
*Created by Antigravity, Dec 2025*
