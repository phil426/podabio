# Checkpoint v2.1.1

**Date:** 2025-12-09
**Version:** 2.1.1

## 📝 Changelog

### 🚀 Features & Improvements
- **Podcast Tab Redesign:** Completely refactored the Podcast Inspector to use a modern, card-based layout matching the Integrations tab's glassmorphic aesthetic.
- **Smart Links Generator:**
  - Implemented persistent storage and display of generated podlinks.
  - Added direct RSS feed input handling for immediate link generation.
  - Refactored results display into a clean grid of mini-cards.
- **Enhanced Search:** Redesigned podcast search results into responsive mini-cards with hover effects for better UX.
- **Navigation Updates:**
  - Optimized left rail navigation for mobile/collapsed states (full-bleed avatar).
  - Removed "Light Mode" toggle to strictly enforce the premium dark themed aesthetic.
- **UI/UX Polish:**
  - Fixed toggle switch deformation on narrow screens.
  - Standardized CSS usage (e.g., `line-clamp` compatibility).

### 🐛 Bug Fixes
- Fixed "Error generating links" by ensuring RSS feed URL is passed directly to the generation API.
- Resolved CSS linter warnings.

## 📦 Version Bump
- `VERSION`: 2.1.0 -> 2.1.1
- `config/constants.php`: 2.1.0 -> 2.1.1
- `admin-ui/package.json`: 2.1.0 -> 2.1.1

## ✅ Code Quality
- **Linting:** Addressed persistent CSS linting issues.
- **Consistency:** Aligned `PodcastInspector` styling with `IntegrationsPanel`.

## ⏭️ Next Steps
- Deploy changes to production.
- Monitor Smart Links generation telemetry.
