<?php
session_start();

require 'mongodb_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];

    try {
        $spare_parts_collection = $db->spare_parts;
        $product = $spare_parts_collection->findOne(['_id' => new MongoDB\BSON\ObjectId($product_id)]);

        if ($product) {
            // Initialize cart if it doesn't exist
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            // Check if the product is already in the cart
            $product_exists = false;
            foreach ($_SESSION['cart'] as &$item) {
                if ($item['product_id'] == $product_id) {
                    $item['quantity']++;
                    $product_exists = true;
                    break;
                }
            }

            // If the product is not in the cart, add it
            if (!$product_exists) {
                $_SESSION['cart'][] = [
                    'product_id' => $product_id,
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'image' => $product['image'],
                    'quantity' => 1 // You can adjust the quantity later
                ];
            }

            // Return the updated cart data as JSON
            header('Content-Type: application/json');
            echo json_encode(['cart' => $_SESSION['cart']]);
            exit();
        } else {
            echo "Product not found.";
        }
    } catch (MongoDB\Driver\Exception\Exception $e) {
        echo "Error: " . $e->getMessage();
    }
} else {
    echo "Invalid request.";
}
?> 