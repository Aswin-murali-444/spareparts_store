<?php
session_start();

// Check if the user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Add this function to format timestamps
function formatTimestamp($timestamp) {
    if ($timestamp instanceof MongoDB\BSON\UTCDateTime) {
        $dateTime = $timestamp->toDateTime();
        return $dateTime->format('M d, Y - h:i A');
    } else if (is_string($timestamp)) {
        return $timestamp; // If it's already a string, return as is
    }
    return 'N/A'; // Default fallback
}

require "./mongodb_connection.php"; // Include the MongoDB connection file

// Fetch all messages from the contact_info collection
try {
    $contact_info_collection = $db->contact_info;
    $messages = $contact_info_collection->find()->toArray();
    

    // Ensure each message has a 'read' field
    foreach ($messages as &$message) {
        if (!isset($message['read'])) {
            $message['read'] = false; // Default to unread
        }
    }
} catch (MongoDB\Driver\Exception\Exception $e) {
    $messages = [];
    $errorMessage = 'Error fetching messages: ' . $e->getMessage();
}

// Initialize variables
$totalUsers = 0;
$newUsers = 0;
$activeUsersCount = 0;
$blockedUsersCount = 0;
$pendingRequests = 0;
$systemUptime = "N/A";

require "./mongodb_connection.php"; // Include the MongoDB connection file

try {
    $collection = $db->users; // Assuming you have a 'users' collection
    
    // Count blocked users
    $blockedUsersCount = $collection->countDocuments(['status' => 'blocked']);
    
    // Count active (unblocked) users
    $activeUsersCount = $collection->countDocuments(['status' => 'active']); // Or any other status you consider as "active"
    
    // Total users (optional, if you want to display total as well)
    $totalUsers = $collection->countDocuments();

} catch (MongoDB\Driver\Exception\Exception $e) {
    $errorMessage = 'Error fetching user counts: ' . $e->getMessage();
    $blockedUsersCount = 0;
    $activeUsersCount = 0;
    $totalUsers = 0;
}

// Placeholder data for dashboard metrics
$newUsers = 28;
$systemUptime = "99.8%";
$recentActivities = [
    ["time" => "Today, 10:15 AM", "user" => "John Doe", "action" => "Created new user account"],
    ["time" => "Today, 09:32 AM", "user" => "Jane Smith", "action" => "Updated system settings"],
    ["time" => "Yesterday, 05:47 PM", "user" => "Mike Johnson", "action" => "Deleted obsolete records"],
    ["time" => "Yesterday, 02:13 PM", "user" => "Sarah Williams", "action" => "Modified user permissions"],
    ["time" => "2 days ago", "user" => "Robert Brown", "action" => "System backup completed"]
];

// Add handler for marking messages as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'mark_read') {
    $messageId = $_POST['message_id'];

    try {
        $contact_info_collection = $db->contact_info;
        $updateResult = $contact_info_collection->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($messageId)],
            ['$set' => ['read' => true]]
        );

        // Return success response for AJAX
        if ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode(['success' => true]);
            exit;
        }
    } catch (Exception $e) {
        // Return error response for AJAX
        if ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}

// Add handler for deleting messages
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_message') {
    $messageId = $_POST['message_id'];

    try {
        $contact_info_collection = $db->contact_info;
        $deleteResult = $contact_info_collection->deleteOne(
            ['_id' => new MongoDB\BSON\ObjectId($messageId)]
        );

        // Return success response for AJAX
        if ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode(['success' => true]);
            exit;
        }
    } catch (Exception $e) {
        // Return error response for AJAX
        if ($_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') {
            echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            exit;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
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
        .stat-card {
            border-left: 4px solid;
            border-radius: 4px;
            transition: transform 0.3s;
        }
        .stat-card:hover {
            transform: translateY(-5px);
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
        /* Modal Header */
        .modal-header.bg-primary {
            background-color: #0d6efd !important;
            border-bottom: none;
        }

        /* Modal Body */
        .modal-body {
            padding: 20px;
        }

        /* Table Styling */
        .table-striped tbody tr:nth-of-type(odd) {
            background-color: rgba(0, 0, 0, 0.05);
        }

        .table-hover tbody tr:hover {
            background-color: rgba(0, 0, 0, 0.075);
        }

        .table thead th {
            font-weight: 600;
            background-color: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }

        /* Modal Footer */
        .modal-footer {
            border-top: none;
            padding: 15px 20px;
        }

        /* Close Button */
        .btn-close.btn-close-white {
            filter: invert(1);
        }

        /* Badge Styling */
        .badge {
            font-size: 0.9em;
            padding: 0.5em 0.75em;
        }

        /* Button Group Styling */
        .btn-group-sm .btn {
            padding: 0.25rem 0.5rem;
            font-size: 0.875rem;
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
                            <a href="#" class="nav-link active">
                                <i class="bi bi-house-door"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item w-100">
                            <a href="shouser.php" class="nav-link">
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
                                <span class="badge bg-danger rounded-pill notification-badge"></span>
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
                        <h3>Dashboard Overview</h3>
                        <p class="text-muted">Welcome back, Admin</p>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#messagesModal">
                                <i class="bi bi-bell"></i>
                                <span class="badge bg-danger"><?php echo count($messages); ?></span>
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
                
                <!-- Stats Cards -->
                <div class="row px-4">
                    <div class="col-md-6 col-xl-3 mb-4">
                        <div class="card stat-card border-0 shadow-sm" style="border-left-color: #4e73df !important;">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Users</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo number_format($totalUsers); ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-people fs-1 text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-xl-3 mb-4">
                        <div class="card stat-card border-0 shadow-sm" style="border-left-color: #1cc88a !important;">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Active Users</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $activeUsersCount; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-person-check fs-1 text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-xl-3 mb-4">
                        <div class="card stat-card border-0 shadow-sm" style="border-left-color: #36b9cc !important;">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Blocked Users</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $blockedUsersCount; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-person-x fs-1 text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6 col-xl-3 mb-4">
                        <div class="card stat-card border-0 shadow-sm" style="border-left-color: #f6c23e !important;">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col mr-2">
                                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">System Uptime</div>
                                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo $systemUptime; ?></div>
                                    </div>
                                    <div class="col-auto">
                                        <i class="bi bi-cloud-check fs-1 text-gray-300"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Content Row -->
                <div class="row px-4">
                    <!-- Recent Activities -->
                    <div class="col-lg-8 mb-4">
                        <div class="card shadow-sm border-0">
                            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between bg-white">
                                <h6 class="m-0 font-weight-bold">Recent Activities</h6>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="dropdownMenuButton">
                                        <li><a class="dropdown-item" href="#">View All</a></li>
                                        <li><a class="dropdown-item" href="#">Export Data</a></li>
                                        <li><a class="dropdown-item" href="#">Settings</a></li>
                                    </ul>
                                </div>
                            </div>
                            <div class="card-body activity-list">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Time</th>
                                            <th>User</th>
                                            <th>Action</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentActivities as $activity): ?>
                                        <tr>
                                            <td><?php echo $activity['time']; ?></td>
                                            <td><?php echo $activity['user']; ?></td>
                                            <td><?php echo $activity['action']; ?></td>
                                            <td class="text-end">
                                                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-eye"></i></button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer bg-white text-center">
                                <a href="#" class="text-decoration-none">View All Activities</a>
                            </div>
                        </div>
                    </div>

                    <!-- Pending Requests and Quick Actions -->
                    <div class="col-lg-4">
                        <!-- Categories Card -->
                        <div class="card shadow-sm border-0">
                            <div class="card-header py-3 bg-white">
                                <h6 class="m-0 font-weight-bold">Categories</h6>
                            </div>
                            <div class="card-body">
                            <?php
require "./mongodb_connection.php"; // Include the MongoDB connection file
try {
    $collection = $db->categories; // Assuming you have a 'categories' collection
    $categories = $collection->find()->toArray();
    
    if (count($categories) > 0) {
        echo '<ul class="list-group">';
        foreach ($categories as $category) {
            // Use the correct field name 'categoryName'
            $categoryName = $category['categoryName'] ?? 'Unnamed Category';
            echo '<li class="list-group-item d-flex justify-content-between align-items-center">';
            echo htmlspecialchars($categoryName);
            echo '<span class="badge bg-primary rounded-pill">View</span>';
            echo '</li>';
        }
        echo '</ul>';
    } else {
        echo '<p class="text-center">No categories found.</p>';
    }
} catch (MongoDB\Driver\Exception\Exception $e) {
    echo '<p class="text-center text-danger">Error fetching categories: ' . $e->getMessage() . '</p>';
}
?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Messages Modal -->
                <div class="modal fade" id="messagesModal" tabindex="-1" aria-labelledby="messagesModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-lg">
                        <div class="modal-content">
                            <div class="modal-header bg-primary text-white">
                                <h5 class="modal-title" id="messagesModalLabel">
                                    <i class="bi bi-envelope me-2"></i>Messages
                                </h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <?php if (empty($messages)): ?>
                                    <div class="text-center py-5">
                                        <i class="bi bi-inbox fs-1 text-muted"></i>
                                        <p class="mt-3">No messages found</p>
                                    </div>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover align-middle">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Status</th>
                                                    <th>Name</th>
                                                    <th>Email</th>
                                                    <th>Message</th>
                                                    <th>Date</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($messages as $message): ?>
                                                    <tr class="<?php echo $message['read'] ? '' : 'table-active'; ?>">
                                                        <td>
                                                            <?php if ($message['read']): ?>
                                                                <span class="badge bg-secondary rounded-pill"><i class="bi bi-envelope-open me-1"></i>Read</span>
                                                            <?php else: ?>
                                                                <span class="badge bg-danger rounded-pill"><i class="bi bi-envelope-fill me-1"></i>New</span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?php echo htmlspecialchars($message['name']); ?></td>
                                                        <td><?php echo htmlspecialchars($message['email']); ?></td>
                                                        <td>
                                                            <?php 
                                                                $shortMessage = substr(htmlspecialchars($message['message']), 0, 50);
                                                                echo $shortMessage . (strlen($message['message']) > 50 ? '...' : '');
                                                            ?>
                                                        </td>
                                                        <td><?php echo formatTimestamp($message['created_at']); ?></td>
                                                        <td>
                                                            <div class="btn-group btn-group-sm">
                                                                <button type="button" class="btn btn-outline-primary view-message" 
                                                                        data-bs-toggle="modal" 
                                                                        data-bs-target="#viewMessageModal" 
                                                                        data-id="<?php echo htmlspecialchars((string)$message['_id']); ?>"
                                                                        data-name="<?php echo htmlspecialchars($message['name']); ?>"
                                                                        data-email="<?php echo htmlspecialchars($message['email']); ?>"
                                                                        data-message="<?php echo htmlspecialchars($message['message']); ?>"
                                                                        data-date="<?php echo formatTimestamp($message['created_at']); ?>">
                                                                    <i class="bi bi-eye"></i>
                                                                </button>
                                                                <?php if (!$message['read']): ?>
                                                                    <button type="button" class="btn btn-outline-success mark-read" 
                                                                            data-id="<?php echo htmlspecialchars((string)$message['_id']); ?>">
                                                                        <i class="bi bi-check-lg"></i>
                                                                    </button>
                                                                <?php endif; ?>
                                                                <button type="button" class="btn btn-outline-danger delete-message" 
                                                                        data-id="<?php echo htmlspecialchars((string)$message['_id']); ?>">
                                                                    <i class="bi bi-trash"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-success" id="markAllRead">Mark All Read</button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- View Message Modal -->
                <div class="modal fade" id="viewMessageModal" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header bg-info text-white">
                                <h5 class="modal-title">Message Details</h5>
                                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">From:</label>
                                    <div id="message-name" class="border-bottom pb-2"></div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Email:</label>
                                    <div id="message-email" class="border-bottom pb-2"></div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Date:</label>
                                    <div id="message-date" class="border-bottom pb-2"></div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Message:</label>
                                    <div id="message-content" class="border p-3 bg-light rounded"></div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                <button type="button" class="btn btn-success mark-read-from-view" id="markReadViewBtn">Mark as Read</button>
                            </div>
                        </div>
                    </div>
                </div>

               
                
                <!-- Footer -->
                <footer class="sticky-footer mt-5">
                    <div class="container my-auto">
                        <div class="copyright text-center my-auto">
                            <span>Copyright © Admin Dashboard 2025</span>
                        </div>
                    </div>
                </footer>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const notificationButton = document.querySelector('.btn-outline-secondary[data-bs-toggle="modal"]');
            const messagesModal = new bootstrap.Modal(document.getElementById('messagesModal'));

            notificationButton.addEventListener('click', function () {
                messagesModal.show();
            });
        });

        document.addEventListener('DOMContentLoaded', function () {
            // View message modal functionality
            const viewMessageModal = document.getElementById('viewMessageModal');
            if (viewMessageModal) {
                viewMessageModal.addEventListener('show.bs.modal', function (event) {
                    const button = event.relatedTarget;
                    const messageId = button.getAttribute('data-id');
                    const name = button.getAttribute('data-name');
                    const email = button.getAttribute('data-email');
                    const message = button.getAttribute('data-message');
                    const date = button.getAttribute('data-date');
                    
                    // Update the modal content
                    document.getElementById('message-name').textContent = name;
                    document.getElementById('message-email').textContent = email;
                    document.getElementById('message-date').textContent = date;
                    document.getElementById('message-content').textContent = message;
                    
                    // Update the mark as read button with the message ID
                    const markReadBtn = document.getElementById('markReadViewBtn');
                    markReadBtn.setAttribute('data-id', messageId);
                });
            }
            
            // Mark as read functionality
            document.querySelectorAll('.mark-read, .mark-read-from-view').forEach(button => {
                button.addEventListener('click', function() {
                    const messageId = this.getAttribute('data-id');
                    console.log('Marking message as read:', messageId); // Debugging
                    markMessageAsRead(messageId, this);
                });
            });
            
            // Delete message functionality
            document.querySelectorAll('.delete-message').forEach(button => {
                button.addEventListener('click', function() {
                    const messageId = this.getAttribute('data-id');
                    deleteMessage(messageId, this);
                });
            });
            
            // Mark all as read functionality
            const markAllReadBtn = document.getElementById('markAllRead');
            if (markAllReadBtn) {
                markAllReadBtn.addEventListener('click', function() {
                    document.querySelectorAll('.mark-read').forEach(button => {
                        const messageId = button.getAttribute('data-id');
                        if (messageId) {
                            markMessageAsRead(messageId, button);
                        }
                    });
                });
            }
            
            function markMessageAsRead(messageId, buttonElement) {
                // Create form data
                const formData = new FormData();
                formData.append('action', 'mark_read');
                formData.append('message_id', messageId);
                
                // Send AJAX request
                fetch('admin.php', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Find the row containing this button and update it
                        const row = buttonElement.closest('tr');
                        if (row) {
                            row.classList.remove('table-active');
                            const statusBadge = row.querySelector('.badge');
                            if (statusBadge) {
                                statusBadge.className = 'badge bg-secondary rounded-pill';
                                statusBadge.innerHTML = '<i class="bi bi-envelope-open me-1"></i>Read';
                            }
                            
                            // Remove the mark as read button
                            buttonElement.remove();
                            
                            // If in view modal, hide the mark as read button and close modal
                            if (buttonElement.classList.contains('mark-read-from-view')) {
                                buttonElement.style.display = 'none';
                                setTimeout(() => {
                                    const viewModal = bootstrap.Modal.getInstance(document.getElementById('viewMessageModal'));
                                    if (viewModal) viewModal.hide();
                                }, 500);
                            }
                            
                            // Update unread count in notification badge
                            updateUnreadCount();
                        }
                    } else {
                        alert('Error marking message as read');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            }
            
            function deleteMessage(messageId, buttonElement) {
                if (confirm('Are you sure you want to delete this message?')) {
                    // Create form data
                    const formData = new FormData();
                    formData.append('action', 'delete_message');
                    formData.append('message_id', messageId);

                    // Send AJAX request
                    fetch('admin.php', {
                        method: 'POST',
                        body: formData,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove the row from the table
                            const row = buttonElement.closest('tr');
                            if (row) {
                                row.remove();
                            }

                            // Update unread count in notification badge
                            updateUnreadCount();
                        } else {
                            alert('Error deleting message');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                    });
                }
            }
            
            function updateUnreadCount() {
                const unreadCount = document.querySelectorAll('.mark-read').length;
                const badge = document.getElementById('notificationBadge');
                if (badge) {
                    badge.textContent = unreadCount;
                    if (unreadCount === 0) {
                        badge.style.display = 'none';
                    } else {
                        badge.style.display = 'inline-block';
                    }
                }
            }

            // Prevent form submission
            const form = document.querySelector('form');
            if (form) {
                form.addEventListener('submit', function (event) {
                    event.preventDefault();
                });
            }
        });
    </script>
</body>
</html>