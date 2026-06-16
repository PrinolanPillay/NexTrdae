
<?php
include("Start_session.php");

if(!isset($_SESSION["login_id"])){
    header("Location: Log In Page.php");
    exit();
}
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
/* GET ALL USERS */
$stmt = $conn->prepare("
SELECT 
    login_info.login_id,
    login_info.login_username,
    personal_info.Firstname,
    personal_info.Surname,
    personal_info.email,
    personal_info.phone_number
FROM login_info

LEFT JOIN personal_info 
ON login_info.login_id = personal_info.login_id

ORDER BY login_info.login_id DESC
");

$stmt->execute();

$result = $stmt->get_result();


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
            <?php if($access_level == "user"|| $access_level == "order"){ ?>

            <!-- ONLY USER ACCESS LEVEL CAN SEE USERS -->
            <li>
                <a href="Admin_User_List.php" class="active">
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
            <li><a href="LogOut.php">Logout</a></li>
        </ul>

    </nav>

<div class="content">

<table class="user_table">

    <tr>
        <th>User ID</th>
        <th>Username</th>
        <th>First Name</th>
        <th>Last Name</th>
        <th>Email</th>
        <th>Phone Number</th> 
        <th>Edit</th>
        <th>Delete</th>
    </tr>

<?php while($row = $result->fetch_assoc()) { ?>

    <tr>

        <td><?php echo $row['login_id']; ?></td>

        <td><?php echo $row['login_username']; ?></td>

        <td>
            <?php 
            if(!empty($row['Firstname'])){
                echo $row['Firstname'];
            } else {
                echo "-";
            }
            ?>
        </td>

        <td>
            <?php 
            if(!empty($row['Surname'])){
                echo $row['Surname'];
            } else {
                echo "-";
            }
            ?>
        </td>

        <td>
            <?php 
            if(!empty($row['email'])){
                echo $row['email'];
            } else {
                echo "-";
            }
            ?>
        </td>

        <td>
            <?php 
            if(!empty($row['phone_number'])){
                echo $row['phone_number'];
            } else {
                echo "-";
            }
            ?>
        </td>

        <!-- EDIT BUTTON -->
        <td>
            <a 
            href="Edit_User_Page.php?id=<?php echo $row['login_id']; ?>" 
            class="edit">
            Edit
            </a>
        </td>

        <!-- DELETE BUTTON -->
        <td>
            <a 
            href="Delete_User.php?id=<?php echo $row['login_id']; ?>" 
            class="delete"
            onclick="return confirm('Are you sure you want to delete this user?');">
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