<?php
header("Content-Type: application/json");
include_once 'db.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    echo json_encode(["message" => "Database connection failed"]);
    exit;
}

$requestMethod = $_SERVER["REQUEST_METHOD"];

// Parse the URL path to extract the post ID
$url = parse_url($_SERVER["REQUEST_URI"]);
$path = explode('/', $url['path']);
$categoryId = isset($path[3]) ? $path[3] : null;

if ($requestMethod == 'GET') {
    // Handle post retrieval
    if (!empty($categoryId)) {
        // Retrieve category by ID
        $query = "SELECT * FROM categories WHERE id = :categoryId";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":categoryId", $categoryId);
    } else {
        // Retrieve all posts
        $query = "SELECT * FROM categories";
        $stmt = $db->prepare($query);
    }

    if ($stmt->execute()) {
        $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($categories);
    } else {
        echo json_encode(["message" => "Unable to retrieve categories", "error" => $stmt->errorInfo()]);
    }
} else {
    echo json_encode(["message" => "Invalid request method"]);
}
