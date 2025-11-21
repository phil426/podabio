# Color Debug Instructions

## Problem
Page title and body colors are not applying to the user's page.

## Debug Steps

1. **Change a color in the UI** - Go to Colors tab and change the page title or body color
2. **Wait for auto-save** - The theme should save automatically after 1 second
3. **Check PHP error log** - Look for these log messages:

### Expected Log Sequence:

#### Step 1: API Receives Data
```
THEME API UPDATE: Parsed theme_data JSON, typography_tokens.color.heading=#FF0000
THEME API UPDATE: typography_tokens.color.body=#00FF00
```

#### Step 2: Theme.php Saves Data
```
THEME UPDATE DEBUG: Saving typography_tokens.color.heading=#FF0000
THEME UPDATE DEBUG: Saving typography_tokens.color.body=#00FF00
```

#### Step 3: ThemeCSSGenerator Reads Data
```
THEME CSS DEBUG: typographyTokens={"color":{"heading":"#FF0000","body":"#00FF00"}}
THEME CSS DEBUG: Found typography_tokens.color.heading=#FF0000
THEME CSS DEBUG: Found typography_tokens.color.body=#00FF00
THEME CSS DEBUG: Using typography_tokens.color.heading=#FF0000 for --heading-font-color
THEME CSS DEBUG: Using typography_tokens.color.body=#00FF00 for --body-font-color
```

## Finding Your PHP Error Log

The error log location depends on your PHP setup:

### macOS (Homebrew PHP)
```bash
tail -f /usr/local/var/log/php-fpm.log
```

### macOS (System PHP)
```bash
tail -f /var/log/php_errors.log
```

### Linux
```bash
tail -f /var/log/php_errors.log
# or
tail -f /var/log/php-fpm/error.log
```

### Check PHP Configuration
```bash
php -i | grep error_log
```

## What to Look For

1. **If you see "NOT SET" in API logs**: The frontend isn't sending the colors
2. **If you see "NOT SET" in Theme.php logs**: The API isn't receiving the colors
3. **If you see "NOT FOUND" in CSS generator logs**: The database isn't saving or reading the colors correctly

## Quick Test

After changing a color, run this SQL query to check the database:

```sql
SELECT 
    id,
    name,
    JSON_EXTRACT(typography_tokens, '$.color.heading') as heading_color,
    JSON_EXTRACT(typography_tokens, '$.color.body') as body_color
FROM themes
WHERE user_id = YOUR_USER_ID
ORDER BY id DESC
LIMIT 1;
```

Replace `YOUR_USER_ID` with your actual user ID.

## Next Steps

Once you have the log output, share it and we can identify exactly where the data is being lost.

