<?php
session_start();
require_once 'db.php';

// 1. Handle Logout Request
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header("Location: login.php");
    exit();
}

// 2. If already logged in, redirect straight to Admin Panel
if (isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true) {
    header("Location: admin.php");
    exit();
}

$error = "";

// 3. Process Login Form Submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = clean_input($_POST['username']);
    $password = $_POST['password'];

    if (empty($username) || empty($password)) {
        $error = "Both username and password are required.";
    } else {
        $stmt = $conn->prepare("SELECT admin_id, username, password_hash FROM admins WHERE username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            $admin = $result->fetch_assoc();

            // Verify entered password against stored Bcrypt hash
            if (password_verify($password, $admin['password_hash'])) {
                session_regenerate_id(true);
                $_SESSION['admin_logged_in'] = true;
                $_SESSION['admin_user'] = $admin['username'];

                header("Location: admin.php");
                exit();
            } else {
                $error = "Invalid username or password.";
            }
        } else {
            $error = "Invalid username or password.";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Login - CampusEvents</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="navbar">
    <h2>CampusEvents</h2>
    <div>
        <a href="index.php">Events Catalog</a>
        <a href="about.html">Guidelines</a>
        <a href="register.html">Register Now</a>
        <a href="ticket.php">Find My Pass</a>
        <a href="login.php" class="active">Admin Login</a>
    </div>
</div>

<div class="container">
    <div class="form-box" style="max-width: 420px; margin-top: 3rem;">
        <h2 style="margin-bottom: 1rem; text-align: center;">Admin Login</h2>

        <?php if (!empty($error)): ?>
            <div class="alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <form method="POST" action="login.php" novalidate>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" name="username" id="username" placeholder="Enter admin username" required>
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" name="password" id="password" placeholder="Enter password" required>
            </div>

            <button type="submit" class="btn" style="width: 100%;">Sign In</button>
        </form>
        <p style="font-size: 0.85rem; color: #64748b; text-align: center; margin-top: 1rem;">
            Default Credentials: <strong>admin</strong> / <strong>password</strong>
        </p>
    </div>
</div>

</body>
</html>
