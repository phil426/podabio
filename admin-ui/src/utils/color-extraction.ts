

function rgbToHex(r: number, g: number, b: number): string {
    return '#' + [r, g, b].map(x => {
        const hex = x.toString(16);
        return hex.length === 1 ? '0' + hex : hex;
    }).join('');
}

/**
 * Generate a lighter tint of a hex color
 * @param hex The color hex code
 * @param factor 0-1 (0 is original, 1 is white)
 */
export function lightenColor(hex: string, factor: number): string {
    // Simple tint implementation
    let r = parseInt(hex.slice(1, 3), 16);
    let g = parseInt(hex.slice(3, 5), 16);
    let b = parseInt(hex.slice(5, 7), 16);

    r = Math.round(r + (255 - r) * factor);
    g = Math.round(g + (255 - g) * factor);
    b = Math.round(b + (255 - b) * factor);

    return rgbToHex(r, g, b);
}

/**
 * Generate a darker shade of a hex color
 * @param hex The color hex code
 * @param factor 0-1 (0 is original, 1 is black)
 */
export function darkenColor(hex: string, factor: number): string {
    let r = parseInt(hex.slice(1, 3), 16);
    let g = parseInt(hex.slice(3, 5), 16);
    let b = parseInt(hex.slice(5, 7), 16);

    r = Math.round(r * (1 - factor));
    g = Math.round(g * (1 - factor));
    b = Math.round(b * (1 - factor));

    return rgbToHex(r, g, b);
}

/**
 * Convert hex to RGB object
 */
export function hexToRgb(hex: string): { r: number, g: number, b: number } {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : { r: 0, g: 0, b: 0 };
}

/**
 * Calculate relative luminance (0-1)
 * https://www.w3.org/TR/WCAG20/#relativeluminancedef
 */
export function getLuminance(r: number, g: number, b: number): number {
    const [rs, gs, bs] = [r, g, b].map(c => {
        c = c / 255;
        return c <= 0.03928 ? c / 12.92 : Math.pow((c + 0.055) / 1.055, 2.4);
    });
    return 0.2126 * rs + 0.7152 * gs + 0.0722 * bs;
}

/**
 * Calculate saturation (0-1)
 */
export function getSaturation(r: number, g: number, b: number): number {
    const max = Math.max(r, g, b);
    const min = Math.min(r, g, b);
    const d = max - min;
    if (max === 0) return 0;
    return d / max;
}

/**
 * Calculate distance between two colors (0-765 approx)
 */
export function getColorDistance(hex1: string, hex2: string): number {
    const rgb1 = hexToRgb(hex1);
    const rgb2 = hexToRgb(hex2);
    return Math.sqrt(
        Math.pow(rgb1.r - rgb2.r, 2) +
        Math.pow(rgb1.g - rgb2.g, 2) +
        Math.pow(rgb1.b - rgb2.b, 2)
    );
}

/**
 * Filter out similar colors from a list
 * @param colors Array of hex strings
 * @param threshold Min distance to be considered distinct (default 40-50 is good)
 */
export function optimizeColorPalette(colors: string[], threshold = 45): string[] {
    const unique: string[] = [];

    // Prioritize high saturation and mid-range luminance (best for contrast)
    const scored = colors.map(hex => {
        const rgb = hexToRgb(hex);
        const sat = getSaturation(rgb.r, rgb.g, rgb.b);
        const lum = getLuminance(rgb.r, rgb.g, rgb.b);

        // Base Score: Saturation is good
        let score = sat * 100;

        // Contrast Tuning (User Request: "More Contrast")
        // Penalize very light colors heavily (hard to read on white)
        if (lum > 0.75) score -= 50;
        // Penalize very dark colors slightly (can look like black)
        if (lum < 0.1) score -= 20;

        // Sweet spot bonus: Colors that are dark enough to be readable but not black
        if (lum >= 0.2 && lum <= 0.6) score += 30;

        return { hex, score };
    }).sort((a, b) => b.score - a.score); // Descending score

    for (const item of scored) {
        // Check if similar color exists
        const isSimilar = unique.some(existing => getColorDistance(existing, item.hex) < threshold);
        if (!isSimilar) {
            unique.push(item.hex);
        }
    }

    // If we filtered too aggressively, fill back from original list (skipping duplicates)
    if (unique.length < 2 && colors.length > 2) {
        for (const c of colors) {
            if (!unique.includes(c)) unique.push(c);
            if (unique.length >= 3) break;
        }
    }

    return unique;
}
