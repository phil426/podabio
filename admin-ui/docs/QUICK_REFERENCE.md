# Quick Reference: Preventing TypeScript Errors

## Daily Development Checklist

### Before Writing Code
- [ ] Define TypeScript interfaces first
- [ ] Ask Cursor to generate type-safe boilerplate

### While Writing Code
- [ ] Use explicit types, not `any`
- [ ] Use centralized utilities (when available)
- [ ] Run `npm run type-check` after significant changes

### Before Committing
- [ ] Run `npm run type-check`
- [ ] Ask Cursor: "Review my changes for TypeScript errors"
- [ ] Fix any issues immediately

## Common Patterns

### ✅ Parsing JSON

```typescript
// Use typed parsing with explicit interface
import { safeParse } from '@/utils/json'; // When centralized
// OR define local with type
const tokens = safeParse<ColorTokens>(theme.color_tokens);
```

### ✅ Accessing Nested Properties

```typescript
// Use optional chaining and nullish coalescing
const primary = tokens?.accent?.primary ?? '#2563eb';
```

### ✅ Type Guards

```typescript
function isColorTokens(obj: unknown): obj is ColorTokens {
  return typeof obj === 'object' && obj !== null;
}
```

### ✅ React Component Props

```typescript
interface MyComponentProps {
  theme: ThemeRecord;
  onSave?: () => void;
}
```

## Cursor Prompts

### Generate Type-Safe Code
```
@Cursor: Create a function that [description]. Use explicit TypeScript types from @/api/types
```

### Review Code
```
@Cursor: Review this code for TypeScript errors and type safety issues
```

### Refactor Safely
```
@Cursor: Refactor this to use proper types instead of 'as any'
```

## Commands

```bash
# Check for TypeScript errors
npm run type-check

# Watch mode (development)
npm run type-check:watch

# Build (includes type checking)
npm run build
```

## Key Rules

1. **Never use `as any`** without justification
2. **Always define interfaces** for object shapes
3. **Use type guards** for runtime validation
4. **Check types early** - run type-check frequently
5. **Let Cursor help** - use AI for type-safe code generation

