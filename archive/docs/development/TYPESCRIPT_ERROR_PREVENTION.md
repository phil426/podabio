# TypeScript Error Prevention Strategies

## How We Got Here

The TypeScript errors in this codebase primarily stem from:

1. **Parsed JSON objects losing type information**: The `safeParse()` function returns `Record<string, unknown> | null`, which loses all type information about the parsed JSON structure.

2. **Multiple implementations of `safeParse`**: The function is duplicated across multiple files instead of using a centralized utility, leading to inconsistent behavior.

3. **Nested property access on untyped objects**: When accessing nested properties like `colorTokens?.accent?.primary`, TypeScript can't verify the property exists because the type is `Record<string, unknown>`.

4. **Missing type definitions**: Some API responses and parsed objects don't have explicit TypeScript interfaces, forcing developers to use type assertions (`as any`).

5. **Dynamic JSON structures**: Theme tokens, color tokens, and other design system data are stored as JSON strings in the database, which are parsed at runtime. TypeScript can't infer the structure.

## Prevention Strategies

### 1. Centralize `safeParse` with Type Guards

**Current Problem:**
```typescript
// Duplicated in multiple files
function safeParse(input: string | null | undefined): Record<string, unknown> | null {
  if (!input) return null;
  try {
    return JSON.parse(input);
  } catch {
    return null;
  }
}

// Later usage loses all type information
const colorTokens = safeParse(theme.color_tokens);
const primary = colorTokens?.accent?.primary; // ❌ Type error: Property 'accent' does not exist
```

**Solution: Create a centralized utility with type guards**

Create `admin-ui/src/utils/json.ts`:

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

/**
 * Type guard to check if object has a property
 */
export function hasProperty<T extends string>(
  obj: unknown,
  prop: T
): obj is Record<T, unknown> {
  return typeof obj === 'object' && obj !== null && prop in obj;
}

/**
 * Type guard for nested property access
 */
export function hasNestedProperty(
  obj: unknown,
  path: string[]
): boolean {
  let current: unknown = obj;
  for (const key of path) {
    if (!hasProperty(current, key)) {
      return false;
    }
    current = (current as Record<string, unknown>)[key];
  }
  return true;
}

/**
 * Safe nested property accessor
 */
export function getNestedProperty<T>(
  obj: unknown,
  path: string[],
  defaultValue: T
): T {
  let current: unknown = obj;
  for (const key of path) {
    if (!hasProperty(current, key)) {
      return defaultValue;
    }
    current = (current as Record<string, unknown>)[key];
  }
  return (current as T) ?? defaultValue;
}
```

**Usage:**
```typescript
import { safeParse, getNestedProperty } from '@/utils/json';

// With explicit type
const colorTokens = safeParse<ColorTokens>(theme.color_tokens);
const primary = getNestedProperty(colorTokens, ['accent', 'primary'], '#2563eb');
```

### 2. Define Explicit Interfaces for Parsed Objects

**Problem:** Using `Record<string, unknown>` everywhere loses type safety.

**Solution:** Define interfaces for all parsed JSON structures.

Create `admin-ui/src/types/tokens.ts`:

```typescript
export interface ColorTokens {
  accent?: {
    primary?: string;
    secondary?: string;
    muted?: string;
  };
  background?: {
    base?: string;
    surface?: string;
    surface_raised?: string;
    overlay?: string;
  };
  text?: {
    primary?: string;
    secondary?: string;
    inverse?: string;
  };
  border?: {
    default?: string;
    focus?: string;
  };
  semantic?: {
    accent?: {
      primary?: string;
    };
  };
}

export interface TypographyTokens {
  font?: {
    heading?: string;
    body?: string;
    metatext?: string;
  };
  color?: {
    heading?: string;
    body?: string;
    widget_heading?: string;
    widget_body?: string;
  };
  scale?: Record<string, number>;
  weight?: Record<string, number>;
  lineHeight?: Record<string, number>;
}

// ... more interfaces
```

**Usage:**
```typescript
import { safeParse } from '@/utils/json';
import type { ColorTokens } from '@/types/tokens';

const colorTokens = safeParse<ColorTokens>(theme.color_tokens);
if (colorTokens?.accent?.primary) {
  // TypeScript knows primary is a string!
  console.log(colorTokens.accent.primary.toUpperCase());
}
```

### 3. Use Type Assertions Strategically

**When to use `as any`:**
- ✅ When you've verified the structure at runtime and TypeScript can't infer it
- ✅ When working with third-party APIs that don't have types
- ✅ When migrating legacy code incrementally

**When NOT to use `as any`:**
- ❌ As a quick fix to silence errors
- ❌ When you can define proper types instead
- ❌ Without runtime validation

**Better approach: Use type guards**

```typescript
// ❌ Bad: Blind type assertion
const primary = (colorTokens as any).accent?.primary;

// ✅ Good: Type guard with validation
function isColorTokens(obj: unknown): obj is ColorTokens {
  return (
    typeof obj === 'object' &&
    obj !== null &&
    (hasProperty(obj, 'accent') || hasProperty(obj, 'background'))
  );
}

const colorTokens = safeParse(theme.color_tokens);
if (isColorTokens(colorTokens) && colorTokens.accent?.primary) {
  const primary = colorTokens.accent.primary; // ✅ Type-safe!
}
```

### 4. Use Helper Functions for Common Patterns

**Problem:** Repeated code for accessing nested properties.

**Solution:** Create reusable helper functions.

```typescript
// admin-ui/src/utils/themeHelpers.ts

export function getThemeColor(
  theme: ThemeRecord,
  path: string[],
  fallback: string
): string {
  const colorTokens = safeParse<ColorTokens>(theme.color_tokens);
  if (!colorTokens) return fallback;
  
  return getNestedProperty(colorTokens, path, fallback);
}

// Usage:
const accentPrimary = getThemeColor(theme, ['accent', 'primary'], '#2563eb');
const textPrimary = getThemeColor(theme, ['text', 'primary'], '#000000');
```

### 5. Validate API Responses

**Problem:** API responses might not match expected types.

**Solution:** Use runtime validation (consider using `zod`).

```typescript
import { z } from 'zod';

const ColorTokensSchema = z.object({
  accent: z.object({
    primary: z.string().optional(),
    secondary: z.string().optional(),
  }).optional(),
  // ... more fields
});

export function parseColorTokens(input: unknown): ColorTokens | null {
  const result = ColorTokensSchema.safeParse(input);
  return result.success ? result.data : null;
}
```

### 6. Centralize Query Result Types

**Problem:** Query results from React Query don't always have explicit types.

**Solution:** Always type query functions and results.

```typescript
// ✅ Good: Explicit return type
export async function fetchThemeLibrary(): Promise<ThemeLibraryResult> {
  const response = await requestJson<ThemeLibraryResponse>(...);
  return response.data;
}

// ✅ Good: Typed query key factory
export const queryKeys = {
  themes: () => ['themes', 'library'] as const,
  theme: (id: number) => ['themes', id] as const,
};

// ✅ Good: Typed query hooks
export function useThemeLibraryQuery() {
  return useQuery({
    queryKey: queryKeys.themes(),
    queryFn: fetchThemeLibrary, // TypeScript infers return type
  });
}
```

### 7. Use Strict TypeScript Configuration

**Recommended `tsconfig.json` settings:**

```json
{
  "compilerOptions": {
    "strict": true,
    "noImplicitAny": true,
    "strictNullChecks": true,
    "strictPropertyInitialization": true,
    "noUncheckedIndexedAccess": true, // Prevents accessing arrays/objects without checks
    "noImplicitReturns": true,
    "noFallthroughCasesInSwitch": true
  }
}
```

### 8. Add Type Checking to CI/CD

Add a type-check step to your build process:

```json
// package.json
{
  "scripts": {
    "type-check": "tsc --noEmit",
    "build": "npm run type-check && vite build"
  }
}
```

## Migration Path

1. **Phase 1:** Create centralized utilities (`utils/json.ts`, `utils/themeHelpers.ts`)
2. **Phase 2:** Define interfaces for all parsed JSON structures (`types/tokens.ts`)
3. **Phase 3:** Migrate one file at a time to use new utilities
4. **Phase 4:** Add runtime validation where needed (optional, using zod)
5. **Phase 5:** Enable stricter TypeScript settings incrementally

## Quick Wins

1. **Replace all `safeParse` duplicates** with a single import from `utils/json`
2. **Add interfaces** for the most commonly accessed structures (ColorTokens, TypographyTokens)
3. **Create helper functions** for frequently repeated patterns
4. **Document** the expected structure of parsed JSON in JSDoc comments

## Example: Refactored Code

**Before:**
```typescript
const colorTokens = safeParse(theme.color_tokens);
const primary = (colorTokens as any)?.accent?.primary || '#2563eb';
```

**After:**
```typescript
import { safeParse } from '@/utils/json';
import { getThemeColor } from '@/utils/themeHelpers';
import type { ColorTokens } from '@/types/tokens';

const primary = getThemeColor(theme, ['accent', 'primary'], '#2563eb');
// OR
const colorTokens = safeParse<ColorTokens>(theme.color_tokens);
const primary = colorTokens?.accent?.primary ?? '#2563eb';
```

## Summary

The root causes of these errors are:
1. **Type information loss** when parsing JSON
2. **Lack of centralized utilities** leading to duplication
3. **Missing type definitions** for parsed structures
4. **Over-reliance on `as any`** instead of proper typing

The solutions are:
1. **Centralize parsing utilities** with type guards
2. **Define explicit interfaces** for all parsed structures
3. **Use helper functions** for common access patterns
4. **Enable strict TypeScript** settings
5. **Validate at runtime** when needed

By following these strategies, we can prevent most of these errors from occurring in the future.

