<?php
session_start();

// Check if the user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require 'mongodb_connection.php';

// Get category ID from the POST request
$categoryId = $_POST['category_id'] ?? null;

if (!$categoryId) {
    $_SESSION['errorMessage'] = "Category ID is missing.";
    header("Location: category.php");
    exit();
}

try {
    $collection = $db->categories;
    $deleteResult = $collection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($categoryId)]);

    if ($deleteResult->getDeletedCount() > 0) {
        $_SESSION['successMessage'] = "Category deleted successfully!";
    } else {
        $_SESSION['errorMessage'] = "Failed to delete category.";
    }
} catch (MongoDB\Driver\Exception\Exception $e) {
    $_SESSION['errorMessage'] = "Error deleting category: " . $e->getMessage();
}

header("Location: category.php");
exit();
?>