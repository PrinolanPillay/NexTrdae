<?php
include("Start_session.php");
if(!isset($_SESSION["login_id"])){
header("Location: Log In Page.php");
exit();
}
$login_id = $_SESSION["login_id"];
$stmt = $conn->prepare("
SELECT product_name, product_group, product_price, discounted_price, product_id
FROM product_list 
WHERE login_id = ?  
");

$stmt->bind_param("i", $login_id);
$stmt->execute();

$result = $stmt->get_result();
?>
<html>
<head>
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="CSS/CssMyProductPage.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="uploads/icon.png">
</head>
<body>
 <div class="layout">
    <nav class="sidebar">
        <h1 class="heading">My Account</h1>
    <ul class="navlist">
<li><a href="AccountPage.php">Personal Details</a></li>
<li><a href="Security_Page.php">Security</a></li>
<li><a href="Orders_Page.php">Orders</a></li>
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
<div class="table-wrapper">
<table class="product-table">
    <tr>
        <th>Product ID</th>
        <th>Product Name</th>
        <th>Price</th>
        <th>Discounted Price</th>
        <th>Group</th>
        <th></th>
       
    </tr>

   <?php if ($result->num_rows > 0) { ?>

    <?php while($row = $result->fetch_assoc()) { ?>
    <tr>
        <td><?php echo $row['product_id']; ?></td>
        <td> <a href="Item_page.php?id=<?php echo $row['product_id']; ?>" class="item_page"><?php echo $row['product_name']; ?>
    </a></td>
        <td>R<?php echo $row['product_price']; ?></td>
        <td>
            <?php 
            if(!empty($row['discounted_price'])) {
                echo "R" . $row['discounted_price'];
            } else {
                echo "-";
            }
            ?>
        </td>
        <td><?php echo $row['product_group']; ?></td>
        <td><a href="Edit_Item_Page.php?id=<?php echo $row['product_id']; ?>" class="edit">Edit</a></td>
    </tr>
    <?php } ?>

<?php } else { ?>

    <tr>
        <td colspan="6" style="text-align:center; padding:20px;">
            No products found
        </td>
    </tr>

<?php } ?>
</table>
</div>
<a href="AddProduct.php" class="add_button">Add Product</a>
</div>
</div>

<a href="Customer_Support.php" class="floating_support">
    💬
</a>
</body>
</html>


