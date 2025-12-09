<?php 
session_start();
include 'db.php'; 

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get current user role
$current_user_role = $_SESSION['role'] ?? 'visitor';

// SECURITY: Visitors cannot add records
if ($current_user_role == 'visitor') {
    // Redirect visitors back to dashboard immediately
    header("Location: index.php");
    exit();
}

// Fetch current user details for display
$current_user_id = $_SESSION['user_id'];
$user_sql = "SELECT username, role FROM users WHERE user_id = '$current_user_id'";
$user_result = $conn->query($user_sql);
$current_user_info = $user_result->fetch_assoc();

if(isset($_POST['submit'])) {
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $department = $conn->real_escape_string($_POST['department']);
    
    // Role Logic for Insertion
    // If Admin, allow them to set whatever role they picked
    if ($current_user_role == 'admin') {
        $role = $conn->real_escape_string($_POST['role']);
    } else {
        // If Client, force the role to be 'Client' (or 'Visitor' if they selected it), 
        // but NEVER 'Admin'.
        $selected_role = $conn->real_escape_string($_POST['role']);
        if(strtolower($selected_role) == 'admin') {
            $role = 'Client'; // Fallback if they try to bypass
        } else {
            $role = $selected_role;
        }
    }
    
    $email = $conn->real_escape_string($_POST['email']);
    $phone_number = $conn->real_escape_string($_POST['phone_number']);
    
    // Creator ID
    $user_id = $_SESSION['user_id']; 

    $sql = "INSERT INTO clients (first_name, last_name, department, role, email, phone_number, user_id) 
            VALUES ('$first_name', '$last_name', '$department', '$role', '$email', '$phone_number', '$user_id')";
    
    if ($conn->query($sql) === TRUE) {
        header("location: index.php");
        exit();
    } else {
        $error = "Error: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Record</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .form-container { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 500px; }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        label { font-size: 14px; color: #666; font-weight: 500; display: block; margin-bottom: 5px; }
        input, select { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; transition: border 0.3s; }
        input:focus, select:focus { border-color: #006ec9; outline: none; }
        input[type="submit"] { background-color: #28a745; color: white; border: none; cursor: pointer; font-weight: bold; padding: 12px; font-size: 16px; margin-top: 10px; width: 100%; }
        input[type="submit"]:hover { background-color: #218838; }
        .cancel-btn { display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none; font-size: 14px; }
        .cancel-btn:hover { color: #333; text-decoration: underline; }
        .row { display: flex; gap: 10px; }
        .col { flex: 1; }
        
        .user-notice { background-color: #e3f2fd; color: #0d47a1; padding: 10px; border-radius: 4px; font-size: 13px; margin-bottom: 20px; border-left: 4px solid #2196f3; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Add New Record</h2>
        
        <!-- Attribution Notice -->
        <div class="user-notice">
            Adding record as: 
            <strong><?php echo htmlspecialchars($current_user_info['username'] ?? 'Unknown'); ?></strong> 
            (<?php echo ucfirst($current_user_role); ?>)
        </div>

        <?php if(isset($error)) { echo "<p style='color:red; text-align:center;'>$error</p>"; } ?>
        
        <form method="POST" action="">
            <div class="row">
                <div class="col">
                    <label>First Name</label>
                    <input type="text" name="first_name" required>
                </div>
                <div class="col">
                    <label>Last Name</label>
                    <input type="text" name="last_name" required>
                </div>
            </div>

            <div class="row">
                <div class="col">
                    <label>Department</label>
                    <input type="text" name="department" placeholder="e.g. IT, HR">
                </div>
                <div class="col">
                    <label>Role</label>
                    
                    <select name="role">
                        <?php if ($current_user_role == 'admin'): ?>
                            <!-- Admins see all options -->
                            <option value="Admin">Admin</option>
                            <option value="Client">Client</option>
                            <option value="Visitor">Visitor</option>
                        <?php else: ?>
                            <!-- Clients only see Client/Visitor options (Admin HIDDEN) -->
                            <option value="Client">Client</option>
                            <option value="Visitor">Visitor</option>
                        <?php endif; ?>
                    </select>
                    
                </div>
            </div>

            <label>Email Address</label>
            <input type="email" name="email" placeholder="name@company.com">

            <label>Phone Number</label>
            <input type="text" name="phone_number" placeholder="+1 234 567 8900">

            <input type="submit" name="submit" value="Save Record">
            <a href="index.php" class="cancel-btn">Cancel</a>
        </form>
    </div>
</body>
</html>