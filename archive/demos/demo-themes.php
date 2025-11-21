<?php
/**
 * Gradient Themes Demo Page
 * Showcases 5 different gradient-heavy themes for user pages
 */

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define APP_NAME if constants.php fails
if (!defined('APP_NAME')) {
    define('APP_NAME', 'PodaBio');
}

// Simple HTML escape function if helpers.php fails
if (!function_exists('h')) {
    function h($string) {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }
}

// Define 5 gradient-heavy themes
$themes = [
    [
        'name' => 'Sunset Dreams',
        'description' => 'Warm sunset gradient with orange to pink transitions',
        'page_background' => 'linear-gradient(135deg, #ff6b6b 0%, #ff8e53 25%, #ff6b9d 50%, #c44569 75%, #6c5ce7 100%)',
        'primary_color' => '#ffffff',
        'secondary_color' => '#ff6b6b',
        'accent_color' => '#ffd93d',
        'widget_background' => 'rgba(255, 255, 255, 0.15)',
        'widget_border' => 'rgba(255, 255, 255, 0.3)',
        'text_color' => '#ffffff',
        'text_secondary' => 'rgba(255, 255, 255, 0.9)',
        'font' => 'Poppins',
        'button_radius' => '12px',
        'button_style' => 'rgba(255, 255, 255, 0.2)'
    ],
    [
        'name' => 'Ocean Depths',
        'description' => 'Deep blue ocean gradient with cyan accents',
        'page_background' => 'linear-gradient(135deg, #0a192f 0%, #172a45 20%, #1e3a5f 40%, #2d5a87 60%, #4a90e2 80%, #5dade2 100%)',
        'primary_color' => '#ffffff',
        'secondary_color' => '#0a192f',
        'accent_color' => '#5dade2',
        'widget_background' => 'rgba(255, 255, 255, 0.1)',
        'widget_border' => 'rgba(93, 173, 226, 0.4)',
        'text_color' => '#ffffff',
        'text_secondary' => 'rgba(255, 255, 255, 0.85)',
        'font' => 'Inter',
        'button_radius' => '8px',
        'button_style' => 'rgba(93, 173, 226, 0.25)'
    ],
    [
        'name' => 'Purple Haze',
        'description' => 'Vibrant purple to magenta gradient',
        'page_background' => 'linear-gradient(135deg, #667eea 0%, #764ba2 25%, #f093fb 50%, #f5576c 75%, #4facfe 100%)',
        'primary_color' => '#ffffff',
        'secondary_color' => '#667eea',
        'accent_color' => '#f093fb',
        'widget_background' => 'rgba(255, 255, 255, 0.2)',
        'widget_border' => 'rgba(255, 255, 255, 0.35)',
        'text_color' => '#ffffff',
        'text_secondary' => 'rgba(255, 255, 255, 0.9)',
        'font' => 'Montserrat',
        'button_radius' => '16px',
        'button_style' => 'rgba(255, 255, 255, 0.25)'
    ],
    [
        'name' => 'Forest Canopy',
        'description' => 'Rich green gradient from emerald to teal',
        'page_background' => 'linear-gradient(135deg, #134e5e 0%, #1e7e56 25%, #2d8659 50%, #3d9970 75%, #4ecdc4 100%)',
        'primary_color' => '#ffffff',
        'secondary_color' => '#134e5e',
        'accent_color' => '#4ecdc4',
        'widget_background' => 'rgba(255, 255, 255, 0.12)',
        'widget_border' => 'rgba(78, 205, 196, 0.4)',
        'text_color' => '#ffffff',
        'text_secondary' => 'rgba(255, 255, 255, 0.88)',
        'font' => 'Raleway',
        'button_radius' => '6px',
        'button_style' => 'rgba(78, 205, 196, 0.2)'
    ],
    [
        'name' => 'Cosmic Night',
        'description' => 'Deep space gradient with cosmic purple and blue',
        'page_background' => 'linear-gradient(135deg, #0c0c0c 0%, #1a1a2e 20%, #16213e 40%, #0f3460 60%, #533483 80%, #8b5fbf 100%)',
        'primary_color' => '#ffffff',
        'secondary_color' => '#0c0c0c',
        'accent_color' => '#8b5fbf',
        'widget_background' => 'rgba(139, 95, 191, 0.15)',
        'widget_border' => 'rgba(139, 95, 191, 0.35)',
        'text_color' => '#ffffff',
        'text_secondary' => 'rgba(255, 255, 255, 0.85)',
        'font' => 'Orbitron',
        'button_radius' => '20px',
        'button_style' => 'rgba(139, 95, 191, 0.2)'
    ],
    [
        'name' => 'Golden Hour',
        'description' => 'Warm golden to amber gradient with rich yellows',
        'page_background' => 'linear-gradient(135deg, #f093fb 0%, #f5576c 15%, #ffd89b 30%, #ffd700 50%, #ffa500 70%, #ff8c00 100%)',
        'primary_color' => '#1a1a1a',
        'secondary_color' => '#ffd700',
        'accent_color' => '#ff6b35',
        'widget_background' => 'rgba(255, 255, 255, 0.2)',
        'widget_border' => 'rgba(255, 215, 0, 0.4)',
        'text_color' => '#1a1a1a',
        'text_secondary' => 'rgba(26, 26, 26, 0.85)',
        'font' => 'Playfair Display',
        'button_radius' => '10px',
        'button_style' => 'rgba(255, 107, 53, 0.3)'
    ],
    [
        'name' => 'Neon Dreams',
        'description' => 'Vibrant neon cyan to electric blue gradient',
        'page_background' => 'linear-gradient(135deg, #00f5ff 0%, #00d4ff 20%, #00b4ff 40%, #0099ff 60%, #0077ff 80%, #0055ff 100%)',
        'primary_color' => '#000000',
        'secondary_color' => '#00f5ff',
        'accent_color' => '#ff00ff',
        'widget_background' => 'rgba(0, 0, 0, 0.3)',
        'widget_border' => 'rgba(0, 245, 255, 0.6)',
        'text_color' => '#ffffff',
        'text_secondary' => 'rgba(255, 255, 255, 0.9)',
        'font' => 'Roboto',
        'button_radius' => '14px',
        'button_style' => 'rgba(255, 0, 255, 0.3)'
    ],
    [
        'name' => 'Autumn Leaves',
        'description' => 'Rich autumn colors from burgundy to golden brown',
        'page_background' => 'linear-gradient(135deg, #8b0000 0%, #a0522d 20%, #cd853f 40%, #daa520 60%, #d2691e 80%, #b8860b 100%)',
        'primary_color' => '#ffffff',
        'secondary_color' => '#8b0000',
        'accent_color' => '#daa520',
        'widget_background' => 'rgba(255, 255, 255, 0.18)',
        'widget_border' => 'rgba(218, 165, 32, 0.5)',
        'text_color' => '#ffffff',
        'text_secondary' => 'rgba(255, 255, 255, 0.9)',
        'font' => 'Merriweather',
        'button_radius' => '8px',
        'button_style' => 'rgba(218, 165, 32, 0.25)'
    ],
    [
        'name' => 'Arctic Chill',
        'description' => 'Cool icy blues and whites with frosty accents',
        'page_background' => 'linear-gradient(135deg, #e0f7fa 0%, #b2ebf2 20%, #80deea 40%, #4dd0e1 60%, #26c6da 80%, #00bcd4 100%)',
        'primary_color' => '#01579b',
        'secondary_color' => '#e0f7fa',
        'accent_color' => '#00bcd4',
        'widget_background' => 'rgba(255, 255, 255, 0.4)',
        'widget_border' => 'rgba(0, 188, 212, 0.5)',
        'text_color' => '#01579b',
        'text_secondary' => 'rgba(1, 87, 155, 0.85)',
        'font' => 'Lato',
        'button_radius' => '18px',
        'button_style' => 'rgba(0, 188, 212, 0.3)'
    ]
];

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gradient Themes Demo - <?php echo h(APP_NAME); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&family=Inter:wght@400;600;700&family=Montserrat:wght@400;600;700&family=Raleway:wght@400;600;700&family=Orbitron:wght@400;600;700&family=Playfair+Display:wght@400;600;700&family=Roboto:wght@400;600;700&family=Merriweather:wght@400;600;700&family=Lato:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: #f5f7fa;
            padding: 2rem 1rem;
            line-height: 1.6;
        }

        .demo-header {
            text-align: center;
            max-width: 800px;
            margin: 0 auto 3rem;
            padding: 2rem;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .demo-header h1 {
            font-size: 2.5rem;
            color: #1a1a2e;
            margin-bottom: 0.5rem;
        }

        .demo-header p {
            color: #666;
            font-size: 1.1rem;
        }

        .style-guides-container {
            max-width: 1600px;
            margin: 3rem auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(700px, 1fr));
            gap: 2.5rem;
            padding: 0 1rem;
        }

        .style-guide-section {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }

        .style-guide-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1a1a2e;
            margin-bottom: 0.5rem;
        }

        .style-guide-description {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 2rem;
        }

        .theme-layers-container {
            display: flex;
            flex-direction: column;
            gap: 0;
        }

        .theme-layer-item {
            display: flex;
            flex-direction: column;
            margin-bottom: 0;
            margin-top: 0;
        }

        .theme-layer-item.dragging {
            opacity: 0.5;
        }

        .theme-layer-item.drag-over {
            border-top: 2px solid #0066ff;
        }

        .style-guide-toggle {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            width: 100%;
            padding: 0.875rem 1.25rem;
            background: white;
            border: 2px solid #e0e0e0;
            border-top: none;
            border-radius: 0;
            cursor: pointer;
            transition: all 0.2s ease;
            margin-bottom: 0;
            margin-top: -2px;
            font-size: 1rem;
            font-weight: 600;
            color: #333;
            position: relative;
        }

        .theme-layer-item:first-child .style-guide-toggle {
            border-top: 2px solid #e0e0e0;
            border-radius: 12px 12px 0 0;
            margin-top: 0;
        }

        .theme-layer-item:last-child .style-guide-toggle {
            border-radius: 0 0 12px 12px;
        }

        .theme-layer-item:only-child .style-guide-toggle {
            border-top: 2px solid #e0e0e0;
            border-radius: 12px;
        }

        .drag-handle {
            cursor: grab;
            color: #999;
            font-size: 1.2rem;
            user-select: none;
            padding: 0 0.25rem;
        }

        .drag-handle:active {
            cursor: grabbing;
        }

        .style-guide-toggle:hover {
            border-color: #999;
            background: #f9f9f9;
        }

        .style-guide-toggle:hover .drag-handle {
            color: #666;
        }

        .toggle-color-chip {
            width: 24px;
            height: 24px;
            border-radius: 6px;
            flex-shrink: 0;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .toggle-theme-name {
            flex: 1;
            text-align: left;
        }

        .toggle-icon {
            font-size: 0.875rem;
            transition: transform 0.2s ease;
            color: #666;
        }

        .style-guide-toggle.collapsed .toggle-icon {
            transform: rotate(-90deg);
        }

        .style-guide-preview {
            border: 2px solid #f0f0f0;
            border-top: none;
            border-radius: 0;
            padding: 1.5rem;
            margin-top: 0;
            margin-bottom: 0;
            max-height: 2000px;
            transition: max-height 0.4s ease, padding 0.4s ease, margin 0.4s ease, border-width 0.4s ease, opacity 0.3s ease;
            overflow: hidden;
        }

        .theme-layer-item:last-child .style-guide-preview:not(.collapsed) {
            border-radius: 0 0 12px 12px;
        }

        .style-guide-preview.collapsed {
            max-height: 0;
            padding-top: 0;
            padding-bottom: 0;
            margin-bottom: 0;
            border-width: 0;
            opacity: 0;
        }

        .profile-picture-frame {
            margin-bottom: 1.5rem;
        }

        .profile-picture-title-container {
            display: inline-block;
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 6px;
            padding: 0.35rem 0.6rem;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
            margin-bottom: 0.75rem;
        }

        .profile-picture-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: #333;
            margin: 0 0 0.75rem 0;
            padding-left: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            white-space: nowrap;
        }

        .profile-picture-container {
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .profile-picture {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            border: 3px solid;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            color: white;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
            position: relative;
            overflow: hidden;
        }

        .profile-picture-icon {
            z-index: 1;
        }

        .color-palette {
            margin-bottom: 1.5rem;
        }

        .color-palette-panel {
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 8px;
            padding: 0.75rem;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .color-palette-title {
            font-size: 0.9rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
        }

        .color-swatches {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            justify-content: center;
        }

        .color-swatch {
            width: 50px;
            height: 50px;
            border-radius: 4px;
            border: 1px solid rgba(0, 0, 0, 0.15);
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
            flex-shrink: 0;
        }

        .color-swatch-wrapper {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.4rem;
        }

        .color-swatch-label {
            font-size: 0.65rem;
            font-weight: 600;
            color: #333;
            text-align: center;
            margin-top: 0.25rem;
        }

        .typography-section {
            margin-bottom: 1.5rem;
        }

        .typography-title-container {
            display: inline-block;
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 6px;
            padding: 0.35rem 0.6rem;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
            margin-bottom: 0.75rem;
        }

        .typography-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: #333;
            margin: 0 0 0.75rem 0;
            padding-left: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            white-space: nowrap;
        }

        .typography-sample {
            padding: 0.75rem;
            background: #f9fafb;
            border-radius: 6px;
            margin-bottom: 0.5rem;
        }

        .typography-sample h1 {
            font-size: 1.5rem;
            font-weight: 700;
            margin: 0 0 0.5rem 0;
        }

        .typography-sample h2 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 0 0 0.5rem 0;
        }

        .typography-sample p {
            font-size: 1rem;
            margin: 0;
            line-height: 1.6;
        }

        .button-section {
            margin-bottom: 1.5rem;
        }

        .button-title-container {
            display: inline-block;
            background: rgba(255, 255, 255, 0.95);
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 6px;
            padding: 0.35rem 0.6rem;
            box-shadow: 0 1px 4px rgba(0, 0, 0, 0.08);
            margin-bottom: 0.75rem;
        }

        .button-title {
            font-size: 0.85rem;
            font-weight: 700;
            color: #333;
            margin: 0 0 0.75rem 0;
            padding-left: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            white-space: nowrap;
        }

        .button-examples {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
        }

        .button-example {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.2s ease;
            border: 2px solid transparent;
        }

        .button-example:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .theme-selector {
            display: flex;
            gap: 0.75rem;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 1rem;
            padding-top: 1.5rem;
            border-top: 2px solid #f0f0f0;
        }

        .theme-selector-btn {
            padding: 0.75rem 1.5rem;
            border: 2px solid #e0e0e0;
            background: white;
            border-radius: 12px;
            cursor: pointer;
            font-weight: 600;
            font-size: 0.95rem;
            color: #333;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        /* Style Guide 1: Horizontal Layout - Compact */
        .style-guide-1 .style-guide-preview {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr 1fr;
            gap: 1rem;
            padding: 1rem;
            min-height: 200px;
        }

        .style-guide-1 .profile-picture-frame,
        .style-guide-1 .color-palette,
        .style-guide-1 .typography-section,
        .style-guide-1 .button-section {
            margin-bottom: 0;
        }

        .style-guide-1 .profile-picture-title,
        .style-guide-1 .typography-title,
        .style-guide-1 .button-title {
            font-size: 0.75rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
            padding-left: 0.5rem;
            letter-spacing: 0.8px;
        }

        .style-guide-1 .profile-picture {
            width: 60px;
            height: 60px;
            font-size: 1.5rem;
            border-width: 2px;
        }

        .style-guide-1 .color-palette-panel {
            padding: 0.5rem;
            background: rgba(255, 255, 255, 0.98);
        }

        .style-guide-1 .color-palette-title {
            font-size: 0.8rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.4rem;
            letter-spacing: 0.8px;
        }

        .style-guide-1 .color-swatches {
            gap: 0.5rem;
        }

        .style-guide-1 .color-swatch {
            min-width: 45px;
            width: 45px;
            height: 45px;
        }

        .style-guide-1 .color-swatch-wrapper {
            gap: 0.3rem;
        }

        .style-guide-1 .color-swatch-label {
            font-size: 0.6rem;
            margin-top: 0.2rem;
        }

        .style-guide-1 .typography-sample {
            padding: 0.5rem;
        }

        .style-guide-1 .typography-sample h1 {
            font-size: 1.1rem;
            margin-bottom: 0.3rem;
        }

        .style-guide-1 .typography-sample h2 {
            font-size: 0.95rem;
            margin-bottom: 0.3rem;
        }

        .style-guide-1 .typography-sample p {
            font-size: 0.8rem;
        }

        .style-guide-1 .button-examples {
            gap: 0.5rem;
        }

        .style-guide-1 .button-example {
            padding: 0.5rem 1rem;
            font-size: 0.8rem;
        }


        .theme-selector-btn:hover {
            border-color: #0066ff;
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0, 102, 255, 0.2);
        }

        .theme-selector-btn.active {
            background: linear-gradient(135deg, #0066ff 0%, #0052cc 100%);
            color: white;
            border-color: #0066ff;
            box-shadow: 0 4px 16px rgba(0, 102, 255, 0.3);
        }

        .theme-swatch {
            transition: all 0.3s ease;
        }

        .swatch-preview {
            width: 100%;
            height: 100%;
            display: flex;
            flex-direction: column;
            gap: 0.2rem;
            position: relative;
        }

        /* Hover effects for all swatch styles */
        .theme-selector-btn:hover .theme-swatch {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
            z-index: 10;
        }

        .theme-selector-btn.active .theme-swatch {
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 2px 12px rgba(255, 255, 255, 0.3);
        }

        .swatch-text {
            font-size: 0.5rem;
            font-weight: 600;
            line-height: 1;
            color: white;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.3);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .swatch-button {
            width: 100%;
            height: 0.75rem;
            background: rgba(255, 255, 255, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            font-size: 0.35rem;
            color: white;
            font-weight: 600;
            text-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
            white-space: nowrap;
            overflow: hidden;
        }

        .swatch-corner-indicator {
            position: absolute;
            bottom: 0.15rem;
            right: 0.15rem;
            width: 0.6rem;
            height: 0.6rem;
            background: rgba(255, 255, 255, 0.5);
            border: 1.5px solid rgba(255, 255, 255, 0.7);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.2);
        }

        .theme-selector-btn:hover .theme-swatch {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.25);
            z-index: 10;
        }

        .theme-selector-btn.active .theme-swatch {
            border-color: rgba(255, 255, 255, 0.5);
            box-shadow: 0 2px 12px rgba(255, 255, 255, 0.3);
        }

        .interactive-preview {
            max-width: 450px;
            margin: 2rem auto;
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 12px 48px rgba(0, 0, 0, 0.15);
            transition: all 0.4s ease;
        }

        .interactive-preview .theme-label {
            padding: 1rem 1.5rem;
            background: rgba(0, 0, 0, 0.05);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .interactive-preview .theme-label h2 {
            font-size: 1.5rem;
            color: #1a1a2e;
            margin-bottom: 0.25rem;
        }

        .interactive-preview .theme-label p {
            font-size: 0.9rem;
            color: #666;
        }

        .interactive-preview .theme-preview {
            min-height: 650px;
        }

        .themes-container {
            max-width: 1400px;
            margin: 0 auto;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(400px, 1fr));
            gap: 2rem;
        }

        .theme-demo {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.12);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .theme-demo:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 48px rgba(0, 0, 0, 0.18);
        }

        .theme-label {
            padding: 1rem 1.5rem;
            background: rgba(0, 0, 0, 0.05);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
        }

        .theme-label h2 {
            font-size: 1.5rem;
            color: #1a1a2e;
            margin-bottom: 0.25rem;
        }

        .theme-label p {
            font-size: 0.9rem;
            color: #666;
        }

        .theme-preview {
            min-height: 600px;
            padding: 2rem 1.5rem;
            position: relative;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .profile-section {
            text-align: center;
            margin-bottom: 2rem;
            width: 100%;
        }

        .profile-image {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            border: 4px solid rgba(255, 255, 255, 0.5);
            margin: 0 auto 1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            color: rgba(255, 255, 255, 0.9);
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.2);
        }

        .profile-name {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .profile-bio {
            font-size: 1rem;
            opacity: 0.9;
            max-width: 300px;
            margin: 0 auto;
        }

        .widgets-section {
            width: 100%;
            max-width: 320px;
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .demo-widget {
            padding: 1.25rem;
            border-radius: 12px;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 2px solid;
            transition: transform 0.2s ease;
            cursor: pointer;
        }

        .demo-widget:hover {
            transform: translateY(-2px);
        }

        .demo-widget-icon {
            font-size: 1.5rem;
            margin-bottom: 0.75rem;
        }

        .demo-widget-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
        }

        .demo-widget-desc {
            font-size: 0.9rem;
            opacity: 0.85;
        }

        .social-icons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1.5rem;
        }

        .social-icon {
            width: 44px;
            height: 44px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.2);
            border: 2px solid rgba(255, 255, 255, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            transition: all 0.2s ease;
            cursor: pointer;
        }

        .social-icon:hover {
            background: rgba(255, 255, 255, 0.3);
            transform: scale(1.1);
        }

        @media (max-width: 768px) {
            .swatch-styles-container {
                grid-template-columns: 1fr;
                gap: 2rem;
                padding: 0 1rem;
            }

            .swatch-style-section {
                padding: 1.5rem;
            }

            .themes-container {
                grid-template-columns: 1fr;
            }

            .demo-header h1 {
                font-size: 2rem;
            }

            .theme-preview {
                min-height: 500px;
            }

            .theme-selector {
                gap: 0.5rem;
            }

            .theme-selector-btn {
                font-size: 0.85rem;
                padding: 0.6rem 1rem;
            }

            .style-guides-container {
                grid-template-columns: 1fr;
                gap: 2rem;
                padding: 0 1rem;
            }

            .style-guide-section {
                padding: 1.5rem;
            }

            .style-guide-1 .style-guide-preview {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="demo-header">
        <h1>Theme Style Guide</h1>
        <p>Interactive style guide showing color palette, profile picture, typography, and button examples</p>
    </div>

    <div class="style-guides-container">
        <!-- Style Guide 1: Horizontal Layout -->
        <div class="style-guide-section style-guide-1">
            <h2 class="style-guide-title">Style Guide Preview</h2>
            <p class="style-guide-description">Four-column layout with profile picture, color palette, typography, and buttons</p>
            <div class="theme-layers-container" id="theme-layers-container">
            <?php foreach ($themes as $index => $theme): ?>
            <div class="theme-layer-item" draggable="true" data-index="<?php echo $index; ?>">
                <button class="style-guide-toggle collapsed" id="guide1-toggle-<?php echo $index; ?>" onclick="if (!event.target.closest('.drag-handle')) toggleStyleGuide('guide1-<?php echo $index; ?>')">
                    <span class="drag-handle" draggable="false">☰</span>
                    <span class="toggle-color-chip" style="background: <?php echo h($theme['page_background']); ?>;"></span>
                    <span class="toggle-theme-name"><?php echo h($theme['name']); ?></span>
                    <span class="toggle-icon">▼</span>
                </button>
                <div class="style-guide-preview collapsed" id="guide1-preview-<?php echo $index; ?>" style="background: <?php echo h($theme['page_background']); ?>; font-family: '<?php echo h($theme['font']); ?>', sans-serif;">
                <div class="profile-picture-frame">
                    <div class="profile-picture-title">Profile Picture</div>
                    <div class="profile-picture-container">
                        <div class="profile-picture" style="background: <?php echo h($theme['accent_color']); ?>; border-color: <?php echo h($theme['widget_border']); ?>;">
                            <i class="fas fa-user profile-picture-icon"></i>
                        </div>
                    </div>
                </div>
                <div class="color-palette">
                    <div class="color-palette-panel">
                        <div class="color-palette-title">Color Palette</div>
                        <div class="color-swatches">
                            <div class="color-swatch-wrapper">
                                <div class="color-swatch" style="background: <?php echo h($theme['primary_color']); ?>;"></div>
                                <div class="color-swatch-label">Primary</div>
                            </div>
                            <div class="color-swatch-wrapper">
                                <div class="color-swatch" style="background: <?php echo h($theme['secondary_color']); ?>;"></div>
                                <div class="color-swatch-label">Secondary</div>
                            </div>
                            <div class="color-swatch-wrapper">
                                <div class="color-swatch" style="background: <?php echo h($theme['accent_color']); ?>;"></div>
                                <div class="color-swatch-label">Accent</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="typography-section">
                    <div class="typography-title">Typography</div>
                    <div class="typography-sample" style="font-family: '<?php echo h($theme['font']); ?>', sans-serif; color: <?php echo h($theme['text_color']); ?>; background: <?php echo h($theme['widget_background']); ?>;">
                        <h1>Heading 1</h1>
                        <h2>Heading 2</h2>
                        <p>Body text sample</p>
                    </div>
                </div>
                <div class="button-section">
                    <div class="button-title">Buttons</div>
                    <div class="button-examples">
                        <button class="button-example" style="background: <?php echo h($theme['accent_color']); ?>; color: <?php echo h($theme['text_color']); ?>; border-radius: <?php echo h($theme['button_radius']); ?>;">Primary</button>
                        <button class="button-example" style="background: <?php echo h($theme['widget_background']); ?>; color: <?php echo h($theme['text_color']); ?>; border: 2px solid <?php echo h($theme['widget_border']); ?>; border-radius: <?php echo h($theme['button_radius']); ?>;">Secondary</button>
                    </div>
                </div>
            </div>
            </div>
            <?php endforeach; ?>
            </div>
        </div>

    </div>

    <!-- Interactive Preview -->
    <div class="interactive-preview" id="interactivePreview">
        <div class="theme-label">
            <h2 id="previewThemeName"><?php echo h($themes[0]['name']); ?></h2>
            <p id="previewThemeDesc"><?php echo h($themes[0]['description']); ?></p>
        </div>
        <div class="theme-preview" id="previewContent" 
             style="background: <?php echo h($themes[0]['page_background']); ?>; font-family: '<?php echo h($themes[0]['font']); ?>', sans-serif;">
            <div class="profile-section">
                <div class="profile-image">
                    <i class="fas fa-microphone"></i>
                </div>
                <div class="profile-name" id="previewName" style="color: <?php echo h($themes[0]['text_color']); ?>;">
                    Podcast Creator
                </div>
                <div class="profile-bio" id="previewBio" style="color: <?php echo h($themes[0]['text_secondary']); ?>;">
                    Sharing stories, insights, and conversations that matter. Join me on this journey.
                </div>
            </div>

            <div class="widgets-section">
                <div class="demo-widget" id="previewWidget1" 
                     style="background: <?php echo h($themes[0]['widget_background']); ?>; border-color: <?php echo h($themes[0]['widget_border']); ?>; color: <?php echo h($themes[0]['text_color']); ?>;">
                    <div class="demo-widget-icon">
                        <i class="fas fa-podcast"></i>
                    </div>
                    <div class="demo-widget-title">Latest Episode</div>
                    <div class="demo-widget-desc">Listen to our newest release</div>
                </div>

                <div class="demo-widget" id="previewWidget2" 
                     style="background: <?php echo h($themes[0]['widget_background']); ?>; border-color: <?php echo h($themes[0]['widget_border']); ?>; color: <?php echo h($themes[0]['text_color']); ?>;">
                    <div class="demo-widget-icon">
                        <i class="fas fa-envelope"></i>
                    </div>
                    <div class="demo-widget-title">Newsletter</div>
                    <div class="demo-widget-desc">Stay updated with our content</div>
                </div>

                <div class="demo-widget" id="previewWidget3" 
                     style="background: <?php echo h($themes[0]['widget_background']); ?>; border-color: <?php echo h($themes[0]['widget_border']); ?>; color: <?php echo h($themes[0]['text_color']); ?>;">
                    <div class="demo-widget-icon">
                        <i class="fas fa-link"></i>
                    </div>
                    <div class="demo-widget-title">Resources</div>
                    <div class="demo-widget-desc">Check out our curated links</div>
                </div>
            </div>

            <div class="social-icons">
                <div class="social-icon">
                    <i class="fab fa-spotify"></i>
                </div>
                <div class="social-icon">
                    <i class="fab fa-apple"></i>
                </div>
                <div class="social-icon">
                    <i class="fab fa-youtube"></i>
                </div>
                <div class="social-icon">
                    <i class="fab fa-twitter"></i>
                </div>
            </div>
        </div>
    </div>

    <div class="themes-container">
        <?php foreach ($themes as $index => $theme): ?>
        <div class="theme-demo">
            <div class="theme-label">
                <h2><?php echo h($theme['name']); ?></h2>
                <p><?php echo h($theme['description']); ?></p>
            </div>
            <div class="theme-preview" style="background: <?php echo h($theme['page_background']); ?>; font-family: '<?php echo h($theme['font']); ?>', sans-serif;">
                <div class="profile-section">
                    <div class="profile-image">
                        <i class="fas fa-microphone"></i>
                    </div>
                    <div class="profile-name" style="color: <?php echo h($theme['text_color']); ?>;">
                        Podcast Creator
                    </div>
                    <div class="profile-bio" style="color: <?php echo h($theme['text_secondary']); ?>;">
                        Sharing stories, insights, and conversations that matter. Join me on this journey.
                    </div>
                </div>

                <div class="widgets-section">
                    <div class="demo-widget" style="background: <?php echo h($theme['widget_background']); ?>; border-color: <?php echo h($theme['widget_border']); ?>; color: <?php echo h($theme['text_color']); ?>;">
                        <div class="demo-widget-icon">
                            <i class="fas fa-podcast"></i>
                        </div>
                        <div class="demo-widget-title">Latest Episode</div>
                        <div class="demo-widget-desc">Listen to our newest release</div>
                    </div>

                    <div class="demo-widget" style="background: <?php echo h($theme['widget_background']); ?>; border-color: <?php echo h($theme['widget_border']); ?>; color: <?php echo h($theme['text_color']); ?>;">
                        <div class="demo-widget-icon">
                            <i class="fas fa-envelope"></i>
                        </div>
                        <div class="demo-widget-title">Newsletter</div>
                        <div class="demo-widget-desc">Stay updated with our content</div>
                    </div>

                    <div class="demo-widget" style="background: <?php echo h($theme['widget_background']); ?>; border-color: <?php echo h($theme['widget_border']); ?>; color: <?php echo h($theme['text_color']); ?>;">
                        <div class="demo-widget-icon">
                            <i class="fas fa-link"></i>
                        </div>
                        <div class="demo-widget-title">Resources</div>
                        <div class="demo-widget-desc">Check out our curated links</div>
                    </div>
                </div>

                <div class="social-icons">
                    <div class="social-icon">
                        <i class="fab fa-spotify"></i>
                    </div>
                    <div class="social-icon">
                        <i class="fab fa-apple"></i>
                    </div>
                    <div class="social-icon">
                        <i class="fab fa-youtube"></i>
                    </div>
                    <div class="social-icon">
                        <i class="fab fa-twitter"></i>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <script>
        const themes = <?php echo json_encode($themes); ?>;
        
        function selectTheme(index, guideId, buttonElement) {
            const theme = themes[index];
            
            // Update active button only within the same style guide section
            const styleSection = buttonElement.closest('.style-guide-section');
            if (styleSection) {
                styleSection.querySelectorAll('.theme-selector-btn').forEach((btn, i) => {
                    if (i === index) {
                        btn.classList.add('active');
                    } else {
                        btn.classList.remove('active');
                    }
                });
            }
            
            // Update the style guide preview
            const preview = document.getElementById(guideId + '-preview');
            if (preview) {
                // Update background and font
                preview.style.background = theme.page_background;
                preview.style.fontFamily = `'${theme.font}', sans-serif`;
                
                // Update color swatches
                const colorSwatches = preview.querySelectorAll('.color-swatch');
                if (colorSwatches.length >= 3) {
                    colorSwatches[0].style.background = theme.primary_color;
                    colorSwatches[1].style.background = theme.secondary_color;
                    colorSwatches[2].style.background = theme.accent_color;
                }
                
                // Update typography samples
                const typographySamples = preview.querySelectorAll('.typography-sample');
                typographySamples.forEach(sample => {
                    sample.style.fontFamily = `'${theme.font}', sans-serif`;
                    sample.style.color = theme.text_color;
                    sample.style.background = theme.widget_background;
                });
                
                // Update buttons
                const buttons = preview.querySelectorAll('.button-example');
                buttons.forEach((btn, i) => {
                    if (i === 0) {
                        // Primary button
                        btn.style.background = theme.accent_color;
                        btn.style.color = theme.text_color;
                        btn.style.borderRadius = theme.button_radius;
                    } else {
                        // Secondary button
                        btn.style.background = theme.widget_background;
                        btn.style.color = theme.text_color;
                        btn.style.border = `2px solid ${theme.widget_border}`;
                        btn.style.borderRadius = theme.button_radius;
                    }
                });
                
                // Update profile picture
                const profilePicture = preview.querySelector('.profile-picture');
                if (profilePicture) {
                    profilePicture.style.background = theme.accent_color;
                    profilePicture.style.borderColor = theme.widget_border;
                }
            }
            
            // Update the toggle button
            const toggle = document.getElementById(guideId + '-toggle');
            if (toggle) {
                const colorChip = toggle.querySelector('.toggle-color-chip');
                const themeName = toggle.querySelector('.toggle-theme-name');
                if (colorChip) {
                    colorChip.style.background = theme.page_background;
                }
                if (themeName) {
                    themeName.textContent = theme.name;
                }
            }
        }
        
        function toggleStyleGuide(guideId) {
            // guideId format is 'guide1-0', need to extract base and index
            const lastDashIndex = guideId.lastIndexOf('-');
            const baseId = guideId.substring(0, lastDashIndex); // 'guide1'
            const index = guideId.substring(lastDashIndex + 1); // '0'
            
            const toggle = document.getElementById(baseId + '-toggle-' + index);
            const preview = document.getElementById(baseId + '-preview-' + index);
            
            if (toggle && preview) {
                const isCollapsed = preview.classList.contains('collapsed');
                
                // Close all other previews (accordion behavior)
                const allToggles = document.querySelectorAll('.style-guide-toggle');
                const allPreviews = document.querySelectorAll('.style-guide-preview');
                
                allToggles.forEach(t => {
                    if (t !== toggle) {
                        t.classList.add('collapsed');
                    }
                });
                
                allPreviews.forEach(p => {
                    if (p !== preview) {
                        p.classList.add('collapsed');
                    }
                });
                
                // Toggle the clicked one
                if (isCollapsed) {
                    // Expand
                    preview.classList.remove('collapsed');
                    toggle.classList.remove('collapsed');
                } else {
                    // Collapse
                    preview.classList.add('collapsed');
                    toggle.classList.add('collapsed');
                }
            }
        }

        // Drag and Drop functionality
        document.addEventListener('DOMContentLoaded', function() {
            const container = document.getElementById('theme-layers-container');
            if (!container) return;

            let draggedElement = null;
            let draggedIndex = null;

            // Make items draggable
            const items = container.querySelectorAll('.theme-layer-item');
            items.forEach((item, index) => {
                // Prevent drag on button click
                const button = item.querySelector('.style-guide-toggle');
                if (button) {
                    button.addEventListener('mousedown', function(e) {
                        if (e.target.closest('.drag-handle')) {
                            e.stopPropagation();
                        }
                    });
                }

                item.addEventListener('dragstart', function(e) {
                    draggedElement = this;
                    draggedIndex = index;
                    this.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                    e.dataTransfer.setData('text/html', this.innerHTML);
                });

                item.addEventListener('dragend', function(e) {
                    this.classList.remove('dragging');
                    items.forEach(item => item.classList.remove('drag-over'));
                });

                item.addEventListener('dragover', function(e) {
                    if (e.preventDefault) {
                        e.preventDefault();
                    }
                    e.dataTransfer.dropEffect = 'move';
                    return false;
                });

                item.addEventListener('dragenter', function(e) {
                    if (this !== draggedElement) {
                        this.classList.add('drag-over');
                    }
                });

                item.addEventListener('dragleave', function(e) {
                    this.classList.remove('drag-over');
                });

                item.addEventListener('drop', function(e) {
                    if (e.stopPropagation) {
                        e.stopPropagation();
                    }

                    if (draggedElement !== this) {
                        const allItems = Array.from(container.querySelectorAll('.theme-layer-item'));
                        const draggedItemIndex = allItems.indexOf(draggedElement);
                        const targetItemIndex = allItems.indexOf(this);

                        if (draggedItemIndex < targetItemIndex) {
                            container.insertBefore(draggedElement, this.nextSibling);
                        } else {
                            container.insertBefore(draggedElement, this);
                        }
                    }

                    this.classList.remove('drag-over');
                    return false;
                });
            });
        });
    </script>
</body>
</html>

