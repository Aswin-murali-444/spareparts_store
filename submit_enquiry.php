<?php
session_start();

// Include MongoDB connection
require 'mongodb_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get form data
    $name = $_POST['name'];
    $email = $_POST['email'];
    $message = $_POST['message'];

    // Prepare the document to insert
    $document = [
        'name' => $name,
        'email' => $email,
        'message' => $message,
        'created_at' => new MongoDB\BSON\UTCDateTime()
    ];

    try {
        // Insert the document into the contact_info collection
        $db->contact_info->insertOne($document);

        // Return a JSON response indicating success
        echo json_encode(['success' => true]);
        exit();
    } catch (MongoDB\Driver\Exception\Exception $e) {
        // Return a JSON response indicating failure
        echo json_encode(['success' => false, 'message' => 'Failed to send your enquiry. Please try again later.']);
        exit();
    }
} else {
    // If the request method is not POST, return a JSON response indicating failure
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}
?> 