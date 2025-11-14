# Manual Deployment Instructions for poda.bio

The automated deployment is failing due to SSH authentication. Please follow these manual steps:

## Step 1: Connect to Server Manually

Open your terminal and run:

```bash
ssh -p 65002 u925957603@195.179.237.142
```

When prompted, enter the password: `[REDACTED]`

**Note:** If this is your first time connecting, type `yes` when asked to accept the host key.

## Step 2: Navigate to Project Directory

Once connected, run:

```bash
cd /home/u925957603/domains/poda.bio/public_html/
```

## Step 3: Check if Repository Exists

Check if Git is already set up:

```bash
ls -la .git
```

### Option A: If .git exists (repository already cloned)

Simply pull the latest code:

```bash
git pull origin main
```

### Option B: If .git doesn't exist (first deployment)

Clone the repository:

```bash
# If directory is empty:
git clone https://github.com/phil426/podn-bio.git .

# If directory has files, backup first:
git clone https://github.com/phil426/podn-bio.git temp
mv temp/* . 2>/dev/null || true
mv temp/.git . 2>/dev/null || true
rmdir temp
```

## Step 4: Create Database Configuration

Create the database config file:

```bash
nano config/database.php
```

Paste this content (use Ctrl+X, then Y, then Enter to save in nano):

```php
<?php
/**
 * Database Configuration
 * Podn.Bio - poda.bio Production
 */

// Database connection settings
define('DB_HOST', 'srv775.hstgr.io');
define('DB_NAME', 'u925957603_podabio');
define('DB_USER', 'u925957603_pab');
define('DB_PASS', '[REDACTED]');
define('DB_CHARSET', 'utf8mb4');

// PDO connection options
define('DB_OPTIONS', [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
]);

/**
 * Get database connection
 * @return PDO
 */
function getDB() {
    static $pdo = null;
    
    if ($pdo === null) {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $pdo = new PDO($dsn, DB_USER, DB_PASS, DB_OPTIONS);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed. Please try again later.");
        }
    }
    
    return $pdo;
}

/**
 * Execute a prepared statement
 * @param string $sql
 * @param array $params
 * @return PDOStatement
 */
function executeQuery($sql, $params = []) {
    $pdo = getDB();
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt;
}

/**
 * Fetch a single row
 * @param string $sql
 * @param array $params
 * @return array|false
 */
function fetchOne($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetch();
}

/**
 * Fetch all rows
 * @param string $sql
 * @param array $params
 * @return array
 */
function fetchAll($sql, $params = []) {
    $stmt = executeQuery($sql, $params);
    return $stmt->fetchAll();
}
```

## Step 5: Set File Permissions

```bash
chmod 755 uploads/ 2>/dev/null || mkdir -p uploads && chmod 755 uploads
chmod 755 uploads/profiles/ 2>/dev/null || mkdir -p uploads/profiles && chmod 755 uploads/profiles
chmod 755 uploads/backgrounds/ 2>/dev/null || mkdir -p uploads/backgrounds && chmod 755 uploads/backgrounds
chmod 755 uploads/thumbnails/ 2>/dev/null || mkdir -p uploads/thumbnails && chmod 755 uploads/thumbnails
chmod 755 uploads/blog/ 2>/dev/null || mkdir -p uploads/blog && chmod 755 uploads/blog
```

## Step 6: Import Database Schema (First Time Only)

If this is the first deployment, import the database:

```bash
mysql -h srv775.hstgr.io -u u925957603_pab -p'[REDACTED]' u925957603_podabio < database/schema.sql
```

Or run the setup script:

```bash
./database/setup_poda_bio.sh
```

## Step 7: Verify Deployment

Exit SSH (type `exit`) and test in your browser:

1. **PHP Backend**: https://poda.bio/index.php
2. **Admin Panel**: https://poda.bio/admin/react-admin.php
3. **Check browser console** for any React app errors
4. **Test file uploads** if possible

## Troubleshooting

### If password doesn't work:
- Verify password in Hostinger hPanel > SSH Access
- Password might have changed

### If Git pull fails:
- Check repository URL: `git remote -v`
- Verify you have access to the repository

### If database connection fails:
- Verify MySQL credentials in Hostinger hPanel
- Check that database exists: `u925957603_podabio`

### If React app doesn't load:
- Check that `admin-ui/dist/` directory exists
- Verify `admin-ui/dist/.vite/manifest.json` exists
- Check file permissions on `admin-ui/dist/`

## Future Deployments

After the first manual deployment, you can:

1. **Set up SSH keys** for passwordless authentication:
   ```bash
   ssh-copy-id -p 65002 u925957603@195.179.237.142
   ```

2. **Then use the automated script**:
   ```bash
   ./deploy_poda_bio.sh
   ```

Or continue with manual deployments:
```bash
ssh -p 65002 u925957603@195.179.237.142
cd /home/u925957603/domains/poda.bio/public_html/
git pull origin main
exit
```


