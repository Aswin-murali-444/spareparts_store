<?php
session_start();

// Check if the user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Include MongoDB connection
require 'mongodb_connection.php';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $categoryName = $_POST['categoryName'];
    $categoryDescription = $_POST['categoryDescription'];

    try {
        // Select the 'categories' collection
        $collection = $db->categories;

        // Insert the new category
        $insertResult = $collection->insertOne([
            'categoryName' => $categoryName,
            'categoryDescription' => $categoryDescription,
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ]);

        if ($insertResult->getInsertedCount() > 0) {
            $successMessage = "Category '$categoryName' added successfully!";
        } else {
            $errorMessage = "Failed to add category.";
        }
    } catch (MongoDB\Driver\Exception\Exception $e) {
        $errorMessage = "Error: " . $e->getMessage();
    }
}

// Handle form submission for editing an existing category
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $categoryId = $_POST['category_id'];

    // Debugging: Output the category ID to check its value
    echo "Category ID: " . htmlspecialchars($categoryId) . "<br>";

    $categoryName = $_POST['categoryName'];
    $categoryDescription = $_POST['categoryDescription'];

    try {
        $collection = $db->categories;
        $updateResult = $collection->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($categoryId)],
            ['$set' => [
                'categoryName' => $categoryName,
                'categoryDescription' => $categoryDescription
            ]]
        );

        // Debugging: Output the result of the update operation
        echo "Modified Count: " . $updateResult->getModifiedCount() . "<br>";

        if ($updateResult->getModifiedCount() > 0) {
            $successMessage = "Category updated successfully!";
        } else {
            $errorMessage = "Failed to update category.";
        }
    } catch (MongoDB\Driver\Exception\Exception $e) {
        $errorMessage = "Error updating category: " . $e->getMessage();
    }
}

// Fetch all categories
try {
    $collection = $db->categories;
    $categories = $collection->find([], ['sort' => ['created_at' => -1]]);
} catch (MongoDB\Driver\Exception\Exception $e) {
    $fetchErrorMessage = "Error fetching categories: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Category - Admin Panel</title>
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
        .main-content {
            background-color: #f8f9fa;
            padding-bottom: 50px;
        }
        .form-container {
            max-width: 600px;
            margin: 50px auto 30px;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .categories-container {
            max-width: 1000px;
            margin: 0 auto 50px;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .form-container h2, .categories-container h2 {
            margin-bottom: 20px;
        }
        .category-card {
            margin-bottom: 20px;
            transition: transform 0.3s ease;
        }
        .category-card:hover {
            transform: translateY(-5px);
        }
        .admin-header {
            background-color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
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
                            <a href="shouser.php" class="nav-link">
                                <i class="bi bi-people"></i> Users
                            </a>
                        </li>
                        <li class="nav-item w-100">
                            <a href="category.php" class="nav-link active">
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
                        <h3>Add Category</h3>
                        <p class="text-muted">Add a new category to the system</p>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="btn-group me-2">
                            <button type="button" class="btn btn-sm btn-outline-secondary">
                                <i class="bi bi-bell"></i>
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

                <!-- Form Container -->
                <div class="form-container">
                    <h2>Add New Category</h2>
                    <?php if (isset($successMessage)): ?>
                        <div class="alert alert-success" role="alert">
                            <?php echo htmlspecialchars($successMessage); ?>
                        </div>
                    <?php endif; ?>
                    <?php if (isset($errorMessage)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($errorMessage); ?>
                        </div>
                    <?php endif; ?>
                    <form id="categoryForm" method="POST" action="category.php">
                        <div class="mb-3">
                            <label for="categoryName" class="form-label">Category Name</label>
                            <input type="text" class="form-control" id="categoryName" name="categoryName" required>
                            <div class="invalid-feedback">Please enter a category name.</div>
                        </div>
                        <div class="mb-3">
                            <label for="categoryDescription" class="form-label">Category Description</label>
                            <textarea class="form-control" id="categoryDescription" name="categoryDescription" rows="3" required></textarea>
                            <div class="invalid-feedback">Please enter a category description.</div>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Category</button>
                    </form>
                </div>

                <!-- Categories Display -->
                <div class="categories-container">
                    <h2>Existing Categories</h2>
                    <?php if (isset($fetchErrorMessage)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($fetchErrorMessage); ?>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php 
                            $hasCategories = false;
                            foreach ($categories as $category): 
                                $hasCategories = true;
                            ?>
                                <div class="col-md-6 col-lg-4">
                                    <div class="card category-card">
                                        <div class="card-body">
                                            <h5 class="card-title"><?php echo htmlspecialchars($category['categoryName']); ?></h5>
                                            <p class="card-text"><?php echo htmlspecialchars($category['categoryDescription']); ?></p>
                                            <div class="d-flex justify-content-end mt-3">
                                                <button class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#editModal<?php echo htmlspecialchars((string)$category['_id']); ?>">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo htmlspecialchars((string)$category['_id']); ?>">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Edit Modal -->
                                    <div class="modal fade" id="editModal<?php echo htmlspecialchars((string)$category['_id']); ?>" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="editModalLabel">Edit Category</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    <form id="editForm<?php echo htmlspecialchars((string)$category['_id']); ?>" method="POST" action="category.php">
                                                        <input type="hidden" name="action" value="edit">
                                                        <input type="hidden" name="category_id" value="<?php echo htmlspecialchars((string)$category['_id']); ?>">
                                                        <div class="mb-3">
                                                            <label for="categoryName" class="form-label">Category Name</label>
                                                            <input type="text" class="form-control" id="categoryName" name="categoryName" value="<?php echo htmlspecialchars($category['categoryName']); ?>" required>
                                                        </div>
                                                        <div class="mb-3">
                                                            <label for="categoryDescription" class="form-label">Category Description</label>
                                                            <textarea class="form-control" id="categoryDescription" name="categoryDescription" rows="3" required><?php echo htmlspecialchars($category['categoryDescription']); ?></textarea>
                                                        </div>
                                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Delete Modal -->
                                    <div class="modal fade" id="deleteModal<?php echo htmlspecialchars((string)$category['_id']); ?>" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">
                                                <div class="modal-header">
                                                    <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                </div>
                                                <div class="modal-body">
                                                    Are you sure you want to delete the category "<?php echo htmlspecialchars($category['categoryName']); ?>"?
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <form action="delete_category.php" method="POST">
                                                        <input type="hidden" name="category_id" value="<?php echo htmlspecialchars((string)$category['_id']); ?>">
                                                        <button type="submit" class="btn btn-danger">Delete</button>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <?php if (!$hasCategories): ?>
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        No categories found. Add your first category using the form above.
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('categoryForm');
            const categoryName = document.getElementById('categoryName');
            const categoryDescription = document.getElementById('categoryDescription');

            form.addEventListener('submit', function(event) {
                if (!categoryName.value.trim() || !categoryDescription.value.trim()) {
                    event.preventDefault();
                    event.stopPropagation();

                    if (!categoryName.value.trim()) {
                        categoryName.classList.add('is-invalid');
                    } else {
                        categoryName.classList.remove('is-invalid');
                    }

                    if (!categoryDescription.value.trim()) {
                        categoryDescription.classList.add('is-invalid');
                    } else {
                        categoryDescription.classList.remove('is-invalid');
                    }
                } else {
                    categoryName.classList.remove('is-invalid');
                    categoryDescription.classList.remove('is-invalid');
                }
            });

            // Auto-dismiss alerts after 5 seconds
            const alerts = document.querySelectorAll('.alert-success, .alert-danger');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    alert.style.transition = 'opacity 1s';
                    alert.style.opacity = '0';
                    setTimeout(function() {
                        alert.style.display = 'none';
                    }, 1000);
                }, 5000);
            });
        });
    </script>
</body>
</html>