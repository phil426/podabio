# Widget Functions and Features Guide

## Table of Contents
1. [Overview](#overview)
2. [Widget Registry](#widget-registry)
3. [Widget Types](#widget-types)
4. [Widget Management](#widget-management)
5. [Widget Configuration](#widget-configuration)
6. [Featured Widgets](#featured-widgets)
7. [Widget Styling](#widget-styling)
8. [Widget Rendering](#widget-rendering)
9. [Admin UI Components](#admin-ui-components)
10. [API Endpoints](#api-endpoints)
11. [Database Schema](#database-schema)

---

## Overview

Widgets are the building blocks of a PodaBio page. Each widget represents a single interactive element (link, video, text, etc.) that users can add, configure, and arrange on their page. Widgets are managed through the admin interface and rendered on the public-facing page.

### Key Concepts
- **Widget Type**: The category/functionality of a widget (e.g., `custom_link`, `youtube_video`)
- **Widget Instance**: A specific widget added to a page with its own configuration
- **Widget Configuration**: Settings specific to each widget instance (stored in `config_data` JSON)
- **Widget Registry**: Central registry defining all available widget types and their metadata
- **Widget Renderer**: PHP class that converts widget data into HTML for public pages

---

## Widget Registry

The `WidgetRegistry` class (`classes/WidgetRegistry.php`) defines all available widgets in the system.

### Structure

Each widget definition includes:
- `widget_id`: Unique identifier (e.g., `custom_link`)
- `name`: Display name (e.g., "Custom Link")
- `description`: Brief description
- `thumbnail`: Path to thumbnail image
- `category`: Category grouping (`links`, `videos`, `content`, `podcast`, `social`, `forms`, `ecommerce`, `advanced`)
- `requires_api`: Boolean indicating if external API configuration is needed
- `config_fields`: Array of configuration fields with types, labels, and validation rules

### Available Methods

```php
// Get all widgets
WidgetRegistry::getAllWidgets()

// Get widget by ID
WidgetRegistry::getWidget($widgetId)

// Get widgets by category
WidgetRegistry::getWidgetsByCategory($category)

// Get all categories
WidgetRegistry::getCategories()

// Check if widget exists
WidgetRegistry::widgetExists($widgetId)

// Get available widgets (filter out "coming soon")
WidgetRegistry::getAvailableWidgets($includeComingSoon = false)
```

### Configuration Field Types

- `text`: Single-line text input
- `textarea`: Multi-line text input
- `url`: URL input with validation
- `select`: Dropdown with options
- `checkbox`: Boolean checkbox
- `number`: Numeric input

Each field can have:
- `type`: Field type
- `label`: Display label
- `required`: Boolean (default: false)
- `help`: Help text
- `placeholder`: Placeholder text
- `default`: Default value
- `options`: For select fields (array or string reference)

---

## Widget Types

### Links Category

#### Custom Link (`custom_link`)
- **Description**: Add a clickable link with title and thumbnail
- **Config Fields**:
  - `url` (url, required): Destination URL
  - `description` (textarea, optional): Description text that scrolls if it overflows
  - `thumbnail_image` (url, optional): Thumbnail image URL
  - `icon` (select, optional): Font Awesome icon selector
- **Rendering**: Horizontal card layout with thumbnail/icon on left, title and description on right

### Videos Category

#### YouTube Video (`youtube_video`)
- **Description**: Embed a YouTube video player
- **Config Fields**:
  - `video_url` (url, required): Full YouTube URL or video ID
  - `autoplay` (checkbox, optional): Enable autoplay
  - `thumbnail_image` (url, optional): Custom thumbnail
- **Rendering**: Embedded iframe player

### Content Category

#### Text/HTML Block (`text_html`)
- **Description**: Add custom text or HTML content
- **Config Fields**:
  - `content` (textarea, required, rows: 6): HTML content (sanitized)
- **Rendering**: Rendered HTML with safe tags only

#### Heading (`heading_block`)
- **Description**: Create a prominent heading with size controls
- **Config Fields**:
  - `text` (text, required, default: "New heading"): Heading text
  - `level` (select, default: "h2"): Heading level (h1, h2, h3)
- **Rendering**: Semantic heading element

#### Italic Text (`text_note`)
- **Description**: Add a small italic note for emphasis
- **Config Fields**:
  - `text` (text, required, default: "Start writing your story…"): Note text
- **Rendering**: Italic paragraph

#### Divider (`divider_rule`)
- **Description**: Insert a horizontal rule to separate sections
- **Config Fields**:
  - `style` (select, default: "flat"): Style (flat, shadow, gradient)
- **Rendering**: Horizontal rule with style class

#### Image (`image`)
- **Description**: Display an image with optional link
- **Config Fields**:
  - `image_url` (url, required): Image URL
  - `alt_text` (text, optional): Alt text for accessibility
  - `link_url` (url, optional): Optional link URL
- **Rendering**: Image with optional link wrapper

### Podcast Category

#### PodNBio Player (`podcast_player_custom`)
- **Description**: Compact podcast player with bottom sheet drawer, chapters, and episode navigation
- **Config Fields**:
  - `rss_feed_url` (url, required): RSS feed URL (auto-populates title, description, cover)
  - `thumbnail_image` (url, optional): Cover image (auto-filled from RSS)
- **Auto-populate**: Automatically fetches podcast data from RSS feed
- **Rendering**: Custom HTML5 audio player with drawer interface
- **Note**: Disabled if page has RSS feed URL set (top drawer handles it)

### Forms Category

#### Email Subscription (`email_subscription`)
- **Description**: Collect email subscriptions from visitors
- **Config Fields**: None (uses page-level email service settings)
- **Rendering**: Button that opens email subscription drawer
- **Requires**: Email service provider configured on page

### E-commerce Category

#### Shopify Product (`shopify_product`)
- **Description**: Display a single product from your Shopify store
- **Config Fields**:
  - `product_handle` (text, required): Product handle/slug
  - `show_description` (checkbox, default: true): Show product description
  - `button_text` (text, default: "Buy Now"): Purchase button text
- **Requires**: Shopify API configuration (`SHOPIFY_SHOP_DOMAIN`, `SHOPIFY_STOREFRONT_TOKEN`)
- **Rendering**: Product card with image, title, price, description, and buy button

#### Shopify Product List (`shopify_product_list`)
- **Description**: Display a list of products from your Shopify store
- **Config Fields**:
  - `product_count` (text, default: "10"): Number of products (1-50)
  - `search_query` (text, optional): Filter by search term
  - `layout` (select, default: "list"): Layout (list, grid)
  - `show_prices` (checkbox, default: true): Show product prices
- **Requires**: Shopify API configuration
- **Rendering**: Grid or list of product cards

#### Shopify Collection (`shopify_collection`)
- **Description**: Display products from a Shopify collection
- **Config Fields**:
  - `collection_handle` (text, required): Collection handle/slug
  - `product_count` (text, default: "10"): Number of products (1-50)
  - `layout` (select, default: "list"): Layout (list, grid)
  - `show_collection_title` (checkbox, default: true): Show collection title
  - `show_prices` (checkbox, default: true): Show product prices
- **Requires**: Shopify API configuration
- **Rendering**: Collection title with grid or list of products

### Content Category (Giphy)

#### Giphy Search (`giphy_search`)
- **Description**: Display GIFs from Giphy search
- **Config Fields**:
  - `search_query` (text, required): Search term
  - `gif_count` (text, default: "12"): Number of GIFs (1-50)
  - `layout` (select, default: "grid"): Layout (grid, list)
  - `columns` (text, default: "3"): Grid columns (1-6)
  - `rating` (select, default: "g"): Content rating (g, pg, pg-13, r)
- **Requires**: Giphy API key (`GIPHY_API_KEY`)
- **Rendering**: Grid or list of GIFs with hover-to-animate

#### Giphy Trending (`giphy_trending`)
- **Description**: Display trending GIFs from Giphy
- **Config Fields**:
  - `gif_count` (text, default: "12"): Number of GIFs (1-50)
  - `layout` (select, default: "grid"): Layout (grid, list)
  - `columns` (text, default: "3"): Grid columns (1-6)
  - `rating` (select, default: "g"): Content rating
- **Requires**: Giphy API key
- **Rendering**: Grid or list of trending GIFs

#### Giphy Random (`giphy_random`)
- **Description**: Display a random GIF from Giphy
- **Config Fields**:
  - `tag` (text, optional): Filter by tag
  - `rating` (select, default: "g"): Content rating
  - `show_title` (checkbox, default: false): Show GIF title
- **Requires**: Giphy API key
- **Rendering**: Single random GIF

---

## Widget Management

### Creating Widgets

Widgets are created through the admin UI or API:

1. **Via Admin UI**:
   - Click "Add Block" button in Left Rail
   - Select widget type from gallery drawer
   - Widget is created with default configuration

2. **Via API**:
   ```php
   POST /api/widgets.php
   {
     "action": "add",
     "widget_type": "custom_link",
     "title": "My Link",
     "config_data": "{\"url\":\"https://example.com\"}"
   }
   ```

### Updating Widgets

Widgets can be updated through:
- **Widget Inspector Panel**: Edit title, configuration fields, active status
- **API**: `POST /api/widgets.php` with `action: "update"`

### Deleting Widgets

- **Via Admin UI**: Delete button in Layers panel or Widget Inspector
- **Via API**: `POST /api/widgets.php` with `action: "delete"`

### Reordering Widgets

- **Via Admin UI**: Drag and drop in Layers panel
- **Via API**: `POST /api/widgets.php` with `action: "reorder"` and `widget_orders` array

### Widget States

- **Active** (`is_active`): Widget is visible on public page (default: 1)
- **Locked** (`is_locked`): Widget cannot be edited/deleted (stored in admin state, not database)
- **Visible** (`is_active === 1`): Widget is displayed on page
- **Featured** (`is_featured`): Widget has special highlighting (only one per page)

---

## Widget Configuration

### Configuration Storage

Widget configuration is stored in the `config_data` column as JSON:

```json
{
  "url": "https://example.com",
  "thumbnail_image": "https://example.com/image.jpg",
  "description": "My description"
}
```

### Configuration Fields

Each widget type defines its configuration fields in `WidgetRegistry`. Fields are rendered in the **Widget Inspector** panel based on their type:

- **Text/URL fields**: Standard input
- **Textarea fields**: Multi-line textarea
- **Select fields**: Dropdown with options
- **Checkbox fields**: Boolean checkbox

### Configuration Validation

- **Required fields**: Must be filled before saving
- **URL fields**: Validated for proper URL format
- **Type coercion**: Values are sanitized based on field type

### Thumbnail Management

Widgets can have thumbnails uploaded or linked:
- **Upload**: Via thumbnail upload button in Widget Inspector
- **URL**: Direct URL input in configuration
- **Auto-populate**: Some widgets (podcast) auto-populate from external sources

---

## Featured Widgets

### Overview

Featured widgets are special widgets that stand out on the page with visual effects. Only **one widget per page** can be featured at a time.

### Featured Effects

Available effects (defined in `FeaturedBlockInspector.tsx`):

1. **Jiggle** 🎯: Random jiggle animation
2. **Burn** 🔥: Fire/burn effect
3. **Rotating Glow** 💫: Rotating glow animation
4. **Blink** 👁️: Blinking animation
5. **Pulse** 💓: Pulsing animation
6. **Shake** 📳: Shake animation
7. **Sparkles** ✨: Sparkle particles

### Setting Featured Widget

1. **Via Layers Panel**:
   - Click star icon on widget in Layers list
   - Widget is marked as featured with default "jiggle" effect

2. **Via Featured Block Inspector**:
   - Select a widget
   - Featured Block Inspector appears (if widget is featured)
   - Choose effect from dropdown
   - Save changes

3. **Via API**:
   ```php
   POST /api/widgets.php
   {
     "action": "update",
     "widget_id": "123",
     "is_featured": "1",
     "featured_effect": "jiggle"
   }
   ```

### Featured Widget Behavior

- **Auto-unfeature**: When a widget is marked as featured, all other widgets are automatically unfeatured
- **Default Effect**: If no effect is specified, defaults to "jiggle"
- **Rendering**: Featured widgets are wrapped in `<div class="featured-widget featured-effect-{effect}">` on public page

### Database Fields

- `is_featured` (TINYINT): 1 if featured, 0 otherwise
- `featured_effect` (VARCHAR): Effect name (jiggle, burn, etc.)

---

## Widget Styling

### Theme-Based Styling

Widgets inherit styling from the active theme. Styling is controlled through the **Edit Theme Panel** in the admin.

### Block Widget Style Settings

All block widgets share the same styling settings from the theme:

#### Background
- **Source**: `theme.widget_background` column
- **Fallback**: `color_tokens.background.surface`
- **Types**: Solid color, gradient, or image
- **Applied via**: `.widget-item { background: ... }` with `!important`

#### Border
- **Color**: `theme.widget_border_color` column
- **Width**: `shape_tokens.border_width` (hairline: 1px, regular: 2px)
- **Applied via**: `.widget-item { border: ... }` with `!important`

#### Border Radius
- **Source**: `shape_tokens.corner` (md, lg, sm, pill, none)
- **Values**:
  - `none`: 0px
  - `small`: 0.375rem (6px)
  - `medium`: 0.75rem (12px)
  - `large`: 1.5rem (24px)
  - `pill`: 9999px
- **Applied via**: `.widget-item { border-radius: ... }` with `!important`

#### Shadow
- **Source**: `shape_tokens.shadow` (level_1: subtle, level_2: pronounced)
- **Values**:
  - `none`: No shadow
  - `subtle`: `0 2px 6px rgba(15, 23, 42, 0.12)`
  - `pronounced`: `0 8px 24px rgba(15, 23, 42, 0.25)`
- **Applied via**: `.widget-item { box-shadow: ... }` with `!important`

#### Typography
- **Heading Font**: `typography_tokens.color.heading` (can be gradient)
- **Body Font**: `typography_tokens.color.body` (can be gradient)
- **Font Family**: `core.typography.font.heading` and `core.typography.font.body`
- **Applied via**: CSS variables and direct styles

#### Spacing
- **Widget Gap**: `spacing_tokens` density affects `--widget-gap` CSS variable
- **Page Padding**: `spacing_tokens` density affects `--page-padding` CSS variable
- **Density Options**: compact, cozy, comfortable

### Styling Flow

1. **Edit Theme Panel** → Saves to database:
   - `widget_background` column
   - `widget_border_color` column
   - `shape_tokens` JSON (corner, border_width, shadow)
   - `typography_tokens` JSON
   - `spacing_tokens` JSON

2. **ThemeCSSGenerator** → Reads from database:
   - Resolves values from theme columns and tokens
   - Generates CSS variables and direct styles
   - Applies with `!important` to ensure precedence

3. **Public Page** → Applies styles:
   - `.widget-item` class receives all styling
   - Individual widgets inherit from theme

### CSS Variables

Generated CSS variables (from `ThemeCSSGenerator.php`):
- `--widget-background`: Widget background color/gradient
- `--widget-border-color`: Widget border color
- `--widget-border-width`: Widget border width
- `--widget-gap`: Spacing between widgets
- `--page-padding`: Padding around page content

### Direct Styles

Some styles are applied directly (not via CSS variables) to ensure they take precedence:
- Background (with `!important`)
- Border (with `!important`)
- Border-radius (with `!important`)
- Box-shadow (with `!important`)

---

## Widget Rendering

### Renderer Class

The `WidgetRenderer` class (`classes/WidgetRenderer.php`) handles converting widget data into HTML for public pages.

### Render Method

```php
WidgetRenderer::render($widget, $page = null)
```

**Parameters**:
- `$widget`: Widget data array from database
- `$page`: Optional page data (for getting user_id)

**Returns**: HTML string

### Rendering Process

1. **Get Widget Type**: Extract `widget_type` from widget data
2. **Get Widget Definition**: Look up in `WidgetRegistry`
3. **Parse Config Data**: Decode `config_data` JSON
4. **Route to Renderer**: Call specific render method based on type
5. **Return HTML**: Return rendered HTML string

### Widget HTML Structure

Most widgets follow this structure:

```html
<div class="widget-item widget-{type}">
  <div class="widget-content">
    <!-- Widget-specific content -->
  </div>
</div>
```

### Custom Link Rendering

Custom links use a horizontal card layout:

```html
<a href="/click.php?link_id={id}&page_id={page_id}" class="widget-item" target="_blank">
  <div class="widget-thumbnail-wrapper">
    <img src="..." class="widget-thumbnail" />
  </div>
  <div class="widget-content">
    <div class="widget-title">Title</div>
    <div class="widget-description">Description</div>
  </div>
</a>
```

### Click Tracking

All widget links go through `/click.php` for analytics:
- Tracks click events
- Redirects to destination URL
- Records analytics data

---

## Admin UI Components

### Left Rail (Layers Panel)

**Location**: `admin-ui/src/components/layout/LeftRail.tsx`

**Features**:
- Lists all widgets in display order
- Drag-and-drop reordering
- Quick actions (lock, visibility, edit, delete, featured)
- "Add Block" button opens widget gallery
- Quick-add buttons for common widgets (heading, text, divider)

**Widget Actions**:
- **Lock/Unlock**: Prevents editing (UI state only)
- **Visibility Toggle**: Shows/hides widget (sets `is_active`)
- **Edit**: Opens Widget Inspector
- **Delete**: Removes widget
- **Featured Toggle**: Marks widget as featured (star icon)

### Widget Inspector

**Location**: `admin-ui/src/components/panels/WidgetInspector.tsx`

**Features**:
- Displays widget name and description
- Title input field
- Configuration fields (dynamically generated from `config_fields`)
- Thumbnail upload/remove
- Active status toggle
- Save button

**Field Rendering**:
- Text/URL: `<input type="text|url">`
- Textarea: `<textarea>`
- Select: `<select>` with options
- Checkbox: `<input type="checkbox">`

### Featured Block Inspector

**Location**: `admin-ui/src/components/panels/FeaturedBlockInspector.tsx`

**Features**:
- Only shown when widget is featured
- Effect selector dropdown
- Save/Reset buttons
- Help text explaining effects

**Integration**:
- Appears above Widget Inspector when widget is featured
- Both inspectors shown simultaneously for featured widgets

### Widget Gallery Drawer

**Location**: `admin-ui/src/components/overlays/WidgetGalleryDrawer.tsx`

**Features**:
- Modal drawer showing all available widgets
- Grouped by category
- Search/filter functionality
- Widget thumbnails and descriptions
- Click to add widget

### Properties Panel

**Location**: `admin-ui/src/components/layout/PropertiesPanel.tsx`

**Features**:
- Container for all inspector panels
- Routes to correct inspector based on selection:
  - Integration Inspector (if integration selected)
  - Social Icon Inspector (if social icon selected)
  - Featured Block Inspector + Widget Inspector (if featured widget selected)
  - Widget Inspector (if regular widget selected)
  - Profile Inspector (if page element selected)

---

## API Endpoints

### Widgets API

**Endpoint**: `/api/widgets.php`

**Authentication**: Required (session-based)

**Methods**: POST only

### Actions

#### Add Widget

```php
POST /api/widgets.php
{
  "action": "add",
  "widget_type": "custom_link",
  "title": "My Link",
  "config_data": "{\"url\":\"https://example.com\"}",
  "csrf_token": "..."
}
```

**Response**:
```json
{
  "success": true,
  "widget_id": 123
}
```

#### Update Widget

```php
POST /api/widgets.php
{
  "action": "update",
  "widget_id": "123",
  "title": "Updated Title",
  "config_data": "{\"url\":\"https://newurl.com\"}",
  "is_active": "1",
  "is_featured": "1",
  "featured_effect": "jiggle",
  "csrf_token": "..."
}
```

**Response**:
```json
{
  "success": true
}
```

#### Delete Widget

```php
POST /api/widgets.php
{
  "action": "delete",
  "widget_id": "123",
  "csrf_token": "..."
}
```

**Response**:
```json
{
  "success": true
}
```

#### Reorder Widgets

```php
POST /api/widgets.php
{
  "action": "reorder",
  "widget_orders": "[{\"widget_id\":123,\"display_order\":1},{\"widget_id\":124,\"display_order\":2}]",
  "csrf_token": "..."
}
```

**Response**:
```json
{
  "success": true
}
```

#### Get Widgets

```php
POST /api/widgets.php
{
  "action": "get"
}
```

**Response**:
```json
{
  "success": true,
  "widgets": [...]
}
```

#### Get Available Widgets

```php
POST /api/widgets.php
{
  "action": "get_available",
  "include_coming_soon": "0"
}
```

**Response**:
```json
{
  "success": true,
  "available_widgets": [...],
  "widgets": [...]
}
```

### React Query Hooks

**Location**: `admin-ui/src/api/widgets.ts`

**Hooks**:
- `useWidgetsQuery()`: Fetch all widgets
- `useAvailableWidgetsQuery()`: Fetch available widget types
- `useAddWidgetMutation()`: Add widget mutation
- `useUpdateWidgetMutation()`: Update widget mutation
- `useDeleteWidgetMutation()`: Delete widget mutation
- `useReorderWidgetMutation()`: Reorder widgets mutation

**Auto-invalidation**: All mutations invalidate `widgets` and `pageSnapshot` queries

---

## Database Schema

### Widgets Table

```sql
CREATE TABLE widgets (
  id INT PRIMARY KEY AUTO_INCREMENT,
  page_id INT NOT NULL,
  widget_type VARCHAR(50) NOT NULL,
  title VARCHAR(255) NOT NULL,
  config_data TEXT, -- JSON
  display_order INT DEFAULT 0,
  is_active TINYINT DEFAULT 1,
  is_featured TINYINT DEFAULT 0,
  featured_effect VARCHAR(50),
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (page_id) REFERENCES pages(id) ON DELETE CASCADE
);
```

### Key Columns

- `id`: Primary key
- `page_id`: Foreign key to pages table
- `widget_type`: Widget type identifier (from WidgetRegistry)
- `title`: Widget display title
- `config_data`: JSON string with widget-specific configuration
- `display_order`: Order for sorting (lower = first)
- `is_active`: 1 = visible, 0 = hidden
- `is_featured`: 1 = featured, 0 = not featured
- `featured_effect`: Effect name if featured (jiggle, burn, etc.)

### Indexes

- `page_id`: For fast lookups by page
- `display_order`: For sorting
- `is_active`: For filtering active widgets

---

## Summary

### Widget Lifecycle

1. **Creation**: User selects widget type → Widget created with default config
2. **Configuration**: User edits title and config fields → Saved to database
3. **Styling**: Theme settings applied → CSS generated → Styles applied to `.widget-item`
4. **Rendering**: Public page loads → WidgetRenderer converts data to HTML
5. **Display**: HTML rendered on page with theme styles

### Key Files

- **Registry**: `classes/WidgetRegistry.php`
- **Renderer**: `classes/WidgetRenderer.php`
- **API**: `api/widgets.php`
- **Admin UI**: `admin-ui/src/components/panels/WidgetInspector.tsx`
- **Layers Panel**: `admin-ui/src/components/layout/LeftRail.tsx`
- **Featured Inspector**: `admin-ui/src/components/panels/FeaturedBlockInspector.tsx`
- **React Hooks**: `admin-ui/src/api/widgets.ts`

### Best Practices

1. **Always validate widget types** using `WidgetRegistry::widgetExists()`
2. **Sanitize configuration data** before saving
3. **Use theme styling** instead of inline styles
4. **Handle errors gracefully** in widget rendering
5. **Provide fallbacks** for missing configuration
6. **Track analytics** via click.php for link widgets
7. **Only one featured widget** per page at a time

---

*Last Updated: 2024-01-XX*

