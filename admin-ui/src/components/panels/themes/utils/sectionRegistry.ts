/**
 * Section Registry System
 * Extensible section framework for theme editor
 */

import { fieldRegistry } from './fieldRegistry';

export interface SectionDefinition {
  id: string;
  title: string;
  description?: string;
  fields: string[]; // Field IDs from fieldRegistry
  component?: string; // Component name for rendering
  collapsible?: boolean;
  defaultExpanded?: boolean;
  order?: number; // Display order
}

class SectionRegistry {
  private sections: Map<string, SectionDefinition> = new Map();

  /**
   * Register a section definition
   */
  register(section: SectionDefinition): void {
    this.sections.set(section.id, {
      ...section,
      collapsible: section.collapsible ?? true,
      defaultExpanded: section.defaultExpanded ?? true,
      order: section.order ?? 0
    });
  }

  /**
   * Get a section definition by ID
   */
  get(sectionId: string): SectionDefinition | undefined {
    return this.sections.get(sectionId);
  }

  /**
   * Get all sections in order
   */
  getAllSections(): SectionDefinition[] {
    return Array.from(this.sections.values())
      .sort((a, b) => (a.order ?? 0) - (b.order ?? 0));
  }

  /**
   * Get fields for a section (with field definitions)
   */
  getFieldsForSection(sectionId: string) {
    const section = this.sections.get(sectionId);
    if (!section) return [];

    return section.fields
      .map(fieldId => fieldRegistry.get(fieldId))
      .filter((field): field is NonNullable<typeof field> => field !== undefined);
  }
}

// Create singleton instance
export const sectionRegistry = new SectionRegistry();

// Register all sections from the plan
sectionRegistry.register({
  id: 'page-background',
  title: 'Page Background',
  description: 'Customize the page background and vertical spacing',
  fields: [
    'page-background',
    'page-vertical-spacing'
  ],
  order: 0
});

sectionRegistry.register({
  id: 'profile-image',
  title: 'Profile Image',
  description: 'Customize profile image appearance and effects',
  fields: [
    'profile-image-size',
    'profile-image-radius',
    'profile-image-effect',
    'profile-image-shadow-color',
    'profile-image-shadow-intensity',
    'profile-image-shadow-depth',
    'profile-image-shadow-blur',
    'profile-image-glow-color',
    'profile-image-glow-width',
    'profile-image-border-color',
    'profile-image-border-width'
  ],
  order: 0.5
});

sectionRegistry.register({
  id: 'page-title',
  title: 'Page Title',
  description: 'Customize page title appearance and effects',
  fields: [
    'page-title-effect',
    'page-title-shadow-color',
    'page-title-shadow-intensity',
    'page-title-shadow-depth',
    'page-title-shadow-blur',
    'page-title-glow-color',
    'page-title-glow-width',
    'page-title-border-color',
    'page-title-border-width',
    'page-title-color',
    'page-title-font',
    'page-title-size',
    'page-title-spacing',
    'page-title-weight'
  ],
  order: 0.6
});

sectionRegistry.register({
  id: 'page-description',
  title: 'Page Description',
  description: 'Customize page description/bio appearance',
  fields: [
    'page-bio-color',
    'page-bio-font',
    'page-bio-size',
    'page-bio-spacing',
    'page-bio-weight'
  ],
  order: 0.7
});

sectionRegistry.register({
  id: 'page-customization',
  title: 'Page Customization',
  description: 'Customize the page background, title, and bio appearance',
  fields: [
    'page-background',
    'page-vertical-spacing',
    'profile-image-size',
    'profile-image-radius',
    'profile-image-effect',
    'profile-image-shadow-color',
    'profile-image-shadow-intensity',
    'profile-image-shadow-depth',
    'profile-image-shadow-blur',
    'profile-image-glow-color',
    'profile-image-glow-width',
    'profile-image-border-color',
    'profile-image-border-width',
    'page-title-effect',
    'page-title-color',
    'page-title-font',
    'page-title-size',
    'page-title-spacing',
    'page-title-weight',
    'page-bio-color',
    'page-bio-font',
    'page-bio-size',
    'page-bio-spacing',
    'page-bio-weight'
  ],
  order: 1
});

sectionRegistry.register({
  id: 'widget-buttons',
  title: 'Widgets & Blocks Button Settings',
  description: 'Customize widget background, border, shadow, and glow effects',
  fields: [
    'widget-background',
    'widget-border-color',
    'widget-border-width',
    'widget-border-effect',
    'widget-shadow-depth',
    'widget-shadow-color',
    'widget-shadow-intensity',
    'widget-glow-width',
    'widget-glow-color',
    'widget-glow-intensity'
  ],
  order: 2
});

sectionRegistry.register({
  id: 'widget-settings',
  title: 'Widget Settings',
  description: 'Customize widget background, border, radius, shadow, and glow',
  fields: [
    'widget-background',
    'widget-border-color',
    'widget-border-width',
    'widget-rounding',
    'widget-border-effect',
    'widget-shadow-depth',
    'widget-shadow-color',
    'widget-shadow-intensity',
    'widget-glow-width',
    'widget-glow-color',
    'widget-glow-intensity'
  ],
  order: 2.5
});

sectionRegistry.register({
  id: 'widget-text',
  title: 'Widgets & Blocks Text Settings',
  description: 'Customize widget heading and body text appearance',
  fields: [
    'widget-heading-color',
    'widget-heading-font',
    'widget-heading-size',
    'widget-heading-spacing',
    'widget-heading-weight',
    'widget-body-color',
    'widget-body-font',
    'widget-body-size',
    'widget-body-spacing',
    'widget-body-weight'
  ],
  order: 3
});

sectionRegistry.register({
  id: 'social-icons',
  title: 'Social Icons',
  description: 'Customize social icon appearance',
  fields: [
    'social-icon-color',
    'social-icon-size',
    'social-icon-spacing'
  ],
  order: 4
});

sectionRegistry.register({
  id: 'podcast-player-bar',
  title: 'Podcast Player Bar',
  description: 'Customize podcast player bar appearance3',
  fields: [
    'podcast-player-background',
    'podcast-player-border-color',
    'podcast-player-border-width',
    'podcast-player-shadow-enabled',
    'podcast-player-shadow-depth',
    'podcast-player-text-color'
  ],
  order: 0.3
});

