<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require_once 'config/config.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: ' . BASE_PATH . '/dashboard');
    exit();
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        $error = 'Username dan password harus diisi!';
    } else {
        $conn = getDBConnection();
        
        // Prepare statement to prevent SQL injection
        $stmt = $conn->prepare("SELECT user_id, username, password, full_name, email, role, cabang_id, status FROM users WHERE username = ? AND status = 'active'");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password using password_verify
            if (password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['cabang_id'] = $user['cabang_id'];
                
                // Update last login
                $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                $update_stmt->bind_param("i", $user['user_id']);
                $update_stmt->execute();
                $update_stmt->close();
                
                // Redirect to dashboard
                header('Location: ' . BASE_PATH . '/dashboard');
                exit();
            } else {
                $error = 'Username atau password salah!';
            }
        } else {
            $error = 'Username atau password salah!';
        }
        
        $stmt->close();
        $conn->close();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sinar Telkom Dashboard System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo BASE_PATH; ?>/assets/css/styles.css">
</head>
<body class="login-page">
    <!-- Left Side - Illustration -->
    <div class="login-illustration">
        <div class="illustration-content">
            <h2>Sinar Telekom Digital Management System</h2>
            <p>Streamline your business operations with our modern, futuristic dashboard solution. Manage inventory, sales, and analytics in real-time.</p>
            
            <!-- Digital Flow Animation -->
            <div class="digital-flow">
                <div class="flow-circle"></div>
                <div class="flow-circle"></div>
                <div class="flow-circle"></div>
                <div class="flow-line"></div>
                <div class="flow-line"></div>
                <div class="flow-line"></div>
            </div>
        </div>
    </div>
    
    <!-- Right Side - Login Form -->
    <div class="login-container">
        <div class="login-box" style="position: relative; padding-top: 120px;">
            <!-- Logo Overlay - positioned absolutely above the form -->
            <div style="position: absolute; top: -100px; left: 50%; transform: translateX(-50%); z-index: 10;">
                <img src="<?php echo BASE_PATH; ?>/assets/images/logo_icon.png" alt="Logo" style="width: 200px; height: 200px; object-fit: contain; filter: drop-shadow(0 6px 16px rgba(0, 0, 0, 0.2));">
            </div>
            
            <h1 class="login-title" style="margin-top: 0;">Sinar Telekom Dashboard System</h1>
            <form method="POST" action="<?php echo BASE_PATH; ?>/login" class="login-form">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required placeholder="Masukkan username" value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required placeholder="Masukkan password">
                </div>
                <?php if ($error): ?>
                <div class="error-message" style="display: block;"><?php echo htmlspecialchars($error); ?></div>
                <?php else: ?>
                <div class="error-message"></div>
                <?php endif; ?>
                <button type="submit" class="login-button">Login</button>
                <div style="text-align: center; margin-top: 20px; color: #7f8c8d; font-size: 12px;">
                    created by <a href="https://ryurakki.id" target="_blank" style="color: #8B1538; text-decoration: none; font-weight: 500;">ryurakki.id</a>
                </div>
            </form>
        </div>
    </div>
</body>
</html>
