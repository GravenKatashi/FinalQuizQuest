<?php
session_start();

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "quizmaker";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$error   = "";
$success = "";
$token   = $_GET["token"] ?? "";

/* ===============================
   VALIDATE TOKEN (GET REQUEST)
   =============================== */
if (empty($token)) {
    $error = "Invalid reset token.";
} else {
    $stmt = $conn->prepare("
        SELECT pr.user_id, pr.expires_at
        FROM password_resets pr
        WHERE pr.token = ?
        LIMIT 1
    ");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows !== 1) {
        $error = "Invalid or expired reset token.";
    } else {
        $row = $result->fetch_assoc();
        if (time() > strtotime($row["expires_at"])) {
            $error = "This reset link has expired.";
        }
    }
}

/* ===============================
   HANDLE PASSWORD RESET (POST)
   =============================== */
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["reset_password"])) {
    $token       = $_POST["token"];
    $newPass     = trim($_POST["new_password"]);
    $confirmPass = trim($_POST["confirm_password"]);

    if (empty($newPass) || empty($confirmPass)) {
        $error = "Please fill in both password fields.";
    } elseif (strlen($newPass) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($newPass !== $confirmPass) {
        $error = "Passwords do not match.";
    } else {
        // Re-check token
        $stmt = $conn->prepare("
            SELECT user_id, expires_at
            FROM password_resets
            WHERE token = ?
            LIMIT 1
        ");
        $stmt->bind_param("s", $token);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows !== 1) {
            $error = "Invalid or expired reset token.";
        } else {
            $row = $res->fetch_assoc();
            if (time() > strtotime($row["expires_at"])) {
                $error = "This reset link has expired.";
            } else {
                $user_id = $row["user_id"];
                $hashed  = password_hash($newPass, PASSWORD_DEFAULT);

                $upd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $upd->bind_param("si", $hashed, $user_id);

                if ($upd->execute()) {
                    // Delete token after success
                    $del = $conn->prepare("DELETE FROM password_resets WHERE token = ?");
                    $del->bind_param("s", $token);
                    $del->execute();

                    $success = "Password updated successfully! <a href='login.php'>Login here</a>.";
                } else {
                    $error = "Error updating password.";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - QuizQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/reset_password.css">
</head>

<body>
<canvas id="background-canvas"></canvas>

<header class="header">
    <div class="logo-container">
        <img src="assets/images/logo.png" alt="QuizQuest Logo">
    </div>
</header>

<div class="container mt-3">
    <div class="login-card">

        <!-- LEFT SIDE -->
        <div class="left-side">
            <h2>Reset Password</h2>
            <div class="bottom-info">
                <div class="side-line"></div>
                <p>Create a new password to regain access to your account.</p>
            </div>
        </div>

        <!-- RIGHT SIDE -->
        <div class="right-side">
            <div class="title">
                <img src="assets/images/quizquest-title.png">
            </div>

            <?php if (!empty($error)) : ?>
                <div class="error-box mb-2"><?php echo $error; ?></div>
            <?php endif; ?>

            <?php if (!empty($success)) : ?>
                <div class="success-box mb-2"><?php echo $success; ?></div>
            <?php endif; ?>

            <?php if (empty($success)) : ?>
            <form method="POST">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <input type="password"
                       name="new_password"
                       class="form-control form-control-sm mb-2"
                       placeholder="New Password (min 8 characters)"
                       minlength="8"
                       required>

                <input type="password"
                       name="confirm_password"
                       class="form-control form-control-sm mb-2"
                       placeholder="Confirm Password"
                       minlength="8"
                       required>

                <div class="login-footer">
                    <button type="submit" name="reset_password">Update Password</button>
                </div>
            </form>
            <?php endif; ?>

        </div>
    </div>
</div>

<script src="teacherscripts.js"></script>
</body>
</html>

<?php $conn->close(); ?>
