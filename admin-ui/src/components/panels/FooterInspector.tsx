import { useEffect, useState, useRef, useMemo } from 'react';
import { useQueryClient } from '@tanstack/react-query';

import { usePageSnapshot, updatePageSettings } from '../../api/page';
import { queryKeys } from '../../api/utils';
import { type TabColorTheme } from '../layout/tab-colors';

import styles from './footer-inspector.module.css';

interface FooterInspectorProps {
  activeColor: TabColorTheme;
}

export function FooterInspector({ activeColor }: FooterInspectorProps): JSX.Element {
  const { data: snapshot } = usePageSnapshot();
  const queryClient = useQueryClient();
  const page = snapshot?.page;

  const [footerText, setFooterText] = useState(page?.footer_text ?? '');
  const [footerCopyright, setFooterCopyright] = useState(page?.footer_copyright ?? '');
  const [footerPrivacyLink, setFooterPrivacyLink] = useState(page?.footer_privacy_link ?? '');
  const [footerTermsLink, setFooterTermsLink] = useState(page?.footer_terms_link ?? '');
  const [footerVisible, setFooterVisible] = useState(page?.footer_visible !== false);
  const [status, setStatus] = useState<string | null>(null);
  const [statusTone, setStatusTone] = useState<'success' | 'error'>('success');
  const [isSaving, setIsSaving] = useState(false);
  const footerTextareaRef = useRef<HTMLTextAreaElement | null>(null);

  const footerTextLength = useMemo(() => footerText.replace(/<[^>]*>/g, '').length, [footerText]);
  const maxFooterLength = 200;

  useEffect(() => {
    setFooterText(page?.footer_text ?? '');
    setFooterCopyright(page?.footer_copyright ?? '');
    setFooterPrivacyLink(page?.footer_privacy_link ?? '');
    setFooterTermsLink(page?.footer_terms_link ?? '');
    setFooterVisible(page?.footer_visible !== false);
  }, [page?.footer_text, page?.footer_copyright, page?.footer_privacy_link, page?.footer_terms_link, page?.footer_visible]);

  useEffect(() => {
    if (!status) return;
    const timer = window.setTimeout(() => setStatus(null), 3500);
    return () => window.clearTimeout(timer);
  }, [status]);

  const handleSave = async () => {
    if (!page) return;

    // Validate URLs if provided
    if (footerPrivacyLink && !footerPrivacyLink.match(/^https?:\/\//)) {
      setStatusTone('error');
      setStatus('Privacy Policy link must be a valid URL (starting with http:// or https://)');
      return;
    }

    if (footerTermsLink && !footerTermsLink.match(/^https?:\/\//)) {
      setStatusTone('error');
      setStatus('Terms of Service link must be a valid URL (starting with http:// or https://)');
      return;
    }

    try {
      setIsSaving(true);
      const payload = {
        footer_text: footerText || '',
        footer_copyright: footerCopyright || '',
        footer_privacy_link: footerPrivacyLink || '',
        footer_terms_link: footerTermsLink || '',
        footer_visible: footerVisible ? '1' : '0'
      };
      console.log('[FooterInspector] Saving footer data:', payload);
      const response = await updatePageSettings(payload);
      console.log('[FooterInspector] Save response:', response);
      
      // Check if the response indicates success
      if (response && typeof response === 'object' && 'success' in response) {
        if (!response.success) {
          const errorMsg = 'error' in response && response.error 
            ? String(response.error) 
            : 'Failed to save footer. Please try again.';
          throw new Error(errorMsg);
        }
      }
      
      await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
      setStatusTone('success');
      setStatus('Footer updated.');
    } catch (error) {
      console.error('[FooterInspector] Error saving footer:', error);
      setStatusTone('error');
      setStatus(error instanceof Error ? error.message : 'Unable to save footer.');
    } finally {
      setIsSaving(false);
    }
  };

  return (
    <section 
      className={styles.wrapper} 
      aria-label="Footer settings"
      style={{ 
        '--active-tab-color': activeColor.text,
        '--active-tab-bg': activeColor.primary,
        '--active-tab-light': activeColor.light,
        '--active-tab-border': activeColor.border
      } as React.CSSProperties}
    >
      <header className={styles.header}>
        <div>
          <h3>Footer</h3>
          <p>Update the note shown at the bottom of your page.</p>
        </div>
      </header>

      <div className={styles.fieldset}>
        <div className={styles.footerFields}>
          <div className={styles.fieldGroup}>
            <div className={styles.fieldHeader}>
              <label htmlFor="footer-text">Footer Text</label>
              <span className={styles.charCounter} data-warning={footerTextLength > maxFooterLength * 0.9}>
                {footerTextLength} / {maxFooterLength}
              </span>
            </div>
            <textarea
              ref={footerTextareaRef}
              id="footer-text"
              value={footerText}
              onChange={(event) => {
                const textOnly = event.target.value.replace(/<[^>]*>/g, '');
                if (textOnly.length <= maxFooterLength) {
                  setFooterText(event.target.value);
                }
              }}
              rows={4}
              placeholder="Enter footer text (e.g., copyright notice, disclaimer, etc.)"
              maxLength={maxFooterLength + 100}
            />
          </div>

          <div className={styles.fieldGroup}>
            <label htmlFor="footer-copyright">Copyright Text</label>
            <input
              type="text"
              id="footer-copyright"
              value={footerCopyright}
              onChange={(event) => setFooterCopyright(event.target.value)}
              placeholder="e.g., © 2024 Your Name"
              maxLength={100}
            />
          </div>

          <div className={styles.fieldGroup}>
            <label htmlFor="footer-privacy-link">Privacy Policy Link</label>
            <input
              type="url"
              id="footer-privacy-link"
              value={footerPrivacyLink}
              onChange={(event) => setFooterPrivacyLink(event.target.value)}
              placeholder="https://example.com/privacy"
            />
          </div>

          <div className={styles.fieldGroup}>
            <label htmlFor="footer-terms-link">Terms of Service Link</label>
            <input
              type="url"
              id="footer-terms-link"
              value={footerTermsLink}
              onChange={(event) => setFooterTermsLink(event.target.value)}
              placeholder="https://example.com/terms"
            />
          </div>

          <div className={styles.fieldGroup}>
            <label className={styles.checkboxLabel}>
              <input
                type="checkbox"
                checked={footerVisible}
                onChange={(event) => setFooterVisible(event.target.checked)}
              />
              <span>Show footer on page</span>
            </label>
          </div>
        </div>
      </div>

      <div className={styles.footer}>
        <button type="button" className={styles.saveButton} onClick={handleSave} disabled={isSaving}>
          {isSaving ? 'Saving…' : 'Save changes'}
        </button>
        {status && (
          <span className={statusTone === 'success' ? styles.statusOk : styles.statusError} role="status">
            {status}
          </span>
        )}
      </div>
    </section>
  );
}

