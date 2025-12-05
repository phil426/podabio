# Production vs Development Differences - Debugging Guide

## Common Issues and Solutions

### Problem: JavaScript Works Locally But Not in Production

**Root Causes:**
1. **Script Execution Timing**
   - Dev: React/HMR loads early, functions ready
   - Prod: Scripts load later or in different order
   - **Fix**: Define functions in `<head>`, use event delegation

2. **Browser Caching**
   - Dev: Cache cleared frequently
   - Prod: Aggressive caching of old files
   - **Fix**: Use cache busting (`?v=timestamp`), test in incognito

3. **React Hydration Timing**
   - Dev: Waits for React before scripts run
   - Prod: Scripts may run before React hydrates
   - **Fix**: Don't rely on React for critical functions

4. **Network Latency**
   - Dev: Local network is fast
   - Prod: Network delays affect script loading order
   - **Fix**: Define functions synchronously in HEAD

### Best Practices

1. **Always Test Production Builds Locally**
   ```bash
   cd admin-ui
   npm run build
   # Serve the production build locally
   ```

2. **Use Event Delegation**
   - Works even if elements don't exist yet
   - Handles dynamically added content
   - No timing issues

3. **Define Critical Functions in HEAD**
   - Available immediately
   - No dependency on script execution order
   - Works with inline onclick handlers

4. **Never Hide Content Until JavaScript Confirms It's Ready**
   - Content should be visible by default
   - JavaScript adds enhancements
   - Progressive enhancement approach

5. **Add Console Logging**
   ```javascript
   console.log('✅ Function loaded');
   console.error('❌ Error:', err);
   ```

6. **Check Browser Console**
   - Always check for errors in production
   - Use Network tab to verify script loading
   - Check Elements tab to verify DOM structure

### Testing Checklist

- [ ] Test with production build locally
- [ ] Test in incognito/private mode (no cache)
- [ ] Check browser console for errors
- [ ] Verify Network tab - all scripts load
- [ ] Test with slow network throttling
- [ ] Test with JavaScript disabled (should still show content)
- [ ] Test on different browsers
- [ ] Test on mobile devices

### Quick Debugging Steps

1. Open browser console (F12)
2. Check for JavaScript errors
3. Verify functions exist: `console.log(window.toggleAccordion)`
4. Check Network tab - are scripts loading?
5. Check Elements tab - do elements exist?
6. Test in incognito mode (clears cache)

### Common Patterns That Cause Issues

❌ **BAD:**
- Defining functions at bottom of page
- Relying on DOM ready before defining functions
- Hiding content with CSS before JavaScript runs
- Assuming scripts execute in specific order

✅ **GOOD:**
- Define functions in HEAD
- Use event delegation
- Content visible by default
- Don't assume execution order

