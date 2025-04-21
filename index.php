<?php
session_start();

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require 'mongodb_connection.php';

// Fetch cart data from session
$cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
$wishlist = isset($_SESSION['wishlist']) ? $_SESSION['wishlist'] : [];

try {
    $cars_collection = $db->cars;
    $makes = $cars_collection->distinct('brand');
    $models = $cars_collection->distinct('model');
    $years = $cars_collection->distinct('year');
} catch (MongoDB\Driver\Exception\Exception $e) {
    $fetchErrorMessage = "Error fetching data: " . $e->getMessage();
    $makes = [];
    $models = [];
    $years = [];
}

try {
    $spare_parts_collection = $db->spare_parts;
    $products = $spare_parts_collection->find()->toArray();
} catch (MongoDB\Driver\Exception\Exception $e) {
    $fetchProductsErrorMessage = "Error fetching products: " . $e->getMessage();
    $products = [];
}

//Calculate Subtotal
$subtotal = 0;
foreach ($cart as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}

$shippingCost = 100; // Fixed shipping cost
$taxRate = 0.10; // 10% tax rate
$taxAmount = $subtotal * $taxRate;
$total = $subtotal + $shippingCost + $taxAmount;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AutoSpare - Professional Spare Parts Store</title>
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

    <!-- Login Modal -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="loginModalLabel">Login to Your Account</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="loginForm">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" class="form-control" id="password" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="rememberMe">
                            <label class="form-check-label" for="rememberMe">Remember me</label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Login</button>
                    </form>
                    <div class="text-center mt-3">
                        <p>Don't have an account? <a href="#" data-bs-toggle="modal" data-bs-target="#registerModal" data-bs-dismiss="modal">Register here</a></p>
                        <p><a href="#">Forgot password?</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Register Modal -->
    <div class="modal fade" id="registerModal" tabindex="-1" aria-labelledby="registerModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="registerModalLabel">Create an Account</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="registerForm">
                        <div class="mb-3">
                            <label for="fullName" class="form-label">Full Name</label>
                            <input type="text" class="form-control" id="fullName" required>
                        </div>
                        <div class="mb-3">
                            <label for="registerEmail" class="form-label">Email address</label>
                            <input type="email" class="form-control" id="registerEmail" required>
                        </div>
                        <div class="mb-3">
                            <label for="registerPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="registerPassword" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirmPassword" class="form-label">Confirm Password</label>
                            <input type="password" class="form-control" id="confirmPassword" required>
                        </div>
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="termsAgree" required>
                            <label class="form-check-label" for="termsAgree">I agree to the <a href="#">Terms and Conditions</a></label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Register</button>
                    </form>
                    <div class="text-center mt-3">
                        <p>Already have an account? <a href="#" data-bs-toggle="modal" data-bs-target="#loginModal" data-bs-dismiss="modal">Login here</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cart Modal -->
    <div class="modal fade" id="cartModal" tabindex="-1" aria-labelledby="cartModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="cartModalLabel">Your Shopping Cart</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
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
                           <!-- Cart items will be dynamically added here -->
                           <?php 
                           foreach ($cart as $item): ?>
                            <?php
                            $itemTotal = $item['price'] * $item['quantity'];
                            ?>
                        <tr>
                            <td class="d-flex align-items-center">
                                <img src="<?php echo htmlspecialchars($item['image']); ?>" alt="<?php echo htmlspecialchars($item['name']); ?>" class="me-2" style="width:50px;">
                                <span><?php echo htmlspecialchars($item['name']); ?></span>
                            </td>
                            <td>₹<?php echo htmlspecialchars($item['price']); ?></td>
                            <td>
                                <div class="input-group" style="width: 100px;">
                                    <button class="btn btn-outline-secondary btn-sm change-quantity" data-product-id="<?php echo htmlspecialchars($item['product_id']); ?>" data-action="decrease">-</button>
                                    <input type="text" class="form-control text-center quantity-input" value="<?php echo htmlspecialchars($item['quantity']); ?>" data-product-id="<?php echo htmlspecialchars($item['product_id']); ?>" readonly>
                                    <button class="btn btn-outline-secondary btn-sm change-quantity" data-product-id="<?php echo htmlspecialchars($item['product_id']); ?>" data-action="increase">+</button>
                                </div>
                            </td>
                            <td>₹<?php echo number_format($itemTotal, 2); ?></td>
                            <td><button class="btn btn-sm btn-outline-danger remove-from-cart" data-product-id="<?php echo htmlspecialchars($item['product_id']); ?>"><i class="fas fa-trash"></i></button></td>
                        </tr>
                    <?php endforeach; ?>
                        </tbody>
                    </table>
                    <div class="d-flex justify-content-between mt-4">
                        <button class="btn btn-outline-secondary">Continue Shopping</button>
                        <div class="text-end">
                            <p class="mb-2"><strong>Subtotal:</strong> <span class="subtotal">₹<?php echo number_format($subtotal, 2); ?></span></p>
                            <p class="mb-2"><strong>Shipping:</strong> ₹<?php echo $shippingCost; ?></p>
                            <p class="mb-2"><strong>Tax (10%):</strong> ₹<?php echo number_format($taxAmount, 2); ?></p>
                            <p class="mb-3"><strong>Total:</strong> <span class="text-primary fs-5 total">₹<?php echo number_format($total, 2); ?></span></p>
                            <button class="btn btn-primary">Proceed to Checkout</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Wishlist Modal -->
    <div class="modal fade" id="wishlistModal" tabindex="-1" aria-labelledby="wishlistModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title" id="wishlistModalLabel">Your Wishlist</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Wishlist items will be dynamically added here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Hero Section -->
    <section id="home" class="hero-section text-center text-white">
        <div class="container animate__animated animate__fadeIn">
            <h1 class="display-3 fw-bold mb-4">Premium Auto Parts for Every Vehicle</h1>
            <p class="lead mb-4 fs-4">Quality spare parts delivered to your doorstep with guaranteed authenticity</p>
            <div class="d-flex justify-content-center gap-3">
                <a href="#products" class="btn btn-primary btn-lg px-4 py-3">
                    <i class="fas fa-shopping-cart me-2"></i> Shop Now
                </a>
                <a href="#parts-finder" class="btn btn-outline-light btn-lg px-4 py-3">
                    <i class="fas fa-search me-2"></i> Find Your Parts
                </a>
            </div>
        </div>
    </section>

    <!-- Parts Finder Section -->
    <section id="parts-finder" class="parts-finder">
        <div class="container py-5">
            <h2 class="text-center mb-4">Find the Right Parts for Your Vehicle</h2>
            <div class="row justify-content-center">
                <div class="col-md-10">
                    <div class="card">
                        <div class="card-body p-4">
                            <form class="row g-3">
                                <div class="col-md-4">
                                    <label for="make" class="form-label">Make</label>
                                    <select id="make" class="form-select">
                                        <option selected>Choose...</option>
                                        <?php foreach ($makes as $make): ?>
                                            <option><?php echo htmlspecialchars($make); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="model" class="form-label">Model</label>
                                    <select id="model" class="form-select">
                                        <option selected>Choose...</option>
                                        <?php foreach ($models as $model): ?>
                                            <option><?php echo htmlspecialchars($model); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="year" class="form-label">Year</label>
                                    <select id="year" class="form-select">
                                        <option selected>Choose...</option>
                                        <?php foreach ($years as $year): ?>
                                            <option><?php echo htmlspecialchars($year); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-12 text-center mt-4">
                                    <button type="submit" class="btn btn-dark btn-lg px-5">Find Parts</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4 text-center">
                    <div class="p-4">
                        <i class="fas fa-check-circle feature-icon"></i>
                        <h3>Genuine Parts</h3>
                        <p>All our parts are 100% authentic with manufacturer warranty</p>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="p-4">
                        <i class="fas fa-truck feature-icon"></i>
                        <h3>Fast Delivery</h3>
                        <p>Enjoy next-day delivery on most orders placed before 2 PM</p>
                    </div>
                </div>
                <div class="col-md-4 text-center">
                    <div class="p-4">
                        <i class="fas fa-headset feature-icon"></i>
                        <h3>Expert Support</h3>
                        <p>Our technicians are available 24/7 to help with your inquiries</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Categories Section -->
    <section id="categories" class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Shop by Category</h2>
            <div class="row g-4">
                <div class="col-6 col-md-3">
                    <div class="card category-card h-100 text-center">
                        <div class="card-body">
                            <i class="fas fa-oil-can fs-1 text-primary mb-3"></i>
                            <h5 class="card-title">Engine Parts</h5>
                            <p class="card-text">Filters, belts, and components</p>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="#" class="btn btn-sm btn-outline-primary">Browse</a>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card category-card h-100 text-center">
                        <div class="card-body">
                            <i class="fas fa-car-battery fs-1 text-primary mb-3"></i>
                            <h5 class="card-title">Electrical</h5>
                            <p class="card-text">Batteries, lights, and sensors</p>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="#" class="btn btn-sm btn-outline-primary">Browse</a>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card category-card h-100 text-center">
                        <div class="card-body">
                            <i class="fas fa-tachometer-alt fs-1 text-primary mb-3"></i>
                            <h5 class="card-title">Brakes</h5>
                            <p class="card-text">Pads, discs, and calipers</p>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="#" class="btn btn-sm btn-outline-primary">Browse</a>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <div class="card category-card h-100 text-center">
                        <div class="card-body">
                            <i class="fas fa-cogs fs-1 text-primary mb-3"></i>
                            <h5 class="card-title">Transmission</h5>
                            <p class="card-text">Fluids, filters, and parts</p>
                        </div>
                        <div class="card-footer bg-transparent border-0">
                            <a href="#" class="btn btn-sm btn-outline-primary">Browse</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Products Section -->
    <section id="products" class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Featured Products</h2>
            <div id="cart-info">
               
            </div>
            <div class="row">
                <?php if (isset($fetchProductsErrorMessage)): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($fetchProductsErrorMessage); ?></div>
                <?php else: ?>
                    <?php foreach ($products as $product): ?>
                        <!-- Product -->
                        <div class="col-md-4 col-lg-3 mb-4">
                            <div class="card h-100 animate__animated animate__fadeIn">
                                <img src="<?php echo htmlspecialchars($product['image']); ?>" class="card-img-top" alt="<?php echo htmlspecialchars($product['name']); ?>">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                                    <button type="button" class="btn btn-outline-danger btn-sm wishlist-button" data-product-id="<?php echo htmlspecialchars((string)$product['_id']); ?>">
                                        <i class="fas fa-heart"></i>
                                    </button>
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="text-primary fw-bold fs-5">₹<?php echo htmlspecialchars(number_format($product['price'], 2)); ?></span>
                                    </div>
                                    <p class="card-text"><?php echo htmlspecialchars($product['description']); ?></p>
                                </div>
                                <div class="card-footer bg-white border-0">
                                    <form class="add-to-cart-form" data-product-id="<?php echo htmlspecialchars((string)$product['_id']); ?>">
                                        <div class="d-grid">
                                            <button type="submit" class="btn btn-primary">Add to Cart</button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <div class="text-center mt-4">
                <a href="#" class="btn btn-outline-primary btn-lg">View All Products</a>
            </div>
        </div>
    </section>

    <!-- Brands Section -->
    <section class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">Top Brands We Carry</h2>
            <div class="row align-items-center justify-content-center">
                <div class="col-4 col-md-2 text-center mb-4">
                    <img src="/api/placeholder/120/60" alt="Brand 1" class="img-fluid brand-logo">
                </div>
                <div class="col-4 col-md-2 text-center mb-4">
                    <img src="/api/placeholder/120/60" alt="Brand 2" class="img-fluid brand-logo">
                </div>
                <div class="col-4 col-md-2 text-center mb-4">
                    <img src="/api/placeholder/120/60" alt="Brand 3" class="img-fluid brand-logo">
                </div>
                <div class="col-4 col-md-2 text-center mb-4">
                    <img src="/api/placeholder/120/60" alt="Brand 4" class="img-fluid brand-logo">
                </div>
                <div class="col-4 col-md-2 text-center mb-4">
                    <img src="/api/placeholder/120/60" alt="Brand 5" class="img-fluid brand-logo">
                </div>
                <div class="col-4 col-md-2 text-center mb-4">
                    <img src="/api/placeholder/120/60" alt="Brand 6" class="img-fluid brand-logo">
                </div>
            </div>
        </div>
    </section>

    <!-- Testimonials -->
    <section class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">What Our Customers Say</h2>
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="testimonial-card">
                        <div class="testimonial-card-body">
                            <img src="/api/placeholder/60/60" alt="Customer 1" class="testimonial-card-img">
                            <p class="testimonial-card-text">"I've been using AutoSpare for years, and their parts have always been a game-changer for my car. The quality is unmatched!"</p>
                        </div>
                        <div class="testimonial-card-footer">
                            <strong>John Doe</strong>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="testimonial-card">
                        <div class="testimonial-card-body">
                            <img src="/api/placeholder/60/60" alt="Customer 2" class="testimonial-card-img">
                            <p class="testimonial-card-text">"The customer service at AutoSpare is top-notch. They went above and beyond to help me find the right parts for my vintage car."</p>
                        </div>
                        <div class="testimonial-card-footer">
                            <strong>Jane Smith</strong>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="testimonial-card">
                        <div class="testimonial-card-body">
                            <img src="/api/placeholder/60/60" alt="Customer 3" class="testimonial-card-img">
                            <p class="testimonial-card-text">"I've never had a better experience with a spare parts store. The website is easy to navigate, and the parts are delivered quickly."</p>
                        </div>
                        <div class="testimonial-card-footer">
                            <strong>Michael Brown</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Product Comparison Section -->
    <section id="product-comparison" class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Product Comparison</h2>
            <div class="row">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Premium Engine Oil Filter</h5>
                            <p class="card-text">High-quality oil filter with enhanced filtration for all car models.</p>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">Price: $15.99</li>
                                <li class="list-group-item">Rating: 4.5/5</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Performance Brake Pads</h5>
                            <p class="card-text">Durable ceramic brake pads for smooth and reliable stopping power.</p>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">Price: $29.99</li>
                                <li class="list-group-item">Rating: 5/5</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Newsletter Subscription Section -->
    <section class="py-5 bg-primary text-white">
        <div class="container">
            <div class="row">
                <div class="col-md-6">
                    <h3>Subscribe to Our Newsletter</h3>
                    <p>Get the latest updates and exclusive offers directly in your inbox.</p>
                </div>
                <div class="col-md-6">
                    <form>
                        <div class="input-group">
                            <input type="email" class="form-control" placeholder="Enter your email" required>
                            <button class="btn btn-dark" type="submit">Subscribe</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- About Us Section -->
    <section id="about" class="py-5">
        <div class="container">
            <h2 class="text-center mb-5">About Us</h2>
            <div class="row">
                <div class="col-md-6">
                    <img src="https://honeywell.scene7.com/is/image/Honeywell65/pmt-hps-marine-spare-parts-portable-gauging-and-sampling-image" alt="About Us" class="img-fluid rounded">
                </div>
                <div class="col-md-6">
                    <h3>Who We Are</h3>
                    <p>AutoSpare is your trusted source for high-quality auto parts, ensuring your vehicle runs smoothly with genuine, reliable components.</p>
                    <h3>Our Mission</h3>
                    <p>Our mission is to provide our customers with the best auto parts and exceptional service. We aim to make your car maintenance experience seamless and stress-free by offering a wide range of products, expert advice, and fast delivery.</p>
                    <h3>Why Choose Us?</h3>
                    <ul>
                        <li>Genuine parts with manufacturer warranty</li>
                        <li>Fast and reliable delivery</li>
                        <li>Expert support available 24/7</li>
                        <li>Competitive pricing</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Contact Section -->
    <section id="contact" class="py-5 bg-light">
        <div class="container">
            <h2 class="text-center mb-5">Contact Us</h2>
            <div class="row">
                <div class="col-md-6">
                    <h3>Get in Touch</h3>
                    <p>Have questions or need assistance? We're here to help! Reach out to us via email, phone, or visit our office. Our team is ready to assist you with any inquiries or support you may need.</p>
                    <ul class="list-unstyled">
                        <li><strong>Email:</strong> support@autospare.com</li>
                        <li><strong>Phone:</strong> +1 (123) 456-7890</li>
                        <li><strong>Address:</strong> 123 Auto Spare St, City, Country</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <h3>Send Us a Message</h3>
                    <form>
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" class="form-control" id="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" class="form-control" id="email" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message</label>
                            <textarea class="form-control" id="message" rows="5" required></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </form>
                </div>
            </div>
        </div>
    </section>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let cartCount = 0;
        const cartCountSpan = document.getElementById('cart-count');
        const cartModal = document.getElementById('cartModal');
        const wishlistModal = document.getElementById('wishlistModal');

        const addToCartForms = document.querySelectorAll('.add-to-cart-form');
        const wishlistButtons = document.querySelectorAll('.wishlist-button');

        addToCartForms.forEach(form => {
            form.addEventListener('submit', function(event) {
                event.preventDefault(); // Prevent the default form submission

                const productId = this.dataset.productId;

                fetch('add_to_cart.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'product_id=' + productId
                })
                .then(response => {
                    if (response.ok) {
                        return response.json(); // Expect JSON response
                    } else {
                        throw new Error('Network response was not ok.');
                    }
                })
                .then(data => {
                    // Update the cart modal content
                    updateCartModal(data.cart);
                     Swal.fire({
                        icon: 'success',
                        title: 'Added to Cart!',
                        showConfirmButton: false,
                        timer: 1500
                    })
                    cartCount++;
                    cartCountSpan.textContent = cartCount;
                })
                .catch(error => {
                    console.error('There has been a problem with your fetch operation:', error);
                });
            });
        });

         wishlistButtons.forEach(button => {
            button.addEventListener('click', function(event) {
                event.preventDefault();
                const productId = this.dataset.productId;
                console.log("Wishlist button clicked for product ID: " + productId); // Debugging

                fetch('add_to_wishlist.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded'
                    },
                    body: 'product_id=' + productId
                })
                .then(response => {
                    console.log("Response received:", response); // Debugging
                    if (response.ok) {
                        return response.json();
                    } else {
                        throw new Error('Network response was not ok.');
                    }
                })
                .then(data => {
                    console.log("Data received:", data); // Debugging
                    updateWishlistModal(data.wishlist);
                    Swal.fire({
                        icon: 'success',
                        title: 'Added to Wishlist!',
                        showConfirmButton: false,
                        timer: 1500
                    });
                })
                .catch(error => {
                    console.error('Error:', error);
                });
            });
        });

         function updateCartModal(cart) {
            let cartItemsHTML = '';
            let subtotal = 0;

            if (cart.length === 0) {
                cartItemsHTML = '<p>Your cart is empty.</p>';
            } else {
                cart.forEach(item => {
                    const itemTotal = item.price * item.quantity;
                    subtotal += itemTotal;
                    cartItemsHTML += `
                        <tr>
                            <td class="d-flex align-items-center">
                                <img src="${item.image}" alt="${item.name}" class="me-2" style="width:50px;">
                                <span>${item.name}</span>
                            </td>
                            <td>₹${item.price}</td>
                            <td>
                                <div class="input-group" style="width: 100px;">
                                    <button class="btn btn-outline-secondary btn-sm change-quantity" data-product-id="${item.product_id}" data-action="decrease">-</button>
                                    <input type="text" class="form-control text-center quantity-input" value="${item.quantity}" data-product-id="${item.product_id}" readonly>
                                    <button class="btn btn-outline-secondary btn-sm change-quantity" data-product-id="${item.product_id}" data-action="increase">+</button>
                                </div>
                            </td>
                            <td>₹${itemTotal.toFixed(2)}</td>
                            <td><button class="btn btn-sm btn-outline-danger remove-from-cart" data-product-id="${item.product_id}"><i class="fas fa-trash"></i></button></td>
                        </tr>
                    `;
                });
            }

            document.querySelector('#cartModal tbody').innerHTML = cartItemsHTML;
            const shippingCost = 100;
            const taxRate = 0.10;
            const taxAmount = subtotal * taxRate;
            const total = subtotal + shippingCost + taxAmount;

            document.querySelector('#cartModal .subtotal').textContent = '₹' + subtotal.toFixed(2);
            document.querySelector('#cartModal .total').textContent = '₹' + total.toFixed(2); // Assuming shipping is $5.99
            console.log("Cart modal updated with cartItemsHTML:", cartItemsHTML); // Debugging
        }

        function updateWishlistModal(wishlist) {
            let wishlistItemsHTML = '';

            if (wishlist.length === 0) {
                wishlistItemsHTML = '<p>Your wishlist is empty.</p>';
            } else {
                wishlist.forEach(item => {
                    wishlistItemsHTML += `
                        <div class="card mb-3">
                            <div class="row g-0">
                                <div class="col-md-4">
                                    <img src="${item.image}" class="img-fluid rounded-start" alt="${item.name}">
                                </div>
                                <div class="col-md-8">
                                    <div class="card-body">
                                        <h5 class="card-title">${item.name}</h5>
                                        <p class="card-text">₹${item.price}</p>
                                        <p class="card-text">${item.description}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                });
            }

            document.querySelector('#wishlistModal .modal-body').innerHTML = wishlistItemsHTML;
        }

        // Event listeners for quantity changes
        document.querySelectorAll('.change-quantity').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.dataset.productId;
                const action = this.dataset.action;

                updateQuantity(productId, action);
            });
        });

        // Event listeners for remove from cart
        document.querySelectorAll('.remove-from-cart').forEach(button => {
            button.addEventListener('click', function() {
                const productId = this.dataset.productId;
                removeFromCart(productId);
            });
        });

        function updateQuantity(productId, action) {
            console.log("updateQuantity called with productId: " + productId + " and action: " + action); // Debugging
            fetch('update_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `product_id=${productId}&action=${action}`
            })
            .then(response => {
                console.log("Response received:", response); // Debugging
                return response.json();
            })
            .then(data => {
                console.log("Data received:", data); // Debugging
                updateCartModal(data.cart);
            })
            .catch(error => console.error('Error:', error));
        }

        function removeFromCart(productId) {
            console.log("removeFromCart called with productId: " + productId); // Debugging
            fetch('update_cart.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: `product_id=${productId}&action=remove`
            })
            .then(response => {
                console.log("Response received:", response); // Debugging
                return response.json();
            })
            .then(data => {
                console.log("Data received:", data); // Debugging
                updateCartModal(data.cart);
            })
            .catch(error => console.error('Error:', error));
        }

         cartModal.addEventListener('show.bs.modal', function () {
            cartCount = 0;
            cartCountSpan.textContent = cartCount;
        });
    </script>
</body>
</html>