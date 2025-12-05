# PodaBio Product Gaps - TODO List

**Generated**: 2025-01-21  
**Status**: Active  
**Priority**: High

---

## 🔴 Critical Priority (Must Fix)

### 1. Add Username Selection During Signup
- [ ] Add username field to `signup.php` form (optional but recommended)
- [ ] Add username validation (3-30 chars, alphanumeric + underscore + hyphen)
- [ ] Add real-time username availability check via AJAX
- [ ] Modify `classes/User.php::create()` to accept optional username parameter
- [ ] Create page automatically after username is provided during signup
- [ ] Add username prompt after Google OAuth signup (before redirect to dashboard)
- [ ] Update `auth/google/callback.php` to prompt for username for new users
- [ ] Add username field to signup success message/redirect flow
- [ ] Test complete signup flow with username selection
- [ ] Update signup form UI to show username preview (poda.bio/username)

**Files to Modify**:
- `signup.php`
- `auth/google/callback.php`
- `classes/User.php`
- `classes/Page.php`
- `api/check-username.php` (may need to expose for signup form)
- `admin-ui/src/components/overlays/CreatePageDrawer.tsx` (may need to adapt for signup)

**Estimated Time**: 6-8 hours

---

### 2. Email Verification Auto-Login & Onboarding
- [ ] Modify `verify-email.php` to auto-login user after successful verification
- [ ] Check if user has a page after verification
- [ ] If no page exists, redirect to page creation flow instead of login
- [ ] Add welcome message/onboarding modal after first login
- [ ] Create onboarding component showing key features
- [ ] Add "Getting Started" checklist
- [ ] Update `login.php` to handle newly verified users
- [ ] Test complete verification → login → onboarding flow

**Files to Modify**:
- `verify-email.php`
- `login.php`
- `admin-ui/src/components/onboarding/` (new directory)
- `admin/userdashboard.php` (may need onboarding check)

**Estimated Time**: 4-6 hours

---

### 3. Implement Email Service API Integrations
- [ ] Research API documentation for each service
- [ ] Implement Mailchimp API integration
- [ ] Implement ConvertKit API integration
- [ ] Implement MailerLite API integration
- [ ] Implement Constant Contact API integration (optional)
- [ ] Implement AWeber API integration (optional)
- [ ] Implement SendinBlue/Brevo API integration (optional)
- [ ] Add proper error handling for each service
- [ ] Add webhook handlers for subscription confirmations
- [ ] Test each integration end-to-end
- [ ] Update `classes/EmailSubscription.php::subscribeToService()`
- [ ] Add service-specific configuration UI (API keys, list IDs)

**Files to Modify**:
- `classes/EmailSubscription.php`
- `api/subscribe.php` (may need updates)
- Admin UI: Email service configuration component (new)

**Estimated Time**: 16-24 hours (2-3 hours per service)

**Priority Services**: Mailchimp, ConvertKit, MailerLite (most popular)

---

### 4. Configure Stripe Payment Integration
- [ ] Create Stripe account (if not exists)
- [ ] Create "PodaBio Pro Monthly" product in Stripe Dashboard ($4.99/month)
- [ ] Create "PodaBio Pro Annual" product in Stripe Dashboard ($53.89/year)
- [ ] Copy Price IDs from Stripe Dashboard
- [ ] Add Stripe API keys to `config/payments.php` or `config/local.php`
  - `STRIPE_SECRET_KEY`
  - `STRIPE_PUBLISHABLE_KEY`
  - `STRIPE_PRO_MONTHLY_PRICE_ID`
  - `STRIPE_PRO_ANNUAL_PRICE_ID`
- [ ] Set up Stripe webhook endpoint
- [ ] Get webhook signing secret
- [ ] Add `STRIPE_WEBHOOK_SECRET` to config
- [ ] Test payment flow in Stripe test mode
- [ ] Test 14-day free trial signup
- [ ] Test subscription creation
- [ ] Test webhook events (subscription created, updated, deleted)
- [ ] Switch to live mode when ready
- [ ] Update `STRIPE_MODE` to 'live'

**Files to Modify**:
- `config/payments.php` or `config/local.php`
- `api/stripe/webhook.php` (verify webhook URL is correct)
- `admin-ui/src/components/panels/BillingPanel.tsx` (verify Stripe keys are exposed)

**Documentation**: See `STRIPE_IMPLEMENTATION_SUMMARY.md` and `LOCAL_STRIPE_SETUP.md`

**Estimated Time**: 2-4 hours (mostly configuration, not coding)

---

### 5. Complete Profile Editing Functionality
- [ ] Review `api/account/profile.php` - check if update endpoint exists
- [ ] If missing, create `POST /api/account/profile` endpoint
- [ ] Add profile update method to User class (if missing)
- [ ] Make profile fields editable in `admin-ui/src/components/panels/AccountPanel.tsx`
- [ ] Add form validation (email format, name length, etc.)
- [ ] Add save/cancel buttons
- [ ] Add success/error feedback
- [ ] Test profile update flow
- [ ] Remove "coming soon" message

**Files to Modify**:
- `api/account/profile.php`
- `classes/User.php` (may need `updateProfile()` method)
- `admin-ui/src/components/panels/AccountPanel.tsx`
- `admin-ui/src/api/account.ts` (may need update function)

**Estimated Time**: 4-6 hours

---

### 6. Complete 2FA UI Implementation
- [ ] Review `admin-ui/src/components/overlays/TwoFactorSetupModal.tsx` - check if complete
- [ ] Connect 2FA setup UI to backend API (`api/account/2fa.php`)
- [ ] Add "Enable 2FA" button to Security tab
- [ ] Implement QR code display for TOTP setup
- [ ] Add backup codes generation and display
- [ ] Add "Disable 2FA" functionality
- [ ] Remove "Coming soon" placeholder
- [ ] Test complete 2FA setup and login flow
- [ ] Test backup codes usage

**Files to Modify**:
- `admin-ui/src/components/panels/AccountPanel.tsx` (SecurityTab)
- `admin-ui/src/components/overlays/TwoFactorSetupModal.tsx`
- `admin-ui/src/api/account.ts` (2FA functions)
- `api/account/2fa.php` (verify all endpoints exist)

**Estimated Time**: 6-8 hours

---

## 🟡 Medium Priority (Should Fix)

### 7. Add Custom Domain Configuration UI
- [ ] Create custom domain settings component
- [ ] Add domain input field with validation
- [ ] Add DNS verification display (show required DNS records)
- [ ] Add domain validation status indicator
- [ ] Integrate into Settings panel or Account panel
- [ ] Connect to existing backend domain validation
- [ ] Test domain configuration flow
- [ ] Add error handling for invalid domains

**Files to Create/Modify**:
- `admin-ui/src/components/panels/CustomDomainSettings.tsx` (new)
- `api/page.php` (verify custom domain update endpoint exists)
- Settings panel or Account panel (integration point)

**Estimated Time**: 6-8 hours

---

### 8. Test and Fix Image Cropping
- [ ] Test profile image upload and cropping
- [ ] Test background image upload and cropping
- [ ] Test widget thumbnail upload and cropping
- [ ] Verify Croppie.js or React alternative is working
- [ ] Fix any cropping issues found
- [ ] Test on different browsers
- [ ] Test on mobile devices
- [ ] Verify cropped images are saved correctly

**Files to Review/Modify**:
- Image upload components in admin-ui
- `classes/ImageHandler.php` (if exists)
- Cropping library integration

**Estimated Time**: 4-6 hours

---

### 9. Verify Password Reset Email Flow
- [ ] Verify `sendPasswordResetEmail()` function exists in `includes/helpers.php`
- [ ] Test password reset email sending
- [ ] Verify email template exists and is correct
- [ ] Test password reset link in email
- [ ] Test complete flow: forgot password → email → reset → login
- [ ] Fix any issues found

**Files to Review/Modify**:
- `includes/helpers.php`
- `forgot-password.php`
- `reset-password.php`
- Email templates

**Estimated Time**: 2-3 hours

---

### 10. Create Onboarding Flow
- [ ] Design onboarding flow (welcome, features, getting started)
- [ ] Create onboarding component
- [ ] Add welcome modal/tour
- [ ] Add step-by-step guidance for first-time users
- [ ] Add "Getting Started" checklist
- [ ] Show feature highlights
- [ ] Add "Skip" option
- [ ] Track onboarding completion in user preferences
- [ ] Test onboarding flow

**Files to Create/Modify**:
- `admin-ui/src/components/onboarding/OnboardingFlow.tsx` (new)
- `admin-ui/src/components/onboarding/WelcomeModal.tsx` (new)
- `admin-ui/src/components/onboarding/GettingStartedChecklist.tsx` (new)
- `admin/userdashboard.php` (check for onboarding flag)

**Estimated Time**: 8-12 hours

---

### 11. Complete Email Subscription Confirmation
- [ ] Verify `confirmation_token` column exists in `email_subscriptions` table
- [ ] Implement confirmation email sending in `EmailSubscription::subscribe()`
- [ ] Create confirmation email template
- [ ] Create confirmation endpoint/page (`/subscribe/confirm.php` or similar)
- [ ] Test double opt-in flow
- [ ] Test confirmation email delivery
- [ ] Test confirmation link
- [ ] Update subscription status after confirmation

**Files to Create/Modify**:
- `classes/EmailSubscription.php`
- `subscribe/confirm.php` (new confirmation page)
- `includes/helpers.php` (confirmation email function)
- Email templates

**Estimated Time**: 4-6 hours

---

## 🟢 Low Priority (Nice to Have)

### 12. Improve Error Handling
- [ ] Audit all API endpoints for consistent error handling
- [ ] Standardize error response format
- [ ] Add user-friendly error messages
- [ ] Improve error logging
- [ ] Add error tracking/monitoring

**Estimated Time**: 8-12 hours

---

### 13. Add Comprehensive Documentation
- [ ] Create user documentation
- [ ] Document API endpoints
- [ ] Add setup guides
- [ ] Create troubleshooting guides
- [ ] Add feature documentation

**Estimated Time**: 12-16 hours

---

### 14. Perform End-to-End Testing
- [ ] Create test plan
- [ ] Test complete user signup flow
- [ ] Test page creation and editing
- [ ] Test payment processing
- [ ] Test email subscriptions
- [ ] Test 2FA setup and login
- [ ] Test password reset flow
- [ ] Document test results
- [ ] Fix issues found

**Estimated Time**: 16-24 hours

---

### 15. Performance Optimization
- [ ] Profile database queries
- [ ] Optimize image loading
- [ ] Review API response times
- [ ] Optimize frontend bundle size
- [ ] Add caching where appropriate

**Estimated Time**: 12-16 hours

---

## Progress Tracking

**Total Tasks**: 15 major items, ~100+ sub-tasks  
**Estimated Total Time**: 120-180 hours  
**Current Status**: 0% complete

### Phase 1: Critical UX (Week 1)
- [ ] Task 1: Username Selection (0%)
- [ ] Task 2: Email Verification Onboarding (0%)
- [ ] Task 5: Profile Editing (0%)

### Phase 2: Payment & Core Features (Week 2)
- [ ] Task 4: Stripe Configuration (0%)
- [ ] Task 6: 2FA UI (0%)
- [ ] Task 3: Email Service APIs (0%)

### Phase 3: Feature Completion (Week 3)
- [ ] Task 7: Custom Domain UI (0%)
- [ ] Task 11: Email Confirmation (0%)
- [ ] Task 8: Image Cropping (0%)

### Phase 4: Polish & Documentation (Week 4)
- [ ] Task 10: Onboarding Flow (0%)
- [ ] Task 12: Error Handling (0%)
- [ ] Task 13: Documentation (0%)
- [ ] Task 14: Testing (0%)
- [ ] Task 15: Performance (0%)

---

## Notes

- **Priority**: Focus on Critical items first, then Medium, then Low
- **Dependencies**: Some tasks depend on others (e.g., Stripe config before testing payments)
- **Testing**: Each task should include testing before marking complete
- **Documentation**: Update documentation as features are completed

---

**Last Updated**: 2025-01-21  
**Next Review**: Weekly during implementation

