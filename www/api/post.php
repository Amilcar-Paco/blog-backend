<?php

header("Content-Type: application/json");
// Allow requests from all origins
header("Access-Control-Allow-Origin: *");
// Allow specific methods
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE");
// Allow specific headers
header("Access-Control-Allow-Headers: Origin, Content-Type, Accept, Authorization");
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
$postId = isset($path[3]) ? $path[3] : null;

if ($requestMethod == 'POST') {
    // Handle post creation
    $data = json_decode(file_get_contents("php://input"));

    // Check if all required fields are provided
    if (!empty($data->title) && !empty($data->body) && !empty($data->category_id)) {
        // Create query
        $query = "INSERT INTO posts (user_id, title, category_id,image, body) VALUES (:userId, :title, :category_id, :image, :body)";

        // Prepare statement
        $stmt = $db->prepare($query);

        // Bind parameters
        $userId = 1; // assuming user_id is 1 for this example
        $stmt->bindParam(":userId", $userId);
        $stmt->bindParam(":title", $data->title);
        $stmt->bindParam(":category_id", $data->category_id);
        $stmt->bindParam(":image", $data->image);
        $stmt->bindParam(":body", $data->body);

        // Execute query
        if ($stmt->execute()) {
            echo json_encode(["message" => "Post created successfully"]);
        } else {
            echo json_encode(["message" => "Unable to create post", "error" => $stmt->errorInfo()]);
        }
    } else {
        echo json_encode(["message" => "Incomplete data provided"]);
    }
} elseif ($requestMethod == 'GET') {
    // Handle post retrieval
    if (!empty($postId)) {
        // Retrieve post by ID
        $query = "SELECT * FROM posts WHERE id = :postId";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":postId", $postId);
    } elseif (!empty($_GET['userId'])) {
        // Retrieve posts by user ID
        $userId = $_GET['userId'];
        $query = "SELECT * FROM posts WHERE user_id = :userId";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":userId", $userId);
    } else {
        // Retrieve all posts
        $query = "SELECT * FROM posts";
        $stmt = $db->prepare($query);
    }

    if ($stmt->execute()) {
        $posts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($posts);
    } else {
        echo json_encode(["message" => "Unable to retrieve posts", "error" => $stmt->errorInfo()]);
    }
} elseif ($requestMethod == 'PUT') {
    // Handle post update
    if (!empty($postId)) {
        $data = json_decode(file_get_contents("php://input"));
        // Check if all required fields are provided for update
        if (!empty($data->title) && !empty($data->body)) {
            // Create query
            $query = "UPDATE posts SET title = :title, body = :body WHERE id = :postId";

            // Prepare statement
            $stmt = $db->prepare($query);

            // Bind parameters
            $stmt->bindParam(":title", $data->title);
            $stmt->bindParam(":body", $data->body);
            $stmt->bindParam(":postId", $postId);

            // Execute query
            if ($stmt->execute()) {
                echo json_encode(["message" => "Post updated successfully"]);
            } else {
                echo json_encode(["message" => "Unable to update post", "error" => $stmt->errorInfo()]);
            }
        } else {
            echo json_encode(["message" => "Incomplete data provided for update"]);
        }
    } else {
        echo json_encode(["message" => "Post ID not provided in URL parameters"]);
    }
} elseif ($requestMethod == 'DELETE') {
    // Handle post deletion
    if (!empty($postId)) {
        // Create query
        $query = "DELETE FROM posts WHERE id = :postId";

        // Prepare statement
        $stmt = $db->prepare($query);

        // Bind parameters
        $stmt->bindParam(":postId", $postId);

        // Execute query
        if ($stmt->execute()) {
            echo json_encode(["message" => "Post deleted successfully"]);
        } else {
            echo json_encode(["message" => "Unable to delete post", "error" => $stmt->errorInfo()]);
        }
    } else {
        echo json_encode(["message" => "Post ID not provided in URL parameters"]);
    }
} else {
    echo json_encode(["message" => "Invalid request method"]);
}
