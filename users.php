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

// Handle actions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $action = $_POST['action'] ?? '';

    if ($action === 'add' || $action === 'update') {
        $id = $_POST['id'] ?? null;
        $fname = $_POST['first_name'] ?? '';
        $lname = $_POST['last_name'] ?? '';
        $email = $_POST['email'] ?? '';
        $role = $_POST['role'] ?? 'Staff';
        $status = $_POST['status'] ?? 'Active';
        $password = $_POST['password'] ?? 'default123'; // Default password for new users

        if ($action === 'add') {
            $query = "INSERT INTO users (first_name, last_name, email, password, role, status) VALUES (:fname, :lname, :email, :password, :role, :status)";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':password', $password);
        } else {
            $query = "UPDATE users SET first_name = :fname, last_name = :lname, email = :email, role = :role, status = :status WHERE id = :id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':id', $id);
        }

        $stmt->bindParam(':fname', $fname);
        $stmt->bindParam(':lname', $lname);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':role', $role);
        $stmt->bindParam(':status', $status);

        if ($stmt->execute()) {
            $message = $action === 'add' ? "User added successfully." : "User updated successfully.";
        } else {
            $error = "Failed to save user.";
        }
    }
}

if ($_SERVER["REQUEST_METHOD"] == "GET" && isset($_GET['action'])) {
    if ($_GET['action'] === 'toggle') {
        $id = $_GET['id'];
        $status = $_GET['status'] === 'Active' ? 'Inactive' : 'Active';
        
        $query = "UPDATE users SET status = :status WHERE id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $id);
        
        if ($stmt->execute()) {
            $message = "User status updated.";
        }
    }
}

// Fetch users
$search = $_GET['search'] ?? '';
$query = "SELECT * FROM users";
if ($search) {
    $query .= " WHERE first_name LIKE :search OR last_name LIKE :search OR email LIKE :search";
}
$query .= " ORDER BY id DESC";

$stmt = $db->prepare($query);
if ($search) {
    $searchTerm = "%$search%";
    $stmt->bindParam(':search', $searchTerm);
}
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management | </title>
    <link rel="stylesheet" href="styles.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { padding: 12px; text-align: left; border-bottom: 1px solid var(--border); }
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 0.8rem; }
        .badge-active { background-color: rgba(16, 185, 129, 0.2); color: #10b981; }
        .badge-inactive { background-color: rgba(239, 68, 68, 0.2); color: #ef4444; }
        .modal { display: none; position: fixed; inset: 0; background: rgba(0,0,0,0.5); align-items: center; justify-content: center; z-index: 50; }
        .modal.active { display: flex; }
    </style>
</head>
<body>
    <nav class="glass">
        <div style="font-size: 1.25rem; font-weight: 700; color: var(--primary);"></div>
        <div class="nav-links">
            <a href="dashboard.php">Dashboard</a>
            <a href="users.php" class="active">User Management</a>
            <a href="transactions_enrollment.php">Transactions</a>
            <a href="report_generator.php">Reports</a>
            <a href="about.php">About</a>
        </div>
        <div>
            <a href="logout.php" class="btn btn-outline" style="padding: 8px 16px;">Logout</a>
        </div>
    </nav>

    <div class="container animate-fade-in">
        <header style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
            <div>
                <h1 style="font-size: 2rem;">User Management</h1>
                <p style="color: var(--text-muted);">Add, update, and manage account statuses.</p>
            </div>
            <button onclick="openModal()" class="btn btn-primary">+ Add Account</button>
        </header>

        <?php if ($message): ?>
            <div style="background-color: rgba(16, 185, 129, 0.2); color: #10b981; padding: 10px; border-radius: 8px; margin-bottom: 20px;">
                <?= htmlspecialchars($message) ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div style="background-color: rgba(239, 68, 68, 0.2); color: #ef4444; padding: 10px; border-radius: 8px; margin-bottom: 20px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <div class="glass" style="padding: 24px;">
            <form method="GET" style="display: flex; gap: 10px; margin-bottom: 20px;">
                <input type="text" name="search" placeholder="Search by name or email..." value="<?= htmlspecialchars($search) ?>" style="padding: 8px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg-card); color: var(--text);">
                <button type="submit" class="btn btn-outline">Search</button>
                <?php if ($search): ?>
                    <a href="users.php" class="btn btn-outline">Clear</a>
                <?php endif; ?>
            </form>

            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td style="font-weight: 600;"><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= htmlspecialchars($user['role']) ?></td>
                        <td>
                            <span class="badge <?= $user['status'] === 'Active' ? 'badge-active' : 'badge-inactive' ?>">
                                <?= $user['status'] ?>
                            </span>
                        </td>
                        <td>
                            <button onclick="editUser(<?= htmlspecialchars(json_encode($user)) ?>)" style="background: none; border: none; color: var(--primary); cursor: pointer; margin-right: 10px;">Edit</button>
                            <a href="users.php?action=toggle&id=<?= $user['id'] ?>&status=<?= $user['status'] ?>" style="color: <?= $user['status'] === 'Active' ? '#ef4444' : '#10b981' ?>; text-decoration: none;">
                                <?= $user['status'] === 'Active' ? 'Deactivate' : 'Activate' ?>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- User Modal -->
    <div id="userModal" class="modal">
        <div class="glass" style="width: 100%; max-width: 500px; padding: 30px;">
            <h2 id="modalTitle" style="margin-bottom: 20px;">Add Account</h2>
            <form action="users.php" method="POST">
                <input type="hidden" name="action" id="formAction" value="add">
                <input type="hidden" name="id" id="userId">
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 15px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px;">First Name</label>
                        <input type="text" name="first_name" id="firstName" required style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg-card); color: var(--text);">
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px;">Last Name</label>
                        <input type="text" name="last_name" id="lastName" required style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg-card); color: var(--text);">
                    </div>
                </div>
                
                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px;">Email</label>
                    <input type="email" name="email" id="userEmail" required style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg-card); color: var(--text);">
                </div>

                <div style="margin-bottom: 15px;">
                    <label style="display: block; margin-bottom: 5px;">Password (Only for new users)</label>
                    <input type="password" name="password" id="userPassword" placeholder="Default: default123" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg-card); color: var(--text);">
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 20px;">
                    <div>
                        <label style="display: block; margin-bottom: 5px;">Role</label>
                        <select name="role" id="userRole" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg-card); color: var(--text);">
                            <option value="Admin">Admin</option>
                            <option value="Staff">Staff</option>
                            <option value="Manager">Manager</option>
                        </select>
                    </div>
                    <div>
                        <label style="display: block; margin-bottom: 5px;">Status</label>
                        <select name="status" id="userStatus" style="width: 100%; padding: 8px; border-radius: 6px; border: 1px solid var(--border); background: var(--bg-card); color: var(--text);">
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div style="display: flex; justify-content: flex-end; gap: 10px;">
                    <button type="button" onclick="closeModal()" class="btn btn-outline">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Account</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal() {
            document.getElementById('modalTitle').innerText = 'Add Account';
            document.getElementById('formAction').value = 'add';
            document.getElementById('userId').value = '';
            document.getElementById('firstName').value = '';
            document.getElementById('lastName').value = '';
            document.getElementById('userEmail').value = '';
            document.getElementById('userRole').value = 'Staff';
            document.getElementById('userStatus').value = 'Active';
            document.getElementById('userPassword').disabled = false;
            document.getElementById('userModal').classList.add('active');
        }

        function closeModal() {
            document.getElementById('userModal').classList.remove('active');
        }

        function editUser(user) {
            document.getElementById('modalTitle').innerText = 'Update Account Profile';
            document.getElementById('formAction').value = 'update';
            document.getElementById('userId').value = user.id;
            document.getElementById('firstName').value = user.first_name;
            document.getElementById('lastName').value = user.last_name;
            document.getElementById('userEmail').value = user.email;
            document.getElementById('userRole').value = user.role;
            document.getElementById('userStatus').value = user.status;
            document.getElementById('userPassword').disabled = true; // Disable password update in this view
            document.getElementById('userModal').classList.add('active');
        }
    </script>
</body>
</html>
