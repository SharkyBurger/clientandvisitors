<?php 
session_start();
include 'db.php'; 

if(isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password']; 

    // Secure Login using Prepared Statements
    $stmt = $conn->prepare("SELECT user_id, email, password, role FROM users WHERE email=?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $stored_password = $row['password'];

        // 1. Try secure hash verification first (For new/migrated users)
        if (password_verify($password, $stored_password)) {
            // Success: Password is already secure
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['role'] = $row['role']; 
            header("Location: index.php");
            exit();
        } 
        // 2. Fallback: Check if it is an OLD plain-text password
        elseif ($stored_password === $password) {
            // Success: User is using an old password. 
            // SECURITY UPGRADE: Hash it immediately and update the database.
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
            $update_stmt->bind_param("si", $new_hash, $row['user_id']);
            $update_stmt->execute();
            $update_stmt->close();

            // Log them in now that we've upgraded them
            $_SESSION['user_id'] = $row['user_id'];
            $_SESSION['email'] = $row['email'];
            $_SESSION['role'] = $row['role']; 
            header("Location: index.php");
            exit();
        } else {
            $error = "Invalid email or password";
        }
    } else {
        $error = "Invalid email or password";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <style>
        body { font-family: Arial, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f5f5f5; }
        .login-box { background: white; padding: 40px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); border-radius: 8px; width: 300px; }
        .login-box h2 { text-align: center; color: #333; }
        input { width: 100%; padding: 10px; margin: 10px 0; border: 1px solid #ddd; box-sizing: border-box; border-radius: 4px; }
        input[type="submit"] { width: 100%; padding: 10px; background-color: #006ec9; color: white; border: none; cursor: pointer; }
        input[type="submit"]:hover { background-color: #0056b3; }
        .error { color: red; text-align: center; font-size: 14px; margin-bottom: 10px; }
        .success { color: green; text-align: center; font-size: 14px; margin-bottom: 10px; background: #e8f5e9; padding: 10px; border-radius: 4px; }
        .forgot-link { display: block; text-align: right; font-size: 12px; margin-bottom: 15px; color: #006ec9; text-decoration: none; }
        .forgot-link:hover { text-decoration: underline; }
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Client System</h2>
        
        <?php 
        // Show success message if redirected from reset page
        if(isset($_GET['msg']) && $_GET['msg'] == 'password_reset') {
            echo "<div class='success'>Password reset successfully! <br>Please login.</div>";
        }
        if(isset($error)) { echo "<div class='error'>$error</div>"; } 
        ?>

        <form method="POST" action="">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            
            <a href="forgot_password.php" class="forgot-link">Forgot Password?</a>
            
            <input type="submit" name="login" value="Login">
        </form>
        <p style="text-align: center; font-size: 14px;">No account? <a href="register.php">Register here</a></p>
    </div>
</body>
</html>