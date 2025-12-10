<?php 
session_start();
include 'db.php'; 

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = isset($_SESSION['role']) ? $_SESSION['role'] : 'visitor';

if (!isset($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$id = intval($_GET['id']);

// FIX: Prepared Statement
$stmt = $conn->prepare("SELECT * FROM clients WHERE client_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    echo "Profile not found.";
    exit();
}

$row = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($row['first_name']); ?>'s Profile</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background-color: #f4f6f9; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; }
        .profile-card { background: white; border-radius: 12px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); overflow: hidden; width: 100%; max-width: 400px; text-align: center; }
        .card-header { background: linear-gradient(135deg, #006ec9, #00c6ff); padding: 30px 20px; color: white; }
        .profile-img { width: 120px; height: 120px; border-radius: 50%; border: 5px solid white; object-fit: cover; margin-top: -60px; background: #fff; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .initials-img { width: 120px; height: 120px; border-radius: 50%; border: 5px solid white; background: #eee; color: #555; display: inline-flex; align-items: center; justify-content: center; font-size: 40px; font-weight: bold; margin-top: -60px; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
        .card-body { padding: 20px 30px 40px; }
        .name { font-size: 24px; font-weight: bold; color: #333; margin: 10px 0 5px; }
        .role { color: #006ec9; font-weight: 600; font-size: 14px; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; }
        .info-grid { text-align: left; margin-top: 20px; }
        .info-item { margin-bottom: 15px; border-bottom: 1px solid #f0f0f0; padding-bottom: 10px; }
        .label { font-size: 12px; color: #999; text-transform: uppercase; display: block; margin-bottom: 4px; }
        .value { font-size: 15px; color: #444; font-weight: 500; }
        .bio-section { background: #f9f9f9; padding: 15px; border-radius: 8px; text-align: left; margin: 20px 0; font-size: 14px; color: #666; line-height: 1.5; font-style: italic; }
        .btn-group { display: flex; gap: 10px; margin-top: 20px; }
        .btn { flex: 1; padding: 12px; border-radius: 6px; text-decoration: none; font-weight: bold; font-size: 14px; transition: 0.2s; }
        .btn-edit { background: #f39c12; color: white; } .btn-edit:hover { background: #e67e22; }
        .btn-back { background: #e0e0e0; color: #555; } .btn-back:hover { background: #d0d0d0; }
    </style>
</head>
<body>

    <div class="profile-card">
        <div class="card-header"></div>

        <?php if (!empty($row['profile_image']) && file_exists($row['profile_image'])): ?>
            <img src="<?php echo htmlspecialchars($row['profile_image']); ?>" class="profile-img" alt="Profile">
        <?php else: ?>
            <div class="initials-img">
                <?php echo strtoupper(substr($row['first_name'], 0, 1) . substr($row['last_name'], 0, 1)); ?>
            </div>
        <?php endif; ?>

        <div class="card-body">
            <div class="name"><?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name']); ?></div>
            <div class="role"><?php echo htmlspecialchars($row['role']); ?></div>

            <?php if (!empty($row['bio'])): ?>
                <div class="bio-section">
                    "<?php echo nl2br(htmlspecialchars($row['bio'])); ?>"
                </div>
            <?php endif; ?>

            <div class="info-grid">
                <div class="info-item">
                    <span class="label">Department</span>
                    <span class="value"><?php echo htmlspecialchars($row['department'] ?? 'N/A'); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Email</span>
                    <span class="value"><?php echo htmlspecialchars($row['email']); ?></span>
                </div>
                <div class="info-item">
                    <span class="label">Phone</span>
                    <span class="value"><?php echo htmlspecialchars($row['phone_number']); ?></span>
                </div>
            </div>

            <div class="btn-group">
                <a href="index.php" class="btn btn-back">Back</a>
                
                <?php 
                $is_admin = ($role == 'admin');
                $is_my_profile = ($row['user_id'] == $_SESSION['user_id']);

                if ($is_admin || $is_my_profile): 
                ?>
                    <a href="edit.php?id=<?php echo $id; ?>" class="btn btn-edit">Edit Profile</a>
                <?php endif; ?>
            </div>
        </div>
    </div>

</body>
</html>