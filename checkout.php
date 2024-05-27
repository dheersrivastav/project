<?php
include 'config.php';

session_start();

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    header('location:login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

if (isset($_POST['order_btn'])) {

    // Sanitize user input to prevent SQL Injection
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $number = $_POST['number'];  // Assuming number input is safe
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $method = mysqli_real_escape_string($conn, $_POST['method']);
    $address = mysqli_real_escape_string($conn, 'flat no. ' . $_POST['flat'] . ', ' . $_POST['street'] . ', ' . $_POST['city'] . ', ' . $_POST['country'] . ' - ' . $_POST['pin_code']);
    $placed_on = date('d-M-Y');

    $cart_total = 0;
    $cart_products = [];

    // Retrieve user's cart items
    $cart_query = mysqli_query($conn, "SELECT * FROM `cart` WHERE user_id = '$user_id'") or die('query failed');
    if (mysqli_num_rows($cart_query) > 0) {
        while ($cart_item = mysqli_fetch_assoc($cart_query)) {
            $cart_products[] = $cart_item['name'] . ' (' . $cart_item['quantity'] . ') ';
            $sub_total = ($cart_item['price'] * $cart_item['quantity']);
            $cart_total += $sub_total;
        }
    }

    $total_products = implode(', ', $cart_products);

    // Check if the same order already exists
    $order_query = mysqli_query($conn, "SELECT * FROM `orders` WHERE name = '$name' AND number = '$number' AND email = '$email' AND method = '$method' AND address = '$address' AND total_products = '$total_products' AND total_price = '$cart_total'") or die('query failed');

    if ($cart_total == 0) {
        $message[] = 'Your cart is empty';
    } else {
        if (mysqli_num_rows($order_query) > 0) {
            $message[] = 'Order already placed!';
        } else {
            // Insert the order into the database
            $insert_order_query = "INSERT INTO `orders`(user_id, name, number, email, method, address, total_products, total_price, placed_on) VALUES(?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($insert_order_query);
            $stmt->bind_param("issssssds", $user_id, $name, $number, $email, $method, $address, $total_products, $cart_total, $placed_on);
            if ($stmt->execute()) {
                $message[] = 'Order placed successfully!';
                // Clear the cart
                mysqli_query($conn, "DELETE FROM `cart` WHERE user_id = '$user_id'") or die('query failed');
                // Store order details in session for receipt
                $_SESSION['order_details'] = [
                    'name' => $name,
                    'number' => $number,
                    'email' => $email,
                    'method' => $method,
                    'address' => $address,
                    'total_products' => $total_products,
                    'total_price' => $cart_total,
                    'placed_on' => $placed_on
                ];
            } else {
                $message[] = 'Failed to place order. Please try again.';
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .receipt {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            background-color: #f9f9f9;
            border-radius: 5px;
        }
        .receipt h3 {
            margin-bottom: 20px;
            font-size: 24px;
            color: #333;
        }
        .receipt p {
            font-size: 18px;
            margin: 10px 0;
        }
        .btn-print {
            display: inline-block;
            margin-top: 10px;
            padding: 10px 20px;
            background-color: #333;
            color: #fff;
            border: none;
            cursor: pointer;
            border-radius: 5px;
        }
    </style>
</head>
<body>
<?php include 'header.php'; ?>

<div class="heading">
    <h3>Checkout</h3>
    <p> <a href="index.php">Home</a> / Checkout </p>
</div>

<section class="display-order">
    <?php  
    $grand_total = 0;
    $select_cart = mysqli_query($conn, "SELECT * FROM `cart` WHERE user_id = '$user_id'") or die('query failed');
    if (mysqli_num_rows($select_cart) > 0) {
        while ($fetch_cart = mysqli_fetch_assoc($select_cart)) {
            $total_price = ($fetch_cart['price'] * $fetch_cart['quantity']);
            $grand_total += $total_price;
            ?>
            <p> <?php echo $fetch_cart['name']; ?> <span>(<?php echo '$' . $fetch_cart['price'] . '/-' . ' x ' . $fetch_cart['quantity']; ?>)</span> </p>
            <?php
        }
    } else {
        echo '<p class="empty">Your cart is empty</p>';
    }
    ?>
    <div class="grand-total"> Grand Total : <span>$<?php echo $grand_total; ?>/-</span> </div>
</section>

<section class="checkout">
    <form action="" method="post">
        <h3>Place Your Order</h3>
        <div class="flex">
            <div class="inputBox">
                <span>Your Name :</span>
                <input type="text" name="name" required placeholder="Enter your name">
            </div>
            <div class="inputBox">
                <span>Your Number :</span>
                <input type="number" name="number" required placeholder="Enter your number">
            </div>
            <div class="inputBox">
                <span>Your Email :</span>
                <input type="email" name="email" required placeholder="Enter your email">
            </div>
            <div class="inputBox">
                <span>Payment Method :</span>
                <select name="method">
                    <option  value="cash on delivery">Cash on Delivery</option>
                    <option value="credit card">Credit Card</option>
                    <option value="paypal">PayPal</option>
                    <option value="paytm">Paytm</option>
                </select>
            </div>
            <div class="inputBox">
                <span>Address Line 01 :</span>
                <input type="number" min="0" name="flat" required placeholder="e.g. Flat No.">
            </div>
            <div class="inputBox">
                <span>Address Line 02 :</span>
                <input type="text" name="street" required placeholder="e.g. Street Name">
            </div>
            <div class="inputBox">
                <span>City :</span>
                <input type="text" name="city" required placeholder="e.g. Mumbai">
            </div>
            <div class="inputBox">
                <span>State :</span>
                <input type="text" name="state" required placeholder="e.g. Maharashtra">
            </div>
            <div class="inputBox">
                <span>Country :</span>
                <input type="text" name="country" required placeholder="e.g. India">
            </div>
            <div class="inputBox">
                <span>Pin Code :</span>
                <input type="number" min="0" name="pin_code" required placeholder="e.g. 123456">
            </div>
        </div>
        <input type="submit" value="Order Now" class="btn" name="order_btn">
        <?php if (isset($_SESSION['order_details'])): ?>
            <button class="btn-print" onclick="printReceipt()">Print Receipt</button>
            <button class="btn-print" onclick="downloadReceipt()">Download Receipt</button>
        <?php endif; ?>
    </form>
</section>

<?php if (isset($_SESSION['order_details'])): ?>
<section class="receipt">
    <h3>Your Receipt</h3>
    <p>Name: <?php echo $_SESSION['order_details']['name']; ?></p>
    <p>Number: <?php echo $_SESSION['order_details']['number']; ?></p>
    <p>Email: <?php echo $_SESSION['order_details']['email']; ?></p>
    <p>Method: <?php echo $_SESSION['order_details']['method']; ?></p>
    <p>Address: <?php echo $_SESSION['order_details']['address']; ?></p>
    <p>Total Products: <?php echo $_SESSION['order_details']['total_products']; ?></p>
    <p>Total Price: $<?php echo $_SESSION['order_details']['total_price']; ?>/-</p>
    <p>Placed On: <?php echo $_SESSION['order_details']['placed_on']; ?></p>
</section>
<?php endif; ?>

<?php include 'footer.php'; ?>

<script src="js/script.js"></script>
<script>
function printReceipt() {
    const printContent = document.querySelector('.receipt').innerHTML;
    const originalContent = document.body.innerHTML;
    document.body.innerHTML = printContent;
    window.print();
    document.body.innerHTML = originalContent;
    window.location.reload();
}

function downloadReceipt() {
    const element = document.createElement('a');
    const receipt = document.querySelector('.receipt').innerHTML;
    const blob = new Blob([receipt], {type: 'text/html'});
    const url = URL.createObjectURL(blob);
    element.href = url;
    element.download = 'receipt.html';
    document.body.appendChild(element);
    element.click();
    document.body.removeChild(element);
}
</script>
</body>
</html>

