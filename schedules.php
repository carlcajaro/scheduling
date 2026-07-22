<?php
require_once 'config.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id  = $_SESSION['user_id'];
$fullname = $_SESSION['fullname'];
$username = $_SESSION['username'];
$role     = $_SESSION['role'] ?? 'user';

// Only regular users can access schedules
if ($role === 'admin') {
    header("Location: admin_database.php");
    exit;
}

// Ensure the schedules table exists
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS `schedules` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `subject` VARCHAR(100) NOT NULL,
        `room` VARCHAR(100) NOT NULL,
        `schedule_date` DATE NOT NULL,
        `start_time` TIME NOT NULL,
        `end_time` TIME NOT NULL,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
} catch (PDOException $e) {
    // Table may already exist, continue
}

$success = '';
$error   = '';

// Flash message from add_schedule.php or delete action
if (isset($_SESSION['sched_success'])) {
    $success = $_SESSION['sched_success'];
    unset($_SESSION['sched_success']);
}

// ── Handle Delete Schedule ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $delete_id = (int)($_POST['delete_id'] ?? 0);
    if ($delete_id > 0) {
        try {
            // Only allow deleting own schedules
            $stmt = $pdo->prepare("DELETE FROM schedules WHERE id = ? AND user_id = ?");
            $stmt->execute([$delete_id, $user_id]);
            $success = 'Schedule deleted successfully.';
        } catch (PDOException $e) {
            $error = 'Could not delete schedule: ' . $e->getMessage();
        }
    }
}

// ── Fetch Schedules ──────────────────────────────────────────────────────────
try {
    $stmt = $pdo->prepare("SELECT * FROM schedules WHERE user_id = ? ORDER BY schedule_date ASC, start_time ASC");
    $stmt->execute([$user_id]);
    $schedules = $stmt->fetchAll();
} catch (PDOException $e) {
    $schedules = [];
    $error = 'Could not load schedules: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Schedules | Control Center</title>
    <meta name="description" content="View and manage your personal schedules.">
    <link rel="stylesheet" href="style.css">
    <script src="theme.js"></script>
    <style>
        body { align-items: flex-start; }
        .sched-table-wrapper {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }
        .empty-state svg {
            margin-bottom: 16px;
            opacity: 0.4;
        }
        .empty-state p {
            font-size: 0.95rem;
            line-height: 1.6;
        }
        .sched-date-col {
            white-space: nowrap;
        }
        .tag-day {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            background: rgba(138, 43, 226, 0.15);
            color: #c084fc;
            border: 1px solid rgba(138, 43, 226, 0.3);
        }
        .filter-bar {
            display: flex;
            gap: 12px;
            margin-bottom: 18px;
            flex-wrap: wrap;
            align-items: center;
        }
        .filter-bar input,
        .filter-bar select {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            color: var(--text-primary);
            border-radius: 8px;
            padding: 8px 14px;
            font-size: 0.88rem;
            outline: none;
            transition: border-color 0.2s;
        }
        .filter-bar input:focus,
        .filter-bar select:focus { border-color: var(--primary); }
        .filter-bar input { flex: 1; min-width: 200px; }
        
        .btn-create-new {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: linear-gradient(135deg, var(--primary) 0%, var(--secondary) 100%);
            color: #fff;
            padding: 8px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.25s ease;
            margin-left: auto;
        }
        .btn-create-new:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(138, 43, 226, 0.3);
        }
        tbody tr:nth-child(even) { background: rgba(255,255,255,0.01); }
    </style>
</head>
<body>
<div class="dashboard-container">

    <!-- Header -->
    <header class="dashboard-header">
        <div class="dashboard-user-info">
            <span>Logged in as</span>
            <h2><?php echo htmlspecialchars($fullname); ?> (<?php echo htmlspecialchars($username); ?>)</h2>
        </div>

        <nav class="dashboard-nav">
            <a href="schedules.php" class="nav-link active">My Schedules</a>
            <a href="add_schedule.php" class="nav-link">Add Schedule</a>
        </nav>

        <button id="theme-toggle-btn" class="theme-toggle-btn" title="Toggle theme"></button>

        <a href="logout.php" class="btn-logout">
            <span style="display:inline-flex;align-items:center;gap:6px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
                Logout
            </span>
        </a>
    </header>

    <!-- Success & Error Alerts -->
    <?php if ($success): ?>
        <div class="alert alert-success" style="animation: fadeIn 0.4s ease;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:10px;flex-shrink:0;">
                <polyline points="20 6 9 17 4 12"></polyline>
            </svg>
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>
    <?php if ($error): ?>
        <div class="alert alert-error" style="animation: fadeIn 0.4s ease;">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:10px;flex-shrink:0;">
                <circle cx="12" cy="12" r="10"></circle>
                <line x1="12" y1="8" x2="12" y2="12"></line>
                <line x1="12" y1="16" x2="12.01" y2="16"></line>
            </svg>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Main Full-Width Schedules List Card -->
    <div style="animation: fadeIn 0.6s ease;">
        <div class="dashboard-card">
            <h3>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="8" y1="6" x2="21" y2="6"></line>
                    <line x1="8" y1="12" x2="21" y2="12"></line>
                    <line x1="8" y1="18" x2="21" y2="18"></line>
                    <line x1="3" y1="6" x2="3.01" y2="6"></line>
                    <line x1="3" y1="12" x2="3.01" y2="12"></line>
                    <line x1="3" y1="18" x2="3.01" y2="18"></line>
                </svg>
                My Schedules
                <span style="font-size:0.8rem;font-weight:400;color:var(--text-muted);margin-left:10px;">
                    (<?php echo count($schedules); ?> entr<?php echo count($schedules) !== 1 ? 'ies' : 'y'; ?>)
                </span>

                <a href="add_schedule.php" class="btn-create-new">
                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                    </svg>
                    Add New Schedule
                </a>
            </h3>

            <?php if (!empty($schedules)): ?>
                <!-- Filter Bar -->
                <div class="filter-bar">
                    <input type="text" id="search-input" placeholder="🔍 Search subject or room…" oninput="filterTable()">
                    <select id="filter-day" onchange="filterTable()">
                        <option value="">All Days</option>
                        <option>Monday</option>
                        <option>Tuesday</option>
                        <option>Wednesday</option>
                        <option>Thursday</option>
                        <option>Friday</option>
                        <option>Saturday</option>
                        <option>Sunday</option>
                    </select>
                </div>
            <?php endif; ?>

            <?php if (empty($schedules)): ?>
                <div class="empty-state">
                    <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="var(--secondary)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                    <p>No schedules added yet.<br>Click "Add New Schedule" to create your first schedule entry.</p>
                </div>
            <?php else: ?>
                <div class="sched-table-wrapper">
                    <table id="user-schedules-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Subject</th>
                                <th>Room</th>
                                <th>Date</th>
                                <th>Day</th>
                                <th>Time</th>
                                <th style="text-align:center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($schedules as $i => $row):
                                $date_obj  = new DateTime($row['schedule_date']);
                                $day_name  = $date_obj->format('l');
                                $date_disp = $date_obj->format('M j, Y');
                                $t_start   = date('h:i A', strtotime($row['start_time']));
                                $t_end     = date('h:i A', strtotime($row['end_time']));
                            ?>
                            <tr data-day="<?php echo $day_name; ?>">
                                <td style="color:var(--text-muted);font-size:0.8rem;"><?php echo $i + 1; ?></td>
                                <td><strong><?php echo htmlspecialchars($row['subject']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['room']); ?></td>
                                <td class="sched-date-col"><?php echo $date_disp; ?></td>
                                <td><span class="tag-day"><?php echo $day_name; ?></span></td>
                                <td class="sched-date-col"><?php echo $t_start; ?> – <?php echo $t_end; ?></td>
                                <td style="text-align:center;">
                                    <form method="POST" action="schedules.php" onsubmit="return confirmDelete(this);" style="display:inline;">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="delete_id" value="<?php echo (int)$row['id']; ?>">
                                        <button type="submit" class="btn-action btn-delete" title="Delete schedule">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <polyline points="3 6 5 6 21 6"></polyline>
                                                <path d="M19 6l-1 14a2 2 0 0 1-2 2H8a2 2 0 0 1-2-2L5 6"></path>
                                                <path d="M10 11v6"></path>
                                                <path d="M14 11v6"></path>
                                                <path d="M9 6V4h6v2"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p id="no-results" style="display:none;text-align:center;padding:30px;color:var(--text-muted);font-size:0.9rem;">
                    No schedules match your search.
                </p>
            <?php endif; ?>
        </div>
    </div>

</div><!-- /.dashboard-container -->

<script>
function confirmDelete(form) {
    return confirm('Are you sure you want to delete this schedule? This cannot be undone.');
}

function filterTable() {
    const searchInput = document.getElementById('search-input');
    const filterDay   = document.getElementById('filter-day');
    if (!searchInput || !filterDay) return;

    const search = searchInput.value.toLowerCase();
    const day    = filterDay.value;
    const rows   = document.querySelectorAll('#user-schedules-table tbody tr');
    let visible  = 0;

    rows.forEach(row => {
        const text    = row.textContent.toLowerCase();
        const rowDay  = row.getAttribute('data-day');
        const matchTx = !search || text.includes(search);
        const matchDy = !day   || rowDay === day;
        if (matchTx && matchDy) {
            row.style.display = '';
            visible++;
        } else {
            row.style.display = 'none';
        }
    });

    const noRes = document.getElementById('no-results');
    if (noRes) noRes.style.display = visible === 0 ? 'block' : 'none';
}
</script>
</body>
</html>
