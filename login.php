<?php
session_start();
require_once 'Database.php';

$error = '';
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $db = $database->getConnection();

    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (!empty($email) && !empty($password)) {
        $query = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($password === $user['password']) {
                if ($user['status'] === 'Active') {
                    $_SESSION['user_id'] = $user['id'];
                    $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
                    $_SESSION['user_role'] = $user['role'];
                    header("Location: dashboard.php");
                    exit();
                } else {
                    $error = "Account is inactive. Please contact admin.";
                }
            } else {
                $error = "Incorrect password.";
            }
        } else {
            $error = "Email not found.";
        }
    } else {
        $error = "Please provide email and password.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | </title>
    <link rel="stylesheet" href="styles.css">
</head>
<body style="justify-content: center; align-items: center; background: radial-gradient(circle at top right, #1e293b, #0f172a);">
    <div class="glass animate-fade-in" style="width: 100%; max-width: 400px; padding: 40px; text-align: center;">
        <h1 style="margin-bottom: 8px; font-weight: 700;">Welcome Back</h1>
        <p style="color: var(--text-muted); margin-bottom: 32px;">Please enter your credentials to access the System</p>

        <?php if ($error): ?>
            <div style="background-color: rgba(239, 68, 68, 0.2); color: #ef4444; padding: 10px; border-radius: 8px; margin-bottom: 20px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <div class="input-group" style="text-align: left;">
                <label for="email">Email</label>
                <input type="email" name="email" id="email" placeholder="Enter your email" required>
            </div>
            <div class="input-group" style="text-align: left;">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" placeholder="••••••••" required>
            </div>
            <div style="display: flex; justify-content: flex-end; margin-bottom: 24px;">
                <a href="recovery.php" style="font-size: 0.8125rem; color: var(--primary); text-decoration: none;">Forgot password?</a>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%;">Sign In</button>
        </form>
    </div>
</body>
</html>
