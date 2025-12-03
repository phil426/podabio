# Two-Factor Authentication (2FA) Implementation Options

## Current Status
2FA is currently marked as "Coming soon" in the Security tab (`AccountPanel.tsx`). No 2FA implementation exists yet.

---

## Recommended Options

### ✅ Option 1: TOTP Authenticator Apps (RECOMMENDED)
**Best balance of security, user experience, and cost**

**How it works:**
- User scans QR code with authenticator app (Google Authenticator, Authy, Microsoft Authenticator, 1Password, etc.)
- App generates 6-digit codes that change every 30 seconds
- User enters code during login after password

**Pros:**
- ✅ Industry standard, widely supported
- ✅ Works offline (no SMS/email dependency)
- ✅ Very secure (resistant to phishing)
- ✅ Free to implement (no per-user costs)
- ✅ No external service dependencies
- ✅ User-friendly (most users already have authenticator apps)

**Cons:**
- ⚠️ Users need to install an app (but most already have one)
- ⚠️ Can lose access if device is lost (requires backup codes)

**Implementation:**
- PHP Library: `spomky-labs/otphp` or `sonata-project/google-authenticator`
- Database: Add `two_factor_secret` and `two_factor_enabled` columns to `users` table
- UI: QR code generation, code verification, backup codes

**Estimated Effort:** 1-2 days
**Cost:** Free
**Security Level:** High

---

### Option 2: SMS-Based 2FA
**Simple but less secure**

**How it works:**
- User enters phone number
- System sends SMS code during login
- User enters code to complete authentication

**Pros:**
- ✅ Easy to use (everyone has a phone)
- ✅ Familiar to users
- ✅ Quick to implement

**Cons:**
- ❌ Vulnerable to SIM swapping attacks
- ❌ Costs money (per SMS sent)
- ❌ Can be delayed or blocked
- ❌ Doesn't work without cell signal
- ❌ Phone numbers can be ported
- ⚠️ Requires SMS service (Twilio, AWS SNS, etc.)

**Implementation:**
- SMS Service: Twilio (~$0.0075 per SMS) or AWS SNS
- Database: Add `phone_number` and `two_factor_enabled` columns
- Verification: Store and validate SMS codes

**Estimated Effort:** 2-3 days (includes SMS service setup)
**Cost:** ~$0.0075 per SMS + service fees
**Security Level:** Medium

---

### Option 3: Email-Based 2FA
**Simple but least secure**

**How it works:**
- System sends email with verification code
- User enters code from email during login

**Pros:**
- ✅ Easiest to implement (you already send emails)
- ✅ Free (no external service needed)
- ✅ Everyone has email

**Cons:**
- ❌ Email accounts can be compromised
- ❌ Less secure than TOTP
- ❌ Requires email delivery (can be delayed/blocked)
- ❌ Email is often accessible on same device as login

**Implementation:**
- Use existing email system
- Database: Add `two_factor_enabled` column
- Generate and email 6-digit codes

**Estimated Effort:** 1 day
**Cost:** Free
**Security Level:** Low-Medium

---

### Option 4: WebAuthn / Passkeys (FUTURE)
**Most secure, modern standard**

**How it works:**
- User registers hardware key (YubiKey) or uses device biometrics (Face ID, Touch ID, Windows Hello)
- Login uses public key cryptography instead of codes

**Pros:**
- ✅ Highest security (resistant to phishing)
- ✅ No codes to enter (biometric/FIDO2)
- ✅ Modern standard (Apple, Google, Microsoft support)
- ✅ Can support passwordless login

**Cons:**
- ⚠️ More complex to implement
- ⚠️ Requires HTTPS with valid certificate
- ⚠️ Users need compatible devices/keys
- ⚠️ Still relatively new (some users unfamiliar)

**Implementation:**
- PHP Library: `web-auth/webauthn-lib`
- Database: Store public keys and credential IDs
- Browser API: WebAuthn JavaScript API

**Estimated Effort:** 3-5 days
**Cost:** Free (or cost of hardware keys if users buy them)
**Security Level:** Highest

---

## Recommendation: TOTP (Option 1)

**Why TOTP is best for PodaBio:**
1. **Security:** Industry-standard, very secure
2. **Cost:** Completely free
3. **User Experience:** Most users already have authenticator apps
4. **Implementation:** Straightforward with existing PHP libraries
5. **Reliability:** No external service dependencies

---

## Implementation Plan for TOTP

### Phase 1: Backend Setup
1. Install PHP library: `composer require spomky-labs/otphp`
2. Database migration:
   - Add `two_factor_secret` (VARCHAR, nullable) to `users` table
   - Add `two_factor_enabled` (BOOLEAN, default false)
   - Add `two_factor_backup_codes` (JSON/TEXT, nullable) - for recovery
3. API endpoints:
   - `POST /api/account/2fa/enable` - Generate QR code and secret
   - `POST /api/account/2fa/verify` - Verify code and enable 2FA
   - `POST /api/account/2fa/disable` - Disable 2FA
   - `POST /api/account/2fa/generate-backup-codes` - Generate backup codes

### Phase 2: Login Flow
1. Modify `login.php` to check if user has 2FA enabled
2. If enabled, show 2FA code input after password verification
3. Verify TOTP code before completing login

### Phase 3: UI Components
1. Replace "Coming soon" in Security tab with:
   - Enable/Disable 2FA toggle
   - QR code display for setup
   - Backup codes display
   - Status indicator

### Phase 4: Backup Codes
1. Generate 10 one-time backup codes when enabling 2FA
2. Display codes once (user should save them)
3. Allow code redemption during login if device is lost

---

## Quick Comparison

| Method | Security | Cost | UX | Complexity | Recommendation |
|--------|----------|------|----|----|--------------|
| **TOTP (Authenticator)** | ⭐⭐⭐⭐⭐ | Free | ⭐⭐⭐⭐ | Medium | ✅ **Recommended** |
| SMS | ⭐⭐⭐ | ~$0.01/user/month | ⭐⭐⭐⭐⭐ | Medium | Consider as secondary |
| Email | ⭐⭐ | Free | ⭐⭐⭐⭐ | Easy | Not recommended |
| WebAuthn | ⭐⭐⭐⭐⭐ | Free | ⭐⭐⭐⭐⭐ | High | Future enhancement |

---

## Suggested Implementation Order

1. **Now:** Implement TOTP (Option 1) - Best ROI
2. **Later:** Add SMS as optional backup for TOTP
3. **Future:** Add WebAuthn/Passkeys for passwordless option

---

## Libraries & Resources

### PHP TOTP Libraries
- **spomky-labs/otphp** (Recommended) - RFC 6238 compliant
- **sonata-project/google-authenticator** - Google Authenticator compatible

### Frontend QR Code Generation
- **qrcode.js** or `qrcode` npm package for QR code display

### Documentation
- RFC 6238 (TOTP standard)
- Google Authenticator documentation

---

## Next Steps

1. **Choose:** Decide on TOTP (recommended) or another option
2. **Plan:** Review implementation plan above
3. **Database:** Create migration for 2FA columns
4. **Backend:** Implement TOTP secret generation and verification
5. **Frontend:** Build 2FA setup UI in Security tab
6. **Login:** Update login flow to check and verify 2FA codes
7. **Testing:** Test with multiple authenticator apps

---

## Questions to Consider

1. **Mandatory or Optional?** (Recommended: Optional, user choice)
2. **For all users or Pro only?** (Recommendation: Available to all, optional)
3. **Backup method?** (Backup codes are essential)
4. **Account recovery?** (Need process if user loses device + backup codes)

---

## Security Best Practices

- ✅ Always generate backup codes (10 codes minimum)
- ✅ Show backup codes only once during setup
- ✅ Require password confirmation to enable/disable 2FA
- ✅ Log 2FA enable/disable events for security audit
- ✅ Rate limit 2FA code verification attempts
- ✅ Clear session on successful 2FA verification
- ✅ Support multiple authenticator apps (not just Google Authenticator)

---

Would you like me to start implementing TOTP-based 2FA? I can begin with the database migration and backend API endpoints.

