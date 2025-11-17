# Deployment Next Steps - poda.bio

## Status
✅ All deployment files have been created and committed to Git
✅ Code has been pushed to GitHub (main branch)
✅ React app has been built and is ready for deployment

## Manual Deployment Required

The deployment requires SSH password authentication. Please run the following steps:

### Option 1: Run Deployment Script (Recommended)

From your local machine, run:
```bash
./deploy_poda_bio.sh
```

You will be prompted for the SSH password: `[REDACTED]`

### Option 2: Manual SSH Deployment

If the script doesn't work, follow these steps:

#### Step 1: Connect to Server
```bash
ssh -p 65002 u925957603@195.179.237.142
# Enter password when prompted: [REDACTED]
```

#### Step 2: Initial Setup (First Time Only)

If this is the first deployment, you need to clone the repository:

```bash
cd /home/u925957603/domains/poda.bio/public_html/

# If directory is empty or doesn't have .git:
git clone https://github.com/phil426/podabio.git .

# If directory already has files, backup first, then:
git clone https://github.com/phil426/podabio.git temp
mv temp/* . 2>/dev/null || true
mv temp/.git . 2>/dev/null || true
rmdir temp
```

#### Step 3: Create Database Configuration

Create `config/database.php` with the following content:

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

#### Step 4: Set File Permissions

```bash
chmod 755 uploads/
chmod 755 uploads/profiles/ 2>/dev/null || mkdir -p uploads/profiles && chmod 755 uploads/profiles
chmod 755 uploads/backgrounds/ 2>/dev/null || mkdir -p uploads/backgrounds && chmod 755 uploads/backgrounds
chmod 755 uploads/thumbnails/ 2>/dev/null || mkdir -p uploads/thumbnails && chmod 755 uploads/thumbnails
chmod 755 uploads/blog/ 2>/dev/null || mkdir -p uploads/blog && chmod 755 uploads/blog
```

#### Step 5: Import Database Schema

```bash
# Run the database setup script
./database/setup_poda_bio.sh

# Or manually:
mysql -h srv775.hstgr.io -u u925957603_pab -p'[REDACTED]' u925957603_podabio < database/schema.sql
```

#### Step 6: Verify Deployment

1. Test PHP backend: https://poda.bio/index.php
2. Test admin panel: https://poda.bio/admin/react-admin.php
3. Check browser console for React app errors
4. Test database connectivity
5. Test file uploads

### Option 3: Use SSH Keys (Future)

For easier automated deployments, consider setting up SSH keys:

1. Generate SSH key pair (if you don't have one):
   ```bash
   ssh-keygen -t ed25519 -C "your_email@example.com"
   ```

2. Copy public key to server:
   ```bash
   ssh-copy-id -p 65002 u925957603@195.179.237.142
   ```

3. Then deployment script will work without password prompts

## Files Ready for Deployment

- ✅ `admin-ui/dist/` - Built React app
- ✅ `deploy_poda_bio.sh` - Deployment script
- ✅ `database/setup_poda_bio.sh` - Database setup script
- ✅ `docs/DEPLOYMENT_PODA_BIO.md` - Complete documentation

## Troubleshooting

If you encounter issues:

1. **SSH Connection Failed**: Verify password is correct
2. **Git Clone Failed**: Check repository URL and permissions
3. **Database Connection Failed**: Verify MySQL credentials in `config/database.php`
4. **React App Not Loading**: Check that `admin-ui/dist/` exists and has correct permissions

See `docs/DEPLOYMENT_PODA_BIO.md` for detailed troubleshooting.


