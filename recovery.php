<?php
require_once 'Database.php';

$message = '';
$error = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $database = new Database();
    $db = $database->getConnection();

    $email = $_POST['email'] ?? '';

    if (!empty($email)) {
        $query = "SELECT id FROM users WHERE email = :email LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $newPassword = 'JosephPosis';
            $updateQuery = "UPDATE users SET password = :password WHERE email = :email";
            $updateStmt = $db->prepare($updateQuery);
            $updateStmt->bindParam(':password', $newPassword);
            $updateStmt->bindParam(':email', $email);

            if ($updateStmt->execute()) {
                $message = "Password has been reset to: 'JosephPosis'. Please login and change it.";
            } else {
                $error = "Failed to reset password.";
            }
        } else {
            $error = "Email address not found in system.";
        }
    } else {
        $error = "Please provide an email address.";
    }
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recover Password | </title>
    <link rel="stylesheet" href="styles.css">
</head>

<body
    style="justify-content: center; align-items: center; background: radial-gradient(circle at top right, #1e293b, #0f172a);">
    <div class="glass animate-fade-in" style="width: 100%; max-width: 400px; padding: 40px; text-align: center;">
        <h1 style="margin-bottom: 8px; font-weight: 700;">Password Recovery</h1>
        <p style="color: var(--text-muted); margin-bottom: 32px;">Enter your email to receive a reset link</p>

        <?php if ($error): ?>
            <div
                style="background-color: rgba(239, 68, 68, 0.2); color: #ef4444; padding: 10px; border-radius: 8px; margin-bottom: 20px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($message): ?>
            <div
                style="background-color: rgba(16, 185, 129, 0.2); color: #10b981; padding: 10px; border-radius: 8px; margin-bottom: 20px;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>

        <form action="recovery.php" method="POST">
            <div class="input-group" style="text-align: left;">
                <label for="recovery-email">Email Address</label>
                <input type="email" name="email" id="recovery-email" placeholder="name@company.com" required>
            </div>
            <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 16px;">Reset
                Password</button>
            <a href="login.php" class="btn btn-outline"
                style="width: 100%; text-decoration: none; display: inline-block; box-sizing: border-box;">Back to
                Login</a>
        </form>
    </div>
</body>

</html>