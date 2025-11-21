# Podabio Studio UI Map

## Overview
This document maps the entire Studio UI structure: tabs, panels, inspectors, routes, and component relationships. It also identifies dead code and unused components.

## Top-Level Tabs

| User Label | Internal `TabValue` | Icon | Description |
|------------|---------------------|------|-------------|
| Style | `structure` | LuLayers | Main editing tab with Layout/Look subtabs |
| Analytics | `analytics` | LuTrendingUp | Analytics dashboard (full-width center panel) |
| Integrations | `integrations` | LuPlug | Integration management |
| Settings | `settings` | LuSettings | Social icons and settings |

**Note:** The `design` tab value exists but is only used as a nested tab under Style. It's not shown in the top-level TabBar.

## Inner Tab Lists

### Style Tab (`structure`)
When `activeTab === 'structure'` or `activeTab === 'design'`, the LeftRail shows an inner tablist:
- **Layout** (`structure`) - Shows DraggableLayerList with widgets, profile, footer, podcast player
- **Look** (`design`) - Shows ThemeLibraryPanel

## Panel Structure

### Left Rail (`LeftRail.tsx`)
- **Location:** Left panel (22% width by default, collapsed for Analytics)
- **Content by Tab:**
  - `structure` (Layout): DraggableLayerList, quick add buttons
  - `design` (Look): ThemeLibraryPanel
  - `analytics`: Empty placeholder
  - `integrations`: IntegrationsPanel
  - `settings`: SettingsPanel

### Center Panel (`CanvasViewport.tsx` / `AnalyticsDashboard.tsx`)
- **Location:** Center panel (46% width by default, 100% for Analytics)
- **Content by Tab:**
  - `structure` / `design`: CanvasViewport (live preview iframe)
  - `analytics`: AnalyticsDashboard (full-width)
  - `integrations` / `settings`: CanvasViewport (preview)

### Right Panel (`PropertiesPanel.tsx`)
- **Location:** Right panel (32% width by default, collapsed for Analytics)
- **Content:** Inspector components based on `activeTab` + selection state

## Inspector Components

### Current Inspectors

| Inspector | Used In Tab(s) | Selection Store | Notes |
|-----------|----------------|-----------------|-------|
| `ProfileInspector` | Style (`structure`) | `widgetSelection` (`page:profile`, `page:footer`) | Default when Style tab active and nothing selected |
| `WidgetInspector` | Style (`structure`) | `widgetSelection` (widget IDs) | For regular widgets |
| `FeaturedBlockInspector` | Style (`structure`) | `widgetSelection` (featured widgets) | Shown alongside WidgetInspector for featured widgets |
| `PodcastPlayerInspector` | Style (`structure`) | `widgetSelection` (`page:podcast-player`) | For podcast player settings |
| `SocialIconInspector` | Settings | `socialIconSelection` | For editing social icon URLs |
| `IntegrationInspector` | Integrations | `integrationSelection` | For integration configuration |
| `ThemeEditorPanel` | Style (`design`) | `themeInspector` (visibility state) | Shown when Look tab is active |

### Unused Inspectors

| Inspector | Status | Notes |
|-----------|--------|-------|
| `BlogPostInspector` | **DEAD CODE** | Not referenced in PropertiesPanel. Blog widgets are filtered out in LeftRail. |

## Selection Stores

| Store | Location | Used By | Status |
|-------|----------|---------|--------|
| `widgetSelection` | `state/widgetSelection.ts` | PropertiesPanel, LeftRail | **ACTIVE** |
| `socialIconSelection` | `state/socialIconSelection.ts` | PropertiesPanel, SettingsPanel | **ACTIVE** |
| `integrationSelection` | `state/integrationSelection.ts` | PropertiesPanel, IntegrationsPanel | **ACTIVE** |
| `themeInspector` | `state/themeInspector.ts` | PropertiesPanel, LeftRail | **ACTIVE** |
| `blogPostSelection` | `state/blogPostSelection.ts` | BlogPostInspector, BlogPostList | **UNUSED** (blog feature retired) |

## Routes

| Route Pattern | Component | Description |
|--------------|-----------|-------------|
| `/account/*` | `AccountWorkspace` + `AccountSummaryPanel` | Account management (profile, security, billing) |
| `/*` (default) | `EditorShell` | Main Studio editor |

## Inspector-to-Tab Mapping (Intended)

### Style Tab (`structure` / `design`)
- **Default:** `ProfileInspector` (focus="profile")
- **When widget selected:** `WidgetInspector` (or `FeaturedBlockInspector` + `WidgetInspector` if featured)
- **When profile selected:** `ProfileInspector` (focus="profile")
- **When footer selected:** `ProfileInspector` (focus="footer")
- **When podcast player selected:** `PodcastPlayerInspector`
- **When Look tab active:** `ThemeEditorPanel` (via `themeInspector` state)

### Analytics Tab (`analytics`)
- **Default:** No inspector (right panel collapsed)

### Integrations Tab (`integrations`)
- **Default:** No inspector (or placeholder)
- **When integration selected:** `IntegrationInspector`

### Settings Tab (`settings`)
- **Default:** No inspector
- **When social icon selected:** `SocialIconInspector`

## Dead Code & Unused Components

### Components to Remove
1. **`BlogPostInspector.tsx`** - Not used, blog feature retired
2. **`BlogPostList.tsx`** - Not used, blog feature retired
3. **`blogPostSelection.ts`** - Not used, blog feature retired
4. **Blog API hooks** (`api/blog.ts`) - May still be used elsewhere, needs audit

### Components to Deprecate
- None identified at this time

### Confusing Naming
- `structure` vs "Style" - Internal value doesn't match user label (but this is acceptable for now)
- `design` exists as both a TabValue and nested tab - Currently handled correctly

## Current Issues

1. **Inspector persistence:** Inspectors from one tab can remain visible when switching to another tab
2. **No tab-based gating:** `PropertiesPanel` doesn't explicitly check `activeTab` before showing inspectors
3. **Selection clearing:** Selections aren't cleared when switching tabs, allowing stale inspectors

## Files to Modify

1. `admin-ui/src/components/layout/PropertiesPanel.tsx` - Add tab-based inspector gating
2. `admin-ui/src/components/layout/EditorShell.tsx` - Potentially clear selections on tab change
3. `admin-ui/src/components/layout/LeftRail.tsx` - Already clears widget selection on tab change (line 471-476)
4. Remove dead code: `BlogPostInspector.tsx`, `BlogPostList.tsx`, `blogPostSelection.ts`













