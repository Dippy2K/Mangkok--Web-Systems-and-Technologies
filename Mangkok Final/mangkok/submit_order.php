<?php
// ----------------------------------------------------
// 1. DATABASE CONFIGURATION
// ----------------------------------------------------
$host = 'localhost';
$db   = 'mangkok_db'; 
$user = 'root';       
$pass = '';           
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

header('Content-Type: application/json'); 

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
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
    
    // Sanitize input
    $name = filter_var($_POST['customerName'] ?? '', FILTER_SANITIZE_STRING);
    $phone = filter_var($_POST['customerPhone'] ?? '', FILTER_SANITIZE_STRING);
    $paymentMethod = filter_var($_POST['paymentMethod'] ?? '', FILTER_SANITIZE_STRING); // <--- NEW FIELD
    $address = filter_var($_POST['deliveryAddress'] ?? '', FILTER_SANITIZE_STRING);
    $notes = filter_var($_POST['orderNotes'] ?? '', FILTER_SANITIZE_STRING);
    
    $cartDataJson = $_POST['cartData'] ?? '[]';
    
    $total = filter_var($_POST['orderTotal'] ?? 0.00, 
                        FILTER_SANITIZE_NUMBER_FLOAT, 
                        FILTER_FLAG_ALLOW_FRACTION);
    
    // Validation Checks
    if (empty($name) || empty($phone) || empty($address) || empty($paymentMethod) || $total <= 0) {
        echo json_encode([
            'success' => false, 
            'message' => "Validation failed: Missing customer details, payment method, or total is zero."
        ]);
        exit();
    }

    // SQL Statement (Added payment_method)
    $stmt = $pdo->prepare("
        INSERT INTO `orders` 
        (customer_name, customer_phone, payment_method, delivery_address, order_notes, cart_data, total_amount) 
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $success = $stmt->execute([
        $name,
        $phone,
        $paymentMethod, // <--- Insert Payment Method
        $address,
        $notes,
        $cartDataJson, 
        $total
    ]);
    
    if ($success) {
        echo json_encode([
            'success' => true, 
            'message' => "Order submitted successfully! ID: " . $pdo->lastInsertId()
        ]);
    } else {
        echo json_encode([
            'success' => false, 
            'message' => "Database error: Could not save the order."
        ]);
    }

} else {
    echo json_encode([
        'success' => false, 
        'message' => "Invalid request method."
    ]);
}
?>