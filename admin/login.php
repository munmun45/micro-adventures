<?php
require_once 'config.php';

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit();
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        $error = 'Please enter both username and password';
    } else {
        try {
            $conn = getDBConnection();
            
            // Check if table exists
            $table_check = $conn->query("SHOW TABLES LIKE 'admin_users'");
            if ($table_check->num_rows == 0) {
                $error = 'Database not set up. Please run setup.php first.';
            } else {
                $stmt = $conn->prepare("SELECT id, username, password, full_name, status FROM admin_users WHERE username = ? OR email = ?");
                if ($stmt) {
                    $stmt->bind_param("ss", $username, $username);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    if ($result->num_rows === 1) {
                        $user = $result->fetch_assoc();
                        
                        if ($user['status'] !== 'active') {
                            $error = 'Your account is inactive. Please contact administrator.';
                        } elseif (password_verify($password, $user['password'])) {
                            // Login successful
                            $_SESSION['admin_id'] = $user['id'];
                            $_SESSION['admin_username'] = $user['username'];
                            $_SESSION['admin_name'] = $user['full_name'];
                            
                            // Update last login
                            $update_stmt = $conn->prepare("UPDATE admin_users SET last_login = NOW() WHERE id = ?");
                            $update_stmt->bind_param("i", $user['id']);
                            $update_stmt->execute();
                            $update_stmt->close();
                            
                            // Log activity
                            logActivity('LOGIN', 'User logged in successfully');
                            
                            header('Location: dashboard.php');
                            exit();
                        } else {
                            $error = 'Invalid username or password';
                        }
                    } else {
                        $error = 'Invalid username or password';
                    }
                    
                    $stmt->close();
                } else {
                    $error = 'Database error. Please check your database connection.';
                }
            }
        } catch (Exception $e) {
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
    <title>Admin Login | TravelApp</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }
        .login-card {
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
        }
    </style>
</head>
<body class="flex items-center justify-center p-4">
    <div class="login-card rounded-2xl shadow-2xl p-8 w-full max-w-md">
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-gradient-to-br from-[#667eea] to-[#764ba2] rounded-2xl mb-4">
                <i class="bi bi-shield-lock text-white text-2xl"></i>
            </div>
            <h1 class="text-3xl font-bold text-gray-900 mb-2">Admin Panel</h1>
            <p class="text-gray-600">Sign in to your account</p>
        </div>

        <?php if ($error): ?>
            <div class="bg-red-50 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded">
                <div class="flex items-center">
                    <i class="bi bi-exclamation-circle mr-2"></i>
                    <span><?php echo htmlspecialchars($error); ?></span>
                </div>
            </div>
        <?php endif; ?>

        <form method="POST" action="" class="space-y-6">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="bi bi-person mr-2"></i>Username or Email
                </label>
                <input type="text" name="username" required
                    class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-[#667eea] focus:ring-2 focus:ring-[#667eea]/20 outline-none transition"
                    placeholder="Enter your username or email"
                    value="<?php echo htmlspecialchars($_POST['username'] ?? ''); ?>">
            </div>

            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-2">
                    <i class="bi bi-lock mr-2"></i>Password
                </label>
                <input type="password" name="password" required
                    class="w-full px-4 py-3 rounded-xl border-2 border-gray-200 focus:border-[#667eea] focus:ring-2 focus:ring-[#667eea]/20 outline-none transition"
                    placeholder="Enter your password">
            </div>

            <button type="submit"
                class="w-full py-3 bg-gradient-to-r from-[#667eea] to-[#764ba2] text-white font-semibold rounded-xl hover:shadow-lg transform hover:scale-[1.02] transition duration-200">
                <i class="bi bi-box-arrow-in-right mr-2"></i>Sign In
            </button>
        </form>

        <div class="mt-6 text-center text-sm text-gray-600">
            <p>Default Credentials:</p>
            <p class="font-mono text-xs mt-1">Username: admin | Password: admin123</p>
        </div>
    </div>
</body>
</html>

