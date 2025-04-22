<?php
session_start();
require 'mongodb_connection.php';

if (isset($_GET['make'])) {
    $make = $_GET['make'];

    try {
        $cars_collection = $db->cars;
        $models = $cars_collection->distinct('model', ['brand' => $make]);
        $years = $cars_collection->distinct('year', ['brand' => $make]);

        echo json_encode(['models' => $models, 'years' => $years]);
    } catch (MongoDB\Driver\Exception\Exception $e) {
        echo json_encode(['error' => 'Error fetching data: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['error' => 'No make provided']);
}
?> 