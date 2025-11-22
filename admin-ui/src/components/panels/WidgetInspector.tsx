import { useEffect, useMemo, useRef, useState } from 'react';
import { Upload, X } from '@phosphor-icons/react';

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


  const handleReset = () => {
    if (!selectedWidget) return;
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

  const handleChooseThumbnailFile = () => {
    thumbnailInputRef.current?.click();
  };

  const handleThumbnailFileChange = async (
    event: React.ChangeEvent<HTMLInputElement>
  ) => {
    const file = event.target.files?.[0];
    if (!file || !selectedWidget) return;
    setThumbnailError(null);
    setUploadingThumbnail(true);
    try {
      const result = await uploadWidgetThumbnail(file);
      if (result.url) {
        handleInputChange('thumbnail_image', result.url);
        setSaveStatus('success');
        // Auto-save the widget after thumbnail upload
        if (formState) {
          await updateWidget({
            widget_id: String(selectedWidget.id),
            title: formState.title,
            is_active: formState.isActive ? '1' : '0',
            config_data: JSON.stringify({
              ...formState.config,
              thumbnail_image: result.url
            })
          });
        }
      }
    } catch (error) {
      setThumbnailError(error instanceof Error ? error.message : 'Unable to upload thumbnail.');
      setSaveStatus('error');
    } finally {
      setUploadingThumbnail(false);
      if (thumbnailInputRef.current) {
        thumbnailInputRef.current.value = '';
      }
    }
  };

  const handleThumbnailRemove = async () => {
    handleInputChange('thumbnail_image', '');
    setThumbnailError(null);
    // Auto-save after removing thumbnail
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
        setSaveStatus('success');
      } catch (error) {
        setSaveStatus('error');
      }
    }
  };

  const handleSave = async () => {
    if (!formState) return;

    try {
      await updateWidget({
        widget_id: String(selectedWidget.id),
        title: formState.title,
        is_active: formState.isActive ? '1' : '0',
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
      </div>

      <div className={styles.fieldset}>
        {Object.entries(configFields).map(([field, fieldDef]) => {
          if (
            field === 'thumbnail_image' &&
            (widgetType === 'custom_link' || widgetType === 'youtube_video')
          ) {
            return null;
          }
          // Special handling for rolodex items field with CSV import
          if (widgetType === 'rolodex' && field === 'items') {
            return (
              <RolodexItemsField
                key={field}
                field={field}
                definition={fieldDef}
                value={formState?.config?.[field]}
                onChange={handleInputChange}
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
                    <button
                      type="button"
                      className={styles.thumbnailActionButton}
                      onClick={handleChooseThumbnailFile}
                      disabled={isUploadingThumbnail}
                      title={isUploadingThumbnail ? 'Uploading…' : resolvedThumbnail ? 'Replace thumbnail' : 'Upload thumbnail'}
                    >
                      <Upload aria-hidden="true" />
                    </button>
                    {resolvedThumbnail && (
                      <button
                        type="button"
                        className={styles.thumbnailActionButton}
                        onClick={handleThumbnailRemove}
                        disabled={isUploadingThumbnail}
                        title="Remove thumbnail"
                      >
                        <X aria-hidden="true" size={16} weight="regular" />
                      </button>
                    )}
                  </div>
                </div>
                <input
                  ref={thumbnailInputRef}
                  type="file"
                  accept="image/png,image/jpeg,image/webp"
                  className={styles.hiddenInput}
                  onChange={handleThumbnailFileChange}
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

interface RolodexItemsFieldProps {
  field: string;
  definition: Record<string, unknown>;
  value: ConfigValue;
  onChange: (field: string, value: ConfigValue) => void;
}

function RolodexItemsField({ field, definition, value, onChange }: RolodexItemsFieldProps): JSX.Element {
  const label = (definition.label ?? field) as string;
  const help = (definition.help ?? '') as string;
  const required = Boolean(definition.required);
  const csvInputRef = useRef<HTMLInputElement | null>(null);
  const [isParsing, setIsParsing] = useState(false);
  const [parseError, setParseError] = useState<string | null>(null);

  const handleCSVFileChange = async (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;

    setIsParsing(true);
    setParseError(null);

    try {
      const text = await file.text();
      const items = parseCSVToRolodexItems(text);
      
      if (items.length === 0) {
        setParseError('No valid items found in CSV file. Expected format: title,description,url (header row optional)');
        return;
      }

      onChange(field, JSON.stringify(items));
    } catch (error) {
      setParseError(error instanceof Error ? error.message : 'Failed to parse CSV file');
    } finally {
      setIsParsing(false);
      if (csvInputRef.current) {
        csvInputRef.current.value = '';
      }
    }
  };

  const handleChooseCSVFile = () => {
    csvInputRef.current?.click();
  };

  return (
    <div className={styles.control}>
      <span>
        {label}
        {required && <span className={styles.required}>*</span>}
      </span>
      <div style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
        <div style={{ display: 'flex', gap: '0.5rem', alignItems: 'center' }}>
          <button
            type="button"
            onClick={handleChooseCSVFile}
            disabled={isParsing}
            style={{
              padding: '0.5rem 1rem',
              borderRadius: '6px',
              border: '1px solid rgba(0, 0, 0, 0.2)',
              background: 'white',
              cursor: isParsing ? 'not-allowed' : 'pointer',
              fontSize: '0.875rem'
            }}
          >
            {isParsing ? 'Parsing…' : 'Import CSV'}
          </button>
          <input
            ref={csvInputRef}
            type="file"
            accept=".csv,text/csv"
            style={{ display: 'none' }}
            onChange={handleCSVFileChange}
          />
          {help && <p className={styles.help} style={{ margin: 0, fontSize: '0.75rem', color: '#666' }}>{help}</p>}
        </div>
        <textarea
          className={styles.textarea}
          value={(value as string) ?? ''}
          onChange={(event) => onChange(field, event.target.value)}
          rows={Number(definition.rows ?? 8)}
          placeholder='[{"title":"Item 1","description":"Details here","url":"https://example.com"}]'
        />
        {parseError && <p style={{ color: '#dc3545', fontSize: '0.875rem', margin: 0 }}>{parseError}</p>}
      </div>
    </div>
  );
}

function parseCSVToRolodexItems(csvText: string): Array<{ title: string; description?: string; url?: string }> {
  const lines = csvText.trim().split('\n');
  if (lines.length === 0) return [];

  // Check if first line is a header (common CSV headers)
  let startIndex = 0;
  const firstLine = lines[0].toLowerCase();
  if (firstLine.includes('title') || firstLine.includes('name') || firstLine.includes('item')) {
    startIndex = 1; // Skip header row
  }

  const items: Array<{ title: string; description?: string; url?: string }> = [];

  for (let i = startIndex; i < lines.length; i++) {
    const line = lines[i].trim();
    if (!line) continue;

    // Parse CSV line (handles quoted fields)
    const fields = parseCSVLine(line);
    
    if (fields.length === 0) continue;

    const item: { title: string; description?: string; url?: string } = {
      title: fields[0]?.trim() || 'Untitled'
    };

    if (fields[1]) {
      item.description = fields[1].trim();
    }

    if (fields[2]) {
      const url = fields[2].trim();
      // Validate URL format
      if (url && (url.startsWith('http://') || url.startsWith('https://') || url.startsWith('/'))) {
        item.url = url;
      }
    }

    items.push(item);
  }

  return items;
}

function parseCSVLine(line: string): string[] {
  const fields: string[] = [];
  let currentField = '';
  let inQuotes = false;

  for (let i = 0; i < line.length; i++) {
    const char = line[i];
    const nextChar = line[i + 1];

    if (char === '"') {
      if (inQuotes && nextChar === '"') {
        // Escaped quote
        currentField += '"';
        i++; // Skip next quote
      } else {
        // Toggle quote state
        inQuotes = !inQuotes;
      }
    } else if (char === ',' && !inQuotes) {
      // Field separator
      fields.push(currentField);
      currentField = '';
    } else {
      currentField += char;
    }
  }

  // Add last field
  fields.push(currentField);

  return fields;
}


