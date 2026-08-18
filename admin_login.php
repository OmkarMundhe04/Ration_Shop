<?php
require_once __DIR__ . '/db_connection.php';

$error_message = '';
$success_message = '';

if (isset($_GET['logged_out'])) {
    $success_message = "Admin session ended securely.";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error_message = "Please provide both Admin Username/Email and Password.";
    } else {
        if (!$pdo) {
            $error_message = "Database connection error.";
        } else {
            try {
                $stmt = $pdo->prepare("SELECT id, username, email, password, full_name, role FROM admins WHERE username = ? OR email = ? LIMIT 1");
                $stmt->execute([$username, $username]);
                $admin = $stmt->fetch();

                // If admin exists in table, verify password. If table was just initialized or default fallback:
                if ($admin && password_verify($password, $admin['password'])) {
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_id'] = $admin['id'];
                    $_SESSION['admin_user'] = $admin['username'];
                    $_SESSION['admin_name'] = $admin['full_name'];
                    $_SESSION['admin_role'] = $admin['role'];

                    header("Location: admin.php");
                    exit();
                } elseif ($username === 'admin' && $password === 'admin123') {
                    // Fallback initial bootstrap
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_user'] = 'admin';
                    $_SESSION['admin_name'] = 'System Administrator';
                    $_SESSION['admin_role'] = 'super_admin';

                    header("Location: admin.php");
                    exit();
                } else {
                    $error_message = "Invalid administrator credentials.";
                }
            } catch (Exception $e) {
                // Fallback for bootstrap
                if ($username === 'admin' && $password === 'admin123') {
                    $_SESSION['admin_logged_in'] = true;
                    $_SESSION['admin_user'] = 'admin';
                    $_SESSION['admin_name'] = 'System Administrator';
                    $_SESSION['admin_role'] = 'super_admin';

                    header("Location: admin.php");
                    exit();
                }
                $error_message = "Authentication failed: " . htmlspecialchars($e->getMessage());
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
    <title>Administrator Login - Online Ration Portal</title>
    <link rel="icon" type="image/x-icon" href="favicon.ico">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body style="background: #0f172a; color: white;">
    <div class="bg-pattern"></div>

    <!-- Navigation Header -->
    <nav class="navbar navbar-dark">
        <a href="index.php" class="nav-brand">
            <div class="brand-icon" style="background: linear-gradient(135deg, #4f46e5, #06b6d4);">🛡️</div>
            <div>
                <span style="color:white;">Admin Management Console</span>
                <span style="display:block; font-size:0.75rem; color:#94a3b8; font-weight:normal;">PDS Authority Control</span>
            </div>
        </a>
        <ul class="nav-menu">
            <li><a href="index.php" class="nav-link">Citizen Portal</a></li>
            <li><a href="admin_login.php" class="nav-link active">Admin Login</a></li>
        </ul>
    </nav>

    <!-- Auth Wrapper -->
    <div class="auth-wrapper">
        <div class="glass-card auth-card" style="background: rgba(30, 41, 59, 0.85); border-color: rgba(255, 255, 255, 0.1); color: white;">
            <div style="text-align: center; margin-bottom: 2rem;">
                <div style="display: inline-flex; width: 60px; height: 60px; border-radius: 50%; background: rgba(79, 70, 229, 0.2); color: #818cf8; align-items: center; justify-content: center; font-size: 2rem; margin-bottom: 0.75rem; border: 1px solid rgba(129, 140, 248, 0.3);">
                    🔐
                </div>
                <h1 style="font-size: 1.75rem; font-weight: 700; color: white;">Department Admin Login</h1>
                <p style="color: #94a3b8; font-size: 0.875rem; margin-top: 0.35rem;">Restricted access for authorized Food & Civil Supplies officers</p>
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

            <form action="admin_login.php" method="POST">
                <div class="form-group">
                    <label class="form-label" for="username" style="color: #cbd5e1;">Admin Username / Official Email</label>
                    <input type="text" id="username" name="username" class="form-control" placeholder="e.g. admin or admin@rationshop.gov" style="background: #0f172a; color: white; border-color: #334155;" required value="<?= htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>

                <div class="form-group">
                    <label class="form-label" for="password" style="color: #cbd5e1;">Password</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="••••••••" style="background: #0f172a; color: white; border-color: #334155;" required>
                </div>

                <div style="background: rgba(15, 23, 42, 0.6); padding: 0.85rem; border-radius: var(--radius-sm); border: 1px dashed #334155; margin-bottom: 1.25rem; font-size: 0.8rem; color: #94a3b8;">
                    <strong>Default Credentials:</strong><br>
                    Username: <code style="color:#38bdf8;">admin</code> | Password: <code style="color:#38bdf8;">admin123</code>
                </div>

                <button type="submit" class="btn btn-primary btn-block" style="padding: 0.85rem;">
                    Authenticate to Control Panel ➔
                </button>
            </form>

            <div style="text-align: center; margin-top: 1.75rem; padding-top: 1.25rem; border-top: 1px solid rgba(255, 255, 255, 0.1); font-size: 0.875rem;">
                <a href="index.php" style="color: #94a3b8; text-decoration: none;">← Return to Public Citizen Portal</a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="site-footer" style="background: #0f172a; color: #64748b; border-color: rgba(255, 255, 255, 0.08);">
        <p>&copy; <?= date('Y'); ?> Online Public Distribution System - Security Administration</p>
    </footer>
</body>
</html>
