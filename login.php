<?php
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if (($_SESSION['role'] ?? 'user') === 'admin') {
        header("Location: dashboard.php");
    } else {
        header("Location: schedules.php");
    }
    exit;
}

$error = '';
$success = '';

// Retrieve flash message from session if it exists (e.g., successful registration)
if (isset($_SESSION['register_success'])) {
    $success = $_SESSION['register_success'];
    unset($_SESSION['register_success']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['login_input'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($login_input) || empty($password)) {
        $error = 'Please fill in both fields.';
    } else {
        try {
            // Retrieve user details by username OR email
            $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$login_input, $login_input]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Password is correct, start user session
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['fullname'] = $user['fullname'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'] ?? 'user';
                
                // Log successful login
                logActivity($pdo, $user['username'], 'LOGIN_SUCCESS');
                
                if ($_SESSION['role'] === 'admin') {
                    header("Location: dashboard.php");
                } else {
                    header("Location: schedules.php");
                }
                exit;
            } else {
                // If user didn't exist or password failed, determine the logging username
                // We'll log whatever they typed in (sanitized) to track the failed attempt
                $log_username = empty($user) ? $login_input : $user['username'];
                
                // Log failed attempt
                logActivity($pdo, $log_username, 'LOGIN_FAILED');
                
                $error = 'Invalid username/email or password.';
            }
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
    <title>Sign In | Secure Portal</title>
    <link rel="stylesheet" href="style.css">
    <script src="theme.js"></script>
</head>
<body>
    <div class="container">
        <div class="card">
            <h1>Welcome Back</h1>
            <p class="subtitle">Please sign in to access your dashboard</p>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error)): ?>
                <div class="alert alert-error">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="margin-right: 8px;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <form action="login.php" method="POST" autocomplete="off">
                <div class="form-group">
                    <label for="login_input" class="form-label">Username or Email</label>
                    <input type="text" id="login_input" name="login_input" class="input-control" required placeholder="username or email@example.com" value="<?php echo isset($_POST['login_input']) ? htmlspecialchars($_POST['login_input']) : ''; ?>">
                </div>
                
                <div class="form-group">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" id="password" name="password" class="input-control" required placeholder="••••••••">
                </div>
                
                <button type="submit" class="btn">Sign In</button>
            </form>
            
            <p class="switch-text">
                Don't have an account? <a href="register.php" class="switch-link">Create Account</a>
            </p>
        </div>
    </div>
</body>
</html>
