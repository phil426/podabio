<?php
/**
 * Admin Panel - Analytics Dashboard
 * PodaBio - System-wide analytics
 */

require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/index.php';

requireAdmin();

// Get analytics data
$period = sanitizeInput($_GET['period'] ?? '30'); // days
$daysAgo = (int)$period;

// Overall stats
$totalPageViews = (int)fetchOne("SELECT COUNT(*) as count FROM analytics WHERE event_type = 'view'")['count'] ?? 0;
$totalLinkClicks = (int)fetchOne("SELECT COUNT(*) as count FROM analytics WHERE event_type = 'click'")['count'] ?? 0;
$totalSubscriptions = (int)fetchOne("SELECT COUNT(*) as count FROM email_subscriptions WHERE status = 'confirmed'")['count'] ?? 0;

// Recent analytics (last N days)
$recentViews = fetchAll(
    "SELECT DATE(created_at) as date, COUNT(*) as views 
     FROM analytics 
     WHERE event_type = 'view' 
       AND created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
     GROUP BY DATE(created_at) 
     ORDER BY date DESC",
    [$daysAgo]
);

// Top pages
$topPages = fetchAll(
    "SELECT p.username, p.podcast_name, 
            SUM(CASE WHEN a.event_type = 'view' THEN 1 ELSE 0 END) as views,
            SUM(CASE WHEN a.event_type = 'click' THEN 1 ELSE 0 END) as clicks
     FROM analytics a
     JOIN pages p ON a.page_id = p.id
     WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)
     GROUP BY a.page_id, p.username, p.podcast_name
     ORDER BY views DESC
     LIMIT 20",
    [$daysAgo]
);

$csrfToken = generateCSRFToken();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - Admin - <?php echo h(APP_NAME); ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; background: #f5f7fa; color: #333; }
        .admin-header { background: #1f2937; color: white; padding: 1rem 2rem; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .admin-header-content { max-width: 1400px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .admin-header h1 { font-size: 1.5rem; color: #667eea; }
        .admin-nav { display: flex; gap: 1rem; }
        .admin-nav a { color: white; text-decoration: none; padding: 0.5rem 1rem; border-radius: 6px; transition: background 0.3s; }
        .admin-nav a:hover, .admin-nav a.active { background: #374151; }
        .admin-container { max-width: 1400px; margin: 0 auto; padding: 2rem; }
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: white; padding: 1.5rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .stat-card h3 { font-size: 0.875rem; color: #6b7280; text-transform: uppercase; margin-bottom: 0.5rem; }
        .stat-card .value { font-size: 2rem; font-weight: 700; color: #1f2937; }
        .section { background: white; padding: 2rem; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1); margin-bottom: 2rem; }
        .section h2 { font-size: 1.5rem; margin-bottom: 1.5rem; color: #1f2937; }
        table { width: 100%; border-collapse: collapse; }
        table th, table td { padding: 0.75rem; text-align: left; border-bottom: 1px solid #e5e7eb; }
        table th { font-weight: 600; color: #374151; background: #f9fafb; }
        .filter-tabs { display: flex; gap: 0.5rem; margin-bottom: 1.5rem; }
        .filter-tabs a { padding: 0.5rem 1rem; border-radius: 6px; text-decoration: none; background: #f3f4f6; color: #374151; }
        .filter-tabs a.active { background: #667eea; color: white; }
    </style>
</head>
<body>
    <header class="admin-header">
        <div class="admin-header-content">
            <h1>Admin Panel - <?php echo h(APP_NAME); ?></h1>
            <nav class="admin-nav">
                <a href="/admin/">Dashboard</a>
                <a href="/admin/users.php">Users</a>
                <a href="/admin/pages.php">Pages</a>
                <a href="/admin/subscriptions.php">Subscriptions</a>
                <a href="/admin/analytics.php" class="active">Analytics</a>
                <a href="/admin/blog.php">Blog</a>
                <a href="/admin/support.php">Support</a>
                <a href="/admin/settings.php">Settings</a>
                <a href="/admin/select-panel.php">Switch Panel</a>
                <a href="/logout.php">Logout</a>
            </nav>
        </div>
    </header>
    
    <div class="admin-container">
        <h2 style="margin-bottom: 2rem; color: #1f2937;">Analytics Dashboard</h2>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Page Views</h3>
                <div class="value"><?php echo number_format($totalPageViews); ?></div>
            </div>
            <div class="stat-card">
                <h3>Total Link Clicks</h3>
                <div class="value"><?php echo number_format($totalLinkClicks); ?></div>
            </div>
            <div class="stat-card">
                <h3>Email Subscriptions</h3>
                <div class="value"><?php echo number_format($totalSubscriptions); ?></div>
            </div>
        </div>
        
        <div class="section">
            <div class="filter-tabs">
                <a href="/admin/analytics.php?period=7" class="<?php echo $period == '7' ? 'active' : ''; ?>">Last 7 Days</a>
                <a href="/admin/analytics.php?period=30" class="<?php echo $period == '30' ? 'active' : ''; ?>">Last 30 Days</a>
                <a href="/admin/analytics.php?period=90" class="<?php echo $period == '90' ? 'active' : ''; ?>">Last 90 Days</a>
            </div>
            
            <h2>Top Pages (Last <?php echo $period; ?> Days)</h2>
            <table>
                <thead>
                    <tr>
                        <th>Username</th>
                        <th>Podcast</th>
                        <th>Views</th>
                        <th>Clicks</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($topPages as $topPage): ?>
                        <tr>
                            <td><?php echo h($topPage['username']); ?></td>
                            <td><?php echo h($topPage['podcast_name'] ?? 'N/A'); ?></td>
                            <td><?php echo number_format($topPage['views']); ?></td>
                            <td><?php echo number_format($topPage['clicks']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>

