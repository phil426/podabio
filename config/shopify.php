<?php
/**
 * Shopify Configuration
 * Podn.Bio
 * 
 * To set up Shopify integration:
 * 1. Go to your Shopify admin: Settings > Apps and sales channels > Develop apps
 * 2. Create a new app (or use existing)
 * 3. Enable "Storefront API" scopes
 * 4. Install the app and copy the Storefront API access token
 * 5. Update the values below
 * 
 * Documentation: https://shopify.dev/docs/api/storefront
 */

// Your shop domain (e.g., 'your-shop.myshopify.com' or 'your-shop.com')
define('SHOPIFY_SHOP_DOMAIN', '');

// Storefront API access token (from your Shopify app settings)
define('SHOPIFY_STOREFRONT_TOKEN', '');

// API version (default: '2024-01', update as needed)
define('SHOPIFY_API_VERSION', '2024-01');

