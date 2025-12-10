<?php 
include 'db.php'; 

if(isset($_POST['register'])) {
    $username = trim($_POST['username'] ?? ''); 
    $first_name = trim($_POST['first_name'] ?? ''); 
    $last_name = trim($_POST['last_name'] ?? '');   
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? ''; 
    
    $role = 'visitor'; 
    
    if(empty($username) || empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        // FIX: Use Prepared Statement to prevent SQL Injection
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email=? OR username=?");
        $stmt->bind_param("ss", $email, $username);
        $stmt->execute();
        $stmt->store_result();
        
        if($stmt->num_rows > 0){
            $error = "Email or Username already exists!";
        } else {
            $stmt->close();

            // FIX: Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert into USERS
            $stmt_user = $conn->prepare("INSERT INTO users (username, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt_user->bind_param("ssss", $username, $email, $hashed_password, $role);
            
            if($stmt_user->execute()) {
                $new_user_id = $conn->insert_id;
                $stmt_user->close();
                
                // Insert into CLIENTS
                $default_dept = 'Not Assigned';
                $default_phone = 'N/A';
                
                $stmt_client = $conn->prepare("INSERT INTO clients (first_name, last_name, email, role, department, phone_number, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt_client->bind_param("ssssssi", $first_name, $last_name, $email, $role, $default_dept, $default_phone, $new_user_id);
                
                if($stmt_client->execute()) {
                    header("Location: login.php");
                    exit();
                } else {
                    $error = "Error creating profile: " . $conn->error;
                }
            } else {
                $error = "Error creating user: " . $conn->error;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register</title>
    <style>
        /* [Your existing CSS styles] */
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; display: flex; justify-content: center; align-items: center; height: 100vh; background-color: #f4f6f9; margin: 0; }
        .register-box { background: white; padding: 40px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border-radius: 8px; width: 320px; }
        .register-box h2 { text-align: center; color: #333; margin-bottom: 25px; }
        label { font-size: 12px; color: #666; font-weight: 600; display: block; margin-bottom: 5px; }
        input { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; box-sizing: border-box; border-radius: 4px; transition: 0.3s; }
        input:focus { border-color: #006ec9; outline: none; }
        input[type="submit"] { width: 100%; padding: 12px; background-color: #28a745; color: white; border: none; cursor: pointer; font-weight: bold; font-size: 16px; margin-top: 10px; border-radius: 4px; }
        input[type="submit"]:hover { background-color: #218838; }
        .error { background-color: #ffebee; color: #c62828; padding: 10px; border-radius: 4px; text-align: center; font-size: 13px; margin-bottom: 15px; border: 1px solid #ffcdd2; }
        .login-link { text-align: center; font-size: 14px; margin-top: 20px; color: #666; }
        .login-link a { color: #006ec9; text-decoration: none; font-weight: 600; }
        .login-link a:hover { text-decoration: underline; }
        .name-row { display: flex; gap: 10px; }
        .name-col { flex: 1; }
    </style>
</head>
<body>
    <div class="register-box">
        <h2>Create Account</h2>
        <?php if(isset($error)) { echo "<div class='error'>".htmlspecialchars($error)."</div>"; } ?>
        
        <form method="POST" action="">
            <label>Username</label>
            <input type="text" name="username" placeholder="Choose a username" required>
            <div class="name-row">
                <div class="name-col">
                    <label>First Name</label>
                    <input type="text" name="first_name" placeholder="John" required>
                </div>
                <div class="name-col">
                    <label>Last Name</label>
                    <input type="text" name="last_name" placeholder="Doe" required>
                </div>
            </div>
            <label>Email Address</label>
            <input type="email" name="email" placeholder="name@example.com" required>
            <label>Password</label>
            <input type="password" name="password" placeholder="Create a password" required>
            <input type="submit" name="register" value="Sign Up">
        </form>
        <p class="login-link">Already have an account? <a href="login.php">Log In</a></p>
    </div>
</body>
</html>