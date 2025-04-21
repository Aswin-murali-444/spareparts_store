<?php
session_start();

// Check if the user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Include MongoDB connection
require 'mongodb_connection.php';

// Check for success query parameter
if (isset($_GET['success'])) {
    if ($_GET['success'] == 1) {
        $successMessage = "Spare part added successfully!";
    } elseif ($_GET['success'] == 2) {
        $successMessage = "Spare part updated successfully!";
    }
}

// Check for error query parameter
if (isset($_GET['error'])) {
    $errorMessage = "Error: " . htmlspecialchars($_GET['error']);
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category_id = $_POST['category_id'];
    $compatible_cars = $_POST['compatible_cars']; // This will be an array of car IDs
    
    // Determine image source
    if (!empty($_FILES['image_upload']['name'])) {
        // Handle image upload
        $uploadDir = "uploads/"; // Create an 'uploads' directory in your project
        $uploadFile = $uploadDir . basename($_FILES['image_upload']['name']);
        
        if (move_uploaded_file($_FILES['image_upload']['tmp_name'], $uploadFile)) {
            $image = $uploadFile; // Save the file path
        } else {
            $errorMessage = "Error uploading image.";
            $image = ''; // Set a default value
        }
    } else {
        $errorMessage = "Please upload an image.";
        $image = '';
    }

    try {
        // Select the 'spare_parts' collection
        $collection = $db->spare_parts;

        // Convert compatible_cars to an array of ObjectIds
        $compatible_cars_object_ids = [];
        foreach ($compatible_cars as $car_id) {
            $compatible_cars_object_ids[] = new MongoDB\BSON\ObjectId($car_id);
        }

        // Insert the new spare part
        $insertResult = $collection->insertOne([
            'name' => $name,
            'description' => $description,
            'price' => (float)$price, // Convert price to float
            'stock' => (int)$stock, // Convert stock to integer
            'category_id' => new MongoDB\BSON\ObjectId($category_id),
            'compatible_cars' => $compatible_cars_object_ids,
            'image' => $image,
            'created_at' => new MongoDB\BSON\UTCDateTime()
        ]);

        if ($insertResult->getInsertedCount() > 0) {
            header("Location: spare_parts_collection.php?success=1");
            exit();
        } else {
            $errorMessage = "Failed to add spare part.";
        }
    } catch (MongoDB\Driver\Exception\Exception $e) {
        $errorMessage = "Error: " . $e->getMessage();
    }
}

// Fetch categories for mapping
$categories_map = [];
try {
    $categories_collection = $db->categories;
    $categories = $categories_collection->find([], ['sort' => ['categoryName' => 1]])->toArray();
    foreach ($categories as $category) {
        $categories_map[(string)$category['_id']] = $category['categoryName'];
    }
} catch (MongoDB\Driver\Exception\Exception $e) {
    $categoriesErrorMessage = "Error fetching categories: " . $e->getMessage();
}

// Fetch cars for mapping
$cars_map = [];
try {
    $cars_collection = $db->cars;
    $cars = $cars_collection->find([], ['sort' => ['brand' => 1, 'model' => 1]])->toArray();
    foreach ($cars as $car) {
        $cars_map[(string)$car['_id']] = $car['brand'] . ' ' . $car['model'];
    }
} catch (MongoDB\Driver\Exception\Exception $e) {
    $carsErrorMessage = "Error fetching cars: " . $e->getMessage();
}

// Fetch spare parts for the table
try {
    $spare_parts_collection = $db->spare_parts;
    $spare_parts = $spare_parts_collection->find([], ['sort' => ['created_at' => -1]])->toArray();
} catch (MongoDB\Driver\Exception\Exception $e) {
    $spare_partsErrorMessage = "Error fetching spare parts: " . $e->getMessage();
    $spare_parts = [];
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Spare Part - Admin Panel</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
            max-width: 90%; /* Increased width */
            margin: 50px auto 30px;
            padding: 30px; /* Increased padding */
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
        .car-card {
            max-width: 200px;
            margin: 0 auto;
        }
        .car-card:hover {
            transform: scale(1.03);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }
        .styled-checkbox {
            appearance: none;
            -webkit-appearance: none;
            height: 1.2em;
            width: 1.2em;
            border: 2px solid #007bff;
            border-radius: 0.25em;
            background-color: white;
            cursor: pointer;
            transition: all 0.2s ease-in-out;
            position: relative;
        }
        .styled-checkbox:checked {
            background-color: #007bff;
            border-color: #007bff;
        }
        .styled-checkbox:checked::before {
            content: '\f26b'; /* Unicode for Bootstrap checkmark icon */
            font-family: 'bootstrap-icons';
            font-size: 0.8em;
            color: white;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        .form-check-label {
            word-break: break-word; /* Prevent long words from breaking the layout */
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
                            <a href="car_collection.php" class="nav-link">
                                <i class="bi bi-file-earmark-text"></i> Car Collection
                            </a>
                        </li>
                        <li class="nav-item w-100">
                            <a href="spare_parts_collection.php" class="nav-link active">
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
                        <h3>Add Spare Part</h3>
                        <p class="text-muted">Add a new spare part to the collection</p>
                    </div>
                    <div class="col-md-6 text-end">
                        <!-- Add any header content here if needed -->
                    </div>
                </div>

                <!-- Form Container -->
                <div class="form-container">
                    <h2>Add New Spare Part</h2>
                    <?php if (isset($successMessage)): ?>
                        <script>
                            document.addEventListener('DOMContentLoaded', function () {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: '<?php echo $successMessage; ?>',
                                    confirmButtonText: 'OK'
                                }).then(() => {
                                    // Redirect to the same page without the success parameter
                                    window.location.href = 'spare_parts_collection.php';
                                });
                            });
                        </script>
                    <?php endif; ?>
                    <?php if (isset($errorMessage)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($errorMessage); ?>
                        </div>
                    <?php endif; ?>
                    <form method="POST" action="spare_parts_collection.php" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="price" class="form-label">Price</label>
                            <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                        </div>
                        <div class="mb-3">
                            <label for="stock" class="form-label">Stock</label>
                            <input type="number" class="form-control" id="stock" name="stock" required>
                        </div>
                        <div class="mb-3">
                            <label for="category_id" class="form-label">Category</label>
                            <select class="form-control" id="category_id" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars((string)$category['_id']); ?>"><?php echo htmlspecialchars($category['categoryName']); ?></option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($categoriesErrorMessage)): ?>
                                <div class="text-danger"><?php echo htmlspecialchars($categoriesErrorMessage); ?></div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Compatible Cars</label>
                            <?php if (isset($carsErrorMessage)): ?>
                                <div class="text-danger"><?php echo htmlspecialchars($carsErrorMessage); ?></div>
                            <?php else: ?>
                                <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
                                    <?php foreach ($cars as $car): ?>
                                        <div class="col">
                                            <div class="card shadow-sm h-100 car-card">
                                                <div class="card-body d-flex align-items-center">
                                                    <div class="form-check">
                                                        <input class="form-check-input styled-checkbox" type="checkbox" name="compatible_cars[]" value="<?php echo htmlspecialchars((string)$car['_id']); ?>" id="car_<?php echo htmlspecialchars((string)$car['_id']); ?>">
                                                        <label class="form-check-label" for="car_<?php echo htmlspecialchars((string)$car['_id']); ?>">
                                                            <?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image</label>
                            <div class="mb-3">
                                <label for="image_upload" class="form-label">Upload Image</label>
                                <input class="form-control" type="file" id="image_upload" name="image_upload" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Add Spare Part</button>
                    </form>
                </div>

                <!-- Spare Parts Collection -->
                <div class="form-container mt-4">
                    <h2>Spare Parts Collection</h2>
                    <?php if (isset($spare_partsErrorMessage)): ?>
                        <div class="alert alert-danger" role="alert">
                            <?php echo htmlspecialchars($spare_partsErrorMessage); ?>
                        </div>
                    <?php else: ?>
                        <table class="table table-hover table-bordered">
                            <thead class="table-light">
                                <tr>
                                    <th>Name</th>
                                    <th>Description</th>
                                    <th>Price</th>
                                    <th>Stock</th>
                                    <th>Category</th>
                                    <th>Compatible Cars</th>
                                    <th>Image</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($spare_parts as $spare_part): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($spare_part['name']); ?></td>
                                        <td><?php echo htmlspecialchars($spare_part['description']); ?></td>
                                        <td>₹<?php echo htmlspecialchars($spare_part['price']); ?></td>
                                        <td><?php echo htmlspecialchars($spare_part['stock']); ?></td>
                                        <td><?php echo htmlspecialchars($categories_map[(string)$spare_part['category_id']] ?? 'Unknown'); ?></td>
                                        <td>
                                            <?php
                                            $compatible_cars_list = [];
                                            foreach ($spare_part['compatible_cars'] as $car_id) {
                                                $compatible_cars_list[] = htmlspecialchars($cars_map[(string)$car_id] ?? 'Unknown');
                                            }
                                            echo implode(', ', $compatible_cars_list);
                                            ?>
                                        </td>
                                        <td>
                                            <img src="<?php echo htmlspecialchars($spare_part['image']); ?>" alt="Spare Part Image" style="width: 50px; height: 50px; object-fit: cover;">
                                        </td>
                                        <td class="d-flex justify-content-around">
                                            <a href="#" class="btn btn-sm btn-outline-primary edit-btn" data-bs-toggle="modal" data-bs-target="#editModal" 
                                               data-id="<?php echo htmlspecialchars((string)$spare_part['_id']); ?>" 
                                               data-name="<?php echo htmlspecialchars($spare_part['name']); ?>" 
                                               data-description="<?php echo htmlspecialchars($spare_part['description']); ?>" 
                                               data-price="<?php echo htmlspecialchars($spare_part['price']); ?>" 
                                               data-stock="<?php echo htmlspecialchars($spare_part['stock']); ?>" 
                                               data-category="<?php echo htmlspecialchars((string)$spare_part['category_id']); ?>" 
                                               data-image="<?php echo htmlspecialchars($spare_part['image']); ?>">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <a href="#" class="btn btn-sm btn-outline-danger delete-btn" 
                                               data-id="<?php echo htmlspecialchars((string)$spare_part['_id']); ?>" 
                                               data-name="<?php echo htmlspecialchars($spare_part['name']); ?>">
                                                <i class="bi bi-trash"></i>
                                            </a>
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

    <!-- Edit Modal -->
    <div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editModalLabel">Edit Spare Part</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editForm" method="POST" action="update_spare_part.php" enctype="multipart/form-data">
                        <input type="hidden" id="editId" name="id">
                        <div class="mb-3">
                            <label for="editName" class="form-label">Name</label>
                            <input type="text" class="form-control" id="editName" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="editDescription" class="form-label">Description</label>
                            <textarea class="form-control" id="editDescription" name="description" rows="3" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="editPrice" class="form-label">Price</label>
                            <input type="number" step="0.01" class="form-control" id="editPrice" name="price" required>
                        </div>
                        <div class="mb-3">
                            <label for="editStock" class="form-label">Stock</label>
                            <input type="number" class="form-control" id="editStock" name="stock" required>
                        </div>
                        <div class="mb-3">
                            <label for="editCategory" class="form-label">Category</label>
                            <select class="form-control" id="editCategory" name="category_id" required>
                                <option value="">Select Category</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo htmlspecialchars((string)$category['_id']); ?>"><?php echo htmlspecialchars($category['categoryName']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Compatible Cars</label>
                            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 row-cols-lg-4 g-3">
                                <?php foreach ($cars as $car): ?>
                                    <div class="col">
                                        <div class="card shadow-sm h-100 car-card">
                                            <div class="card-body d-flex align-items-center">
                                                <div class="form-check">
                                                    <input class="form-check-input compatible-car-checkbox" type="checkbox" name="compatible_cars[]" value="<?php echo htmlspecialchars((string)$car['_id']); ?>" id="editCar_<?php echo htmlspecialchars((string)$car['_id']); ?>">
                                                    <label class="form-check-label" for="editCar_<?php echo htmlspecialchars((string)$car['_id']); ?>">
                                                        <?php echo htmlspecialchars($car['brand'] . ' ' . $car['model']); ?>
                                                    </label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Current Image</label>
                            <div class="mb-3">
                                <img id="currentImage" src="" alt="Current Image" style="width: 100px; height: 100px; object-fit: cover;">
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Image</label>
                            <div class="mb-3">
                                <label for="editImage" class="form-label">Upload Image</label>
                                <input class="form-control" type="file" id="editImage" name="image_upload">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary">Save Changes</button>
                    </form>
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

                const name = document.getElementById('name').value.trim();
                const description = document.getElementById('description').value.trim();
                const price = document.getElementById('price').value.trim();
                const stock = document.getElementById('stock').value.trim();
                const category_id = document.getElementById('category_id').value;
                const compatible_cars = document.querySelectorAll('input[name="compatible_cars[]"]:checked');
                const image_upload = document.getElementById('image_upload').value;

                // Basic validation checks
                if (name === '') {
                    alert('Name must be filled out');
                    isValid = false;
                }

                if (description === '') {
                    alert('Description must be filled out');
                    isValid = false;
                }

                if (price === '' || isNaN(price)) {
                    alert('Price must be a number and filled out');
                    isValid = false;
                }

                if (stock === '' || isNaN(stock)) {
                    alert('Stock must be a number and filled out');
                    isValid = false;
                }

                if (category_id === '') {
                    alert('Please select a category');
                    isValid = false;
                }

                if (compatible_cars.length === 0) {
                    alert('Please select at least one compatible car');
                    isValid = false;
                }
                
                if (image_upload === '') {
                    alert('Please upload an image');
                    isValid = false;
                }

                if (!isValid) {
                    event.preventDefault(); // Prevent form submission
                }
            });

            // Edit button event handler
            const editButtons = document.querySelectorAll('.edit-btn');
            editButtons.forEach(button => {
                button.addEventListener('click', function () {
                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');
                    const description = this.getAttribute('data-description');
                    const price = this.getAttribute('data-price');
                    const stock = this.getAttribute('data-stock');
                    const category = this.getAttribute('data-category');
                    const image = this.getAttribute('data-image');

                    document.getElementById('editId').value = id;
                    document.getElementById('editName').value = name;
                    document.getElementById('editDescription').value = description;
                    document.getElementById('editPrice').value = price;
                    document.getElementById('editStock').value = stock;
                    document.getElementById('editCategory').value = category;
                    document.getElementById('currentImage').src = image;

                    // Clear all checkboxes first
                    document.querySelectorAll('.compatible-car-checkbox').forEach(checkbox => {
                        checkbox.checked = false;
                    });
                    
                    // Fetch the compatible cars for this spare part using AJAX
                    fetch('get_compatible_cars.php?id=' + id)
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                // Check the checkboxes based on the returned car IDs
                                data.compatible_cars.forEach(carId => {
                                    const checkbox = document.getElementById('editCar_' + carId);
                                    if (checkbox) {
                                        checkbox.checked = true;
                                    }
                                });
                            } else {
                                console.error('Error fetching compatible cars:', data.error);
                            }
                        })
                        .catch(error => {
                            console.error('Error:', error);
                        });
                });
            });

            const deleteButtons = document.querySelectorAll('.delete-btn');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function (event) {
                    event.preventDefault(); // Prevent the default link behavior

                    const id = this.getAttribute('data-id');
                    const name = this.getAttribute('data-name');

                    Swal.fire({
                        title: 'Confirm Delete',
                        text: `Are you sure you want to delete the spare part "${name}"?`,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#d33',
                        cancelButtonColor: '#3085d6',
                        confirmButtonText: 'Yes, delete it!',
                        cancelButtonText: 'Cancel'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            // Redirect to delete_spare_part.php with the ID
                            window.location.href = `delete_spare_part.php?id=${id}`;
                        }
                    });
                });
            });
        });
    </script>
</body>
</html>