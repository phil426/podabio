<?php

/**
 * Default token bundle shared between the legacy editor and the new admin experience.
 */

return [
    'core' => [
        'color' => [
            'base' => [
                'slate-950' => '#04070f',
                'slate-900' => '#091227',
                'slate-850' => '#101b36',
                'slate-800' => '#172447',
                'slate-700' => '#212f59',
                'slate-300' => '#d6dff9',
                'slate-200' => '#e3e9ff',
                'slate-150' => '#ecf1ff',
                'slate-100' => '#f5f7ff',
                'slate-50' => '#fafcff',
                'white' => '#ffffff',
            ],
            'brand' => [
                'sunrise' => '#fbcf7d',
                'sky' => '#5fd7ff',
                'turquoise' => '#48f2d3',
                'magenta' => '#ff75d1',
                'violet' => '#9f8eff',
                'glow-amber' => '#f9a742',
            ],
            'alpha' => [
                'overlay-60' => 'rgba(9, 18, 39, 0.6)',
                'shadow-ambient' => 'rgba(9, 18, 39, 0.14)',
                'shadow-strong' => 'rgba(9, 18, 39, 0.28)',
                'night-70' => 'rgba(9, 18, 39, 0.72)',
                'night-50' => 'rgba(9, 18, 39, 0.55)',
                'white-80' => 'rgba(255, 255, 255, 0.85)',
                'white-60' => 'rgba(255, 255, 255, 0.62)',
                'midnight-70' => 'rgba(4, 12, 26, 0.72)',
            ],
        ],
        'typography' => [
            'font' => [
                'heading' => 'Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
                'body' => 'Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
                'metatext' => 'Inter, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
            ],
            'scale' => [
                'xl' => 2.488,
                'lg' => 1.777,
                'md' => 1.333,
                'sm' => 1.111,
                'xs' => 0.889,
            ],
            'weight' => [
                'normal' => 400,
                'medium' => 500,
                'semibold' => 600,
                'bold' => 700,
            ],
            'lineHeight' => [
                'tight' => 1.2,
                'normal' => 1.5,
                'relaxed' => 1.7,
            ],
        ],
        'space' => [
            'scale' => [
                '2xs' => 0.25,
                'xs' => 0.5,
                'sm' => 0.75,
                'md' => 1,
                'lg' => 1.5,
                'xl' => 2,
                '2xl' => 3,
            ],
        ],
        'shape' => [
            'radius' => [
                'none' => '0px',
                'sm' => '6px',
                'md' => '12px',
                'lg' => '18px',
                'pill' => '9999px',
            ],
            'borderWidth' => [
                'hairline' => '1px',
                'thin' => '2px',
                'thick' => '4px',
            ],
        ],
        'motion' => [
            'duration' => [
                'instant' => 80,
                'fast' => 150,
                'normal' => 250,
                'slow' => 400,
            ],
            'easing' => [
                'standard' => 'cubic-bezier(0.4, 0, 0.2, 1)',
                'decelerate' => 'cubic-bezier(0, 0, 0.2, 1)',
                'accelerate' => 'cubic-bezier(0.4, 0, 1, 1)',
            ],
        ],
        'elevation' => [
            'shadow' => [
                'level0' => 'none',
                'level1' => '0 8px 18px rgba(9, 18, 39, 0.12)',
                'level2' => '0 20px 48px rgba(9, 18, 39, 0.22)',
                'focus' => '0 0 0 3px rgba(95, 215, 255, 0.4)',
            ],
            'zIndex' => [
                'base' => 0,
                'panel' => 10,
                'overlay' => 100,
                'toaster' => 1000,
            ],
        ],
    ],
    'semantic' => [
        'surface' => [
            'page' => 'color.base.slate-50',
            'panel' => 'color.base.white',
            'canvas' => 'color.base.slate-100',
            'inverse' => 'color.base.slate-900',
            'overlay' => 'color.alpha.overlay-60',
        ],
        'text' => [
            'primary' => 'color.base.slate-900',
            'secondary' => 'color.alpha.night-70',
            'inverse' => 'color.base.white',
            'muted' => 'color.alpha.night-50',
            'accent' => 'color.brand.sky',
        ],
        'accent' => [
            'primary' => 'color.brand.sky',
            'secondary' => 'color.brand.magenta',
            'outline' => 'color.brand.turquoise',
        ],
        'state' => [
            'success' => '#24d3a3',
            'warning' => '#f8a947',
            'critical' => '#ff6188',
            'informational' => '#7f9cff',
        ],
        'density' => [
            'compact' => 0.85,
            'cozy' => 1,
            'comfortable' => 1.15,
        ],
        'focus' => [
            'ring' => 'core.elevation.shadow.focus',
            'halo' => 'color.alpha.shadow-strong',
        ],
        'divider' => [
            'subtle' => 'rgba(9, 18, 39, 0.08)',
            'strong' => 'rgba(9, 18, 39, 0.14)',
        ],
    ],
    'component' => [
        'layout' => [
            'topbar' => [
                'height' => '64px',
                'background' => 'semantic.surface.panel',
                'borderBottom' => 'semantic.divider.subtle',
            ],
            'left-rail' => [
                'minWidth' => '280px',
                'background' => 'semantic.surface.panel',
            ],
            'properties' => [
                'minWidth' => '340px',
                'background' => 'semantic.surface.panel',
            ],
        ],
        'button' => [
            'primary' => [
                'background' => 'semantic.accent.primary',
                'color' => 'semantic.text.inverse',
                'radius' => 'core.shape.radius.md',
            ],
            'ghost' => [
                'color' => 'semantic.text.primary',
                'radius' => 'core.shape.radius.md',
            ],
        ],
    ],
];

