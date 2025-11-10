<?php
// ----------------------------------------------------
// 1. DATABASE CONFIGURATION (CUSTOMIZE THESE LINES)
// ----------------------------------------------------
$host = 'localhost';
$db   = 'mangkok_db'; // <<< CHANGE THIS to the name of your database
$user = 'root';      // <<< CHANGE THIS if you set a MySQL username
$pass = '';          // <<< CHANGE THIS if you set a MySQL password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Set header to return JSON, as expected by the JavaScript fetch()
header('Content-Type: application/json'); 

try {
    // Attempt to connect to the database
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // If connection fails (e.g., MySQL is not running or wrong credentials)
    echo json_encode([
        'success' => false, 
        'message' => "Error connecting to database. Please check XAMPP services."
    ]);
    exit();
}

// ----------------------------------------------------
// 2. SERVER-SIDE VALIDATION AND INSERTION
// ----------------------------------------------------

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Sanitize and validate required input from the AJAX form data
    $name = filter_var($_POST['customerName'] ?? '', FILTER_SANITIZE_STRING);
    $phone = filter_var($_POST['customerPhone'] ?? '', FILTER_SANITIZE_STRING);
    $address = filter_var($_POST['deliveryAddress'] ?? '', FILTER_SANITIZE_STRING);
    $notes = filter_var($_POST['orderNotes'] ?? '', FILTER_SANITIZE_STRING);
    
    // Cart data is sent as a JSON string
    $cartDataJson = $_POST['cartData'] ?? '[]';
    
    // Total price validation
    $total = filter_var($_POST['orderTotal'] ?? 0.00, 
                        FILTER_SANITIZE_NUMBER_FLOAT, 
                        FILTER_FLAG_ALLOW_FRACTION);
    
    // --- Server-Side Validation Checks ---
    if (empty($name) || empty($phone) || empty($address) || $total <= 0) {
        echo json_encode([
            'success' => false, 
            'message' => "Validation failed: Missing customer details or total is zero."
        ]);
        exit();
    }
    // --- End Validation Checks ---

    // Prepare SQL statement to insert the order
    $stmt = $pdo->prepare("
        INSERT INTO `orders` 
        (customer_name, customer_phone, delivery_address, order_notes, cart_data, total_amount) 
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    
    // Execute the statement
    $success = $stmt->execute([
        $name,
        $phone,
        $address,
        $notes,
        $cartDataJson, // Stored as JSON data type in MySQL
        $total
    ]);
    
    if ($success) {
        // Return success message back to JavaScript
        echo json_encode([
            'success' => true, 
            'message' => "Order submitted successfully! ID: " . $pdo->lastInsertId()
        ]);
    } else {
        // Return database error
        echo json_encode([
            'success' => false, 
            'message' => "Database error: Could not save the order."
        ]);
    }

} else {
    // Handle cases where the script is accessed directly (not via POST)
    echo json_encode([
        'success' => false, 
        'message' => "Invalid request method."
    ]);
}
?>