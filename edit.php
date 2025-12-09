<?php 
session_start();
include 'db.php'; 

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$current_user_role = $_SESSION['role'] ?? 'visitor';

// Get ID safely
if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = intval($_GET['id']); 

$result = $conn->query("SELECT * FROM clients WHERE client_id=$id");

if ($result->num_rows == 0) {
    echo "Record not found.";
    exit();
}

$row = $result->fetch_assoc();

if(isset($_POST['update'])) {
    // Sanitize inputs
    $first_name = $conn->real_escape_string($_POST['first_name']);
    $last_name = $conn->real_escape_string($_POST['last_name']);
    $department = $conn->real_escape_string($_POST['department']);
    
    // Role Logic: Use posted value (if admin) or keep existing (if not)
    $role = isset($_POST['role']) ? $conn->real_escape_string($_POST['role']) : $conn->real_escape_string($row['role']);
    
    $email = $conn->real_escape_string($_POST['email']);
    $phone_number = $conn->real_escape_string($_POST['phone_number']);
    $bio = $conn->real_escape_string($_POST['bio']); 
    
    // --- Image Upload Logic ---
    $current_image_path = $row['profile_image'] ?? ''; 
    $profile_image = $current_image_path;
    
    if(isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] == 0) {
        $target_dir = "uploads/";
        if (!file_exists($target_dir)) {
            mkdir($target_dir, 0777, true);
        }
        
        $file_extension = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        
        if(in_array($file_extension, $allowed_extensions)) {
            $new_filename = "profile_" . $id . "_" . time() . "." . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                $profile_image = $conn->real_escape_string($target_file);
            }
        }
    }
    
    // 1. Update CLIENTS table
    $sql = "UPDATE clients SET 
            first_name='$first_name', 
            last_name='$last_name', 
            department='$department', 
            role='$role',
            email='$email', 
            phone_number='$phone_number',
            profile_image='$profile_image',
            bio='$bio'
            WHERE client_id=$id";

    if ($conn->query($sql) === TRUE) {
        
        // --- FIX: SYNC ROLE TO USERS TABLE ---
        // We must also update the 'users' table, otherwise the login permission won't change.
        $user_id_to_update = $row['user_id'];
        
        if(!empty($user_id_to_update)) {
            // Update the role in the login table
            $sql_user = "UPDATE users SET role='$role' WHERE user_id='$user_id_to_update'";
            $conn->query($sql_user);
            
            // Optional: If you changed your OWN role, update the session immediately
            if($user_id_to_update == $_SESSION['user_id']) {
                $_SESSION['role'] = $role;
            }
        }

        header("Location: index.php");
        exit();
    } else {
        $error = "Error updating record: " . $conn->error;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profile</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .form-container { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); width: 100%; max-width: 600px; }
        h2 { text-align: center; color: #333; margin-bottom: 20px; }
        label { font-size: 14px; color: #666; font-weight: 500; display: block; margin-bottom: 5px; }
        input[type="text"], input[type="email"], select, textarea { width: 100%; padding: 10px; margin-bottom: 15px; border: 1px solid #ddd; border-radius: 4px; box-sizing: border-box; transition: border 0.3s; }
        input:focus, select:focus, textarea:focus { border-color: #006ec9; outline: none; }
        input[type="submit"] { background-color: #f39c12; color: white; border: none; cursor: pointer; font-weight: bold; padding: 12px; font-size: 16px; margin-top: 10px; width: 100%; }
        input[type="submit"]:hover { background-color: #e67e22; }
        .cancel-btn { display: block; text-align: center; margin-top: 15px; color: #666; text-decoration: none; font-size: 14px; }
        .cancel-btn:hover { color: #333; text-decoration: underline; }
        .row { display: flex; gap: 15px; }
        .col { flex: 1; }
        
        .profile-upload { text-align: center; margin-bottom: 20px; }
        .current-image { width: 100px; height: 100px; object-fit: cover; border-radius: 50%; border: 3px solid #eee; margin-bottom: 10px; }
        .no-image { width: 100px; height: 100px; background: #eee; border-radius: 50%; display: inline-flex; align-items: center; justify-content: center; color: #aaa; font-weight: bold; margin-bottom: 10px; }
        input[type="file"] { font-size: 12px; }
        .readonly-input { background-color: #e9ecef; cursor: not-allowed; color: #6c757d; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Edit User Profile</h2>
        <?php if(isset($error)) { echo "<p style='color:red; text-align:center;'>$error</p>"; } ?>
        
        <form method="POST" action="" enctype="multipart/form-data">
            
            <div class="profile-upload">
                <?php 
                $img_path = $row['profile_image'] ?? null;
                if(!empty($img_path) && file_exists($img_path)): 
                ?>
                    <img src="<?php echo htmlspecialchars($img_path); ?>" class="current-image" alt="Profile">
                <?php else: ?>
                    <div class="no-image">No IMG</div>
                <?php endif; ?>
                <br>
                <input type="file" name="profile_image" accept="image/*">
            </div>

            <div class="row">
                <div class="col">
                    <label>First Name</label>
                    <input type="text" name="first_name" value="<?php echo htmlspecialchars($row['first_name']); ?>" required>
                </div>
                <div class="col">
                    <label>Last Name</label>
                    <input type="text" name="last_name" value="<?php echo htmlspecialchars($row['last_name']); ?>" required>
                </div>
            </div>

            <div class="row">
                <div class="col">
                    <label>Department</label>
                    <input type="text" name="department" value="<?php echo htmlspecialchars($row['department']); ?>">
                </div>
                <div class="col">
                    <label>Role</label>
                    
                    <?php if($current_user_role === 'admin'): ?>
                        <!-- Admin sees dropdown -->
                        <select name="role">
                            <?php 
                            $roles = ['visitor', 'client', 'admin']; 
                            foreach($roles as $r) {
                                $selected = (strtolower($row['role']) == strtolower($r)) ? 'selected' : '';
                                echo "<option value='$r' $selected>" . ucfirst($r) . "</option>";
                            }
                            ?>
                        </select>
                    <?php else: ?>
                        <!-- Others see read-only text -->
                        <input type="text" value="<?php echo htmlspecialchars($row['role']); ?>" class="readonly-input" readonly>
                        <input type="hidden" name="role" value="<?php echo htmlspecialchars($row['role']); ?>">
                    <?php endif; ?>

                </div>
            </div>

            <label>Email Address</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($row['email']); ?>">

            <label>Phone Number</label>
            <input type="text" name="phone_number" value="<?php echo htmlspecialchars($row['phone_number']); ?>">

            <label>Bio / About</label>
            <textarea name="bio" rows="3" placeholder="Brief description..."><?php echo htmlspecialchars($row['bio'] ?? ''); ?></textarea>

            <input type="submit" name="update" value="Save Profile Changes">
            <a href="index.php" class="cancel-btn">Cancel</a>
        </form>
    </div>
</body>
</html>