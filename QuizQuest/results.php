<?php
session_start();
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "quizmaker";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$quiz_id = $_GET['quiz_id'] ?? '';
if (!$quiz_id) die("Quiz not specified.");

// Fetch quiz info including class
$stmt = $conn->prepare("
    SELECT 
        q.title AS quiz_title,
        q.class_id,
        c.title AS class_title,
        c.section,
        c.class_code
    FROM quizzes q
    JOIN classes c ON q.class_id = c.id
    WHERE q.id = ?
");
$stmt->bind_param("i", $quiz_id);
$stmt->execute();
$quiz_info = $stmt->get_result()->fetch_assoc();
$_SESSION['class_id'] = $quiz_info['class_id'];
$stmt->close();

// Fetch all students who took this quiz
$stmt2 = $conn->prepare("
    SELECT sq.score, sq.taken_at, u.full_name AS student_name
    FROM student_quizzes sq
    JOIN users u ON sq.student_id = u.id
    WHERE sq.quiz_id = ?
    ORDER BY sq.taken_at DESC
");
$stmt2->bind_param("i", $quiz_id);
$stmt2->execute();
$students = $stmt2->get_result();
$stmt2->close();
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Quiz Results - <?php echo htmlspecialchars($quiz_info['quiz_title']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/teacher.css">
    <style>
        .results-card {
            background: linear-gradient(135deg,#6D28D9,#F472B6);
            padding: 2rem;
            border-radius: 15px;
            color: #fff;
            margin-top: 2rem;
        }
        .results-card h2, .results-card h4 {
            color: #fff;
        }
        .results-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 1.5rem;
            border-radius: 10px;
            overflow: hidden;
        }
        .results-table th, .results-table td {
            padding: 0.75rem 1rem;
            text-align: left;
        }
        .results-table thead tr {
            background: rgba(255, 255, 255, 0.15);
        }
        .results-table tbody tr {
            background: rgba(255, 255, 255, 0.08);
            transition: background 0.3s;
        }
        .results-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.25);
        }
        .search-sort {
            display: flex;
            justify-content: space-between;
            margin-bottom: 1rem;
            gap: 0.5rem;
        }
        .search-sort input, .search-sort select {
            border-radius: 8px;
            border: none;
            padding: 0.4rem 0.75rem;
            flex: 1;
        }
    </style>
</head>
<body>
<canvas id="background-canvas"></canvas>
<div class="sidebar">
    <img src="assets/images/logo.png" class="logo-img" alt="QuizQuest Logo">
    <div class="menu-wrapper">
        <div class="nav">
            <a class="nav-item" href="teacher.php?class_id=<?php echo $_SESSION['class_id']; ?>">
                <i data-lucide="layout"></i> Back to Classroom
            </a>
        </div>
    </div>
    <a class="logout" href="logout.php"><i data-lucide="log-out"></i> Logout</a>
</div>

<div class="content container mt-4">
    <div class="results-card">
        <h2><?php echo htmlspecialchars($quiz_info['quiz_title']); ?></h2>
        <h4>Class: <?php echo htmlspecialchars($quiz_info['class_title']); ?> | Section: <?php echo htmlspecialchars($quiz_info['section']); ?> | Code: <?php echo htmlspecialchars($quiz_info['class_code']); ?></h4>

        <div class="search-sort">
            <input type="text" id="searchInput" placeholder="Search student...">
            <select id="sortSelect">
                <option value="date_desc">Sort by Date ↓</option>
                <option value="date_asc">Sort by Date ↑</option>
                <option value="score_desc">Sort by Score ↓</option>
                <option value="score_asc">Sort by Score ↑</option>
            </select>
        </div>

        <table class="results-table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student</th>
                    <th>Score</th>
                    <th>Date</th>
                </tr>
            </thead>
            <tbody id="resultsBody">
                <?php
                $count = 1;
                while ($row = $students->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>'.$count.'</td>';
                    echo '<td>'.htmlspecialchars($row['student_name']).'</td>';
                    echo '<td>'.$row['score'].'</td>';
                    echo '<td>'.date('M d, Y H:i', strtotime($row['taken_at'])).'</td>';
                    echo '</tr>';
                    $count++;
                }
                ?>
            </tbody>
        </table>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="teacherscripts.js"></script>
<script>
lucide.replace();

// SEARCH FUNCTIONALITY
document.getElementById('searchInput').addEventListener('keyup', function() {
    const filter = this.value.toLowerCase();
    const rows = document.querySelectorAll('#resultsBody tr');
    rows.forEach(row => {
        const student = row.cells[1].textContent.toLowerCase();
        row.style.display = student.includes(filter) ? '' : 'none';
    });
});

// SORTING FUNCTIONALITY
document.getElementById('sortSelect').addEventListener('change', function() {
    const tbody = document.getElementById('resultsBody');
    const rows = Array.from(tbody.querySelectorAll('tr'));
    const val = this.value;

    rows.sort((a, b) => {
        if(val.includes('date')) {
            const aDate = new Date(a.cells[3].textContent);
            const bDate = new Date(b.cells[3].textContent);
            return val === 'date_desc' ? bDate - aDate : aDate - bDate;
        } else {
            const aScore = parseInt(a.cells[2].textContent);
            const bScore = parseInt(b.cells[2].textContent);
            return val === 'score_desc' ? bScore - aScore : aScore - bScore;
        }
    });

    rows.forEach(row => tbody.appendChild(row));
});
</script>
</body>
</html>
