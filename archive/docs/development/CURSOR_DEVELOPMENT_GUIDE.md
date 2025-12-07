# Cursor Development Guide - TypeScript Best Practices

## Leveraging Cursor's AI-Powered Development Tools

Cursor offers powerful AI-assisted development features that can significantly improve TypeScript code quality and reduce errors. Here's how to use them effectively:

### 1. Cursor Rules (.cursorrules)

Create a `.cursorrules` file in your project root to give Cursor AI context about your project's TypeScript patterns and preferences.

### 2. TypeScript-Aware Completions

Cursor's AI understands TypeScript types. Use it to:
- **Generate typed components** - Ask Cursor to generate components with proper prop types
- **Type inference help** - When Cursor suggests code, it will maintain type safety
- **Refactoring assistance** - Cursor can refactor with type safety in mind

### 3. Error Prevention Before Commits

Use Cursor's built-in TypeScript checking to catch errors before they're committed:
- Enable TypeScript diagnostics in Cursor settings
- Use Cursor's code review before committing
- Leverage Cursor's batch fix capabilities

### 4. AI-Powered Code Reviews

Before committing, ask Cursor to review your changes:
```
@Cursor: Review this code for TypeScript errors and type safety issues
```

### 5. Context-Aware Suggestions

Cursor maintains context across files. When working with:
- API responses → Cursor remembers the type definitions
- Theme tokens → Cursor understands the structure
- Component props → Cursor suggests based on existing patterns

## Cursor-Specific Workflows

### Workflow 1: Type-Safe Component Generation

```
@Cursor: Create a new React component called ThemePreviewCard that takes a ThemeRecord prop and displays theme preview. Use proper TypeScript types from @/api/types
```

Cursor will:
- Generate properly typed component
- Import types correctly
- Use existing patterns from your codebase

### Workflow 2: Safe Refactoring

```
@Cursor: Refactor this function to use the centralized safeParse utility from @/utils/json instead of the local implementation. Maintain all type safety.
```

Cursor will:
- Find all instances
- Update imports
- Maintain type safety throughout

### Workflow 3: Batch Type Fixes

```
@Cursor: Find all instances where we use 'as any' type assertions and suggest type-safe alternatives using proper interfaces and type guards.
```

### Workflow 4: Type Definition Generation

```
@Cursor: Generate TypeScript interfaces for the parsed JSON structure returned by this API endpoint. Include JSDoc comments with examples.
```

## Best Practices with Cursor

1. **Always specify types in prompts** - "Create a function that accepts a ThemeRecord and returns a Promise<string>"
2. **Reference existing patterns** - "Use the same typing pattern as ThemeEditorPanel.tsx"
3. **Ask for type guards** - "Add runtime type validation for this API response"
4. **Request refactoring suggestions** - "How can I make this code more type-safe?"

