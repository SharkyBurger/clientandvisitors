<?php 
session_start();
include 'db.php'; 

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Get user role, default to visitor if missing
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'visitor';

// Get current tab from URL, default to 'dashboard'
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'dashboard';

// --- AUTO-CREATE / CHECK PROFILE LOGIC ---
$current_user_id = $_SESSION['user_id'];

// 1. Check if a profile already exists for this user (AND FETCH IMAGE)
$profile_sql = "SELECT client_id, profile_image FROM clients WHERE user_id = '$current_user_id' LIMIT 1";
$profile_result = $conn->query($profile_sql);

$my_profile_id = null;
$my_profile_img = null;

if ($profile_result && $profile_result->num_rows > 0) {
    // Profile exists, get the ID and Image
    $data = $profile_result->fetch_assoc();
    $my_profile_id = $data['client_id'];
    $my_profile_img = $data['profile_image'];
} else {
    // 2. NO PROFILE FOUND -> AUTO-CREATE ONE
    $user_fetch = $conn->query("SELECT username, email, role FROM users WHERE user_id = '$current_user_id'");
    if ($user_fetch && $user_fetch->num_rows > 0) {
        $user_data = $user_fetch->fetch_assoc();
        
        $new_firstname = $user_data['username']; 
        $new_lastname = "(New)";               
        $new_email = $user_data['email'];
        $new_role = $user_data['role'];        
        
        $insert_sql = "INSERT INTO clients (first_name, last_name, email, role, department, phone_number, user_id) 
                       VALUES ('$new_firstname', '$new_lastname', '$new_email', '$new_role', 'Not Assigned', 'N/A', '$current_user_id')";
        
        if ($conn->query($insert_sql) === TRUE) {
            $my_profile_id = $conn->insert_id; 
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Client Management</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background-color: #f4f6f9; color: #333; }

        /* Fixed Header */
        .header { background-color: #006ec9; color: white; height: 60px; display: flex; align-items: center; justify-content: space-between; padding: 0 20px; position: fixed; width: 100%; top: 0; z-index: 1000; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .header h1 { font-size: 20px; margin: 0; font-weight: 600; }
        .user-info { font-size: 14px; display: flex; align-items: center; gap: 15px; }
        
        /* Header Profile Avatar */
        .profile-dropdown { position: relative; }
        
        .header-avatar {
            width: 40px; 
            height: 40px;
            background-color: white;
            color: #006ec9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 16px;
            cursor: pointer;
            border: 2px solid rgba(255,255,255,0.8);
            transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            user-select: none;
            overflow: hidden; 
            z-index: 1002;
        }
        
        .header-avatar:hover {
            transform: scale(1.05);
        }

        .header-avatar.active {
            transform: scale(1.1); /* Subtle scale for active state */
            border-color: #fff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        /* Styles for the image inside the avatar */
        .avatar-img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        /* Dropdown Menu */
        .dropdown-content {
            display: none;
            position: absolute;
            right: 0;
            top: 60px;
            background-color: #fff;
            min-width: 260px; /* Slightly wider for the new layout */
            box-shadow: 0 8px 16px rgba(0,0,0,0.15);
            z-index: 1001;
            border-radius: 8px;
            overflow: hidden;
            animation: fadeIn 0.2s ease-out;
            border: 1px solid #eee;
        }
        
        /* Dropdown Header Section */
        .dropdown-header {
            padding: 20px; /* Increased padding */
            background-color: #f8f9fa;
            border-bottom: 1px solid #eee;
            color: #333;
            text-align: center; /* Center everything */
        }
        
        /* NEW: Big Avatar inside Dropdown */
        .dropdown-avatar {
            width: 70px;
            height: 70px;
            margin: 0 auto 10px auto; /* Center horizontally */
            background-color: white;
            color: #006ec9;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            font-size: 24px; /* Larger font for initials */
            border: 3px solid #e0e0e0;
            overflow: hidden;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .dropdown-name { font-weight: bold; display: block; font-size: 15px; color: #2c3e50; }
        .dropdown-role { font-size: 12px; color: #7f8c8d; text-transform: uppercase; font-weight: 600; margin-top: 4px; display: block; }

        /* Dropdown Links */
        .dropdown-content a {
            color: #555;
            padding: 12px 20px;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 14px;
            transition: background 0.1s;
        }
        .dropdown-content a:hover {
            background-color: #f1f5f9;
            color: #006ec9;
        }
        
        /* Disabled Link Style */
        .dropdown-content a.disabled {
            color: #ccc;
            pointer-events: none;
            background-color: #fff;
        }
        
        .dropdown-divider { height: 1px; background-color: #eee; margin: 0; }
        
        .logout-item { color: #e74c3c !important; }
        .logout-item:hover { background-color: #fdf2f2 !important; }

        /* Show class for JS toggle */
        .show { display: block; }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Role Badge */
        .role-badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: bold; text-transform: uppercase; background: rgba(255,255,255,0.2); }
        .role-admin { background: #e74c3c; color: white; }
        .role-client { background: #2ecc71; color: white; }
        .role-visitor { background: #f1c40f; color: black; }
        
        .role-tag { font-size: 0.85em; padding: 2px 8px; border-radius: 12px; font-weight: 600; display: inline-block; }
        .tag-client { background: #e8f5e9; color: #2e7d32; border: 1px solid #a5d6a7; }
        .tag-visitor { background: #fff8e1; color: #f57f17; border: 1px solid #ffe082; }

        /* Sidebar */
        .sidebar { width: 250px; background-color: #2c3e50; color: #ecf0f1; position: fixed; top: 60px; bottom: 0; left: 0; overflow-y: auto; transition: all 0.3s; }
        .sidebar ul { list-style: none; padding: 0; margin: 0; }
        .sidebar a { display: block; color: #bdc3c7; text-decoration: none; padding: 15px 20px; border-bottom: 1px solid #34495e; transition: all 0.2s; }
        .sidebar a:hover { background-color: #34495e; color: white; padding-left: 25px; }
        .sidebar a.active { background-color: #006ec9; color: white; border-left: 4px solid #004d8c; }
        
        .sidebar-header { padding: 15px 20px; font-size: 12px; text-transform: uppercase; color: #7f8c8d; font-weight: bold; margin-top: 10px; }

        /* Main Content */
        .content { margin-left: 250px; margin-top: 60px; padding: 30px; height: calc(100vh - 60px); overflow-y: auto; }

        /* Section Header */
        .section-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; background: white; padding: 15px 20px; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .section-header h2 { margin: 0; font-size: 18px; color: #2c3e50; font-weight: 700; }
        .section-title-wrapper { display: flex; flex-direction: column; }
        .section-subtitle { font-size: 12px; color: #7f8c8d; margin-top: 4px; font-weight: normal; }

        /* Buttons */
        .add-btn { background-color: #28a745; color: white; padding: 8px 16px; text-decoration: none; border-radius: 5px; font-weight: bold; font-size: 14px; transition: background 0.2s; }
        .add-btn:hover { background-color: #218838; }

        /* Table */
        .table-container { background: white; border-radius: 8px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); overflow: hidden; margin-bottom: 40px; }
        .client-table { width: 100%; border-collapse: collapse; }
        .client-table th, .client-table td { padding: 12px 15px; text-align: left; border-bottom: 1px solid #eee; }
        .client-table th { background-color: #f8f9fa; font-weight: 600; color: #555; text-transform: uppercase; font-size: 12px; letter-spacing: 0.5px; }
        .client-table tr:hover { background-color: #f9f9fb; }
        .client-table td { color: #444; font-size: 14px; }

        /* Action Links */
        .action-links a { margin-right: 10px; text-decoration: none; font-weight: 500; font-size: 13px; }
        .view-link { color: #006ec9; }
        .edit-link { color: #f39c12; }
        .delete-link { color: #e74c3c; }
        .view-link:hover, .edit-link:hover, .delete-link:hover { text-decoration: underline; }

        @media (max-width: 768px) {
            .sidebar { width: 70px; }
            .sidebar a span { display: none; }
            .sidebar-header { display: none; }
            .content { margin-left: 70px; }
        }
    </style>
</head>
<body>
    
    <header class="header">
        <h1>ClientManager</h1>
        <div class="user-info">
            <span class="role-badge role-<?php echo strtolower($role); ?>">
                <?php echo ucfirst($role); ?>
            </span>
            <span style="opacity: 0.8;"><?php echo htmlspecialchars($_SESSION['email'] ?? 'User'); ?></span>
            
            <!-- Profile Dropdown -->
            <div class="profile-dropdown">
                <!-- IMPORTANT: Added ID 'profileAvatar' for JS targeting -->
                <div id="profileAvatar" class="header-avatar" onclick="toggleDropdown(event)" title="Click for menu">
                    <?php 
                    // CHECK FOR PROFILE IMAGE
                    if (!empty($my_profile_img) && file_exists($my_profile_img)) {
                        // Display Uploaded Image
                        echo '<img src="'.htmlspecialchars($my_profile_img).'" class="avatar-img" alt="Profile">';
                    } else {
                        // Display First Initial
                        echo strtoupper(substr($_SESSION['email'] ?? 'U', 0, 1));
                    }
                    ?>
                </div>
                
                <!-- Dropdown Menu -->
                <div id="myDropdown" class="dropdown-content">
                    <div class="dropdown-header">
                        
                        <!-- NEW: Big Avatar Display -->
                        <div class="dropdown-avatar">
                            <?php 
                            if (!empty($my_profile_img) && file_exists($my_profile_img)) {
                                echo '<img src="'.htmlspecialchars($my_profile_img).'" class="avatar-img" alt="Profile">';
                            } else {
                                echo strtoupper(substr($_SESSION['email'] ?? 'U', 0, 1));
                            }
                            ?>
                        </div>

                        <span class="dropdown-name"><?php echo htmlspecialchars($_SESSION['email'] ?? 'Unknown User'); ?></span>
                        <span class="dropdown-role"><?php echo ucfirst($role); ?> Account</span>
                    </div>
                    
                    <?php if ($my_profile_id): ?>
                        <a href="profile.php?id=<?php echo $my_profile_id; ?>">
                            <span>👤</span> My Profile
                        </a>
                        <a href="edit.php?id=<?php echo $my_profile_id; ?>">
                            <span>⚙️</span> Edit Profile
                        </a>
                    <?php else: ?>
                        <a href="#" class="disabled">
                            <span>⚠️</span> Profile Error
                        </a>
                    <?php endif; ?>

                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="logout-item">
                        <span>🚪</span> Logout
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div class="sidebar">
        <ul>
            <li><a href="index.php?tab=dashboard" class="<?php echo $tab == 'dashboard' ? 'active' : ''; ?>">Dashboard</a></li>
            
            <!-- Admin Only Menu Items -->
            <?php if($role == 'admin'): ?>
                <div class="sidebar-header">User Management</div>
                <li><a href="index.php?tab=clients" class="<?php echo $tab == 'clients' ? 'active' : ''; ?>">Registered Clients</a></li>
                <li><a href="index.php?tab=visitors" class="<?php echo $tab == 'visitors' ? 'active' : ''; ?>">Registered Visitors</a></li>
            <?php endif; ?>
            
            <div class="sidebar-header">Account</div>
            <li><a href="logout.php">Logout</a></li>
        </ul>
    </div>

    <div class="content">
        
        <!-- DASHBOARD TAB (Default View) -->
        <?php if($tab == 'dashboard'): ?>
            <div class="section-header">
                <div class="section-title-wrapper">
                    <h2>Database Records</h2>
                    <span class="section-subtitle">Manage client information and details</span>
                </div>
                
                <?php if($role == 'admin' || $role == 'client'): ?>
                    <a href="add.php" class="add-btn">+ Add Record</a>
                <?php endif; ?>
            </div>

            <div class="table-container">
                <table class="client-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Name</th>
                            <th>Role</th>
                            <th>Department</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php
                    // --- MODIFIED QUERY: EXCLUDE ADMINS FROM MAIN LIST ---
                    $sql = "SELECT * FROM clients WHERE role != 'admin' ORDER BY created_at DESC";
                    $result = $conn->query($sql);

                    if ($result && $result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            echo "<tr>
                            <td>#{$row['client_id']}</td>
                            <td><strong>{$row['first_name']} {$row['last_name']}</strong></td>
                            <td><span class='role-tag tag-client'>{$row['role']}</span></td>
                            <td>{$row['department']}</td>
                            <td>{$row['email']}</td>
                            <td>{$row['phone_number']}</td>
                            <td class='action-links'>
                                <a href='profile.php?id={$row['client_id']}' class='view-link'>View</a>"; 

                            if($role == 'admin' || $role == 'client') {
                                echo "<a href='edit.php?id={$row['client_id']}' class='edit-link'>Edit</a>";
                                if($role == 'admin') {
                                    echo "<a href='delete.php?id={$row['client_id']}' class='delete-link' onclick='return confirm(\"Are you sure?\")'>Delete</a>";
                                }
                            }
                            echo "</td></tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align:center; padding: 30px;'>No records found.</td></tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>


        <!-- REGISTERED CLIENTS TAB (Admin Only) -->
        <?php if($tab == 'clients' && $role == 'admin'): ?>
            <div class="section-header" style="border-left: 4px solid #2ecc71;">
                <div class="section-title-wrapper">
                    <h2 style="color: #27ae60;">Registered Clients Log</h2>
                    <span class="section-subtitle">List of users registered as Clients in the system</span>
                </div>
            </div>

            <div class="table-container">
                <table class="client-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $clients_query = $conn->query("SELECT * FROM users WHERE role='client' ORDER BY user_id DESC");
                        if ($clients_query && $clients_query->num_rows > 0) {
                            while($u = $clients_query->fetch_assoc()) {
                                echo "<tr>
                                    <td>#{$u['user_id']}</td>
                                    <td><strong>{$u['username']}</strong></td>
                                    <td>{$u['email']}</td>
                                    <td><span class='role-tag tag-client'>Client</span></td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' style='text-align:center; padding: 15px;'>No registered clients found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>


        <!-- REGISTERED VISITORS TAB (Admin Only) -->
        <?php if($tab == 'visitors' && $role == 'admin'): ?>
            <div class="section-header" style="border-left: 4px solid #f1c40f;">
                <div class="section-title-wrapper">
                    <h2 style="color: #d35400;">Registered Visitors Log</h2>
                    <span class="section-subtitle">List of users registered as Visitors in the system</span>
                </div>
            </div>

            <div class="table-container">
                <table class="client-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $visitors_query = $conn->query("SELECT * FROM users WHERE role='visitor' ORDER BY user_id DESC");
                        if ($visitors_query && $visitors_query->num_rows > 0) {
                            while($v = $visitors_query->fetch_assoc()) {
                                echo "<tr>
                                    <td>#{$v['user_id']}</td>
                                    <td><strong>{$v['username']}</strong></td>
                                    <td>{$v['email']}</td>
                                    <td><span class='role-tag tag-visitor'>Visitor</span></td>
                                </tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4' style='text-align:center; padding: 15px;'>No registered visitors found.</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>

    </div>

    <!-- Script to handle dropdown toggle -->
    <script>
        function toggleDropdown(event) {
            // Prevent the click from closing the menu immediately
            event.stopPropagation();
            
            // Toggle Content
            document.getElementById("myDropdown").classList.toggle("show");
            
            // Toggle Avatar Animation (Make it big/small)
            document.getElementById("profileAvatar").classList.toggle("active");
        }

        // Close dropdown if user clicks outside
        window.onclick = function(event) {
            // If click is NOT inside the dropdown menu or the avatar
            if (!event.target.closest('.profile-dropdown')) {
                var dropdowns = document.getElementsByClassName("dropdown-content");
                for (var i = 0; i < dropdowns.length; i++) {
                    var openDropdown = dropdowns[i];
                    if (openDropdown.classList.contains('show')) {
                        openDropdown.classList.remove('show');
                    }
                }
                
                // Also revert avatar size
                var avatar = document.getElementById("profileAvatar");
                if (avatar.classList.contains('active')) {
                    avatar.classList.remove('active');
                }
            }
        }
    </script>
</body>
</html>