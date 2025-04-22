<?php
session_start();
require 'mongodb_connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $make = $_POST['make'];
    $model = $_POST['model'];
    $year = $_POST['year'];

    error_log("Make: " . $make); // Debugging statement
    error_log("Model: " . $model); // Debugging statement
    error_log("Year: " . $year); // Debugging statement

    try {
        $cars_collection = $db->cars;
        $car = $cars_collection->findOne(['brand' => $make, 'model' => $model, 'year' => (int)$year]);

        if ($car) {
            $carId = (string)$car['_id'];
            error_log("Car ID: " . $carId); // Debugging statement

            $spare_parts_collection = $db->spare_parts;
            $parts = $spare_parts_collection->find(['compatible_cars' => new MongoDB\BSON\ObjectId($carId)])->toArray();

            error_log("Parts found: " . count($parts)); // Debugging statement

            if (!empty($parts)) {
                echo json_encode(['success' => true, 'parts' => $parts]);
            } else {
                echo json_encode(['success' => false, 'message' => 'No parts found for the selected vehicle.']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'No matching car found.']);
        }
    } catch (MongoDB\Driver\Exception\Exception $e) {
        error_log("Error: " . $e->getMessage()); // Debugging statement
        echo json_encode(['success' => false, 'message' => 'Error fetching parts: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
}
?> 