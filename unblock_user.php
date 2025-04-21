<?php
session_start();

// Check if the user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header("Location: login.php");
    exit();
}

require "./mongodb_connection.php"; // Include the MongoDB connection file

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $_POST['user_id'];

    try {
        $collection = $db->users;

        // Update the user's status to unblocked
        $result = $collection->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($userId)],
            ['$set' => ['status' => 'active']]
        );

        if ($result->getModifiedCount() > 0) {
            // Redirect to user management page with success message
            header("Location: shouser.php?unblock_success=1");
            exit();
        } else {
            // Redirect to user management page with error message
            header("Location: shouser.php?unblock_error=1");
            exit();
        }
    } catch (MongoDB\Driver\Exception\Exception $e) {
        // Redirect to user management page with error message
        header("Location: shouser.php?unblock_error=1");
        exit();
    }
} else {
    // Redirect to user management page if accessed directly
    header("Location: shouser.php");
    exit();
}
?>