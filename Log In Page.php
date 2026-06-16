<?php
include("Start_session.php");

if(isset($_SESSION["login_id"])){
    header("Location: index.php");
    exit();
}
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = $_POST["username"];
    $password = $_POST["password"];
    $errors = [];
if (empty($errors)) {
    $stmt = $conn->prepare("
        SELECT login_id, login_password, admin_access 
        FROM login_info 
        WHERE login_username = ?
    ");

    $stmt->bind_param("s", $username);
    $stmt->execute();

    $result = $stmt->get_result();

    if ($row = $result->fetch_assoc()) {

        $hashedPassword = $row["login_password"];

        if (password_verify($password, $hashedPassword)) {

            $_SESSION["login_id"] = $row["login_id"];
            $_SESSION["admin_access"] = $row["admin_access"];

            // CHECK ADMIN ACCESS
            if ($row["admin_access"] == 1) {

                header("Location: Admin_Home_Page.php");
                exit();

            } else {

                header("Location: index.php");
                exit();

            }

        } else {

            $errors[]="Incorrect password!";

        }

    } else {
$errors[]="User not found!";
        

    }

    $stmt->close();
    $conn->close();
}
}
?>

<html>
<head>
    <title>NexTrade</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="CSS/Css_Log_In_Page.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="uploads/icon.png">
</head>

<body>

<div class="login_container">
    <h1>Login</h1>
<?php if (!empty($errors)): ?>
    <div class="error_box">
        <ul>
            <?php foreach($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
    <form method="post">

        <div class="input_group">
            <label>Username:</label>
            <input type="text" name="username" required>
        </div>

        <div class="input_group">
            <label>Password:</label>
            <input type="password" name="password" required>
        </div>
        <div class="forgot_password">
            <a href="ForgotPassword.php">Forgot Password?</a>
        </div>
        <input type="submit" value="login" class="login_btn"> 

        <div class="signup_text">
            No Account? <a href='SignUpPage.php'>Create One</a>
        </div>

    </form>
</div>

</body>
</html>

