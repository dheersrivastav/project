<?php
include 'config.php';

session_start();

$user_id = $_SESSION['user_id'];

if(!isset($user_id)){
   header('location:login.php');
}

if(isset($_POST['add to sell'])){
   $book_id = $_POST['book_id'];
   $book_quantity = $_POST['book_quantity'];

   // Fetch book details from the database
   $select_book = mysqli_query($conn, "SELECT * FROM `books` WHERE id = '$book_id'") or die('query failed');
   $book_row = mysqli_fetch_assoc($select_book);

   // Check if the book is already in the cart
   $check_cart = mysqli_query($conn, "SELECT * FROM `cart` WHERE id = '$book_id' AND user_id = '$user_id'") or die('query failed');

   if(mysqli_num_rows($check_cart) > 0){
      $message[] = 'Book already added to  sell cart!';
   } else {
      // Add the book to the cart
      mysqli_query($conn, "INSERT INTO `cart`(user_id, id, name, price, quantity, image) VALUES('$user_id', '$book_id', '{$book_row['title']}', '{$book_row['price']}', '$book_quantity', '{$book_row['image']}')") or die('query failed');
      $message[] = 'Book added to  sell cart!';
   }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
   <meta charset="UTF-8">
   <meta http-equiv="X-UA-Compatible" content="IE=edge">
   <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <title>Sell Store</title>

   <!-- font awesome cdn link -->
   <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

   <!-- custom css file link -->
   <link rel="stylesheet" href="css/style.css">
</head>
<body>
   
<?php include 'header.php'; ?>

<div class="heading">
   <h3>Our Sell Store</h3>
   <p> <a href="index.php">home</a> / sell </p>
</div>

<section class="products">
   <h1 class="title">Latest Books To Sell</h1>
   <div class="box-container">
      <?php  
         // Fetch books from the database
         $select_books = mysqli_query($conn, "SELECT * FROM `books`") or die('query failed');
         if(mysqli_num_rows($select_books) > 0){
            while($fetch_book = mysqli_fetch_assoc($select_books)){
      ?>
     <form action="" method="post" class="box">
      <img class="image" src="uploaded_img/<?php echo $fetch_book['image']; ?>" alt="">
      <div class="name"><?php echo $fetch_book['title']; ?></div>
      <div class="author"><?php echo $fetch_book['author']; ?></div>
      <div class="price">$<?php echo $fetch_book['price']; ?>/-</div>
      <input type="number" min="1" name="book_quantity" value="1" class="qty">
      <input type="hidden" name="book_id" value="<?php echo $fetch_book['id']; ?>">
      <input type="submit" value="Add to Cart" name="add_to_cart" class="btn">
     </form>
      <?php
         }
      } else {
        echo '<p class="empty">No books available for sale.</p>';
     }
     ?>
   </div>
</section>

<?php include 'footer.php'; ?>

<!-- custom js file link -->
<script src="js/script.js"></script>

</body>
</html>
