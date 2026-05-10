<?php
session_start();  // Start session to store success/error messages
require_once 'db.php';  // Include database connection

header('Content-Type: application/json');  // Tell browser we're returning JSON data (not HTML)

// Get JSON data sent from checkout page
$data = json_decode(file_get_contents('php://input'), true);  
// file_get_contents('php://input') = reads raw POST data
// json_decode() = converts JSON string to PHP array
// true = return as associative array (not object)

// Validate that cart exists and is not empty
if(!$data || !isset($data['cart']) || empty($data['cart'])) {
    echo json_encode(['success' => false, 'message' => 'No items in cart']);
    exit;  // Stop execution
}

$cart = $data['cart'];
$out_of_stock = [];  // Array to store names of out-of-stock items

// START TRANSACTION - All database changes happen together or none happen
// If one query fails, everything gets rolled back (undo)
mysqli_begin_transaction($conn);

try {
    // STEP 1: Check stock for each item in cart
    foreach($cart as $item) {
        $book_name = mysqli_real_escape_string($conn, $item['name']);  // Prevents SQL injection
        $requested_qty = intval($item['qty']);  // Convert to integer
        
        // Get current stock from database
        $query = "SELECT id, stock, price FROM books WHERE title = '$book_name'";
        $result = mysqli_query($conn, $query);
        
        if(!$result) {
            throw new Exception("Error checking stock: " . mysqli_error($conn));  // Stop if error
        }
        
        $book = mysqli_fetch_assoc($result);  // Get book data
        
        if(!$book) {
            throw new Exception("Book '$book_name' not found");  // Book doesn't exist in DB
        }
        
        // If requested quantity exceeds available stock
        if($book['stock'] < $requested_qty) {
            $out_of_stock[] = "$book_name (Only {$book['stock']} left, you requested $requested_qty)";
        }
    }
    
    // STEP 2: If any item out of stock, rollback and show error
    if(!empty($out_of_stock)) {
        mysqli_rollback($conn);  // Undo any changes (nothing changed yet, but good practice)
        echo json_encode([
            'success' => false,
            'message' => 'Some items are out of stock',
            'out_of_stock_items' => $out_of_stock
        ]);
        exit;
    }
    
    // STEP 3: Generate unique order ID
    // Format: ORD-20260315-1234
    $order_id = 'ORD-' . date('Ymd') . '-' . rand(1000, 9999);  // Ymd = YearMonthDay
    
    // STEP 4: Get customer details from request data
    $customer_name = mysqli_real_escape_string($conn, $data['customer_name']);
    $customer_phone = mysqli_real_escape_string($conn, $data['customer_phone']);
    $customer_email = mysqli_real_escape_string($conn, $data['customer_email'] ?? '');  // ?? = if not set, use empty string
    $customer_address = mysqli_real_escape_string($conn, $data['customer_address'] ?? '');
    $payment_method = mysqli_real_escape_string($conn, $data['payment_method']);
    
    // STEP 5: Calculate total amounts
    $subtotal = 0;
    foreach($cart as $item) {
        $subtotal += $item['price'] * $item['qty'];
    }
    $shipping = 120;  // Fixed shipping charge
    $total_amount = $subtotal + $shipping;
    
    // STEP 6: Prepare order details as JSON (to store entire cart in one column)
    $order_details = json_encode($cart);
    
    // STEP 7: Insert order into database
    $insert_query = "INSERT INTO orders (
        order_id, 
        customer_name, 
        customer_phone, 
        customer_email, 
        customer_address, 
        payment_method, 
        order_details, 
        order_date,
        subtotal,
        shipping,
        total_amount
    ) VALUES (
        '$order_id',
        '$customer_name',
        '$customer_phone',
        '$customer_email',
        '$customer_address',
        '$payment_method',
        '$order_details',
        NOW(),  
        '$subtotal',
        '$shipping',
        '$total_amount'
    )";
    
    $insert_result = mysqli_query($conn, $insert_query);
    
    if(!$insert_result) {
        throw new Exception("Failed to save order: " . mysqli_error($conn));
    }
    
    // STEP 8: Update stock for each item (reduce by ordered quantity)
    foreach($cart as $item) {
        $book_name = mysqli_real_escape_string($conn, $item['name']);
        $requested_qty = intval($item['qty']);
        
        $update = "UPDATE books SET stock = stock - $requested_qty WHERE title = '$book_name'";
        $update_result = mysqli_query($conn, $update);
        
        if(!$update_result) {
            throw new Exception("Failed to update stock for: $book_name");
        }
    }
    
    // STEP 9: Commit transaction (save all changes permanently)
    mysqli_commit($conn);
    
    // STEP 10: Store success message in session to show on confirmation page
    $_SESSION['message'] = "Order placed successfully! Your Order ID is: $order_id";
    
    // Return success response to JavaScript
    echo json_encode([
        'success' => true, 
        'message' => 'Order placed successfully',
        'order_id' => $order_id
    ]);
    
} catch(Exception $e) {
    // If ANY error occurred, rollback ALL changes (undo everything)
    mysqli_rollback($conn);
    
    // Return error response
    echo json_encode([
        'success' => false, 
        'message' => $e->getMessage()  // Show error message
    ]);
}
?>
