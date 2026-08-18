<?php
require_once __DIR__ . '/db_connection.php';

$error_message = '';
$success_message = '';

if (isset($_GET['registered'])) {
    $success_message = "Registration successful! You are now logged in.";
}
if (isset($_GET['logged_out'])) {
    $success_message = "You have been safely logged out.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login_input = trim($_POST['name'] ?? $_POST['login_input'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($login_input) || empty($password)) {
        $error_message = "Please enter your Name/Email and Password.";
    } else {
        if (!$pdo) {
            $error_message = "Database connection error. Please try again later.";
        } else {
            try {
                // Find user by Name, Email, or Ration Card Number
                $stmt = $pdo->prepare("SELECT id, name, email, ration_card_number, password FROM users WHERE name = ? OR email = ? OR ration_card_number = ? LIMIT 1");
                $stmt->execute([$login_input, $login_input, $login_input]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // Password matches
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['name'];
                    $_SESSION['ration_card_number'] = $user['ration_card_number'];

                    header("Location: select_ration_card.php");
                    exit();
                } else {
                    $error_message = "Invalid credentials. Please check your name/email and password.";
                }
            } catch (Exception $e) {
                $error_message = "Login failed: " . htmlspecialchars($e->getMessage());
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Citizen Login - Online Ration Portal</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="bg-pattern"></div>

    <!-- Navigation Header -->
    <nav class="navbar">
        <a href="index.php" class="nav-brand">
            <div class="brand-icon">🏛️</div>
            <div>
                <span>e-Ration Portal</span>
                <span style="display:block; font-size:0.75rem; color:var(--text-muted); font-weight:normal;">Public Distribution System</span>
            </div>
        </a>
        <ul class="nav-menu">
            <li><a href="index.php" class="nav-link">Home</a></li>
            <li><a href="login.php" class="nav-link active">Login</a></li>
            <li><a href="register.php" class="btn btn-primary btn-sm">Register</a></li>
            <li><a href="admin_login.php" class="nav-link" style="font-size:0.85rem; color:var(--text-muted);">🔒 Admin</a></li>
        </ul>
    </nav>

    <!-- Auth Wrapper -->
    <div class="auth-wrapper">
        <div class="glass-card auth-card">
            <div style="text-align: center; margin-bottom: 2rem;">
                <div style="display: inline-flex; width: 56px; height: 56px; border-radius: 50%; background: var(--primary-light); color: var(--primary); align-items: center; justify-content: center; font-size: 1.75rem; margin-bottom: 0.75rem;">
                    🔑
                </div>
                <h1 style="font-size: 1.75rem; font-weight: 700;">Citizen Login</h1>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-top: 0.35rem;">Sign in with your registered name, email, or ration card number</p>
            </div>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <span>✅</span>
                    <div><?= htmlspecialchars($success_message); ?></div>
                </div>
            <?php endif; ?>

            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger">
                    <span>⚠️</span>
                    <div><?= htmlspecialchars($error_message); ?></div>
                </div>
            <?php endif; ?>

            <form action="login.php" method="POST">
                <div class="form-group">
                    <label class="form-label" for="login_input">Name, Email, or Ration Card No.</label>
                    <input type="text" id="login_input" name="name" class="form-control" placeholder="e.g. Rajesh Kumar or citizen@example.com" required value="<?= htmlspecialchars($_POST['name'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="password">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Enter your password" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="margin-top: 1.5rem;">
                    Sign In ➔
                </button>
            </form>

            <div style="text-align: center; margin-top: 1.75rem; padding-top: 1.25rem; border-top: 1px solid var(--border-color); font-size: 0.9rem; color: var(--text-muted);">
                Don't have an account yet? <a href="register.php" style="color: var(--primary); font-weight: 600; text-decoration: none;">Register here</a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="site-footer">
        <p>&copy; <?= date('Y'); ?> Online Public Distribution System (PDS) Portal</p>
    </footer>
</body>
</html>
