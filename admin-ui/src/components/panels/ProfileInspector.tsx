import { useEffect, useMemo, useRef, useState } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { TextB, TextItalic, TextUnderline, CheckCircle, CircleNotch } from '@phosphor-icons/react';

import { usePageSnapshot, updatePageSettings } from '../../api/page';
import { queryKeys } from '../../api/utils';
import { useDebouncedCallback } from './themes/hooks/useDebouncedCallback';

import { type TabColorTheme } from '../layout/tab-colors';

import styles from './profile-inspector.module.css';

interface ProfileInspectorProps {
  focus: 'image' | 'bio' | 'profile';
  activeColor: TabColorTheme;
}

export function ProfileInspector({ focus, activeColor }: ProfileInspectorProps): JSX.Element {
  const { data: snapshot } = usePageSnapshot();
  const queryClient = useQueryClient();
  const page = snapshot?.page;

  const [name, setName] = useState(page?.podcast_name ?? '');
  const [nameAlignment, setNameAlignment] = useState<'left' | 'center' | 'right'>(page?.name_alignment ?? 'center');
  const [nameTextSize, setNameTextSize] = useState<'large' | 'xlarge' | 'xxlarge'>(page?.name_text_size ?? 'large');
  const [bio, setBio] = useState(page?.podcast_description ?? '');
  const [imageShape, setImageShape] = useState<'circle' | 'rounded' | 'square'>(page?.profile_image_shape ?? 'circle');
  const [imageShadow, setImageShadow] = useState<'none' | 'subtle' | 'strong'>(page?.profile_image_shadow ?? 'subtle');
  const [imageSize, setImageSize] = useState<'small' | 'medium' | 'large'>(page?.profile_image_size ?? 'medium');
  const [imageBorder, setImageBorder] = useState<'none' | 'thin' | 'thick'>(page?.profile_image_border ?? 'none');
  const [bioAlignment, setBioAlignment] = useState<'left' | 'center' | 'right'>(page?.bio_alignment ?? 'center');
  const [bioTextSize, setBioTextSize] = useState<'small' | 'medium' | 'large'>(page?.bio_text_size ?? 'medium');
  const [footerText, setFooterText] = useState(page?.footer_text ?? '');
  const [footerCopyright, setFooterCopyright] = useState(page?.footer_copyright ?? '');
  const [footerPrivacyLink, setFooterPrivacyLink] = useState(page?.footer_privacy_link ?? '');
  const [footerTermsLink, setFooterTermsLink] = useState(page?.footer_terms_link ?? '');
  const [footerVisible, setFooterVisible] = useState(page?.footer_visible !== false);

  const [saveStatus, setSaveStatus] = useState<'saved' | 'saving' | 'error' | 'idle'>('idle');
  const [statusMessage, setStatusMessage] = useState<string | null>(null);

  const nameTextareaRef = useRef<HTMLTextAreaElement | null>(null);
  const bioTextareaRef = useRef<HTMLTextAreaElement | null>(null);
  const isInitialMount = useRef(true);

  const nameTextLength = useMemo(() => name.replace(/<[^>]*>/g, '').length, [name]);
  const bioTextLength = useMemo(() => bio.replace(/<[^>]*>/g, '').length, [bio]);
  const footerTextLength = useMemo(() => footerText.replace(/<[^>]*>/g, '').length, [footerText]);
  const maxBioLength = 150;
  const maxNameLength = 30;
  const maxFooterLength = 200;

  // Decode HTML entities in text
  const decodeHtmlEntities = (text: string): string => {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = text;
    return textarea.value;
  };

  useEffect(() => {
    // Only update state from props if we're not currently saving to avoid overwriting user input
    if (saveStatus === 'saving') return;

    setName(page?.podcast_name ?? '');
    setNameAlignment(page?.name_alignment ?? 'center');
    setNameTextSize(page?.name_text_size ?? 'large');
    const rawBio = page?.podcast_description ?? '';
    setBio(decodeHtmlEntities(rawBio));
    setImageShape(page?.profile_image_shape ?? 'circle');
    setImageShadow(page?.profile_image_shadow ?? 'subtle');
    setImageSize(page?.profile_image_size ?? 'medium');
    setImageBorder(page?.profile_image_border ?? 'none');
    setBioAlignment(page?.bio_alignment ?? 'center');
    setBioTextSize(page?.bio_text_size ?? 'medium');
    setFooterText(page?.footer_text ?? '');
    setFooterCopyright(page?.footer_copyright ?? '');
    setFooterPrivacyLink(page?.footer_privacy_link ?? '');
    setFooterTermsLink(page?.footer_terms_link ?? '');
    setFooterVisible(page?.footer_visible !== false);
  }, [page?.podcast_name, page?.name_alignment, page?.name_text_size, page?.podcast_description, page?.profile_image_shape, page?.profile_image_shadow, page?.profile_image_size, page?.profile_image_border, page?.bio_alignment, page?.bio_text_size, page?.footer_text, page?.footer_copyright, page?.footer_privacy_link, page?.footer_terms_link, page?.footer_visible, saveStatus]);

  useEffect(() => {
    if (saveStatus === 'saved' || saveStatus === 'error') {
      const timer = window.setTimeout(() => {
        setSaveStatus('idle');
        setStatusMessage(null);
      }, 3000);
      return () => window.clearTimeout(timer);
    }
  }, [saveStatus]);

  const performSave = async () => {
    if (!page) return;

    // Check character limits for profile
    const nameTextOnly = name.replace(/<[^>]*>/g, '');
    const bioTextOnly = bio.replace(/<[^>]*>/g, '');
    const footerTextOnly = footerText.replace(/<[^>]*>/g, '');

    if (nameTextOnly.length > maxNameLength || bioTextOnly.length > maxBioLength || footerTextOnly.length > maxFooterLength) {
      setSaveStatus('error');
      setStatusMessage('Character limit exceeded');
      return;
    }

    // Validate URLs if provided
    if (footerPrivacyLink && !footerPrivacyLink.match(/^https?:\/\//)) {
      setSaveStatus('error');
      setStatusMessage('Privacy Policy link must be a valid URL');
      return;
    }

    if (footerTermsLink && !footerTermsLink.match(/^https?:\/\//)) {
      setSaveStatus('error');
      setStatusMessage('Terms of Service link must be a valid URL');
      return;
    }

    try {
      setSaveStatus('saving');
      const response = await updatePageSettings({
        podcast_name: name,
        name_alignment: nameAlignment,
        name_text_size: nameTextSize,
        podcast_description: bio,
        profile_image_shape: imageShape,
        profile_image_shadow: imageShadow,
        profile_image_size: imageSize,
        profile_image_border: imageBorder,
        bio_alignment: bioAlignment,
        bio_text_size: bioTextSize,
        footer_text: footerText,
        footer_copyright: footerCopyright,
        footer_privacy_link: footerPrivacyLink,
        footer_terms_link: footerTermsLink,
        footer_visible: footerVisible ? '1' : '0'
      });

      if (!response.success) {
        throw new Error(response.error || 'Failed to save');
      }

      await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
      setSaveStatus('saved');
      setStatusMessage('Changes saved');
    } catch (error) {
      setSaveStatus('error');
      setStatusMessage(error instanceof Error ? error.message : 'Error saving changes');
    }
  };

  const debouncedSave = useDebouncedCallback(performSave, 1000);

  // Autosave trigger
  useEffect(() => {
    if (isInitialMount.current) {
      isInitialMount.current = false;
      return;
    }

    // Check if values actually changed from props to avoid redundant saves/loops
    // We check against the decoded/normalized values of the props
    const hasChanges =
      name !== (page?.podcast_name ?? '') ||
      nameAlignment !== (page?.name_alignment ?? 'center') ||
      nameTextSize !== (page?.name_text_size ?? 'large') ||
      bio !== decodeHtmlEntities(page?.podcast_description ?? '') ||
      imageShape !== (page?.profile_image_shape ?? 'circle') ||
      imageShadow !== (page?.profile_image_shadow ?? 'subtle') ||
      imageSize !== (page?.profile_image_size ?? 'medium') ||
      imageBorder !== (page?.profile_image_border ?? 'none') ||
      bioAlignment !== (page?.bio_alignment ?? 'center') ||
      bioTextSize !== (page?.bio_text_size ?? 'medium') ||
      footerText !== (page?.footer_text ?? '') ||
      footerCopyright !== (page?.footer_copyright ?? '') ||
      footerPrivacyLink !== (page?.footer_privacy_link ?? '') ||
      footerTermsLink !== (page?.footer_terms_link ?? '') ||
      footerVisible !== (page?.footer_visible !== false);

    if (hasChanges) {
      if (saveStatus !== 'saving') {
        setStatusMessage('Saving...');
        // We don't set 'saving' state here because 'saving' blocks inputs in our first useEffect.
        // We just rely on debouncedSave eventually calling performSave which sets 'saving'.
        // But maybe we want a visual indicator "Unsaved" or "Pending"?
        // For now, let's just let it be silent until save starts, or 'saving' when performSave runs.
      }
      debouncedSave();
    }
  }, [
    name, bio, nameAlignment, nameTextSize, imageShape, imageShadow, imageSize, imageBorder, bioAlignment, bioTextSize, footerText, footerCopyright, footerPrivacyLink, footerTermsLink, footerVisible,
    page, debouncedSave, saveStatus
  ]);

  const handleFormatName = (format: 'bold' | 'italic' | 'underline') => {
    const textarea = nameTextareaRef.current;
    if (!textarea) return;

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = name.substring(start, end);

    if (!selectedText) return;

    const tags: Record<string, { open: string; close: string }> = {
      bold: { open: '<strong>', close: '</strong>' },
      italic: { open: '<em>', close: '</em>' },
      underline: { open: '<u>', close: '</u>' }
    };

    const formattedText = `${tags[format].open}${selectedText}${tags[format].close}`;
    const newName = name.substring(0, start) + formattedText + name.substring(end);

    // Check character limit (count only text, not HTML tags)
    const textOnly = newName.replace(/<[^>]*>/g, '');
    if (textOnly.length > maxNameLength) {
      setSaveStatus('error');
      setStatusMessage(`Name cannot exceed ${maxNameLength} characters.`);
      return;
    }

    setName(newName);

    setTimeout(() => {
      textarea.focus();
      const newCursorPos = start + tags[format].open.length + selectedText.length;
      textarea.setSelectionRange(newCursorPos, newCursorPos);
    }, 0);
  };

  const handleFormatBio = (format: 'bold' | 'italic' | 'underline') => {
    const textarea = bioTextareaRef.current;
    if (!textarea) return;

    const start = textarea.selectionStart;
    const end = textarea.selectionEnd;
    const selectedText = bio.substring(start, end);

    if (!selectedText) return;

    const tags: Record<string, { open: string; close: string }> = {
      bold: { open: '<strong>', close: '</strong>' },
      italic: { open: '<em>', close: '</em>' },
      underline: { open: '<u>', close: '</u>' }
    };

    const formattedText = `${tags[format].open}${selectedText}${tags[format].close}`;
    const newBio = bio.substring(0, start) + formattedText + bio.substring(end);

    const textOnly = newBio.replace(/<[^>]*>/g, '');
    if (textOnly.length > maxBioLength) {
      setSaveStatus('error');
      setStatusMessage(`Bio cannot exceed ${maxBioLength} characters.`);
      return;
    }

    setBio(newBio);

    setTimeout(() => {
      textarea.focus();
      const newCursorPos = start + tags[format].open.length + selectedText.length;
      textarea.setSelectionRange(newCursorPos, newCursorPos);
    }, 0);
  };

  return (
    <section
      className={styles.wrapper}
      aria-label="Profile settings"
      data-active={focus === 'image' || focus === 'bio' || focus === 'profile'}
      style={{
        '--active-tab-color': activeColor.text,
        '--active-tab-bg': activeColor.primary,
        '--active-tab-light': activeColor.light,
        '--active-tab-border': activeColor.border
      } as React.CSSProperties}
    >
      <header className={styles.header}>
        <div>
          <h3>Profile</h3>
          <p>Preview and fine‑tune how your name and bio appear on your page.</p>
        </div>
      </header>

      <div className={styles.fieldset}>
        {/* Name Section */}
        <div className={styles.nameSection}>
          <div className={styles.nameHeader}>
            <label htmlFor="name-text">Name</label>
            <span className={styles.charCounter} data-warning={nameTextLength > maxNameLength * 0.9}>
              {nameTextLength} / {maxNameLength}
            </span>
          </div>
          <div className={styles.nameEditor}>
            <textarea
              ref={nameTextareaRef}
              id="name-text"
              value={name}
              onChange={(event) => {
                const textOnly = event.target.value.replace(/<[^>]*>/g, '');
                if (textOnly.length <= maxNameLength) {
                  setName(event.target.value);
                }
              }}
              rows={2}
              placeholder="Enter your name or podcast title"
              maxLength={maxNameLength + 100}
            />
          </div>
        </div>
      </div>

      <div className={styles.fieldset}>
        {/* Bio Section */}
        <div className={styles.bioSection}>
          <div className={styles.bioHeader}>
            <label htmlFor="bio-text">Short bio</label>
            <span className={styles.charCounter} data-warning={bioTextLength > maxBioLength * 0.9}>
              {bioTextLength} / {maxBioLength}
            </span>
          </div>
          <div className={styles.bioEditor}>
            <textarea
              ref={bioTextareaRef}
              id="bio-text"
              value={bio}
              onChange={(event) => {
                const textOnly = event.target.value.replace(/<[^>]*>/g, '');
                if (textOnly.length <= maxBioLength) {
                  setBio(event.target.value);
                }
              }}
              rows={3}
              placeholder="Tell listeners what to expect from your show."
              maxLength={maxBioLength + 100} // Allow HTML tags
            />
          </div>
        </div>
      </div>

      {/* Footer Section */}
      <div className={styles.fieldset}>
        <div className={styles.footerSection}>
          <div className={styles.footerHeader}>
            <label htmlFor="footer-text">Footer Text</label>
            <span className={styles.charCounter} data-warning={footerTextLength > maxFooterLength * 0.9}>
              {footerTextLength} / {maxFooterLength}
            </span>
          </div>
          <div className={styles.footerEditor}>
            <textarea
              id="footer-text"
              value={footerText}
              onChange={(event) => {
                const textOnly = event.target.value.replace(/<[^>]*>/g, '');
                if (textOnly.length <= maxFooterLength) {
                  setFooterText(event.target.value);
                }
              }}
              rows={3}
              placeholder="Enter footer text (e.g., copyright notice, disclaimer, etc.)"
              maxLength={maxFooterLength + 100}
            />
          </div>
        </div>
      </div>

      <div className={styles.fieldset}>
        <div className={styles.footerFields}>
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
        <div style={{ display: 'flex', alignItems: 'center', gap: '8px', opacity: 0.9, fontSize: '0.9rem', fontWeight: 500 }}>
          {saveStatus === 'saving' && (
            <>
              <CircleNotch size={16} weight="bold" className="icon-spin" /> {/* Assuming global icon-spin or similar animation exists, or static for now */}
              <span>Saving...</span>
            </>
          )}
          {saveStatus === 'saved' && (
            <>
              <CheckCircle size={16} weight="fill" color="#22c55e" />
              <span style={{ color: '#22c55e' }}>Saved</span>
            </>
          )}
          {saveStatus === 'error' && (
            <span style={{ color: '#ef4444' }}>{statusMessage || 'Error saving'}</span>
          )}
        </div>
      </div>
    </section>
  );
}
