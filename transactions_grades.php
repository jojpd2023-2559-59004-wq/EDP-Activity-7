<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'Database.php';
$database = new Database();
$db = $database->getConnection();

$message = '';
$error = '';

// Handle POST actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_grade') {
        $enrollId = $_POST['enroll_id'] ?? '';
        $gradeNum = $_POST['grade_num'] ?? 0;

        if ($gradeNum < 0 || $gradeNum > 4) {
            $error = "Grade must be between 0.00 and 4.00.";
        } else {
            $stmt = $db->prepare("UPDATE enrollment SET GradeNum = :grade WHERE EnrollID = :eid");
            $stmt->bindParam(':grade', $gradeNum);
            $stmt->bindParam(':eid', $enrollId);
            if ($stmt->execute()) {
                $message = "Grade updated successfully for Enrollment #$enrollId.";
            } else {
                $error = "Failed to update grade.";
            }
        }
    }
}

// Fetch all enrollment records with student/course names
$enrollments = $db->query("
    SELECT e.EnrollID, e.StudentID, CONCAT(s.FirstName, ' ', s.LastName) AS StudentName,
           e.CourseID, c.Title AS CourseTitle, e.GradeNum
    FROM enrollment e
    LEFT JOIN student s ON e.StudentID = s.StudentID
    LEFT JOIN course c ON e.CourseID = c.CourseID
    ORDER BY e.EnrollID ASC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grade Assignment | POSIS Academic EDP</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 14px 10px; text-align: left; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
        .table th { color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .table tr:hover { background: rgba(99, 102, 241, 0.05); }
        .badge { padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; }
        .badge-high { background: rgba(16, 185, 129, 0.15); color: #10b981; }
        .badge-mid { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
        .badge-low { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); align-items: center; justify-content: center; z-index: 50; }
        .modal.active { display: flex; }
        .action-btn { background: none; border: none; cursor: pointer; padding: 4px 8px; border-radius: 4px; transition: all 0.2s; font-size: 0.85rem; color: var(--primary); }
        .action-btn:hover { background: rgba(255,255,255,0.05); }
        .stat-pill { display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; border-radius: 8px; font-size: 0.85rem; background: rgba(245, 158, 11, 0.1); color: #f59e0b; }
        .page-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; color: white; }
        .grade-input { width: 80px; padding: 6px 10px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg-card); color: var(--text-main); text-align: center; font-weight: 600; }
    </style>
</head>
<body>
    <nav class="glass">
        <div style="font-size: 1.25rem; font-weight: 700; color: var(--primary);"></div>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="users.php">User Management</a>
            <a href="report_generator.php" class="active">Reports</a>
            <a href="about.php">About</a>
        </div>
        <div>
            <a href="logout.php" class="btn btn-outline" style="padding: 8px 16px;">Logout</a>
        </div>
    </nav>

    <div class="container animate-fade-in">
        <header style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px;">
            <div style="display: flex; gap: 16px; align-items: center;">
                <div class="page-icon" style="background: linear-gradient(135deg, #f59e0b, #f97316);">
                    <i class="fas fa-star"></i>
                </div>
                <div>
                    <h1 style="font-size: 1.75rem; margin-bottom: 4px;">Grade Assignment</h1>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">Update and manage student academic grades for each enrollment.</p>
                </div>
            </div>
            <span class="stat-pill"><i class="fas fa-edit"></i> <?= count($enrollments) ?> Entries</span>
        </header>

        <?php if ($message): ?>
            <div style="background-color: rgba(16, 185, 129, 0.15); color: #10b981; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; border: 1px solid rgba(16, 185, 129, 0.2);">
                <i class="fas fa-check-circle"></i> <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div style="background-color: rgba(239, 68, 68, 0.15); color: #ef4444; padding: 12px 16px; border-radius: 10px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; border: 1px solid rgba(239, 68, 68, 0.2);">
                <i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <!-- Sub-nav -->
        <div style="display: flex; gap: 8px; margin-bottom: 24px;">
            <a href="transactions_enrollment.php" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.85rem; text-decoration: none;">
                <i class="fas fa-user-graduate"></i> Student Enrollment
            </a>
            <a href="transactions_grades.php" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.85rem; text-decoration: none;">
                <i class="fas fa-star"></i> Grade Assignment
            </a>
            <a href="transactions_course_assignment.php" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.85rem; text-decoration: none;">
                <i class="fas fa-chalkboard-teacher"></i> Course Assignment
            </a>
        </div>

        <!-- Data Grid -->
        <div class="glass" style="padding: 24px;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Enroll ID</th>
                        <th>Student</th>
                        <th>Course</th>
                        <th>Current Grade</th>
                        <th>Performance</th>
                        <th style="text-align: center;">Update Grade</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($enrollments)): ?>
                        <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 40px;">No enrollment records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($enrollments as $row): ?>
                        <tr>
                            <td style="font-weight: 600;">#<?= $row['EnrollID'] ?></td>
                            <td>
                                <div style="font-weight: 500;"><?= htmlspecialchars($row['StudentName'] ?? 'N/A') ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">ID: <?= $row['StudentID'] ?></div>
                            </td>
                            <td><?= htmlspecialchars($row['CourseTitle'] ?? 'N/A') ?></td>
                            <td style="font-weight: 700; font-size: 1.1rem;"><?= number_format($row['GradeNum'], 2) ?></td>
                            <td>
                                <?php
                                $grade = floatval($row['GradeNum']);
                                if ($grade >= 3.5) { $badgeClass = 'badge-high'; $label = 'Excellent'; }
                                elseif ($grade >= 2.5) { $badgeClass = 'badge-mid'; $label = 'Good'; }
                                else { $badgeClass = 'badge-low'; $label = 'Needs Improvement'; }
                                ?>
                                <span class="badge <?= $badgeClass ?>"><?= $label ?></span>
                            </td>
                            <td style="text-align: center;">
                                <form method="POST" style="display: inline-flex; gap: 6px; align-items: center;">
                                    <input type="hidden" name="action" value="update_grade">
                                    <input type="hidden" name="enroll_id" value="<?= $row['EnrollID'] ?>">
                                    <input type="number" name="grade_num" class="grade-input" step="0.01" min="0" max="4" value="<?= number_format($row['GradeNum'], 2) ?>" required>
                                    <button type="submit" class="btn btn-primary" style="padding: 6px 12px; font-size: 0.8rem;">
                                        <i class="fas fa-save"></i> Save
                                    </button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</body>
</html>
