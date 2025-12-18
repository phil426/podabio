import { useEffect, useMemo, useRef, useState } from 'react';
import { X, Check, CheckSquare, Square, Images, Plus, Trash, CircleNotch, CheckCircle } from '@phosphor-icons/react';

import { useAvailableWidgetsQuery, useUpdateWidgetMutation } from '../../api/widgets';
import { usePageSnapshot } from '../../api/page';
import type { WidgetRecord } from '../../api/types';
import { useWidgetSelection } from '../../state/widgetSelection';
import { uploadWidgetThumbnail } from '../../api/uploads';
import { getYouTubeThumbnail } from '../../utils/media';
import { normalizeImageUrl } from '../../api/utils';
import { MediaLibraryModal } from '../overlays/MediaLibraryModal';
import type { MediaItem } from '../../api/media';
import { useMediaLibraryQuery } from '../../api/media';
import { useDebouncedCallback } from './themes/hooks/useDebouncedCallback';

import { type TabColorTheme } from '../layout/tab-colors';

import styles from './widget-inspector.module.css';

type ConfigValue = string | number | boolean | string[] | null | undefined;

interface WidgetFormState {
  title: string;
  isActive: boolean;
  config: Record<string, ConfigValue>;
}

interface WidgetInspectorProps {
  activeColor: TabColorTheme;
  /** Optional widget ID to use instead of global selection (for modal use) */
  widgetId?: string | null;
}

export function WidgetInspector({ activeColor, widgetId: widgetIdProp }: WidgetInspectorProps): JSX.Element {
  const { data: snapshot, isLoading } = usePageSnapshot();
  const { data: availableWidgets } = useAvailableWidgetsQuery();
  const selectedWidgetIdFromState = useWidgetSelection((state) => state.selectedWidgetId);
  const selectWidget = useWidgetSelection((state) => state.selectWidget);

  // Use prop if provided, otherwise fall back to global selection
  const selectedWidgetId = widgetIdProp !== undefined ? widgetIdProp : selectedWidgetIdFromState;
  const { mutateAsync: updateWidget, isPending: isSaving } = useUpdateWidgetMutation();

  const selectedWidget = useMemo<WidgetRecord | undefined>(() => {
    if (!selectedWidgetId || !snapshot?.widgets) return undefined;
    return snapshot.widgets.find((widget) => String(widget.id) === selectedWidgetId);
  }, [selectedWidgetId, snapshot?.widgets]);

  const widgetDefinition = useMemo(() => {
    if (!selectedWidget || !availableWidgets) return undefined;
    const widgetType = selectedWidget.widget_type;
    const found = availableWidgets.find((item) => {
      const key = (item.widget_id ?? item.type ?? item.name ?? '').toString();
      return key === widgetType;
    });

    // Debug: Log if widget not found
    if (!found && widgetType) {
      console.warn('Widget not found in registry:', {
        widgetType,
        availableWidgetIds: availableWidgets.map((w) => w.widget_id ?? w.type ?? w.name)
      });
    }

    return found;
  }, [availableWidgets, selectedWidget]);

  const [formState, setFormState] = useState<WidgetFormState | null>(null);
  const [saveStatus, setSaveStatus] = useState<'idle' | 'saving' | 'saved' | 'error'>('idle');
  const [thumbnailError, setThumbnailError] = useState<string | null>(null);
  const [isUploadingThumbnail, setUploadingThumbnail] = useState(false);
  const [mediaLibraryOpen, setMediaLibraryOpen] = useState(false);
  const widgetType =
    selectedWidget?.widget_type ?? (typeof widgetDefinition?.widget_id === 'string' ? widgetDefinition?.widget_id : '');

  const performAutoSave = async (state: WidgetFormState) => {
    if (!selectedWidget) return;

    try {
      await updateWidget({
        widget_id: String(selectedWidget.id),
        title: state.title,
        is_active: state.isActive ? '1' : '0',
        config_data: JSON.stringify(state.config ?? {})
      });
      setSaveStatus('saved');
    } catch (error) {
      console.error('Failed to update widget', error);
      setSaveStatus('error');
    }
  };

  const debouncedSave = useDebouncedCallback((state: WidgetFormState) => {
    performAutoSave(state);
  }, 1000);


  useEffect(() => {
    if (!selectedWidget) {
      setFormState(null);
      return;
    }

    const normalizedConfig = normalizeConfig(selectedWidget.config_data);

    setFormState({
      title: selectedWidget.title,
      isActive: selectedWidget.is_active === 1,
      config: normalizedConfig
    });
    setSaveStatus('idle');
  }, [selectedWidget]);

  const configFields = widgetDefinition?.config_fields ?? {};
  const videoUrlConfig = (formState?.config?.video_url as string | undefined) ?? undefined;
  const storedThumbnail = (formState?.config?.thumbnail_image as string | undefined) ?? undefined;

  useEffect(() => {
    if (widgetType !== 'youtube_video') return;
    const derived = getYouTubeThumbnail(videoUrlConfig ?? '');
    if (!derived || storedThumbnail === derived) return;

    setFormState((prev) => {
      if (!prev) return prev;
      if (prev.config?.thumbnail_image === derived) {
        return prev;
      }
      const newState = {
        ...prev,
        config: {
          ...prev.config,
          thumbnail_image: derived
        }
      };
      // Trigger auto-save for thumbnail update
      debouncedSave(newState);
      return newState;
    });
  }, [widgetType, videoUrlConfig, storedThumbnail]);

  const hasChanges = useMemo(() => {
    if (!formState || !selectedWidget) return false;

    const initialConfig = normalizeConfig(selectedWidget.config_data);

    const sameTitle = formState.title === selectedWidget.title;
    const sameActive = formState.isActive === (selectedWidget.is_active === 1);
    const sameConfig = JSON.stringify(formState.config ?? {}) === JSON.stringify(initialConfig ?? {});

    return !(sameTitle && sameActive && sameConfig);
  }, [formState, selectedWidget]);

  if (isLoading) {
    return (
      <section className={styles.wrapper} aria-label="Widget inspector">
        <p>Loading widget data…</p>
      </section>
    );
  }

  if (!selectedWidget) {
    if (selectedWidgetId) {
      return (
        <section className={styles.wrapper} aria-label="Widget inspector">
          <p className={styles.placeholder}>Loading widget details...</p>
        </section>
      );
    }
    return (
      <section className={styles.wrapper} aria-label="Widget inspector">
        <p className={styles.placeholder}>Select a block to edit its settings.</p>
      </section>
    );
  }

  if (!widgetDefinition) {
    return (
      <section className={styles.wrapper} aria-label="Widget inspector">
        <header className={styles.header}>
          <h3>{selectedWidget.title}</h3>
          <button type="button" onClick={() => selectWidget(null)} className={styles.closeButton}>
            Clear
          </button>
        </header>
        <p className={styles.error}>
          This widget type isn’t available in the registry yet. You can still rename or toggle it from the layers panel.
        </p>
      </section>
    );
  }



  const handleInputChange = (field: string, value: ConfigValue) => {
    setFormState((prev) => {
      if (!prev) return prev;
      const newState = {
        ...prev,
        config: {
          ...prev.config,
          [field]: value
        }
      };
      setSaveStatus('saving');
      debouncedSave(newState);
      return newState;
    });
  };

  const handleTitleChange = (value: string) => {
    setFormState((prev) => {
      if (!prev) return prev;
      const newState = {
        ...prev,
        title: value
      };
      setSaveStatus('saving');
      debouncedSave(newState);
      return newState;
    });
  };

  const handleActiveToggle = (checked: boolean) => {
    setFormState((prev) => {
      if (!prev) return prev;
      const newState = {
        ...prev,
        isActive: checked
      };
      setSaveStatus('saving');
      debouncedSave(newState);
      return newState;
    });
  };


  const handleReset = () => {
    if (!selectedWidget) return;

    // Cancel any pending auto-save
    debouncedSave.cancel();

    const normalizedConfig = normalizeConfig(selectedWidget.config_data);

    setFormState({
      title: selectedWidget.title,
      isActive: selectedWidget.is_active === 1,
      config: normalizedConfig
    });
    setSaveStatus('idle');
  };

  const currentThumbnail =
    typeof formState?.config?.thumbnail_image === 'string'
      ? (formState.config.thumbnail_image as string)
      : '';
  const resolvedThumbnail =
    currentThumbnail ||
    (widgetType === 'youtube_video' ? getYouTubeThumbnail(videoUrlConfig ?? '') ?? '' : '');


  const handleSelectThumbnailFromLibrary = async (mediaItem: MediaItem) => {
    if (!selectedWidget) return;

    try {
      setUploadingThumbnail(true);
      // Update form state immediately
      setFormState((prev) => {
        if (!prev) return prev;
        return {
          ...prev,
          config: {
            ...prev.config,
            thumbnail_image: mediaItem.file_url
          }
        };
      });
      setMediaLibraryOpen(false);

      // Save immediately
      await updateWidget({
        widget_id: String(selectedWidget.id),
        title: formState?.title ?? selectedWidget.title,
        is_active: (formState?.isActive ?? selectedWidget.is_active === 1) ? '1' : '0',
        config_data: JSON.stringify({
          ...(formState?.config ?? normalizeConfig(selectedWidget.config_data)),
          thumbnail_image: mediaItem.file_url
        })
      });

      setSaveStatus('saved');
    } catch (error) {
      setThumbnailError(error instanceof Error ? error.message : 'Unable to update thumbnail.');
      setSaveStatus('error');
    } finally {
      setUploadingThumbnail(false);
    }
  };

  const handleThumbnailRemove = async () => {
    // Update local state
    setFormState((prev) => {
      if (!prev) return prev;
      return {
        ...prev,
        config: {
          ...prev.config,
          thumbnail_image: ''
        }
      };
    });
    setThumbnailError(null);

    // Auto-save immediately
    if (formState && selectedWidget) {
      try {
        await updateWidget({
          widget_id: String(selectedWidget.id),
          title: formState.title,
          is_active: formState.isActive ? '1' : '0',
          config_data: JSON.stringify({
            ...formState.config,
            thumbnail_image: ''
          })
        });
        setSaveStatus('saved');
      } catch (error) {
        setSaveStatus('error');
      }
    }
  };



  return (
    <section
      className={styles.wrapper}
      aria-label="Widget inspector"
      style={{
        '--active-tab-color': activeColor.text,
        '--active-tab-bg': activeColor.primary,
        '--active-tab-light': activeColor.light,
        '--active-tab-border': activeColor.border
      } as React.CSSProperties}
    >
      <header className={styles.header}>
        <div>
          <h3>{widgetDefinition.name ?? selectedWidget.title}</h3>
          <p>{widgetDefinition.description}</p>
        </div>
        <button type="button" onClick={() => selectWidget(null)} className={styles.closeButton}>
          Clear
        </button>
      </header>

      <div className={styles.controlGroup}>
        <label className={styles.control}>
          <span>Title</span>
          <input
            className={styles.input}
            type="text"
            value={formState?.title ?? ''}
            onChange={(event) => handleTitleChange(event.target.value)}
          />
        </label>
      </div>

      <div className={styles.fieldset}>
        {Object.entries(configFields).map(([field, fieldDef]) => {
          if (
            field === 'thumbnail_image' &&
            (widgetType === 'custom_link' || widgetType === 'youtube_video')
          ) {
            return null;
          }


          // General handling for media_gallery fields (used by profile_carousel and image_gallery)
          if (fieldDef.type === 'media_gallery') {
            return (
              <ProfileCarouselImagesField
                key={field}
                field={field}
                definition={fieldDef}
                value={formState?.config?.[field]}
                onChange={handleInputChange}
                activeColor={activeColor}
              />
            );
          }

          return (
            <WidgetField
              key={field}
              field={field}
              definition={fieldDef}
              value={formState?.config?.[field]}
              onChange={handleInputChange}
            />
          );
        })}
        {(widgetType === 'custom_link' || widgetType === 'youtube_video') && (
          <div className={styles.thumbnailSection}>
            <span className={styles.thumbnailLabel}>Thumbnail</span>
            {widgetType === 'custom_link' ? (
              <>
                <div
                  className={styles.thumbnailPreview}
                  data-has-image={resolvedThumbnail ? 'true' : 'false'}
                >
                  {resolvedThumbnail ? (
                    <img src={normalizeImageUrl(resolvedThumbnail)} alt="Thumbnail preview" />
                  ) : (
                    <span>No image</span>
                  )}
                  <div className={styles.thumbnailOverlay}>
                    <div className={styles.segmentedBar}>
                      <button
                        type="button"
                        className={styles.segmentedButton}
                        onClick={() => setMediaLibraryOpen(true)}
                        title="Choose from library"
                      >
                        <Images size={16} weight="regular" aria-hidden="true" />
                      </button>
                      {resolvedThumbnail && (
                        <>
                          <div className={styles.segmentedDivider} />
                          <button
                            type="button"
                            className={`${styles.segmentedButton} ${styles.segmentedButtonDanger}`}
                            onClick={handleThumbnailRemove}
                            disabled={isUploadingThumbnail}
                            title="Remove thumbnail"
                          >
                            <X size={16} weight="regular" aria-hidden="true" />
                          </button>
                        </>
                      )}
                    </div>
                  </div>
                </div>
                <MediaLibraryModal
                  open={mediaLibraryOpen}
                  onClose={() => setMediaLibraryOpen(false)}
                  onSelect={handleSelectThumbnailFromLibrary}
                />
                {thumbnailError && <p className={styles.thumbnailError}>{thumbnailError}</p>}

              </>
            ) : (
              <>
                <div className={styles.thumbnailPreview}>
                  {resolvedThumbnail ? (
                    <img src={normalizeImageUrl(resolvedThumbnail)} alt="YouTube thumbnail" />
                  ) : (
                    <div className={styles.thumbnailPlaceholder}>No thumbnail</div>
                  )}
                </div>
                {resolvedThumbnail && (
                  <p className={styles.help}>
                    We automatically pull the preview from YouTube. Update the video URL to refresh it.
                  </p>
                )}
              </>
            )}
          </div>
        )}
        {Object.keys(configFields).length === 0 && <p className={styles.placeholder}>No configurable fields.</p>}
      </div>

      <div className={styles.footer} data-status={saveStatus}>
        <div className={styles.statusIndicator}>
          {saveStatus === 'saving' && (
            <>
              <CircleNotch className="spin" size={16} weight="regular" />
              <span>Saving...</span>
            </>
          )}
          {saveStatus === 'saved' && (
            <>
              <CheckCircle size={16} weight="fill" className={styles.statusIconSuccess} />
              <span>Saved</span>
            </>
          )}
          {saveStatus === 'error' && (
            <span className={styles.statusError}>Error saving changes</span>
          )}
        </div>
        <button type="button" className={styles.resetButton} onClick={handleReset} disabled={isSaving}>
          Revert Changes
        </button>
      </div>
    </section>
  );
}

interface WidgetFieldProps {
  field: string;
  definition: Record<string, unknown>;
  value: ConfigValue;
  onChange: (field: string, value: ConfigValue) => void;
}

function WidgetField({ field, definition, value, onChange }: WidgetFieldProps): JSX.Element {
  const type = (definition.type ?? 'text') as string;
  const label = (definition.label ?? field) as string;
  const help = (definition.help ?? '') as string;
  const required = Boolean(definition.required);

  if (type === 'checkbox') {
    return (
      <div className={styles.control}>
        <label className={styles.checkboxRow}>
          <input
            type="checkbox"
            checked={Boolean(value)}
            onChange={(event) => onChange(field, event.currentTarget.checked)}
          />
          <span>{label}</span>
        </label>
        {help && <p className={styles.help}>{help}</p>}
      </div>
    );
  }

  const handleChange = (event: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) => {
    onChange(field, event.currentTarget.value);
  };

  let input: JSX.Element;

  switch (type) {
    case 'textarea':
      input = (
        <textarea
          className={styles.textarea}
          value={(value as string) ?? ''}
          onChange={handleChange}
          rows={Number(definition.rows ?? 4)}
        />
      );
      break;
    case 'select': {
      const options = normalizeSelectOptions(definition.options);
      input = (
        <select className={styles.input} value={(value as string) ?? ''} onChange={handleChange}>
          <option value="">Select…</option>
          {options.map((option) => (
            <option key={option.value} value={option.value}>
              {option.label}
            </option>
          ))}
        </select>
      );
      break;
    }
    default:
      input = (
        <input
          className={styles.input}
          type={type === 'url' ? 'url' : 'text'}
          value={(value as string) ?? ''}
          onChange={handleChange}
        />
      );
      break;
  }

  return (
    <label className={styles.control}>
      <span>
        {label}
        {required && <span className={styles.required}>*</span>}
      </span>
      {input}
      {help && <p className={styles.help}>{help}</p>}
    </label>
  );
}

function normalizeSelectOptions(input: unknown): Array<{ value: string; label: string }> {
  if (!input) return [];
  if (Array.isArray(input)) {
    return input.map((entry) =>
      typeof entry === 'string'
        ? { value: entry, label: entry }
        : { value: String((entry as { value?: string; label?: string }).value ?? ''), label: String((entry as { value?: string; label?: string }).label ?? entry) }
    );
  }
  if (typeof input === 'object') {
    return Object.entries(input as Record<string, string>).map(([value, label]) => ({ value, label }));
  }
  return [];
}

function normalizeConfig(input: unknown): Record<string, ConfigValue> {
  if (!input || typeof input !== 'object') {
    return {};
  }

  return Object.entries(input as Record<string, unknown>).reduce<Record<string, ConfigValue>>((acc, [key, value]) => {
    acc[key] = toConfigValue(value);
    return acc;
  }, {});
}

function toConfigValue(value: unknown): ConfigValue {
  if (value === null || value === undefined) {
    return '';
  }

  if (typeof value === 'string' || typeof value === 'number' || typeof value === 'boolean') {
    return value;
  }

  return JSON.stringify(value);
}



interface ProfileCarouselImagesFieldProps {
  field: string;
  definition: Record<string, unknown>;
  value: ConfigValue;
  onChange: (field: string, value: ConfigValue) => void;
  activeColor: TabColorTheme;
}

function ProfileCarouselImagesField({ field, definition, value, onChange, activeColor }: ProfileCarouselImagesFieldProps): JSX.Element {
  const label = (definition.label ?? field) as string;
  const help = (definition.help ?? '') as string;
  const required = Boolean(definition.required);
  const [mediaLibraryOpen, setMediaLibraryOpen] = useState(false);
  const [isUploading, setIsUploading] = useState(false);

  // Parse images array from value
  const images = useMemo(() => {
    if (!value) return [];
    if (Array.isArray(value)) return value.filter((img): img is string => typeof img === 'string' && img.length > 0);
    if (typeof value === 'string') {
      try {
        const parsed = JSON.parse(value);
        return Array.isArray(parsed) ? parsed.filter((img): img is string => typeof img === 'string' && img.length > 0) : [];
      } catch {
        return [];
      }
    }
    return [];
  }, [value]);

  const handleAddImage = (imageUrl: string) => {
    const newImages = [...images, imageUrl];
    onChange(field, newImages);
  };

  const handleRemoveImage = (index: number) => {
    const newImages = images.filter((_, i) => i !== index);
    onChange(field, newImages);
  };

  const handleReorder = (fromIndex: number, toIndex: number) => {
    const newImages = [...images];
    const [removed] = newImages.splice(fromIndex, 1);
    newImages.splice(toIndex, 0, removed);
    onChange(field, newImages);
  };

  const handleSelectFromLibrary = async (mediaItem: MediaItem) => {
    handleAddImage(mediaItem.file_url);
    setMediaLibraryOpen(false);
  };


  return (
    <div className={styles.control}>
      <span>
        {label}
        {required && <span className={styles.required}>*</span>}
      </span>
      {help && <p className={styles.help}>{help}</p>}

      <div className={styles.carouselImagesGrid}>
        {images.map((imageUrl, index) => (
          <div key={index} className={styles.carouselImageItem}>
            <img src={normalizeImageUrl(imageUrl)} alt={`Carousel image ${index + 1}`} />
            <button
              type="button"
              className={styles.carouselImageRemove}
              onClick={() => handleRemoveImage(index)}
              aria-label={`Remove image ${index + 1}`}
            >
              <X size={16} weight="regular" />
            </button>
            {index > 0 && (
              <button
                type="button"
                className={styles.carouselImageMove}
                onClick={() => handleReorder(index, index - 1)}
                aria-label="Move left"
              >
                ←
              </button>
            )}
            {index < images.length - 1 && (
              <button
                type="button"
                className={styles.carouselImageMove}
                onClick={() => handleReorder(index, index + 1)}
                aria-label="Move right"
              >
                →
              </button>
            )}
          </div>
        ))}
      </div>

      <div className={styles.carouselImageActions}>
        <button
          type="button"
          className={styles.carouselImageAddButton}
          onClick={() => setMediaLibraryOpen(true)}
          disabled={isUploading}
        >
          <Images size={16} weight="regular" />
          Choose from Library
        </button>
      </div>


      <MediaLibraryModal
        open={mediaLibraryOpen}
        onClose={() => setMediaLibraryOpen(false)}
        onSelect={handleSelectFromLibrary}
      />

      {images.length === 0 && (
        <p className={styles.help} style={{ color: 'var(--admin-text-secondary)', fontStyle: 'italic' }}>
          No images added yet. Add at least one image to display the carousel.
        </p>
      )}
    </div>
  );
}


