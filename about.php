<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About | </title>
    <meta name="description" content="Learn more about the POSIS Employee Data Platform and its development team.">
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>

    <nav class="glass">
        <div style="font-size: 1.25rem; font-weight: 700; color: var(--primary);"></div>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="users.php">User Management</a>
            <a href="transactions_enrollment.php">Transactions</a>
            <a href="report_generator.php">Reports</a>
            <a href="about.php" class="active">About</a>
        </div>
        <div>
            <a href="logout.php" class="btn btn-outline" style="padding: 8px 16px;">Logout</a>
        </div>
    </nav>

    <div class="container animate-fade-in" style="max-width: 800px; text-align: center;">
        <header style="margin-bottom: 60px;">
            <div
                style="width: 100px; height: 100px; background: var(--primary); border-radius: 24px; margin: 0 auto 32px; display: flex; align-items: center; justify-content: center; font-size: 2.5rem; color: white; box-shadow: 0 8px 16px rgba(99, 102, 241, 0.4);">
                A
            </div>
            <h1 style="font-size: 2.5rem; margin-bottom: 16px;"></h1>
            <p style="color: var(--text-muted); font-size: 1.1rem; line-height: 1.6;"></p>
        </header>

        <div class="glass" style="padding: 40px; text-align: justify; margin-bottom: 40px;">
            <h2 style="margin-bottom: 20px; text-align: center;">Academic Infrastructure</h2>
            <p style="color: var(--text-muted); margin-bottom: 24px; line-height: 1.7;">
                The POSIS Academic EDP provides a robust framework for managing institutional data.
                Built on a relational architecture, it seamlessly connects Student records, Professor assignments,
                Course catalogs, and Department logistics info to provide a 360-degree view of academic operations.
            </p>

            <h3 style="margin-bottom: 16px; font-size: 1.1rem; text-align: center;">Core Database Modules</h3>
            <ul style="color: var(--text-muted); padding-left: 20px; line-height: 2;">
                <li><strong>Student & Enrollment:</strong> Tracking academic progress and grade management.</li>
                <li><strong>Professor & Course:</strong> Faculty assignment and curriculum scheduling.</li>
                <li><strong>Department & Facilities:</strong> Institutional resources and building management.</li>
                <li><strong>Relational Analytics:</strong> Cross-table performance reporting.</li>
            </ul>
        </div>

        <div style="font-size: 0.875rem; color: var(--text-muted);">
            © 2026 POSIS Technology Group. All rights reserved.
        </div>
    </div>

</body>
</html>
