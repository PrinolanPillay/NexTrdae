<?php
include("Start_session.php");

if(!isset($_SESSION["login_id"])){
    header("Location: Log In Page.php");
    exit();
}
// GET ADMIN ACCESS LEVEL
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

// GET ALL PRODUCTS + OWNER INFO
$stmt = $conn->prepare("
SELECT 
    product_list.product_id,
    product_list.product_name,
    product_list.product_group,
    product_list.product_price,
    product_list.discounted_price,
    login_info.login_username,
    personal_info.Firstname,
    personal_info.Surname

FROM product_list

LEFT JOIN login_info
ON product_list.login_id = login_info.login_id

LEFT JOIN personal_info
ON login_info.login_id = personal_info.login_id

ORDER BY product_list.product_id DESC
");

$stmt->execute();

$result = $stmt->get_result();
?>

<html>
<head>
    <title>NexTrade</title>
    <link rel="stylesheet" href="CSS/Css_Admin_Hp.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="uploads/icon.png">
</head>

<body>

<div class="layout">

    <nav class="sidebar">

<h1 class="heading">
    Admin Panel
    <span class="admin_name">
        <?php echo $username; ?>
    </span>
</h1>

    <ul class="navlist">

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

    </ul>

</nav>

<div class="content">

<table class="product_table">

    <tr>
        <th>Product ID</th>
        <th>Product Name</th>
        <th>Category</th>
        <th>Price</th>
        <th>Discounted Price</th>
        <th>Owner Username</th>
        <th>Owner Name</th>
        <th>Edit</th>
        <th>Delete</th>
    </tr>

<?php while($row = $result->fetch_assoc()) { ?>

    <tr>

        <td><?php echo $row['product_id']; ?></td>

      <td><a href="Item_page.php?id=<?php echo $row['product_id']; ?>" class="item_page" ><?php echo $row['product_name']; ?></a></td>

        <td><?php echo $row['product_group']; ?></td>

        <td>
            R<?php echo $row['product_price']; ?>
        </td>

        <td>
            <?php 
            if(!empty($row['discounted_price'])){
                echo "R" . $row['discounted_price'];
            } else {
                echo "-";
            }
            ?>
        </td>

        <td>
            <?php 
            if(!empty($row['login_username'])){
                echo $row['login_username'];
            } else {
                echo "-";
            }
            ?>
        </td>

        <td>
            <?php 
            if(!empty($row['Firstname']) || !empty($row['Surname'])){
                echo $row['Firstname'] . " " . $row['Surname'];
            } else {
                echo "-";
            }
            ?>
        </td>

        <!-- EDIT BUTTON -->
        <td>
            <a 
            href="Edit_Item_Page.php?id=<?php echo $row['product_id']; ?>" 
            class="edit">
            Edit
            </a>
        </td>

        <!-- DELETE BUTTON -->
        <td>
            <a 
            href="Delete_Product.php?id=<?php echo $row['product_id']; ?>" 
            class="delete"
            onclick="return confirm('Are you sure you want to delete this product?');">
            Delete
            </a>
           
        </td>

    </tr>

<?php } ?>

</table>

</div>
</div>

</body>
</html>