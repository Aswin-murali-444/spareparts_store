<?php
session_start();
require 'mongodb_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message_id = $_POST['message_id'];

    try {
        $contact_info_collection = $db->contact_info;
        $updateResult = $contact_info_collection->updateOne(
            ['_id' => new MongoDB\BSON\ObjectId($message_id)],
            ['$set' => ['read' => true]]
        );

        if ($updateResult->getModifiedCount() > 0) {
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Message not found or already read']);
        }
    } catch (MongoDB\Driver\Exception\Exception $e) {
        echo json_encode(['success' => false, 'error' => 'Error updating message: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
}
?> 