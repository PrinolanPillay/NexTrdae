<?php
include("Start_session.php");

if(!isset($_SESSION["login_id"])){
    header("Location: Log In Page.php");
    exit();
}

if (!isset($_GET['id'])) {
    echo "No user selected.";
    exit();
}

$user_id = $_GET['id'];

if (isset($_SESSION['message'])) {
    $message = $_SESSION['message'];
    unset($_SESSION['message']);
}

/* GET USER INFO */
$stmt = $conn->prepare("
SELECT 
    login_info.login_id,
    login_info.login_username,
    login_info.admin_access,
    personal_info.Firstname,
    personal_info.Surname,
    personal_info.email,
    personal_info.phone_number

FROM login_info

LEFT JOIN personal_info
ON login_info.login_id = personal_info.login_id

WHERE login_info.login_id = ?
");

$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

/* CHECK IF CURRENT USER IS ADMIN */
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

// GET ADMIN ACCESS LEVEL
$levelStmt = $conn->prepare("
SELECT access_level
FROM admin
WHERE login_id = ?
");

$levelStmt->bind_param("i", $_SESSION["login_id"]);
$levelStmt->execute();

$levelResult = $levelStmt->get_result();
$levelData = $levelResult->fetch_assoc();

$access_level = $levelData['access_level'] ?? "";

?>


<html>
<head>
    <title>NexTrade</title>
<link rel="stylesheet" href="CSS/Css_Edit_UP.css?v=<?php echo time(); ?>">
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

<div class="edit_info">

<form method="POST">

<h2>Update User</h2>

<div class="info_row">
<label class="info_label">Username:</label>

<input 
type="text"
name="login_username"
value="<?php echo $user['login_username']; ?>"
class="info_value">
</div>

<div class="info_row">
<label class="info_label">First Name:</label>

<input
type="text"
name="Firstname"
value="<?php echo $user['Firstname']; ?>"
class="info_value">
</div>

<div class="info_row">
<label class="info_label">Last Name:</label>

<input
type="text"
name="Surname"
value="<?php echo $user['Surname']; ?>"
class="info_value">
</div>

<div class="info_row">
<label class="info_label">Email:</label>

<input
type="email"
name="email"
value="<?php echo $user['email']; ?>"
class="info_value">
</div>

<div class="info_row">
<label class="info_label">Phone Number:</label>

<input
type="text"
name="phone_number"
value="<?php echo $user['phone_number']; ?>"
class="info_value">
</div>



<button type="submit" class="update_btn">
Update User
</button>



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

    /* UPDATE login_info */
    $stmt = $conn->prepare("
    UPDATE login_info
    SET 
        login_username = ?,
        admin_access = ?
    WHERE login_id = ?
    ");

    $stmt->bind_param(
        "sii",
        $_POST['login_username'],
        $_POST['admin_access'],
        $user_id
    );

    $stmt->execute();

    /* UPDATE personal_info */
    $stmt2 = $conn->prepare("
    UPDATE personal_info
    SET
        Firstname = ?,
        Surname = ?,
        email = ?,
        phone_number = ?
    WHERE login_id = ?
    ");

    $stmt2->bind_param(
        "ssssi",
        $_POST['Firstname'],
        $_POST['Surname'],
        $_POST['email'],
        $_POST['phone_number'],
        $user_id
    );

    $stmt2->execute();

    $_SESSION['message'] =
    "User updated successfully!";

    header("Location: Edit_User_Page.php?id=$user_id");
    exit();
}
?>