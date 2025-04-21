<?php
session_start();

// Check if the user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require "./mongodb_connection.php"; // Include the MongoDB connection file

// Initialize pending requests variable
$pendingRequests = 0; // You need to set this value from your database

try {
    $collection = $db->users; // Assuming you have a 'users' collection
    $users = $collection->find()->toArray();
} catch (MongoDB\Driver\Exception\Exception $e) {
    $errorMessage = 'Error fetching users: ' . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Users Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .sidebar {
            min-height: 100vh;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            background-color: #343a40;
        }
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            border-radius: 0;
            margin-bottom: 5px;
        }
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: white;
            background-color: rgba(255, 255, 255, 0.1);
        }
        .sidebar .nav-link i {
            margin-right: 10px;
        }
        .main-content {
            background-color: #f8f9fa;
        }
        .admin-header {
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            background-color: white;
        }
        .activity-list {
            max-height: 400px;
            overflow-y: auto;
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -5px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0 sidebar">
                <div class="d-flex flex-column align-items-center align-items-sm-start p-3 text-white">
                    <a href="admin.php" class="d-flex align-items-center pb-3 mb-md-0 me-md-auto text-white text-decoration-none">
                        <i class="bi bi-speedometer2 fs-4 me-2"></i>
                        <span class="fs-4">Admin Panel</span>
                    </a>
                    <hr class="text-white w-100">
                    <ul class="nav nav-pills flex-column mb-sm-auto mb-0 align-items-center align-items-sm-start w-100" id="menu">
                        <li class="nav-item w-100">
                            <a href="admin.php" class="nav-link">
                                <i class="bi bi-house-door"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item w-100">
                            <a href="shouser.php" class="nav-link active">
                                <i class="bi bi-people"></i> Users
                            </a>
                        </li>
                        <li class="nav-item w-100">
                            <a href="category.php" class="nav-link">
                                <i class="bi bi-plus-circle"></i> Add Category
                            </a>
                        </li>
                        <li class="nav-item w-100">
                            <a href="car_collection.php" class="nav-link">
                                <i class="bi bi-file-earmark-text"></i> Car Collection
                            </a>
                        </li>
                        <li class="nav-item w-100">
                            <a href="spare_parts_collection.php" class="nav-link">
                                <i class="bi bi-bell"></i> Spare Parts Collection
                            </a>
                        </li>
                        <li class="nav-item w-100">
                            <a href="#" class="nav-link">
                                <i class="bi bi-shield-lock"></i> Permissions
                            </a>
                        </li>
                        <li class="nav-item w-100">
                            <a href="#" class="nav-link">
                                <i class="bi bi-bar-chart"></i> Analytics
                            </a>
                        </li>
                        <li class="nav-item w-100">
                            <a href="logout.php" class="nav-link text-danger">
                                <i class="bi bi-box-arrow-left"></i> Logout
                            </a>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10 main-content">
                <!-- Header -->
                <div class="row admin-header py-3 mb-4">
                    <div class="col-md-6">
                        <h3>Users</h3>
                        <p class="text-muted">Manage users</p>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-bell"></i>
                                <span class="badge bg-danger"><?php echo $pendingRequests; ?></span>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-envelope"></i>
                            </button>
                        </div>
                        <div class="dropdown d-inline-block">
                            <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="bi bi-person-circle"></i> Admin User
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <li><a class="dropdown-item" href="#"><i class="bi bi-person me-2"></i>Profile</a></li>
                                <li><a class="dropdown-item" href="#"><i class="bi bi-gear me-2"></i>Settings</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                
                <!-- Users Table -->
                <div class="row px-4">
                    <div class="col-lg-12">
                        <div class="card shadow-sm border-0">
                            <div class="card-header py-3 bg-white">
                                <h6 class="m-0 font-weight-bold">Users List</h6>
                            </div>
                            <div class="card-body">
                                <?php if (isset($errorMessage)): ?>
                                    <div class="alert alert-danger"><?php echo $errorMessage; ?></div>
                                <?php else: ?>
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th>Status</th>
                                                
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($users as $user): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars((string)$user['_id']); ?></td>
                                                    <td><?php echo htmlspecialchars($user['fullName'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($user['email'] ?? 'N/A'); ?></td>
                                                    <td><?php echo htmlspecialchars($user['role'] ?? 'User'); ?></td>
                                                    <td><?php echo htmlspecialchars($user['status'] ?? 'Active'); ?></td>
                                                    <td>
                                                        <a href="view_user.php?id=<?php echo htmlspecialchars((string)$user['_id']); ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></a>
                                                        <a href="edit_user.php?id=<?php echo htmlspecialchars((string)$user['_id']); ?>" class="btn btn-sm btn-outline-warning"><i class="bi bi-pencil"></i></a>
                                                        <?php if (isset($user['status']) && $user['status'] !== 'blocked'): ?>
                                                            <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#blockModal<?php echo htmlspecialchars((string)$user['_id']); ?>"><i class="bi bi-ban"></i></button>
                                                        <?php else: ?>
                                                            <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#unblockModal<?php echo htmlspecialchars((string)$user['_id']); ?>"><i class="bi bi-check-circle"></i></button>
                                                        <?php endif; ?>
                                                        
                                                        <!-- Block Modal -->
                                                        <div class="modal fade" id="blockModal<?php echo htmlspecialchars((string)$user['_id']); ?>" tabindex="-1" aria-labelledby="blockModalLabel" aria-hidden="true">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title" id="blockModalLabel">Confirm Block</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        Are you sure you want to block user: <?php echo htmlspecialchars($user['fullName'] ?? 'this user'); ?>?
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <form action="block_user.php" method="POST">
                                                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars((string)$user['_id']); ?>">
                                                                            <button type="submit" class="btn btn-danger">Block</button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <!-- Unblock Modal -->
                                                        <div class="modal fade" id="unblockModal<?php echo htmlspecialchars((string)$user['_id']); ?>" tabindex="-1" aria-labelledby="unblockModalLabel" aria-hidden="true">
                                                            <div class="modal-dialog">
                                                                <div class="modal-content">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title" id="unblockModalLabel">Confirm Unblock</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        Are you sure you want to unblock user: <?php echo htmlspecialchars($user['fullName'] ?? 'this user'); ?>?
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <form action="unblock_user.php" method="POST">
                                                                            <input type="hidden" name="user_id" value="<?php echo htmlspecialchars((string)$user['_id']); ?>">
                                                                            <button type="submit" class="btn btn-success">Unblock</button>
                                                                        </form>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Footer -->
              
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>