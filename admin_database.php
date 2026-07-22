<?php
require_once 'config.php';

// Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Admin only
$role = $_SESSION['role'] ?? 'user';
if ($role !== 'admin') {
    header("Location: schedules.php");
    exit;
}

$fullname = $_SESSION['fullname'];
$username = $_SESSION['username'];

// ── Fetch ALL schedules with the owner's name ────────────────────────────────
try {
    $stmt = $pdo->query(
        "SELECT s.*, u.fullname AS owner_fullname, u.username AS owner_username
         FROM schedules s
         JOIN users u ON s.user_id = u.id
         ORDER BY s.schedule_date ASC, s.start_time ASC"
    );
    $all_schedules = $stmt->fetchAll();
} catch (PDOException $e) {
    $all_schedules = [];
    $db_error = $e->getMessage();
}

// ── Total stats ───────────────────────────────────────────────────────────────
try {
    $total_schedules = $pdo->query("SELECT COUNT(*) FROM schedules")->fetchColumn();
    $total_users_with_sched = $pdo->query("SELECT COUNT(DISTINCT user_id) FROM schedules")->fetchColumn();
    $upcoming = $pdo->query("SELECT COUNT(*) FROM schedules WHERE schedule_date >= CURDATE()")->fetchColumn();
} catch (PDOException $e) {
    $total_schedules = $total_users_with_sched = $upcoming = 0;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Schedule Database | Admin Panel</title>
    <meta name="description" content="Admin view of all user-encoded schedules.">
    <link rel="stylesheet" href="style.css">
    <style>
        body { align-items: flex-start; }

        /* ── Admin badge ────────────────────────────────────── */
        .admin-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            background: linear-gradient(135deg, rgba(251,191,36,0.18), rgba(245,158,11,0.10));
            border: 1px solid rgba(251,191,36,0.35);
            color: #fbbf24;
            font-size: 0.7rem;
            font-weight: 700;
            letter-spacing: 0.08em;
            text-transform: uppercase;
            padding: 3px 10px;
            border-radius: 20px;
            margin-left: 10px;
            vertical-align: middle;
        }

        /* ── Stat row ───────────────────────────────────────── */
        .db-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 16px;
            margin-bottom: 24px;
        }
        .db-stat-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.07);
            border-radius: 14px;
            padding: 20px 22px;
            display: flex;
            align-items: center;
            gap: 16px;
            transition: border-color 0.2s, transform 0.2s;
        }
        .db-stat-card:hover {
            border-color: rgba(138,43,226,0.3);
            transform: translateY(-2px);
        }
        .db-stat-icon {
            width: 44px;
            height: 44px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .db-stat-icon.purple { background: rgba(138,43,226,0.18); }
        .db-stat-icon.blue   { background: rgba(59,130,246,0.18); }
        .db-stat-icon.green  { background: rgba(16,185,129,0.18); }
        .db-stat-num  { font-size: 1.8rem; font-weight: 700; line-height: 1; }
        .db-stat-lbl  { font-size: 0.78rem; color: var(--text-muted); margin-top: 4px; }

        /* ── Filter bar ─────────────────────────────────────── */
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

        /* ── Table ──────────────────────────────────────────── */
        .db-table-wrapper {
            overflow-x: auto;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.05);
        }
        .db-table-wrapper table th { font-size: 0.78rem; }

        /* owner chip */
        .owner-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(59,130,246,0.12);
            border: 1px solid rgba(59,130,246,0.25);
            color: #60a5fa;
            font-size: 0.78rem;
            font-weight: 600;
            padding: 3px 9px;
            border-radius: 20px;
        }
        .owner-chip svg { opacity: 0.8; }

        /* day tag */
        .tag-day {
            display: inline-block;
            padding: 3px 10px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            background: rgba(138,43,226,0.15);
            color: #c084fc;
            border: 1px solid rgba(138,43,226,0.3);
        }

        /* empty state */
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--text-muted);
        }
        .empty-state svg { margin-bottom: 16px; opacity: 0.4; }
        .empty-state p { font-size: 0.95rem; line-height: 1.6; }

        /* row stripe */
        tbody tr:nth-child(even) { background: rgba(255,255,255,0.01); }

        @media (max-width: 640px) {
            .db-stats { grid-template-columns: 1fr; }
        }
    </style>
    <script src="theme.js"></script>
</head>
<body>
<div class="dashboard-container">

    <!-- Header -->
    <header class="dashboard-header">
        <div class="dashboard-user-info">
            <span>Admin Panel</span>
            <h2>
                <?php echo htmlspecialchars($fullname); ?> (<?php echo htmlspecialchars($username); ?>)
                <span class="admin-badge">
                    <svg width="10" height="10" viewBox="0 0 24 24" fill="currentColor"><path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/></svg>
                    Admin
                </span>
            </h2>
        </div>

        <nav class="dashboard-nav">
            <a href="dashboard.php" class="nav-link">Login Logs</a>
            <a href="admin_database.php" class="nav-link active">User Schedules</a>
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

    <!-- Stats Row -->
    <div style="padding: 0 0 0 0; animation: fadeIn 0.5s ease;">
        <div class="db-stats">
            <div class="db-stat-card">
                <div class="db-stat-icon purple">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#c084fc" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="16" y1="2" x2="16" y2="6"></line>
                        <line x1="8" y1="2" x2="8" y2="6"></line>
                        <line x1="3" y1="10" x2="21" y2="10"></line>
                    </svg>
                </div>
                <div>
                    <div class="db-stat-num"><?php echo number_format($total_schedules); ?></div>
                    <div class="db-stat-lbl">Total Schedules</div>
                </div>
            </div>
            <div class="db-stat-card">
                <div class="db-stat-icon blue">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#60a5fa" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="9" cy="7" r="4"></circle>
                        <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                        <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                    </svg>
                </div>
                <div>
                    <div class="db-stat-num"><?php echo number_format($total_users_with_sched); ?></div>
                    <div class="db-stat-lbl">Users with Schedules</div>
                </div>
            </div>
            <div class="db-stat-card">
                <div class="db-stat-icon green">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#34d399" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="12" cy="12" r="10"></circle>
                        <polyline points="12 6 12 12 16 14"></polyline>
                    </svg>
                </div>
                <div>
                    <div class="db-stat-num"><?php echo number_format($upcoming); ?></div>
                    <div class="db-stat-lbl">Upcoming Schedules</div>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Card -->
    <div style="animation: fadeIn 0.7s ease;">
        <div class="dashboard-card">
            <h3>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                    <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                    <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                </svg>
                All Encoded Schedules
                <span style="margin-left:auto;font-size:0.8rem;font-weight:400;color:var(--text-muted);">
                    <?php echo count($all_schedules); ?> entr<?php echo count($all_schedules) !== 1 ? 'ies' : 'y'; ?>
                </span>
            </h3>

            <!-- Filter Bar -->
            <div class="filter-bar">
                <input type="text" id="search-input" placeholder="🔍  Search subject, room or user…" oninput="filterTable()">
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

            <?php if (isset($db_error)): ?>
                <div class="alert alert-error">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:10px;flex-shrink:0;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <?php echo htmlspecialchars($db_error); ?>
                </div>
            <?php endif; ?>

            <?php if (empty($all_schedules)): ?>
                <div class="empty-state">
                    <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="var(--primary)" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
                        <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                        <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                        <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                    </svg>
                    <p>No schedules have been encoded yet.<br>Users can add schedules from their Schedules page.</p>
                </div>
            <?php else: ?>
                <div class="db-table-wrapper">
                    <table id="schedules-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Encoded By</th>
                                <th>Subject</th>
                                <th>Room</th>
                                <th>Date</th>
                                <th>Day</th>
                                <th>Time</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($all_schedules as $i => $row):
                                $date_obj  = new DateTime($row['schedule_date']);
                                $day_name  = $date_obj->format('l');
                                $date_disp = $date_obj->format('M j, Y');
                                $t_start   = date('h:i A', strtotime($row['start_time']));
                                $t_end     = date('h:i A', strtotime($row['end_time']));
                                $created   = date('M j, Y', strtotime($row['created_at']));
                            ?>
                            <tr data-day="<?php echo $day_name; ?>">
                                <td style="color:var(--text-muted);font-size:0.8rem;"><?php echo $i + 1; ?></td>
                                <td>
                                    <span class="owner-chip">
                                        <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                            <circle cx="12" cy="7" r="4"></circle>
                                        </svg>
                                        <?php echo htmlspecialchars($row['owner_fullname']); ?>
                                    </span>
                                    <div style="font-size:0.72rem;color:var(--text-muted);margin-top:3px;padding-left:2px;">
                                        @<?php echo htmlspecialchars($row['owner_username']); ?>
                                    </div>
                                </td>
                                <td><strong><?php echo htmlspecialchars($row['subject']); ?></strong></td>
                                <td><?php echo htmlspecialchars($row['room']); ?></td>
                                <td style="white-space:nowrap;"><?php echo $date_disp; ?></td>
                                <td><span class="tag-day"><?php echo $day_name; ?></span></td>
                                <td style="white-space:nowrap;"><?php echo $t_start; ?> – <?php echo $t_end; ?></td>
                                <td style="white-space:nowrap;font-size:0.8rem;color:var(--text-muted);"><?php echo $created; ?></td>
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
function filterTable() {
    const search = document.getElementById('search-input').value.toLowerCase();
    const day    = document.getElementById('filter-day').value;
    const rows   = document.querySelectorAll('#schedules-table tbody tr');
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
