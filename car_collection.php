<?php
session_start();

// Check if the user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Include MongoDB connection
require 'mongodb_connection.php';

// Handle form submission for adding a car
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['action'])) {
    $brand = $_POST['brand'];
    $model = $_POST['model'];
    $year = $_POST['year'];
    $variant = $_POST['variant'];

    try {
        // Select the 'cars' collection
        $collection = $db->cars;

        // Insert the new car
        $insertResult = $collection->insertOne([
            'brand' => $brand,
            'model' => $model,
            'year' => (int)$year, // Convert year to integer
            'variant' => $variant,
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ]);

        if ($insertResult->getInsertedCount() > 0) {
            $successMessage = "Car '$brand $model' added successfully!";
        } else {
            $errorMessage = "Failed to add car.";
        }
    } catch (MongoDB\Driver\Exception\Exception $e) {
        $errorMessage = "Error: " . $e->getMessage();
    }
}

// Handle form submission for editing a car
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit') {
    $carId = $_POST['car_id'];
    $brand = $_POST['brand'];
    $model = $_POST['model'];
    $year = $_POST['year'];
    $variant = $_POST['variant'];

    try {
        $collection = $db->cars;
        $updateResult = $collection->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($carId)],
            ['$set' => [
                'brand' => $brand,
                'model' => $model,
                'year' => (int)$year,
                'variant' => $variant
            ]]
        );

        if ($updateResult->getModifiedCount() > 0) {
            $successMessage = "Car updated successfully!";
        } else {
            $errorMessage = "Failed to update car.";
        }
    } catch (MongoDB\Driver\Exception\Exception $e) {
        $errorMessage = "Error updating car: " . $e->getMessage();
    }
}

// Handle car deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $carId = $_POST['car_id'];

    try {
        $collection = $db->cars;
        $deleteResult = $collection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($carId)]);

        if ($deleteResult->getDeletedCount() > 0) {
            $successMessage = "Car deleted successfully!";
        } else {
            $errorMessage = "Failed to delete car.";
        }
    } catch (MongoDB\Driver\Exception\Exception $e) {
        $errorMessage = "Error deleting car: " . $e->getMessage();
    }
}

// Fetch all cars from the database
try {
    $collection = $db->cars;
    $cars = $collection->find([], ['sort' => ['created_at' => -1]])->toArray();
} catch (MongoDB\Driver\Exception\Exception $e) {
    $fetchErrorMessage = "Error fetching cars: " . $e->getMessage();
    $cars = [];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Car Collection - Admin Panel</title>
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
        .form-container h2 {
            margin-bottom: 20px;
        }
        .admin-header {
            background-color: white;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }
        .car-table-container {
            max-width: 1000px;
            margin: 20px auto 50px;
            padding: 20px;
            background-color: white;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }
        .car-table-container h2 {
            margin-bottom: 20px;
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
                            <a href="category.php" class="nav-link">
                                <i class="bi bi-plus-circle"></i> Add Category
                            </a>
                        </li>
                        <li class="nav-item w-100">
                            <a href="car_collection.php" class="nav-link active">
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
                        <h3>Car Collection</h3>
                        <p class="text-muted">Manage your car collection</p>
                    </div>
                    <div class="col-md-6 text-end">
                        <!-- Add any header content here if needed -->
                    </div>
                </div>

                <!-- Form Container -->
                <div class="form-container">
                    <h2>Add New Car</h2>
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
                    <form method="POST" action="car_collection.php">
                        <div class="mb-3">
                            <label for="brand" class="form-label">Brand</label>
                            <input type="text" class="form-control" id="brand" name="brand" required>
                        </div>
                        <div class="mb-3">
                            <label for="model" class="form-label">Model</label>
                            <input type="text" class="form-control" id="model" name="model" required>
                        </div>
                        <div class="mb-3">
                            <label for="year" class="form-label">Year</label>
                            <input type="number" class="form-control" id="year" name="year" required>
                        </div>
                        <div class="mb-3">
                            <label for="variant" class="form-label">Variant</label>
                            <input type="text" class="form-control" id="variant" name="variant" required>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Car</button>
                    </form>
                </div>

                <!-- Car Table Container -->
                <div class="car-table-container">
                    <h2>Existing Cars</h2>
                    <?php if (isset($fetchErrorMessage)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($fetchErrorMessage); ?>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-striped table-bordered">
                                <thead>
                                    <tr>
                                        <th>Brand</th>
                                        <th>Model</th>
                                        <th>Year</th>
                                        <th>Variant</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($cars as $car): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($car['brand']); ?></td>
                                            <td><?php echo htmlspecialchars($car['model']); ?></td>
                                            <td><?php echo htmlspecialchars($car['year']); ?></td>
                                            <td><?php echo htmlspecialchars($car['variant']); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary me-2" data-bs-toggle="modal" data-bs-target="#editModal<?php echo htmlspecialchars((string)$car['_id']); ?>">
                                                    <i class="bi bi-pencil"></i> Edit
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo htmlspecialchars((string)$car['_id']); ?>">
                                                    <i class="bi bi-trash"></i> Delete
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Edit Modal -->
                                        <div class="modal fade" id="editModal<?php echo htmlspecialchars((string)$car['_id']); ?>" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="editModalLabel">Edit Car</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <form method="POST" action="car_collection.php">
                                                            <input type="hidden" name="action" value="edit">
                                                            <input type="hidden" name="car_id" value="<?php echo htmlspecialchars((string)$car['_id']); ?>">
                                                            <div class="mb-3">
                                                                <label for="brand" class="form-label">Brand</label>
                                                                <input type="text" class="form-control" id="brand" name="brand" value="<?php echo htmlspecialchars($car['brand']); ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="model" class="form-label">Model</label>
                                                                <input type="text" class="form-control" id="model" name="model" value="<?php echo htmlspecialchars($car['model']); ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="year" class="form-label">Year</label>
                                                                <input type="number" class="form-control" id="year" name="year" value="<?php echo htmlspecialchars($car['year']); ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label for="variant" class="form-label">Variant</label>
                                                                <input type="text" class="form-control" id="variant" name="variant" value="<?php echo htmlspecialchars($car['variant']); ?>" required>
                                                            </div>
                                                            <button type="submit" class="btn btn-primary">Save Changes</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Delete Modal -->
                                        <div class="modal fade" id="deleteModal<?php echo htmlspecialchars((string)$car['_id']); ?>" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        Are you sure you want to delete the car "<?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>"?
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                        <form method="POST" action="car_collection.php">
                                                            <input type="hidden" name="action" value="delete">
                                                            <input type="hidden" name="car_id" value="<?php echo htmlspecialchars((string)$car['_id']); ?>">
                                                            <button type="submit" class="btn btn-danger">Delete</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.querySelector('.form-container form');

            form.addEventListener('submit', function (event) {
                let isValid = true;

                const brand = document.getElementById('brand').value.trim();
                const model = document.getElementById('model').value.trim();
                const year = document.getElementById('year').value.trim();
                const variant = document.getElementById('variant').value.trim();

                // Basic validation checks
                if (brand === '') {
                    alert('Brand must be filled out');
                    isValid = false;
                }

                if (model === '') {
                    alert('Model must be filled out');
                    isValid = false;
                }

                if (year === '' || isNaN(year)) {
                    alert('Year must be a number and filled out');
                    isValid = false;
                }

                if (variant === '') {
                    alert('Variant must be filled out');
                    isValid = false;
                }

                if (!isValid) {
                    event.preventDefault(); // Prevent form submission
                }
            });
        });
    </script>
</body>
</html> 