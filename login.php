<?php
// Include MongoDB connection file
require "mongodb_connection.php";

// Initialize message variable
$message = '';
$messageClass = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    // Collect form data
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $rememberMe = isset($_POST['rememberMe']);

    // Validate inputs
    $errors = [];

    // Validate email
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Validate password
    if (empty($password)) {
        $errors[] = "Password is required.";
    }

    // If no validation errors, check credentials
    if (empty($errors)) {
        // Check if the credentials match the admin credentials
        $adminEmail = 'admin@example.com'; // Replace with actual admin email
        $adminPassword = 'admin123'; // Replace with actual admin password

        if ($email === $adminEmail && $password === $adminPassword) {
            // Start session and set admin logged in flag
            session_start();
            $_SESSION['admin_logged_in'] = true;

            // Redirect to admin page
            header("Location: admin.php");
            exit();
        }

        // Find user in MongoDB
        try {
            $user = $db->users->findOne(['email' => $email]);
            
            if ($user && password_verify($password, (string)$user->password)) {
                // Check if the user is blocked
                if (isset($user->status) && $user->status === 'blocked') {
                    $message = "This account has been blocked. Please contact support.";
                    $messageClass = "alert-danger";
                } else {
                    // Set up a session
                    session_start();
                    $_SESSION['user_id'] = (string)$user->_id;
                    $_SESSION['user_name'] = $user->fullName;
                    $_SESSION['user_email'] = $user->email;
                    
                    // Handle "Remember Me" functionality
                    if ($rememberMe) {
                        // Set cookies for 30 days
                        $token = bin2hex(random_bytes(32));
                        setcookie('remember_token', $token, time() + (86400 * 30), '/');
                        
                        // Store token in database
                        $db->users->updateOne(
                            ['_id' => $user->_id],
                            ['$set' => ['remember_token' => $token]]
                        );
                    }
                    
                    // Redirect to index or dashboard page
                    header("Location: index.php");
                    exit();
                }
            } else {
                $message = "Invalid email or password.";
                $messageClass = "alert-danger";
            }
        } catch (Exception $e) {
            $message = "Error while logging in: " . $e->getMessage();
            $messageClass = "alert-danger";
        }
    } else {
        // If there are validation errors, display them
        $message = implode("<br>", $errors);
        $messageClass = "alert-danger";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - AutoSpare</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background: url('images/background.jpg') no-repeat center center/cover;
            background-attachment: fixed;
            padding-top: 56px;
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            line-height: 1.6;
        }

        .login-container {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            max-width: 450px;
            margin: 100px auto;
        }

        .login-container h2 {
            text-align: center;
            margin-bottom: 30px;
        }

        .login-container .form-control {
            margin-bottom: 15px;
        }

        .login-container .btn {
            width: 100%;
        }

        .login-container .text-center {
            margin-top: 15px;
        }
        
        .social-login {
            margin-top: 20px;
            text-align: center;
        }
        
        .social-login .btn {
            margin: 0 5px;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .divider {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }
        
        .divider::before, .divider::after {
            content: "";
            flex: 1;
            border-bottom: 1px solid #dee2e6;
        }
        
        .divider span {
            padding: 0 10px;
            color: #6c757d;
        }
        
        /* Fix for the password field */
        .password-container {
            position: relative;
        }
        
        .password-container .form-control {
            padding-right: 40px;
        }
        
        .password-toggle {
            position: absolute;
            right: 10px; /* Adjusted from 0 to 10px */
            top: 75%; /* Center vertically */
            transform: translateY(-50%); /* Center vertically */
            height: 100%;
            background: none;
            border: none;
            color: #6c757d;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            z-index: 10;
        }
        
        .password-toggle:focus {
            outline: none;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark fixed-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php">
                <i class="fas fa-cogs me-2"></i>
                <span>AutoSpare</span>
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
                        <a class="nav-link" href="index.php#about">About Us</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#contact">Contact</a>
                    </li>
                </ul>
                <form class="d-flex me-3">
                    <input class="form-control me-2" type="search" placeholder="Search for parts..." aria-label="Search">
                    <button class="btn btn-outline-light" type="submit"><i class="fas fa-search"></i></button>
                </form>
                <div class="d-flex">
                    <a href="register.php" class="btn btn-outline-light me-2">
                        <i class="fas fa-user-plus me-1"></i> Register
                    </a>
                    <a href="#" class="btn btn-outline-light me-2" data-bs-toggle="modal" data-bs-target="#wishlistModal">
                        <i class="fas fa-heart me-1"></i> Wishlist
                    </a>
                    <a href="#" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cartModal">
                        <i class="fas fa-shopping-cart me-1"></i> Cart <span class="badge bg-danger">3</span>
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Login Section -->
    <div class="login-container">
        <h2>Login to Your Account</h2>
        
        <!-- Display message if there is one -->
        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $messageClass; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <form id="loginForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" novalidate>
            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                <div class="invalid-feedback">Please enter a valid email address</div>
            </div>
            <div class="mb-3 password-container">
                <label for="password" class="form-label">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
                <button type="button" class="password-toggle" id="passwordToggle">
                    <i class="far fa-eye"></i>
                </button>
                <div class="invalid-feedback">Please enter your password</div>
            </div>
            <div class="d-flex justify-content-between mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="rememberMe" name="rememberMe">
                    <label class="form-check-label" for="rememberMe">Remember me</label>
                </div>
                <div>
                    <a href="forgot-password.php">Forgot Password?</a>
                </div>
            </div>
            <button type="submit" name="login" class="btn btn-primary">Login</button>
        </form>
        
        <div class="divider">
            <span>or login with</span>
        </div>
        
        <div class="social-login">
            <a href="#" class="btn btn-outline-primary"><i class="fab fa-facebook-f"></i></a>
            <a href="#" class="btn btn-outline-danger"><i class="fab fa-google"></i></a>
            <a href="#" class="btn btn-outline-dark"><i class="fab fa-apple"></i></a>
        </div>
        
        <div class="text-center mt-3">
            <p>Don't have an account? <a href="register.php">Register here</a></p>
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

    <script>
        // Enhanced client-side validation with visual feedback
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('loginForm');
            const passwordField = document.getElementById('password');
            const passwordToggle = document.getElementById('passwordToggle');
            
            // Form validation before submission
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                // Add validation classes to show feedback
                form.classList.add('was-validated');
            });
            
            // Toggle password visibility
            passwordToggle.addEventListener('click', function() {
                const type = passwordField.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordField.setAttribute('type', type);
                this.innerHTML = type === 'password' ? '<i class="far fa-eye"></i>' : '<i class="far fa-eye-slash"></i>';
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>