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

// Parse the URL path to extract the like ID
$url = parse_url($_SERVER["REQUEST_URI"]);
$path = explode('/', $url['path']);
$likeId = isset($path[3]) ? $path[3] : null;

if ($requestMethod == 'POST') {
    // Handle like creation
    $data = json_decode(file_get_contents("php://input"));
    
    // Check if all required fields are provided
    if (!empty($data->post_id) && !empty($data->user_id)) {
        // Create query
        $query = "INSERT INTO likes (post_id, user_id) VALUES (:post_id, :user_id)";
        
        // Prepare statement
        $stmt = $db->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(":post_id", $data->post_id);
        $stmt->bindParam(":user_id", $data->user_id);
        
        // Execute query
        try {
            if ($stmt->execute()) {
                echo json_encode(["message" => "Like created successfully"]);
            } else {
                echo json_encode(["message" => "Unable to create like", "error" => $stmt->errorInfo()]);
            }
        } catch (PDOException $e) {
            if ($e->getCode() == 23000) { // Integrity constraint violation
                echo json_encode(["message" => "User has already liked this post"]);
            } else {
                echo json_encode(["message" => "Unable to create like", "error" => $e->getMessage()]);
            }
        }
    } else {
        echo json_encode(["message" => "Incomplete data provided"]);
    }
} elseif ($requestMethod == 'GET') {
    // Handle like retrieval
    if (!empty($likeId)) {
        // Retrieve like by ID
        $query = "SELECT * FROM likes WHERE id = :likeId";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":likeId", $likeId);
    } elseif (!empty($_GET['post_id'])) {
        // Retrieve likes by post ID
        $postId = $_GET['post_id'];
        $query = "SELECT * FROM likes WHERE post_id = :postId";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":postId", $postId);
    } else {
        // Retrieve all likes
        $query = "SELECT * FROM likes";
        $stmt = $db->prepare($query);
    }

    if ($stmt->execute()) {
        $likes = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($likes);
    } else {
        echo json_encode(["message" => "Unable to retrieve likes", "error" => $stmt->errorInfo()]);
    }
} elseif ($requestMethod == 'DELETE') {
    // Handle like deletion
    $data = json_decode(file_get_contents("php://input"));
    if (!empty($data->post_id) && !empty($data->user_id)) {
        // Create query
        $query = "DELETE FROM likes WHERE post_id = :post_id AND user_id = :user_id";
        
        // Prepare statement
        $stmt = $db->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(":post_id", $data->post_id);
        $stmt->bindParam(":user_id", $data->user_id);
        
        // Execute query
        if ($stmt->execute()) {
            echo json_encode(["message" => "Like deleted successfully"]);
        } else {
            echo json_encode(["message" => "Unable to delete like", "error" => $stmt->errorInfo()]);
        }
    } else {
        echo json_encode(["message" => "Incomplete data provided"]);
    }
} else {
    echo json_encode(["message" => "Invalid request method"]);
}
