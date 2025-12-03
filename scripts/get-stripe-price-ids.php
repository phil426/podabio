<?php
/**
 * Get Stripe Price IDs from Product IDs
 * This script helps you find the Price IDs for your products
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/payments.php';

if (file_exists(__DIR__ . '/../config/local.php')) {
    require_once __DIR__ . '/../config/local.php';
}

// Load Stripe SDK
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
} else {
    die("❌ Stripe SDK not found. Run: composer install\n");
}

use Stripe\Stripe;
use Stripe\Product;
use Stripe\Price;

if (empty(STRIPE_SECRET_KEY)) {
    die("❌ STRIPE_SECRET_KEY is not set in config\n");
}

Stripe::setApiKey(STRIPE_SECRET_KEY);

echo "🔍 Fetching Price IDs from your Stripe products...\n\n";

// Product IDs you provided
$productIds = [
    'monthly' => 'prod_TXBda2HMchgZKn',
    'annual' => 'prod_TXBfPiqlba5CKi'
];

foreach ($productIds as $type => $productId) {
    try {
        $product = Product::retrieve($productId);
        echo "📦 Product: {$product->name} ({$productId})\n";
        
        // Get prices for this product
        $prices = Price::all(['product' => $productId, 'limit' => 10]);
        
        if (count($prices->data) > 0) {
            foreach ($prices->data as $price) {
                $amount = number_format($price->unit_amount / 100, 2);
                $interval = $price->recurring->interval ?? 'one-time';
                $intervalCount = $price->recurring->interval_count ?? 1;
                
                echo "   💰 Price ID: {$price->id}\n";
                echo "      Amount: \${$amount} / {$interval}";
                if ($intervalCount > 1) {
                    echo " (every {$intervalCount} {$interval}s)";
                }
                echo "\n";
                echo "      Active: " . ($price->active ? 'Yes' : 'No') . "\n";
                
                // Check if this matches our expected pricing
                $expectedAmount = $type === 'monthly' ? 4.99 : 53.89;
                $expectedInterval = $type === 'monthly' ? 'month' : 'year';
                
                if ($amount == $expectedAmount && $interval == $expectedInterval) {
                    echo "      ✅ MATCHES expected pricing!\n";
                    echo "\n";
                    echo "   Copy this to config/payments.php:\n";
                    echo "   define('STRIPE_PRO_" . strtoupper($type) . "_PRICE_ID', '{$price->id}');\n";
                }
                echo "\n";
            }
        } else {
            echo "   ⚠️  No prices found for this product\n";
        }
        
        echo "\n";
    } catch (\Stripe\Exception\ApiErrorException $e) {
        echo "   ❌ Error: " . $e->getMessage() . "\n\n";
    }
}

echo "✅ Done! Copy the Price IDs above to config/payments.php\n";

