<?php
include 'db.php';
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Takes data from HTML form using POST method

$fullname = $_POST['fullname'];
$email = $_POST['email'];
$password = $_POST['password'];
$confirm_password = $_POST['confirm_password'];

// Check both passwords match
if ($password !== $confirm_password) {
    echo "<script>
        alert('Passwords do not match!');
        window.location.href='signup.html';
    </script>";
    exit();   // stops script
}

// Check if email already exists
$check = $conn->prepare("SELECT id FROM users WHERE email = ?");
$check->bind_param("s", $email);  // Put the email (as a string) into the SQL query where ? is written.
$check->execute();
$check->store_result();

// If email already exists, Shows alert and stops signup
if ($check->num_rows > 0) {
    echo "<script>
        alert('Email already exists!');
        window.location.href='signup.html';
    </script>";
    exit();
}

// Converts password into encrypted form
$hashed_password = password_hash($password, PASSWORD_DEFAULT);

// Insert User into Database
$stmt = $conn->prepare("INSERT INTO users (fullname, email, password) VALUES (?, ?, ?)");
$stmt->bind_param("sss", $fullname, $email, $hashed_password);


// If insertion is successful, show signup sucessful message and move to login page
if ($stmt->execute()){
    echo "<script>
        alert('Signup successful!');
        window.location.href='login.html';
    </script>";
} 
else{
    echo "Error: " . $conn->error;   // Shows database error
}

$conn->close();   //Ends database connection
?>
