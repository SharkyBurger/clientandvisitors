<?php 
session_start();
include 'db.php'; 

if(isset($_POST['reset_request'])) {
    $email = $_POST['email'];
    
    // 1. Check if email exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // 2. Generate unique token and expiry (1 hour)
        $token = bin2hex(random_bytes(32));
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // 3. Store token in database
        $update = $conn->prepare("UPDATE users SET reset_token = ?, reset_expiry = ? WHERE email = ?");
        $update->bind_param("sss", $token, $expiry, $email);
        
        if ($update->execute()) {
            // GENERATE DYNAMIC LINK
            // This automatically detects your website URL
            $path = dirname($_SERVER['PHP_SELF']);
            $host = $_SERVER['HTTP_HOST'];
            $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
            $resetLink = "$protocol://$host$path/reset_password.php?token=$token";

            // In a real app, use mail() here. For now, we print it.
            $success = "Recovery link generated! <br><a href='$resetLink'>Click here to reset password</a>";
        } else {
            $error = "Database error: " . $conn->error;
        }
    } else {
        $error = "No account found with that email.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f4f6f9; margin: 0; }
        .box { background: white; padding: 40px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-radius: 8px; width: 100%; max-width: 350px; text-align: center; }
        h2 { color: #333; margin-top: 0; }
        p { color: #666; font-size: 14px; margin-bottom: 20px; }
        input[type="email"] { width: 100%; padding: 12px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; }
        input[type="submit"] { width: 100%; padding: 12px; background-color: #f39c12; color: white; border: none; cursor: pointer; font-weight: bold; border-radius: 4px; font-size: 16px; transition: background 0.3s; }
        input[type="submit"]:hover { background-color: #e67e22; }
        .msg { font-size: 14px; margin-bottom: 15px; padding: 10px; border-radius: 4px; }
        .error { background-color: #ffebee; color: #c62828; border: 1px solid #ffcdd2; }
        .success { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #c8e6c9; word-break: break-all; }
        .back-link { display: block; margin-top: 20px; color: #006ec9; text-decoration: none; font-size: 14px; }
        .back-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Reset Password</h2>
        <?php if(isset($error)) { echo "<div class='msg error'>$error</div>"; } ?>
        <?php if(isset($success)) { echo "<div class='msg success'>$success</div>"; } ?>
        
        <form method="POST" action="">
            <p>Enter your email address and we'll help you reset your password.</p>
            <input type="email" name="email" placeholder="name@example.com" required>
            <input type="submit" name="reset_request" value="Send Reset Link">
        </form>
        
        <a href="login.php" class="back-link">← Back to Login</a>
    </div>
</body>
</html>