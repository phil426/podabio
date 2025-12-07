# PodaBio Development Guide

Complete guide for TypeScript, React, and development best practices in the PodaBio admin UI.

## Table of Contents

1. [Quick Start](#quick-start)
2. [TypeScript Best Practices](#typescript-best-practices)
3. [Error Prevention Strategy](#error-prevention-strategy)
4. [Cursor AI Workflows](#cursor-ai-workflows)
5. [Development Workflow](#development-workflow)
6. [Common Patterns](#common-patterns)
7. [Quick Reference](#quick-reference)

---

## Quick Start

### Daily Development Checklist

**Before Writing Code:**
- [ ] Define TypeScript interfaces first
- [ ] Ask Cursor to generate type-safe boilerplate

**While Writing Code:**
- [ ] Use explicit types, not `any`
- [ ] Use centralized utilities (when available)
- [ ] Run `npm run type-check` after significant changes

**Before Committing:**
- [ ] Run `npm run type-check`
- [ ] Ask Cursor: "Review my changes for TypeScript errors"
- [ ] Fix any issues immediately

### Essential Commands

```bash
# Check for TypeScript errors
npm run type-check

# Watch mode (development)
npm run type-check:watch

# Build (includes type checking)
npm run build
```

---

## TypeScript Best Practices

### Root Causes of TypeScript Errors

Based on fixing 269 TypeScript errors, the main issues were:

1. **Type Information Loss** (40% of errors)
   - Parsed JSON objects becoming `Record<string, unknown>`
   - No type information after `safeParse()`

2. **Code Duplication** (25% of errors)
   - Multiple `safeParse()` implementations
   - Repeated type assertion patterns

3. **Missing Type Definitions** (20% of errors)
   - APIs without explicit return types
   - Parsed structures without interfaces

4. **Over-reliance on `as any`** (10% of errors)
   - Quick fixes instead of proper typing
   - Missing type guards

5. **Configuration Issues** (5% of errors)
   - Missing strict settings
   - No pre-commit checks

### Key Principles

1. **Type Early** - Define interfaces before implementation
2. **Check Often** - Run type-check frequently
3. **Use Cursor** - Leverage AI for type-safe code generation
4. **Fix Immediately** - Don't accumulate errors

### ✅ DO: Use Explicit Types

```typescript
// ✅ Good
interface ColorTokens {
  accent?: { primary?: string };
}
const tokens = safeParse<ColorTokens>(theme.color_tokens);
const primary = tokens?.accent?.primary ?? '#2563eb';
```

### ❌ DON'T: Use Untyped Parsing

```typescript
// ❌ Bad
const colorTokens = JSON.parse(theme.color_tokens);
const primary = colorTokens?.accent?.primary; // Type error!
```

### ✅ DO: Use Type Guards

```typescript
function isColorTokens(obj: unknown): obj is ColorTokens {
  return typeof obj === 'object' && obj !== null;
}

const tokens = safeParse(theme.color_tokens);
if (isColorTokens(tokens)) {
  // TypeScript knows tokens is ColorTokens
  const primary = tokens.accent?.primary;
}
```

### ❌ DON'T: Use `as any` Without Justification

```typescript
// ❌ Bad (unless absolutely necessary)
const primary = (tokens as any)?.accent?.primary;

// ✅ Better
if (isColorTokens(tokens) && tokens.accent?.primary) {
  const primary = tokens.accent.primary; // Type-safe!
}
```

---

## Error Prevention Strategy

### Immediate Actions

#### 1. Use Type-Check Script

Add to `package.json`:
```json
{
  "scripts": {
    "type-check": "tsc --noEmit",
    "type-check:watch": "tsc --noEmit --watch",
    "build": "npm run type-check && vite build"
  }
}
```

#### 2. Enhanced TypeScript Configuration

Add these settings incrementally to `tsconfig.json`:

```json
{
  "compilerOptions": {
    "strict": true, // ✅ Already enabled
    
    // Add these incrementally (one per week):
    "noUncheckedIndexedAccess": true,        // Arrays/objects require checks
    "noImplicitReturns": true,               // Functions must return explicitly
    "noFallthroughCasesInSwitch": true,      // Switch cases need breaks
    "noUnusedLocals": true,                  // Catch unused variables
    "noUnusedParameters": true               // Catch unused params
  }
}
```

**Implementation:** Enable one setting per week, fix errors as they appear.

#### 3. Centralize JSON Parsing

Create `admin-ui/src/utils/json.ts` with typed `safeParse`:

```typescript
/**
 * Safely parse JSON string to typed object
 */
export function safeParse<T = Record<string, unknown>>(
  input: string | null | undefined | Record<string, unknown>
): T | null {
  if (!input) return null;
  if (typeof input === 'object' && !Array.isArray(input)) {
    return input as T;
  }
  if (typeof input !== 'string') return null;
  try {
    return JSON.parse(input) as T;
  } catch {
    return null;
  }
}
```

### Long-term Strategy

1. **Centralize utilities** - Create `utils/json.ts` with typed `safeParse`
2. **Define interfaces** - Add interfaces for all parsed JSON structures
3. **Migrate incrementally** - One file at a time
4. **Enable stricter settings** - One setting per week

---

## Cursor AI Workflows

### Leveraging Cursor's AI-Powered Development

Cursor offers powerful AI-assisted development features that can significantly improve TypeScript code quality.

### 1. Cursor Rules (.cursorrules)

The `.cursorrules` file in the project root guides Cursor AI to:
- Always use explicit types
- Prefer centralized utilities
- Use type guards instead of `as any`
- Follow project patterns

Cursor automatically follows these rules when generating code, refactoring, or suggesting fixes.

### 2. Type-Safe Code Generation

**Prompt Cursor:**
```
@Cursor: Create a React component called ThemePreviewCard that takes a ThemeRecord prop and displays theme preview. Use proper TypeScript types from @/api/types
```

Cursor will:
- Generate properly typed component
- Import types correctly
- Use existing patterns from your codebase

### 3. Safe Refactoring

**Prompt Cursor:**
```
@Cursor: Refactor this function to use the centralized safeParse utility from @/utils/json instead of the local implementation. Maintain all type safety.
```

### 4. Batch Type Fixes

**Prompt Cursor:**
```
@Cursor: Find all instances where we use 'as any' type assertions and suggest type-safe alternatives using proper interfaces and type guards.
```

### 5. Pre-Commit Reviews

**Before committing, ask Cursor:**
```
@Cursor: Review my changes for TypeScript errors and type safety issues. Suggest improvements.
```

### 6. Type Definition Generation

**Prompt Cursor:**
```
@Cursor: Generate TypeScript interfaces for the parsed JSON structure returned by this API endpoint. Include JSDoc comments with examples.
```

### Best Practices with Cursor

1. **Always specify types in prompts** - "Create a function that accepts a ThemeRecord and returns a Promise<string>"
2. **Reference existing patterns** - "Use the same typing pattern as ThemeEditorPanel.tsx"
3. **Ask for type guards** - "Add runtime type validation for this API response"
4. **Request refactoring suggestions** - "How can I make this code more type-safe?"

---

## Development Workflow

### Daily Development

1. **Start with Type Definitions**
   - Define interfaces before implementation
   - Use Cursor to generate boilerplate from interfaces

2. **Use Type-Safe Patterns**
   - Always use centralized utilities
   - Prefer type guards over assertions
   - Use helper functions for common patterns

3. **Leverage Cursor for Code Generation**
   - Generate typed components
   - Create type-safe API functions
   - Generate interfaces from examples

4. **Regular Type Checks**
   - Run `npm run type-check` before committing
   - Use Cursor's inline diagnostics
   - Fix errors immediately, not later

### Before Committing

1. **Run Type Check**
   ```bash
   npm run type-check
   ```

2. **Ask Cursor to Review**
   ```
   @Cursor: Review my changes for TypeScript errors
   ```

3. **Fix Issues Immediately**
   - Don't accumulate errors
   - Fix as you go

### Code Review Process

1. **Automated Checks**
   - Type checking in CI/CD
   - ESLint with TypeScript rules
   - Pre-commit hooks (optional)

2. **Cursor-Assisted Reviews**
   ```
   @Cursor: Review this PR for type safety and suggest improvements
   ```

---

## Common Patterns

### Parsing JSON

```typescript
// Use typed parsing with explicit interface
import { safeParse } from '@/utils/json'; // When centralized
// OR define local with type
const tokens = safeParse<ColorTokens>(theme.color_tokens);
```

### Accessing Nested Properties

```typescript
// Use optional chaining and nullish coalescing
const primary = tokens?.accent?.primary ?? '#2563eb';
```

### Type Guards

```typescript
function isColorTokens(obj: unknown): obj is ColorTokens {
  return typeof obj === 'object' && obj !== null;
}

const tokens = safeParse(theme.color_tokens);
if (isColorTokens(tokens)) {
  // TypeScript knows tokens is ColorTokens
}
```

### React Component Props

```typescript
interface MyComponentProps {
  theme: ThemeRecord;
  onSave?: () => void;
}

export function MyComponent({ theme, onSave }: MyComponentProps) {
  // Component implementation
}
```

### API Responses

```typescript
interface ApiResponse<T> {
  success: boolean;
  data?: T;
  error?: string;
}

async function fetchTheme(id: number): Promise<ApiResponse<ThemeRecord>> {
  // API implementation
}
```

---

## Quick Reference

### Cursor Prompts

**Generate Type-Safe Code:**
```
@Cursor: Create a function that [description]. Use explicit TypeScript types from @/api/types
```

**Review Code:**
```
@Cursor: Review this code for TypeScript errors and type safety issues
```

**Refactor Safely:**
```
@Cursor: Refactor this to use proper types instead of 'as any'
```

### Key Rules

1. **Never use `as any`** without justification
2. **Always define interfaces** for object shapes
3. **Use type guards** for runtime validation
4. **Check types early** - run type-check frequently
5. **Let Cursor help** - use AI for type-safe code generation

### Configuration Files

- **`.cursorrules`** - Project rules for Cursor AI (root directory)
- **`tsconfig.json`** - TypeScript configuration
- **`package.json`** - Includes `type-check` scripts

---

## Success Metrics

Track these to measure improvement:

1. **TypeScript Error Count**
   - Target: < 10 errors at any time
   - Measure: Weekly error counts

2. **Type Safety Score**
   - Target: > 95% of code properly typed
   - Measure: % of `any` usage

3. **Build Time Impact**
   - Target: < 10% increase in build time
   - Measure: Before/after strict settings

4. **Developer Velocity**
   - Target: Faster development with fewer bugs
   - Measure: Time to fix type errors

---

## Resources

- **TypeScript Handbook**: https://www.typescriptlang.org/docs/handbook/intro.html
- **Cursor Documentation**: https://cursor.sh/docs
- **Project Rules**: `.cursorrules` file in project root
- **Type Definitions**: `admin-ui/src/types/` directory
- **API Types**: `admin-ui/src/api/types.ts`

---

**Last Updated:** 2025-01-XX  
**Status:** Active Development

