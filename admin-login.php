<?php
require_once 'admin-config.php';

// If already logged in, redirect to dashboard
if (isLoggedIn()) {
    header('Location: admin-dashboard.php');
    exit;
}

$error = '';

// Handle login form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username === ADMIN_USERNAME && password_verify($password, ADMIN_PASSWORD_HASH)) {
        $_SESSION['admin_logged_in'] = true;
        $_SESSION['last_activity'] = time();
        $_SESSION['admin_username'] = $username;
        header('Location: admin-dashboard.php');
        exit;
    } else {
        $error = 'Invalid username or password';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Gallery CMS</title>
    <link rel="stylesheet" href="styles.css">
    <style>
        .login-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            position: relative;
            overflow: hidden;
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at center, transparent 0%, rgba(0, 0, 0, 0.5) 100%);
            z-index: 0;
        }

        .login-box {
            position: relative;
            z-index: 1;
            background: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(20px);
            padding: 3rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 215, 0, 0.3);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.8);
            max-width: 450px;
            width: 90%;
        }

        .login-box h1 {
            font-family: var(--font-serif);
            font-size: 2.5rem;
            color: var(--accent-gold);
            margin-bottom: 0.5rem;
            text-align: center;
            text-shadow: 0 0 20px rgba(255, 215, 0, 0.3);
        }

        .login-box p {
            color: rgba(255, 255, 255, 0.7);
            text-align: center;
            margin-bottom: 2rem;
            font-size: 0.95rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        .form-group label {
            display: block;
            color: var(--accent-gold);
            font-weight: 600;
            margin-bottom: 0.5rem;
            font-size: 0.95rem;
        }

        .form-group input {
            width: 100%;
            padding: 1rem 1.25rem;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 12px;
            background: rgba(0, 0, 0, 0.6);
            color: var(--text-light);
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .form-group input:focus {
            outline: none;
            border-color: var(--accent-gold);
            background: rgba(0, 0, 0, 0.8);
            box-shadow: 0 0 20px rgba(248, 152, 30, 0.15);
        }

        .error-message {
            background: rgba(220, 38, 38, 0.2);
            border: 1px solid rgba(220, 38, 38, 0.5);
            color: #ff6b6b;
            padding: 0.875rem 1.25rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
            text-align: center;
        }

        .login-btn {
            width: 100%;
            padding: 1.125rem;
            background: linear-gradient(135deg, var(--primary-color), #2a1a05);
            color: var(--accent-gold);
            border: 1px solid rgba(248, 152, 30, 0.3);
            border-radius: 12px;
            font-size: 1.05rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.4s ease;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .login-btn:hover {
            border-color: var(--accent-gold);
            box-shadow: 0 15px 40px rgba(248, 152, 30, 0.5);
            transform: translateY(-2px);
        }

        .back-to-site {
            text-align: center;
            margin-top: 1.5rem;
        }

        .back-to-site a {
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.9rem;
            text-decoration: none;
            transition: color 0.3s ease;
        }

        .back-to-site a:hover {
            color: var(--accent-gold);
        }

        .security-note {
            margin-top: 2rem;
            padding: 1rem;
            background: rgba(248, 152, 30, 0.1);
            border: 1px solid rgba(248, 152, 30, 0.2);
            border-radius: 10px;
            font-size: 0.85rem;
            color: rgba(255, 255, 255, 0.6);
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <h1>Admin Portal</h1>
            <p>Gallery Content Management System</p>

            <?php if ($error): ?>
                <div class="error-message">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" required autocomplete="username" autofocus>
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                </div>

                <button type="submit" class="login-btn">Login</button>
            </form>

            <div class="back-to-site">
                <a href="index.html">← Back to Website</a>
            </div>

            <div class="security-note">
                🔒 Secure admin access. Sessions expire after 1 hour of inactivity.
            </div>
        </div>
    </div>
</body>
</html>
