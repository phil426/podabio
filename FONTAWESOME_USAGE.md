# Font Awesome Integration

Font Awesome 6.5.1 has been integrated into Podn.Bio for easy icon usage.

## CDN Link

Font Awesome is loaded via CDN. Include this in your page `<head>`:

```html
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
```

## Helper Function

Use the `fa_icon()` helper function (available in `includes/helpers.php`):

```php
<?php echo fa_icon('user'); ?>                    // Solid user icon
<?php echo fa_icon('user', 'regular'); ?>         // Regular (outline) user icon
<?php echo fa_icon('github', 'brands'); ?>         // Brands GitHub icon
<?php echo fa_icon('envelope', 'solid', 'fa-lg'); ?> // Large envelope icon
```

## Direct HTML Usage

You can also use Font Awesome icons directly in HTML:

```html
<i class="fas fa-user"></i>           <!-- Solid user -->
<i class="far fa-user"></i>           <!-- Regular user -->
<i class="fab fa-github"></i>         <!-- Brands GitHub -->
<i class="fas fa-envelope fa-lg"></i> <!-- Large envelope -->
```

## Common Icons for Podn.Bio

### User & Account
- `fa-user` - User profile
- `fa-user-circle` - User circle
- `fa-cog` / `fa-gear` - Settings
- `fa-sign-out-alt` / `fa-right-from-bracket` - Logout

### Navigation & Actions
- `fa-home` - Home
- `fa-edit` / `fa-pen` - Edit
- `fa-trash` - Delete
- `fa-plus` - Add/Create
- `fa-check` - Confirm/Success
- `fa-times` / `fa-xmark` - Cancel/Close

### Social Media & Links
- `fa-link` - Link
- `fa-external-link` - External link
- `fa-share` - Share
- `fa-heart` - Favorite/Like

### Podcast & Media
- `fa-podcast` - Podcast
- `fa-microphone` - Microphone
- `fa-headphones` - Headphones
- `fa-music` - Music
- `fa-play` - Play
- `fa-download` - Download

### Communication
- `fa-envelope` - Email
- `fa-envelope-open` - Open email
- `fa-bell` - Notifications
- `fa-comment` - Comment

### Status & Feedback
- `fa-check-circle` - Success
- `fa-exclamation-circle` - Warning
- `fa-info-circle` - Information
- `fa-times-circle` - Error
- `fa-spinner` - Loading

### Payment & Subscriptions
- `fa-credit-card` - Payment
- `fa-dollar-sign` - Price
- `fa-star` - Premium/Featured
- `fa-crown` - Pro plan

## Icon Styles

- **Solid** (`fas`): Filled icons (most common)
- **Regular** (`far`): Outline icons
- **Brands** (`fab`): Brand logos (social media, etc.)

## Sizing

Add size classes:
- `fa-xs` - Extra small
- `fa-sm` - Small
- `fa-lg` - Large
- `fa-xl` - Extra large
- `fa-2x`, `fa-3x`, etc. - 2x, 3x multiplier

## Pages with Font Awesome Already Included

- ✅ Dashboard (`dashboard.php`)
- ✅ Landing page (`index.php`)
- ✅ Login (`login.php`)
- ✅ Signup (`signup.php`)

## Adding to Other Pages

To add Font Awesome to any other page, include this in the `<head>` section:

```html
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
```

## Full Documentation

For the complete icon library, visit: https://fontawesome.com/icons

