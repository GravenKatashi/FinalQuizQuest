<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$mysqli = new mysqli("localhost","root","","quizmaker");
if ($mysqli->connect_error) die("Connection failed: ".$mysqli->connect_error);

$user_id = (int)$_SESSION['user_id'];
$username = $_SESSION['username'] ?? 'User';
$role = $_SESSION['role'];

// --------------------
// HANDLE CREATE CLASS
// --------------------
if ($role === 'teacher' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['title'], $_POST['section'])) {
    $title = trim($_POST['title']);
    $section = trim($_POST['section']);
    $class_code = strtoupper(substr(bin2hex(random_bytes(4)), 0, 7));

    $stmt = $mysqli->prepare("INSERT INTO classes (teacher_id, title, section, class_code) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $title, $section, $class_code);
    if ($stmt->execute()) {
        header("Location: classes.php");
        exit;
    } else {
        $error = "Failed to create class. Please try again.";
    }
    $stmt->close();
}

// --------------------
// HANDLE DELETE CLASS
// --------------------
if ($role === 'teacher' && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_class_id'])) {
    $delete_class_id = (int)$_POST['delete_class_id'];
    $stmt = $mysqli->prepare("DELETE FROM classes WHERE id = ? AND teacher_id = ?");
    $stmt->bind_param("ii", $delete_class_id, $user_id);
    $stmt->execute();
    $stmt->close();
}

// Fetch classes
if ($role === 'teacher') {
    $stmt = $mysqli->prepare("SELECT id, title, section, class_code, created_at FROM classes WHERE teacher_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $user_id);
} else {
    $stmt = $mysqli->prepare("
        SELECT c.id, c.title, c.section, c.class_code, c.created_at
        FROM classes c
        INNER JOIN student_classes sc ON sc.class_code = c.class_code
        WHERE sc.student_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->bind_param("i", $user_id);
}
$stmt->execute();
$res = $stmt->get_result();
$classes = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Classes - QuizQuest</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="assets/css/teacher.css">
<style>
/* Compact CSS for Delete Button */
.delete-class-btn{position:absolute;top:8px;right:12px;background:rgba(255,255,255,0.15);color:#fff;border:none;border-radius:50%;width:28px;height:28px;font-size:18px;font-weight:bold;cursor:pointer;transition:all 0.3s;z-index:10;}
.delete-class-btn:hover{background:rgba(255,0,0,0.8);transform:scale(1.1);}
</style>
</head>
<body>
<canvas id="background-canvas"></canvas>

<div class="sidebar">
    <img src="assets/images/logo.png" class="logo-img" alt="QuizQuest">
    <div class="menu-wrapper">
        <div class="nav">
            <a class="nav-item" href="profile.php"><i data-lucide="user"></i> Profile (<?=htmlspecialchars($username)?>)</a>
            <a class="nav-item active" href="classes.php"><i data-lucide="layout"></i> Classes</a>
            <a class="nav-item" href="leaderboard.php"><i data-lucide="award"></i> Leaderboard</a>
        </div>
    </div>
    <a class="logout" href="logout.php"><i data-lucide="log-out"></i> Logout</a>
</div>

<div class="content">
    <div class="avatar-container">
        <span class="greeting">Hello! <?=htmlspecialchars($username)?></span>
        <img src="https://i.imgur.com/oQEsWSV.png" alt="avatar" class="freiren-avatar">
    </div>

    <h2 class="quizzes-title mb-4">Your Classes</h2>

    <div class="row g-4">
        <?php if($role==='teacher'): ?>
        <div class="col-md-4 col-sm-6">
            <div class="card subject-card h-100 d-flex align-items-center justify-content-center" style="cursor:pointer; background:linear-gradient(135deg,#2563EB,#3B82F6);" data-bs-toggle="modal" data-bs-target="#createClassModal">
                <h3 class="card-title text-center">+ Create Class</h3>
            </div>
        </div>
        <?php endif; ?>

        <?php if(!empty($classes)): ?>
            <?php foreach($classes as $class): ?>
            <div class="col-md-4 col-sm-6">
                <div class="card subject-card h-100 position-relative" onclick="enterClass(<?= (int)$class['id'] ?>)">
                    <button class="delete-class-btn" data-class-id="<?= (int)$class['id'] ?>" title="Delete Class">&times;</button>
                    <div class="card-body d-flex flex-column">
                        <h5 class="card-title mb-1"><?=htmlspecialchars($class['title'])?></h5>
                        <p class="card-text small mb-1 d-flex justify-content-between align-items-center">
                           <span>Section: <?=htmlspecialchars($class['section'])?></span>
                           <span class="badge" style="background:rgba(255,255,255,0.15);color:#fff;">Code: <?=htmlspecialchars($class['class_code'])?></span>
                        </p>
                        <div class="mt-auto text-end">
                            <small class="text-muted">Created: <?=date('M d, Y', strtotime($class['created_at']))?></small>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="col-12"><p class="text-muted">No classes found.</p></div>
        <?php endif; ?>
    </div>
</div>

<!-- Create Class Modal -->
<?php if($role==='teacher'): ?>
<div class="modal fade" id="createClassModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Create New Class</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
            <label class="form-label">Class Title</label>
            <input type="text" name="title" class="form-control" required maxlength="255">
        </div>
        <div class="mb-3">
            <label class="form-label">Section</label>
            <input type="text" name="section" class="form-control" required maxlength="255">
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">Create Class</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<!-- Delete Class Confirmation Modal -->
<div class="modal fade" id="deleteClassModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="POST" class="modal-content">
      <input type="hidden" name="delete_class_id" id="deleteClassIdInput">
      <div class="modal-header">
        <h5 class="modal-title">Delete Class</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <p>Are you sure you want to delete this class? This action cannot be undone.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger">Yes, Delete</button>
      </div>
    </form>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/lucide@0.259.0/dist/lucide.js"></script>
<script src="teacherscripts.js"></script>
<script>
function enterClass(classId){
    <?php if($role==='teacher'): ?>
    window.location.href = `teacher.php?class_id=${classId}`;
    <?php else: ?>
    window.location.href = `student_class.php?class_id=${classId}`;
    <?php endif; ?>
}

// Delete class confirmation
document.querySelectorAll('.delete-class-btn').forEach(btn => {
    btn.addEventListener('click', e => {
        e.stopPropagation();
        const classId = btn.dataset.classId;
        document.getElementById('deleteClassIdInput').value = classId;
        const deleteModal = new bootstrap.Modal(document.getElementById('deleteClassModal'));
        deleteModal.show();
    });
});

lucide.replace();
</script>
</body>
</html>
<?php $mysqli->close(); ?>