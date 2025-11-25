import { useEffect, useMemo, useRef, useState } from 'react';
import { Upload, X, Check, CheckSquare, Square } from '@phosphor-icons/react';

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
          // Special handling for people widget thumbnail - show upload interface
          if (widgetType === 'people' && field === 'thumbnail_image') {
            return null; // Handled separately below
          }
          // Special handling for people contacts field with CSV import
          if (widgetType === 'people' && field === 'contacts') {
            return (
              <PeopleContactsField
                key={field}
                field={field}
                definition={fieldDef}
                value={formState?.config?.[field]}
                onChange={handleInputChange}
              />
            );
          }
          
          // Special handling for rolodex items field with CSV import (legacy)
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
        {(widgetType === 'custom_link' || widgetType === 'youtube_video' || widgetType === 'people') && (
          <div className={styles.thumbnailSection}>
            <span className={styles.thumbnailLabel}>Thumbnail</span>
            {widgetType === 'custom_link' || widgetType === 'people' ? (
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
                {widgetType === 'people' && (
                  <input
                    type="url"
                    className={styles.input}
                    value={currentThumbnail}
                    onChange={(e) => handleInputChange('thumbnail_image', e.target.value)}
                    placeholder="Or enter image URL"
                    style={{ marginTop: '0.5rem' }}
                  />
                )}
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

interface PeopleContactsFieldProps {
  field: string;
  definition: Record<string, unknown>;
  value: ConfigValue;
  onChange: (field: string, value: ConfigValue) => void;
}

function PeopleContactsField({ field, definition, value, onChange }: PeopleContactsFieldProps): JSX.Element {
  const label = (definition.label ?? field) as string;
  const help = (definition.help ?? '') as string;
  const required = Boolean(definition.required);
  const csvInputRef = useRef<HTMLInputElement | null>(null);
  const [isParsing, setIsParsing] = useState(false);
  const [parseError, setParseError] = useState<string | null>(null);
  const [isLoadingGoogle, setIsLoadingGoogle] = useState(false);
  const [showContactForm, setShowContactForm] = useState(false);
  const [showContactSelection, setShowContactSelection] = useState(false);
  const [fetchedContacts, setFetchedContacts] = useState<Array<{
    name: string;
    email?: string;
    phone?: string;
    company?: string;
    title?: string;
    address?: string;
    website?: string;
    notes?: string;
    photo?: string;
    groups?: string[];
  }>>([]);
  const [groupNames, setGroupNames] = useState<Record<string, string>>({});
  const [searchQuery, setSearchQuery] = useState('');
  const [selectedContactIndices, setSelectedContactIndices] = useState<Set<number>>(new Set());
  const [currentContacts, setCurrentContacts] = useState<Array<{
    name: string;
    email?: string;
    phone?: string;
    company?: string;
    title?: string;
    address?: string;
    website?: string;
    notes?: string;
    photo?: string;
  }>>([]);

  // Parse current contacts from JSON value
  useEffect(() => {
    try {
      const contacts = typeof value === 'string' && value.trim() ? JSON.parse(value) : [];
      setCurrentContacts(Array.isArray(contacts) ? contacts : []);
    } catch {
      setCurrentContacts([]);
    }
  }, [value]);

  const updateContacts = (newContacts: Array<{
    name: string;
    email?: string;
    phone?: string;
    company?: string;
    title?: string;
    address?: string;
    website?: string;
    notes?: string;
    photo?: string;
  }>) => {
    setCurrentContacts(newContacts);
    onChange(field, JSON.stringify(newContacts));
  };

  // Filter contacts based on search query
  const getFilteredContacts = () => {
    if (!searchQuery.trim()) {
      return fetchedContacts.map((contact, index) => ({ contact, index }));
    }
    
    const query = searchQuery.toLowerCase().trim();
    return fetchedContacts
      .map((contact, index) => ({ contact, index }))
      .filter(({ contact }) => {
        const searchableText = [
          contact.name,
          contact.email,
          contact.company,
          contact.title,
          contact.phone
        ]
          .filter(Boolean)
          .join(' ')
          .toLowerCase();
        
        return searchableText.includes(query);
      });
  };

  const handleCSVFileChange = async (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0];
    if (!file) return;

    setIsParsing(true);
    setParseError(null);

    try {
      const text = await file.text();
      const importedContacts = parseCSVToPeopleContacts(text);
      
      if (importedContacts.length === 0) {
        setParseError('No valid contacts found in CSV file. Expected format: name,email,phone,company,title,address,website,notes,photo (header row optional)');
        return;
      }

      // Merge with existing contacts
      const mergedContacts = [...currentContacts, ...importedContacts];
      updateContacts(mergedContacts);
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

  const handleImportGoogleContacts = async () => {
    setIsLoadingGoogle(true);
    setParseError(null);

    try {
      // Get Google OAuth URL with contacts scope
      const response = await fetch('/api/google-contacts/auth-url.php', {
        method: 'GET',
        credentials: 'include'
      });

      if (!response.ok) {
        throw new Error('Failed to get Google auth URL');
      }

      const data = await response.json();
      if (data.authUrl) {
        // Open popup for Google OAuth
        const width = 500;
        const height = 600;
        const left = window.screenX + (window.outerWidth - width) / 2;
        const top = window.screenY + (window.outerHeight - height) / 2;
        
        const popup = window.open(
          data.authUrl,
          'Google Contacts',
          `width=${width},height=${height},left=${left},top=${top},resizable=yes,scrollbars=yes`
        );

        // Listen for message from popup
        const messageListener = (event: MessageEvent) => {
          // Validate origin - check if it matches our origin or if origin is provided in data
          const isValidOrigin = 
            event.origin === window.location.origin || 
            (event.data.origin && event.data.origin === window.location.origin) ||
            event.origin.startsWith('http://localhost') || 
            event.origin.startsWith('https://poda.bio');
          
          if (!isValidOrigin) {
            console.warn('Ignoring message from invalid origin:', event.origin);
            return;
          }
          
          if (event.data.type === 'GOOGLE_CONTACTS_IMPORTED') {
            const contacts = event.data.contacts || [];
            const groups = event.data.groupNames || {};
            if (contacts.length > 0) {
              // Store fetched contacts and show selection dialog
              setFetchedContacts(contacts);
              setGroupNames(groups);
              setSelectedContactIndices(new Set(contacts.map((_: unknown, index: number) => index)));
              setSearchQuery('');
              setShowContactSelection(true);
              setIsLoadingGoogle(false);
            } else {
              setParseError('No contacts found in your Google account');
              setIsLoadingGoogle(false);
            }
            window.removeEventListener('message', messageListener);
            try {
            popup?.close();
            } catch (e) {
              // Ignore COOP errors when closing
            }
          } else if (event.data.type === 'GOOGLE_CONTACTS_ERROR') {
            setParseError(event.data.error || 'Failed to import Google contacts');
            setIsLoadingGoogle(false);
            window.removeEventListener('message', messageListener);
            try {
            popup?.close();
            } catch (e) {
              // Ignore COOP errors when closing
            }
          }
        };

        window.addEventListener('message', messageListener);

        // Check if popup was closed
        const checkClosed = setInterval(() => {
          if (popup?.closed) {
            clearInterval(checkClosed);
            window.removeEventListener('message', messageListener);
          }
        }, 1000);
      }
    } catch (error) {
      setParseError(error instanceof Error ? error.message : 'Failed to import Google contacts');
    } finally {
      setIsLoadingGoogle(false);
    }
  };

  const handleAddContact = (contact: {
    name: string;
    email?: string;
    phone?: string;
    company?: string;
    title?: string;
    address?: string;
    website?: string;
    notes?: string;
    photo?: string;
  }) => {
    const newContacts = [...currentContacts, contact];
    updateContacts(newContacts);
    setShowContactForm(false);
  };

  const handleRemoveContact = (index: number) => {
    const newContacts = currentContacts.filter((_, i) => i !== index);
    updateContacts(newContacts);
  };

  return (
    <div className={styles.control}>
      <span>
        {label}
        {required && <span className={styles.required}>*</span>}
      </span>
      <div style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem' }}>
        {/* Action buttons */}
        <div style={{ display: 'flex', gap: '0.5rem', flexWrap: 'wrap', alignItems: 'center' }}>
          <button
            type="button"
            onClick={() => setShowContactForm(!showContactForm)}
            style={{
              padding: '0.5rem 1rem',
              borderRadius: '6px',
              border: '1px solid rgba(0, 0, 0, 0.2)',
              background: 'white',
              cursor: 'pointer',
              fontSize: '0.875rem'
            }}
          >
            {showContactForm ? 'Cancel' : 'Add Contact'}
          </button>
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
        </div>

        {/* Contact form */}
        {showContactForm && (
          <ContactForm
            onAdd={handleAddContact}
            onCancel={() => setShowContactForm(false)}
          />
        )}

        {/* Contacts list */}
        {currentContacts.length > 0 && (
          <div style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem', maxHeight: '200px', overflowY: 'auto', padding: '0.5rem', border: '1px solid rgba(0, 0, 0, 0.1)', borderRadius: '6px' }}>
            {currentContacts.map((contact, index) => (
              <div key={index} style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', padding: '0.5rem', background: 'rgba(0, 0, 0, 0.02)', borderRadius: '4px' }}>
                <div style={{ flex: 1 }}>
                  <div style={{ fontWeight: 600 }}>{contact.name}</div>
                  {contact.email && <div style={{ fontSize: '0.875rem', color: '#666' }}>{contact.email}</div>}
                  {contact.company && <div style={{ fontSize: '0.875rem', color: '#666' }}>{contact.company}</div>}
                </div>
                <button
                  type="button"
                  onClick={() => handleRemoveContact(index)}
                  style={{
                    padding: '0.25rem 0.5rem',
                    borderRadius: '4px',
                    border: '1px solid rgba(0, 0, 0, 0.2)',
                    background: 'white',
                    cursor: 'pointer',
                    fontSize: '0.75rem',
                    color: '#dc3545'
                  }}
                >
                  Remove
                </button>
              </div>
            ))}
          </div>
        )}

        {/* Contact Selection Dialog */}
        {showContactSelection && (
          <div style={{
            position: 'fixed',
            top: 0,
            left: 0,
            right: 0,
            bottom: 0,
            background: 'rgba(0, 0, 0, 0.5)',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            zIndex: 10000,
            padding: '1rem'
          }}>
            <div style={{
              background: 'white',
              borderRadius: '8px',
              padding: '1.5rem',
              maxWidth: '600px',
              width: '100%',
              maxHeight: '80vh',
              display: 'flex',
              flexDirection: 'column',
              boxShadow: '0 4px 20px rgba(0, 0, 0, 0.15)'
            }}>
              <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: '1rem' }}>
                <h3 style={{ margin: 0, fontSize: '1.125rem', fontWeight: 600 }}>
                  Select Contacts to Import ({selectedContactIndices.size} of {fetchedContacts.length} selected)
                </h3>
                <button
                  type="button"
                  onClick={() => {
                    setShowContactSelection(false);
                    setFetchedContacts([]);
                    setGroupNames({});
                    setSelectedContactIndices(new Set());
                    setSearchQuery('');
                  }}
                  style={{
                    background: 'none',
                    border: 'none',
                    cursor: 'pointer',
                    padding: '0.25rem',
                    display: 'flex',
                    alignItems: 'center',
                    justifyContent: 'center'
                  }}
                >
                  <X size={20} weight="bold" />
                </button>
              </div>
              
              {/* Search field */}
              <div style={{ marginBottom: '1rem' }}>
                <input
                  type="text"
                  placeholder="Search contacts by name, email, company..."
                  value={searchQuery}
                  onChange={(e) => setSearchQuery(e.target.value)}
                  style={{
                    width: '100%',
                    padding: '0.5rem',
                    borderRadius: '6px',
                    border: '1px solid rgba(0, 0, 0, 0.2)',
                    fontSize: '0.875rem'
                  }}
                />
              </div>

              {/* Group selection */}
              {Object.keys(groupNames).length > 0 && (
                <div style={{ marginBottom: '1rem' }}>
                  <div style={{ fontSize: '0.875rem', fontWeight: 500, marginBottom: '0.5rem', color: '#666' }}>
                    Select by Group:
                  </div>
                  <div style={{ display: 'flex', flexWrap: 'wrap', gap: '0.5rem' }}>
                    {Object.entries(groupNames).map(([groupId, groupName]) => {
                      const groupContacts = fetchedContacts
                        .map((contact, idx) => ({ contact, idx }))
                        .filter(({ contact }) => contact.groups?.includes(groupId))
                        .map(({ idx }) => idx);
                      
                      const allSelected = groupContacts.length > 0 && 
                        groupContacts.every(idx => selectedContactIndices.has(idx));
                      
                      return (
                        <button
                          key={groupId}
                          type="button"
                          onClick={() => {
                            const newSelected = new Set(selectedContactIndices);
                            if (allSelected) {
                              // Deselect all contacts in this group
                              groupContacts.forEach(idx => newSelected.delete(idx));
                            } else {
                              // Select all contacts in this group
                              groupContacts.forEach(idx => newSelected.add(idx));
                            }
                            setSelectedContactIndices(newSelected);
                          }}
                          style={{
                            padding: '0.375rem 0.75rem',
                            borderRadius: '6px',
                            border: '1px solid rgba(0, 0, 0, 0.2)',
                            background: allSelected ? '#007bff' : 'white',
                            color: allSelected ? 'white' : '#333',
                            cursor: 'pointer',
                            fontSize: '0.8125rem',
                            fontWeight: allSelected ? 500 : 400
                          }}
                        >
                          {groupName} ({groupContacts.length})
                        </button>
                      );
                    })}
                  </div>
                </div>
              )}

              <div style={{ display: 'flex', gap: '0.5rem', marginBottom: '1rem' }}>
                <button
                  type="button"
                  onClick={() => {
                    // Filter contacts based on search, then select all visible
                    const filtered = getFilteredContacts();
                    const newSelected = new Set(selectedContactIndices);
                    filtered.forEach(({ index }) => newSelected.add(index));
                    setSelectedContactIndices(newSelected);
                  }}
                  style={{
                    padding: '0.5rem 1rem',
                    borderRadius: '6px',
                    border: '1px solid rgba(0, 0, 0, 0.2)',
                    background: 'white',
                    cursor: 'pointer',
                    fontSize: '0.875rem'
                  }}
                >
                  Select All Visible
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setSelectedContactIndices(new Set(fetchedContacts.map((_, index) => index)));
                  }}
                  style={{
                    padding: '0.5rem 1rem',
                    borderRadius: '6px',
                    border: '1px solid rgba(0, 0, 0, 0.2)',
                    background: 'white',
                    cursor: 'pointer',
                    fontSize: '0.875rem'
                  }}
                >
                  Select All
                </button>
                <button
                  type="button"
                  onClick={() => {
                    setSelectedContactIndices(new Set());
                  }}
                  style={{
                    padding: '0.5rem 1rem',
                    borderRadius: '6px',
                    border: '1px solid rgba(0, 0, 0, 0.2)',
                    background: 'white',
                    cursor: 'pointer',
                    fontSize: '0.875rem'
                  }}
                >
                  Deselect All
                </button>
              </div>

              <div style={{
                flex: 1,
                overflowY: 'auto',
                border: '1px solid rgba(0, 0, 0, 0.1)',
                borderRadius: '6px',
                padding: '0.5rem',
                marginBottom: '1rem',
                maxHeight: '400px'
              }}>
                {(() => {
                  const filtered = getFilteredContacts();
                  
                  if (filtered.length === 0) {
                    return (
                      <div style={{ padding: '2rem', textAlign: 'center', color: '#666' }}>
                        No contacts match your search
                      </div>
                    );
                  }
                  
                  return filtered.map(({ contact, index }) => {
                  const isSelected = selectedContactIndices.has(index);
                  const contactGroups = (contact.groups || [])
                    .map((groupId: string) => groupNames[groupId])
                    .filter(Boolean);
                  
                  return (
                    <div
                      key={index}
                      onClick={() => {
                        const newSelected = new Set(selectedContactIndices);
                        if (isSelected) {
                          newSelected.delete(index);
                        } else {
                          newSelected.add(index);
                        }
                        setSelectedContactIndices(newSelected);
                      }}
                      style={{
                        display: 'flex',
                        alignItems: 'center',
                        gap: '0.75rem',
                        padding: '0.75rem',
                        borderRadius: '4px',
                        cursor: 'pointer',
                        background: isSelected ? 'rgba(0, 123, 255, 0.1)' : 'transparent',
                        border: isSelected ? '1px solid rgba(0, 123, 255, 0.3)' : '1px solid transparent',
                        marginBottom: '0.5rem',
                        transition: 'all 0.2s'
                      }}
                    >
                      <div style={{ display: 'flex', alignItems: 'center', justifyContent: 'center', minWidth: '24px' }}>
                        {isSelected ? (
                          <CheckSquare size={20} weight="fill" style={{ color: '#007bff' }} />
                        ) : (
                          <Square size={20} weight="regular" style={{ color: '#666' }} />
                        )}
                      </div>
                      {contact.photo && (
                        <img
                          src={contact.photo}
                          alt={contact.name}
                          style={{
                            width: '40px',
                            height: '40px',
                            borderRadius: '50%',
                            objectFit: 'cover'
                          }}
                        />
                      )}
                      <div style={{ flex: 1 }}>
                        <div style={{ fontWeight: 600, fontSize: '0.9375rem' }}>{contact.name}</div>
                        {contact.email && (
                          <div style={{ fontSize: '0.875rem', color: '#666', marginTop: '0.25rem' }}>
                            {contact.email}
                          </div>
                        )}
                        {contact.company && (
                          <div style={{ fontSize: '0.875rem', color: '#666' }}>
                            {contact.company}{contact.title && ` • ${contact.title}`}
                          </div>
                        )}
                        {contact.phone && (
                          <div style={{ fontSize: '0.875rem', color: '#666' }}>
                            {contact.phone}
                          </div>
                        )}
                        {contactGroups.length > 0 && (
                          <div style={{ 
                            display: 'flex', 
                            flexWrap: 'wrap', 
                            gap: '0.25rem', 
                            marginTop: '0.25rem' 
                          }}>
                            {contactGroups.map((groupName: string) => (
                              <span
                                key={groupName}
                                style={{
                                  fontSize: '0.75rem',
                                  padding: '0.125rem 0.375rem',
                                  background: 'rgba(0, 123, 255, 0.1)',
                                  color: '#007bff',
                                  borderRadius: '4px'
                                }}
                              >
                                {groupName}
                              </span>
                            ))}
                          </div>
                        )}
                      </div>
                    </div>
                  );
                });
                })()}
              </div>

              <div style={{ display: 'flex', gap: '0.5rem', justifyContent: 'flex-end' }}>
                <button
                  type="button"
                  onClick={() => {
                    setShowContactSelection(false);
                    setFetchedContacts([]);
                    setGroupNames({});
                    setSelectedContactIndices(new Set());
                    setSearchQuery('');
                  }}
                  style={{
                    padding: '0.5rem 1rem',
                    borderRadius: '6px',
                    border: '1px solid rgba(0, 0, 0, 0.2)',
                    background: 'white',
                    cursor: 'pointer',
                    fontSize: '0.875rem'
                  }}
                >
                  Cancel
                </button>
                <button
                  type="button"
                  onClick={() => {
                    const selectedContacts = Array.from(selectedContactIndices)
                      .map(index => fetchedContacts[index])
                      .filter(Boolean);
                    
                    if (selectedContacts.length > 0) {
                      const mergedContacts = [...currentContacts, ...selectedContacts];
                      updateContacts(mergedContacts);
                    }
                    
                    setShowContactSelection(false);
                    setFetchedContacts([]);
                    setGroupNames({});
                    setSelectedContactIndices(new Set());
                    setSearchQuery('');
                  }}
                  disabled={selectedContactIndices.size === 0}
                  style={{
                    padding: '0.5rem 1rem',
                    borderRadius: '6px',
                    border: 'none',
                    background: selectedContactIndices.size === 0 ? '#ccc' : '#007bff',
                    color: 'white',
                    cursor: selectedContactIndices.size === 0 ? 'not-allowed' : 'pointer',
                    fontSize: '0.875rem',
                    fontWeight: 500
                  }}
                >
                  Import Selected ({selectedContactIndices.size})
                </button>
              </div>
            </div>
          </div>
        )}

        {parseError && <p style={{ color: '#dc3545', fontSize: '0.875rem', margin: 0 }}>{parseError}</p>}
        {help && <p className={styles.help} style={{ margin: 0, fontSize: '0.75rem', color: '#666' }}>{help}</p>}
      </div>
    </div>
  );
}

interface ContactFormProps {
  onAdd: (contact: {
    name: string;
    email?: string;
    phone?: string;
    company?: string;
    title?: string;
    address?: string;
    website?: string;
    notes?: string;
    photo?: string;
  }) => void;
  onCancel: () => void;
}

function ContactForm({ onAdd, onCancel }: ContactFormProps): JSX.Element {
  const [formData, setFormData] = useState({
    name: '',
    email: '',
    phone: '',
    company: '',
    title: '',
    address: '',
    website: '',
    notes: '',
    photo: ''
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    if (!formData.name.trim()) return;

    const contact: {
      name: string;
      email?: string;
      phone?: string;
      company?: string;
      title?: string;
      address?: string;
      website?: string;
      notes?: string;
      photo?: string;
    } = {
      name: formData.name.trim()
    };

    if (formData.email.trim()) contact.email = formData.email.trim();
    if (formData.phone.trim()) contact.phone = formData.phone.trim();
    if (formData.company.trim()) contact.company = formData.company.trim();
    if (formData.title.trim()) contact.title = formData.title.trim();
    if (formData.address.trim()) contact.address = formData.address.trim();
    if (formData.website.trim()) {
      const url = formData.website.trim();
      contact.website = url.startsWith('http://') || url.startsWith('https://') ? url : `https://${url}`;
    }
    if (formData.notes.trim()) contact.notes = formData.notes.trim();
    if (formData.photo.trim()) {
      const photo = formData.photo.trim();
      if (photo.startsWith('http://') || photo.startsWith('https://') || photo.startsWith('/')) {
        contact.photo = photo;
      }
    }

    onAdd(contact);
    setFormData({
      name: '',
      email: '',
      phone: '',
      company: '',
      title: '',
      address: '',
      website: '',
      notes: '',
      photo: ''
    });
  };

  return (
    <form onSubmit={handleSubmit} style={{ display: 'flex', flexDirection: 'column', gap: '0.75rem', padding: '1rem', border: '1px solid rgba(0, 0, 0, 0.1)', borderRadius: '6px', background: 'rgba(255, 255, 255, 0.5)' }}>
      <div style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
        <label style={{ fontSize: '0.875rem', fontWeight: 600 }}>
          Name <span style={{ color: '#dc3545' }}>*</span>
        </label>
        <input
          type="text"
          className={styles.input}
          value={formData.name}
          onChange={(e) => setFormData({ ...formData, name: e.target.value })}
          required
          placeholder="John Doe"
        />
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.5rem' }}>
        <div style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
          <label style={{ fontSize: '0.875rem', fontWeight: 600 }}>Email</label>
          <input
            type="email"
            className={styles.input}
            value={formData.email}
            onChange={(e) => setFormData({ ...formData, email: e.target.value })}
            placeholder="john@example.com"
          />
        </div>
        <div style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
          <label style={{ fontSize: '0.875rem', fontWeight: 600 }}>Phone</label>
          <input
            type="tel"
            className={styles.input}
            value={formData.phone}
            onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
            placeholder="+1-555-0123"
          />
        </div>
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.5rem' }}>
        <div style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
          <label style={{ fontSize: '0.875rem', fontWeight: 600 }}>Company</label>
          <input
            type="text"
            className={styles.input}
            value={formData.company}
            onChange={(e) => setFormData({ ...formData, company: e.target.value })}
            placeholder="Acme Inc"
          />
        </div>
        <div style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
          <label style={{ fontSize: '0.875rem', fontWeight: 600 }}>Title</label>
          <input
            type="text"
            className={styles.input}
            value={formData.title}
            onChange={(e) => setFormData({ ...formData, title: e.target.value })}
            placeholder="CEO"
          />
        </div>
      </div>

      <div style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
        <label style={{ fontSize: '0.875rem', fontWeight: 600 }}>Address</label>
        <textarea
          className={styles.textarea}
          value={formData.address}
          onChange={(e) => setFormData({ ...formData, address: e.target.value })}
          rows={2}
          placeholder="123 Main St, City, State 12345"
        />
      </div>

      <div style={{ display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '0.5rem' }}>
        <div style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
          <label style={{ fontSize: '0.875rem', fontWeight: 600 }}>Website</label>
          <input
            type="url"
            className={styles.input}
            value={formData.website}
            onChange={(e) => setFormData({ ...formData, website: e.target.value })}
            placeholder="example.com"
          />
        </div>
        <div style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
          <label style={{ fontSize: '0.875rem', fontWeight: 600 }}>Photo URL</label>
          <input
            type="url"
            className={styles.input}
            value={formData.photo}
            onChange={(e) => setFormData({ ...formData, photo: e.target.value })}
            placeholder="https://example.com/photo.jpg"
          />
        </div>
      </div>

      <div style={{ display: 'flex', flexDirection: 'column', gap: '0.5rem' }}>
        <label style={{ fontSize: '0.875rem', fontWeight: 600 }}>Notes</label>
        <textarea
          className={styles.textarea}
          value={formData.notes}
          onChange={(e) => setFormData({ ...formData, notes: e.target.value })}
          rows={2}
          placeholder="Additional notes about this contact"
        />
      </div>

      <div style={{ display: 'flex', gap: '0.5rem', justifyContent: 'flex-end' }}>
        <button
          type="button"
          onClick={onCancel}
          style={{
            padding: '0.5rem 1rem',
            borderRadius: '6px',
            border: '1px solid rgba(0, 0, 0, 0.2)',
            background: 'white',
            cursor: 'pointer',
            fontSize: '0.875rem'
          }}
        >
          Cancel
        </button>
        <button
          type="submit"
          disabled={!formData.name.trim()}
          style={{
            padding: '0.5rem 1rem',
            borderRadius: '6px',
            border: 'none',
            background: formData.name.trim() ? 'var(--active-tab-bg, #667eea)' : '#ccc',
            color: 'white',
            cursor: formData.name.trim() ? 'pointer' : 'not-allowed',
            fontSize: '0.875rem',
            fontWeight: 600
          }}
        >
          Add Contact
        </button>
      </div>
    </form>
  );
}

function parseCSVToPeopleContacts(csvText: string): Array<{
  name: string;
  email?: string;
  phone?: string;
  company?: string;
  title?: string;
  address?: string;
  website?: string;
  notes?: string;
  photo?: string;
}> {
  const lines = csvText.trim().split('\n');
  if (lines.length === 0) return [];

  // Check if first line is a header (common CSV headers)
  let startIndex = 0;
  const firstLine = lines[0].toLowerCase();
  if (firstLine.includes('name') || firstLine.includes('contact') || firstLine.includes('person')) {
    startIndex = 1; // Skip header row
  }

  const contacts: Array<{
    name: string;
    email?: string;
    phone?: string;
    company?: string;
    title?: string;
    address?: string;
    website?: string;
    notes?: string;
    photo?: string;
  }> = [];

  for (let i = startIndex; i < lines.length; i++) {
    const line = lines[i].trim();
    if (!line) continue;

    // Parse CSV line (handles quoted fields)
    const fields = parseCSVLine(line);
    
    if (fields.length === 0) continue;

    const contact: {
      name: string;
      email?: string;
      phone?: string;
      company?: string;
      title?: string;
      address?: string;
      website?: string;
      notes?: string;
      photo?: string;
    } = {
      name: fields[0]?.trim() || 'Unnamed'
    };

    // Map CSV columns to contact fields
    // Expected order: name, email, phone, company, title, address, website, notes, photo
    if (fields[1]) contact.email = fields[1].trim();
    if (fields[2]) contact.phone = fields[2].trim();
    if (fields[3]) contact.company = fields[3].trim();
    if (fields[4]) contact.title = fields[4].trim();
    if (fields[5]) contact.address = fields[5].trim();
    if (fields[6]) {
      const url = fields[6].trim();
      if (url && (url.startsWith('http://') || url.startsWith('https://') || url.startsWith('/'))) {
        contact.website = url;
      } else if (url) {
        contact.website = 'https://' + url;
      }
    }
    if (fields[7]) contact.notes = fields[7].trim();
    if (fields[8]) {
      const photo = fields[8].trim();
      if (photo && (photo.startsWith('http://') || photo.startsWith('https://') || photo.startsWith('/'))) {
        contact.photo = photo;
      }
    }

    contacts.push(contact);
  }

  return contacts;
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


