# Resolution Protocol - Quick Start Guide

## How to Use

When you encounter a problem, simply say:

> **"Apply Resolution Protocol to [your problem description]"**

### Examples

```
"Apply Resolution Protocol to: API endpoints returning 401 errors"
"Apply Resolution Protocol to: React app not loading on production"
"Apply Resolution Protocol to: Database connection failing"
"Apply Resolution Protocol to: Images not uploading"
```

## What Happens

The AI agent will automatically:

1. **Gather Information** - Search codebase, check logs, review recent changes
2. **Form Hypotheses** - Generate likely causes based on evidence
3. **Test Systematically** - Test each hypothesis in priority order
4. **Analyze Results** - Look for patterns and refine approach
5. **Implement Solution** - Apply fix and verify it works
6. **Document** - Record the solution for future reference

## What You'll See

The agent will provide updates like:

```
🔍 RESOLUTION PROTOCOL ACTIVATED
Problem: API endpoints returning 401 Unauthorized errors

Phase 1: Gathering Information
✅ Found 5 related files
✅ Reviewed 3 recent commits
✅ Checked session configuration

Phase 2: Forming Hypotheses
✅ Generated 6 hypotheses, prioritized by likelihood
Top hypothesis: Session cookies not being sent (likelihood: 9/10)

Phase 3: Testing Hypotheses
🧪 Testing: Session cookies not being sent
✅ CONFIRMED - Session cookies configured correctly
🧪 Testing: Session expired or invalid
❌ REFUTED - Sessions are valid
...

Phase 5: Implementing Solution
🔧 Fix: Add credentials: 'include' to all fetch requests
✅ Solution verified - problem resolved!

📝 Solution documented
✅ RESOLUTION PROTOCOL COMPLETE
```

## Key Features

- **Autonomous** - Works without constant intervention
- **Systematic** - Tests hypotheses in logical order
- **Traceable** - Logs all actions and findings
- **Iterative** - Refines approach based on new evidence
- **Documented** - Records solutions for future reference

## When to Use

Use the Resolution Protocol for:
- ✅ Complex bugs that aren't immediately obvious
- ✅ Issues that require investigation across multiple files
- ✅ Problems that have multiple possible causes
- ✅ Situations where you want a systematic approach

Don't use for:
- ❌ Simple syntax errors (just fix them)
- ❌ Obvious typos or missing files
- ❌ Questions that need quick answers

## Tips

1. **Be specific** - The more detail you provide, the better
2. **Include error messages** - Copy/paste actual errors
3. **Mention recent changes** - If you know what changed, say so
4. **Be patient** - The protocol may take several iterations

## Example Session

**You:** "Apply Resolution Protocol to: Users can't log in, getting 'Invalid credentials' error even with correct password"

**AI:** 
```
🔍 RESOLUTION PROTOCOL ACTIVATED
Problem: Users can't log in, getting 'Invalid credentials' error

Phase 1: Gathering Information
- Checking User.php login method...
- Reviewing password hashing implementation...
- Examining session creation after login...
✅ Information gathered: 8 components, 2 recent changes

Phase 2: Forming Hypotheses
1. Password hashing algorithm mismatch (likelihood: 9/10)
2. Database password column type issue (likelihood: 7/10)
3. Login form not submitting password correctly (likelihood: 6/10)
...

Phase 3: Testing
🧪 Testing: Password hashing algorithm mismatch
✅ CONFIRMED - password_verify() using different algorithm than password_hash()

Phase 5: Implementing Solution
🔧 Fix: Update password hashing to use consistent algorithm
✅ Solution verified - login works!

✅ RESOLUTION PROTOCOL COMPLETE
Root cause: Password hashing algorithm mismatch
Solution: Updated to use PASSWORD_BCRYPT consistently
```

---

**Remember:** Just say "Apply Resolution Protocol to [problem]" and let the AI work!

