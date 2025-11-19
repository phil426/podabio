<?php
/**
 * Font Awesome Integration
 * PodaBio - Helper to include Font Awesome icons
 * 
 * Font Awesome 6.5.1 via CDN
 * Usage: Include this file in your page head or use the constant below
 */

// Font Awesome CDN URL
define('FONTAWESOME_CDN', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css');
define('FONTAWESOME_INTEGRITY', 'sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==');

/**
 * Output Font Awesome CDN link tag
 * @return void
 */
function fontawesome_link() {
    echo '<link rel="stylesheet" href="' . FONTAWESOME_CDN . '" integrity="' . FONTAWESOME_INTEGRITY . '" crossorigin="anonymous" referrerpolicy="no-referrer" />';
}

/**
 * Get Font Awesome icon HTML
 * @param string $icon Icon name (e.g., 'user', 'home', 'cog')
 * @param string $style Icon style: 'solid', 'regular', 'brands' (default: 'solid')
 * @param string $class Additional CSS classes
 * @return string HTML for icon
 */
function fa_icon($icon, $style = 'solid', $class = '') {
    $styleClass = 'fa-' . $style;
    $iconClass = 'fa-' . $icon;
    $classes = trim($styleClass . ' ' . $iconClass . ' ' . $class);
    return '<i class="' . h($classes) . '" aria-hidden="true"></i>';
}

