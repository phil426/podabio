<?php
/**
 * Shopify API Client
 * Handles interactions with Shopify Storefront API
 * 
 * Documentation: https://shopify.dev/docs/api/storefront
 */

class ShopifyClient {
    
    private $shopDomain;
    private $storefrontAccessToken;
    private $apiVersion;
    
    /**
     * Get shop domain (public accessor)
     * @return string
     */
    public function getShopDomain() {
        return $this->shopDomain;
    }
    
    /**
     * Initialize Shopify client
     * @param string $shopDomain Your shop domain (e.g., 'your-shop.myshopify.com')
     * @param string $storefrontAccessToken Storefront API access token
     * @param string $apiVersion API version (default: '2024-01')
     */
    public function __construct($shopDomain = null, $storefrontAccessToken = null, $apiVersion = '2024-01') {
        // Load from config if not provided
        if ($shopDomain === null) {
            $shopDomain = defined('SHOPIFY_SHOP_DOMAIN') ? SHOPIFY_SHOP_DOMAIN : '';
        }
        if ($storefrontAccessToken === null) {
            $storefrontAccessToken = defined('SHOPIFY_STOREFRONT_TOKEN') ? SHOPIFY_STOREFRONT_TOKEN : '';
        }
        
        $this->shopDomain = $shopDomain;
        $this->storefrontAccessToken = $storefrontAccessToken;
        $this->apiVersion = $apiVersion;
    }
    
    /**
     * Make a GraphQL request to Shopify Storefront API
     * @param string $query GraphQL query
     * @param array $variables GraphQL variables
     * @return array Response data
     */
    public function graphql($query, $variables = []) {
        if (empty($this->shopDomain) || empty($this->storefrontAccessToken)) {
            return ['errors' => [['message' => 'Shopify credentials not configured']]];
        }
        
        $url = "https://{$this->shopDomain}/api/{$this->apiVersion}/graphql.json";
        
        $headers = [
            'Content-Type: application/json',
            'X-Shopify-Storefront-Access-Token: ' . $this->storefrontAccessToken
        ];
        
        $data = [
            'query' => $query,
            'variables' => $variables
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return ['errors' => [['message' => 'CURL error: ' . $error]]];
        }
        
        if ($httpCode !== 200) {
            return ['errors' => [['message' => "HTTP {$httpCode} error"]]];
        }
        
        $decoded = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return ['errors' => [['message' => 'Invalid JSON response']]];
        }
        
        return $decoded;
    }
    
    /**
     * Get a product by handle (slug)
     * @param string $handle Product handle
     * @return array|null Product data or null if not found
     */
    public function getProductByHandle($handle) {
        $query = '
            query getProduct($handle: String!) {
                product(handle: $handle) {
                    id
                    title
                    description
                    handle
                    vendor
                    productType
                    priceRange {
                        minVariantPrice {
                            amount
                            currencyCode
                        }
                    }
                    images(first: 1) {
                        edges {
                            node {
                                url
                                altText
                            }
                        }
                    }
                    variants(first: 1) {
                        edges {
                            node {
                                id
                                title
                                price {
                                    amount
                                    currencyCode
                                }
                                availableForSale
                            }
                        }
                    }
                }
            }
        ';
        
        $response = $this->graphql($query, ['handle' => $handle]);
        
        if (isset($response['errors'])) {
            error_log('Shopify API error: ' . json_encode($response['errors']));
            return null;
        }
        
        return $response['data']['product'] ?? null;
    }
    
    /**
     * Get multiple products
     * @param int $limit Number of products to fetch (max 250)
     * @param string|null $query Search query
     * @return array List of products
     */
    public function getProducts($limit = 10, $query = null) {
        $graphqlQuery = '
            query getProducts($first: Int!, $query: String) {
                products(first: $first, query: $query) {
                    edges {
                        node {
                            id
                            title
                            description
                            handle
                            vendor
                            productType
                            priceRange {
                                minVariantPrice {
                                    amount
                                    currencyCode
                                }
                            }
                            images(first: 1) {
                                edges {
                                    node {
                                        url
                                        altText
                                    }
                                }
                            }
                            variants(first: 1) {
                                edges {
                                    node {
                                        id
                                        title
                                        price {
                                            amount
                                            currencyCode
                                        }
                                        availableForSale
                                    }
                                }
                            }
                        }
                    }
                }
            }
        ';
        
        $variables = ['first' => min($limit, 250)];
        if ($query) {
            $variables['query'] = $query;
        }
        
        $response = $this->graphql($graphqlQuery, $variables);
        
        if (isset($response['errors'])) {
            error_log('Shopify API error: ' . json_encode($response['errors']));
            return [];
        }
        
        $products = [];
        if (isset($response['data']['products']['edges'])) {
            foreach ($response['data']['products']['edges'] as $edge) {
                $products[] = $edge['node'];
            }
        }
        
        return $products;
    }
    
    /**
     * Get a collection by handle
     * @param string $handle Collection handle
     * @param int $limit Number of products to fetch
     * @return array|null Collection data or null if not found
     */
    public function getCollectionByHandle($handle, $limit = 10) {
        $query = '
            query getCollection($handle: String!, $first: Int!) {
                collection(handle: $handle) {
                    id
                    title
                    description
                    handle
                    image {
                        url
                        altText
                    }
                    products(first: $first) {
                        edges {
                            node {
                                id
                                title
                                description
                                handle
                                vendor
                                productType
                                priceRange {
                                    minVariantPrice {
                                        amount
                                        currencyCode
                                    }
                                }
                                images(first: 1) {
                                    edges {
                                        node {
                                            url
                                            altText
                                        }
                                    }
                                }
                                variants(first: 1) {
                                    edges {
                                        node {
                                            id
                                            title
                                            price {
                                                amount
                                                currencyCode
                                            }
                                            availableForSale
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        ';
        
        $response = $this->graphql($query, ['handle' => $handle, 'first' => min($limit, 250)]);
        
        if (isset($response['errors'])) {
            error_log('Shopify API error: ' . json_encode($response['errors']));
            return null;
        }
        
        return $response['data']['collection'] ?? null;
    }
    
    /**
     * Format price for display
     * @param string $amount Price amount as string
     * @param string $currencyCode Currency code (e.g., 'USD')
     * @return string Formatted price
     */
    public static function formatPrice($amount, $currencyCode = 'USD') {
        $amount = floatval($amount);
        $symbols = [
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'CAD' => 'C$',
            'AUD' => 'A$',
            'JPY' => '¥'
        ];
        
        $symbol = $symbols[$currencyCode] ?? $currencyCode . ' ';
        return $symbol . number_format($amount, 2);
    }
}

