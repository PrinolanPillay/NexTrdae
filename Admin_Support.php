<?php
include("Start_session.php");

if(!isset($_SESSION["login_id"])){
    header("Location: Log In Page.php");
    exit();
}

/* GET ADMIN ACCESS LEVEL */
$adminStmt = $conn->prepare("
SELECT access_level
FROM admin
WHERE login_id = ?
");

$adminStmt->bind_param("i", $_SESSION["login_id"]);
$adminStmt->execute();

$admin = $adminStmt->get_result()->fetch_assoc();

$access_level = $admin['access_level'];

/* GET USERNAME */
$userStmt = $conn->prepare("
SELECT login_username
FROM login_info
WHERE login_id = ?
");

$userStmt->bind_param("i", $_SESSION["login_id"]);
$userStmt->execute();

$userData = $userStmt->get_result()->fetch_assoc();

$username = $userData['login_username'];

/* GET ALL TICKETS */

$stmt = $conn->prepare("
SELECT
    customer_support.support_id,
    customer_support.subject,
    customer_support.status,
    customer_support.created_at,
    personal_info.Firstname,
    personal_info.Surname,
    personal_info.email

FROM customer_support

JOIN personal_info
ON customer_support.login_id = personal_info.login_id

ORDER BY customer_support.created_at DESC
");

$stmt->execute();
$result = $stmt->get_result();
?>

<html>
<head>
<title>NexTrade</title>
<link rel="stylesheet" href="CSS/Css_Admin_Support.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="uploads/icon.png">
</head>

<body>

<div class="layout">

<nav class="sidebar">

<h1 class="heading">
    Admin Panel
    <span class="admin_name">
        <?php echo htmlspecialchars($username); ?>
    </span>
</h1>

<ul class="navlist">

<li>
    <a href="Admin_Home_Page.php">
        Product List
    </a>
</li>

<?php if($access_level == "user" || $access_level == "order"){ ?>
<li>
    <a href="Admin_User_List.php">
        User Accounts
    </a>
</li>
<?php } ?>

<?php if($access_level == "order"){ ?>
<li>
    <a href="Admin_Order.php">
        Orders
    </a>
</li>
<?php } ?>

<li>
    <a href="Admin_Support.php" class="active">
        Support Tickets
    </a>
</li>

<li>
    <a href="LogOut.php">
        Logout
    </a>
</li>

</ul>

</nav>

<div class="content">

<h2 class="page_title">
    Customer Support Tickets
</h2>

<table class="product_table">

<tr>
    <th>ID</th>
    <th>Customer</th>
    <th>Email</th>
    <th>Subject</th>
    <th>Status</th>
    <th>Date</th>
    <th>Open</th>
</tr>

<?php while($row = $result->fetch_assoc()) { ?>

<tr>

<td>
<?php echo $row['support_id']; ?>
</td>

<td>
<?php echo htmlspecialchars($row['Firstname']." ".$row['Surname']); ?>
</td>

<td>
<?php echo htmlspecialchars($row['email']); ?>
</td>

<td>
<?php echo htmlspecialchars($row['subject']); ?>
</td>

<td>

<?php if($row['status']=="Open"){ ?>

<span class="status_open">
Open
</span>

<?php } else { ?>

<span class="status_resolved">
Resolved
</span>

<?php } ?>

</td>

<td>
<?php echo $row['created_at']; ?>
</td>

<td>

<a
href="Admin_Support_Chat.php?id=<?php echo $row['support_id']; ?>"
class="edit">
Open Ticket
</a>

</td>

</tr>

<?php } ?>

</table>

</div>

</div>

</body>
</html>