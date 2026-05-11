<?php 
session_start();    // Sessions are used to store user data after login

include 'db.php';

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// Takes data from HTML form using POST method
$email = $_POST['email'];
$password = $_POST['password'];


// Searches user by email in database
$stmt = $conn->prepare("SELECT id, fullname, password FROM users WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();


// If email found, it means user exists
if ($result->num_rows > 0){

    // Geting user data as array from database
    $row = $result->fetch_assoc();

    if(password_verify($password, $row['password'])){   // checking user entered password and password in database are same

        $_SESSION['user_id'] = $row['id'];
        $_SESSION['fullname'] = $row['fullname'];

        echo "<script>
            alert('Login successful!');
            window.location.href = 'home.html';
        </script>";
        exit();

    } 
    else{
        echo "<script>
            alert('Incorrect password!');
            window.location.href = 'login.html';
        </script>";
        exit();
    }

} 
else{
    echo "<script>
        alert('Email not registered!');
        window.location.href = 'login.html';
    </script>";
    exit();
}

$conn->close();   //Ends database connection
?>
