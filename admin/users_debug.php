<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);

require_once '../config.php';
requireLogin();

$user = getCurrentUser();

echo "<h2>Debug Info</h2>";
echo "<pre>";
echo "Current User:\n";
print_r($user);
echo "\n\n";

if ($user['role'] !== 'administrator') {
    echo "ERROR: User is not administrator!\n";
    echo "Role: " . $user['role'] . "\n";
    exit();
}

$conn = getDBConnection();
echo "Database connection: OK\n\n";

// Check if POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    echo "POST Request Detected\n";
    echo "POST Data:\n";
    print_r($_POST);
    echo "\n\n";
    
    if (isset($_POST['action']) && $_POST['action'] === 'delete') {
        echo "Delete action detected\n";
        echo "User ID to delete: " . $_POST['user_id'] . "\n\n";
        
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id=?");
        if (!$stmt) {
            echo "ERROR preparing statement: " . $conn->error . "\n";
            exit();
        }
        
        $stmt->bind_param("i", $_POST['user_id']);
        echo "Statement prepared and bound\n";
        
        if ($stmt->execute()) {
            echo "Delete executed successfully\n";
            echo "Affected rows: " . $stmt->affected_rows . "\n";
            $_SESSION['message'] = "User berhasil dihapus!";
        } else {
            echo "ERROR executing delete: " . $stmt->error . "\n";
            $_SESSION['message'] = "Error: " . $stmt->error;
        }
        $stmt->close();
        
        echo "\nAbout to redirect to users.php\n";
        echo "Session message set: " . $_SESSION['message'] . "\n";
        
        // Don't redirect for debugging
        echo "\n\n=== REDIRECT WOULD HAPPEN HERE ===\n";
        echo "header('Location: users.php');\n";
        echo "exit();\n";
        echo "\n<a href='users.php'>Click here to go back to users.php</a>\n";
        exit();
    }
}

// List users
echo "Listing users:\n";
$result = $conn->query("SELECT u.*, c.nama_cabang FROM users u LEFT JOIN cabang c ON u.cabang_id = c.cabang_id ORDER BY u.user_id DESC");

if (!$result) {
    echo "ERROR in query: " . $conn->error . "\n";
} else {
    echo "Query successful\n";
    echo "Number of rows: " . $result->num_rows . "\n\n";
    
    while ($row = $result->fetch_assoc()) {
        echo "User ID: " . $row['user_id'] . " - " . $row['username'] . " (" . $row['role'] . ")\n";
    }
}

$conn->close();
echo "\n</pre>";
?>

<h2>Test Delete Form</h2>
<form method="POST">
    <input type="hidden" name="action" value="delete">
    <label>User ID to delete: <input type="number" name="user_id" value=""></label>
    <button type="submit">Test Delete</button>
</form>
