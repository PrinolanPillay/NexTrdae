<?php
include("Start_session.php");

if(!isset($_SESSION["login_id"])){
    header("Location: Log In Page.php");
    exit();
}

/* GET ALL ORDERS */
$stmt = $conn->prepare("
SELECT
    orders.order_id,
    orders.login_id,
    orders.created_at,
    orders.total_price,
    order_items.order_status,
    login_info.login_username,
    personal_info.Firstname,
    personal_info.Surname

FROM orders

LEFT JOIN login_info
ON orders.login_id = login_info.login_id

LEFT JOIN personal_info
ON orders.login_id = personal_info.login_id

LEFT JOIN order_items
ON orders.order_id = order_items.order_id

ORDER BY orders.order_id DESC
");

$stmt->execute();
$result = $stmt->get_result();

/* GET LOGGED IN USERNAME */
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

<html>
<head>
    <title>NexTrade</title>
    <link rel="stylesheet" href="CSS/Css_Admin_Ul.css?v=<?php echo time(); ?>">
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
            <li>
         <a href="Admin_Support.php"> Support Tickets</a>
        </li>
            <li><a href="LogOut.php">Logout</a></li>
        </ul>

    </nav>

    <div class="content">

        <table class="user_table">

            <tr>
                <th>Order ID</th>
                <th>User ID</th>
                <th>Customer</th>
                <th>Username</th>
                <th>Order Date</th>
                <th>Total</th>
                <th>Status</th>
                <th>View</th>
                <th>Delete</th>
            </tr>

            <?php while($row = $result->fetch_assoc()) { ?>

            <tr>

                <td><?php echo $row['order_id']; ?></td>

                <td><?php echo $row['login_id']; ?></td>

                <td>
                    <?php
                    if(!empty($row['Firstname']) || !empty($row['Surname'])){
                        echo $row['Firstname'] . " " . $row['Surname'];
                    } else {
                        echo "-";
                    }
                    ?>
                </td>

                <td><?php echo $row['login_username']; ?></td>

                <td><?php echo $row['created_at']; ?></td>

                <td>R<?php echo number_format($row['total_price'], 2); ?></td>

                <td><?php echo $row['order_status']; ?></td>

                <td>
                    <a
                    href="Admin_View_Order.php?id=<?php echo $row['order_id']; ?>"
                    class="edit">
                    View
                    </a>
                </td>

                <td>
                    <a
                    href="Delete_Order.php?id=<?php echo $row['order_id']; ?>"
                    class="delete"
                    onclick="return confirm('Are you sure you want to delete this order?');">
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