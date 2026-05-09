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

// SQL query to fetch only Crime category books from database
$query = "SELECT * FROM books WHERE category='Crime'"; // Change category to "Crime" for crime.php
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
  <title>Books - Crime</title>
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
    <!-- Page heading specific to Crime category -->
    <h1 class="title">Crime</h1>
    <!-- Subheading description for Crime category -->
    <p class="subtitle">Explore our Crime books collection</p>

    <!-- ========== CATEGORY FILTER BUTTONS ========== -->
    <div class="categories">
      <!-- All button - redirects to main books page showing all categories -->
      <button onclick="location.href='book.php'">All</button>
      <!-- Horror button - redirects to horror category page -->
      <button onclick="location.href='horror.php'">Horror</button>
      <!-- Crime button - active class highlights current page (Crime) -->
      <button class="active">Crime</button>
      <!-- Thriller button - redirects to thriller category page -->
      <button onclick="location.href='thrillersuspense.php'">Thriller & Suspense</button>
    </div>

    <!-- ========== SHOW SUCCESS/ERROR MESSAGE IF EXISTS ========== -->
    <?php if($message): ?>
    <div class="message <?php echo strpos($message, 'success') !== false ? 'success' : 'error'; ?>">
      <?php echo $message; ?>
    </div>
    <?php endif; ?>

    <!-- ========== BOOKS GRID - Display Crime Books ========== -->
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
    let cart = localStorage.getItem('cart');
    return cart ? JSON.parse(cart) : [];
  }

  // Save cart to localStorage
  function saveCart(cart) {
    localStorage.setItem('cart', JSON.stringify(cart));
  }

  // Add to cart function
  function addToCart(name, price, image, stock) {

    // LOGIN CHECK 
    if (!isLoggedIn) {
      alert("Login to add to cart.");
      return;
    } // ends here

    let cart = getCart();
    let existingItem = cart.find(item => item.name === name);

    if (existingItem) {
      if (existingItem.qty + 1 > stock) {
        alert(`Sorry! Only ${stock} copies available.`);
        return;
      }
      existingItem.qty++;
    } else {
      if (1 > stock) {
        alert(`Sorry! This book is out of stock.`);
        return;
      }
      cart.push({
        name: name,
        price: price,
        image: image,
        qty: 1,
        maxStock: stock
      });
    }

    saveCart(cart);
    alert(`${name} added to cart!`);
    loadCart();
  }

  // Load cart display
  function loadCart() {
    let cart = getCart();
    let cartItemsDiv = document.getElementById('cartItems');
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
      cart.splice(index, 1);
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
    document.getElementById('cartBox').classList.toggle('active');
    document.getElementById('overlay').classList.toggle('active');
    if (document.getElementById('cartBox').classList.contains('active')) {
      loadCart();
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

  // Clear all items from cart
  function clearCart() {
    localStorage.removeItem('cart');
    loadCart();
  }

  // Load cart when page loads
  loadCart();
  </script>

  <!-- ========== FOOTER ========== -->
  <footer>
    <p>© 2026 BookNest | All Rights Reserved</p>
  </footer>

</body>

</html>
