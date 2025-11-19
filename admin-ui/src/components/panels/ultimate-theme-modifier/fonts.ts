/**
 * Available fonts categorized by style
 * All fonts are available via Google Fonts
 */

export interface FontCategory {
  label: string;
  fonts: string[];
}

export const FONT_CATEGORIES: FontCategory[] = [
  {
    label: 'Modern Fonts',
    fonts: [
      'Inter',
      'Poppins',
      'DM Sans',
      'Space Grotesk',
      'Outfit',
      'Manrope',
      'Plus Jakarta Sans',
      'Sora',
      'Figtree',
      'Geist',
      'Lexend',
      'Work Sans',
      'Urbanist',
      'Satoshi',
      'Clash Display',
      'Cabinet Grotesk',
      'Space Mono',
      'IBM Plex Sans',
      'JetBrains Mono',
      'Fira Code',
      'Crimson Pro',
      'Literata',
      'Alegreya',
      'Rubik',
      'Syne'
    ]
  },
  {
    label: 'Sans Serif Fonts',
    fonts: [
      'Roboto',
      'Open Sans',
      'Lato',
      'Montserrat',
      'Raleway',
      'Source Sans Pro',
      'Nunito',
      'Quicksand',
      'Muli',
      'Barlow',
      'Cabin',
      'Karla',
      'Asap',
      'PT Sans',
      'Titillium Web',
      'Josefin Sans',
      'Exo',
      'Bebas Neue',
      'Oswald',
      'Archivo',
      'Chivo',
      'Kanit',
      'Mukta',
      'Hind',
      'Noto Sans'
    ]
  },
  {
    label: 'Vintage Fonts',
    fonts: [
      'Playfair Display',
      'Cinzel',
      'Cormorant Garamond',
      'Libre Baskerville',
      'Merriweather',
      'Crimson Text',
      'Lora',
      'PT Serif',
      'Bitter',
      'Old Standard TT'
    ]
  },
  {
    label: 'Display Fonts',
    fonts: [
      'Bebas Neue',
      'Oswald',
      'Anton',
      'Righteous',
      'Bangers',
      'Lobster',
      'Pacifico',
      'Great Vibes',
      'Dancing Script',
      'Amatic SC'
    ]
  }
];

/**
 * Flattened list of all fonts for backward compatibility (deduplicated)
 */
export const ALL_FONTS: string[] = Array.from(
  new Set(FONT_CATEGORIES.flatMap(category => category.fonts))
);

/**
 * Get all fonts from a specific category
 */
export function getFontsByCategory(categoryLabel: string): string[] {
  const category = FONT_CATEGORIES.find(cat => cat.label === categoryLabel);
  return category?.fonts || [];
}

/**
 * Get the category for a specific font
 */
export function getFontCategory(fontName: string): string | null {
  for (const category of FONT_CATEGORIES) {
    if (category.fonts.includes(fontName)) {
      return category.label;
    }
  }
  return null;
}
