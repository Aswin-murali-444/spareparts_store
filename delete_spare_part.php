<?php
session_start();

// Check if the user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

// Include MongoDB connection
require 'mongodb_connection.php';

// Check if the 'id' parameter is set
if (isset($_GET['id'])) {
    $spare_part_id = $_GET['id'];

    try {
        // Select the 'spare_parts' collection
        $collection = $db->spare_parts;

        // Convert the spare part ID to an ObjectId
        $spare_part_object_id = new MongoDB\BSON\ObjectId($spare_part_id);

        // Delete the spare part
        $deleteResult = $collection->deleteOne(['_id' => $spare_part_object_id]);

        if ($deleteResult->getDeletedCount() > 0) {
            // Redirect back to the spare parts collection page with a success message
            header("Location: spare_parts_collection.php?success=2");
            exit();
        } else {
            // Redirect back with an error message if the spare part was not found
            header("Location: spare_parts_collection.php?error=delete_failed");
            exit();
        }
    } catch (MongoDB\Driver\Exception\Exception $e) {
        // Handle any exceptions and redirect back with an error message
        header("Location: spare_parts_collection.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    // Redirect back if the 'id' parameter is not set
    header("Location: spare_parts_collection.php?error=invalid_id");
    exit();
}
?>