<?php
// Include MongoDB connection file
require "mongodb_connection.php";

// Initialize message variable
$message = '';
$messageClass = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['register'])) {
    // Collect form data
    $fullName = trim($_POST['fullName'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirmPassword = $_POST['confirmPassword'] ?? '';
    $termsAgree = isset($_POST['termsAgree']);

    // Enhanced validation
    $errors = [];

    // Validate full name (no special characters except spaces, hyphens, apostrophes)
    if (empty($fullName)) {
        $errors[] = "Full name is required.";
    } elseif (!preg_match('/^[a-zA-Z\s\'-]{2,50}$/', $fullName)) {
        $errors[] = "Full name contains invalid characters or is too long.";
    }

    // Validate email
    if (empty($email)) {
        $errors[] = "Email is required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    } else {
        // Check if email already exists in database
        $existingUser = $db->users->findOne(['email' => $email]);
        if ($existingUser) {
            $errors[] = "Email already registered. Please use a different email or login.";
        }
    }

    // Validate password
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/', $password)) {
        $errors[] = "Password must include at least one uppercase letter, one lowercase letter, one number, and one special character.";
    }

    // Check if passwords match
    if ($password !== $confirmPassword) {
        $errors[] = "Passwords do not match.";
    }

    // Check terms agreement
    if (!$termsAgree) {
        $errors[] = "You must agree to the terms and conditions.";
    }

    // If there are no validation errors, proceed with registration
    if (empty($errors)) {
        // Hash the password for secure storage
        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

        // Prepare data for MongoDB
        $user = [
            'fullName' => $fullName,
            'email' => $email,
            'password' => $hashedPassword,
            'createdAt' => new MongoDB\BSON\UTCDateTime()
        ];

        // Attempt to insert into MongoDB
        try {
            // Create the collection if it doesn't exist
            try {
                $db->createCollection('users');
            } catch (Exception $e) {
                // Collection already exists, this is fine
            }
            
            // Insert the user
            $result = $db->users->insertOne($user);

            if ($result->getInsertedCount() > 0) {
                // Set a success message that will be stored in session
                session_start();
                $_SESSION['registration_success'] = "Your account has been created successfully! Please log in.";
                
                // Redirect to the login page
                header("Location: login.php");
                exit;
            } else {
                $message = "Failed to register user.";
                $messageClass = "alert-danger";
            }
        } catch (Exception $e) {
            $message = "Error while inserting data: " . $e->getMessage();
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
    <title>Register - AutoSpare</title>
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

        .register-container {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 10px;
            padding: 30px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            margin: 100px auto;
        }

        .register-container h2 {
            text-align: center;
            margin-bottom: 20px;
        }

        .register-container .form-control {
            margin-bottom: 15px;
        }

        .register-container .btn {
            width: 100%;
        }

        .register-container .text-center {
            margin-top: 15px;
        }
        
        .password-strength {
            height: 5px;
            margin-top: 5px;
            background-color: #e9ecef;
            border-radius: 3px;
        }
        
        .password-strength-meter {
            height: 100%;
            border-radius: 3px;
            transition: width 0.3s ease;
        }
        
        .weak {
            background-color: #dc3545;
            width: 25%;
        }
        
        .medium {
            background-color: #ffc107;
            width: 50%;
        }
        
        .strong {
            background-color: #28a745;
            width: 100%;
        }
        
        .password-toggle {
            position: relative;
        }
        
        .password-toggle .form-control {
            padding-right: 40px;
        }
        
        .password-toggle-icon {
            position: absolute;
            top: 50%;
            right: 10px;
            transform: translateY(-50%);
            cursor: pointer;
            color: #6c757d;
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
                    <a href="login.php" class="btn btn-outline-light me-2">
                        <i class="fas fa-user me-1"></i> Login
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

    <!-- Register Section -->
    <div class="register-container">
        <h2>Create an Account</h2>
        
        <!-- Display message if there is one -->
        <?php if (!empty($message)): ?>
            <div class="alert <?php echo $messageClass; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <form id="registerForm" method="POST" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" novalidate>
            <div class="mb-3">
                <label for="fullName" class="form-label">Full Name</label>
                <input type="text" class="form-control" id="fullName" name="fullName" value="<?php echo htmlspecialchars($fullName ?? ''); ?>" required>
                <div class="invalid-feedback">Please enter your full name</div>
            </div>
            <div class="mb-3">
                <label for="email" class="form-label">Email address</label>
                <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($email ?? ''); ?>" required>
                <div class="invalid-feedback">Please enter a valid email address</div>
            </div>
            <div class="mb-3">
                <label for="password" class="form-label">Password</label>
                <div class="password-toggle">
                    <input type="password" class="form-control" id="password" name="password" required>
                    <span class="password-toggle-icon" id="togglePassword">
                        <i class="fas fa-eye"></i>
                    </span>
                </div>
                <div class="password-strength">
                    <div id="passwordStrengthMeter" class="password-strength-meter"></div>
                </div>
                <small class="form-text text-muted">Password must be at least 8 characters with lowercase, uppercase, number, and special character.</small>
            </div>
            <div class="mb-3">
                <label for="confirmPassword" class="form-label">Confirm Password</label>
                <!-- Removed the password toggle elements for confirm password field -->
                <input type="password" class="form-control" id="confirmPassword" name="confirmPassword" required>
                <div class="invalid-feedback">Passwords do not match</div>
            </div>
            <div class="mb-3 form-check">
                <input type="checkbox" class="form-check-input" id="termsAgree" name="termsAgree" required>
                <label class="form-check-label" for="termsAgree">I agree to the <a href="#">Terms and Conditions</a></label>
                <div class="invalid-feedback">You must agree to the terms and conditions</div>
            </div>
            <button type="submit" name="register" class="btn btn-primary">Register</button>
        </form>
        <div class="text-center mt-3">
            <p>Already have an account? <a href="login.php">Login here</a></p>
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
            const form = document.getElementById('registerForm');
            const password = document.getElementById('password');
            const confirmPassword = document.getElementById('confirmPassword');
            const passwordStrengthMeter = document.getElementById('passwordStrengthMeter');
            const togglePassword = document.getElementById('togglePassword');
            
            // Toggle password visibility for main password only
            togglePassword.addEventListener('click', function() {
                const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                password.setAttribute('type', type);
                this.querySelector('i').classList.toggle('fa-eye');
                this.querySelector('i').classList.toggle('fa-eye-slash');
            });
            
            // Removed the toggleConfirmPassword event listener since we removed that element
            
            // Password strength meter
            password.addEventListener('input', function() {
                const value = this.value;
                
                // Reset classes
                passwordStrengthMeter.classList.remove('weak', 'medium', 'strong');
                
                if (value.length === 0) {
                    passwordStrengthMeter.style.width = '0';
                    return;
                }
                
                // Check password strength
                const hasLowerCase = /[a-z]/.test(value);
                const hasUpperCase = /[A-Z]/.test(value);
                const hasNumber = /\d/.test(value);
                const hasSpecialChar = /[@$!%*?&]/.test(value);
                const isLongEnough = value.length >= 8;
                
                const strength = [hasLowerCase, hasUpperCase, hasNumber, hasSpecialChar, isLongEnough].filter(Boolean).length;
                
                if (strength <= 2) {
                    passwordStrengthMeter.classList.add('weak');
                } else if (strength <= 4) {
                    passwordStrengthMeter.classList.add('medium');
                } else {
                    passwordStrengthMeter.classList.add('strong');
                }
            });
            
            // Real-time validation for password match
            confirmPassword.addEventListener('input', function() {
                if (this.value !== password.value) {
                    this.setCustomValidity("Passwords don't match");
                } else {
                    this.setCustomValidity('');
                }
            });
            
            // Form validation before submission
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                
                // Add validation classes to show feedback
                form.classList.add('was-validated');
                
                // Custom validation for the password
                const passwordRegex = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/;
                if (!passwordRegex.test(password.value)) {
                    password.setCustomValidity("Password must have at least 8 characters with lowercase, uppercase, number, and special character");
                    event.preventDefault();
                } else {
                    password.setCustomValidity('');
                }
                
                // Confirm passwords match
                if (password.value !== confirmPassword.value) {
                    confirmPassword.setCustomValidity("Passwords don't match");
                    event.preventDefault();
                } else {
                    confirmPassword.setCustomValidity('');
                }
            });
        });
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>