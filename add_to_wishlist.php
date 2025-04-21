<?php
session_start();

require 'mongodb_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];

    try {
        $spare_parts_collection = $db->spare_parts;
        $product = $spare_parts_collection->findOne(['_id' => new MongoDB\BSON\ObjectId($product_id)]);

        if ($product) {
            // Initialize wishlist if it doesn't exist
            if (!isset($_SESSION['wishlist'])) {
                $_SESSION['wishlist'] = [];
            }

            // Check if the product is already in the wishlist
            $product_exists = false;
            foreach ($_SESSION['wishlist'] as $item) {
                if ($item['product_id'] == $product_id) {
                    $product_exists = true;
                    break;
                }
            }

            // If the product is not in the wishlist, add it
            if (!$product_exists) {
                $_SESSION['wishlist'][] = [
                    'product_id' => $product_id,
                    'name' => $product['name'],
                    'price' => $product['price'],
                    'image' => $product['image'],
                    'description' => $product['description'] ?? 'No description available' // Use null coalescing operator
                ];
            }

            // Return the updated wishlist data as JSON
            header('Content-Type: application/json');
            echo json_encode(['wishlist' => $_SESSION['wishlist']]);
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