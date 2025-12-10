<?php
session_start();
include 'db.php';

$error = null;
$success = null;
$token_valid = false;

if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // 1. Validate token and its expiry
    $stmt = $conn->prepare("SELECT user_id, reset_expiry FROM users WHERE reset_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $expiry_time = strtotime($row['reset_expiry']);

        if ($expiry_time > time()) {
            $token_valid = true;
            $user_id = $row['user_id'];
        } else {
            $error = "This password reset link has expired.";
        }
    } else {
        $error = "Invalid password reset link.";
    }
    $stmt->close();
} else {
    $error = "No reset token provided.";
}

if ($token_valid && isset($_POST['reset_password'])) {
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    if ($new_password === $confirm_password) {
        // Hash the new password
        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

        // Update the password and clear the reset token
        $update_stmt = $conn->prepare("UPDATE users SET password = ?, reset_token = NULL, reset_expiry = NULL WHERE user_id = ?");
        $update_stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($update_stmt->execute()) {
            // Redirect to login page with a success message
            header("Location: login.php?msg=password_reset");
            exit();
        } else {
            $error = "Failed to reset password. Please try again.";
        }
        $update_stmt->close();
    } else {
        $error = "Passwords do not match.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f4f6f9; margin: 0; }
        .box { background: white; padding: 40px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-radius: 8px; width: 100%; max-width: 350px; text-align: center; }
        h2 { color: #333; margin-top: 0; }
        .msg { font-size: 14px; margin-bottom: 15px; padding: 10px; border-radius: 4px; }
        .error { background-color: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        .success { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; }
        input[type="password"] { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        input[type="submit"] { width: 100%; padding: 12px; background-color: #28a745; color: white; border: none; cursor: pointer; font-weight: bold; border-radius: 4px; font-size: 16px; transition: background 0.3s; }
        input[type="submit"]:hover { background-color: #218838; }
        .back-link { display: block; margin-top: 20px; color: #006ec9; text-decoration: none; font-size: 14px; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Set New Password</h2>

        <?php if($error): ?>
            <div class="msg error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if($token_valid): ?>
            <form method="POST" action="">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($_GET['token']); ?>">
                
                <input type="password" name="new_password" id="new_password" placeholder="Enter new password" required>
                <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password" required>
                
                <div style="display: flex; align-items: center; margin-bottom: 10px; text-align: left;">
                    <input type="checkbox" onclick="togglePasswordVisibility()" id="showPassword">
                    <label for="showPassword" style="margin-left: 5px; font-size: 14px;">Show Passwords</label>
                </div>
                
                <input type="submit" name="reset_password" value="Reset Password">
            </form>
        <?php else: ?>
             <a href="forgot_password.php" class="back-link">Request a new link</a>
        <?php endif; ?>

    </div>

    <script>
        function togglePasswordVisibility() {
            var newPassword = document.getElementById("new_password");
            var confirmPassword = document.getElementById("confirm_password");
            if (newPassword.type === "password") {
                newPassword.type = "text";
                confirmPassword.type = "text";
            } else {
                newPassword.type = "password";
                confirmPassword.type = "password";
            }
        }
    </script>
</body>
</html>
