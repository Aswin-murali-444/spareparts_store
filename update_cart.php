<?php
session_start();

require 'mongodb_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];
    $action = $_POST['action'];

    try {
        // Validate inputs
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }

        $cart = &$_SESSION['cart']; // Create a reference to the cart

        $product_exists = false;
        foreach ($cart as $key => &$item) {
            if ($item['product_id'] == $product_id) {
                $product_exists = true;

                if ($action == 'increase') {
                    $item['quantity']++;
                } elseif ($action == 'decrease') {
                    $item['quantity']--;
                    if ($item['quantity'] <= 0) {
                        unset($cart[$key]); // Remove the item if quantity is 0 or less
                    }
                } elseif ($action == 'remove') {
                    unset($cart[$key]); // Remove the item from the cart
                }
                break;
            }
        }

        // Reindex the array to avoid gaps
        $cart = array_values($cart);

        // Return the updated cart data as JSON
        header('Content-Type: application/json');
        echo json_encode(['cart' => $cart]);
        exit();

    } catch (Exception $e) {
        http_response_code(500); // Set a 500 Internal Server Error status code
        echo json_encode(['error' => $e->getMessage()]); // Return the error message as JSON
        error_log($e->getMessage()); // Log the error message to the server's error log
    }
}
?> 