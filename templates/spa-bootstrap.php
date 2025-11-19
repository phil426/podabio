<?php
/**
 * SPA Bootstrap Template
 * HTML template for React admin dashboard entry point
 * PodaBio
 * 
 * @var string $title Page title
 * @var string|null $cssHref Production CSS stylesheet href (optional)
 * @var string $windowGlobals JavaScript code for window globals
 * @var bool $isDev Whether running in development mode
 * @var array $devScripts Array of dev mode script tag HTML strings
 * @var string|null $scriptSrc Entry script source URL
 */

?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo h($title ?? 'PodaBio User Dashboard'); ?></title>
    <?php if (!empty($cssHref)): ?>
        <link rel="stylesheet" href="<?php echo h($cssHref); ?>">
    <?php endif; ?>
    <!-- SPA Loader CSS (fallback styles) -->
    <link rel="stylesheet" href="/css/spa-loader.css?v=<?php echo filemtime(__DIR__ . '/../css/spa-loader.css'); ?>">
</head>
<body>
    <?php echo $windowGlobals ?? ''; ?>
    <div id="root">
        <div class="fallback">
            <p>Loading the new admin experience…</p>
        </div>
    </div>
    <?php if (!empty($isDev) && !empty($devScripts)): ?>
        <?php foreach ($devScripts as $script): ?>
            <?php echo $script; ?>
        <?php endforeach; ?>
    <?php endif; ?>
    <?php if (!empty($scriptSrc)): ?>
        <script type="module" src="<?php echo h($scriptSrc); ?>"></script>
    <?php endif; ?>
</body>
</html>

