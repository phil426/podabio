# The Hard Problem Protocol 🔍

> **A systematic, agentic problem-solving methodology for complex debugging scenarios**

## Quick Reference

When you encounter a problem, simply say:
> **"Apply Hard Problem Protocol to [problem description]"**

The AI will then execute this systematic debugging process autonomously until a solution is found.

## Overview

The Hard Problem Protocol is a recursive, evidence-based debugging methodology that:
- **Systematically gathers information** about the problem
- **Forms and tests hypotheses** in order of likelihood
- **Logs all attempts and findings** for traceability
- **Iterates automatically** until the root cause is identified
- **Documents the solution** for future reference

## The Protocol Steps

### Phase 1: Problem Definition & Information Gathering
1. **Define the problem clearly** - What exactly is broken? What should work?
2. **Gather initial evidence** - Error messages, logs, symptoms
3. **Identify the scope** - What systems/components are affected?
4. **Check recent changes** - What changed before this started?

### Phase 2: Hypothesis Formation
1. **List possible causes** - Brainstorm 5-10 potential root causes
2. **Prioritize by likelihood** - Most common issues first
3. **Identify testable hypotheses** - Each cause should have a test

### Phase 3: Systematic Testing
1. **Test each hypothesis** - One at a time, in priority order
2. **Log all results** - Success or failure, with evidence
3. **Gather additional data** - If test fails, what did we learn?

### Phase 4: Analysis & Iteration
1. **Analyze test results** - What patterns emerge?
2. **Refine hypotheses** - Update based on new evidence
3. **Repeat testing** - Continue until root cause found

### Phase 5: Solution Implementation
1. **Implement fix** - Apply the solution
2. **Verify the fix** - Test that problem is resolved
3. **Check for regressions** - Ensure nothing else broke
4. **Document solution** - Record what fixed it and why

## Agentic Execution

When the protocol is activated, the AI will:

1. **Autonomously gather information** using available tools
2. **Form hypotheses** based on evidence and experience
3. **Execute tests** programmatically where possible
4. **Log all actions** in a structured format
5. **Iterate automatically** until solution found
6. **Report findings** with clear explanations

## Success Criteria

The protocol is complete when:
- ✅ Root cause is identified
- ✅ Solution is implemented and verified
- ✅ Problem is resolved
- ✅ Solution is documented

## Example Usage

**User:** "Apply Hard Problem Protocol to: API endpoints returning 401 errors"

**AI Response:**
```
🔍 HARD PROBLEM PROTOCOL ACTIVATED
Problem: API endpoints returning 401 Unauthorized errors

Phase 1: Information Gathering
- Checking session configuration...
- Reviewing authentication flow...
- Examining API endpoint code...
[Gathering continues...]

Phase 2: Hypothesis Formation
1. Session cookies not being sent (most likely)
2. Session expired or invalid
3. CSRF token mismatch
4. Cookie domain/path mismatch
[Testing begins...]
```

---

## Implementation

See `HARD_PROBLEM_PROTOCOL_IMPLEMENTATION.md` for the executable algorithm.

