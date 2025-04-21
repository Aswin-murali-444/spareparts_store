<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Placeholder data - in a real application, this would come from a database
$orders = [
    ['id' => '12345', 'date' => '2025-04-12', 'status' => 'Delivered', 'total' => '$129.99'],
    ['id' => '12344', 'date' => '2025-03-28', 'status' => 'Processing', 'total' => '$85.50']
];

$addresses = [
    ['type' => 'Shipping', 'address' => '123 Main St, Apt 4B, New York, NY 10001'],
    ['type' => 'Billing', 'address' => '123 Main St, Apt 4B, New York, NY 10001']
];

$wishlist = [
    ['name' => 'Brake Pads - Premium', 'price' => '$45.99'],
    ['name' => 'Oil Filter Kit', 'price' => '$22.50'],
    ['name' => 'LED Headlight Set', 'price' => '$89.99']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - AutoSpare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" integrity="sha512-9usAa10IRO0HhonpyAIVpjrylPvoDwiPUiKdWk5t3PyolY1cOd4DSE0Ga+ri4AuTroPR5aQvXU9xC6qOPnzFeg==" crossorigin="anonymous" referrerpolicy="no-referrer" />
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: url('https://example.com/your-background-image.jpg') no-repeat center center/cover;
            background-attachment: fixed;
            padding-top: 56px;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            line-height: 1.6;
        }

        .hero-section {
            background: linear-gradient(to right, #2b00ff, #7345e8); /* Gradient from green to dark green */
            background-position: center;
            background-size: cover;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
        }

        #products, #about, #contact {
            background: rgba(255, 255, 255, 0.95);
            padding: 80px 0;
            border-radius: 10px;
            margin: 20px 0;
        }

        #products .card {
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            border-radius: 10px;
            overflow: hidden;
        }

        #products .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        #products .card img {
            height: 200px;
            object-fit: cover;
        }

        footer {
            background: rgba(0, 0, 0, 0.9);
            color: #fff;
            text-align: center;
            padding: 20px 0;
            position: relative;
            bottom: 0;
            width: 100%;
        }

        footer .social-links a {
            color: #fff;
            margin: 0 10px;
            text-decoration: none;
            transition: color 0.3s;
        }

        footer .social-links a:hover {
            color: #007bff;
        }

        .login-container {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            margin: 100px auto;
        }

        .feature-icon {
            font-size: 3rem;
            color: #0d6efd;
            margin-bottom: 20px;
        }

        .category-card {
            transition: transform 0.3s;
            cursor: pointer;
        }
        
        .category-card:hover {
            transform: scale(1.05);
        }

        .brand-logo {
            height: 60px;
            filter: grayscale(100%);
            transition: filter 0.3s;
        }
        
        .brand-logo:hover {
            filter: grayscale(0%);
        }

        .parts-finder {
            background: linear-gradient(rgba(13, 110, 253, 0.8), rgba(13, 110, 253, 0.9));
            color: white;
            padding: 50px 0;
        }

        .testimonial-card {
            background-color: #f8f9fa;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }

        .testimonial-card img {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: cover;
        }

        .navbar .nav-link {
            color: rgba(255, 255, 255, 0.8); /* Adjust link color */
            margin-right: 15px; /* Add spacing between links */
        }

        .navbar .nav-link:hover {
            color: #fff; /* Adjust hover color */
        }

        .navbar .dropdown-menu {
            border: none;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
        }

        .navbar .dropdown-item:hover {
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark" style="background: linear-gradient(to right, #2b00ff, #7345e8); box-shadow: 0 0.25rem 0.5rem rgba(0,0,0,0.2); fixed-top">
        <div class="container">
            <a class="navbar-brand fw-bold fs-4 d-flex align-items-center" href="index.php">
                <i class="fas fa-cogs me-2"></i>
                <span class="text-uppercase">AutoSpare</span>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#products">Products</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#categories">Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#about">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#contact">Contact</a>
                    </li>
                </ul>
                <form class="d-flex me-3">
                    <div class="input-group">
                        <input class="form-control me-2" type="search" placeholder="Search for parts..." aria-label="Search">
                        <button class="btn btn-outline-light" type="submit"><i class="fas fa-search"></i></button>
                    </div>
                </form>
                <div class="d-flex align-items-center justify-content-end">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="me-3">
                            <div class="dropdown">
                                <a class="btn btn-outline-light dropdown-toggle" href="#" role="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="fas fa-user me-1"></i> <?php echo htmlspecialchars($_SESSION['user_name']); ?>
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                    <li><a class="dropdown-item" href="profile.php">Profile</a></li>
                                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                                </ul>
                            </div>
                        </div>
                    <?php else: ?>
                        <a href="login.php" class="btn btn-outline-light me-3">
                            <i class="fas fa-user me-1"></i> Login
                        </a>
                    <?php endif; ?>
                    <button type="button" class="btn btn-outline-light me-3 position-relative" data-bs-toggle="modal" data-bs-target="#wishlistModal">
                        <i class="fas fa-heart"></i>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                            <?php echo count($_SESSION['wishlist'] ?? []); ?>
                            <span class="visually-hidden">items in wishlist</span>
                        </span>
                    </button>
                    <button type="button" class="btn btn-warning position-relative d-flex align-items-center" data-bs-toggle="modal" data-bs-target="#cartModal">
                        <i class="fas fa-shopping-cart me-1"></i>
                        View Cart
                        <span id="cart-count" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger ms-1" style="padding: 0.3em 0.6em;">
                            0
                            <span class="visually-hidden">items in cart</span>
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </nav>

    <!-- Profile Section -->
    <div class="container mt-5 pt-5">
        <div class="row">
            <div class="col-md-3 mb-4">
                <!-- Profile Navigation Menu -->
                <div class="card">
                    <div class="card-header bg-dark text-white">
                        <h5 class="mb-0">My Account</h5>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="#personal-info" class="list-group-item list-group-item-action active" data-bs-toggle="list">
                            <i class="fas fa-user-circle me-2"></i> Personal Information
                        </a>
                        <a href="#orders" class="list-group-item list-group-item-action" data-bs-toggle="list">
                            <i class="fas fa-shopping-bag me-2"></i> Order History
                        </a>
                        <a href="#addresses" class="list-group-item list-group-item-action" data-bs-toggle="list">
                            <i class="fas fa-map-marker-alt me-2"></i> My Addresses
                        </a>
                        <a href="#wishlist" class="list-group-item list-group-item-action" data-bs-toggle="list">
                            <i class="fas fa-heart me-2"></i> My Wishlist
                        </a>
                        <a href="#password" class="list-group-item list-group-item-action" data-bs-toggle="list">
                            <i class="fas fa-lock me-2"></i> Change Password
                        </a>
                        <a href="logout.php" class="list-group-item list-group-item-action text-danger">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </div>
                </div>
            </div>
            
            <div class="col-md-9">
                <!-- Tab Content -->
                <div class="tab-content">
                    <!-- Personal Information -->
                    <div class="tab-pane fade show active" id="personal-info">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="mb-0">Personal Information</h4>
                                <button class="btn btn-sm btn-primary" id="edit-profile-btn">
                                    <i class="fas fa-edit me-1"></i> Edit
                                </button>
                            </div>
                            <div class="card-body">
                                <div id="profile-info">
                                    <div class="row mb-3">
                                        <div class="col-md-3 fw-bold">Name:</div>
                                        <div class="col-md-9"><?php echo htmlspecialchars($_SESSION['user_name']); ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-3 fw-bold">Email:</div>
                                        <div class="col-md-9"><?php echo htmlspecialchars($_SESSION['user_email']); ?></div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-3 fw-bold">Phone:</div>
                                        <div class="col-md-9">(555) 123-4567</div>
                                    </div>
                                    <div class="row mb-3">
                                        <div class="col-md-3 fw-bold">Member Since:</div>
                                        <div class="col-md-9">January 15, 2023</div>
                                    </div>
                                </div>
                                
                                <!-- Edit Profile Form (Hidden by default) -->
                                <form id="edit-profile-form" style="display: none;">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Name</label>
                                        <input type="text" class="form-control" id="name" value="<?php echo htmlspecialchars($_SESSION['user_name']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" value="<?php echo htmlspecialchars($_SESSION['user_email']); ?>">
                                    </div>
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone</label>
                                        <input type="tel" class="form-control" id="phone" value="(555) 123-4567">
                                    </div>
                                    <div class="d-flex justify-content-end">
                                        <button type="button" class="btn btn-secondary me-2" id="cancel-edit-btn">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Save Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Order History -->
                    <div class="tab-pane fade" id="orders">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="mb-0">Order History</h4>
                            </div>
                            <div class="card-body">
                                <?php if (empty($orders)): ?>
                                    <p class="text-center">You haven't placed any orders yet.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Order #</th>
                                                    <th>Date</th>
                                                    <th>Status</th>
                                                    <th>Total</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($orders as $order): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($order['id']); ?></td>
                                                    <td><?php echo htmlspecialchars($order['date']); ?></td>
                                                    <td>
                                                        <?php if ($order['status'] == 'Delivered'): ?>
                                                            <span class="badge bg-success"><?php echo htmlspecialchars($order['status']); ?></span>
                                                        <?php elseif ($order['status'] == 'Processing'): ?>
                                                            <span class="badge bg-warning text-dark"><?php echo htmlspecialchars($order['status']); ?></span>
                                                        <?php else: ?>
                                                            <span class="badge bg-info"><?php echo htmlspecialchars($order['status']); ?></span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($order['total']); ?></td>
                                                    <td>
                                                        <a href="order_details.php?id=<?php echo htmlspecialchars($order['id']); ?>" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-eye me-1"></i> View
                                                        </a>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Addresses -->
                    <div class="tab-pane fade" id="addresses">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h4 class="mb-0">My Addresses</h4>
                                <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addAddressModal">
                                    <i class="fas fa-plus me-1"></i> Add New Address
                                </button>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <?php foreach ($addresses as $address): ?>
                                    <div class="col-md-6 mb-3">
                                        <div class="card h-100">
                                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                                <h5 class="mb-0"><?php echo htmlspecialchars($address['type']); ?> Address</h5>
                                                <div>
                                                    <button class="btn btn-sm btn-outline-primary me-1">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                            <div class="card-body">
                                                <p class="mb-0"><?php echo htmlspecialchars($address['address']); ?></p>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Wishlist -->
                    <div class="tab-pane fade" id="wishlist">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="mb-0">My Wishlist</h4>
                            </div>
                            <div class="card-body">
                                <?php if (empty($wishlist)): ?>
                                    <p class="text-center">Your wishlist is empty.</p>
                                <?php else: ?>
                                    <div class="table-responsive">
                                        <table class="table table-hover">
                                            <thead>
                                                <tr>
                                                    <th>Product</th>
                                                    <th>Price</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($wishlist as $item): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($item['name']); ?></td>
                                                    <td><?php echo htmlspecialchars($item['price']); ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-primary me-1">
                                                            <i class="fas fa-shopping-cart me-1"></i> Add to Cart
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Change Password -->
                    <div class="tab-pane fade" id="password">
                        <div class="card">
                            <div class="card-header">
                                <h4 class="mb-0">Change Password</h4>
                            </div>
                            <div class="card-body">
                                <form>
                                    <div class="mb-3">
                                        <label for="current-password" class="form-label">Current Password</label>
                                        <input type="password" class="form-control" id="current-password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="new-password" class="form-label">New Password</label>
                                        <input type="password" class="form-control" id="new-password" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="confirm-password" class="form-label">Confirm New Password</label>
                                        <input type="password" class="form-control" id="confirm-password" required>
                                    </div>
                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">Update Password</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Address Modal -->
    <div class="modal fade" id="addAddressModal" tabindex="-1" aria-labelledby="addAddressModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="addAddressModalLabel">Add New Address</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form>
                        <div class="mb-3">
                            <label for="address-type" class="form-label">Address Type</label>
                            <select class="form-select" id="address-type" required>
                                <option value="Shipping">Shipping</option>
                                <option value="Billing">Billing</option>
                                <option value="Both">Both Shipping & Billing</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="address-line1" class="form-label">Address Line 1</label>
                            <input type="text" class="form-control" id="address-line1" required>
                        </div>
                        <div class="mb-3">
                            <label for="address-line2" class="form-label">Address Line 2</label>
                            <input type="text" class="form-control" id="address-line2">
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" required>
                            </div>
                            <div class="col-md-6">
                                <label for="state" class="form-label">State</label>
                                <input type="text" class="form-control" id="state" required>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="zip" class="form-label">ZIP Code</label>
                                <input type="text" class="form-control" id="zip" required>
                            </div>
                            <div class="col-md-6">
                                <label for="country" class="form-label">Country</label>
                                <input type="text" class="form-control" id="country" required>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary">Save Address</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Wishlist Modal -->
    <div class="modal fade" id="wishlistModal" tabindex="-1" aria-labelledby="wishlistModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="wishlistModalLabel">My Wishlist</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <?php if (empty($wishlist)): ?>
                        <p class="text-center">Your wishlist is empty.</p>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th>Price</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($wishlist as $item): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                                        <td><?php echo htmlspecialchars($item['price']); ?></td>
                                        <td>
                                            <button class="btn btn-sm btn-primary me-1">
                                                <i class="fas fa-shopping-cart me-1"></i> Add to Cart
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger">
                                                <i class="fas fa-trash"></i>
                                            </button>
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
                </div>
            </div>
        </div>
    </div>

    <!-- Cart Modal -->
    <div class="modal fade" id="cartModal" tabindex="-1" aria-labelledby="cartModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cartModalLabel">Shopping Cart</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Mock cart items -->
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th>Quantity</th>
                                    <th>Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Oil Filter Kit</td>
                                    <td>$22.50</td>
                                    <td>
                                        <div class="input-group input-group-sm" style="width: 100px;">
                                            <button class="btn btn-outline-secondary" type="button">-</button>
                                            <input type="text" class="form-control text-center" value="1">
                                            <button class="btn btn-outline-secondary" type="button">+</button>
                                        </div>
                                    </td>
                                    <td>$22.50</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Brake Pads - Premium</td>
                                    <td>$45.99</td>
                                    <td>
                                        <div class="input-group input-group-sm" style="width: 100px;">
                                            <button class="btn btn-outline-secondary" type="button">-</button>
                                            <input type="text" class="form-control text-center" value="2">
                                            <button class="btn btn-outline-secondary" type="button">+</button>
                                        </div>
                                    </td>
                                    <td>$91.98</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr>
                                    <td>Windshield Wipers (Pair)</td>
                                    <td>$35.00</td>
                                    <td>
                                        <div class="input-group input-group-sm" style="width: 100px;">
                                            <button class="btn btn-outline-secondary" type="button">-</button>
                                            <input type="text" class="form-control text-center" value="1">
                                            <button class="btn btn-outline-secondary" type="button">+</button>
                                        </div>
                                    </td>
                                    <td>$35.00</td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-danger">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                    <td>$149.48</td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <div class="modal-footer justify-content-between">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Continue Shopping</button>
                    <a href="checkout.php" class="btn btn-primary">Proceed to Checkout</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <p>Email: support@autospare.com</p>
                    <p>Phone: +1 (123) 456-7890</p>
                    <p>Address: 123 Auto Spare St, City, Country</p>
                </div>
                <div class="col-md-4">
                    <h5>Follow Us</h5>
                    <div class="social-links">
                        <a href="#" class="text-white me-2"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-md-4">
                    <h5>Legal</h5>
                    <ul class="list-unstyled">
                        <li><a href="#" class="text-white">Privacy Policy</a></li>
                        <li><a href="#" class="text-white">Terms of Service</a></li>
                        <li><a href="#" class="text-white">Return Policy</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <!-- JavaScript for Profile Page -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Edit profile functionality
            document.getElementById('edit-profile-btn').addEventListener('click', function() {
                document.getElementById('profile-info').style.display = 'none';
                document.getElementById('edit-profile-form').style.display = 'block';
                this.style.display = 'none';
            });
            
            document.getElementById('cancel-edit-btn').addEventListener('click', function() {
                document.getElementById('profile-info').style.display = 'block';
                document.getElementById('edit-profile-form').style.display = 'none';
                document.getElementById('edit-profile-btn').style.display = 'block';
            });
        });
    </script>
</body>
</html>