<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'Database.php';
$database = new Database();
$db = $database->getConnection();

function fetchTableData($db, $query, $columns) {
    try {
        $stmt = $db->prepare($query);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $data = [];
        foreach ($rows as $row) {
            $rowData = [];
            foreach ($columns as $col) {
                $rowData[] = $row[$col] ?? '';
            }
            $data[] = $rowData;
        }
        return $data;
    } catch (Exception $e) {
        return [];
    }
}

$students = fetchTableData($db, "SELECT * FROM student", ['StudentID', 'FirstName', 'LastName', 'Major_DeptID', 'EnrollYear']);
$professors = fetchTableData($db, "SELECT * FROM professor", ['ProfID', 'FirstName', 'LastName', 'Email', 'DeptID']);
$courses = fetchTableData($db, "SELECT * FROM course", ['CourseID', 'Title', 'Credits', 'ProfID']);
$enrollments = fetchTableData($db, "SELECT * FROM enrollment", ['EnrollID', 'StudentID', 'CourseID', 'GradeNum']);
$departments = fetchTableData($db, "SELECT * FROM department", ['DeptID', 'DeptName', 'Building', 'RoomNumber']);

$studentCount = count($students);
$profCount = count($professors);
$courseCount = count($courses);
$enrollCount = count($enrollments);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | </title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <nav class="glass">
        <div style="font-size: 1.25rem; font-weight: 700; color: var(--primary);"></div>
        <div class="nav-links">
            <a href="dashboard.php" class="active">Dashboard</a>
            <a href="users.php">User Management</a>
            <a href="transactions_enrollment.php">Transactions</a>
            <a href="report_generator.php">Reports</a>
            <a href="about.php">About</a>
        </div>
        <div>
            <a href="logout.php" class="btn btn-outline" style="padding: 8px 16px;">Logout</a>
        </div>
    </nav>

    <div class="container animate-fade-in">
        <header style="margin-bottom: 40px; text-align: center;">
            <h1 style="font-size: 2.5rem; margin-bottom: 12px;">Academic Management Dashboard</h1>
            <p style="color: var(--text-muted); font-size: 1.1rem; max-width: 600px; margin: 0 auto;">Manage Students, Professors, Courses, and Departments across the institution.</p>
        </header>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 24px; margin-bottom: 40px;">
            <div class="glass" style="padding: 24px;">
                <div style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 12px;">Total Students</div>
                <div style="font-size: 2.25rem; font-weight: 700;"><?= $studentCount ?></div>
                <div style="color: #10b981; font-size: 0.875rem; margin-top: 8px;">Academic Year 2026</div>
            </div>
            <div class="glass" style="padding: 24px;">
                <div style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 12px;">Active Professors</div>
                <div style="font-size: 2.25rem; font-weight: 700;"><?= $profCount ?></div>
                <div style="color: var(--primary); font-size: 0.875rem; margin-top: 8px;">12 Departments</div>
            </div>
            <div class="glass" style="padding: 24px;">
                <div style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 12px;">Courses Offered</div>
                <div style="font-size: 2.25rem; font-weight: 700;"><?= $courseCount ?></div>
                <div style="color: #6366f1; font-size: 0.875rem; margin-top: 8px;">Across all majors</div>
            </div>
            <div class="glass" style="padding: 24px;">
                <div style="color: var(--text-muted); font-size: 0.875rem; margin-bottom: 12px;">Total Enrollments</div>
                <div style="font-size: 2.25rem; font-weight: 700;"><?= $enrollCount ?></div>
                <div style="color: #f59e0b; font-size: 0.875rem; margin-top: 8px;">Current Semester</div>
            </div>
        </div>
        
        <!-- Quick Transactions Section -->
        <div style="margin-bottom: 36px;">
            <h2 style="font-size: 1.25rem; margin-bottom: 16px; color: var(--text-muted);">Quick Transactions</h2>
            <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px;">
                <a href="transactions_enrollment.php" class="glass" style="padding: 20px; text-decoration: none; color: inherit; display: flex; align-items: center; gap: 14px; transition: all 0.3s; border: 1px solid transparent;" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='transparent'">
                    <div style="width: 42px; height: 42px; border-radius: 10px; background: linear-gradient(135deg, #6366f1, #8b5cf6); display: flex; align-items: center; justify-content: center; color: white; font-size: 1rem;">
                        <i class="fas fa-user-graduate"></i>
                    </div>
                    <div>
                        <div style="font-weight: 600; font-size: 0.95rem;">Student Enrollment</div>
                        <div style="color: var(--text-muted); font-size: 0.8rem;">Enroll students in courses</div>
                    </div>
                </a>
                <a href="transactions_grades.php" class="glass" style="padding: 20px; text-decoration: none; color: inherit; display: flex; align-items: center; gap: 14px; transition: all 0.3s; border: 1px solid transparent;" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='transparent'">
                    <div style="width: 42px; height: 42px; border-radius: 10px; background: linear-gradient(135deg, #f59e0b, #f97316); display: flex; align-items: center; justify-content: center; color: white; font-size: 1rem;">
                        <i class="fas fa-star"></i>
                    </div>
                    <div>
                        <div style="font-weight: 600; font-size: 0.95rem;">Grade Assignment</div>
                        <div style="color: var(--text-muted); font-size: 0.8rem;">Update student grades</div>
                    </div>
                </a>
                <a href="transactions_course_assignment.php" class="glass" style="padding: 20px; text-decoration: none; color: inherit; display: flex; align-items: center; gap: 14px; transition: all 0.3s; border: 1px solid transparent;" onmouseover="this.style.borderColor='var(--primary)'" onmouseout="this.style.borderColor='transparent'">
                    <div style="width: 42px; height: 42px; border-radius: 10px; background: linear-gradient(135deg, #10b981, #059669); display: flex; align-items: center; justify-content: center; color: white; font-size: 1rem;">
                        <i class="fas fa-chalkboard-teacher"></i>
                    </div>
                    <div>
                        <div style="font-weight: 600; font-size: 0.95rem;">Course Assignment</div>
                        <div style="color: var(--text-muted); font-size: 0.8rem;">Assign professors to courses</div>
                    </div>
                </a>
            </div>
        </div>

        <!-- Table Management Section -->
        <div id="module-nav" style="margin-bottom: 32px; display: flex; gap: 12px; overflow-x: auto; padding-bottom: 10px;">
            <button onclick="switchTable('student')" class="btn btn-primary" id="btn-student">Student Records</button>
            <button onclick="switchTable('professor')" class="btn btn-outline" id="btn-professor">Professors</button>
            <button onclick="switchTable('course')" class="btn btn-outline" id="btn-course">Courses</button>
            <button onclick="switchTable('enrollment')" class="btn btn-outline" id="btn-enrollment">Enrollments</button>
            <button onclick="switchTable('department')" class="btn btn-outline" id="btn-department">Departments</button>
        </div>

        <div class="glass" style="padding: 32px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px;">
                <h2 id="table-title" style="font-size: 1.5rem;">Recent Student Enrollments</h2>
                <button id="add-btn" class="btn btn-primary" style="padding: 8px 16px; font-size: 0.875rem;">+ Add New Record</button>
            </div>
            <table id="data-table" style="width: 100%; border-collapse: collapse;">
                <thead id="table-head">
                    <!-- Head generated by JS -->
                </thead>
                <tbody id="table-body">
                    <!-- Body generated by JS -->
                </tbody>
            </table>
        </div>
    </div>

    <script>
        const tableData = {
            student: {
                title: 'Student Academic Records',
                headers: ['StudentID', 'FirstName', 'LastName', 'Major_DeptID', 'EnrollYear'],
                rows: <?= json_encode($students) ?>
            },
            professor: {
                title: 'Faculty Assignments',
                headers: ['ProfID', 'FirstName', 'LastName', 'Email', 'DeptID'],
                rows: <?= json_encode($professors) ?>
            },
            course: {
                title: 'Course Catalog',
                headers: ['CourseID', 'Title', 'Credits', 'ProfID'],
                rows: <?= json_encode($courses) ?>
            },
            enrollment: {
                title: 'Enrollment & Grades',
                headers: ['EnrollID', 'StudentID', 'CourseID', 'GradeNum'],
                rows: <?= json_encode($enrollments) ?>
            },
            department: {
                title: 'Institutional Departments',
                headers: ['DeptID', 'DeptName', 'Building', 'RoomNumber'],
                rows: <?= json_encode($departments) ?>
            }
        };

        function switchTable(category) {
            const data = tableData[category];
            const head = document.getElementById('table-head');
            const body = document.getElementById('table-body');
            const title = document.getElementById('table-title');

            // Update Title
            title.innerText = data.title;

            // Update Headers
            let headHTML = `<tr style="text-align: left; color: var(--text-muted); font-size: 0.875rem; border-bottom: 1px solid var(--border);">`;
            data.headers.forEach(h => { headHTML += `<th style="padding: 16px 8px;">${h}</th>`; });
            headHTML += `<th style="padding: 16px 8px;">Actions</th></tr>`;
            head.innerHTML = headHTML;

            // Update Body
            let bodyHTML = '';
            if (data.rows.length === 0) {
                bodyHTML = `<tr><td colspan="${data.headers.length + 1}" style="padding: 16px 8px; text-align: center; color: var(--text-muted);">No records found in database.</td></tr>`;
            } else {
                data.rows.forEach(row => {
                    bodyHTML += `<tr style="border-bottom: 1px solid var(--border);">`;
                    row.forEach((cell, index) => {
                        bodyHTML += `<td style="padding: 16px 8px; ${index === 0 ? 'font-weight: 600;' : ''}">${cell}</td>`;
                    });
                    bodyHTML += `<td style="padding: 16px 8px;"><button style="background: none; border: none; color: var(--primary); cursor: pointer;">Edit</button></td></tr>`;
                });
            }
            body.innerHTML = bodyHTML;

            // Update Active Button
            document.querySelectorAll('#module-nav .btn').forEach(btn => {
                btn.classList.remove('btn-primary');
                btn.classList.add('btn-outline');
            });
            document.getElementById(`btn-${category}`).classList.add('btn-primary');
            document.getElementById(`btn-${category}`).classList.remove('btn-outline');
        }

        // Initialize default view
        switchTable('student');
    </script>
</body>
</html>
