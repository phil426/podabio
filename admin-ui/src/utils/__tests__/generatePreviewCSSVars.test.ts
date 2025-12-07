import { describe, it, expect } from 'vitest';
import { generatePreviewCSSVars } from '../generatePreviewCSSVars';
import type { GeneratedThemeData } from '../../api/podcastTheme';

const baseThemeData: GeneratedThemeData = {
  name: 'Test Theme',
  color_tokens: {
    semantic: {
      accent: { primary: '#123456' }
    }
  },
  typography_tokens: {
    color: {
      heading: '#111111',
      body: '#222222',
      widget_heading: '#333333',
      widget_body: '#444444'
    }
  },
  page_background: '#ffffff',
  widget_background: '#f8f8f8',
  widget_border_color: '#dddddd',
  page_primary_font: 'Inter',
  page_secondary_font: 'Space Mono',
  widget_primary_font: 'Inter',
  widget_secondary_font: 'Space Mono',
  widget_styles: {},
};

describe('generatePreviewCSSVars', () => {
  it('returns critical CSS variables with fallbacks', () => {
    const cssVars = generatePreviewCSSVars(baseThemeData, null);

    expect(cssVars['--page-background']).toBe('#ffffff');
    expect(cssVars['--widget-background']).toBe('#f8f8f8');
    expect(cssVars['--widget-border-color']).toBe('#dddddd');
    expect(cssVars['--page-title-color']).toBe('#111111');
    expect(cssVars['--page-description-color']).toBe('#222222');
    expect(cssVars['--widget-heading-color']).toBe('#333333');
    expect(cssVars['--widget-body-color']).toBe('#444444');
    expect(cssVars['--color-accent-primary']).toBe('#123456');
    expect(cssVars['--profile-image-radius']).toBe('15%'); // default
  });

  it('includes preview profile image when provided', () => {
    const cssVars = generatePreviewCSSVars(baseThemeData, 'https://example.com/image.png');
    expect(cssVars['--preview-profile-image-url']).toContain('https://example.com/image.png');
  });
});


