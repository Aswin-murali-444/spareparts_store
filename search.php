<?php 
session_start(); 
require 'mongodb_connection.php';

$query = isset($_GET['query']) ? trim($_GET['query']) : '';

if (!empty($query)) {
    try {
        $spare_parts_collection = $db->spare_parts;
        $searchResults = $spare_parts_collection->find([
            '$or' => [
                ['name' => new MongoDB\BSON\Regex($query, 'i')],
                ['description' => new MongoDB\BSON\Regex($query, 'i')]
            ]
        ])->toArray();
    } catch (MongoDB\Driver\Exception\Exception $e) {
        $searchErrorMessage = "Error searching for parts: " . $e->getMessage();
        $searchResults = [];
    }
} else {
    $searchResults = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Search Results - AutoSpare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        .card {
            transition: transform 0.3s, box-shadow 0.3s;
            border: none;
            border-radius: 10px;
            overflow: hidden;
        }
        .card:hover {
            transform: translateY(-10px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        .card-img-top {
            height: 200px;
            object-fit: cover;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top" style="background: linear-gradient(to right, #2b00ff, #7345e8); box-shadow: 0 0.25rem 0.5rem rgba(0,0,0,0.2);">
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
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="catalog.php">Catalog</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="contact.php">Contact</a>
                    </li>
                </ul>
                <form class="d-flex" action="search.php" method="GET">
                    <input class="form-control me-2" type="search" name="query" placeholder="Search parts..." aria-label="Search" value="<?php echo htmlspecialchars($query); ?>">
                    <button class="btn btn-light" type="submit"><i class="fas fa-search"></i></button>
                </form>
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php"><i class="fas fa-user-circle me-1"></i> My Account</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt me-1"></i> Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php"><i class="fas fa-user-plus me-1"></i> Register</a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="cart.php"><i class="fas fa-shopping-cart me-1"></i> Cart</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Search Results -->
    <div class="container my-5 pt-4">
        <div class="row">
            <div class="col-12">
                <h2 class="text-center mb-4">Search Results for "<?php echo htmlspecialchars($query); ?>"</h2>
                <?php if (isset($searchErrorMessage)): ?>
                    <div class="alert alert-danger"><?php echo $searchErrorMessage; ?></div>
                <?php endif; ?>

                <?php if (empty($searchResults)): ?>
                    <div class="alert alert-info">No parts found matching your search. Try different keywords.</div>
                <?php else: ?>
                    <p class="text-center mb-4">Found <?php echo count($searchResults); ?> result(s)</p>
                    <div class="row row-cols-1 row-cols-md-3 g-4">
                        <?php foreach ($searchResults as $part): ?>
                            <?php
                            // Debugging: Print the image path
                            error_log("Image Path: " . $part['image']);
                            ?>
                            <div class="col">
                                <div class="card h-100">
                                    <img src="<?php echo !empty($part['image']) ? htmlspecialchars($part['image']) : 'images/fallback.jpg'; ?>" class="card-img-top" alt="<?php echo htmlspecialchars($part['name']); ?>" style="height: 200px; object-fit: cover;">
                                    <div class="card-body">
                                        <h5 class="card-title"><?php echo htmlspecialchars($part['name']); ?></h5>
                                        <p class="card-text"><?php echo htmlspecialchars(substr($part['description'], 0, 100) . (strlen($part['description']) > 100 ? '...' : '')); ?></p>
                                        <p class="card-text fw-bold text-primary">$<?php echo number_format($part['price'], 2); ?></p>
                                    </div>
                                    <div class="card-footer bg-white border-0">
                                        <div class="d-flex justify-content-between">
                                            <a href="part_details.php?id=<?php echo htmlspecialchars($part['_id']); ?>" class="btn btn-outline-primary">Details</a>
                                            <form action="add_to_cart.php" method="POST">
                                                <input type="hidden" name="part_id" value="<?php echo htmlspecialchars($part['_id']); ?>">
                                                <button type="submit" class="btn btn-primary"><i class="fas fa-cart-plus me-1"></i> Add to Cart</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-5">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>AutoSpare</h5>
                    <p>Your one-stop shop for quality auto spare parts</p>
                    <div class="social-icons">
                        <a href="#" class="text-white me-2"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-white me-2"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-white"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-md-4">
                    <h5>Quick Links</h5>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-white">Home</a></li>
                        <li><a href="catalog.php" class="text-white">Catalog</a></li>
                        <li><a href="about.php" class="text-white">About Us</a></li>
                        <li><a href="contact.php" class="text-white">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Contact Us</h5>
                    <address>
                        <p><i class="fas fa-map-marker-alt me-2"></i> 123 Auto Street, City, Country</p>
                        <p><i class="fas fa-phone me-2"></i> +1 (555) 123-4567</p>
                        <p><i class="fas fa-envelope me-2"></i> info@autospare.example</p>
                    </address>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> AutoSpare. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>