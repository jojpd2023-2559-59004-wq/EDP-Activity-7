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

    if ($action === 'assign') {
        $courseId = $_POST['course_id'] ?? '';
        $profId = $_POST['prof_id'] ?? '';

        $stmt = $db->prepare("UPDATE course SET ProfID = :pid WHERE CourseID = :cid");
        $stmt->bindParam(':pid', $profId);
        $stmt->bindParam(':cid', $courseId);
        if ($stmt->execute()) {
            $message = "Professor successfully assigned to the course.";
        } else {
            $error = "Failed to assign professor.";
        }
    } elseif ($action === 'unassign') {
        $courseId = $_POST['course_id'] ?? '';
        $stmt = $db->prepare("UPDATE course SET ProfID = NULL WHERE CourseID = :cid");
        $stmt->bindParam(':cid', $courseId);
        if ($stmt->execute()) {
            $message = "Professor unassigned from the course.";
        } else {
            $error = "Failed to unassign professor.";
        }
    }
}

// Fetch courses with professor names
$courses = $db->query("
    SELECT c.CourseID, c.Title, c.Credits, c.ProfID,
           CONCAT(p.FirstName, ' ', p.LastName) AS ProfName, p.Email AS ProfEmail
    FROM course c
    LEFT JOIN professor p ON c.ProfID = p.ProfID
    ORDER BY c.CourseID ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Fetch professors for dropdown
$professors = $db->query("SELECT ProfID, CONCAT(FirstName, ' ', LastName) AS FullName, Email FROM professor ORDER BY FirstName")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Course Assignment | POSIS Academic EDP</title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { padding: 14px 10px; text-align: left; border-bottom: 1px solid var(--border); font-size: 0.9rem; }
        .table th { color: var(--text-muted); font-size: 0.8rem; text-transform: uppercase; letter-spacing: 0.05em; }
        .table tr:hover { background: rgba(99, 102, 241, 0.05); }
        .badge { padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; font-weight: 600; }
        .badge-assigned { background: rgba(16, 185, 129, 0.15); color: #10b981; }
        .badge-unassigned { background: rgba(239, 68, 68, 0.15); color: #ef4444; }
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.6); align-items: center; justify-content: center; z-index: 50; }
        .modal.active { display: flex; }
        .action-btn { background: none; border: none; cursor: pointer; padding: 4px 8px; border-radius: 4px; transition: all 0.2s; font-size: 0.85rem; }
        .action-btn:hover { background: rgba(255,255,255,0.05); }
        .stat-pill { display: inline-flex; align-items: center; gap: 8px; padding: 6px 14px; border-radius: 8px; font-size: 0.85rem; background: rgba(16, 185, 129, 0.1); color: #10b981; }
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
        <header style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 32px;">
            <div style="display: flex; gap: 16px; align-items: center;">
                <div class="page-icon" style="background: linear-gradient(135deg, #10b981, #059669);">
                    <i class="fas fa-chalkboard-teacher"></i>
                </div>
                <div>
                    <h1 style="font-size: 1.75rem; margin-bottom: 4px;">Course Assignment</h1>
                    <p style="color: var(--text-muted); font-size: 0.9rem;">Assign or reassign professors to available courses.</p>
                </div>
            </div>
            <span class="stat-pill"><i class="fas fa-book"></i> <?= count($courses) ?> Courses</span>
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
            <a href="transactions_grades.php" class="btn btn-outline" style="padding: 8px 16px; font-size: 0.85rem; text-decoration: none;">
                <i class="fas fa-star"></i> Grade Assignment
            </a>
            <a href="transactions_course_assignment.php" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.85rem; text-decoration: none;">
                <i class="fas fa-chalkboard-teacher"></i> Course Assignment
            </a>
        </div>

        <!-- Data Grid -->
        <div class="glass" style="padding: 24px;">
            <table class="table">
                <thead>
                    <tr>
                        <th>Course ID</th>
                        <th>Course Title</th>
                        <th>Credits</th>
                        <th>Assigned Professor</th>
                        <th>Status</th>
                        <th style="text-align: center;">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($courses)): ?>
                        <tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 40px;">No courses found.</td></tr>
                    <?php else: ?>
                        <?php foreach ($courses as $row): ?>
                        <tr>
                            <td style="font-weight: 600;">#<?= $row['CourseID'] ?></td>
                            <td style="font-weight: 500;"><?= htmlspecialchars($row['Title']) ?></td>
                            <td><?= $row['Credits'] ?></td>
                            <td>
                                <?php if ($row['ProfName']): ?>
                                    <div style="font-weight: 500;"><?= htmlspecialchars($row['ProfName']) ?></div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);"><?= htmlspecialchars($row['ProfEmail'] ?? '') ?></div>
                                <?php else: ?>
                                    <span style="color: var(--text-muted); font-style: italic;">Unassigned</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge <?= $row['ProfID'] ? 'badge-assigned' : 'badge-unassigned' ?>">
                                    <?= $row['ProfID'] ? 'Assigned' : 'Unassigned' ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <button onclick="openAssignModal(<?= $row['CourseID'] ?>, '<?= htmlspecialchars($row['Title']) ?>', <?= $row['ProfID'] ?? 'null' ?>)" 
                                        class="btn btn-outline" style="padding: 6px 12px; font-size: 0.8rem;">
                                    <i class="fas fa-exchange-alt"></i> <?= $row['ProfID'] ? 'Reassign' : 'Assign' ?>
                                </button>
                                <?php if ($row['ProfID']): ?>
                                <form method="POST" style="display: inline; margin-left: 4px;" onsubmit="return confirm('Unassign professor from this course?');">
                                    <input type="hidden" name="action" value="unassign">
                                    <input type="hidden" name="course_id" value="<?= $row['CourseID'] ?>">
                                    <button type="submit" class="action-btn" style="color: #ef4444;" title="Unassign">
                                        <i class="fas fa-user-minus"></i>
                                    </button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Assign Professor Modal -->
    <div id="assignModal" class="modal">
        <div class="glass" style="width: 100%; max-width: 460px; padding: 32px;">
            <h2 style="margin-bottom: 6px;">Assign Professor</h2>
            <p id="assignCourseLabel" style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 24px;">Select a professor for this course.</p>
            <form action="transactions_course_assignment.php" method="POST">
                <input type="hidden" name="action" value="assign">
                <input type="hidden" name="course_id" id="assignCourseId">

                <div class="input-group">
                    <label for="prof_id">Professor</label>
                    <select name="prof_id" id="prof_id" required>
                        <option value="">-- Select Professor --</option>
                        <?php foreach ($professors as $p): ?>
                            <option value="<?= $p['ProfID'] ?>"><?= htmlspecialchars($p['FullName']) ?> (<?= htmlspecialchars($p['Email']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 24px;">
                    <button type="button" onclick="closeAssignModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Assign</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openAssignModal(courseId, courseTitle, currentProfId) {
            document.getElementById('assignCourseId').value = courseId;
            document.getElementById('assignCourseLabel').innerText = 'Assign a professor to: ' + courseTitle;
            if (currentProfId) {
                document.getElementById('prof_id').value = currentProfId;
            } else {
                document.getElementById('prof_id').value = '';
            }
            document.getElementById('assignModal').classList.add('active');
        }
        function closeAssignModal() { document.getElementById('assignModal').classList.remove('active'); }
        document.getElementById('assignModal').addEventListener('click', function(e) { if (e.target === this) closeAssignModal(); });
    </script>
</body>
</html>
