<?php
/**
 * Favicon Generator
 * Generates a simple favicon for Podn.Bio
 */

header('Content-Type: image/x-icon');
header('Cache-Control: public, max-age=31536000');

// Create a simple 32x32 PNG image with a blue circle and white 'P'
$size = 32;
$image = imagecreatetruecolor($size, $size);
imagesavealpha($image, true);

// Create colors
$transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
$blue = imagecolorallocate($image, 0, 102, 255); // #0066ff
$white = imagecolorallocate($image, 255, 255, 255);

// Fill with transparent background
imagefill($image, 0, 0, $transparent);

// Draw circle
imagefilledellipse($image, $size/2, $size/2, $size-4, $size-4, $blue);

// Draw 'P' text (using a simple approach)
imagestring($image, 5, $size/2-6, $size/2-8, 'P', $white);

// Output as ICO (convert PNG to ICO format)
// Since PHP can't directly create ICO, we'll output PNG which browsers will accept
header('Content-Type: image/png');
imagepng($image);
imagedestroy($image);

