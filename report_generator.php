<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'Database.php';
$database = new Database();
$db = $database->getConnection();

// Determine selected report type
$reportType = $_GET['report'] ?? '';
$reportData = [];
$reportHeaders = [];
$reportTitle = '';

if ($reportType === 'enrollment') {
    $reportTitle = 'Student Enrollment Report';
    $reportHeaders = ['Enroll ID', 'Student ID', 'Student Name', 'Course ID', 'Course Title', 'Credits', 'Grade'];
    $reportData = $db->query("
        SELECT e.EnrollID, e.StudentID, CONCAT(s.FirstName, ' ', s.LastName) AS StudentName,
               e.CourseID, c.Title AS CourseTitle, c.Credits, e.GradeNum
        FROM enrollment e
        LEFT JOIN student s ON e.StudentID = s.StudentID
        LEFT JOIN course c ON e.CourseID = c.CourseID
        ORDER BY e.EnrollID ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
} elseif ($reportType === 'grades') {
    $reportTitle = 'Student Grades & Performance Report';
    $reportHeaders = ['Student ID', 'Student Name', 'Course', 'Grade (4.0)', 'Percentage (%)', 'Performance'];
    $rows = $db->query("
        SELECT e.StudentID, CONCAT(s.FirstName, ' ', s.LastName) AS StudentName,
               c.Title AS CourseTitle, e.GradeNum,
               ROUND((e.GradeNum / 4.0) * 100, 1) AS Percentage
        FROM enrollment e
        LEFT JOIN student s ON e.StudentID = s.StudentID
        LEFT JOIN course c ON e.CourseID = c.CourseID
        ORDER BY s.LastName, s.FirstName
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $g = floatval($r['GradeNum']);
        $r['Performance'] = $g >= 3.5 ? 'Excellent' : ($g >= 2.5 ? 'Good' : 'Needs Improvement');
    }
    $reportData = $rows;
} elseif ($reportType === 'course_assignments') {
    $reportTitle = 'Course-Professor Assignment Report';
    $reportHeaders = ['Course ID', 'Course Title', 'Credits', 'Professor ID', 'Professor Name', 'Professor Email', 'Status'];
    $rows = $db->query("
        SELECT c.CourseID, c.Title, c.Credits, c.ProfID,
               CONCAT(p.FirstName, ' ', p.LastName) AS ProfName, p.Email AS ProfEmail
        FROM course c
        LEFT JOIN professor p ON c.ProfID = p.ProfID
        ORDER BY c.CourseID ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as &$r) {
        $r['Status'] = $r['ProfID'] ? 'Assigned' : 'Unassigned';
        if (!$r['ProfName']) { $r['ProfName'] = 'N/A'; $r['ProfEmail'] = 'N/A'; }
    }
    $reportData = $rows;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report Generator | POSIS Academic EDP</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .report-card {
            cursor: pointer;
            padding: 28px;
            transition: all 0.3s;
            position: relative;
            overflow: hidden;
        }
        .report-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(99, 102, 241, 0.15);
            border-color: var(--primary);
        }
        .report-card.selected {
            border-color: var(--primary);
            box-shadow: 0 0 0 2px var(--primary), 0 8px 24px rgba(99, 102, 241, 0.15);
        }
        .report-card .card-icon {
            width: 44px; height: 44px; border-radius: 12px; display: flex;
            align-items: center; justify-content: center; font-size: 1.1rem; color: white; margin-bottom: 16px;
        }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 12px 10px; text-align: left; border-bottom: 1px solid var(--border); font-size: 0.85rem; }
        .table th { color: var(--text-muted); font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.05em; background: rgba(0,0,0,0.2); }
        .table tr:hover { background: rgba(99, 102, 241, 0.04); }
        .badge { padding: 3px 8px; border-radius: 4px; font-size: 0.75rem; font-weight: 600; }
        .badge-high { background: rgba(16, 185, 129, 0.15); color: #10b981; }
        .badge-mid { background: rgba(245, 158, 11, 0.15); color: #f59e0b; }
        .badge-low { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
        .export-bar {
            display: flex; align-items: center; justify-content: space-between;
            padding: 16px 24px; border-radius: 12px; margin-top: 24px;
            background: rgba(99, 102, 241, 0.08); border: 1px solid rgba(99, 102, 241, 0.15);
        }
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
        <header style="margin-bottom: 36px; text-align: center;">
            <h1 style="font-size: 2.5rem; color: var(--primary); margin-bottom: 8px;">Report Generator</h1>
            <p style="color: var(--text-main); font-size: 1.1rem;">Select a report type to preview data, then export to Excel with charts and headers.</p>
        </header>

        <!-- Transaction Navigation -->
        <div style="display: flex; gap: 8px; margin-bottom: 28px; justify-content: center;">
            <a href="transactions_enrollment.php" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.85rem; text-decoration: none;">
                <i class="fas fa-user-graduate"></i> Student Enrollment
            </a>
            <a href="transactions_grades.php" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.85rem; text-decoration: none;">
                <i class="fas fa-star"></i> Grade Assignment
            </a>
            <a href="transactions_course_assignment.php" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.85rem; text-decoration: none;">
                <i class="fas fa-chalkboard-teacher"></i> Course Assignment
            </a>
        </div>

        <!-- Report Type Selection Cards -->
        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; margin-bottom: 36px;">
            <a href="report_generator.php?report=enrollment" class="glass report-card <?= $reportType === 'enrollment' ? 'selected' : '' ?>" style="text-decoration: none; color: inherit;">
                <div class="card-icon" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);"><i class="fas fa-user-graduate"></i></div>
                <h3 style="font-size: 1rem; margin-bottom: 6px;">Student Enrollment Report</h3>
                <p style="color: var(--text-muted); font-size: 0.825rem; line-height: 1.5;">Complete list of all student course enrollments with grades and credit details.</p>
            </a>
            <a href="report_generator.php?report=grades" class="glass report-card <?= $reportType === 'grades' ? 'selected' : '' ?>" style="text-decoration: none; color: inherit;">
                <div class="card-icon" style="background: linear-gradient(135deg, #f59e0b, #f97316);"><i class="fas fa-chart-bar"></i></div>
                <h3 style="font-size: 1rem; margin-bottom: 6px;">Grades & Performance Report</h3>
                <p style="color: var(--text-muted); font-size: 0.825rem; line-height: 1.5;">Grade analysis with percentage conversion and performance classification.</p>
            </a>
            <a href="report_generator.php?report=course_assignments" class="glass report-card <?= $reportType === 'course_assignments' ? 'selected' : '' ?>" style="text-decoration: none; color: inherit;">
                <div class="card-icon" style="background: linear-gradient(135deg, #10b981, #059669);"><i class="fas fa-chalkboard-teacher"></i></div>
                <h3 style="font-size: 1rem; margin-bottom: 6px;">Course Assignment Report</h3>
                <p style="color: var(--text-muted); font-size: 0.825rem; line-height: 1.5;">Professor-to-course mapping with assignment status and contact info.</p>
            </a>
        </div>

        <?php if ($reportType && !empty($reportData)): ?>
        <!-- Data Grid Preview -->
        <div class="glass" style="padding: 24px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h2 style="font-size: 1.25rem; margin-bottom: 4px;"><?= htmlspecialchars($reportTitle) ?></h2>
                    <p style="color: var(--text-muted); font-size: 0.825rem;"><?= count($reportData) ?> records found &bull; Generated on <?= date('F j, Y \a\t g:i A') ?></p>
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <?php foreach ($reportHeaders as $header): ?>
                                <th><?= htmlspecialchars($header) ?></th>
                            <?php endforeach; ?>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reportData as $row): ?>
                        <tr>
                            <?php foreach ($row as $key => $cell): ?>
                                <td>
                                    <?php if ($key === 'Performance'): ?>
                                        <?php
                                        $bc = 'badge-mid';
                                        if ($cell === 'Excellent') $bc = 'badge-high';
                                        elseif ($cell === 'Needs Improvement') $bc = 'badge-low';
                                        ?>
                                        <span class="badge <?= $bc ?>"><?= htmlspecialchars($cell) ?></span>
                                    <?php elseif ($key === 'Status'): ?>
                                        <span class="badge <?= $cell === 'Assigned' ? 'badge-high' : 'badge-low' ?>"><?= htmlspecialchars($cell) ?></span>
                                    <?php elseif ($key === 'GradeNum'): ?>
                                        <strong><?= number_format($cell, 2) ?></strong>
                                    <?php elseif ($key === 'Percentage'): ?>
                                        <?= $cell ?>%
                                    <?php else: ?>
                                        <?= htmlspecialchars($cell ?? 'N/A') ?>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Export Bar -->
            <div class="export-bar">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-file-excel" style="font-size: 1.5rem; color: #10b981;"></i>
                    <div>
                        <div style="font-weight: 600; font-size: 0.9rem;">Export to Microsoft Excel</div>
                        <div style="color: var(--text-muted); font-size: 0.8rem;">Includes header with logo, data sheet, chart sheet, and signature placeholder.</div>
                    </div>
                </div>
                <a href="export_excel.php?report=<?= urlencode($reportType) ?>" class="btn btn-primary" style="padding: 10px 24px; text-decoration: none;">
                    <i class="fas fa-download"></i> Export to Excel
                </a>
            </div>
        </div>
        <?php elseif ($reportType && empty($reportData)): ?>
            <div class="glass" style="padding: 40px; text-align: center;">
                <i class="fas fa-database" style="font-size: 2rem; color: var(--text-muted); margin-bottom: 12px;"></i>
                <p style="color: var(--text-muted);">No data found for this report type.</p>
            </div>
        <?php else: ?>
            <div class="glass" style="padding: 48px; text-align: center;">
                <i class="fas fa-hand-pointer" style="font-size: 2.5rem; color: var(--text-muted); margin-bottom: 16px;"></i>
                <h3 style="margin-bottom: 8px; color: var(--text-muted);">Select a Report</h3>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Choose one of the report types above to preview data and export to Excel.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
