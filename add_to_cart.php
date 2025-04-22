<?php
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not logged in']);
    exit();
}

if (!isset($_POST['product_id'])) {
    echo json_encode(['success' => false, 'message' => 'Product ID not provided']);
    exit();
}

$productId = $_POST['product_id'];

// Fetch product details from the database (assuming MongoDB)
require 'mongodb_connection.php';

try {
    $product = $db->spare_parts->findOne(['_id' => new MongoDB\BSON\ObjectId($productId)]);

    if (!$product) {
        echo json_encode(['success' => false, 'message' => 'Product not found']);
        exit();
    }

    // Initialize cart if not already set
    if (!isset($_SESSION['cart'])) {
        $_SESSION['cart'] = [];
    }

    // Check if the product is already in the cart
    $found = false;
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['product_id'] == $productId) {
            $item['quantity'] += 1;
            $found = true;
            break;
        }
    }

    // If the product is not in the cart, add it
    if (!$found) {
        $_SESSION['cart'][] = [
            'product_id' => $productId,
            'name' => $product['name'],
            'price' => $product['price'],
            'image' => $product['image'],
            'quantity' => 1
        ];
    }

    // Return success response
    echo json_encode(['success' => true, 'cartCount' => count($_SESSION['cart'])]);

    error_log("Session: " . print_r($_SESSION, true));
    error_log("Product ID: " . $productId);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
?> 