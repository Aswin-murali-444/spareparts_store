<?php
session_start();

// Check if the user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Include MongoDB connection
require 'mongodb_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category_id = $_POST['category_id'];
    $compatible_cars = $_POST['compatible_cars']; // This will be an array of car IDs

    // Handle image update
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
        // If no new image is uploaded, keep the existing image
        $spare_part = $db->spare_parts->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
        $image = $spare_part['image'];
    }

    try {
        // Select the 'spare_parts' collection
        $collection = $db->spare_parts;

        // Convert compatible_cars to an array of ObjectIds
        $compatible_cars_object_ids = [];
        foreach ($compatible_cars as $car_id) {
            $compatible_cars_object_ids[] = new MongoDB\BSON\ObjectId($car_id);
        }

        // Update the spare part
        $updateResult = $collection->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($id)],
            [
                '$set' => [
                    'name' => $name,
                    'description' => $description,
                    'price' => (float)$price,
                    'stock' => (int)$stock,
                    'category_id' => new MongoDB\BSON\ObjectId($category_id),
                    'compatible_cars' => $compatible_cars_object_ids,
                    'image' => $image
                ]
            ]
        );

        if ($updateResult->getModifiedCount() > 0) {
            // Redirect to the same page to prevent form resubmission
            header("Location: spare_parts_collection.php?success=1");
            exit();
        } else {
            // If no changes were made, redirect with an error message
            header("Location: spare_parts_collection.php?error=No changes were made.");
            exit();
        }
    } catch (MongoDB\Driver\Exception\Exception $e) {
        // Handle any errors that occur during the update
        header("Location: spare_parts_collection.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // If the request method is not POST, redirect to the spare parts collection page
    header("Location: spare_parts_collection.php");
    exit();
}