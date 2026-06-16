<?php
   
require 'vendor/autoload.php';
   use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;



include("Start_session.php");
require 'EmailConfig.php';
$message = "";
$message_type = "";
if(isset($_POST['submit'])){

    $email = $_POST['email'];

    $stmt = $conn->prepare("
        SELECT login_id
        FROM personal_info
        WHERE email = ?
    ");

    $stmt->bind_param("s", $email);
    $stmt->execute();

    $result = $stmt->get_result();

    if($row = $result->fetch_assoc()){

        $token = bin2hex(random_bytes(32));

        $expires = date("Y-m-d H:i:s", strtotime("+1 hour"));

        $insert = $conn->prepare("
            INSERT INTO password_reset
            (login_id, reset_token, expires_at)
            VALUES (?, ?, ?)
        ");

        $insert->bind_param(
            "iss",
            $row['login_id'],
            $token,
            $expires
        );

        $insert->execute();

     
       $resetLink =
"https://nex-trade.page.gd/ResetPassword.php?token=".$token;
    



$mail = new PHPMailer(true);

try {

    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;

    $mail->Username = MAIL_USERNAME;
    $mail->Password = MAIL_PASSWORD;
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port = 587;

    $mail->setFrom(
        'pillayprinolan2@gmail.com',
        'Nextrade'
    );

    $mail->addAddress($email);

    $mail->isHTML(true);

    $mail->Subject = 'Reset your Nextrade password';

    $mail->Body = "
    <div style='font-family: Arial, sans-serif; background-color:#f4f6f8; padding:30px;'>

        <div style='max-width:600px; margin:auto; background:white; padding:30px; border-radius:10px;'>

            <h2 style='color:#1e3a8a; margin-bottom:10px;'>
                Reset your Nextrade password
            </h2>

            <p style='font-size:16px; color:#333;'>
                We received a request to reset the password for your Nextrade account.
            </p>

            <p style='font-size:16px; color:#333;'>
                Click the button below to create a new password.
            </p>

            <p style='text-align:center; margin:30px 0;'>
                <a href='$resetLink'
                   style='background-color:#1e3a8a;
                          color:white;
                          padding:14px 24px;
                          text-decoration:none;
                          border-radius:6px;
                          font-weight:bold;
                          display:inline-block;'>
                    Reset Password
                </a>
            </p>

            <p style='font-size:14px; color:#555;'>
                This password reset link will expire in 1 hour.
            </p>

            <p style='font-size:14px; color:#555;'>
                If you did not request this password reset, you can safely ignore this email.
            </p>

            <hr style='border:none; border-top:1px solid #ddd; margin:25px 0;'>

            <p style='font-size:13px; color:#777; text-align:center;'>
                Nextrade Marketplace<br>
                Buy and sell safely online.
            </p>

        </div>

    </div>
    ";

    $mail->AltBody = "
Reset your Nextrade password

We received a request to reset the password for your Nextrade account.

Use this link to reset your password:
$resetLink

This link expires in 1 hour.

If you did not request this password reset, you can ignore this email.

Nextrade Marketplace
";

    $mail->send();

    $message = "Password reset email sent successfully. Please check your inbox.";
    $message_type = "success";

}
catch(Exception $e){

    $message = "Unable to send email. Please try again later.";
    $message_type = "error";
}

        // Later send this by email instead
    }
    else{
      $message = "No account exists with that email address.";
$message_type = "error";
    }
}
?>
<html>
    <head>
        <title>NexTrade</title>
<link rel="stylesheet" href="CSS/Css_Forgot_Password_Page.css?v=<?php echo time(); ?>">
        <link rel="icon" type="image/png" sizes="512x512" href="uploads/icon.png">
</head>
        <body>
<div class="forgot_container">

    <h1>Forgot Password</h1>

    <p class="description">
        Enter your email address and we'll send you a password reset link.
    </p>

    <?php if(!empty($message)){ ?>
    <div class="message <?php echo $message_type; ?>">
        <?php echo $message; ?>
    </div>
<?php } ?>
    <form method="post">

        <div class="input_group">
            <label>Email Address</label>
            <input type="email" name="email" required>
        </div>

        <button type="submit" name="submit" class="reset_btn">
            Send Reset Link
        </button>

        <div class="back_login">
            <a href="Log In Page.php">Back to Login</a>
        </div>

    </form>

</div>
</body>
</html>