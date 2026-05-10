<?php
session_start();
require_once 'db.php';

// Check if user is logged in (optional - add your own authentication)
// For now, we'll just show orders

$query = "SELECT * FROM orders ORDER BY order_date DESC";
$result = mysqli_query($conn, $query);
$orders = mysqli_fetch_all($result, MYSQLI_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="icon" type="image/x-icon" href="image/logo.png">
  <title>View Orders - BookNest</title>
  <style>
  body {
    display: flex;
    flex-direction: column;
    min-height: 100vh;
  }

  * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: Arial, sans-serif;
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

  /* ICONS */
  .nav-icons {
    display: flex;
    align-items: center;
  }

  .cart-icon {
    width: 25px;
    height: 25px;
    cursor: pointer;
  }

  .container {
    max-width: 1200px;
    margin: 50px auto;
    padding: 0 20px;
  }

  .title {
    text-align: center;
    margin-bottom: 30px;
    color: #0d2b4d;
  }

  .orders-table {
    background: white;
    border-radius: 10px;
    overflow: hidden;
    box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
  }

  table {
    width: 100%;
    border-collapse: collapse;
  }

  th {
    background: #0d2b4d;
    color: white;
    padding: 12px;
    text-align: left;
  }

  td {
    padding: 12px;
    border-bottom: 1px solid #ddd;
  }

  tr:hover {
    background: #f5f5f5;
  }

  .order-details {
    cursor: pointer;
    color: #0d2b4d;
    text-decoration: underline;
  }

  .modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
  }

  .modal-content {
    background: white;
    margin: 10% auto;
    padding: 20px;
    width: 80%;
    max-width: 600px;
    border-radius: 10px;
    max-height: 80%;
    overflow-y: auto;
  }

  .close {
    float: right;
    cursor: pointer;
    font-size: 28px;
    font-weight: bold;
  }

  .close:hover {
    color: red;
  }

  .status-pending {
    color: orange;
    font-weight: bold;
  }

  .status-completed {
    color: green;
    font-weight: bold;
  }

  /* FOOTER */
  footer {
    background: #111;
    color: white;
    text-align: center;
    padding: 15px;
    margin-top: auto;
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
      <li><a href="view_orders.php">Orders</a></li>
      <li><a href="login.html">Login</a></li> <!-- Added login as link -->
    </ul>
    <img src="image/shopping-cart.png" alt="Cart" class="cart-icon" onclick="toggleCart()">
  </nav>

  <div class="container">
    <h1 class="title">All Orders</h1>

    <?php if(empty($orders)): ?>
    <p style="text-align: center;">No orders found.</p>
    <?php else: ?>
    <div class="orders-table">
      <table>
        <thead>
          <tr>
            <th>Order ID</th>
            <th>Customer Name</th>
            <th>Phone</th>
            <th>Total Amount</th>
            <th>Payment Method</th>
            <th>Order Date</th>
            <th>Status</th>
            <th>Action</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach($orders as $order): ?>
          <tr>
            <td><?php echo $order['order_id']; ?></td>
            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
            <td><?php echo $order['customer_phone']; ?></td>
            <td>Rs. <?php echo number_format($order['total_amount']); ?></td>
            <td><?php echo str_replace('_', ' ', ucfirst($order['payment_method'])); ?></td>
            <td><?php echo date('d M Y, h:i A', strtotime($order['order_date'])); ?></td>
            <td class="status-<?php echo $order['order_status']; ?>">
              <?php echo ucfirst($order['order_status']); ?>
            </td>
            <td>
              <button class="order-details" onclick="showDetails(<?php echo htmlspecialchars(json_encode($order)); ?>)">
                View Details
              </button>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

  <!-- Modal for order details -->
  <div id="orderModal" class="modal">
    <div class="modal-content">
      <span class="close" onclick="closeModal()">&times;</span>
      <h2>Order Details</h2>
      <div id="modalContent"></div>
    </div>
  </div>

  <script>
  function showDetails(order) {
    let modal = document.getElementById('orderModal');
    let modalContent = document.getElementById('modalContent');

    let orderItems = JSON.parse(order.order_details);
    let itemsHtml = '<h3>Items:</h3><ul>';
    orderItems.forEach(item => {
      itemsHtml += `<li>${item.name} - Quantity: ${item.qty} - Price: Rs. ${item.price * item.qty}</li>`;
    });
    itemsHtml += '</ul>';

    modalContent.innerHTML = `
        <p><strong>Order ID:</strong> ${order.order_id}</p>
        <p><strong>Customer:</strong> ${order.customer_name}</p>
        <p><strong>Phone:</strong> ${order.customer_phone}</p>
        <p><strong>Email:</strong> ${order.customer_email || 'N/A'}</p>
        <p><strong>Address:</strong> ${order.customer_address || 'N/A'}</p>
        <p><strong>Payment Method:</strong> ${order.payment_method.replace('_', ' ')}</p>
        ${itemsHtml}
        <p><strong>Subtotal:</strong> Rs. ${order.subtotal}</p>
        <p><strong>Shipping:</strong> Rs. ${order.shipping}</p>
        <p><strong>Total:</strong> Rs. ${order.total_amount}</p>
        <p><strong>Order Date:</strong> ${new Date(order.order_date).toLocaleString()}</p>
        <p><strong>Status:</strong> ${order.order_status}</p>
    `;

    modal.style.display = 'block';
  }

  function closeModal() {
    document.getElementById('orderModal').style.display = 'none';
  }

  // Close modal when clicking outside
  window.onclick = function(event) {
    let modal = document.getElementById('orderModal');
    if (event.target == modal) {
      modal.style.display = 'none';
    }
  }
  </script>

  <footer>
    <p>© 2026 BookNest | All Rights Reserved</p>
  </footer>

</body>

</html>