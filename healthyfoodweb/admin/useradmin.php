<?php
require_once __DIR__ . '/../init.php';

if (!isLoggedIn() || !isAdmin()) {
    header("Location: /app/login.php");
    exit();
}

$message = '';
$error = '';

// 1. Handle Delete Action
if (isset($_GET['delete'])) {
    $userId = intval($_GET['delete']);
    // Protect against self-deletion if needed, but for now simple
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    if ($stmt->execute()) {
        header("Location: useradmin.php?msg=deleted");
        exit();
    } else {
        $error = "Error deleting user: " . $conn->error;
    }
}

// 2. Handle Role Update (via AJAX or Form)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_role') {
    $userId = intval($_POST['id']);
    $role = $_POST['role'] == '1' ? 'admin' : 'customer';
    $stmt = $conn->prepare("UPDATE users SET role = ? WHERE id = ?");
    $stmt->bind_param("si", $role, $userId);
    if ($stmt->execute()) {
        echo "success";
    } else {
        echo "error";
    }
    exit;
}

// 3. Messages
if (isset($_GET['msg'])) {
    if ($_GET['msg'] === 'updated') $message = "User profile updated successfully.";
    if ($_GET['msg'] === 'reset') {
        $username = isset($_GET['user']) ? htmlspecialchars($_GET['user']) : 'User';
        $message = "The password for $username has been successfully changed.";
    }
    if ($_GET['msg'] === 'deleted') $message = "User deleted successfully.";
    if ($_GET['msg'] === 'added') $message = "New user account created successfully.";
    if ($_GET['msg'] === 'added_verify') $message = "User account created! A verification email has been sent to the user.";
    if ($_GET['msg'] === 'added_no_mail') $message = "User account created, but the verification email failed to send. Please verify them manually.";
}

// 4. Fetch Users
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

$query = "SELECT id, username, email, role FROM users";
if ($search !== '') {
    if (is_numeric($search)) {
        $searchInt = intval($search);
        $query .= " WHERE id = $searchInt OR username LIKE '%$search%' OR email LIKE '%$search%'";
    } else {
        $safeSearch = $conn->real_escape_string($search);
        $query .= " WHERE username LIKE '%$safeSearch%' OR email LIKE '%$safeSearch%'";
    }
}
$result = $conn->query($query);
$users = $result->fetch_all(MYSQLI_ASSOC);

// Get actual total users for the stat card
$total_users_query = "SELECT COUNT(*) as total FROM users";
$total_users = $conn->query($total_users_query)->fetch_assoc()['total'];
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <title>User Management</title>
    <meta content="width=device-width, initial-scale=1.0" name="viewport">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/useradmin.css">
</head>

<body>
    <nav>
        <ul>
            <li><a href="../shop.php"
                    style="background-color: #27ae60; color: white; margin-bottom: 20px; font-weight: bold;">← Back to
                    Shop</a></li>
            <li><a href="AdminPanel.php">Admin Panel</a></li>
            <li><a href="#" class="active">Users</a></li>
            <li><a href="products.php">Products</a></li>
            <li><a href="Inventory.php">Inventory Management</a></li>
            <li><a href="order_management.php">Order Management</a></li>
            <li><a href="reviews_management.php">Reviews</a></li>
            <li><a href="/app/logout.php">Logout</a></li>
        </ul>
    </nav>

    <div class="main-content">
        <h1 class="page-title">USERS</h1>

        <?php if ($message): ?>
            <div style="background: #d4edda; color: #155724; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #c3e6cb;">
                <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div style="background: #f8d7da; color: #721c24; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid #f5c6cb;">
                <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>

        <div class="search-container" style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); margin-bottom: 30px;">
            <form action="" method="GET" style="display: flex; gap: 15px; align-items: center; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 300px; position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                    <input type="text" name="search" value="<?php echo htmlspecialchars($search); ?>" placeholder="Search by ID, Username or Email..." 
                        style="width: 100%; padding: 12px 15px 12px 45px; border: 1.5px solid #e2e8f0; border-radius: 10px; font-size: 0.95rem; outline: none; transition: all 0.2s;">
                </div>
                <button type="submit" style="padding: 12px 30px; background: #3498db; color: white; border: none; border-radius: 10px; font-weight: 600; cursor: pointer; transition: background 0.2s;">
                    Search
                </button>
                <?php if ($search !== ''): ?>
                    <a href="useradmin.php" style="padding: 12px 20px; background: #f1f5f9; color: #64748b; border-radius: 10px; text-decoration: none; font-weight: 600; font-size: 0.9rem;">Reset</a>
                <?php endif; ?>
            </form>
        </div>

        <div class="dashboard-cards">
            <div class="card">
                <div class="card-header">
                    <div class="card-icon users">
                        <i class="fas fa-users"></i>
                    </div>
                    <div>
                        <p class="card-title">TOTAL USERS</p>
                        <p class="card-value"><?php echo $total_users; ?></p>
                    </div>
                </div>
            </div>
            <div class="card" style="display: flex; align-items: center; justify-content: center; background: #f8fafc; border: 2px dashed #e2e8f0; box-shadow: none;">
                <a href="add_user.php" style="text-decoration: none; color: #27ae60; font-weight: 700; font-size: 1.1rem; display: flex; align-items: center; gap: 10px;">
                    <i class="fas fa-plus-circle" style="font-size: 1.5rem;"></i> Add New User Account
                </a>
            </div>
        </div>

        <div class="tables-section">
            <div class="table-container">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($users as $user) {
                            echo "<tr>";
                            echo "<td>" . $user['id'] . "</td>";
                            echo "<td>" . htmlspecialchars($user['username']) . "</td>";
                            echo "<td>" . htmlspecialchars($user['email']) . "</td>";
                            echo "<td>";
                            echo "<select onchange='updateRole(" . $user['id'] . ", this.value)'>";
                            if ($user['role'] == 'admin') {
                                echo "<option value='1' selected>Admin</option>";
                                echo "<option value='0'>User</option>";
                            } else {
                                echo "<option value='1'>Admin</option>";
                                echo "<option value='0' selected>User</option>";
                            }
                            echo "</select>";
                            echo "</td>";
                            echo "<td class='actions'>";
                            echo "<a href='edit_user.php?id=" . $user['id'] . "' class='btn-edit' title='Edit Details' style='display:inline-block; padding: 8px; background: #dbeafe; color: #2563eb; border-radius: 6px; margin-right: 5px;'><i class='fas fa-edit'></i></a>";
                            echo "<a href='reset_user_password.php?id=" . $user['id'] . "' class='btn-reset' title='Reset Password' style='display:inline-block; padding: 8px; background: #fef3c7; color: #d97706; border-radius: 6px; margin-right: 5px;'><i class='fas fa-key'></i></a>";
                            echo "<a href='?delete=" . $user['id'] . "' class='btn-delete' title='Delete User' style='display:inline-block; padding: 8px; background: #fee2e2; color: #dc2626; border-radius: 6px;' onclick='return confirm(\"Are you sure you want to delete this user?\")'><i class='fas fa-trash'></i></a>";
                            echo "</td>";
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
<script>
    function updateRole(id, role) {
        const formData = new URLSearchParams();
        formData.append('action', 'update_role');
        formData.append('id', id);
        formData.append('role', role);

        fetch('', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: formData.toString()
        })
        .then(response => response.text())
        .then(result => {
            if (result.trim() !== 'success') {
                alert('Error updating role');
            }
        });
    }
</script>
</html>