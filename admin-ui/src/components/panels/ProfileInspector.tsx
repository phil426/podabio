import { useEffect, useMemo, useRef, useState } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { LuBold, LuItalic, LuUnderline, LuAlignLeft, LuAlignCenter, LuAlignRight, LuUpload, LuX } from 'react-icons/lu';

import { usePageSnapshot, updatePageSettings, removeProfileImage } from '../../api/page';
import { uploadProfileImage } from '../../api/uploads';
import { queryKeys, normalizeImageUrl } from '../../api/utils';

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
  const maxNameLength = 30;

  // Derive a shared text size preset for simple S / M / L controls
  const [textSizePreset, setTextSizePreset] = useState<'small' | 'medium' | 'large'>('medium');

  useEffect(() => {
    // Map existing name/bio sizes into a single preset
    if (nameTextSize === 'large' && bioTextSize === 'small') {
      setTextSizePreset('small');
    } else if (nameTextSize === 'xxlarge' && bioTextSize === 'large') {
      setTextSizePreset('large');
    } else {
      setTextSizePreset('medium');
    }
  }, [nameTextSize, bioTextSize]);

  const previewName = name.trim() || page?.podcast_name || 'Your show name';
  const previewBio = bio.trim() || page?.podcast_description || 'Give listeners a one-line reason to follow your show.';

  const previewInitials = useMemo(() => {
    const source = previewName.replace(/<[^>]*>/g, '');
    const words = source.split(/\s+/).filter(Boolean);
    if (!words.length) return 'PB';
    if (words.length === 1) return words[0].slice(0, 2).toUpperCase();
    return (words[0][0] + words[1][0]).toUpperCase();
  }, [previewName]);

  // Decode HTML entities in text
  const decodeHtmlEntities = (text: string): string => {
    const textarea = document.createElement('textarea');
    textarea.innerHTML = text;
    return textarea.value;
  };

  useEffect(() => {
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

  const handleAlignmentChange = (alignment: 'left' | 'center' | 'right') => {
    setNameAlignment(alignment);
    setBioAlignment(alignment);
  };

  const handleTextSizePresetChange = (preset: 'small' | 'medium' | 'large') => {
    setTextSizePreset(preset);
    if (preset === 'small') {
      setNameTextSize('large');
      setBioTextSize('small');
    } else if (preset === 'large') {
      setNameTextSize('xxlarge');
      setBioTextSize('large');
    } else {
      setNameTextSize('xlarge');
      setBioTextSize('medium');
    }
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
          <p>Preview and fine‑tune how your avatar, name, and bio appear on your page.</p>
        </div>
      </header>

      {/* Compact live preview */}
      <div className={styles.previewCard} aria-label="Profile preview">
        <div className={styles.previewAvatar}>
          {profileImage ? (
            <img src={normalizeImageUrl(profileImage)} alt="" aria-hidden="true" />
          ) : (
            <span aria-hidden="true">{previewInitials}</span>
          )}
        </div>
        <div className={styles.previewText}>
          <p className={styles.previewName}>{previewName.replace(/<[^>]*>/g, '')}</p>
          <p className={styles.previewBio}>{previewBio.replace(/<[^>]*>/g, '')}</p>
        </div>
      </div>

      <div className={styles.fieldset}>
        {/* Layout Section */}
        <div className={styles.layoutSection}>
          <div className={styles.layoutHeader}>
            <span>Layout</span>
          </div>
          <div className={styles.layoutRow} aria-label="Text alignment" role="radiogroup">
            <button
              type="button"
              className={`${styles.layoutChip} ${nameAlignment === 'left' ? styles.layoutChipActive : ''}`}
              onClick={() => handleAlignmentChange('left')}
              role="radio"
              aria-checked={nameAlignment === 'left'}
            >
              <LuAlignLeft aria-hidden="true" />
              <span>Left</span>
            </button>
            <button
              type="button"
              className={`${styles.layoutChip} ${nameAlignment === 'center' ? styles.layoutChipActive : ''}`}
              onClick={() => handleAlignmentChange('center')}
              role="radio"
              aria-checked={nameAlignment === 'center'}
            >
              <LuAlignCenter aria-hidden="true" />
              <span>Center</span>
            </button>
            <button
              type="button"
              className={`${styles.layoutChip} ${nameAlignment === 'right' ? styles.layoutChipActive : ''}`}
              onClick={() => handleAlignmentChange('right')}
              role="radio"
              aria-checked={nameAlignment === 'right'}
            >
              <LuAlignRight aria-hidden="true" />
              <span>Right</span>
            </button>
          </div>
          <div className={styles.layoutRow} aria-label="Text size" role="radiogroup">
            <button
              type="button"
              className={`${styles.layoutChip} ${textSizePreset === 'small' ? styles.layoutChipActive : ''}`}
              onClick={() => handleTextSizePresetChange('small')}
              role="radio"
              aria-checked={textSizePreset === 'small'}
            >
              <span className={styles.sizeLabel}>S</span>
              <span>Compact</span>
            </button>
            <button
              type="button"
              className={`${styles.layoutChip} ${textSizePreset === 'medium' ? styles.layoutChipActive : ''}`}
              onClick={() => handleTextSizePresetChange('medium')}
              role="radio"
              aria-checked={textSizePreset === 'medium'}
            >
              <span className={styles.sizeLabel}>M</span>
              <span>Balanced</span>
            </button>
            <button
              type="button"
              className={`${styles.layoutChip} ${textSizePreset === 'large' ? styles.layoutChipActive : ''}`}
              onClick={() => handleTextSizePresetChange('large')}
              role="radio"
              aria-checked={textSizePreset === 'large'}
            >
              <span className={styles.sizeLabel}>L</span>
              <span>Hero</span>
            </button>
          </div>
        </div>
      </div>

      <div className={styles.fieldset}>
        {/* Profile Image Section */}
        <div
          className={styles.imagePreview}
          data-shape={imageShape}
          data-shadow={imageShadow}
          data-size={imageSize}
          data-border={imageBorder}
          data-has-image={profileImage ? 'true' : 'false'}
        >
          {profileImage ? <img src={normalizeImageUrl(profileImage)} alt="Current profile" /> : <span>PB</span>}
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
        <div className={styles.imageOptionsCompact}>
          <div className={styles.layoutSection}>
            <div className={styles.layoutHeader}>Avatar</div>
            <div className={styles.layoutRow} aria-label="Avatar shape" role="radiogroup">
              <button
                type="button"
                className={`${styles.layoutChip} ${imageShape === 'circle' ? styles.layoutChipActive : ''}`}
                onClick={() => setImageShape('circle')}
                role="radio"
                aria-checked={imageShape === 'circle'}
              >
                <div className={styles.shapePreview} data-shape="circle" />
                <span>Circle</span>
              </button>
              <button
                type="button"
                className={`${styles.layoutChip} ${imageShape === 'rounded' ? styles.layoutChipActive : ''}`}
                onClick={() => setImageShape('rounded')}
                role="radio"
                aria-checked={imageShape === 'rounded'}
              >
                <div className={styles.shapePreview} data-shape="rounded" />
                <span>Rounded</span>
              </button>
              <button
                type="button"
                className={`${styles.layoutChip} ${imageShape === 'square' ? styles.layoutChipActive : ''}`}
                onClick={() => setImageShape('square')}
                role="radio"
                aria-checked={imageShape === 'square'}
              >
                <div className={styles.shapePreview} data-shape="square" />
                <span>Square</span>
              </button>
            </div>
            <div className={styles.layoutRow} aria-label="Avatar size" role="radiogroup">
              <button
                type="button"
                className={`${styles.layoutChip} ${imageSize === 'small' ? styles.layoutChipActive : ''}`}
                onClick={() => setImageSize('small')}
                role="radio"
                aria-checked={imageSize === 'small'}
              >
                <span className={styles.sizeLabel}>S</span>
                <span>Small</span>
              </button>
              <button
                type="button"
                className={`${styles.layoutChip} ${imageSize === 'medium' ? styles.layoutChipActive : ''}`}
                onClick={() => setImageSize('medium')}
                role="radio"
                aria-checked={imageSize === 'medium'}
              >
                <span className={styles.sizeLabel}>M</span>
                <span>Medium</span>
              </button>
              <button
                type="button"
                className={`${styles.layoutChip} ${imageSize === 'large' ? styles.layoutChipActive : ''}`}
                onClick={() => setImageSize('large')}
                role="radio"
                aria-checked={imageSize === 'large'}
              >
                <span className={styles.sizeLabel}>L</span>
                <span>Large</span>
              </button>
            </div>
            <div className={styles.layoutRow} aria-label="Avatar frame style" role="radiogroup">
              <button
                type="button"
                className={`${styles.layoutChip} ${imageBorder === 'none' && imageShadow === 'none' ? styles.layoutChipActive : ''}`}
                onClick={() => {
                  setImageBorder('none');
                  setImageShadow('none');
                }}
                role="radio"
                aria-checked={imageBorder === 'none' && imageShadow === 'none'}
              >
                <div className={styles.borderPreview} data-border="none" data-shape={imageShape} />
                <span>Minimal</span>
              </button>
              <button
                type="button"
                className={`${styles.layoutChip} ${imageBorder === 'thin' && imageShadow !== 'strong' ? styles.layoutChipActive : ''}`}
                onClick={() => {
                  setImageBorder('thin');
                  setImageShadow('subtle');
                }}
                role="radio"
                aria-checked={imageBorder === 'thin' && imageShadow !== 'strong'}
              >
                <div className={styles.borderPreview} data-border="thin" data-shape={imageShape} />
                <span>Soft frame</span>
              </button>
              <button
                type="button"
                className={`${styles.layoutChip} ${imageBorder === 'thick' || imageShadow === 'strong' ? styles.layoutChipActive : ''}`}
                onClick={() => {
                  setImageBorder('thick');
                  setImageShadow('strong');
                }}
                role="radio"
                aria-checked={imageBorder === 'thick' || imageShadow === 'strong'}
              >
                <div className={styles.borderPreview} data-border="thick" data-shape={imageShape} />
                <span>Bold frame</span>
              </button>
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
      </div>

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

      <div className={styles.footer}>
        <button type="button" className={styles.saveButton} onClick={handleSaveProfile} disabled={isSavingProfile}>
          {isSavingProfile ? 'Saving…' : 'Save changes'}
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
