<?php
session_start();

// Check if the user is logged in as admin
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Include MongoDB connection
require 'mongodb_connection.php';

if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => 'Missing spare part ID']);
    exit();
}

try {
    $spare_part_id = $_GET['id'];
    
    // Get the spare part document
    $spare_parts_collection = $db->spare_parts;
    $spare_part = $spare_parts_collection->findOne(['_id' => new MongoDB\BSON\ObjectId($spare_part_id)]);
    
    if (!$spare_part) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'error' => 'Spare part not found']);
        exit();
    }
    
    // Extract the compatible car IDs as strings
    $compatible_cars = [];
    foreach ($spare_part['compatible_cars'] as $car_id) {
        $compatible_cars[] = (string)$car_id;
    }
    
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'compatible_cars' => $compatible_cars]);
    
} catch (MongoDB\Driver\Exception\Exception $e) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>