<?php
session_start();  // Start session to access cart and messages
require_once 'db.php';  // Include database connection

// Check if the user is logged in and store true or false in $isLoggedIn
$isLoggedIn = isset($_SESSION['user_id']); 

// Check if book ID is provided in URL
if(isset($_GET['id'])){  // $_GET['id'] comes from URL like book_des.php?id=5
    $id = $_GET['id'];  // Get the book ID from URL
    $query = "SELECT * FROM books WHERE id = $id";  // SQL query to get specific book
    $result = mysqli_query($conn, $query);  // Execute query
    $book = mysqli_fetch_assoc($result);  // Convert result to array (single book)
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="https://cdn-icons-png.flaticon.com/512/29/29302.png" type="image/png">
  <link rel="stylesheet" href="book.css">
  <link rel="stylesheet" href="cart.css">
  <title><?php echo $book['title']; ?></title>
</head>

<body>

  <!-- ========== NAVIGATION BAR ========== -->
  <nav class="navbar">
    <div class="logo">
      <img src="image/logo.png" alt="Logo">
      <span>BookNest</span>
    </div>
    <ul class="nav-links">
      <li><a href="home.html">Home</a></li>
      <li><a href="book.php">Books</a></li>
      <li><a href="about.html">About Us</a></li>
      <li><a href="contact.html">Contact Us</a></li>
      <li><a href="login.html">Login</a></li>
    </ul>
    <div class="nav-icons">
      <img src="image/shopping-cart.png" alt="Cart" class="cart-icon" onclick="toggleCart()">
    </div>
  </nav>

  <!-- ========== BACK BUTTON ========== -->
  <div class="back">
    <a href="book.php">← Back to All Books</a>
  </div>

  <!-- ========== BOOK DETAIL SECTION ========== -->
  <section class="book-detail">
    <div class="detail-card">
      <!-- Left side: Book image -->
      <div class="detail-img">
        <img src="<?php echo $book['image']; ?>" alt="<?php echo $book['title']; ?>">
      </div>

      <!-- Right side: Book information -->
      <div class="detail-info">
        <h1><?php echo $book['title']; ?></h1>
        <p class="author">by <?php echo $book['author']; ?></p>

        <!-- Star Rating display -->
        <p class="rating">⭐ <?php echo $book['rating']; ?></p>

        <!-- Price -->
        <p class="price">Rs. <?php echo number_format($book['price']); ?></p>

        <!-- Book description -->
        <p class="desc"><?php echo $book['description']; ?></p>

        <!-- Quantity selector (Plus/Minus buttons) -->
        <div class="quantity">
          <button onclick="changeQuantity(-1)">-</button>
          <!-- Display current quantity (starts at 1) -->
          <span id="quantity">1</span>
          <button onclick="changeQuantity(1)">+</button>
        </div>

        <!-- Add to Cart Button -->
        <?php if($book['stock'] > 0): ?>
        <button class="add-btn" onclick="addToCart()">Add to Cart</button>
        <?php else: ?>
        <!-- Grayed out button if out of stock -->
        <button class="add-btn" disabled style="background: #ccc; cursor: not-allowed;">Out of Stock</button>
        <?php endif; ?>
        <!-- Warning message area (shows when user tries to exceed stock) -->
        <div id="stockWarning" class="stock-warning"></div>
      </div>
    </div>
  </section>

  <!-- ========== CART SIDEBAR ========== -->
  <div class="right" id="cartBox"
    style="position:fixed; right:20px; top:80px; background:#fff; padding:20px; width:300px; box-shadow:0 0 10px rgba(0,0,0,0.2); z-index:999; display: none;">
    <h3>Your Cart</h3>
    <div id="cartItems"></div>
    <hr>
    <div>Subtotal: Rs. <span id="subtotal">0</span></div>
    <div>Shipping: Rs. 120</div>
    <div style="font-weight: bold;">Total: Rs. <span id="total">0</span></div>
    <button onclick="goToCheckout()"
      style="margin-top:10px; width:100%; padding:10px; background:#0d2b4d; color:white; border:none; cursor: pointer;">Checkout</button>
  </div>
  <!-- Dark overlay behind cart -->
  <div id="overlay" onclick="toggleCart()"
    style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 998;">
  </div>

  <!-- ========== JAVASCRIPT ========== -->
  <script>
  // Take the PHP login status (true or false) and pass it into JavaScript, so make decision
  const isLoggedIn = <?php echo json_encode($isLoggedIn); ?>;

  // Book data passed from PHP to JavaScript
  const bookData = {
    id: <?php echo $book['id']; ?>, // Book ID from database
    name: "<?php echo addslashes($book['title']); ?>", // Title (addslashes prevents quotes from breaking code)
    price: <?php echo $book['price']; ?>,
    image: "<?php echo $book['image']; ?>",
    stock: <?php echo $book['stock']; ?>
  };

  let currentQuantity = 1; // Default quantity when page loads

  // Change quantity function - called by plus/minus buttons
  function changeQuantity(change) {
    let newQuantity = currentQuantity + change; // Add or subtract 1

    if (newQuantity < 1) {
      newQuantity = 1; // Can't go below 1
    }

    // Check if trying to order more than available stock
    if (newQuantity > bookData.stock) {
      document.getElementById('stockWarning').innerHTML = `⚠️ Only ${bookData.stock} copies available in stock!`;
      return; // Stop - don't allow
    } else {
      document.getElementById('stockWarning').innerHTML = ''; // Clear warning
    }

    currentQuantity = newQuantity; // Update current quantity
    document.getElementById('quantity').innerText = currentQuantity; // Update display
  }

  // Get cart from localStorage
  function getCart() {
    let cart = localStorage.getItem('cart');
    return cart ? JSON.parse(cart) : []; // Parse JSON or return empty array
  }

  // Save cart to localStorage
  function saveCart(cart) {
    localStorage.setItem('cart', JSON.stringify(cart));
  }

  // Add to cart function
  function addToCart() {

    // LOGIN CHECK 
    if (!isLoggedIn) {
      alert("Login to add to cart.");
      return;
    } // ends here

    // Check if enough stock available
    if (currentQuantity > bookData.stock) {
      alert(`Sorry! Only ${bookData.stock} copies available.`);
      return;
    }

    let cart = getCart();
    let existingItem = cart.find(item => item.name === bookData.name);

    if (existingItem) {
      // If book already in cart, check total quantity wouldn't exceed stock
      let newTotalQty = existingItem.qty + currentQuantity;
      if (newTotalQty > bookData.stock) {
        alert(
          `Sorry! You already have ${existingItem.qty} in cart. Only ${bookData.stock - existingItem.qty} more available.`
        );
        return;
      }
      existingItem.qty += currentQuantity; // Add to existing quantity
    } else {
      // Add new item to cart
      cart.push({
        name: bookData.name,
        price: bookData.price,
        image: bookData.image,
        qty: currentQuantity,
        maxStock: bookData.stock
      });
    }

    saveCart(cart); // Save to localStorage
    alert(`${currentQuantity} x "${bookData.name}" added to cart!`);

    // Reset quantity back to 1 for next selection
    currentQuantity = 1;
    document.getElementById('quantity').innerText = currentQuantity;

    loadCart(); // Refresh cart display
  }

  // Load cart display - same as book.php
  function loadCart() {
    let cart = getCart();
    let cartItemsDiv = document.getElementById('cartItems');
    if (!cartItemsDiv) return; // Exit if element doesn't exist

    let subtotal = 0;

    if (cart.length === 0) {
      cartItemsDiv.innerHTML = '<p>Your cart is empty</p>';
      document.getElementById('subtotal').innerText = '0';
      document.getElementById('total').innerText = '0';
      return;
    }

    cartItemsDiv.innerHTML = '';
    cart.forEach((item, index) => {
      subtotal += item.price * item.qty;
      cartItemsDiv.innerHTML += `
                <div style="margin-bottom: 15px; display: flex; gap: 10px;">
                    <img src="${item.image}" style="width: 50px; height: 60px; object-fit: cover;">
                    <div style="flex: 1;">
                        <p style="font-weight: bold;">${item.name}</p>
                        <div>
                            <button onclick="updateQuantity(${index}, -1)">-</button>
                            <span style="margin: 0 10px;">${item.qty}</span>
                            <button onclick="updateQuantity(${index}, 1)">+</button>
                        </div>
                        <p>Rs. ${item.price * item.qty}</p>
                    </div>
                </div>
            `;
    });

    document.getElementById('subtotal').innerText = subtotal;
    document.getElementById('total').innerText = subtotal + 120;
  }

  // Update quantity in cart
  function updateQuantity(index, change) {
    let cart = getCart();
    let newQty = cart[index].qty + change;

    if (newQty < 1) {
      cart.splice(index, 1); // Remove item if quantity becomes 0
    } else if (newQty > cart[index].maxStock) {
      alert(`Sorry! Only ${cart[index].maxStock} copies available.`);
      return;
    } else {
      cart[index].qty = newQty;
    }

    saveCart(cart);
    loadCart();
  }

  // Toggle cart visibility
  function toggleCart() {
    let cartBox = document.getElementById('cartBox');
    let overlay = document.getElementById('overlay');

    if (cartBox.style.display === 'none' || cartBox.style.display === '') {
      cartBox.style.display = 'block'; // Show cart
      overlay.style.display = 'block'; // Show overlay
      loadCart(); // Refresh cart contents
    } else {
      cartBox.style.display = 'none'; // Hide cart
      overlay.style.display = 'none'; // Hide overlay
    }
  }

  // Go to checkout page
  function goToCheckout() {
    let cart = getCart();
    if (cart.length === 0) {
      alert('Your cart is empty!');
      return;
    }
    window.location.href = 'checkout.php';
  }

  // Initialize when page loads - ensures cart starts hidden
  document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('cartBox')) {
      document.getElementById('cartBox').style.display = 'none'; // Start with cart closed
    }
  });
  </script>

  <!-- FOOTER -->
  <footer>
    <p>© 2026 BookNest. All rights reserved.</p>
  </footer>

</body>

</html>
