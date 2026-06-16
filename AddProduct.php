<?php
    ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include("Start_session.php");
if(!isset($_SESSION["login_id"])){
header("Location: Log In Page.php");
exit();
}

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
} else {
    $message = "";
}
?>

<html>
<head>
    <title>NexTrade</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/CssAddProduct.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="uploads/icon.png">
</head>
<body>
 <div class="layout">
    <nav class="sidebar">
        <h1 class="heading">My Account</h1>
    <ul class="navlist">
<li><a href="AccountPage.php">Personal Details</a></li>
<li><a href="Security_Page.php">Security</a></li>
<li><a href="Orders_Page">Orders</a></li>
<li><a href="MyProductPage.php">My Products</a></li>
<li><a href="index.php">HomePage</a></li>
<li><a href="LogOut.php">Logout</a></li>
<li>
<a href="Delete_Account.php" 
onclick="return confirm('Are you sure you want to delete your account? This cannot be undone.');">
Delete Account
</a>
</li>
</ul>
</nav>
<div class="content">
    <div class="product_info">
<form method="POST" enctype="multipart/form-data">

    <h2>Add New Product</h2>

<div class="info_row">
    <label class="info_label">Product Name:</label>
    <input type="text" name="product_name" class="info_value" required>
</div>

<div class="info_row">
    <label class="info_label">Product Description:</label>
    <textarea name="product_description" rows="5" cols="30" class="info_value" required></textarea>
</div>

    <div class="info_row">
  <label for="product_type" class="info_label"> Product Type:</label>
    <select name = "product_type" id="product_type" class="info_value">
        <option value="electronics">Electronics</option>
        <option value="fashion">Fashion</option>
        <option value="home_furniture">Home & Furniture</option>
        <option value="books_education">Books</option>
        <option value="kids_baby_items">Kids & Baby Items</option>
        <option value="vehicle">Vehicle</option>
        <option value="hobbies_entertainment">Hobbies & Entertainment</option>
        <option value="beauty_health">Beauty & Skincare</option>

</select>
</div>

<div class="info_row">
    <label class="info_label">Price:</label>
    <input type="number" name="product_price" class="info_value" required>
   </div>

    <div class="info_row">
    <label class="info_label">Cover Image:</label>
    <input type="file" name="product_image" accept="image/*" class="info_value" required>
</div>

    <div class="info_row">
    <label class="info_label">Images of Product:</label>
    <input type="file" name="product_images[]" accept="image/*" class="info_value" multiple required>
</div>

<div class="info_row">
    <label class="info_label">Product Quantity</label>
    <input type="number" name="quantity" class="info_value" required>
</div>
    
    <button type="submit" class="add_btn">Add Product</button>
 <?php
    if(!empty($message)){
    echo $message;
    }
    ?>
</form>
</div>
</div>
</div>


</body>
</html>

<?php 
 if($_SERVER["REQUEST_METHOD"]=="POST"){
$product_name=trim($_POST["product_name"]);
$product_description=trim($_POST["product_description"]);
$product_price=(float)$_POST["product_price"];
$product_group=trim($_POST["product_type"]);
$product_quantity = (int)$_POST["quantity"];
$login_id=$_SESSION["login_id"];

  // CHECK IF PRODUCT NAME ALREADY EXISTS FOR THIS USER
     
     
   $checkStmt = $conn->prepare("
    SELECT product_id 
    FROM product_list 
    WHERE LOWER(TRIM(product_name)) = LOWER(TRIM(?))
    LIMIT 1
");

$checkStmt->bind_param("s", $product_name);
$checkStmt->execute();

/* IMPORTANT FIX */
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
  $_SESSION['message'] = "Error: Product name already in use!";
header("Location: AddProduct.php");
exit();
}

     
$image = $_FILES['product_image']['name'];
$tmp = $_FILES['product_image']['tmp_name'];

$folder = "uploads/";
$product_image = $folder . uniqid() . "_" . $image;

if (move_uploaded_file($tmp, $product_image)) {



$stmt = $conn->prepare("
INSERT INTO product_list 
(product_name, product_description, product_price, login_id,product_image, product_group, quantity)
VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt->bind_param(
    "ssiissi",$product_name,$product_description,$product_price,$login_id,$product_image,$product_group,$product_quantity
);


    if(!$stmt->execute()){
    $_SESSION['message'] = "Error: " . $stmt->error;
    header("Location: AddProduct.php");
    exit();
}
//Product Images uploads
$product_id = $conn->insert_id;
 $uploadDir = "uploads/";

foreach ($_FILES['product_images']['tmp_name'] as $key => $tmp_name) {

    $fileName = basename($_FILES['product_images']['name'][$key]);

    $targetFile = $uploadDir . uniqid() . "_" . $fileName;

    move_uploaded_file($tmp_name, $targetFile);

    $stmt = $conn->prepare("
        INSERT INTO product_images (product_id, image_path)
        VALUES (?, ?)
    ");

    $stmt->bind_param("is", $product_id, $targetFile);
    
    if(!$stmt->execute()){
    $_SESSION['message'] = "Error: " . $stmt->error;
    header("Location: AddProduct.php");
    exit();
}
}

$_SESSION['message'] = "Product added successfully!";
header("Location: AddProduct.php");
exit();
}
else{
    echo"Upload failed";
}
 }

?>