<?php
require "./vendor/autoload.php";

$con = new MongoDB\Client("mongodb://localhost:27017");

try {
    // Select or create the 'spare_parts_store' database
    $db = $con->spare_parts_store;
    
   
    
    // echo "Connection successful and 'spare_parts_store' database created.\n";
} catch (MongoDB\Driver\Exception\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
