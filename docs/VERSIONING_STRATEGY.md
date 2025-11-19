# Versioning Strategy

**Last Updated**: 2025-01-XX  
**Current Application Version**: 1.0.1

## Overview

This document defines the versioning strategy for PodaBio to ensure consistency across all version references.

## Version Number Format

PodaBio uses **Semantic Versioning (SemVer)**: `MAJOR.MINOR.PATCH`

- **MAJOR**: Breaking changes or major feature releases
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes and minor improvements (backward compatible)

## Version Storage Locations

### 1. Root `VERSION` File
- **Purpose**: Single source of truth for application version
- **Location**: `/VERSION`
- **Format**: Plain text, one line (e.g., `1.0.1`)
- **Used by**: Deployment scripts, documentation, manual version checks

### 2. PHP Constants (`config/constants.php`)
- **Purpose**: Version available in PHP runtime
- **Location**: `config/constants.php`
- **Constant**: `APP_VERSION`
- **Format**: PHP constant (e.g., `'1.0.1'`)
- **Must match**: `VERSION` file

### 3. React Admin UI (`admin-ui/package.json`)
- **Purpose**: NPM package version for admin UI
- **Location**: `admin-ui/package.json`
- **Format**: JSON field (e.g., `"version": "1.0.1"`)
- **Should match**: Main application version (for consistency)
- **Note**: Can be independent for major releases, but should stay aligned for minor/patch releases

## Version Reconciliation Rules

### ✅ Primary Source of Truth
The **`VERSION` file** is the single source of truth for the application version.

### ✅ Required Synchronization
1. `VERSION` file ↔ `config/constants.php` (APP_VERSION) - **MUST MATCH**
2. `VERSION` file ↔ `admin-ui/package.json` (version) - **SHOULD MATCH** (for consistency)

### ⚠️ Documentation References
- All documentation should reference the current version from `VERSION` file
- When updating version, update documentation references as well

## Version Update Process

### Step 1: Update Version Number

1. **Update `VERSION` file** (primary source of truth):
   ```bash
   echo "1.0.2" > VERSION
   ```

2. **Update `config/constants.php`**:
   ```php
   define('APP_VERSION', '1.0.2');
   ```

3. **Update `admin-ui/package.json`**:
   ```json
   {
     "version": "1.0.2"
   }
   ```

### Step 2: Update Documentation

4. **Update version references in documentation**:
   - `docs/SECONDARY_DEPLOYMENT_REFERENCE.md` (if version tag mentioned)
   - Any README files mentioning version
   - Release notes or changelog

### Step 3: Commit Changes

5. **Commit all version updates together**:
   ```bash
   git add VERSION config/constants.php admin-ui/package.json docs/
   git commit -m "Bump version to 1.0.2"
   ```

### Step 4: Create Git Tag (Optional)

6. **Tag the release** (if creating a release):
   ```bash
   git tag -a v1.0.2 -m "Release version 1.0.2"
   git push origin v1.0.2
   ```

## Current Version Status

**Application Version**: `1.0.1`

### Version Locations

| Location | Current Value | Status |
|----------|---------------|--------|
| `/VERSION` | `1.0.1` | ✅ Current |
| `config/constants.php` (APP_VERSION) | `1.0.1` | ✅ Matches |
| `admin-ui/package.json` (version) | `0.1.0` | ⚠️ **MISMATCH** |
| Documentation references | Various | ⚠️ **INCONSISTENT** |

### Actions Required

- [ ] Update `admin-ui/package.json` version to `1.0.1`
- [ ] Update `docs/SECONDARY_DEPLOYMENT_REFERENCE.md` (fix `v1.4.0` reference)
- [ ] Verify all documentation references current version

## Version Checking Script

You can verify version consistency using:

```bash
# Check version in all locations
echo "VERSION file: $(cat VERSION)"
echo "APP_VERSION: $(grep "APP_VERSION" config/constants.php | cut -d"'" -f2)"
echo "package.json: $(grep '"version"' admin-ui/package.json | cut -d'"' -f4)"
```

All three should match!

## Best Practices

1. **Always update all locations together** when bumping version
2. **Commit version changes in a single commit** for clarity
3. **Use git tags** for release versions (optional but recommended)
4. **Update documentation** when version changes
5. **Test version consistency** before deploying

## Version History

| Version | Date | Changes |
|---------|------|---------|
| 1.0.1 | 2025-01-XX | Current version |
| 1.0.0 | Initial | Initial release |

---

**Note**: This document should be updated whenever the versioning strategy changes or when version is updated.

