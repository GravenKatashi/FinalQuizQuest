<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // PHPMailer

$host = "localhost";
$user = "root";
$pass = "";
$dbname = "quizmaker";

$conn = new mysqli($host, $user, $pass, $dbname);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["reset_request"])) {
    $email = trim($_POST["email"]);

    if (empty($email)) {
        $error = "Please enter your email.";
    } else {
        $stmt = $conn->prepare("SELECT id, username FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            $user_id = $user["id"];

            // Create reset token
            $token = bin2hex(random_bytes(32));
            $expires_at = date("Y-m-d H:i:s", time() + 3600);

            // Delete old tokens
            $conn->prepare("DELETE FROM password_resets WHERE user_id = ?")
                 ->bind_param("i", $user_id)
                 ->execute();

            // Insert new token
            $stmtInsert = $conn->prepare("
                INSERT INTO password_resets (user_id, token, expires_at)
                VALUES (?, ?, ?)
            ");
            $stmtInsert->bind_param("iss", $user_id, $token, $expires_at);
            $stmtInsert->execute();

            // Reset link
            $resetLink = "http://localhost/QuizQuest/reset_password.php?token=" . urlencode($token);

            // PHPMailer
            $mail = new PHPMailer(true);
            try {
                $mail->isSMTP();
                $mail->Host       = 'smtp.gmail.com'; // SMTP server
                $mail->SMTPAuth   = true;
                $mail->Username   = 'campoandreiannilov@gmail.com';       // your email
                $mail->Password   = 'wdaz mcyb zofe nbcc';          // app password
                $mail->SMTPSecure = 'tls';
                $mail->Port       = 587;

                $mail->setFrom('no-reply@quizquest.com', 'QuizQuest');
                $mail->addAddress($email);

                $mail->isHTML(true);
                $mail->Subject = 'QuizQuest Password Reset';
                $mail->Body    = "Hello,<br><br>We received a request to reset your QuizQuest password.<br>
                                  Click the link below to reset it (valid for 1 hour):<br>
                                  <a href='$resetLink'>$resetLink</a><br><br>
                                  If you did not request this, ignore this email.";

                $mail->send();
                $message = "If an account with that email exists, a reset link has been sent.";

            } catch (Exception $e) {
                $error = "Email could not be sent. Mailer Error: {$mail->ErrorInfo}";
            }

        } else {
            $message = "If an account with that email exists, a reset link has been sent.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password - QuizQuest</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <link rel="stylesheet" href="assets/css/forgot_password.css">
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
            <h2> Forgot Password </h2>
            <div class="bottom-info">
                <div class="side-line"></div>
                <p>Enter your registered email and we'll send you a reset link.</p>
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

            <?php if (!empty($message)) : ?>
                <div class="success-box mb-2"><?php echo $message; ?></div>
            <?php endif; ?>

            <form method="POST">
                <input type="email" name="email" class="form-control form-control-sm mb-2" placeholder="Enter your email" required>

                <div class="login-footer">
                    <button type="button" onclick="window.location.href='login.php'" class="btn btn-secondary">Back to Login</button>
                    <button type="submit" name="reset_request">Send Reset Link</button>
                </div>
            </form>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="teacherscripts.js"></script>
</body>
</html>

<?php $conn->close(); ?>

