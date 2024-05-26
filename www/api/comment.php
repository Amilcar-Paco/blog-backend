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

// Parse the URL path to extract the comment ID and post ID
$url = parse_url($_SERVER["REQUEST_URI"]);
$path = explode('/', $url['path']);
$commentId = isset($path[3]) ? $path[3] : null;

if ($requestMethod == 'POST') {
    // Handle comment creation
    $data = json_decode(file_get_contents("php://input"));
    
    // Check if all required fields are provided
    if (!empty($data->post_id) && !empty($data->user_id) && !empty($data->body)) {
        // Check if it's a reply to a comment
        $reply = !empty($_GET['reply']) && $_GET['reply'] === 'true';
        
        // Create query
        $query = "INSERT INTO comments (post_id, user_id, body";
        if ($reply && !empty($data->parent_comment_id)) {
            // If it's a reply, include parent_comment_id in the query
            $query .= ", parent_comment_id";
        }
        $query .= ") VALUES (:post_id, :user_id, :body";
        if ($reply && !empty($data->parent_comment_id)) {
            // If it's a reply, include parent_comment_id in the parameters
            $query .= ", :parent_comment_id";
        }
        $query .= ")";
        
        // Prepare statement
        $stmt = $db->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(":post_id", $data->post_id);
        $stmt->bindParam(":user_id", $data->user_id);
        $stmt->bindParam(":body", $data->body);
        if ($reply && !empty($data->parent_comment_id)) {
            // If it's a reply, bind parent_comment_id parameter
            $stmt->bindParam(":parent_comment_id", $data->parent_comment_id);
        }
        
        // Execute query
        if ($stmt->execute()) {
            echo json_encode(["message" => "Comment created successfully"]);
        } else {
            echo json_encode(["message" => "Unable to create comment", "error" => $stmt->errorInfo()]);
        }
    } else {
        echo json_encode(["message" => "Incomplete data provided"]);
    }
} elseif ($requestMethod == 'GET') {
    // Handle comment retrieval
    if (!empty($commentId)) {
        // Retrieve comment by ID
        $query = "SELECT * FROM comments WHERE id = :commentId";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":commentId", $commentId);
    } elseif (!empty($_GET['post_id'])) {
        // Retrieve comments by post ID
        $postId = $_GET['post_id'];
        $query = "SELECT * FROM comments WHERE post_id = :postId";
        $stmt = $db->prepare($query);
        $stmt->bindParam(":postId", $postId);
    } else {
        // Retrieve all comments
        $query = "SELECT * FROM comments";
        $stmt = $db->prepare($query);
    }

    if ($stmt->execute()) {
        $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($comments);
    } else {
        echo json_encode(["message" => "Unable to retrieve comments", "error" => $stmt->errorInfo()]);
    }
} elseif ($requestMethod == 'PUT') {
    // Handle comment update
    if (!empty($commentId)) {
        $data = json_decode(file_get_contents("php://input"));
        // Check if all required fields are provided for update
        if (!empty($data->body)) {
            // Create query
            $query = "UPDATE comments SET body = :body WHERE id = :commentId";
            
            // Prepare statement
            $stmt = $db->prepare($query);
            
            // Bind parameters
            $stmt->bindParam(":body", $data->body);
            $stmt->bindParam(":commentId", $commentId);
            
            // Execute query
            if ($stmt->execute()) {
                echo json_encode(["message" => "Comment updated successfully"]);
            } else {
                echo json_encode(["message" => "Unable to update comment", "error" => $stmt->errorInfo()]);
            }
        } else {
            echo json_encode(["message" => "Incomplete data provided for update"]);
        }
    } else {
        echo json_encode(["message" => "Comment ID not provided in URL parameters"]);
    }
} elseif ($requestMethod == 'DELETE') {
    // Handle comment deletion
    if (!empty($commentId)) {
        // Create query
        $query = "DELETE FROM comments WHERE id = :commentId";
        
        // Prepare statement
        $stmt = $db->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(":commentId", $commentId);
        
        // Execute query
        if ($stmt->execute()) {
            echo json_encode(["message" => "Comment deleted successfully"]);
        } else {
            echo json_encode(["message" => "Unable to delete comment", "error" => $stmt->errorInfo()]);
        }
    } else {
        echo json_encode(["message" => "Comment ID not provided in URL parameters"]);
    }
} else {
    echo json_encode(["message" => "Invalid request method"]);
}
?>
