<?php
header("Content-Type: application/json");
include_once 'db.php';

$database = new Database();
$db = $database->getConnection();

if (!$db) {
    exit;
}

$requestMethod = $_SERVER["REQUEST_METHOD"];

if ($requestMethod == 'POST') {
    // Handle user registration
    $data = json_decode(file_get_contents("php://input"));
    
    // Check if all required fields are provided
    if (!empty($data->username) && !empty($data->password)) {
        // Create query
        $query = "INSERT INTO users (username, password) VALUES (:username, :password)";
        
        // Prepare statement
        $stmt = $db->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(":username", $data->username);
        $stmt->bindParam(":password", $data->password);
        
        // Execute query
        if ($stmt->execute()) {
            echo json_encode(["message" => "User registered successfully"]);
        } else {
            echo json_encode(["message" => "Unable to register user", "error" => $stmt->errorInfo()]);
        }
    } else {
        echo json_encode(["message" => "Incomplete data provided"]);
    }
} elseif ($requestMethod == 'GET') {
    // Handle user retrieval
    $query = "SELECT * FROM users";
    $stmt = $db->prepare($query);
    if ($stmt->execute()) {
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($users);
    } else {
        echo json_encode(["message" => "Unable to retrieve users", "error" => $stmt->errorInfo()]);
    }
} elseif ($requestMethod == 'PUT') {
    // Handle user update
    $data = json_decode(file_get_contents("php://input"));
    
    // Check if all required fields are provided
    if (!empty($data->id) && !empty($data->username) && !empty($data->password)) {
        // Create query
        $query = "UPDATE users SET username = :username, password = :password WHERE id = :id";
        
        // Prepare statement
        $stmt = $db->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(":id", $data->id);
        $stmt->bindParam(":username", $data->username);
        $stmt->bindParam(":password", $data->password);
        
        // Execute query
        if ($stmt->execute()) {
            echo json_encode(["message" => "User updated successfully"]);
        } else {
            echo json_encode(["message" => "Unable to update user", "error" => $stmt->errorInfo()]);
        }
    } else {
        echo json_encode(["message" => "Incomplete data provided"]);
    }
} elseif ($requestMethod == 'DELETE') {
    // Handle user deletion
    $data = json_decode(file_get_contents("php://input"));
    
    // Check if user ID is provided
    if (!empty($data->id)) {
        // Create query
        $query = "DELETE FROM users WHERE id = :id";
        
        // Prepare statement
        $stmt = $db->prepare($query);
        
        // Bind parameters
        $stmt->bindParam(":id", $data->id);
        
        // Execute query
        if ($stmt->execute()) {
            echo json_encode(["message" => "User deleted successfully"]);
        } else {
            echo json_encode(["message" => "Unable to delete user", "error" => $stmt->errorInfo()]);
        }
    } else {
        echo json_encode(["message" => "User ID not provided"]);
    }
} else {
    echo json_encode(["message" => "Invalid request method"]);
}
?>
