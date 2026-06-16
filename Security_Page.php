<?php
include("Start_session.php");
if(!isset($_SESSION["login_id"])){
header("Location: Log In Page.php");
exit();
}

$login_id=$_SESSION["login_id"];
//Get info
$stmt = $conn->prepare("
SELECT personal_info.phone_number,
       personal_info.email,
       personal_info.paypal_email
FROM personal_info
WHERE personal_info.login_id = ?
");
$stmt->bind_param("i", $login_id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();
if (isset($_SESSION['message'])) {
   $message=$_SESSION['message'];
    unset($_SESSION['message']);
}
$phone_number = $user["phone_number"] ?? "";
$email = $user["email"] ?? "";
?>


<html>
<head>
    <title>NexTrade</title>
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="CSS/Css_Security_Page.css?v=<?php echo time(); ?>">
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
<li><a href="Delete_Account.php" onclick="return confirm('Are you sure you want to delete your account? This cannot be undone.');">Delete Account</a></li>
</ul>
</nav>
<div class="content">
 <div class="security_info">
<form method="POST" >

<h2>Security</h2>

<div class="info_row">
    <label class="info_label">Phone Number:</label>
    <input type="text" name="phone_number" placeholder="Enter your Phone number" value="<?php echo $phone_number; ?>" class="info_value">
</div>
<div class="info_row">
    <label class="info_label">Email:</label>
    <input type="text" name="email" placeholder="Enter your email" value="<?php echo $email; ?>" class="info_value">
</div>

<div class="info_row">
    <label class="info_label">PayPal Email:</label>
<input type="email" name="paypal_email" placeholder="Enter your PayPal email" class="info_value">
</div>
    <button type="submit" class="Update_btn">Update Details</button><br>
   <?php
    if(!empty($message)){
    echo $message;
    }
    ?>
</form>

</div>
</div>
<a href="Customer_Support.php" class="floating_support">
    💬
</a>
</body>
</html>
<?php
$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $new_phone = $_POST['phone_number'];
    $new_email = $_POST['email'];
$paypal_email = isset($_POST["paypal_email"])
    ? trim($_POST["paypal_email"])
    : "";

    // VALIDATE PHONE
    
    if (!empty($new_phone)) {

        if (!preg_match("/^0[0-9]{9}$/", $new_phone)) {
            $errors[] = "Phone number must be 10 digits and start with 0.";
        }

        $stmt4 = $conn->prepare("
            SELECT phone_number 
            FROM personal_info 
            WHERE phone_number = ? AND login_id != ?
        ");
        $stmt4->bind_param("si", $new_phone, $login_id);
        $stmt4->execute();
        $result = $stmt4->get_result();

        if ($result->num_rows > 0) {
            $errors[] = "Phone number is already in use.";
        }
        $stmt4->close();
    }

//Comapre paypal emails
$paypal_existing = $user["paypal_email"] ?? "";

$paypal_existing = $paypal_existing ?? "";
// Validate PayPal email
if ($paypal_email !== "") {

    if (!filter_var($paypal_email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid PayPal email.";
    }
}

    // Stop if errors exist
    if (!empty($errors)) {
        $_SESSION['message'] = implode(", ", $errors);
        header("Location: Security_Page.php");
        exit();
    }

    // BUILD UPDATE
   
    $fields = [];
    $params = [];
    $types = "";

    if (!empty($new_phone) && $new_phone !== $phone_number) {
        $fields[] = "phone_number = ?";
        $params[] = $new_phone;
        $types .= "s";
    }

    if ($new_email !== $email) {

        if ($new_email === "") {
            $fields[] = "email = NULL";
        } else {
            $fields[] = "email = ?";
            $params[] = $new_email;
            $types .= "s";
        }
    }

   
// PAYPAL UPDATE (supports add, update, remove)
if ($paypal_email !== $paypal_existing) {

    if ($paypal_email === "") {
        $fields[] = "paypal_email = NULL";
    } else {
        $fields[] = "paypal_email = ?";
        $params[] = $paypal_email;
        $types .= "s";
    }
}

  
    // EXECUTE
  
    if (!empty($fields)) {

        $sql = "UPDATE personal_info SET " . implode(", ", $fields) . " WHERE login_id = ?";
        $stmt = $conn->prepare($sql);

        $types .= "i";
        $params[] = $login_id;

        $stmt->bind_param($types, ...$params);
        $stmt->execute();

        $_SESSION['message'] = "Personal details updated successfully!";
        header("Location: Security_Page.php");
        exit();

    } else {
        $_SESSION['message'] = "No changes made.";
        header("Location: Security_Page.php");
        exit();
    }
}
?>