<?php
    ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include("Start_session.php");
if(!isset($_SESSION["login_id"])){
header("Location: Log In Page.php");
exit();
}

if (!isset($_GET['id'])) {
    echo "No product selected.";
    
    exit();
}
$product_id = $_GET['id'];
if (isset($_SESSION['message'])) {
   $message=$_SESSION['message'];
    unset($_SESSION['message']);
}
$stmt = $conn->prepare("SELECT * FROM product_list WHERE product_id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$result = $stmt->get_result();
$product = $result->fetch_assoc();

// CHECK IF USER IS ADMIN
$adminStmt = $conn->prepare("
SELECT admin_access 
FROM login_info 
WHERE login_id = ?
");

$adminStmt->bind_param("i", $_SESSION["login_id"]);
$adminStmt->execute();

$adminResult = $adminStmt->get_result();

$adminRow = $adminResult->fetch_assoc();

$isAdmin = $adminRow['admin_access'];

// GET ADMIN ACCESS LEVEL
if($isAdmin==1){

$adminStmt = $conn->prepare("
SELECT access_level
FROM admin
WHERE login_id = ?
");

$adminStmt->bind_param("i", $_SESSION["login_id"]);
$adminStmt->execute();

$adminResult = $adminStmt->get_result();

$admin = $adminResult->fetch_assoc();

$access_level = $admin['access_level'];

}
// GET LOGGED IN USERNAME 
$userStmt = $conn->prepare("
SELECT login_username
FROM login_info
WHERE login_id = ?
");

$userStmt->bind_param("i", $_SESSION["login_id"]);
$userStmt->execute();

$userResult = $userStmt->get_result();

$userData = $userResult->fetch_assoc();

$username = $userData['login_username'];

?>
<script>
function confirmDelete() {
    if (confirm("Are you sure you want to delete this product?")) {
        window.location.href = "Delete_Product.php?id=<?php echo $product_id; ?>";
    }
}
</script>

<html>
<head>
    <title>NexTrade</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/Css_Edit_Item_Page.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="uploads/icon.png">
</head>
<body>
 <div class="layout">
   <nav class="sidebar">

<?php 
if($isAdmin == 1){
?>

<h1 class="heading">
    Admin Panel
    <span class="admin_name">
        <?php echo $username; ?>
    </span>
</h1>

<?php
} else {
?>

<h1 class="heading">
    My Account
</h1>

<?php
}
?>


<ul class="navlist">

<?php if($isAdmin == 1){ ?>

    <!-- ADMIN NAVIGATION -->
     

        <!-- PRODUCT TAB ALWAYS SHOWS -->
        <li>
            
            <a href="Admin_Home_Page.php" class="active">
                Product List
            </a>
        </li>

        <?php if($access_level == "user"|| $access_level == "order"){ ?>

            <!-- ONLY USER ACCESS LEVEL CAN SEE USERS -->
            <li>
                <a href="Admin_User_List.php" >
                    User Accounts
                </a>
            </li>

        <?php } ?>

        <?php if($access_level == "order"){ ?>

            <!-- ONLY ORDER ACCESS LEVEL CAN SEE ORDER -->
            <li>
                <a href="Admin_Order.php">
                    Orders
                </a>
            </li>

        <?php } ?>
        <li>
         <a href="Admin_Support.php"> Support Tickets</a>
        </li>
        <li>
            <a href="LogOut.php">
                Logout
            </a>
        </li>

    

<?php } else { ?>

    <!-- NORMAL USER NAVIGATION -->
    <li><a href="AccountPage.php">Personal Details</a></li>
    <li><a href="Security_Page.php">Security</a></li>
    <li><a href="Orders_Page.php">Orders</a></li>
    <li><a href="MyProductPage.php" class="active">My Products</a></li>
    <li><a href="index.php">HomePage</a></li>
    <li><a href="LogOut.php">Logout</a></li>

    <li>
        <a 
        href="Delete_Account.php"
        onclick="return confirm('Are you sure you want to delete your account? This cannot be undone.');">
        Delete Account
        </a>
    </li>

<?php } ?>

</ul>
</nav>
<div class="content">
    <div class="edit_info">
<form method="POST" enctype="multipart/form-data">

    <h2>Update Product</h2>

<div class="info_row">
    <label class="info_label">Product Name:</label>
    <input type="text" name="product_name" value="<?php echo $product["product_name"]; ?>" class="info_value">
</div>

<div class="info_row">
    <label class="info_label">Product Description:</label>
    <textarea name="product_description" rows="5" cols="30" class="info_value"><?php echo $product["product_description"]; ?></textarea>
</div>
    
<div class="info_row">
  <label for="product_group" class="info_label"> Product Type:</label>
    <select name = "product_group" id="product_group" value="<?php echo $product["product_group"]; ?>" class="info_value">
        <option value="electronics" <?php if($product["product_group"]=="electronics") echo "selected";?>>Electronics</option>
        <option value="fashion"<?php if($product["product_group"]=="fashion") echo "selected";?>>Fashion</option>
        <option value="home_furniture"<?php if($product["product_group"]=="home_furniture") echo "selected";?>>Home & Furniture</option>
        <option value="books_education"<?php if($product["product_group"]=="books_education") echo "selected";?>>Books & Education</option>
        <option value="kids_baby_items"<?php if($product["product_group"]=="kids_baby_items") echo "selected";?>>Kids & Baby Items</option>
        <option value="vehicle"<?php if($product["product_group"]=="vehicle") echo "selected";?>>Vehicle</option>
        <option value="hobbies_entertainment" <?php if($product["product_group"]=="hobbies_entertainment") echo "selected";?>>Hobbies & Entertainment</option>
        <option value="beauty_health"<?php if($product["product_group"]=="beauty_health") echo "selected";?>>Beauty & Skincare</option>

</select>
</div>

<div class="info_row">
    <label class="info_label">Price:</label>
    <input type="number" name="product_price"  value="<?php echo $product['product_price']; ?>" class="info_value">
</div>

<div class="info_row">
    <label class="info_label">Discount Price</label>
     <input type="number" name="discounted_price"  value="<?php echo $product['discounted_price']; ?>" class="info_value">
</div>

<div class="info_row">
    <label class="info_label">Product Quantity</label>
    <input type="number" name="quantity" value="<?php echo $product['quantity']; ?>" class="info_value">
 </div>

 <div class="info_row">   
    <label class="info_label">Update Cover Image:</label>
    <input type="file" name="product_image" accept="image/*" class="info_value" >
</div>

<div class="info_row">
    <label class="info_label">Update Images of Product:</label>
    <input type="file" name="product_images[]" accept="image/*" multiple  class="info_value">
</div>

    <button type="submit"class="update_btn">Update Product</button>
    <button type="button" onclick="confirmDelete()" class="delete_btn">Delete Product</button>
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
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $fields = [];
    $params = [];
    $types = "";
//Check Changes
    if (!empty($_POST['product_name'])) {
        $fields[] = "product_name = ?";
        $params[] = $_POST['product_name'];
        $types .= "s";
    }

    if (!empty($_POST['product_description'])) {
        $fields[] = "product_description = ?";
        $params[] = $_POST['product_description'];
        $types .= "s";
    }

    if (!empty($_POST['product_price'])) {
        $fields[] = "product_price = ?";
        $params[] = $_POST['product_price'];
        $types .= "i";
    }

    if (isset($_POST['discounted_price'])) {

    if ($_POST['discounted_price'] === "") {
        
        $fields[] = "discounted_price = NULL";
    } else {
        $fields[] = "discounted_price = ?";
        $params[] = $_POST["discounted_price"];
        $types .= "i";
    }
}

    if (!empty($_POST['quantity'])) {
        $fields[] = "quantity = ?";
        $params[] = $_POST['quantity'];
        $types .= "i";
    }
    if (!empty($_POST['product_group'])) {
    $fields[] = "product_group = ?";
    $params[] = $_POST['product_group'];
    $types .= "s";
}
if (
    isset($_FILES['product_image']) &&
    $_FILES['product_image']['error'] == 0
) {

    $folder = "uploads/";

    $imageName = $_FILES['product_image']['name'];

    $tmp = $_FILES['product_image']['tmp_name'];

    $newImage = $folder . uniqid() . "_" . basename($imageName);

    if(move_uploaded_file($tmp, $newImage)){

        $fields[] = "product_image = ?";

        $params[] = $newImage;

        $types .= "s";
    }
}

 if (!empty($fields)) {

    $sql = "UPDATE Product_List SET " . implode(", ", $fields) . " WHERE product_id = ?";

    $stmt = $conn->prepare($sql);

    $types .= "i";

    $params[] = $product_id;

    $stmt->bind_param($types, ...$params);

    $stmt->execute();
}



if (
    isset($_FILES['product_images']) &&
    !empty($_FILES['product_images']['name'][0])
) {

    $uploadDir = "uploads/";

   

    $deleteStmt = $conn->prepare("
        DELETE FROM Product_Images
        WHERE product_id = ?
    ");

    $deleteStmt->bind_param("i", $product_id);

    $deleteStmt->execute();


    foreach ($_FILES['product_images']['tmp_name'] as $key => $tmp_name) {

        $fileName = basename(
            $_FILES['product_images']['name'][$key]
        );

        $targetFile =
            $uploadDir .
            uniqid() .
            "_" .
            $fileName;

        if(move_uploaded_file($tmp_name, $targetFile)){

            $stmtImg = $conn->prepare("
                INSERT INTO Product_Images
                (product_id, image_path)
                VALUES (?, ?)
            ");

            $stmtImg->bind_param(
                "is",
                $product_id,
                $targetFile
            );

            $stmtImg->execute();
        }
    }

}
if (!empty($fields) || !empty($_FILES['product_images']['name'][0])){


  $_SESSION['message'] = "Product updated successfully!";

header("Location: Edit_Item_Page.php?id=$product_id");

exit();

}

    
}

?>