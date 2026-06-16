<?php
include("Start_session.php");

if(!isset($_GET['token'])){
    die("Invalid request");
}

$token = $_GET['token'];

$stmt = $conn->prepare("
    SELECT *
    FROM password_reset
    WHERE reset_token = ?
    AND used = 0
    AND expires_at > NOW()
");

$stmt->bind_param("s", $token);
$stmt->execute();

$result = $stmt->get_result();

if(!$result->fetch_assoc()){
    die("Invalid or expired token. Please request a new reset password link");
}

if(isset($_POST['reset'])){

    $newPassword = password_hash(
        $_POST['password'],
        PASSWORD_DEFAULT
    );

    $update = $conn->prepare("
        UPDATE login_info li
        JOIN password_reset pr
        ON li.login_id = pr.login_id
        SET li.login_password = ?
        WHERE pr.reset_token = ?
    ");

    $update->bind_param(
        "ss",
        $newPassword,
        $token
    );

    $update->execute();

    $markUsed = $conn->prepare("
        UPDATE password_reset
        SET used = 1
        WHERE reset_token = ?
    ");

    $markUsed->bind_param("s", $token);
    $markUsed->execute();

    echo "Password updated successfully.";
}
?>


<!DOCTYPE html>
<html>
<head>
    <title>NexTrade</title>
    <link rel="stylesheet" href="CSS/Css_Reset_Password_Page.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="uploads/icon.png">
</head>

<body>

<div class="reset_container">

    <h1>Reset Password</h1>

    <p class="description">
        Enter your new password below.
    </p>

    <?php if(!empty($message)){ ?>
        <div class="message success">
            <?php echo $message; ?>
        </div>
    <?php } ?>

    <form method="post">

        <div class="input_group">
            <label>New Password</label>
            <input type="password" name="password" required>
        </div>

        <button type="submit" name="reset" class="reset_btn">
            Reset Password
        </button>

    </form>

</div>

</body>
</html>
