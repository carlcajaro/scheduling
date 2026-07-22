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
    // Table may already exist
}

$error = '';

// ── Handle Add Schedule ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $subject       = trim($_POST['subject']       ?? '');
    $room          = trim($_POST['room']          ?? '');
    $schedule_date = trim($_POST['schedule_date'] ?? '');
    $start_time    = trim($_POST['start_time']    ?? '');
    $end_time      = trim($_POST['end_time']      ?? '');

    if (!$subject || !$room || !$schedule_date || !$start_time || !$end_time) {
        $error = 'All fields are required. Please fill in every field.';
    } elseif ($end_time <= $start_time) {
        $error = 'End time must be later than start time.';
    } else {
        try {
            $stmt = $pdo->prepare("INSERT INTO schedules (user_id, subject, room, schedule_date, start_time, end_time) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$user_id, $subject, $room, $schedule_date, $start_time, $end_time]);
            
            $_SESSION['sched_success'] = 'Schedule added successfully!';
            header("Location: schedules.php");
            exit;
        } catch (PDOException $e) {
            $error = 'Database error: ' . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Schedule | Control Center</title>
    <meta name="description" content="Add a new schedule entry.">
    <link rel="stylesheet" href="style.css">
    <script src="theme.js"></script>
    <style>
        body { align-items: flex-start; }
        .form-card-container {
            max-width: 680px;
            margin: 0 auto;
            width: 100%;
        }
        .sched-form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }
        .sched-form-grid .form-group.full-width {
            grid-column: 1 / -1;
        }
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 24px;
            padding-top: 18px;
            border-top: 1px solid var(--table-row-border);
        }
        .btn-cancel {
            background: rgba(255,255,255,0.05);
            color: var(--text-muted);
            border: 1px solid var(--card-border);
            border-radius: 12px;
            padding: 12px 24px;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.25s ease;
        }
        .btn-cancel:hover {
            background: rgba(255,255,255,0.1);
            color: var(--text-main);
        }
        .btn-add {
            width: auto;
            padding: 12px 28px;
            margin-top: 0;
        }
        @media (max-width: 640px) {
            .sched-form-grid { grid-template-columns: 1fr; }
        }
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
            <a href="schedules.php" class="nav-link">My Schedules</a>
            <a href="add_schedule.php" class="nav-link active">Add Schedule</a>
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

    <!-- Form Container -->
    <div class="form-card-container" style="animation: fadeIn 0.6s ease;">
        <div class="dashboard-card">
            <h3>
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="var(--secondary)" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                    <line x1="16" y1="2" x2="16" y2="6"></line>
                    <line x1="8" y1="2" x2="8" y2="6"></line>
                    <line x1="3" y1="10" x2="21" y2="10"></line>
                    <line x1="12" y1="14" x2="12" y2="18"></line>
                    <line x1="10" y1="16" x2="14" y2="16"></line>
                </svg>
                Add New Schedule
            </h3>

            <?php if ($error): ?>
                <div class="alert alert-error">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right:10px;flex-shrink:0;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="add_schedule.php" id="add-schedule-form">
                <div class="sched-form-grid">
                    <div class="form-group full-width">
                        <label class="form-label" for="subject">Subject / Class</label>
                        <input
                            type="text"
                            id="subject"
                            name="subject"
                            class="input-control"
                            placeholder="e.g. Mathematics 101"
                            maxlength="100"
                            required
                            value="<?php echo isset($_POST['subject']) && $error ? htmlspecialchars($_POST['subject']) : ''; ?>"
                        >
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label" for="room">Room / Location</label>
                        <input
                            type="text"
                            id="room"
                            name="room"
                            class="input-control"
                            placeholder="e.g. Room 204 — Building A"
                            maxlength="100"
                            required
                            value="<?php echo isset($_POST['room']) && $error ? htmlspecialchars($_POST['room']) : ''; ?>"
                        >
                    </div>

                    <div class="form-group full-width">
                        <label class="form-label" for="schedule_date">Date</label>
                        <input
                            type="date"
                            id="schedule_date"
                            name="schedule_date"
                            class="input-control"
                            required
                            value="<?php echo isset($_POST['schedule_date']) && $error ? htmlspecialchars($_POST['schedule_date']) : ''; ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="start_time">Start Time</label>
                        <input
                            type="time"
                            id="start_time"
                            name="start_time"
                            class="input-control"
                            required
                            value="<?php echo isset($_POST['start_time']) && $error ? htmlspecialchars($_POST['start_time']) : ''; ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label class="form-label" for="end_time">End Time</label>
                        <input
                            type="time"
                            id="end_time"
                            name="end_time"
                            class="input-control"
                            required
                            value="<?php echo isset($_POST['end_time']) && $error ? htmlspecialchars($_POST['end_time']) : ''; ?>"
                        >
                    </div>
                </div>

                <div class="form-actions">
                    <a href="schedules.php" class="btn-cancel">Cancel</a>
                    <button type="submit" class="btn btn-add" id="btn-add-schedule">
                        <span style="display:inline-flex;align-items:center;gap:8px;">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            Add Schedule
                        </span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Auto-set today as default date
(function() {
    const dateInput = document.getElementById('schedule_date');
    if (dateInput && !dateInput.value) {
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm   = String(today.getMonth() + 1).padStart(2, '0');
        const dd   = String(today.getDate()).padStart(2, '0');
        dateInput.value = `${yyyy}-${mm}-${dd}`;
    }
})();
</script>
</body>
</html>
