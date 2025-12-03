import { useState, useEffect } from 'react';
import * as Dialog from '@radix-ui/react-dialog';
import { X, Check, CircleNotch, Copy, Shield, Envelope, DeviceMobile } from '@phosphor-icons/react';
import * as ScrollArea from '@radix-ui/react-scroll-area';
import QRCode from 'qrcode';

import {
  useGenerate2FASetupMutation,
  useEnable2FAMutation,
  useEnableEmail2FAMutation,
  useSendSetupEmailCodeMutation,
  type TwoFactorSetupData
} from '../../api/account';

import styles from './two-factor-setup-modal.module.css';

export interface TwoFactorSetupModalProps {
  open: boolean;
  onClose: () => void;
  onSuccess: () => void;
  userEmail?: string;
}

type SetupStep = 'method' | 'totp-setup' | 'totp-verify' | 'email-send' | 'email-verify' | 'backup-codes';

export function TwoFactorSetupModal({ open, onClose, onSuccess, userEmail }: TwoFactorSetupModalProps): JSX.Element {
  const [step, setStep] = useState<SetupStep>('method');
  const [selectedMethod, setSelectedMethod] = useState<'totp' | 'email' | 'both' | null>(null);
  const [totpData, setTotpData] = useState<TwoFactorSetupData | null>(null);
  const [qrCodeDataUrl, setQrCodeDataUrl] = useState<string | null>(null);
  const [verificationCode, setVerificationCode] = useState('');
  const [backupCodes, setBackupCodes] = useState<string[]>([]);
  const [showBackupCodes, setShowBackupCodes] = useState(false);
  const [copiedCodes, setCopiedCodes] = useState(false);

  const generateSetupMutation = useGenerate2FASetupMutation();
  const enable2FAMutation = useEnable2FAMutation();
  const enableEmail2FAMutation = useEnableEmail2FAMutation();
  const sendEmailCodeMutation = useSendSetupEmailCodeMutation();

  // Reset state when modal opens/closes
  useEffect(() => {
    if (open) {
      setStep('method');
      setSelectedMethod(null);
      setTotpData(null);
      setQrCodeDataUrl(null);
      setVerificationCode('');
      setBackupCodes([]);
      setShowBackupCodes(false);
      setCopiedCodes(false);
    }
  }, [open]);

  // Generate QR code image from provisioning URI
  useEffect(() => {
    if (totpData?.qr_code_url) {
      QRCode.toDataURL(totpData.qr_code_url, {
        width: 256,
        margin: 2,
        color: {
          dark: '#000000',
          light: '#FFFFFF'
        }
      })
        .then((dataUrl) => {
          setQrCodeDataUrl(dataUrl);
        })
        .catch((error) => {
          console.error('Failed to generate QR code:', error);
        });
    }
  }, [totpData?.qr_code_url]);

  const handleMethodSelect = async (method: 'totp' | 'email' | 'both') => {
    setSelectedMethod(method);

    if (method === 'totp' || method === 'both') {
      // Generate TOTP setup
      try {
        const data = await generateSetupMutation.mutateAsync();
        setTotpData(data);
        setStep('totp-setup');
      } catch (error) {
        console.error('Failed to generate TOTP setup:', error);
      }
    } else if (method === 'email') {
      // Send email code
      try {
        await sendEmailCodeMutation.mutateAsync();
        setStep('email-send');
      } catch (error) {
        console.error('Failed to send email code:', error);
      }
    }
  };

  const handleVerifyTOTP = async () => {
    if (!selectedMethod || !verificationCode || verificationCode.length !== 6) {
      return;
    }

    try {
      const result = await enable2FAMutation.mutateAsync({
        code: verificationCode,
        method: selectedMethod
      });
      setBackupCodes(result.backup_codes);
      setShowBackupCodes(true);
      setStep('backup-codes');
    } catch (error) {
      console.error('Failed to enable 2FA:', error);
    }
  };

  const handleVerifyEmail = async () => {
    if (!verificationCode || verificationCode.length !== 6) {
      return;
    }

    try {
      const result = await enableEmail2FAMutation.mutateAsync(verificationCode);
      setBackupCodes(result.backup_codes);
      setShowBackupCodes(true);
      setStep('backup-codes');
    } catch (error) {
      console.error('Failed to enable email 2FA:', error);
    }
  };

  const handleResendEmailCode = async () => {
    try {
      await sendEmailCodeMutation.mutateAsync();
    } catch (error) {
      console.error('Failed to resend email code:', error);
    }
  };

  const handleCopyBackupCodes = async () => {
    const codesText = backupCodes.join('\n');
    try {
      await navigator.clipboard.writeText(codesText);
      setCopiedCodes(true);
      setTimeout(() => setCopiedCodes(false), 2000);
    } catch (error) {
      console.error('Failed to copy backup codes:', error);
    }
  };

  const handleComplete = () => {
    onSuccess();
    onClose();
  };

  const getError = () => {
    return enable2FAMutation.error || enableEmail2FAMutation.error || generateSetupMutation.error || sendEmailCodeMutation.error;
  };

  return (
    <Dialog.Root open={open} onOpenChange={(open) => !open && onClose()}>
      <Dialog.Portal>
        <Dialog.Overlay className={styles.overlay} />
        <Dialog.Content className={styles.modal} aria-label="Set up Two-Factor Authentication">
          <header className={styles.header}>
            <div className={styles.headerContent}>
              <Dialog.Title className={styles.title}>Set up Two-Factor Authentication</Dialog.Title>
              <Dialog.Description className={styles.description}>
                Add an extra layer of security to your account
              </Dialog.Description>
            </div>
            <Dialog.Close asChild>
              <button
                type="button"
                className={styles.closeButton}
                aria-label="Close"
              >
                <X size={20} weight="regular" aria-hidden="true" />
              </button>
            </Dialog.Close>
          </header>

          <ScrollArea.Root className={styles.scrollArea}>
            <ScrollArea.Viewport className={styles.viewport}>
              <div className={styles.content}>
                {getError() && (
                  <div className={styles.errorBanner}>
                    <X size={16} weight="regular" aria-hidden="true" />
                    <span>{getError() instanceof Error ? getError().message : 'An error occurred'}</span>
                  </div>
                )}

                {/* Step 1: Method Selection */}
                {step === 'method' && (
                  <div className={styles.step}>
                    <h3 className={styles.stepTitle}>Choose your verification method</h3>
                    <p className={styles.stepDescription}>
                      Select how you want to verify your identity when logging in.
                    </p>

                    <div className={styles.methodOptions}>
                      <button
                        type="button"
                        className={styles.methodOption}
                        onClick={() => handleMethodSelect('totp')}
                        disabled={generateSetupMutation.isPending}
                      >
                        <div className={styles.methodIcon}>
                          <DeviceMobile size={24} weight="regular" aria-hidden="true" />
                        </div>
                        <div className={styles.methodContent}>
                          <h4>Authenticator App</h4>
                          <p>Use Google Authenticator, Authy, or similar apps</p>
                          <span className={styles.methodBadge}>Most Secure</span>
                        </div>
                        <Check size={20} weight="regular" className={styles.methodCheck} aria-hidden="true" />
                      </button>

                      <button
                        type="button"
                        className={styles.methodOption}
                        onClick={() => handleMethodSelect('email')}
                        disabled={sendEmailCodeMutation.isPending}
                      >
                        <div className={styles.methodIcon}>
                          <Envelope size={24} weight="regular" aria-hidden="true" />
                        </div>
                        <div className={styles.methodContent}>
                          <h4>Email Codes</h4>
                          <p>Receive verification codes via email</p>
                          <span className={styles.methodBadge}>Simple</span>
                        </div>
                        <Check size={20} weight="regular" className={styles.methodCheck} aria-hidden="true" />
                      </button>

                      <button
                        type="button"
                        className={styles.methodOption}
                        onClick={() => handleMethodSelect('both')}
                        disabled={generateSetupMutation.isPending}
                      >
                        <div className={styles.methodIcon}>
                          <Shield size={24} weight="regular" aria-hidden="true" />
                        </div>
                        <div className={styles.methodContent}>
                          <h4>Both Methods</h4>
                          <p>Use either authenticator app or email codes</p>
                          <span className={styles.methodBadge}>Maximum Security</span>
                        </div>
                        <Check size={20} weight="regular" className={styles.methodCheck} aria-hidden="true" />
                      </button>
                    </div>
                  </div>
                )}

                {/* Step 2: TOTP Setup (QR Code) */}
                {step === 'totp-setup' && totpData && (
                  <div className={styles.step}>
                    <h3 className={styles.stepTitle}>Scan QR Code</h3>
                    <p className={styles.stepDescription}>
                      Open your authenticator app and scan this QR code.
                    </p>

                    <div className={styles.qrCodeContainer}>
                      {qrCodeDataUrl ? (
                        <img 
                          src={qrCodeDataUrl} 
                          alt="QR Code for Two-Factor Authentication"
                          className={styles.qrCode}
                        />
                      ) : (
                        <div className={styles.qrCodePlaceholder}>
                          <CircleNotch size={32} weight="regular" className={styles.spinner} aria-hidden="true" />
                          <span>Generating QR code...</span>
                        </div>
                      )}
                    </div>

                    <p className={styles.helpText}>
                      Can't scan? Enter this code manually: <code className={styles.secretCode}>{totpData.secret}</code>
                    </p>

                    <button
                      type="button"
                      className={styles.primaryButton}
                      onClick={() => setStep('totp-verify')}
                    >
                      I've scanned the code
                    </button>
                  </div>
                )}

                {/* Step 3: TOTP Verification */}
                {step === 'totp-verify' && (
                  <div className={styles.step}>
                    <h3 className={styles.stepTitle}>Verify Setup</h3>
                    <p className={styles.stepDescription}>
                      Enter the 6-digit code from your authenticator app to confirm setup.
                    </p>

                    <div className={styles.codeInputContainer}>
                      <input
                        type="text"
                        inputMode="numeric"
                        pattern="[0-9]*"
                        maxLength={6}
                        value={verificationCode}
                        onChange={(e) => setVerificationCode(e.target.value.replace(/\D/g, '').slice(0, 6))}
                        placeholder="000000"
                        className={styles.codeInput}
                        autoFocus
                      />
                    </div>

                    <button
                      type="button"
                      className={styles.primaryButton}
                      onClick={handleVerifyTOTP}
                      disabled={verificationCode.length !== 6 || enable2FAMutation.isPending}
                    >
                      {enable2FAMutation.isPending ? (
                        <>
                          <CircleNotch size={16} weight="regular" className={styles.spinner} aria-hidden="true" />
                          Verifying...
                        </>
                      ) : (
                        'Verify and Enable'
                      )}
                    </button>

                    <button
                      type="button"
                      className={styles.secondaryButton}
                      onClick={() => setStep('totp-setup')}
                    >
                      Back
                    </button>
                  </div>
                )}

                {/* Step 4: Email Code Sent */}
                {step === 'email-send' && (
                  <div className={styles.step}>
                    <h3 className={styles.stepTitle}>Check Your Email</h3>
                    <p className={styles.stepDescription}>
                      We've sent a 6-digit verification code to <strong>{userEmail}</strong>
                    </p>

                    <div className={styles.emailSentIcon}>
                      <Envelope size={48} weight="regular" aria-hidden="true" />
                    </div>

                    <button
                      type="button"
                      className={styles.primaryButton}
                      onClick={() => setStep('email-verify')}
                    >
                      I've received the code
                    </button>

                    <button
                      type="button"
                      className={styles.secondaryButton}
                      onClick={handleResendEmailCode}
                      disabled={sendEmailCodeMutation.isPending}
                    >
                      {sendEmailCodeMutation.isPending ? 'Sending...' : 'Resend code'}
                    </button>
                  </div>
                )}

                {/* Step 5: Email Verification */}
                {step === 'email-verify' && (
                  <div className={styles.step}>
                    <h3 className={styles.stepTitle}>Enter Verification Code</h3>
                    <p className={styles.stepDescription}>
                      Enter the 6-digit code sent to {userEmail}
                    </p>

                    <div className={styles.codeInputContainer}>
                      <input
                        type="text"
                        inputMode="numeric"
                        pattern="[0-9]*"
                        maxLength={6}
                        value={verificationCode}
                        onChange={(e) => setVerificationCode(e.target.value.replace(/\D/g, '').slice(0, 6))}
                        placeholder="000000"
                        className={styles.codeInput}
                        autoFocus
                      />
                    </div>

                    <button
                      type="button"
                      className={styles.primaryButton}
                      onClick={handleVerifyEmail}
                      disabled={verificationCode.length !== 6 || enableEmail2FAMutation.isPending}
                    >
                      {enableEmail2FAMutation.isPending ? (
                        <>
                          <CircleNotch size={16} weight="regular" className={styles.spinner} aria-hidden="true" />
                          Verifying...
                        </>
                      ) : (
                        'Verify and Enable'
                      )}
                    </button>

                    <button
                      type="button"
                      className={styles.secondaryButton}
                      onClick={handleResendEmailCode}
                      disabled={sendEmailCodeMutation.isPending}
                    >
                      {sendEmailCodeMutation.isPending ? 'Sending...' : 'Resend code'}
                    </button>
                  </div>
                )}

                {/* Step 6: Backup Codes */}
                {step === 'backup-codes' && backupCodes.length > 0 && (
                  <div className={styles.step}>
                    <h3 className={styles.stepTitle}>Save Your Backup Codes</h3>
                    <p className={styles.stepDescription}>
                      These codes can be used to access your account if you lose your device. 
                      <strong> Save them now - you won't be able to see them again!</strong>
                    </p>

                    <div className={styles.backupCodesContainer}>
                      <div className={styles.backupCodesList}>
                        {backupCodes.map((code, index) => (
                          <div key={index} className={styles.backupCodeItem}>
                            {code}
                          </div>
                        ))}
                      </div>
                    </div>

                    <div className={styles.backupCodesActions}>
                      <button
                        type="button"
                        className={styles.secondaryButton}
                        onClick={handleCopyBackupCodes}
                      >
                        <Copy size={16} weight="regular" aria-hidden="true" />
                        {copiedCodes ? 'Copied!' : 'Copy All Codes'}
                      </button>
                    </div>

                    <div className={styles.warningBox}>
                      <strong>⚠️ Important:</strong> Store these codes in a safe place. Each code can only be used once.
                    </div>

                    <button
                      type="button"
                      className={styles.primaryButton}
                      onClick={handleComplete}
                    >
                      I've Saved My Codes
                    </button>
                  </div>
                )}
              </div>
            </ScrollArea.Viewport>
            <ScrollArea.Scrollbar orientation="vertical" className={styles.scrollbar}>
              <ScrollArea.Thumb className={styles.thumb} />
            </ScrollArea.Scrollbar>
          </ScrollArea.Root>
        </Dialog.Content>
      </Dialog.Portal>
    </Dialog.Root>
  );
}

