/**
 * Page Background Section
 * Settings for page background and vertical spacing only
 */

import { useState, useEffect, useRef } from 'react';
import { useQueryClient } from '@tanstack/react-query';
import { Upload, X, Images, Square, Gradient, Image, Trash, Crosshair } from '@phosphor-icons/react';
import { BackgroundColorSwatch } from '../../../controls/BackgroundColorSwatch';
import { SliderInput } from '../../ultimate-theme-modifier/SliderInput';
import { SegmentedControl } from '../../../common/SegmentedControl';
import { FocalPointPicker } from '../controls/FocalPointPicker';
import { MediaLibraryModal } from '../../../overlays/MediaLibraryModal';
import { queryKeys, normalizeImageUrl } from '../../../../api/utils';
import type { MediaItem } from '../../../../api/media';
import type { TabColorTheme } from '../../../layout/tab-colors';
import styles from './page-customization-section.module.css';


interface PageBackgroundSectionProps {
  uiState: Record<string, unknown>;
  onFieldChange: (fieldId: string, value: unknown) => void;
  activeColor: TabColorTheme;
  palette?: string[];
  onPreviewVisibilityChange?: (visible: boolean) => void;
}

export function PageBackgroundSection({
  uiState,
  onFieldChange,
  activeColor,
  palette,
  onPreviewVisibilityChange
}: PageBackgroundSectionProps): JSX.Element {
  const queryClient = useQueryClient();
  const [mediaLibraryOpen, setMediaLibraryOpen] = useState(false);
  /* Focal Point Logic: Always On */
  const hudContainerRef = useRef<HTMLDivElement>(null);
  const miniMapRef = useRef<HTMLDivElement>(null);

  const handleFocalPointInteraction = (clientX: number, clientY: number) => {
    if (!hudContainerRef.current) return;
    const rect = hudContainerRef.current.getBoundingClientRect();
    const x = Math.max(0, Math.min(100, ((clientX - rect.left) / rect.width) * 100));
    const y = Math.max(0, Math.min(100, ((clientY - rect.top) / rect.height) * 100));

    onFieldChange('page_background_image_focal_x', `${Math.round(x)}%`);
    onFieldChange('page_background_image_focal_y', `${Math.round(y)}%`);
  };

  const handleMiniMapInteraction = (clientX: number, clientY: number) => {
    if (!miniMapRef.current) return;

    const img = miniMapRef.current.querySelector('img');
    if (!img) return;

    const rect = miniMapRef.current.getBoundingClientRect();

    // Calculate the actual displayed image rectangle (letterboxed)
    const containerRatio = rect.width / rect.height;
    const imageRatio = img.naturalWidth / img.naturalHeight;

    let renderWidth = rect.width;
    let renderHeight = rect.height;
    let offsetX = 0;
    let offsetY = 0;

    if (imageRatio > containerRatio) {
      // Image is wider than container (letterbox top/bottom)
      renderHeight = rect.width / imageRatio;
      offsetY = (rect.height - renderHeight) / 2;
    } else {
      // Image is taller than container (letterbox left/right)
      renderWidth = rect.height * imageRatio;
      offsetX = (rect.width - renderWidth) / 2;
    }

    // Calculate click position relative to the rendered image
    const clientRelX = clientX - rect.left - offsetX;
    const clientRelY = clientY - rect.top - offsetY;

    const x = Math.max(0, Math.min(100, (clientRelX / renderWidth) * 100));
    const y = Math.max(0, Math.min(100, (clientRelY / renderHeight) * 100));

    onFieldChange('page_background_image_focal_x', `${Math.round(x)}%`);
    onFieldChange('page_background_image_focal_y', `${Math.round(y)}%`);
  };

  const [isDraggingFocal, setIsDraggingFocal] = useState(false);
  const [isDraggingMiniMap, setIsDraggingMiniMap] = useState(false);

  useEffect(() => {
    const handleMove = (e: MouseEvent) => {
      if (isDraggingFocal) handleFocalPointInteraction(e.clientX, e.clientY);
      if (isDraggingMiniMap) handleMiniMapInteraction(e.clientX, e.clientY);
    };
    const handleUp = () => {
      setIsDraggingFocal(false);
      setIsDraggingMiniMap(false);
    };
    const handleTouchMove = (e: TouchEvent) => {
      if (isDraggingFocal) {
        e.preventDefault();
        handleFocalPointInteraction(e.touches[0].clientX, e.touches[0].clientY);
      }
      if (isDraggingMiniMap) {
        e.preventDefault();
        handleMiniMapInteraction(e.touches[0].clientX, e.touches[0].clientY);
      }
    };

    if (isDraggingFocal || isDraggingMiniMap) {
      window.addEventListener('mousemove', handleMove);
      window.addEventListener('mouseup', handleUp);
      window.addEventListener('touchmove', handleTouchMove, { passive: false });
      window.addEventListener('touchend', handleUp);
    }
    return () => {
      window.removeEventListener('mousemove', handleMove);
      window.removeEventListener('mouseup', handleUp);
      window.removeEventListener('touchmove', handleTouchMove);
      window.removeEventListener('touchend', handleUp);
    };
  }, [isDraggingFocal, isDraggingMiniMap]);

  // Background Values
  const pageBackground = (uiState['page-background'] as string) ?? '#ffffff';
  const pageSpacing = (uiState['page-spacing'] as number) ?? 16;
  const pageBackgroundAnimate = (uiState['page-background-animate'] as boolean) ?? false;

  // Image Values
  // Image Values
  const bgImageUrl = (uiState['page_background_image_url'] as string) ?? null;
  const bgImageOverlay = (uiState['page_background_image_overlay'] as string) ?? 'rgba(0,0,0,0.4)';
  const bgImageScale = (uiState['page_background_image_scale'] as number) ?? 1;
  const bgImageBlur = (uiState['page_background_image_blur'] as string) ?? '0px';
  const bgImageFocalX = (uiState['page_background_image_focal_x'] as string) ?? '50%';
  const bgImageFocalY = (uiState['page_background_image_focal_y'] as string) ?? '50%';

  // Determine current mode
  const [mode, setMode] = useState<'solid' | 'gradient' | 'image'>('solid');

  useEffect(() => {
    let newMode: 'solid' | 'gradient' | 'image';
    // Check if background specifically asks to ignore image via CSS comment
    const ignoreImage = pageBackground && (pageBackground.includes('/* no-image */') || pageBackground.includes('no-image'));

    if (bgImageUrl && bgImageUrl !== 'none' && !ignoreImage) {
      newMode = 'image';
    } else if (pageBackground && pageBackground.includes('gradient')) {
      newMode = 'gradient';
    } else {
      newMode = 'solid';
    }
    setMode(newMode);

    // Notify parent about preview visibility (hide if image mode)
    if (onPreviewVisibilityChange) {
      onPreviewVisibilityChange(newMode !== 'image');
    }
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [bgImageUrl, pageBackground]);

  const handleModeChange = (newMode: 'solid' | 'gradient' | 'image') => {
    setMode(newMode);

    // Notify parent about preview visibility
    if (onPreviewVisibilityChange) {
      onPreviewVisibilityChange(newMode !== 'image');
    }

    if (newMode === 'solid') {
      // Append comment to tell backend to ignore any existing image URL
      onFieldChange('page-background', '#ffffff /* no-image */');
      onFieldChange('page-background-animate', false);
      // DO NOT clear image URL - preserve it
    } else if (newMode === 'gradient') {
      // Append comment to tell backend to ignore any existing image URL
      onFieldChange('page-background', 'linear-gradient(140deg, #02040d 0%, #0a1331 45%, #1a2151 100%) /* no-image */');
      // DO NOT clear image URL - preserve it
    } else if (newMode === 'image') {
      // Clean the background value (remove no-image marker) so image shows up
      const cleanBg = pageBackground ? pageBackground.replace('/* no-image */', '').replace('no-image', '').trim() : '#ffffff';
      onFieldChange('page-background', cleanBg);
    }
  };

  const handleSelectFromLibrary = (mediaItem: MediaItem) => {
    onFieldChange('page_background_image_url', mediaItem.file_url);
    // Also ensure we remove the no-image marker if we are selecting an image
    const cleanBg = pageBackground ? pageBackground.replace('/* no-image */', '').replace('no-image', '').trim() : '#ffffff';
    onFieldChange('page-background', cleanBg);
    setMediaLibraryOpen(false);
  };

  return (
    <>
      <div className={styles.section}>
        {/* Background Type Selector */}
        <div className={styles.fieldGroup}>
          <SegmentedControl
            options={[
              { value: 'solid', label: 'Solid', icon: <Square size={16} /> },
              { value: 'gradient', label: 'Gradient', icon: <Gradient size={16} /> },
              { value: 'image', label: 'Image', icon: <Image size={16} /> },
            ]}
            value={mode}
            onChange={(val) => handleModeChange(val as 'solid' | 'gradient' | 'image')}
          />
        </div>

        {mode === 'image' && (
          <div className={styles.subsection}>
            {/* HUD Container / Side-by-Side Layout */}
            {!bgImageUrl ? (
              /* No Image Placeholder (Full Width) */
              <div
                className={styles.hudContainer}
                style={{
                  width: '100%',
                  padding: '2rem',
                  borderRadius: '12px',
                  border: '2px dashed rgba(255,255,255,0.15)',
                  background: 'rgba(255,255,255,0.03)',
                  display: 'flex',
                  flexDirection: 'column',
                  alignItems: 'center',
                  justifyContent: 'center',
                  marginBottom: '1rem'
                }}
              >
                <div style={{ marginBottom: '1rem', color: 'rgba(255,255,255,0.4)', display: 'flex', justifyContent: 'center' }}>
                  <Images size={48} weight="thin" />
                </div>
                <button
                  type="button"
                  onClick={() => setMediaLibraryOpen(true)}
                  style={{
                    padding: '0.75rem 1.5rem',
                    background: 'rgba(255,255,255,0.1)',
                    border: '1px solid rgba(255,255,255,0.1)',
                    borderRadius: '8px',
                    color: '#fff',
                    cursor: 'pointer',
                    fontSize: '0.95rem',
                    fontWeight: '500',
                    transition: 'all 0.2s ease'
                  }}
                >
                  Choose Image
                </button>
                <div style={{ marginTop: '0.75rem', fontSize: '0.85rem', color: 'rgba(255,255,255,0.4)' }}>
                  JPG, PNG, WebP up to 5MB
                </div>
              </div>
            ) : (
              /* Image Mode: Side-by-Side Layout */
              <div style={{ display: 'flex', gap: '1rem', alignItems: 'flex-start', marginBottom: '1rem' }}>

                {/* Left Column: Image Preview (50%) */}
                <div
                  ref={hudContainerRef}
                  className={styles.hudContainer}
                  style={{
                    flex: 1,
                    aspectRatio: '9/20',
                    borderRadius: '12px',
                    overflow: 'hidden',
                    position: 'relative',
                    background: '#000',
                    border: 'none',
                    display: 'flex',
                    flexDirection: 'column',
                    justifyContent: 'center',
                    alignItems: 'stretch'
                  }}
                >
                  {/* Background Layer */}
                  <div style={{ position: 'absolute', inset: 0, zIndex: 0, overflow: 'hidden' }}>
                    <img
                      src={normalizeImageUrl(bgImageUrl)}
                      alt="Background"
                      style={{
                        width: '100%',
                        height: '100%',
                        objectFit: 'cover',
                        objectPosition: `${bgImageFocalX} ${bgImageFocalY}`, // Use Object Position for alignment
                        transform: `scale(${bgImageScale})`,
                        filter: `blur(${bgImageBlur})`,
                        transformOrigin: `${bgImageFocalX} ${bgImageFocalY}`, // Keep transformOrigin for zoom behavior
                        transition: 'transform 0.1s ease, filter 0.1s ease, object-position 0.1s ease'
                      }}
                    />
                    {/* Visual Overlay Layer */}
                    <div
                      style={{
                        position: 'absolute',
                        inset: 0,
                        backgroundColor: bgImageOverlay,
                        transition: 'background-color 0.1s ease'
                      }}
                    />
                  </div>

                  {/* Focal Point Interaction Overlay (Always Active) */}
                  <div
                    style={{
                      position: 'absolute',
                      inset: 0,
                      zIndex: 20,
                      cursor: 'crosshair',
                    }}
                    onMouseDown={(e) => {
                      setIsDraggingFocal(true);
                      handleFocalPointInteraction(e.clientX, e.clientY);
                    }}
                    onTouchStart={(e) => {
                      setIsDraggingFocal(true);
                      handleFocalPointInteraction(e.touches[0].clientX, e.touches[0].clientY);
                    }}
                  >
                    {/* Grid helpers (Only visible on hover or dragging?) Let's keep them subtle */}
                    <div style={{ position: 'absolute', left: '50%', top: 0, bottom: 0, width: '1px', background: 'rgba(255,255,255,0.1)', pointerEvents: 'none' }} />
                    <div style={{ position: 'absolute', top: '50%', left: 0, right: 0, height: '1px', background: 'rgba(255,255,255,0.1)', pointerEvents: 'none' }} />

                    {/* Focal Point Handle */}
                    <div
                      style={{
                        position: 'absolute',
                        left: bgImageFocalX,
                        top: bgImageFocalY,
                        width: '20px',
                        height: '20px',
                        transform: 'translate(-50%, -50%)',
                        border: '2px solid rgba(255,255,255,0.8)',
                        borderRadius: '50%',
                        boxShadow: '0 0 4px rgba(0,0,0,0.5)',
                        pointerEvents: 'none',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center'
                      }}
                    >
                      <div style={{ width: '4px', height: '4px', background: '#fff', borderRadius: '50%' }} />
                    </div>

                    {/* Coordinate Label */}
                    <div
                      style={{
                        position: 'absolute',
                        bottom: '10px',
                        right: '10px',
                        background: 'rgba(0,0,0,0.6)',
                        padding: '4px 8px',
                        borderRadius: '4px',
                        color: '#fff',
                        fontSize: '0.75rem',
                        pointerEvents: 'none'
                      }}
                    >
                      {Math.round(parseFloat(bgImageFocalX))}% {Math.round(parseFloat(bgImageFocalY))}%
                    </div>

                    {/* Mini-Map Overlay ("The Squeeze") */}
                    <div
                      ref={miniMapRef}
                      style={{
                        position: 'absolute',
                        bottom: '8px',
                        left: '8px',
                        width: '80px',
                        height: '60px',
                        backgroundColor: 'rgba(0,0,0,0.8)',
                        border: '1px solid rgba(255,255,255,0.3)',
                        borderRadius: '4px',
                        zIndex: 30, // Higher than main overlay
                        cursor: 'crosshair',
                        overflow: 'hidden',
                        pointerEvents: 'auto', // Re-enable pointer events
                        boxShadow: '0 2px 8px rgba(0,0,0,0.5)'
                      }}
                      onMouseDown={(e) => {
                        e.stopPropagation(); // Stop bubbling to main HUD
                        setIsDraggingMiniMap(true);
                        handleMiniMapInteraction(e.clientX, e.clientY);
                      }}
                      onTouchStart={(e) => {
                        e.stopPropagation();
                        setIsDraggingMiniMap(true);
                        handleMiniMapInteraction(e.touches[0].clientX, e.touches[0].clientY);
                      }}
                    >
                      {/* Full Image Preview */}
                      <img
                        src={normalizeImageUrl(bgImageUrl)}
                        alt="Mini Map"
                        draggable={false}
                        style={{
                          width: '100%',
                          height: '100%',
                          objectFit: 'contain',
                          opacity: 0.8,
                          userSelect: 'none',
                          pointerEvents: 'none'
                        }}
                      />

                      {/* Focal Point Dot on Mini-Map */}
                      <div
                        style={{
                          position: 'absolute',
                          left: bgImageFocalX,
                          top: bgImageFocalY,
                          width: '8px',
                          height: '8px',
                          backgroundColor: '#3C82F6',
                          border: '1px solid #fff',
                          borderRadius: '50%',
                          transform: 'translate(-50%, -50%)',
                          pointerEvents: 'none'
                        }}
                      />
                    </div>
                  </div>
                </div>

                {/* Right Column: Controls (50%) */}
                <div style={{ flex: 1, display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>

                  {/* Actions Row */}
                  <div style={{ display: 'flex', gap: '0.5rem', marginBottom: '0.25rem' }}>
                    <button
                      type="button"
                      onClick={() => setMediaLibraryOpen(true)}
                      style={{
                        flex: 1,
                        height: '32px',
                        borderRadius: '6px',
                        background: 'rgba(255,255,255,0.1)',
                        border: '1px solid rgba(255,255,255,0.1)',
                        color: '#fff',
                        cursor: 'pointer',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        gap: '6px',
                        fontSize: '0.85rem',
                        transition: 'all 0.2s ease'
                      }}
                      title="Replace Image"
                    >
                      <Images size={16} />
                      <span>Replace</span>
                    </button>
                    <button
                      type="button"
                      onClick={() => onFieldChange('page_background_image_url', null)}
                      style={{
                        width: '32px',
                        height: '32px',
                        borderRadius: '6px',
                        background: 'rgba(255,77,77,0.15)',
                        border: '1px solid rgba(255,77,77,0.2)',
                        color: '#ffcccc',
                        cursor: 'pointer',
                        display: 'flex',
                        alignItems: 'center',
                        justifyContent: 'center',
                        transition: 'all 0.2s ease'
                      }}
                      title="Remove Image"
                    >
                      <Trash size={16} />
                    </button>
                  </div>

                  {/* Overlay Section */}
                  <div className={styles.fieldGroup} style={{ marginBottom: 0 }}>
                    <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'space-between', marginBottom: '4px' }}>
                      <label className={styles.label}>Overlay Color</label>
                      <BackgroundColorSwatch
                        value={(() => {
                          const rgbaMatch = bgImageOverlay.match(/rgba\(([\d\s]+),([\d\s]+),([\d\s]+),([\d\s.]+)\)/);
                          if (rgbaMatch) {
                            const [r, g, b] = [rgbaMatch[1], rgbaMatch[2], rgbaMatch[3]].map(s => parseInt(s.trim()));
                            const hex = "#" + ((1 << 24) + (r << 16) + (g << 8) + b).toString(16).slice(1);
                            return hex;
                          }
                          return '#000000';
                        })()}
                        onChange={(color) => {
                          const rgbaMatch = bgImageOverlay.match(/rgba\(([\d\s]+),([\d\s]+),([\d\s]+),([\d\s.]+)\)/);
                          const currentOpacity = rgbaMatch ? parseFloat(rgbaMatch[4]) : 0.4;
                          const r = parseInt(color.slice(1, 3), 16);
                          const g = parseInt(color.slice(3, 5), 16);
                          const b = parseInt(color.slice(5, 7), 16);
                          onFieldChange('page_background_image_overlay', `rgba(${r},${g},${b},${currentOpacity})`);
                        }}
                        label="Overlay Color"
                        palette={palette}
                      />
                    </div>
                    <SliderInput
                      value={(() => {
                        const rgbaMatch = bgImageOverlay.match(/rgba\(([\d\s]+),([\d\s]+),([\d\s]+),([\d\s.]+)\)/);
                        return rgbaMatch ? parseFloat(rgbaMatch[4]) : 0.4;
                      })()}
                      min={0}
                      max={1}
                      step={0.05}
                      onChange={(opacity) => {
                        const rgbaMatch = bgImageOverlay.match(/rgba\(([\d\s]+),([\d\s]+),([\d\s]+),([\d\s.]+)\)/);
                        const currentRgb = rgbaMatch ? `${rgbaMatch[1]},${rgbaMatch[2]},${rgbaMatch[3]}` : '0,0,0';
                        onFieldChange('page_background_image_overlay', `rgba(${currentRgb},${opacity})`);
                      }}
                    />
                  </div>

                  {/* Scale Section */}
                  <div className={styles.fieldGroup} style={{ marginBottom: 0 }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '4px' }}>
                      <label className={styles.label}>Scale</label>
                    </div>
                    <SliderInput
                      value={bgImageScale}
                      min={1}
                      max={2}
                      step={0.1}
                      onChange={(val: number) => onFieldChange('page_background_image_scale', val)}
                    />
                  </div>

                  {/* Blur Section */}
                  <div className={styles.fieldGroup} style={{ marginBottom: 0 }}>
                    <div style={{ display: 'flex', justifyContent: 'space-between', marginBottom: '4px' }}>
                      <label className={styles.label}>Blur</label>
                    </div>
                    <SliderInput
                      value={parseInt(bgImageBlur) || 0}
                      min={0}
                      max={20}
                      step={1}
                      unit="px"
                      onChange={(val) => onFieldChange('page_background_image_blur', `${val}px`)}
                    />
                  </div>
                </div>
              </div>
            )}
          </div>
        )}

        {(mode === 'solid' || mode === 'gradient') && (
          <div className={styles.fieldGroup}>
            <label className={styles.label}>Page Background</label>
            <BackgroundColorSwatch
              value={pageBackground ? pageBackground.replace('/* no-image */', '').replace('no-image', '').trim() : pageBackground}
              backgroundType={mode === 'gradient' ? 'gradient' : 'solid'}
              onChange={(value) => {
                // Always append marker in this mode to ensure image remains hidden if present
                const finalValue = `${value} /* no-image */`;
                onFieldChange('page-background', finalValue);
              }}
              solidOnly={mode === 'solid'}
              label="Page background"
              palette={palette}
            />
          </div>
        )}

        {/* Gradient Animation Toggle */}
        {mode === 'gradient' && (
          <div className={styles.fieldGroup}>
            <label className={styles.toggleRow}>
              <span className={styles.label}>Animate Gradient</span>
              <label className={styles.toggleSwitch}>
                <input
                  type="checkbox"
                  checked={pageBackgroundAnimate}
                  onChange={(e) => onFieldChange('page-background-animate', e.target.checked)}
                />
                <span className={styles.toggleSlider} />
              </label>
            </label>
          </div>
        )}
      </div>

      {/* Separate Spacing Section */}
      <div className={styles.section} style={{ marginTop: '1rem' }}>
        <h3 className={styles.sectionTitle}>Page Layout</h3>
        <div className={styles.controlsContainer}>
          <div className={styles.fieldGroup}>
            <label className={styles.label}>Vertical Spacing</label>
            <p className={styles.description}>
              Adjust the space between widgets.
            </p>
            <SliderInput
              value={pageSpacing}
              min={8}
              max={48}
              step={2}
              unit="px"
              onChange={(value) => onFieldChange('page-spacing', value)}
            />
          </div>
        </div>
      </div>

      <MediaLibraryModal
        open={mediaLibraryOpen}
        onClose={() => setMediaLibraryOpen(false)}
        onSelect={handleSelectFromLibrary}
      />
    </>
  );
}
