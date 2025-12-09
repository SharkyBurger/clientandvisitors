<?php 
session_start();
include 'db.php'; 

if(isset($_POST['login'])) {
    $email = $_POST['email'];
    $password = $_POST['password']; 

    // Fetch user AND role
    $sql = "SELECT * FROM users WHERE email='$email' AND password='$password'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $_SESSION['user_id'] = $row['user_id'];
        $_SESSION['email'] = $row['email'];
        $_SESSION['role'] = $row['role']; // Save role to session
        header("Location: index.php");
    } else {
        $error = "Invalid email or password";
    }
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
    </style>
</head>
<body>
    <div class="login-box">
        <h2>Client System</h2>
        <?php if(isset($error)) { echo "<div class='error'>$error</div>"; } ?>
        <form method="POST" action="">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="submit" name="login" value="Login">
        </form>
        <p style="text-align: center; font-size: 14px;">No account? <a href="register.php">Register here</a></p>
    </div>
</body>
</html>