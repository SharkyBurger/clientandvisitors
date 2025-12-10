<?php
// --- BACKEND LOGIC ---
$resetLink = ""; // Initialize empty variable

// Check if the user clicked the "Send" button
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // 1. Get the email the user typed
    $email = $_POST['email'];

    // 2. Generate a fake random token (Simulation)
    $token = bin2hex(random_bytes(16));

    // 3. Create the "Magic Link"
    // This points to a fictional 'reset_confirm.php' page
    $resetLink = "reset_confirm.php?token=" . $token . "&email=" . urlencode($email);
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <style>
        body { font-family: sans-serif; background: #f4f6f9; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .card { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 350px; text-align: center; }
        .input-group { margin-bottom: 15px; }
        input { width: 90%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
        button { width: 100%; padding: 10px; background-color: #f39c12; color: white; border: none; border-radius: 4px; cursor: pointer; font-weight: bold; }
        button:hover { background-color: #e67e22; }
        .alert-success { background-color: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; border: 1px solid #c3e6cb; }
    </style>
</head>
<body>

    <div class="card">
        <h2>Reset Password</h2>

        <?php if (!empty($resetLink)): ?>
            <div class="alert-success">
                Recovery link generated!<br>
                <a href="<?php echo $resetLink; ?>">Click here to reset password</a>
            </div>
        <?php endif; ?>

        <p style="color: #666; font-size: 14px;">Enter your email address and we'll help you reset your password.</p>

        <form action="" method="POST">
            <div class="input-group">
                <input type="email" name="email" placeholder="name@example.com" required>
            </div>
            <button type="submit">Send Reset Link</button>
        </form>
        
        <br>
        <a href="login.php" style="text-decoration: none; color: #3498db;">← Back to Login</a>
    </div>

</body>
</html>