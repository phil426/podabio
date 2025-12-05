# Cloudflare for SaaS - Custom Domains Research

## Overview

**Cloudflare for SaaS** (formerly "SSL for SaaS") allows SaaS providers like PodaBio to offer custom domains to customers with automatic SSL provisioning. This eliminates the need for manual SSL certificate management.

---

## How It Works

### Architecture

```
┌─────────────────────────────────────────────────────────────────┐
│                      CUSTOMER'S DOMAIN                          │
│                    (mypodcast.com)                              │
├─────────────────────────────────────────────────────────────────┤
│  DNS Configuration (at their registrar):                        │
│  ┌─────────────────────────────────────────────────────────┐    │
│  │ CNAME: mypodcast.com → custom.poda.bio                  │    │
│  │   (points to your Cloudflare-proxied fallback origin)   │    │
│  └─────────────────────────────────────────────────────────┘    │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                      CLOUDFLARE EDGE                            │
│               (Automatic SSL Provisioning)                       │
├─────────────────────────────────────────────────────────────────┤
│  1. Receives request for mypodcast.com                          │
│  2. Checks Custom Hostname database                             │
│  3. Provisions/renews SSL certificate automatically             │
│  4. Routes to your origin server                                │
└─────────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────────┐
│                    PODA.BIO ORIGIN SERVER                       │
│                    (Hostinger 156.67.73.201)                    │
├─────────────────────────────────────────────────────────────────┤
│  - Receives request with Host header: mypodcast.com             │
│  - Looks up custom_domain in pages table                        │
│  - Serves the correct user's page                               │
└─────────────────────────────────────────────────────────────────┘
```

---

## Cloudflare for SaaS Features

### Included Features
- **Automatic SSL/TLS certificates** - Let's Encrypt or Cloudflare-issued
- **Automatic certificate renewal** - No manual intervention needed
- **DDoS protection** - Cloudflare's edge network protection
- **CDN caching** - Faster page loads globally
- **HTTP/2 and HTTP/3** - Modern protocol support
- **Custom hostname validation** - API for adding/verifying domains

### Validation Methods
1. **HTTP validation** - Cloudflare places a file on your origin
2. **TXT record validation** - Customer adds a TXT record
3. **Email validation** - Sent to domain admin contacts

---

## Pricing

| Tier | Custom Hostnames | Price |
|------|-----------------|-------|
| Free (with Pro plan) | 100 | $0 |
| Additional | 100+ | ~$0.10/hostname/month |
| Enterprise | Unlimited | Custom pricing |

**Requirement**: You need at least a Cloudflare **Pro plan** ($20/month) to enable SSL for SaaS.

---

## API Integration

### 1. Create a Fallback Origin

First, create a fallback origin hostname on your Cloudflare zone:

```
Fallback Origin: custom.poda.bio → 156.67.73.201
```

This is where traffic will route when a custom hostname is added.

### 2. Add a Custom Hostname (API Call)

```bash
curl -X POST "https://api.cloudflare.com/client/v4/zones/{zone_id}/custom_hostnames" \
  -H "Authorization: Bearer {api_token}" \
  -H "Content-Type: application/json" \
  --data '{
    "hostname": "mypodcast.com",
    "ssl": {
      "method": "http",
      "type": "dv",
      "settings": {
        "min_tls_version": "1.2"
      }
    }
  }'
```

### 3. Response

```json
{
  "result": {
    "id": "abc123",
    "hostname": "mypodcast.com",
    "ssl": {
      "status": "pending_validation",
      "method": "http",
      "type": "dv",
      "validation_records": [
        {
          "txt_name": "_cf-custom-hostname.mypodcast.com",
          "txt_value": "abc123xyz..."
        }
      ]
    },
    "status": "pending",
    "verification_errors": [],
    "ownership_verification": {
      "type": "txt",
      "name": "_cf-custom-hostname.mypodcast.com",
      "value": "abc123xyz..."
    }
  }
}
```

### 4. Check Status

```bash
curl "https://api.cloudflare.com/client/v4/zones/{zone_id}/custom_hostnames/{hostname_id}" \
  -H "Authorization: Bearer {api_token}"
```

Status values:
- `pending` - Waiting for DNS configuration
- `pending_validation` - SSL being provisioned
- `active` - Fully working
- `moved` - Domain moved away
- `deleted` - Removed

### 5. Delete a Custom Hostname

```bash
curl -X DELETE "https://api.cloudflare.com/client/v4/zones/{zone_id}/custom_hostnames/{hostname_id}" \
  -H "Authorization: Bearer {api_token}"
```

---

## Implementation Plan for PodaBio

### Phase 1: Cloudflare Setup (1-2 hours)

1. **Upgrade to Cloudflare Pro** ($20/month)
2. **Enable SSL for SaaS** in Cloudflare dashboard
3. **Create fallback origin**: `custom.poda.bio` → your server IP
4. **Generate API token** with Custom Hostname permissions

### Phase 2: Backend Integration (4-6 hours)

Create a new `CloudflareCustomHostnames.php` class:

```php
<?php
class CloudflareCustomHostnames {
    private string $zoneId;
    private string $apiToken;
    private string $apiBase = 'https://api.cloudflare.com/client/v4';
    
    public function __construct() {
        $this->zoneId = CLOUDFLARE_ZONE_ID;
        $this->apiToken = CLOUDFLARE_API_TOKEN;
    }
    
    /**
     * Add a custom hostname
     */
    public function addHostname(string $domain): array {
        $response = $this->request('POST', "/zones/{$this->zoneId}/custom_hostnames", [
            'hostname' => $domain,
            'ssl' => [
                'method' => 'http',
                'type' => 'dv'
            ]
        ]);
        
        return [
            'success' => $response['success'] ?? false,
            'hostname_id' => $response['result']['id'] ?? null,
            'status' => $response['result']['status'] ?? 'error',
            'verification' => $response['result']['ownership_verification'] ?? null
        ];
    }
    
    /**
     * Get hostname status
     */
    public function getStatus(string $hostnameId): array {
        return $this->request('GET', "/zones/{$this->zoneId}/custom_hostnames/{$hostnameId}");
    }
    
    /**
     * Delete a custom hostname
     */
    public function deleteHostname(string $hostnameId): bool {
        $response = $this->request('DELETE', "/zones/{$this->zoneId}/custom_hostnames/{$hostnameId}");
        return $response['success'] ?? false;
    }
    
    private function request(string $method, string $endpoint, array $data = []): array {
        $ch = curl_init($this->apiBase . $endpoint);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->apiToken,
                'Content-Type: application/json'
            ]
        ]);
        
        if ($method !== 'GET' && !empty($data)) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        return json_decode($response, true) ?? ['success' => false];
    }
}
```

### Phase 3: Database Updates

Add columns to `pages` table:

```sql
ALTER TABLE pages ADD COLUMN cloudflare_hostname_id VARCHAR(64) NULL;
ALTER TABLE pages ADD COLUMN custom_domain_status ENUM('pending', 'pending_validation', 'active', 'error') DEFAULT NULL;
ALTER TABLE pages ADD COLUMN custom_domain_verified_at DATETIME NULL;
```

### Phase 4: Frontend UI (6-8 hours)

Create `CustomDomainSettings.tsx` component with:
- Domain input field
- DNS instructions display
- Verification status with auto-refresh
- Error handling

### Phase 5: Server Configuration

Update `.htaccess` or nginx to handle custom domains:

```apache
# .htaccess - Handle custom domains
RewriteCond %{HTTP_HOST} !^(www\.)?poda\.bio$ [NC]
RewriteCond %{HTTP_HOST} !^localhost$ [NC]
RewriteRule ^(.*)$ /page.php?custom_domain_request=1 [L,QSA]
```

Update `page.php` to detect and serve custom domains.

---

## Alternative: Without Cloudflare

If you don't want to use Cloudflare, alternatives include:

| Solution | Pros | Cons |
|----------|------|------|
| **Let's Encrypt + Certbot** | Free, open source | Manual/cron job required, rate limits |
| **Caddy Server** | Automatic HTTPS built-in | Requires switching web servers |
| **AWS Certificate Manager** | Scalable, managed | Requires AWS infrastructure |
| **Fly.io** | Built-in custom domains | Requires platform migration |

---

## Recommended Approach for PodaBio

Given your current Hostinger setup:

1. **Short-term**: Use Cloudflare for SaaS
   - Minimal server changes needed
   - $20/month Pro plan is reasonable
   - Handles SSL complexity for you

2. **User experience**: BYOD (Bring Your Own Domain)
   - Users point CNAME to `custom.poda.bio`
   - Cloudflare handles SSL automatically
   - Status updates via API polling

3. **For domain purchases**: Affiliate links
   - Partner with Namecheap/Porkbun/Google Domains
   - "Need a domain?" → Affiliate link
   - No reseller complexity

---

## Next Steps

1. [ ] Sign up for Cloudflare Pro ($20/month)
2. [ ] Add poda.bio to Cloudflare (if not already)
3. [ ] Enable SSL for SaaS feature
4. [ ] Create `custom.poda.bio` fallback origin
5. [ ] Get API token with Custom Hostnames permissions
6. [ ] Implement `CloudflareCustomHostnames.php` class
7. [ ] Build frontend UI component
8. [ ] Test with a real domain

---

## Cost Analysis

| Item | Monthly Cost |
|------|--------------|
| Cloudflare Pro | $20 |
| First 100 custom hostnames | $0 (included) |
| Additional hostnames (101-1000) | ~$0.10 each |
| **Total for 100 Pro users** | **$20/month** |
| **Total for 500 Pro users** | **$60/month** |

This is very cost-effective compared to managing SSL certificates manually.

---

## References

- Cloudflare for SaaS Documentation: https://developers.cloudflare.com/cloudflare-for-platforms/cloudflare-for-saas/
- Custom Hostnames API: https://developers.cloudflare.com/api/operations/custom-hostname-for-a-zone-list-custom-hostnames
- SSL for SaaS Getting Started: https://developers.cloudflare.com/cloudflare-for-platforms/cloudflare-for-saas/start/getting-started/


