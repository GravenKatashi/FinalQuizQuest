<?php
session_start();

// Only allow admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}

$mysqli = new mysqli("localhost","root","","quizmaker");
if ($mysqli->connect_error) die("Connection failed: ".$mysqli->connect_error);

$admin_name = $_SESSION['username'] ?? 'Admin';
$feedback = "";

// Handle Add / Update / Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    // Add user
    if ($action === 'add') {
        $full_name = trim($_POST['full_name']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $school = trim($_POST['school_affiliation']);
        $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

        $stmt = $mysqli->prepare("INSERT INTO users (full_name, username, email, role, school_affiliation, password) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $full_name, $username, $email, $role, $school, $password);
        $stmt->execute();
        $stmt->close();
        $feedback = "<div class='alert alert-success'>User added successfully.</div>";
    }

    // Update user
    if ($action === 'update') {
        $id = (int)$_POST['id'];
        $full_name = trim($_POST['full_name']);
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $role = $_POST['role'];
        $school = trim($_POST['school_affiliation']);

        if (!empty($_POST['password'])) {
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $mysqli->prepare("UPDATE users SET full_name=?, username=?, email=?, role=?, school_affiliation=?, password=? WHERE id=?");
            $stmt->bind_param("ssssssi", $full_name, $username, $email, $role, $school, $password, $id);
        } else {
            $stmt = $mysqli->prepare("UPDATE users SET full_name=?, username=?, email=?, role=?, school_affiliation=? WHERE id=?");
            $stmt->bind_param("sssssi", $full_name, $username, $email, $role, $school, $id);
        }
        $stmt->execute();
        $stmt->close();
        $feedback = "<div class='alert alert-success'>User updated successfully.</div>";
    }

    // Delete user
    if ($action === 'delete') {
        $id = (int)$_POST['id'];
        $stmt = $mysqli->prepare("DELETE FROM users WHERE id=?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
        $feedback = "<div class='alert alert-danger'>User deleted successfully.</div>";
    }
}

// Fetch all users
$users = $mysqli->query("SELECT id, full_name, username, email, role, school_affiliation FROM users ORDER BY id DESC");
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Admin Dashboard - Users</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin_users.css">
</head>
<body>
<canvas id="background-canvas"></canvas>

<div class="sidebar">
    <img src="assets/images/logo.png" class="logo-img" alt="QuizQuest">
    <div class="menu-wrapper">
        <div class="nav">
            <a class="nav-item active" href="admin_users.php">
                <i data-lucide="users"></i> Users
            </a>
        </div>
    </div>
    <a class="logout" href="logout.php"><i data-lucide="log-out"></i> Logout</a>
</div>

<div class="content container mt-4">
    <div class="avatar-container d-flex align-items-center gap-3 mb-4">
        <span class="greeting h5 mb-0">Hello, <?php echo htmlspecialchars($admin_name); ?></span>
        <img src="https://i.imgur.com/oQEsWSV.png" alt="Avatar" class="freiren-avatar rounded-circle" width="50" height="50">
    </div>

    <?php echo $feedback; ?>

    <!-- Add User Form -->
    <div class="card subject-card-style">
        <div class="card mb-4 shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">Add New User</h5>
                <form method="POST" class="row g-2">
                    <input type="hidden" name="action" value="add">
                    <div class="col-md-4">
                        <input type="text" name="full_name" class="form-control form-control-sm" placeholder="Full Name" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="username" class="form-control form-control-sm" placeholder="Username" required>
                    </div>
                    <div class="col-md-3">
                        <input type="email" name="email" class="form-control form-control-sm" placeholder="Email" required>
                    </div>
                    <div class="col-md-2">
                        <select name="role" class="form-select form-select-sm" required>
                            <option value="student">Student</option>
                            <option value="teacher">Teacher</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="text" name="school_affiliation" class="form-control form-control-sm" placeholder="School (optional)">
                    </div>
                    <div class="col-md-3">
                        <input type="password" name="password" class="form-control form-control-sm" placeholder="Password" required>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-success btn-sm w-100">Add User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card subject-card-style">
        <div class="card shadow-sm">
            <div class="card-body">
                <h5 class="card-title mb-3">Existing Users</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover align-middle">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Full Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>School</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while($user = $users->fetch_assoc()): ?>
                            <tr>
                                <form method="POST">
                                    <input type="hidden" name="id" value="<?php echo $user['id']; ?>">
                                    <td><?php echo $user['id']; ?></td>
                                    <td><input type="text" name="full_name" value="<?php echo htmlspecialchars($user['full_name']); ?>" class="form-control form-control-sm"></td>
                                    <td><input type="text" name="username" value="<?php echo htmlspecialchars($user['username']); ?>" class="form-control form-control-sm"></td>
                                    <td><input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" class="form-control form-control-sm"></td>
                                    <td>
                                        <select name="role" class="form-select form-select-sm">
                                            <option value="student" <?php if($user['role']==='student') echo 'selected'; ?>>Student</option>
                                            <option value="teacher" <?php if($user['role']==='teacher') echo 'selected'; ?>>Teacher</option>
                                            <option value="admin" <?php if($user['role']==='admin') echo 'selected'; ?>>Admin</option>
                                        </select>
                                    </td>
                                    <td><input type="text" name="school_affiliation" value="<?php echo htmlspecialchars($user['school_affiliation']); ?>" class="form-control form-control-sm"></td>
                                    <td class="d-flex gap-1">
                                        <input type="password" name="password" class="form-control form-control-sm" placeholder="New Password">
                                        <button type="submit" name="action" value="update" class="btn btn-primary btn-sm">Update</button>
                                        <button type="submit" name="action" value="delete" class="btn btn-danger btn-sm" onclick="return confirm('Delete this user?');">Delete</button>
                                    </td>
                                </form>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/lucide@0.259.0/dist/lucide.js"></script>
<script>lucide.replace();</script>
</body>
</html>
<?php $mysqli->close(); ?>
