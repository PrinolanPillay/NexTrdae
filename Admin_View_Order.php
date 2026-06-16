<?php
include("Start_session.php");

if(!isset($_SESSION["login_id"])){
    header("Location: Log In Page.php");
    exit();
}

if(!isset($_GET['id'])){
    echo "No order selected.";
    exit();
}

$order_id = $_GET['id'];

/* GET LOGGED IN USERNAME */
$userStmt = $conn->prepare("
SELECT login_username
FROM login_info
WHERE login_id = ?
");

$userStmt->bind_param("i", $_SESSION["login_id"]);
$userStmt->execute();
$userData = $userStmt->get_result()->fetch_assoc();

$username = $userData['login_username'];

/* GET ORDER DETAILS */
$stmt = $conn->prepare("
SELECT
    orders.order_id,
    orders.login_id AS buyer_id,
    orders.created_at,
    orders.total_price,
    orders.payment_status,

    buyer_login.login_username AS buyer_username,
    buyer_info.Firstname AS buyer_firstname,
    buyer_info.Surname AS buyer_surname,
    buyer_info.email AS buyer_email,
    buyer_info.phone_number AS buyer_phone,

    order_items.order_item_id,
    order_items.quantity,
    order_items.price,
    order_items.order_status,
    order_items.tracking_number,
    order_items.tracking_url,
    order_items.buyer_delivered,
    order_items.seller_delivered,
    order_items.reserved_until,

    product_list.product_id,
    product_list.product_name,
    product_list.product_image,
    product_list.login_id AS seller_id,

    seller_login.login_username AS seller_username,
    seller_info.Firstname AS seller_firstname,
    seller_info.Surname AS seller_surname,
    seller_info.email AS seller_email,
    seller_info.phone_number AS seller_phone

FROM orders

INNER JOIN order_items
ON orders.order_id = order_items.order_id

INNER JOIN product_list
ON order_items.product_id = product_list.product_id

LEFT JOIN login_info AS buyer_login
ON orders.login_id = buyer_login.login_id

LEFT JOIN personal_info AS buyer_info
ON orders.login_id = buyer_info.login_id

LEFT JOIN login_info AS seller_login
ON product_list.login_id = seller_login.login_id

LEFT JOIN personal_info AS seller_info
ON product_list.login_id = seller_info.login_id

WHERE orders.order_id = ?
");

$stmt->bind_param("i", $order_id);
$stmt->execute();
$result = $stmt->get_result();

if($result->num_rows == 0){
    echo "Order not found.";
    exit();
}

$rows = [];
while($row = $result->fetch_assoc()){
    $rows[] = $row;
}

$order = $rows[0];
?>

<html>
<head>
    <title>NexTrade</title>
    <link rel="stylesheet" href="CSS/Css_Admin_View_Order.css?v=<?php echo time(); ?>">
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
        <li><a href="Admin_Home_Page.php">Product List</a></li>
        <li><a href="Admin_User_List.php">User Accounts</a></li>
        <li><a href="Admin_Order_List.php" class="active">Orders</a></li>
        <li><a href="Admin_Support.php">Support Tickets</a></li>
        <li><a href="LogOut.php">Logout</a></li>
    </ul>

</nav>

<div class="content">

<h1 class="page_title">Order #<?php echo $order_id; ?></h1>

<a href="Admin_Order_List.php" class="back_btn">Back to Orders</a>

<div class="info_grid">

    <div class="info_card">
        <h2>Order Information</h2>

        <p><strong>Order ID:</strong> <?php echo $order['order_id']; ?></p>
        <p><strong>Order Date:</strong> <?php echo $order['created_at']; ?></p>
        <p><strong>Payment Status:</strong> <?php echo $order['payment_status']; ?></p>
        <p><strong>Total Price:</strong> R<?php echo number_format($order['total_price'], 2); ?></p>

        <?php if($order['payment_status'] == "Pay On Collection" && !empty($order['reserved_until'])){ ?>
            <p>
                <strong>Reserved Until:</strong>
                <?php echo date("d M Y H:i", strtotime($order['reserved_until'])); ?>
            </p>
        <?php } ?>
    </div>

    <div class="info_card">
        <h2>Buyer Information</h2>

        <p><strong>Buyer ID:</strong> <?php echo $order['buyer_id']; ?></p>
        <p><strong>Username:</strong> <?php echo $order['buyer_username']; ?></p>
        <p>
            <strong>Name:</strong>
            <?php echo $order['buyer_firstname'] . " " . $order['buyer_surname']; ?>
        </p>
        <p><strong>Email:</strong> <?php echo $order['buyer_email']; ?></p>
        <p><strong>Phone:</strong> <?php echo $order['buyer_phone']; ?></p>
    </div>

</div>

<?php foreach($rows as $row){ ?>

<div class="order_box">

    <div class="order_header">
        <h2><?php echo $row['product_name']; ?></h2>

        <p><strong>Order Item ID:</strong> <?php echo $row['order_item_id']; ?></p>
        <p><strong>Status:</strong> <?php echo $row['order_status']; ?></p>
    </div>

    <div class="order_item">

        <img src="<?php echo $row['product_image']; ?>" class="order_image">

        <div class="order_info">

            <h3><?php echo $row['product_name']; ?></h3>

            <p><strong>Product ID:</strong> <?php echo $row['product_id']; ?></p>
            <p><strong>Quantity:</strong> <?php echo $row['quantity']; ?></p>
            <p><strong>Price:</strong> R<?php echo number_format($row['price'], 2); ?></p>

        </div>

    </div>

    <div class="status_box">

        <p>
            <strong>Buyer Confirmed:</strong>
            <?php echo ($row['buyer_delivered'] ? "Yes" : "No"); ?>
        </p>

        <p>
            <strong>Seller Confirmed:</strong>
            <?php echo ($row['seller_delivered'] ? "Yes" : "No"); ?>
        </p>

        <?php if(!empty($row['tracking_number'])){ ?>
            <p><strong>Tracking Number:</strong> <?php echo $row['tracking_number']; ?></p>
        <?php } ?>

        <?php if(!empty($row['tracking_url'])){ ?>
            <a href="<?php echo $row['tracking_url']; ?>" target="_blank" class="track_button">
                Track Package
            </a>
        <?php } ?>

    </div>

    <div class="info_card">
        <h2>Seller Information</h2>

        <p><strong>Seller ID:</strong> <?php echo $row['seller_id']; ?></p>
        <p><strong>Username:</strong> <?php echo $row['seller_username']; ?></p>
        <p>
            <strong>Name:</strong>
            <?php echo $row['seller_firstname'] . " " . $row['seller_surname']; ?>
        </p>
        <p><strong>Email:</strong> <?php echo $row['seller_email']; ?></p>
        <p><strong>Phone:</strong> <?php echo $row['seller_phone']; ?></p>
    </div>

</div>

<?php } ?>

</div>

</div>

</body>
</html>