<?php
include_once 'db.php';

$database = new Database();
$conn = $database->getConnection();

if ($conn) {
    echo "Connection successful!";
} else {
    echo "Connection failed!";
}
?>
