<?php
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$fullname = $_SESSION['fullname'];
$username = $_SESSION['username'];
$email    = $_SESSION['email'];
$role     = $_SESSION['role'] ?? 'user';

// Access control: only admins can view login info/activity logs
if ($role !== 'admin') {
    header("Location: schedules.php");
    exit;
}

try {
    // Fetch stats
    // 1. Total registered users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $total_users = $stmt->fetchColumn();

    // 2. Total successful logins
    $stmt = $pdo->query("SELECT COUNT(*) FROM activity_logs WHERE activity_type = 'LOGIN_SUCCESS'");
    $total_success_logins = $stmt->fetchColumn();

    // 3. Total failed login attempts
    $stmt = $pdo->query("SELECT COUNT(*) FROM activity_logs WHERE activity_type = 'LOGIN_FAILED'");
    $total_failed_logins = $stmt->fetchColumn();

    // 4. Total user schedules count
    $stmt = $pdo->query("SELECT COUNT(*) FROM schedules");
    $total_schedules_count = $stmt->fetchColumn();

    // Fetch activity logs
    $stmt = $pdo->query("SELECT * FROM activity_logs ORDER BY created_at DESC LIMIT 50");
    $logs = $stmt->fetchAll();

} catch (PDOException $e) {
    die("Database query error: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Logs | Admin Panel</title>
    <link rel="stylesheet" href="style.css">
    <script src="theme.js"></script>
</head>
<body>
    <div class="dashboard-container">
        
        <!-- Header -->
        <header class="dashboard-header">
            <div class="dashboard-user-info">
                <span>Admin Panel</span>
                <h2><?php echo htmlspecialchars($fullname); ?> (<?php echo htmlspecialchars($username); ?>)</h2>
            </div>
            
            <nav class="dashboard-nav">
                <a href="dashboard.php" class="nav-link active">Login Logs</a>
                <a href="admin_database.php" class="nav-link">User Schedules</a>
            </nav>

            <button id="theme-toggle-btn" class="theme-toggle-btn" title="Toggle theme"></button>
            
            <a href="logout.php" class="btn-logout">
                <span style="display: inline-flex; align-items: center; gap: 6px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                        <polyline points="16 17 21 12 16 7"></polyline>
                        <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    Logout
                </span>
            </a>
        </header>

        <!-- Main Grid -->
        <div class="dashboard-grid">
            
            <!-- Statistics Card -->
            <div class="dashboard-card">
                <h3>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--secondary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="18" y1="20" x2="18" y2="10"></line>
                        <line x1="12" y1="20" x2="12" y2="4"></line>
                        <line x1="6" y1="20" x2="6" y2="14"></line>
                    </svg>
                    System Statistics
                </h3>
                
                <div class="stats-row">
                    <div class="stat-box">
                        <div class="stat-val"><?php echo number_format($total_users); ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                    
                    <div class="stat-box">
                        <div class="stat-val"><?php echo number_format($total_success_logins); ?></div>
                        <div class="stat-label">Successful Logins</div>
                    </div>
                    
                    <div class="stat-box">
                        <div class="stat-val"><?php echo number_format($total_failed_logins); ?></div>
                        <div class="stat-label">Failed Attempts</div>
                    </div>

                    <div class="stat-box">
                        <div class="stat-val"><?php echo number_format($total_schedules_count); ?></div>
                        <div class="stat-label">Total Schedules</div>
                    </div>
                </div>
            </div>

            <!-- Activity Logs Card -->
            <div class="dashboard-card">
                <h3>
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                        <polyline points="14 2 14 8 20 8"></polyline>
                        <line x1="16" y1="13" x2="8" y2="13"></line>
                        <line x1="16" y1="17" x2="8" y2="17"></line>
                        <polyline points="10 9 9 9 8 9"></polyline>
                    </svg>
                    Authentication & Sign-In Logs
                </h3>
                
                <div class="table-wrapper">
                    <table>
                        <thead>
                            <tr>
                                <th>Target Username / Input</th>
                                <th>Event Type</th>
                                <th>IP Address</th>
                                <th>Browser / OS</th>
                                <th>Timestamp</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($logs) > 0): ?>
                                <?php foreach ($logs as $log): ?>
                                    <?php 
                                        $badge_class = '';
                                        $display_type = '';
                                        switch ($log['activity_type']) {
                                            case 'REGISTRATION':
                                                $badge_class = 'badge-reg';
                                                $display_type = 'Registration';
                                                break;
                                            case 'LOGIN_SUCCESS':
                                                $badge_class = 'badge-success';
                                                $display_type = 'Login Success';
                                                break;
                                            case 'LOGIN_FAILED':
                                                $badge_class = 'badge-failed';
                                                $display_type = 'Login Failed';
                                                break;
                                            case 'LOGOUT':
                                                $badge_class = 'badge-logout';
                                                $display_type = 'Logout';
                                                break;
                                            default:
                                                $badge_class = '';
                                                $display_type = htmlspecialchars($log['activity_type']);
                                        }

                                        // Shorten User Agent for display
                                        $ua = $log['user_agent'];
                                        $short_ua = 'Unknown';
                                        if (preg_match('/Chrome/i', $ua)) {
                                            $short_ua = 'Chrome';
                                        } elseif (preg_match('/Firefox/i', $ua)) {
                                            $short_ua = 'Firefox';
                                        } elseif (preg_match('/Safari/i', $ua)) {
                                            $short_ua = 'Safari';
                                        } elseif (preg_match('/Edge/i', $ua)) {
                                            $short_ua = 'Edge';
                                        } elseif (preg_match('/Postman/i', $ua)) {
                                            $short_ua = 'Postman';
                                        } else {
                                            $short_ua = substr($ua, 0, 20) . '...';
                                        }
                                    ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($log['username']); ?></strong></td>
                                        <td><span class="badge <?php echo $badge_class; ?>"><?php echo $display_type; ?></span></td>
                                        <td><code><?php echo htmlspecialchars($log['ip_address']); ?></code></td>
                                        <td title="<?php echo htmlspecialchars($log['user_agent']); ?>"><?php echo htmlspecialchars($short_ua); ?></td>
                                        <td><?php echo date('Y-m-d H:i:s', strtotime($log['created_at'])); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; color: var(--text-muted);">No activity logs available yet.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</body>
</html>
