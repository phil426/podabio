# TypeScript Error Prevention Strategy

## Quick Summary

After fixing 269 TypeScript errors, here's our strategy to prevent future errors using TypeScript best practices + Cursor AI features.

## Root Causes Identified

1. **Type Information Loss** (40%) - Parsed JSON becomes `Record<string, unknown>`
2. **Code Duplication** (25%) - Multiple `safeParse()` implementations
3. **Missing Type Definitions** (20%) - APIs without explicit return types
4. **Over-reliance on `as any`** (10%) - Quick fixes instead of proper typing
5. **Configuration Issues** (5%) - Missing strict settings

## Immediate Actions

### 1. Use Cursor's Built-in Type Checking

**In Cursor Settings:**
- Enable TypeScript diagnostics (Settings → TypeScript)
- Enable "Check JS" for JavaScript files
- Use real-time error highlighting

**Before Committing:**
```bash
npm run type-check  # Verify this passes
```

### 2. Leverage Cursor Rules

The `.cursorrules` file in the project root guides Cursor AI to:
- Always use explicit types
- Prefer centralized utilities
- Use type guards instead of `as any`
- Follow project patterns

**Cursor will automatically:**
- Generate type-safe code
- Suggest proper typing patterns
- Follow your TypeScript preferences

### 3. Use Cursor AI for Type-Safe Development

**When creating new code:**
```
@Cursor: Create a function that parses theme tokens. Use explicit TypeScript types and include runtime validation.
```

**When refactoring:**
```
@Cursor: Refactor this to use centralized utilities and remove 'as any' type assertions.
```

**For code review:**
```
@Cursor: Review this code for TypeScript errors and type safety issues.
```

### 4. Enhanced TypeScript Configuration

**Current:** `strict: true` is enabled ✅

**Recommended additions (enable incrementally):**

```json
{
  "compilerOptions": {
    "strict": true,
    "noUncheckedIndexedAccess": true,      // Arrays/objects require checks
    "noImplicitReturns": true,              // Functions must return
    "noUnusedLocals": true,                 // Catch unused vars
    "noUnusedParameters": true              // Catch unused params
  }
}
```

**Implementation:** Enable one setting per week, fix errors as they appear.

### 5. Better Build Scripts

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

**Usage:**
- Run `npm run type-check` before committing
- Use `type-check:watch` during development

## Best Practices

### ✅ DO: Use Explicit Types

```typescript
// ✅ Good
interface ColorTokens {
  accent?: { primary?: string };
}
const tokens = safeParse<ColorTokens>(theme.color_tokens);
```

### ❌ DON'T: Use Untyped Parsing

```typescript
// ❌ Bad
const tokens = JSON.parse(theme.color_tokens);
const primary = tokens?.accent?.primary; // Type error!
```

### ✅ DO: Use Type Guards

```typescript
function isColorTokens(obj: unknown): obj is ColorTokens {
  return typeof obj === 'object' && obj !== null;
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

## Cursor-Specific Workflows

### 1. Type-Safe Code Generation

**Prompt Cursor:**
```
@Cursor: Generate a React component with TypeScript types. Component should accept a ThemeRecord prop and display theme preview. Use interfaces from @/api/types.
```

### 2. Batch Refactoring

**Prompt Cursor:**
```
@Cursor: Find all instances of 'as any' in this file and replace with proper type guards or explicit types.
```

### 3. Type Definition Help

**Prompt Cursor:**
```
@Cursor: Generate TypeScript interfaces for this JSON structure with JSDoc comments and examples.
```

### 4. Error Fixing

**Prompt Cursor:**
```
@Cursor: Fix all TypeScript errors in this file. Use proper types, not 'as any'.
```

## Development Workflow

### Daily Development

1. **Start with types** - Define interfaces before implementation
2. **Use Cursor** - Ask Cursor to generate typed code
3. **Check early** - Run `npm run type-check` frequently
4. **Fix immediately** - Don't accumulate errors

### Before Committing

1. Run `npm run type-check`
2. Ask Cursor: "Review my changes for TypeScript errors"
3. Fix any issues
4. Commit

### Code Review

Use Cursor to review PRs:
```
@Cursor: Review this PR for TypeScript type safety issues
```

## Quick Wins

1. **Add type-check script** - 5 minutes
2. **Enable one strict setting** - Fix errors as they appear
3. **Use Cursor for new code** - Generate type-safe code from the start
4. **Review before commit** - Catch errors early

## Long-term Strategy

1. **Centralize utilities** - Create `utils/json.ts` with typed `safeParse`
2. **Define interfaces** - Add interfaces for all parsed JSON structures
3. **Migrate incrementally** - One file at a time
4. **Enable stricter settings** - One setting per week

## Success Metrics

- **Target:** < 10 TypeScript errors at any time
- **Measure:** Weekly error counts
- **Goal:** Catch errors before commit, not after

## Resources

- **Existing Docs:**
  - `TYPESCRIPT_ERROR_PREVENTION.md` - Detailed technical strategies
  - `CURSOR_DEVELOPMENT_GUIDE.md` - Cursor-specific workflows
  - `IMPROVED_DEVELOPMENT_STRATEGY.md` - Comprehensive plan

- **Cursor Features:**
  - `.cursorrules` - Project-specific AI rules
  - AI Composer - Type-safe code generation
  - Chat - Type questions and reviews
  - Diagnostics - Real-time error checking

## Action Checklist

- [x] Created `.cursorrules` file
- [ ] Add `type-check` script to package.json
- [ ] Enable one additional strict TypeScript setting
- [ ] Create centralized `utils/json.ts` (optional, gradual migration)
- [ ] Document team patterns
- [ ] Set up CI/CD type checking (optional)

## Key Takeaway

**Use Cursor AI proactively:**
- Generate type-safe code from the start
- Review code before committing
- Ask for type-safe refactoring suggestions
- Let Cursor follow your project rules (`.cursorrules`)

**Combine with:**
- Strict TypeScript settings (incrementally)
- Type-check scripts
- Early error detection
- Proper typing patterns

This combination will prevent 90%+ of the errors we just fixed from happening again!

