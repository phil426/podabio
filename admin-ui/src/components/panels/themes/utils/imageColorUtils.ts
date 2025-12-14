
/**
 * Utility to extract prominent colors from an image
 */

export async function extractImageColors(imageUrl: string, maxColors: number = 5): Promise<string[]> {
    return new Promise((resolve, reject) => {
        const img = new Image();
        img.crossOrigin = 'Anonymous';
        img.src = imageUrl;

        img.onload = () => {
            try {
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                if (!ctx) {
                    resolve([]);
                    return;
                }

                // Resize to small dimension for faster processing and natural smoothing
                const size = 50;
                canvas.width = size;
                canvas.height = size;

                // Draw image
                ctx.drawImage(img, 0, 0, size, size);

                // Get pixel data
                const imageData = ctx.getImageData(0, 0, size, size).data;
                const colorCounts: Record<string, number> = {};

                // Iterate pixels (step by 4 for RGBA)
                for (let i = 0; i < imageData.length; i += 4) {
                    const r = imageData[i];
                    const g = imageData[i + 1];
                    const b = imageData[i + 2];
                    const a = imageData[i + 3];

                    // Skip transparent pixels
                    if (a < 128) continue;

                    // Quantize colors to reduce noise (round to nearest 24)
                    // This groups similar colors together
                    const bucketSize = 24;
                    const qr = Math.round(r / bucketSize) * bucketSize;
                    const qg = Math.round(g / bucketSize) * bucketSize;
                    const qb = Math.round(b / bucketSize) * bucketSize;

                    const key = `${Math.min(255, qr)},${Math.min(255, qg)},${Math.min(255, qb)}`;
                    colorCounts[key] = (colorCounts[key] || 0) + 1;
                }

                // Sort by frequency
                const sortedColors = Object.entries(colorCounts)
                    .sort(([, a], [, b]) => b - a)
                    .map(([key]) => key)
                    .slice(0, maxColors);

                // Convert key "r,g,b" to Hex
                const hexColors = sortedColors.map(rgbStr => {
                    const [r, g, b] = rgbStr.split(',').map(Number);
                    return '#' + [r, g, b].map(x => x.toString(16).padStart(2, '0')).join('');
                });

                resolve(hexColors);
            } catch (e) {
                console.error('Error extracting colors:', e);
                resolve([]);
            }
        };

        img.onerror = (e) => {
            console.error('Failed to load image for color extraction:', e);
            resolve([]);
        };
    });
}
