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

    if ($action === 'add') {
        $studentId = $_POST['student_id'] ?? '';
        $courseId = $_POST['course_id'] ?? '';
        $gradeNum = $_POST['grade_num'] ?? 0.00;

        // Check if student is already enrolled in this course
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM enrollment WHERE StudentID = :sid AND CourseID = :cid");
        $checkStmt->bindParam(':sid', $studentId);
        $checkStmt->bindParam(':cid', $courseId);
        $checkStmt->execute();
        
        if ($checkStmt->fetchColumn() > 0) {
            $error = "This student is already enrolled in this course.";
        } else {
            $stmt = $db->prepare("INSERT INTO enrollment (StudentID, CourseID, GradeNum) VALUES (:sid, :cid, :grade)");
            $stmt->bindParam(':sid', $studentId);
            $stmt->bindParam(':cid', $courseId);
            $stmt->bindParam(':grade', $gradeNum);
            if ($stmt->execute()) {
                $message = "Student successfully enrolled in the course.";
            } else {
                $error = "Failed to enroll student.";
            }
        }
    } elseif ($action === 'delete') {
        $enrollId = $_POST['enroll_id'] ?? '';
        $stmt = $db->prepare("DELETE FROM enrollment WHERE EnrollID = :eid");
        $stmt->bindParam(':eid', $enrollId);
        if ($stmt->execute()) {
            $message = "Enrollment record removed successfully.";
        } else {
            $error = "Failed to remove enrollment.";
        }
    }
}

// Fetch enrollment data with student and course names via JOIN
$enrollments = $db->query("
    SELECT e.EnrollID, e.StudentID, CONCAT(s.FirstName, ' ', s.LastName) AS StudentName,
           e.CourseID, c.Title AS CourseTitle, c.Credits, e.GradeNum
    FROM enrollment e
    LEFT JOIN student s ON e.StudentID = s.StudentID
    LEFT JOIN course c ON e.CourseID = c.CourseID
    ORDER BY e.EnrollID DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch students and courses for the dropdown
$students = $db->query("SELECT StudentID, CONCAT(FirstName, ' ', LastName) AS FullName FROM student ORDER BY FirstName")->fetchAll(PDO::FETCH_ASSOC);
$courses = $db->query("SELECT CourseID, Title FROM course ORDER BY Title")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Enrollment | POSIS Academic EDP</title>
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
        .action-btn { background: none; border: none; cursor: pointer; padding: 4px 8px; border-radius: 4px; transition: all 0.2s; font-size: 0.85rem; }
        .action-btn:hover { background: rgba(255,255,255,0.05); }
        .action-btn.delete { color: #ef4444; }
        .action-btn.edit { color: var(--primary); }
        .stat-pill { display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; border-radius: 8px; font-size: 0.85rem; background: rgba(99, 102, 241, 0.1); color: var(--primary); }
        .page-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem; color: white; }
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
        <!-- Page Header -->
        <header style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px;">
            <div style="display: flex; gap: 16px; align-items: center;">
                <div class="page-icon" style="background: linear-gradient(135deg, #6366f1, #8b5cf6);">
                    <i class="fas fa-user-graduate"></i>
                </div>
                <div>
                    <h1 style="font-size: 1.75rem; margin-bottom: 4px;">Student Enrollment</h1>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">Manage student course registrations and enrollment records.</p>
                </div>
            </div>
            <div style="display: flex; gap: 10px; align-items: center;">
                <span class="stat-pill"><i class="fas fa-list"></i> <?= count($enrollments) ?> Records</span>
                <button onclick="openModal()" class="btn btn-primary" style="padding: 10px 20px; font-size: 0.875rem;">
                    <i class="fas fa-plus"></i> Enroll Student
                </button>
            </div>
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

        <!-- Sub-nav for transactions -->
        <div style="display: flex; gap: 8px; margin-bottom: 24px;">
            <a href="transactions_enrollment.php" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.85rem; text-decoration: none;">
                <i class="fas fa-user-graduate"></i> Student Enrollment
            </a>
            <a href="transactions_grades.php" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.85rem; text-decoration: none;">
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
                        <th>Credits</th>
                        <th>Grade</th>
                        <th>Performance</th>
                        <th style="text-align: center;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($enrollments)): ?>
                        <tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 40px;">No enrollment records found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($enrollments as $row): ?>
                        <tr>
                            <td style="font-weight: 600;">#<?= $row['EnrollID'] ?></td>
                            <td>
                                <div style="font-weight: 500;"><?= htmlspecialchars($row['StudentName'] ?? 'N/A') ?></div>
                                <div style="font-size: 0.75rem; color: var(--text-muted);">ID: <?= $row['StudentID'] ?></div>
                            </td>
                            <td><?= htmlspecialchars($row['CourseTitle'] ?? 'N/A') ?></td>
                            <td><?= $row['Credits'] ?? '-' ?></td>
                            <td style="font-weight: 600;"><?= number_format($row['GradeNum'], 2) ?></td>
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
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Remove this enrollment?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="enroll_id" value="<?= $row['EnrollID'] ?>">
                                    <button type="submit" class="action-btn delete" title="Remove Enrollment"><i class="fas fa-trash-alt"></i></button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Enroll Student Modal -->
    <div id="enrollModal" class="modal">
        <div class="glass" style="width: 100%; max-width: 480px; padding: 32px;">
            <h2 style="margin-bottom: 6px;">Enroll Student in Course</h2>
            <p style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 24px;">Select a student and course to create a new enrollment record.</p>
            <form action="transactions_enrollment.php" method="POST">
                <input type="hidden" name="action" value="add">

                <div class="input-group">
                    <label for="student_id">Student</label>
                    <select name="student_id" id="student_id" required>
                        <option value="">-- Select Student --</option>
                        <?php foreach ($students as $s): ?>
                            <option value="<?= $s['StudentID'] ?>"><?= htmlspecialchars($s['FullName']) ?> (ID: <?= $s['StudentID'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-group">
                    <label for="course_id">Course</label>
                    <select name="course_id" id="course_id" required>
                        <option value="">-- Select Course --</option>
                        <?php foreach ($courses as $c): ?>
                            <option value="<?= $c['CourseID'] ?>"><?= htmlspecialchars($c['Title']) ?> (ID: <?= $c['CourseID'] ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="input-group">
                    <label for="grade_num">Initial Grade (0.00 - 4.00)</label>
                    <input type="number" name="grade_num" id="grade_num" step="0.01" min="0" max="4" value="0.00" required>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px;">
                    <button type="button" onclick="closeModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Enroll</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() { document.getElementById('enrollModal').classList.add('active'); }
        function closeModal() { document.getElementById('enrollModal').classList.remove('active'); }
        document.getElementById('enrollModal').addEventListener('click', function(e) { if (e.target === this) closeModal(); });
    </script>
</body>
</html>
