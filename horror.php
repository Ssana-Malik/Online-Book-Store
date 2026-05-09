<?php
session_start();  // Start session to store temporary data like messages and cart
require_once 'db.php';  // Include database connection file

// Check if the user is logged in and store true or false in $isLoggedIn
$isLoggedIn = isset($_SESSION['user_id']);  

// Display message if any (success/error after adding to cart)
$message = '';
if(isset($_SESSION['message'])) {
    $message = $_SESSION['message'];  // Get message from session
    unset($_SESSION['message']);  // Remove message after reading (so it doesn't show again)
}

// SQL query to fetch only Horror category books from database
$query = "SELECT * FROM books WHERE category='Horror'"; // Change category to "Horror" for horror.php
$result = mysqli_query($conn, $query);  // Execute the query
$books = mysqli_fetch_all($result, MYSQLI_ASSOC);  // Convert result into array of books
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="https://cdn-icons-png.flaticon.com/512/29/29302.png" type="image/png">
  <link rel="stylesheet" href="book.css">
  <link rel="stylesheet" href="cart.css">
  <title>Books - Horror</title>
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
    <img src="image/shopping-cart.png" alt="Cart" class="cart-icon" onclick="toggleCart()">
  </nav>

  <!-- ========== MAIN CONTENT CONTAINER ========== -->
  <div class="container">
    <!-- Page heading specific to Horror category -->
    <h1 class="title">Horror</h1>
    <!-- Subheading description for Horror category -->
    <p class="subtitle">Explore our Horror books collection</p>

    <!-- ========== CATEGORY FILTER BUTTONS ========== -->
    <div class="categories">
      <!-- All button - redirects to main books page showing all categories -->
      <button onclick="location.href='book.php'">All</button>
      <!-- Horror button - active class highlights current page (Horror) -->
      <button class="active">Horror</button>
      <!-- Crime button - redirects to crime category page -->
      <button onclick="location.href='crime.php'">Crime</button>
      <!-- Thriller button - redirects to thriller category page -->
      <button onclick="location.href='thrillersuspense.php'">Thriller & Suspense</button>
    </div>

    <!-- ========== SHOW SUCCESS/ERROR MESSAGE IF EXISTS ========== -->
    <?php if($message): ?>
    <div class="message <?php echo strpos($message, 'success') !== false ? 'success' : 'error'; ?>">
      <?php echo $message; ?>
    </div>
    <?php endif; ?>

    <!-- ========== BOOKS GRID - Display Horror Books ========== -->
    <div class="books">
      <!-- Loop through each book in database -->
      <?php foreach($books as $book): ?>
      <!-- Each book card, "data-id" stores book ID -->
      <div class="card" data-id="<?php echo $book['id']; ?>">
        <!-- Click image to go to book details page, passing book ID in URL -->
        <a href="book_des.php?id=<?php echo $book['id']; ?>">
          <!-- Book cover image -->
          <img src="<?php echo $book['image']; ?>" alt="<?php echo $book['title']; ?>">
        </a>
        <h3><?php echo $book['title']; ?></h3>
        <p><?php echo $book['author']; ?></p>
        <!-- Price (formatted with commas) -->
        <p class="price">Rs. <?php echo number_format($book['price']); ?></p>

        <!-- If book is in stock -->
        <?php if($book['stock'] > 0): ?>
        <!-- Add to cart button - passes title, price, image, stock to JavaScript function -->
        <button class="add-btn"
          onclick="addToCart('<?php echo addslashes($book['title']); ?>', <?php echo $book['price']; ?>, '<?php echo $book['image']; ?>', <?php echo $book['stock']; ?>)">Add
          to Cart</button>
        <!-- If book is out of stock -->
        <?php else: ?>
        <!-- Disable add to cart button and show "Out of Stock" message -->
        <button class="add-btn" disabled>Out of Stock</button>
        <?php endif; ?>
      </div>
      <?php endforeach; ?>
    </div>
  </div>

  <!-- ========== CART SIDEBAR ========== -->
  <!-- Pops up from right side when cart icon is clicked -->
  <div id="cartBox">
    <h3>Your Cart</h3>
    <!-- dynamic content - items will be added here by JavaScript -->
    <div id="cartItems"></div>
    <hr>
    <!-- subtotal before shipping -->
    <div>Subtotal: Rs. <span id="subtotal">0</span></div>
    <!-- fixed shipping charge -->
    <div>Shipping: Rs. 120</div>
    <!-- grand total -->
    <div style="font-weight: bold;">Total: Rs. <span id="total">0</span></div>
    <!-- Checkout button - redirects to checkout page -->
    <button onclick="goToCheckout()"
      style="margin-top: 10px; width: 100%; padding: 10px; background: #0d2b4d; color: white; border: none; cursor: pointer;">Checkout</button>
  </div>
  <!-- Dark overlay behind cart - click to close cart -->
  <div id="overlay" onclick="toggleCart()"></div>

  <!-- ========== JAVASCRIPT FOR CART FUNCTIONALITY ========== -->
  <script>
  // Take the PHP login status (true or false) and pass it into JavaScript, so make decision
  const isLoggedIn = <?php echo json_encode($isLoggedIn); ?>;

  // Get cart from localStorage - localStorage stores data even after page refresh
  function getCart() {
    let cart = localStorage.getItem('cart'); // Try to get cart data from browser storage
    return cart ? JSON.parse(cart) : []; // If exists, convert from JSON to array, else return empty array
  }

  // Save cart to localStorage - stores cart data in browser
  function saveCart(cart) {
    localStorage.setItem('cart', JSON.stringify(cart)); // Convert array to JSON string and save
  }

  // Add to cart function - triggered when user clicks "Add to Cart"
  function addToCart(name, price, image, stock) {

    // LOGIN CHECK 
    if (!isLoggedIn) {
      alert("Login to add to cart.");
      return;
    } // ends here

    let cart = getCart(); // Get current cart
    let existingItem = cart.find(item => item.name === name); // Check if book already in cart

    if (existingItem) {
      // If book already in cart, check if adding more exceeds stock
      if (existingItem.qty + 1 > stock) {
        alert(`Sorry! Only ${stock} copies available.`); // Show error alert
        return; // Stop function
      }
      existingItem.qty++; // Increase quantity by 1
    } else {
      // If book not in cart, check if at least 1 copy available
      if (1 > stock) {
        alert(`Sorry! This book is out of stock.`);
        return;
      }
      cart.push({ // Add new item to cart array
        name: name,
        price: price,
        image: image,
        qty: 1, // Quantity = 1
        maxStock: stock // Store max stock to check limits later
      });
    }

    saveCart(cart); // Save updated cart to localStorage
    alert(`${name} added to cart!`); // Show success message
    loadCart(); // Refresh cart display
  }

  // Load cart display - shows all items in cart sidebar
  function loadCart() {
    let cart = getCart();
    let cartItemsDiv = document.getElementById('cartItems');
    let subtotal = 0;

    if (cart.length === 0) {
      cartItemsDiv.innerHTML = '<p>Your cart is empty</p>'; // Show empty message
      document.getElementById('subtotal').innerText = '0';
      document.getElementById('total').innerText = '0';
      return;
    }

    cartItemsDiv.innerHTML = ''; // Clear existing items
    cart.forEach((item, index) => { // Loop through each cart item
      subtotal += item.price * item.qty; // Calculate subtotal
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

    document.getElementById('subtotal').innerText = subtotal; // Update subtotal display
    document.getElementById('total').innerText = subtotal + 120; // Total = subtotal + shipping
  }

  // Update quantity in cart - called when +/- buttons are clicked
  function updateQuantity(index, change) {
    let cart = getCart();
    let newQty = cart[index].qty + change; // Calculate new quantity

    if (newQty < 1) {
      cart.splice(index, 1); // If quantity becomes 0, remove item from cart
    } else if (newQty > cart[index].maxStock) {
      alert(`Sorry! Only ${cart[index].maxStock} copies available.`); // Stock limit check
      return;
    } else {
      cart[index].qty = newQty; // Update quantity
    }

    saveCart(cart); // Save updated cart
    loadCart(); // Refresh display
  }

  // Toggle cart - opens/closes the cart sidebar
  function toggleCart() {
    document.getElementById('cartBox').classList.toggle('active'); // Toggle active class (shows/hides)
    document.getElementById('overlay').classList.toggle('active'); // Toggle overlay
    if (document.getElementById('cartBox').classList.contains('active')) {
      loadCart(); // Load cart items when opening
    }
  }

  // Go to checkout - redirects to checkout page
  function goToCheckout() {
    let cart = getCart();
    if (cart.length === 0) {
      alert('Your cart is empty!'); // Don't allow empty checkout
      return;
    }
    window.location.href = 'checkout.php'; // Redirect to checkout page
  }

  // Clear cart function - removes all items (not used in button, but available)
  function clearCart() {
    localStorage.removeItem('cart'); // Delete cart from storage
    loadCart(); // Refresh display
  }

  // Load cart on page load - shows cart if it contained items from previous session
  loadCart();
  </script>

  <!-- ========== FOOTER ========== -->
  <footer>
    <p>© 2026 BookNest | All Rights Reserved</p>
  </footer>

</body>

</html>
