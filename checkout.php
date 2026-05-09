<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" href="https://cdn-icons-png.flaticon.com/512/29/29302.png" type="image/png">
  <title>Checkout - BookNest</title>
  <style>
  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
  }

  body {
    font-family: Arial, sans-serif;
    background: #f5f5f5;
  }

  /* NAVBAR */
  .navbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 50px;
    background: #2c3e50;
    color: white;
  }

  .logo {
    display: flex;
    align-items: center;
    gap: 10px;
  }

  .logo img {
    width: 40px;
    height: 40px;
  }

  .logo span {
    font-size: 22px;
    font-weight: bold;
  }

  .nav-links {
    display: flex;
    list-style: none;
    width: 120vh;
  }

  .nav-links li {
    margin: 0 15px;
  }

  .nav-links a {
    color: white;
    text-decoration: none;
    transition: 0.3s;
  }

  .nav-links a:hover {
    color: orange;
  }

  .container {
    display: flex;
    max-width: 1200px;
    margin: 50px auto;
    gap: 30px;
    padding: 0 20px;
  }

  .left {
    flex: 2;
    background: white;
    padding: 30px;
    border-radius: 10px;
  }

  .right {
    flex: 1;
    background: white;
    padding: 20px;
    border-radius: 10px;
    position: sticky;
    top: 20px;
    height: fit-content;
  }

  input,
  select {
    width: 100%;
    padding: 10px;
    margin: 10px 0;
    border: 1px solid #ddd;
    border-radius: 5px;
  }

  .payment-options {
    margin: 10px 0;
  }

  .payment-options label {
    display: block;
    margin: 10px 0;
  }

  .place-order-btn {
    background: #0d2b4d;
    color: white;
    padding: 15px;
    border: none;
    border-radius: 5px;
    cursor: pointer;
    width: 100%;
    font-size: 16px;
    margin-top: 20px;
  }

  .place-order-btn:hover {
    background: #1a3d66;
  }

  .cart-items {
    margin: 15px 0;
  }

  .cart-item {
    display: flex;
    gap: 10px;
    margin-bottom: 15px;
    padding-bottom: 15px;
    border-bottom: 1px solid #eee;
  }

  footer {
    background: #0d2b4d;
    color: white;
    text-align: center;
    padding: 20px;
    margin-top: 50px;
  }

  .message {
    padding: 10px;
    margin: 20px;
    border-radius: 5px;
    text-align: center;
  }

  .message.error {
    background: #f8d7da;
    color: #721c24;
  }

  .message.success {
    background: #d4edda;
    color: #00b303;
  }

  .cart-icon {
    width: 30px;
    cursor: pointer;
  }
  </style>
</head>

<body>

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
      <li><a href="login.html">Login</a></li> <!-- Added login as link -->
    </ul>
  </nav>

  <div class="container">
    <div class="left">
      <h2>Customer Details</h2>
      <form id="checkoutForm">
        <input type="text" id="name" placeholder="Full Name" required>
        <input type="text" id="phone" placeholder="Phone Number" required>
        <input type="email" id="email" placeholder="Email" required>
        <input type="text" id="address" placeholder="Delivery Address" required>
        <h3 style="margin-top: 20px;">Payment Method</h3>
        <div class="payment-options">
          <label>
            <input type="radio" name="payment" value="cash_on_delivery" checked>
            Cash on Delivery
          </label>
          <label>
            <input type="radio" name="payment" value="card">
            Credit/Debit Card
          </label>
          <label>
            <input type="radio" name="payment" value="easypaisa">
            Easypaisa
          </label>
        </div>

        <button type="button" class="place-order-btn" onclick="placeOrder()">Place Order</button>
      </form>
    </div>

    <div class="right">
      <h3>Order Summary</h3>
      <div id="cartSummary" class="cart-items"></div>
      <hr>
      <div style="margin: 10px 0;">Subtotal: Rs. <span id="subtotal">0</span></div>
      <div style="margin: 10px 0;">Shipping: Rs. 120</div>
      <div style="font-size: 20px; font-weight: bold; margin-top: 10px;">Total: Rs. <span id="total">0</span></div>
    </div>
  </div>

  <script>
  // Get cart from localStorage
  function getCart() {
    let cart = localStorage.getItem('cart');
    return cart ? JSON.parse(cart) : [];
  }

  // Load order summary
  function loadOrderSummary() {
    let cart = getCart();
    let cartSummary = document.getElementById('cartSummary');
    let subtotal = 0;

    if (cart.length === 0) {
      cartSummary.innerHTML = '<p>Your cart is empty</p>';
      window.location.href = 'book.php';
      return;
    }

    cartSummary.innerHTML = '';
    cart.forEach(item => {
      subtotal += item.price * item.qty;
      cartSummary.innerHTML += `
            <div class="cart-item">
                <img src="${item.image}" style="width: 60px; height: 70px; object-fit: cover;">
                <div>
                    <p><strong>${item.name}</strong></p>
                    <p>Quantity: ${item.qty}</p>
                    <p>Rs. ${item.price * item.qty}</p>
                </div>
            </div>
        `;
    });

    document.getElementById('subtotal').innerText = subtotal;
    document.getElementById('total').innerText = subtotal + 120;
  }

  // Place order function
  function placeOrder() {
    let name = document.getElementById('name').value;
    let phone = document.getElementById('phone').value;

    if (!name || !phone) {
      alert('Please fill in Name and Phone Number!');
      return;
    }

    let cart = getCart();
    if (cart.length === 0) {
      alert('Your cart is empty!');
      window.location.href = 'book.php';
      return;
    }

    let paymentMethod = document.querySelector('input[name="payment"]:checked').value;

    // Prepare order data
    let orderData = {
      customer_name: name,
      customer_phone: phone,
      customer_email: document.getElementById('email').value,
      customer_address: document.getElementById('address').value,
      payment_method: paymentMethod,
      cart: cart
    };

    // Send to server
    fetch('process_order.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify(orderData)
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Order placed successfully!');
          localStorage.removeItem('cart');
          window.location.href = 'book.php';
        } else {
          alert(data.message);
          if (data.out_of_stock_items) {
            alert('Out of stock: ' + data.out_of_stock_items.join('\n'));
          }
          window.location.href = 'book.php';
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Error placing order. Please try again.');
      });
  }

  // Load summary on page load
  loadOrderSummary();
  </script>

  <footer>
    <p>© 2026 BookNest | All Rights Reserved</p>
  </footer>

</body>

</html>