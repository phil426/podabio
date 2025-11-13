import { useEffect, useMemo, useRef, useState } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { LuBold, LuItalic, LuUnderline, LuAlignLeft, LuAlignCenter, LuAlignRight, LuUpload, LuX } from 'react-icons/lu';

import { usePageSnapshot, updatePageSettings, removeProfileImage } from '../../api/page';
import { uploadProfileImage } from '../../api/uploads';
import { queryKeys } from '../../api/utils';

import { type TabColorTheme } from '../layout/tab-colors';

import styles from './profile-inspector.module.css';

interface ProfileInspectorProps {
  focus: 'image' | 'bio' | 'profile' | 'footer';
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
  const [status, setStatus] = useState<string | null>(null);
  const [statusTone, setStatusTone] = useState<'success' | 'error'>('success');
  const [isSavingProfile, setSavingProfile] = useState(false);
  const [isUploading, setUploading] = useState(false);
  const fileInputRef = useRef<HTMLInputElement | null>(null);
  const nameTextareaRef = useRef<HTMLTextAreaElement | null>(null);
  const bioTextareaRef = useRef<HTMLTextAreaElement | null>(null);

  const profileImage = page?.profile_image ?? null;
  const nameTextLength = useMemo(() => name.replace(/<[^>]*>/g, '').length, [name]);
  const bioTextLength = useMemo(() => bio.replace(/<[^>]*>/g, '').length, [bio]);
  const maxBioLength = 150;
  const maxNameLength = 100;

  useEffect(() => {
    setName(page?.podcast_name ?? '');
    setNameAlignment(page?.name_alignment ?? 'center');
    setNameTextSize(page?.name_text_size ?? 'large');
    setBio(page?.podcast_description ?? '');
    setImageShape(page?.profile_image_shape ?? 'circle');
    setImageShadow(page?.profile_image_shadow ?? 'subtle');
    setImageSize(page?.profile_image_size ?? 'medium');
    setImageBorder(page?.profile_image_border ?? 'none');
    setBioAlignment(page?.bio_alignment ?? 'center');
    setBioTextSize(page?.bio_text_size ?? 'medium');
  }, [page?.podcast_name, page?.name_alignment, page?.name_text_size, page?.podcast_description, page?.profile_image_shape, page?.profile_image_shadow, page?.profile_image_size, page?.profile_image_border, page?.bio_alignment, page?.bio_text_size]);

  useEffect(() => {
    if (!status) return;
    const timer = window.setTimeout(() => setStatus(null), 3500);
    return () => window.clearTimeout(timer);
  }, [status]);

  const handleChooseFile = () => {
    fileInputRef.current?.click();
  };

  const handleFileChange = async (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;

    try {
      setUploading(true);
      await uploadProfileImage(file);
      await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
      setStatusTone('success');
      setStatus('Profile image updated.');
    } catch (error) {
      setStatusTone('error');
      setStatus(error instanceof Error ? error.message : 'Upload failed. Please try again.');
    } finally {
      setUploading(false);
      if (fileInputRef.current) {
        fileInputRef.current.value = '';
      }
    }
  };

  const handleRemoveImage = async () => {
    try {
      setUploading(true);
      await removeProfileImage();
      await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
      setStatusTone('success');
      setStatus('Profile image removed.');
    } catch (error) {
      setStatusTone('error');
      setStatus(error instanceof Error ? error.message : 'Unable to remove profile image.');
    } finally {
      setUploading(false);
    }
  };

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
      setStatusTone('error');
      setStatus(`Name cannot exceed ${maxNameLength} characters.`);
      return;
    }

    setName(newName);
    
    // Restore cursor position
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
    
    // Check character limit (count only text, not HTML tags)
    const textOnly = newBio.replace(/<[^>]*>/g, '');
    if (textOnly.length > maxBioLength) {
      setStatusTone('error');
      setStatus(`Bio cannot exceed ${maxBioLength} characters.`);
      return;
    }

    setBio(newBio);
    
    // Restore cursor position
    setTimeout(() => {
      textarea.focus();
      const newCursorPos = start + tags[format].open.length + selectedText.length;
      textarea.setSelectionRange(newCursorPos, newCursorPos);
    }, 0);
  };

  const handleSaveProfile = async () => {
    if (!page) return;
    
    // Check character limits
    const nameTextOnly = name.replace(/<[^>]*>/g, '');
    const bioTextOnly = bio.replace(/<[^>]*>/g, '');
    if (nameTextOnly.length > maxNameLength) {
      setStatusTone('error');
      setStatus(`Name cannot exceed ${maxNameLength} characters.`);
      return;
    }
    if (bioTextOnly.length > maxBioLength) {
      setStatusTone('error');
      setStatus(`Bio cannot exceed ${maxBioLength} characters.`);
      return;
    }

    try {
      setSavingProfile(true);
      await updatePageSettings({
        podcast_name: name,
        name_alignment: nameAlignment,
        name_text_size: nameTextSize,
        podcast_description: bio,
        profile_image_shape: imageShape,
        profile_image_shadow: imageShadow,
        profile_image_size: imageSize,
        profile_image_border: imageBorder,
        bio_alignment: bioAlignment,
        bio_text_size: bioTextSize
      });
      await queryClient.invalidateQueries({ queryKey: queryKeys.pageSnapshot() });
      setStatusTone('success');
      setStatus('Profile updated.');
    } catch (error) {
      setStatusTone('error');
      setStatus(error instanceof Error ? error.message : 'Unable to save profile.');
    } finally {
      setSavingProfile(false);
    }
  };


  return (
    <section 
      className={styles.wrapper} 
      aria-label="Profile settings"
      style={{ 
        '--active-tab-color': activeColor.text,
        '--active-tab-bg': activeColor.primary,
        '--active-tab-light': activeColor.light,
        '--active-tab-border': activeColor.border
      } as React.CSSProperties}
    >
      <div className={styles.section} data-active={focus === 'image' || focus === 'bio' || focus === 'profile'}>
        <header className={styles.sectionHeader}>
          <h3>Profile</h3>
          <p>Manage your profile image and bio.</p>
        </header>

        {/* Profile Image Section */}
        <div className={styles.profileImageSection}>
          <div className={styles.imagePreviewWrapper}>
            <div
              className={styles.imagePreview}
              data-shape={imageShape}
              data-shadow={imageShadow}
              data-size={imageSize}
              data-border={imageBorder}
              data-has-image={profileImage ? 'true' : 'false'}
            >
              {profileImage ? <img src={profileImage} alt="Current profile" /> : <span>PB</span>}
              <div className={styles.imageOverlay}>
                <button
                  type="button"
                  className={styles.imageActionButton}
                  onClick={handleChooseFile}
                  disabled={isUploading}
                  title={isUploading ? 'Uploading…' : profileImage ? 'Replace image' : 'Upload image'}
                >
                  <LuUpload aria-hidden="true" />
                </button>
                {profileImage && (
                  <button
                    type="button"
                    className={styles.imageActionButton}
                    onClick={handleRemoveImage}
                    disabled={isUploading}
                    title="Remove image"
                  >
                    <LuX aria-hidden="true" />
                  </button>
                )}
              </div>
            </div>
          </div>
          <div className={styles.imageControls}>
            <div className={styles.imageOptionsCompact}>
              <div className={styles.optionGroup}>
                <span className={styles.optionGroupLabel}>Shape</span>
                <div className={styles.optionButtons}>
                  <button
                    type="button"
                    className={`${styles.optionButton} ${imageShape === 'circle' ? styles.optionButtonActive : ''}`}
                    onClick={() => setImageShape('circle')}
                    title="Circle"
                  >
                    <div className={styles.shapePreview} data-shape="circle" />
                  </button>
                  <button
                    type="button"
                    className={`${styles.optionButton} ${imageShape === 'rounded' ? styles.optionButtonActive : ''}`}
                    onClick={() => setImageShape('rounded')}
                    title="Rounded"
                  >
                    <div className={styles.shapePreview} data-shape="rounded" />
                  </button>
                  <button
                    type="button"
                    className={`${styles.optionButton} ${imageShape === 'square' ? styles.optionButtonActive : ''}`}
                    onClick={() => setImageShape('square')}
                    title="Square"
                  >
                    <div className={styles.shapePreview} data-shape="square" />
                  </button>
                </div>
              </div>
              <div className={styles.optionGroup}>
                <span className={styles.optionGroupLabel}>Shadow</span>
                <div className={styles.optionButtons}>
                  <button
                    type="button"
                    className={`${styles.optionButton} ${imageShadow === 'none' ? styles.optionButtonActive : ''}`}
                    onClick={() => setImageShadow('none')}
                    title="None"
                  >
                    <div className={styles.shadowPreview} data-shadow="none" />
                  </button>
                  <button
                    type="button"
                    className={`${styles.optionButton} ${imageShadow === 'subtle' ? styles.optionButtonActive : ''}`}
                    onClick={() => setImageShadow('subtle')}
                    title="Subtle"
                  >
                    <div className={styles.shadowPreview} data-shadow="subtle" />
                  </button>
                  <button
                    type="button"
                    className={`${styles.optionButton} ${imageShadow === 'strong' ? styles.optionButtonActive : ''}`}
                    onClick={() => setImageShadow('strong')}
                    title="Strong"
                  >
                    <div className={styles.shadowPreview} data-shadow="strong" />
                  </button>
                </div>
              </div>
              <div className={styles.optionGroup}>
                <span className={styles.optionGroupLabel}>Size</span>
                <div className={styles.optionButtons}>
                  <button
                    type="button"
                    className={`${styles.optionButton} ${imageSize === 'small' ? styles.optionButtonActive : ''}`}
                    onClick={() => setImageSize('small')}
                    title="Small"
                  >
                    <span className={styles.sizeLabel}>S</span>
                  </button>
                  <button
                    type="button"
                    className={`${styles.optionButton} ${imageSize === 'medium' ? styles.optionButtonActive : ''}`}
                    onClick={() => setImageSize('medium')}
                    title="Medium"
                  >
                    <span className={styles.sizeLabel}>M</span>
                  </button>
                  <button
                    type="button"
                    className={`${styles.optionButton} ${imageSize === 'large' ? styles.optionButtonActive : ''}`}
                    onClick={() => setImageSize('large')}
                    title="Large"
                  >
                    <span className={styles.sizeLabel}>L</span>
                  </button>
                </div>
              </div>
              <div className={styles.optionGroup}>
                <span className={styles.optionGroupLabel}>Border</span>
                <div className={styles.optionButtons}>
                  <button
                    type="button"
                    className={`${styles.optionButton} ${imageBorder === 'none' ? styles.optionButtonActive : ''}`}
                    onClick={() => setImageBorder('none')}
                    title="None"
                  >
                    <div className={styles.borderPreview} data-border="none" />
                  </button>
                  <button
                    type="button"
                    className={`${styles.optionButton} ${imageBorder === 'thin' ? styles.optionButtonActive : ''}`}
                    onClick={() => setImageBorder('thin')}
                    title="Thin"
                  >
                    <div className={styles.borderPreview} data-border="thin" />
                  </button>
                  <button
                    type="button"
                    className={`${styles.optionButton} ${imageBorder === 'thick' ? styles.optionButtonActive : ''}`}
                    onClick={() => setImageBorder('thick')}
                    title="Thick"
                  >
                    <div className={styles.borderPreview} data-border="thick" />
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
        <input
          ref={fileInputRef}
          type="file"
          accept="image/png,image/jpeg,image/webp"
          className={styles.hiddenInput}
          onChange={handleFileChange}
        />

        {/* Name Section */}
        <div className={styles.nameSection}>
          <div className={styles.nameHeader}>
            <label htmlFor="name-text">Name</label>
            <span className={styles.charCounter} data-warning={nameTextLength > maxNameLength * 0.9}>
              {nameTextLength} / {maxNameLength}
            </span>
          </div>
          <div className={styles.nameEditor}>
            <div className={styles.formatToolbar}>
              <button
                type="button"
                className={styles.formatButton}
                onClick={() => handleFormatName('bold')}
                title="Bold"
              >
                <LuBold aria-hidden="true" />
              </button>
              <button
                type="button"
                className={styles.formatButton}
                onClick={() => handleFormatName('italic')}
                title="Italic"
              >
                <LuItalic aria-hidden="true" />
              </button>
              <button
                type="button"
                className={styles.formatButton}
                onClick={() => handleFormatName('underline')}
                title="Underline"
              >
                <LuUnderline aria-hidden="true" />
              </button>
              <div className={styles.formatDivider} />
              <button
                type="button"
                className={`${styles.formatButton} ${nameAlignment === 'left' ? styles.formatButtonActive : ''}`}
                onClick={() => setNameAlignment('left')}
                title="Align left"
              >
                <LuAlignLeft aria-hidden="true" />
              </button>
              <button
                type="button"
                className={`${styles.formatButton} ${nameAlignment === 'center' ? styles.formatButtonActive : ''}`}
                onClick={() => setNameAlignment('center')}
                title="Align center"
              >
                <LuAlignCenter aria-hidden="true" />
              </button>
              <button
                type="button"
                className={`${styles.formatButton} ${nameAlignment === 'right' ? styles.formatButtonActive : ''}`}
                onClick={() => setNameAlignment('right')}
                title="Align right"
              >
                <LuAlignRight aria-hidden="true" />
              </button>
              <div className={styles.formatDivider} />
              <div className={styles.textSizeGroup}>
                <button
                  type="button"
                  className={`${styles.textSizeButton} ${nameTextSize === 'large' ? styles.textSizeButtonActive : ''}`}
                  onClick={() => setNameTextSize('large')}
                  title="Large"
                >
                  <span className={styles.textSizeLabel}>L</span>
                </button>
                <button
                  type="button"
                  className={`${styles.textSizeButton} ${nameTextSize === 'xlarge' ? styles.textSizeButtonActive : ''}`}
                  onClick={() => setNameTextSize('xlarge')}
                  title="Extra Large"
                >
                  <span className={styles.textSizeLabel}>XL</span>
                </button>
                <button
                  type="button"
                  className={`${styles.textSizeButton} ${nameTextSize === 'xxlarge' ? styles.textSizeButtonActive : ''}`}
                  onClick={() => setNameTextSize('xxlarge')}
                  title="2X Large"
                >
                  <span className={styles.textSizeLabel}>2XL</span>
                </button>
              </div>
            </div>
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

        {/* Bio Section */}
        <div className={styles.bioSection}>
          <div className={styles.bioHeader}>
            <label htmlFor="bio-text">Short bio</label>
            <span className={styles.charCounter} data-warning={bioTextLength > maxBioLength * 0.9}>
              {bioTextLength} / {maxBioLength}
            </span>
          </div>
          <div className={styles.bioEditor}>
            <div className={styles.formatToolbar}>
              <button
                type="button"
                className={styles.formatButton}
                onClick={() => handleFormatBio('bold')}
                title="Bold"
              >
                <LuBold aria-hidden="true" />
              </button>
              <button
                type="button"
                className={styles.formatButton}
                onClick={() => handleFormatBio('italic')}
                title="Italic"
              >
                <LuItalic aria-hidden="true" />
              </button>
              <button
                type="button"
                className={styles.formatButton}
                onClick={() => handleFormatBio('underline')}
                title="Underline"
              >
                <LuUnderline aria-hidden="true" />
              </button>
              <div className={styles.formatDivider} />
              <button
                type="button"
                className={`${styles.formatButton} ${bioAlignment === 'left' ? styles.formatButtonActive : ''}`}
                onClick={() => setBioAlignment('left')}
                title="Align left"
              >
                <LuAlignLeft aria-hidden="true" />
              </button>
              <button
                type="button"
                className={`${styles.formatButton} ${bioAlignment === 'center' ? styles.formatButtonActive : ''}`}
                onClick={() => setBioAlignment('center')}
                title="Align center"
              >
                <LuAlignCenter aria-hidden="true" />
              </button>
              <button
                type="button"
                className={`${styles.formatButton} ${bioAlignment === 'right' ? styles.formatButtonActive : ''}`}
                onClick={() => setBioAlignment('right')}
                title="Align right"
              >
                <LuAlignRight aria-hidden="true" />
              </button>
              <div className={styles.formatDivider} />
              <div className={styles.textSizeGroup}>
                <button
                  type="button"
                  className={`${styles.textSizeButton} ${bioTextSize === 'small' ? styles.textSizeButtonActive : ''}`}
                  onClick={() => setBioTextSize('small')}
                  title="Small"
                >
                  <span className={styles.textSizeLabel}>S</span>
                </button>
                <button
                  type="button"
                  className={`${styles.textSizeButton} ${bioTextSize === 'medium' ? styles.textSizeButtonActive : ''}`}
                  onClick={() => setBioTextSize('medium')}
                  title="Medium"
                >
                  <span className={styles.textSizeLabel}>M</span>
                </button>
                <button
                  type="button"
                  className={`${styles.textSizeButton} ${bioTextSize === 'large' ? styles.textSizeButtonActive : ''}`}
                  onClick={() => setBioTextSize('large')}
                  title="Large"
                >
                  <span className={styles.textSizeLabel}>L</span>
                </button>
              </div>
            </div>
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
          <div className={styles.bioActions}>
            <button type="button" onClick={handleSaveProfile} disabled={isSavingProfile}>
              {isSavingProfile ? 'Saving…' : 'Save profile'}
            </button>
          </div>
        </div>
        <div className={styles.profileNote}>
          <p>Fonts are set in Themes</p>
        </div>
      </div>

      {status && (
        <p className={styles[`status_${statusTone}`]} role="status">
          {status}
        </p>
      )}
    </section>
  );
}
