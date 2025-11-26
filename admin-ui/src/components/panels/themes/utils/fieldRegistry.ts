/**
 * Field Registry System
 * Central registry for all theme fields (implemented + deferred)
 * Allows adding new fields without code changes
 */

export type FieldType = 
  | 'color' 
  | 'gradient' 
  | 'image' 
  | 'font' 
  | 'size' 
  | 'spacing' 
  | 'effect' 
  | 'weight' 
  | 'border-width' 
  | 'shadow' 
  | 'glow'
  | 'number'
  | 'select'
  | 'toggle';

export interface FieldDefinition {
  id: string;
  label: string;
  type: FieldType;
  tokenPath: string; // JSON token path (e.g., 'typography_tokens.color.heading')
  section: string; // Section ID this field belongs to
  defaultValue?: unknown;
  min?: number;
  max?: number;
  step?: number;
  options?: Array<{ value: string; label: string }>;
  unit?: string; // 'px', 'rem', '%', etc.
  description?: string;
  implemented?: boolean; // Whether UI is implemented (default: false for deferred)
  component?: string; // Component name to use for rendering
  validation?: (value: unknown) => boolean;
}

class FieldRegistry {
  private fields: Map<string, FieldDefinition> = new Map();

  /**
   * Register a field definition
   */
  register(field: FieldDefinition): void {
    this.fields.set(field.id, {
      ...field,
      implemented: field.implemented ?? false
    });
  }

  /**
   * Get a field definition by ID
   */
  get(fieldId: string): FieldDefinition | undefined {
    return this.fields.get(fieldId);
  }

  /**
   * Get all fields for a section
   */
  getFieldsForSection(sectionId: string): FieldDefinition[] {
    return Array.from(this.fields.values())
      .filter(field => field.section === sectionId)
      .sort((a, b) => a.id.localeCompare(b.id));
  }

  /**
   * Get all implemented fields
   */
  getImplementedFields(): FieldDefinition[] {
    return Array.from(this.fields.values())
      .filter(field => field.implemented === true);
  }

  /**
   * Get all fields (implemented + deferred)
   */
  getAllFields(): FieldDefinition[] {
    return Array.from(this.fields.values());
  }

  /**
   * Check if a field is implemented
   */
  isImplemented(fieldId: string): boolean {
    const field = this.fields.get(fieldId);
    return field?.implemented === true;
  }
}

// Create singleton instance
export const fieldRegistry = new FieldRegistry();

// Register all fields from the plan (architecture supports all, UI implemented incrementally)

// Page Customization Section
fieldRegistry.register({
  id: 'page-background',
  label: 'Page Background',
  type: 'color',
  tokenPath: 'page_background', // Direct column
  section: 'page-customization',
  defaultValue: '#ffffff',
  implemented: true
});

fieldRegistry.register({
  id: 'page-vertical-spacing',
  label: 'Vertical Spacing',
  type: 'number',
  tokenPath: 'spacing_tokens.vertical_spacing',
  section: 'page-customization',
  defaultValue: 24,
  min: 0,
  max: 100,
  step: 4,
  unit: 'px',
  implemented: true
});

fieldRegistry.register({
  id: 'page-background-animate',
  label: 'Animate Gradient',
  type: 'boolean',
  tokenPath: 'page.page_background_animate',
  section: 'page-background',
  defaultValue: false,
  implemented: true
});

fieldRegistry.register({
  id: 'page-title-effect',
  label: 'Special Effect',
  type: 'select',
  tokenPath: 'page.page_name_effect',
  section: 'page-customization',
  defaultValue: 'none',
  options: [
    { value: 'none', label: 'None' },
    { value: 'glow', label: 'Neon Glow' },
    { value: 'shadow', label: 'Drop Shadow' },
    { value: 'retro', label: 'Retro Shadow' },
    { value: 'anaglyphic', label: 'Anaglyphic' },
    { value: 'deep', label: 'Deep' },
    { value: 'game', label: 'Game' },
    { value: 'fancy', label: 'Fancy' },
    { value: 'pretty', label: 'Pretty' },
    { value: 'flat', label: 'Flat' },
    { value: 'long', label: 'Long Shadow' },
    { value: 'party', label: 'Party Time' }
  ],
  implemented: true
});

// Shadow properties
fieldRegistry.register({
  id: 'page-title-shadow-color',
  label: 'Shadow Color',
  type: 'color',
  tokenPath: 'typography_tokens.effect.shadow.color',
  section: 'page-customization',
  defaultValue: '#000000',
  implemented: true
});

fieldRegistry.register({
  id: 'page-title-shadow-intensity',
  label: 'Shadow Intensity',
  type: 'number',
  tokenPath: 'typography_tokens.effect.shadow.intensity',
  section: 'page-customization',
  defaultValue: 0.5,
  min: 0,
  max: 1,
  step: 0.1,
  implemented: true
});

fieldRegistry.register({
  id: 'page-title-shadow-depth',
  label: 'Shadow Depth',
  type: 'number',
  tokenPath: 'typography_tokens.effect.shadow.depth',
  section: 'page-customization',
  defaultValue: 4,
  min: 0,
  max: 20,
  step: 1,
  unit: 'px',
  implemented: true
});

fieldRegistry.register({
  id: 'page-title-shadow-blur',
  label: 'Shadow Blur',
  type: 'number',
  tokenPath: 'typography_tokens.effect.shadow.blur',
  section: 'page-customization',
  defaultValue: 8,
  min: 0,
  max: 50,
  step: 1,
  unit: 'px',
  implemented: true
});

// Glow properties
fieldRegistry.register({
  id: 'page-title-glow-color',
  label: 'Glow Color',
  type: 'color',
  tokenPath: 'typography_tokens.effect.glow.color',
  section: 'page-customization',
  defaultValue: '#2563eb',
  implemented: true
});

fieldRegistry.register({
  id: 'page-title-glow-width',
  label: 'Glow Width',
  type: 'number',
  tokenPath: 'typography_tokens.effect.glow.width',
  section: 'page-customization',
  defaultValue: 10,
  min: 0,
  max: 50,
  step: 1,
  unit: 'px',
  implemented: true
});

// Border/Stroke properties
fieldRegistry.register({
  id: 'page-title-border-color',
  label: 'Font Border Color',
  type: 'color',
  tokenPath: 'typography_tokens.effect.border.color',
  section: 'page-customization',
  defaultValue: '#000000',
  implemented: true
});

fieldRegistry.register({
  id: 'page-title-border-width',
  label: 'Font Border Width',
  type: 'number',
  tokenPath: 'typography_tokens.effect.border.width',
  section: 'page-customization',
  defaultValue: 0,
  min: 0,
  max: 10,
  step: 0.5,
  unit: 'px',
  implemented: true
});

fieldRegistry.register({
  id: 'page-title-color',
  label: 'Page Title Color',
  type: 'color',
  tokenPath: 'typography_tokens.color.heading',
  section: 'page-customization',
  defaultValue: '#0f172a',
  implemented: true
});

fieldRegistry.register({
  id: 'page-title-font',
  label: 'Page Title Font',
  type: 'font',
  tokenPath: 'typography_tokens.font.heading',
  section: 'page-customization',
  defaultValue: 'Inter',
  implemented: true
});

fieldRegistry.register({
  id: 'page-title-size',
  label: 'Page Title Size',
  type: 'size',
  tokenPath: 'typography_tokens.scale.heading', // Custom field in typography_tokens
  section: 'page-customization',
  defaultValue: 24,
  min: 14,
  max: 48,
  unit: 'px',
  implemented: true
});

fieldRegistry.register({
  id: 'page-title-spacing',
  label: 'Page Title Spacing',
  type: 'spacing',
  tokenPath: 'typography_tokens.line_height.heading', // Custom field in typography_tokens
  section: 'page-customization',
  defaultValue: 1.2,
  min: 1,
  max: 2,
  step: 0.1,
  implemented: true
});

fieldRegistry.register({
  id: 'page-title-weight',
  label: 'Page Title Style',
  type: 'weight',
  tokenPath: 'typography_tokens.weight.heading',
  section: 'page-customization',
  defaultValue: { bold: false, italic: false },
  implemented: true
});

fieldRegistry.register({
  id: 'page-bio-color',
  label: 'Page Bio Color',
  type: 'color',
  tokenPath: 'typography_tokens.color.body',
  section: 'page-customization',
  defaultValue: '#4b5563',
  implemented: true
});

fieldRegistry.register({
  id: 'page-bio-font',
  label: 'Page Bio Font',
  type: 'font',
  tokenPath: 'typography_tokens.font.body',
  section: 'page-customization',
  defaultValue: 'Inter',
  implemented: true
});

fieldRegistry.register({
  id: 'page-bio-size',
  label: 'Page Bio Size',
  type: 'size',
  tokenPath: 'typography_tokens.scale.body', // Custom field in typography_tokens
  section: 'page-customization',
  defaultValue: 16,
  min: 10,
  max: 24,
  unit: 'px',
  implemented: true
});

fieldRegistry.register({
  id: 'page-bio-spacing',
  label: 'Page Bio Spacing',
  type: 'spacing',
  tokenPath: 'spacing_tokens.page_spacing',
  section: 'page-customization',
  defaultValue: 100,
  min: 50,
  max: 200,
  unit: '%',
  implemented: true
});

fieldRegistry.register({
  id: 'page-bio-weight',
  label: 'Page Bio Style',
  type: 'weight',
  tokenPath: 'typography_tokens.weight.body',
  section: 'page-customization',
  defaultValue: { bold: false, italic: false },
  implemented: true
});

// Profile Image Styling (page-level fields, not theme tokens)
fieldRegistry.register({
  id: 'profile-image-size',
  label: 'Profile Image Size',
  type: 'number',
  tokenPath: 'page.profile_image_size', // Will store as number (px)
  section: 'page-customization',
  defaultValue: 120,
  min: 80,
  max: 180,
  step: 4,
  unit: 'px',
  implemented: true
});

fieldRegistry.register({
  id: 'profile-image-radius',
  label: 'Profile Image Radius',
  type: 'number',
  tokenPath: 'page.profile_image_radius', // New field for radius
  section: 'page-customization',
  defaultValue: 16, // 16% = rounded, 0% = square, 50% = circle
  min: 0,
  max: 50,
  step: 1,
  unit: '%',
  implemented: true
});

fieldRegistry.register({
  id: 'profile-image-effect',
  label: 'Special Effect',
  type: 'select',
  tokenPath: 'page.profile_image_effect',
  section: 'page-customization',
  defaultValue: 'none',
  options: [
    { value: 'none', label: 'None' },
    { value: 'glow', label: 'Glow' },
    { value: 'shadow', label: 'Drop Shadow' }
  ],
  implemented: true
});

// Shadow properties
fieldRegistry.register({
  id: 'profile-image-shadow-color',
  label: 'Shadow Color',
  type: 'color',
  tokenPath: 'page.profile_image_shadow_color',
  section: 'page-customization',
  defaultValue: '#000000',
  implemented: true
});

fieldRegistry.register({
  id: 'profile-image-shadow-intensity',
  label: 'Shadow Intensity',
  type: 'number',
  tokenPath: 'page.profile_image_shadow_intensity',
  section: 'page-customization',
  defaultValue: 0.5,
  min: 0,
  max: 1,
  step: 0.1,
  implemented: true
});

fieldRegistry.register({
  id: 'profile-image-shadow-depth',
  label: 'Shadow Depth',
  type: 'number',
  tokenPath: 'page.profile_image_shadow_depth',
  section: 'page-customization',
  defaultValue: 4,
  min: 0,
  max: 20,
  step: 1,
  unit: 'px',
  implemented: true
});

fieldRegistry.register({
  id: 'profile-image-shadow-blur',
  label: 'Shadow Blur',
  type: 'number',
  tokenPath: 'page.profile_image_shadow_blur',
  section: 'page-customization',
  defaultValue: 8,
  min: 0,
  max: 50,
  step: 1,
  unit: 'px',
  implemented: true
});

// Glow properties
fieldRegistry.register({
  id: 'profile-image-glow-color',
  label: 'Glow Color',
  type: 'color',
  tokenPath: 'page.profile_image_glow_color',
  section: 'page-customization',
  defaultValue: '#2563eb',
  implemented: true
});

fieldRegistry.register({
  id: 'profile-image-glow-width',
  label: 'Glow Width',
  type: 'number',
  tokenPath: 'page.profile_image_glow_width',
  section: 'page-customization',
  defaultValue: 10,
  min: 0,
  max: 50,
  step: 1,
  unit: 'px',
  implemented: true
});

// Border/Stroke properties
fieldRegistry.register({
  id: 'profile-image-border-color',
  label: 'Border Color',
  type: 'color',
  tokenPath: 'page.profile_image_border_color',
  section: 'page-customization',
  defaultValue: '#000000',
  implemented: true
});

fieldRegistry.register({
  id: 'profile-image-border-width',
  label: 'Border Width',
  type: 'number',
  tokenPath: 'page.profile_image_border_width',
  section: 'page-customization',
  defaultValue: 0,
  min: 0,
  max: 10,
  step: 0.5,
  unit: 'px',
  implemented: true
});

// Widget Buttons Section
fieldRegistry.register({
  id: 'widget-background',
  label: 'Widget Background',
  type: 'color',
  tokenPath: 'widget_background', // Direct column (or widget_styles.background)
  section: 'widget-buttons',
  defaultValue: '#ffffff',
  implemented: true
});

fieldRegistry.register({
  id: 'widget-border-color',
  label: 'Widget Border Color',
  type: 'color',
  tokenPath: 'widget_styles.border_color', // Can be in widget_styles JSON or widget_border_color direct column
  section: 'widget-buttons',
  defaultValue: '#e2e8f0',
  implemented: true
});

fieldRegistry.register({
  id: 'widget-border-width',
  label: 'Widget Border Width',
  type: 'border-width',
  tokenPath: 'widget_styles.border_width', // JSON field
  section: 'widget-buttons',
  defaultValue: 0,
  min: 0,
  max: 8,
  unit: 'px',
  implemented: true
});

fieldRegistry.register({
  id: 'widget-rounding',
  label: 'Rounding',
  type: 'number',
  tokenPath: 'shape_tokens.corner.radius',
  section: 'widget-buttons',
  defaultValue: 12,
  min: 0,
  max: 50,
  step: 1,
  unit: 'px',
  implemented: true
});

fieldRegistry.register({
  id: 'widget-button-width',
  label: 'Button Width',
  type: 'number',
  tokenPath: 'widget_styles.width',
  section: 'widget-buttons',
  defaultValue: 100,
  min: 50,
  max: 100,
  step: 5,
  unit: '%',
  implemented: true
});

fieldRegistry.register({
  id: 'widget-border-effect',
  label: 'Widget Effect',
  type: 'select',
  tokenPath: 'widget_styles.border_effect',
  section: 'widget-buttons',
  defaultValue: 'none',
  options: [
    { value: 'none', label: 'None' },
    { value: 'shadow', label: 'Shadow' },
    { value: 'glow', label: 'Glow' }
  ],
  implemented: true
});

fieldRegistry.register({
  id: 'widget-shadow-depth',
  label: 'Shadow Depth',
  type: 'shadow',
  tokenPath: 'widget_styles.shadow_depth',
  section: 'widget-buttons',
  defaultValue: 1,
  min: 0,
  max: 10,
  implemented: true
});

fieldRegistry.register({
  id: 'widget-shadow-color',
  label: 'Shadow Color',
  type: 'color',
  tokenPath: 'widget_styles.shadow_color',
  section: 'widget-buttons',
  defaultValue: 'rgba(15, 23, 42, 0.12)',
  implemented: true
});

fieldRegistry.register({
  id: 'widget-shadow-intensity',
  label: 'Shadow Intensity',
  type: 'number',
  tokenPath: 'widget_styles.shadow_intensity',
  section: 'widget-buttons',
  defaultValue: 1,
  min: 0,
  max: 1,
  step: 0.1,
  implemented: true
});

fieldRegistry.register({
  id: 'widget-glow-width',
  label: 'Glow Width',
  type: 'glow',
  tokenPath: 'widget_styles.glow_width',
  section: 'widget-buttons',
  defaultValue: 2,
  min: 0,
  max: 20,
  unit: 'px',
  implemented: true
});

fieldRegistry.register({
  id: 'widget-glow-color',
  label: 'Glow Color',
  type: 'color',
  tokenPath: 'widget_styles.glow_color',
  section: 'widget-buttons',
  defaultValue: '#2563eb',
  implemented: true
});

fieldRegistry.register({
  id: 'widget-glow-intensity',
  label: 'Glow Intensity',
  type: 'number',
  tokenPath: 'widget_styles.glow_intensity',
  section: 'widget-buttons',
  defaultValue: 1,
  min: 0,
  max: 1,
  step: 0.1,
  implemented: true
});

// Widget Text Section
fieldRegistry.register({
  id: 'widget-heading-color',
  label: 'Widget Heading Color',
  type: 'color',
  tokenPath: 'typography_tokens.color.widget_heading',
  section: 'widget-text',
  defaultValue: '#0f172a',
  implemented: true
});

fieldRegistry.register({
  id: 'widget-heading-font',
  label: 'Widget Heading Font',
  type: 'font',
  tokenPath: 'typography_tokens.font.widget_heading',
  section: 'widget-text',
  defaultValue: 'Inter',
  implemented: true
});

fieldRegistry.register({
  id: 'widget-heading-size',
  label: 'Widget Heading Size',
  type: 'size',
  tokenPath: 'typography_tokens.scale.widget_heading', // Custom field in typography_tokens
  section: 'widget-text',
  defaultValue: 20,
  min: 14,
  max: 48,
  unit: 'px',
  implemented: true
});

fieldRegistry.register({
  id: 'widget-heading-spacing',
  label: 'Widget Heading Spacing',
  type: 'spacing',
  tokenPath: 'typography_tokens.line_height.widget_heading', // Custom field in typography_tokens
  section: 'widget-text',
  defaultValue: 1.3,
  min: 1,
  max: 2,
  step: 0.1,
  implemented: true
});

fieldRegistry.register({
  id: 'widget-heading-weight',
  label: 'Widget Heading Style',
  type: 'weight',
  tokenPath: 'typography_tokens.weight.widget_heading',
  section: 'widget-text',
  defaultValue: { bold: false, italic: false },
  implemented: true
});

fieldRegistry.register({
  id: 'widget-body-color',
  label: 'Widget Body Color',
  type: 'color',
  tokenPath: 'typography_tokens.color.widget_body',
  section: 'widget-text',
  defaultValue: '#4b5563',
  implemented: true
});

fieldRegistry.register({
  id: 'widget-body-font',
  label: 'Widget Body Font',
  type: 'font',
  tokenPath: 'typography_tokens.font.widget_body',
  section: 'widget-text',
  defaultValue: 'Inter',
  implemented: true
});

fieldRegistry.register({
  id: 'widget-body-size',
  label: 'Widget Body Size',
  type: 'size',
  tokenPath: 'typography_tokens.scale.widget_body', // Custom field in typography_tokens
  section: 'widget-text',
  defaultValue: 16,
  min: 14,
  max: 48,
  unit: 'px',
  implemented: true
});

fieldRegistry.register({
  id: 'widget-body-spacing',
  label: 'Widget Body Spacing',
  type: 'spacing',
  tokenPath: 'typography_tokens.line_height.widget_body', // Custom field in typography_tokens
  section: 'widget-text',
  defaultValue: 1.5,
  min: 1,
  max: 2,
  step: 0.1,
  implemented: true
});

fieldRegistry.register({
  id: 'widget-body-weight',
  label: 'Widget Body Style',
  type: 'weight',
  tokenPath: 'typography_tokens.weight.widget_body',
  section: 'widget-text',
  defaultValue: { bold: false, italic: false },
  implemented: true
});

// Social Icons Section
fieldRegistry.register({
  id: 'social-icon-color',
  label: 'Social Icon Color',
  type: 'color',
  tokenPath: 'iconography_tokens.color',
  section: 'social-icons',
  defaultValue: '#2563eb',
  implemented: true
});

fieldRegistry.register({
  id: 'social-icon-size',
  label: 'Social Icon Size',
  type: 'size',
  tokenPath: 'iconography_tokens.size',
  section: 'social-icons',
  defaultValue: 32,
  min: 20,
  max: 64,
  unit: 'px',
  implemented: true
});

fieldRegistry.register({
  id: 'social-icon-spacing',
  label: 'Social Icon Spacing',
  type: 'spacing',
  tokenPath: 'iconography_tokens.spacing',
  section: 'social-icons',
  defaultValue: 1,
  min: 0,
  max: 3,
  unit: 'rem',
  implemented: true
});

// Podcast Player Bar Section
fieldRegistry.register({
  id: 'podcast-player-background',
  label: 'Background',
  type: 'color',
  tokenPath: 'podcast_player.background',
  section: 'podcast-player-bar',
  defaultValue: 'linear-gradient(135deg, #6366f1 0%, #4f46e5 100%)',
  implemented: true
});

fieldRegistry.register({
  id: 'podcast-player-border-color',
  label: 'Border Color',
  type: 'color',
  tokenPath: 'podcast_player.border_color',
  section: 'podcast-player-bar',
  defaultValue: 'rgba(255, 255, 255, 0.2)',
  implemented: true
});

fieldRegistry.register({
  id: 'podcast-player-border-width',
  label: 'Border Width',
  type: 'number',
  tokenPath: 'podcast_player.border_width',
  section: 'podcast-player-bar',
  defaultValue: 1,
  min: 0,
  max: 8,
  step: 1,
  unit: 'px',
  implemented: true
});

fieldRegistry.register({
  id: 'podcast-player-shadow-enabled',
  label: 'Shadow Enabled',
  type: 'toggle',
  tokenPath: 'podcast_player.shadow_enabled',
  section: 'podcast-player-bar',
  defaultValue: true,
  implemented: true
});

fieldRegistry.register({
  id: 'podcast-player-shadow-depth',
  label: 'Shadow Depth',
  type: 'number',
  tokenPath: 'podcast_player.shadow_depth',
  section: 'podcast-player-bar',
  defaultValue: 16,
  min: 0,
  max: 50,
  step: 1,
  unit: 'px',
  implemented: true
});

fieldRegistry.register({
  id: 'podcast-player-text-color',
  label: 'Text Color',
  type: 'color',
  tokenPath: 'podcast_player.text_color',
  section: 'podcast-player-bar',
  defaultValue: '#ffffff',
  implemented: true
});

