# Problem Solving Protocols

Systematic methodologies for debugging and resolving complex issues in the PodaBio project.

## Table of Contents

1. [Hard Problem Protocol](#hard-problem-protocol)
2. [Resolution Protocol](#resolution-protocol)
3. [Quick Start Guide](#quick-start-guide)
4. [When to Use Each Protocol](#when-to-use-each-protocol)

---

## Hard Problem Protocol 🔍

> **A systematic, agentic problem-solving methodology for complex debugging scenarios**

### Quick Reference

When you encounter a problem, simply say:
> **"Apply Hard Problem Protocol to [problem description]"**

The AI will then execute this systematic debugging process autonomously until a solution is found.

### Overview

The Hard Problem Protocol is a recursive, evidence-based debugging methodology that:
- **Systematically gathers information** about the problem
- **Forms and tests hypotheses** in order of likelihood
- **Logs all attempts and findings** for traceability
- **Iterates automatically** until the root cause is identified
- **Documents the solution** for future reference

### The Protocol Steps

#### Phase 1: Problem Definition & Information Gathering
1. **Define the problem clearly** - What exactly is broken? What should work?
2. **Gather initial evidence** - Error messages, logs, symptoms
3. **Identify the scope** - What systems/components are affected?
4. **Check recent changes** - What changed before this started?

#### Phase 2: Hypothesis Formation
1. **List possible causes** - Brainstorm 5-10 potential root causes
2. **Prioritize by likelihood** - Most common issues first
3. **Identify testable hypotheses** - Each cause should have a test

#### Phase 3: Systematic Testing
1. **Test each hypothesis** - One at a time, in priority order
2. **Log all results** - Success or failure, with evidence
3. **Gather additional data** - If test fails, what did we learn?

#### Phase 4: Analysis & Iteration
1. **Analyze test results** - What patterns emerge?
2. **Refine hypotheses** - Update based on new evidence
3. **Repeat testing** - Continue until root cause found

#### Phase 5: Solution Implementation
1. **Implement fix** - Apply the solution
2. **Verify the fix** - Test that problem is resolved
3. **Check for regressions** - Ensure nothing else broke
4. **Document solution** - Record what fixed it and why

### Agentic Execution

When you invoke the Hard Problem Protocol, the AI will:

1. **Automatically gather information** - Read relevant files, check logs, examine code
2. **Form hypotheses** - Based on common patterns and your codebase
3. **Test systematically** - Try fixes, check results, iterate
4. **Document progress** - Log all attempts in a structured format
5. **Continue until solved** - Won't stop until root cause is found

### Example Usage

```
"Apply Hard Problem Protocol to: React app not loading on production, 
shows blank page but works fine locally"
```

The AI will:
- Check build configuration
- Verify manifest.json exists
- Check server paths
- Test API endpoints
- Review error logs
- Continue until solution found

---

## Resolution Protocol

> **A structured approach to resolving issues with clear steps and verification**

### Overview

The Resolution Protocol provides a clear, step-by-step framework for:
- Identifying the root cause
- Implementing fixes
- Verifying solutions
- Preventing recurrence

### Protocol Steps

#### Step 1: Problem Identification
- **What is the issue?** - Clear description
- **When does it occur?** - Conditions, triggers
- **Who is affected?** - Users, systems, components
- **What is the expected behavior?** - What should happen

#### Step 2: Root Cause Analysis
- **Check logs** - Error messages, stack traces
- **Review recent changes** - Git history, deployments
- **Test hypotheses** - Isolate variables
- **Identify root cause** - Not just symptoms

#### Step 3: Solution Design
- **Plan the fix** - What needs to change
- **Consider side effects** - What else might break
- **Design tests** - How to verify the fix
- **Document approach** - Why this solution

#### Step 4: Implementation
- **Make changes** - Code, config, data
- **Test locally** - Verify fix works
- **Check for regressions** - Nothing else broke
- **Update documentation** - If needed

#### Step 5: Verification
- **Test the fix** - Does it solve the problem?
- **Verify no regressions** - Other features still work
- **Monitor in production** - Watch for issues
- **Document solution** - Add to knowledge base

#### Step 6: Prevention
- **Identify prevention** - How to avoid this again
- **Update processes** - If needed
- **Add monitoring** - If applicable
- **Share knowledge** - Team awareness

---

## Quick Start Guide

### When to Use Hard Problem Protocol

Use for:
- Complex bugs with unclear causes
- Issues affecting multiple systems
- Problems that resist quick fixes
- When you need systematic investigation

**Invoke with:**
```
"Apply Hard Problem Protocol to: [your problem description]"
```

### When to Use Resolution Protocol

Use for:
- Clear, well-defined issues
- Problems with known patterns
- When you need structured approach
- Team collaboration scenarios

**Follow the 6 steps:**
1. Problem Identification
2. Root Cause Analysis
3. Solution Design
4. Implementation
5. Verification
6. Prevention

---

## When to Use Each Protocol

### Hard Problem Protocol
- ✅ Complex, multi-system issues
- ✅ Unclear root causes
- ✅ Need systematic investigation
- ✅ Want AI to handle autonomously

### Resolution Protocol
- ✅ Clear, well-defined problems
- ✅ Known issue patterns
- ✅ Team collaboration needed
- ✅ Want structured manual process

### Both Protocols
- ✅ Can be combined
- ✅ Use Hard Problem for investigation
- ✅ Use Resolution for implementation
- ✅ Document in both formats

---

## Best Practices

### Documentation
- Always document the solution
- Include what didn't work (negative results)
- Note prevention strategies
- Update relevant docs

### Testing
- Test fixes thoroughly
- Check for regressions
- Verify in production
- Monitor after deployment

### Communication
- Share solutions with team
- Update knowledge base
- Add to troubleshooting guides
- Document patterns

---

## Examples

### Example 1: Database Connection Timeout

**Hard Problem Protocol:**
```
"Apply Hard Problem Protocol to: Database connection timeouts 
occurring randomly in production"
```

AI will systematically:
- Check connection pool settings
- Review query performance
- Examine server resources
- Test connection patterns
- Continue until root cause found

**Resolution Protocol:**
1. **Problem**: Random DB timeouts
2. **Root Cause**: Connection pool exhausted
3. **Solution**: Increase pool size, add connection cleanup
4. **Implementation**: Update config, add monitoring
5. **Verification**: Monitor connections, test under load
6. **Prevention**: Add connection pool monitoring

### Example 2: React Build Failing

**Hard Problem Protocol:**
```
"Apply Hard Problem Protocol to: Vite build failing with 
TypeScript errors that don't appear in dev mode"
```

AI will:
- Check tsconfig differences
- Review build vs dev settings
- Examine type definitions
- Test build process
- Find and fix root cause

---

## Integration with Work History

All solutions should be documented in `docs/WORK_HISTORY.md`:
- Date and category
- Problem description
- Root cause
- Solution implemented
- Files modified
- Prevention measures

---

**Last Updated:** 2025-01-XX  
**Status:** Active

