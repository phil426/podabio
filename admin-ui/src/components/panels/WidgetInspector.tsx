import { useEffect, useMemo, useRef, useState } from 'react';

import { useAvailableWidgetsQuery, useUpdateWidgetMutation } from '../../api/widgets';
import { usePageSnapshot } from '../../api/page';
import type { WidgetRecord } from '../../api/types';
import { useWidgetSelection } from '../../state/widgetSelection';
import { uploadWidgetThumbnail } from '../../api/uploads';
import { getYouTubeThumbnail } from '../../utils/media';
import { normalizeImageUrl } from '../../api/utils';

import { type TabColorTheme } from '../layout/tab-colors';

import styles from './widget-inspector.module.css';

type ConfigValue = string | number | boolean | null | undefined;

interface WidgetFormState {
  title: string;
  isActive: boolean;
  isFeatured: boolean;
  featuredEffect: string;
  config: Record<string, ConfigValue>;
}

interface WidgetInspectorProps {
  activeColor: TabColorTheme;
}

export function WidgetInspector({ activeColor }: WidgetInspectorProps): JSX.Element {
  const { data: snapshot, isLoading } = usePageSnapshot();
  const { data: availableWidgets } = useAvailableWidgetsQuery();
  const selectedWidgetId = useWidgetSelection((state) => state.selectedWidgetId);
  const selectWidget = useWidgetSelection((state) => state.selectWidget);
  const { mutateAsync: updateWidget, isPending: isSaving } = useUpdateWidgetMutation();

  const selectedWidget = useMemo<WidgetRecord | undefined>(() => {
    if (!selectedWidgetId || !snapshot?.widgets) return undefined;
    return snapshot.widgets.find((widget) => String(widget.id) === selectedWidgetId);
  }, [selectedWidgetId, snapshot?.widgets]);

  const widgetDefinition = useMemo(() => {
    if (!selectedWidget || !availableWidgets) return undefined;
    return availableWidgets.find((item) => {
      const key = (item.widget_id ?? item.type ?? item.name ?? '').toString();
      return key === selectedWidget.widget_type;
    });
  }, [availableWidgets, selectedWidget]);

  const [formState, setFormState] = useState<WidgetFormState | null>(null);
  const [saveStatus, setSaveStatus] = useState<'idle' | 'success' | 'error'>('idle');
  const [thumbnailError, setThumbnailError] = useState<string | null>(null);
  const [isUploadingThumbnail, setUploadingThumbnail] = useState(false);
  const thumbnailInputRef = useRef<HTMLInputElement | null>(null);
  const widgetType =
    selectedWidget?.widget_type ?? (typeof widgetDefinition?.widget_id === 'string' ? widgetDefinition?.widget_id : '');

  useEffect(() => {
    if (!selectedWidget) {
      setFormState(null);
      return;
    }

    const normalizedConfig = normalizeConfig(selectedWidget.config_data);

    setFormState({
      title: selectedWidget.title,
      isActive: selectedWidget.is_active === 1,
      isFeatured: selectedWidget.is_featured === 1,
      featuredEffect: (selectedWidget.featured_effect as string) || 'jiggle',
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
      return {
        ...prev,
        config: {
          ...prev.config,
          thumbnail_image: derived
        }
      };
    });
    setSaveStatus('idle');
  }, [widgetType, videoUrlConfig, storedThumbnail, setSaveStatus]);

  const hasChanges = useMemo(() => {
    if (!formState || !selectedWidget) return false;

    const initialConfig = normalizeConfig(selectedWidget.config_data);

    const sameTitle = formState.title === selectedWidget.title;
    const sameActive = formState.isActive === (selectedWidget.is_active === 1);
    const sameFeatured = formState.isFeatured === (selectedWidget.is_featured === 1);
    const sameFeaturedEffect = formState.featuredEffect === ((selectedWidget.featured_effect as string) || 'jiggle');
    const sameConfig = JSON.stringify(formState.config ?? {}) === JSON.stringify(initialConfig ?? {});

    return !(sameTitle && sameActive && sameFeatured && sameFeaturedEffect && sameConfig);
  }, [formState, selectedWidget]);

  if (isLoading) {
    return (
      <section className={styles.wrapper} aria-label="Widget inspector">
        <p>Loading widget data…</p>
      </section>
    );
  }

  if (!selectedWidget) {
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
    if (!formState) return;
    setFormState((prev) =>
      prev
        ? {
            ...prev,
            config: {
              ...prev.config,
              [field]: value
            }
          }
        : prev
    );
    setSaveStatus('idle');
  };

  const handleTitleChange = (value: string) => {
    if (!formState) return;
    setFormState({
      ...formState,
      title: value
    });
    setSaveStatus('idle');
  };

  const handleActiveToggle = (checked: boolean) => {
    if (!formState) return;
    setFormState({
      ...formState,
      isActive: checked
    });
    setSaveStatus('idle');
  };

  const handleFeaturedToggle = (checked: boolean) => {
    if (!formState) return;
    setFormState({
      ...formState,
      isFeatured: checked,
      featuredEffect: checked && !formState.featuredEffect ? 'jiggle' : formState.featuredEffect
    });
    setSaveStatus('idle');
  };

  const handleReset = () => {
    if (!selectedWidget) return;
    const normalizedConfig = normalizeConfig(selectedWidget.config_data);

    setFormState({
      title: selectedWidget.title,
      isActive: selectedWidget.is_active === 1,
      isFeatured: selectedWidget.is_featured === 1,
      featuredEffect: (selectedWidget.featured_effect as string) || 'jiggle',
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

  const handleThumbnailUploadClick = () => {
    thumbnailInputRef.current?.click();
  };

  const handleThumbnailFileChange = async (
    event: React.ChangeEvent<HTMLInputElement>
  ) => {
    const file = event.target.files?.[0];
    if (!file) return;
    setThumbnailError(null);
    setUploadingThumbnail(true);
    try {
      const result = await uploadWidgetThumbnail(file);
      if (result.url) {
        handleInputChange('thumbnail_image', result.url);
      }
    } catch (error) {
      setThumbnailError(error instanceof Error ? error.message : 'Unable to upload thumbnail.');
    } finally {
      setUploadingThumbnail(false);
      if (thumbnailInputRef.current) {
        thumbnailInputRef.current.value = '';
      }
    }
  };

  const handleThumbnailRemove = () => {
    handleInputChange('thumbnail_image', '');
    setThumbnailError(null);
  };

  const handleSave = async () => {
    if (!formState) return;

    try {
      await updateWidget({
        widget_id: String(selectedWidget.id),
        title: formState.title,
        is_active: formState.isActive ? '1' : '0',
        is_featured: formState.isFeatured ? '1' : '0',
        featured_effect: formState.isFeatured ? formState.featuredEffect : '',
        config_data: JSON.stringify(formState.config ?? {})
      });
      setSaveStatus('success');
    } catch (error) {
      console.error('Failed to update widget', error);
      setSaveStatus('error');
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
            type="text"
            value={formState?.title ?? ''}
            onChange={(event) => handleTitleChange(event.target.value)}
          />
        </label>
        <label className={styles.toggle}>
          <input
            type="checkbox"
            checked={formState?.isActive ?? false}
            onChange={(event) => handleActiveToggle(event.target.checked)}
          />
          <span>Show block on page</span>
        </label>
        <label className={styles.toggle}>
          <input
            type="checkbox"
            checked={formState?.isFeatured ?? false}
            onChange={(event) => handleFeaturedToggle(event.target.checked)}
          />
          <span>Mark as featured block</span>
        </label>
        {formState?.isFeatured && (
          <label className={styles.control}>
            <span>Featured Effect</span>
            <select
              className={styles.input}
              value={formState.featuredEffect}
              onChange={(e) => {
                if (formState) {
                  setFormState({ ...formState, featuredEffect: e.target.value });
                  setSaveStatus('idle');
                }
              }}
            >
              <option value="jiggle">Jiggle 🎯</option>
              <option value="burn">Burn 🔥</option>
              <option value="rotating-glow">Rotating Glow 💫</option>
              <option value="blink">Blink 👁️</option>
              <option value="pulse">Pulse 💓</option>
              <option value="shake">Shake 📳</option>
              <option value="sparkles">Sparkles ✨</option>
            </select>
          </label>
        )}
      </div>

      <div className={styles.fieldset}>
        {Object.entries(configFields).map(([field, fieldDef]) => {
          if (
            field === 'thumbnail_image' &&
            (widgetType === 'custom_link' || widgetType === 'youtube_video')
          ) {
            return null;
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
            <div className={styles.thumbnailPreview}>
              {resolvedThumbnail ? (
                <img src={normalizeImageUrl(resolvedThumbnail)} alt="" />
              ) : (
                <div className={styles.thumbnailPlaceholder}>No thumbnail</div>
              )}
            </div>

            {widgetType === 'custom_link' && (
              <>
                <div className={styles.thumbnailActions}>
                  <button
                    type="button"
                    onClick={handleThumbnailUploadClick}
                    className={styles.thumbnailButton}
                    disabled={isUploadingThumbnail}
                  >
                    {isUploadingThumbnail ? 'Uploading…' : 'Upload thumbnail'}
                  </button>
                  {resolvedThumbnail && (
                    <button
                      type="button"
                      onClick={handleThumbnailRemove}
                      className={styles.thumbnailButtonSecondary}
                      disabled={isUploadingThumbnail}
                    >
                      Remove
                    </button>
                  )}
                </div>
                <input
                  ref={thumbnailInputRef}
                  type="file"
                  accept="image/png,image/jpeg,image/webp"
                  hidden
                  onChange={handleThumbnailFileChange}
                />
                {thumbnailError && <p className={styles.thumbnailError}>{thumbnailError}</p>}
              </>
            )}

            {widgetType === 'youtube_video' && resolvedThumbnail && (
              <p className={styles.help}>
                We automatically pull the preview from YouTube. Update the video URL to refresh it.
              </p>
            )}
          </div>
        )}
        {Object.keys(configFields).length === 0 && <p className={styles.placeholder}>No configurable fields.</p>}
      </div>

      <div className={styles.footer}>
        <button type="button" className={styles.saveButton} onClick={handleSave} disabled={!hasChanges || isSaving}>
          {isSaving ? 'Saving…' : 'Save changes'}
        </button>
        <button type="button" className={styles.resetButton} onClick={handleReset} disabled={!hasChanges || isSaving}>
          Reset
        </button>
        {saveStatus === 'success' && <span className={styles.statusOk}>Saved!</span>}
        {saveStatus === 'error' && <span className={styles.statusError}>Save failed. Try again.</span>}
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


