<?php
include("Start_session.php");

if(!isset($_SESSION["login_id"])){
    header("Location: Log In Page.php");
    exit();
}

if(!isset($_GET['id'])){
    die("Invalid Ticket");
}

$support_id = $_GET['id'];

/* GET TICKET */

$stmt = $conn->prepare("
SELECT
    customer_support.*,
    personal_info.Firstname,
    personal_info.Surname,
    personal_info.email

FROM customer_support

JOIN personal_info
ON customer_support.login_id = personal_info.login_id

WHERE customer_support.support_id = ?
");

$stmt->bind_param("i",$support_id);
$stmt->execute();

$ticket = $stmt->get_result()->fetch_assoc();

if(!$ticket){
    die("Ticket not found");
}

/* SEND MESSAGE */

if(isset($_POST['send_message'])){

    if($ticket['status'] == "Open"){

        $message = trim($_POST['message']);

        if(!empty($message)){

            $stmt = $conn->prepare("
            INSERT INTO support_messages
            (support_id, sender, message)
            VALUES (?, 'Admin', ?)
            ");

            $stmt->bind_param("is",$support_id,$message);
            $stmt->execute();
        }
    }

    header("Location: Admin_Support_Chat.php?id=".$support_id);
    exit();
}

/* RESOLVE TICKET */

if(isset($_POST['resolve_ticket'])){

    $stmt = $conn->prepare("
    UPDATE customer_support
    SET status='Resolved'
    WHERE support_id=?
    ");

    $stmt->bind_param("i",$support_id);
    $stmt->execute();

    header("Location: Admin_Support_Chat.php?id=".$support_id);
    exit();
}

/* LOAD CHAT */

$stmt = $conn->prepare("
SELECT *
FROM support_messages
WHERE support_id = ?
ORDER BY sent_at ASC
");

$stmt->bind_param("i",$support_id);
$stmt->execute();

$messages = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>

<title>NexTrade</title>

<link rel="stylesheet"
href="CSS/Css_Admin_Support_Caht.css?v=<?php echo time(); ?>">
<link rel="icon" type="image/png" sizes="512x512" href="uploads/icon.png">
</head>

<body>

<div class="chat_container">

<div class="chat_header">

<a href="Admin_Support.php" class="back_btn">
← Back
</a>

<h2>
Ticket #<?php echo $ticket['support_id']; ?>
</h2>

<div class="customer_info">

<strong>
<?php echo htmlspecialchars($ticket['Firstname']." ".$ticket['Surname']); ?>
</strong>

<br>

<?php echo htmlspecialchars($ticket['email']); ?>

<br><br>

<b>Subject:</b>
<?php echo htmlspecialchars($ticket['subject']); ?>

<br>

<b>Status:</b>

<?php if($ticket['status'] == "Open"){ ?>

<span class="status_open">
Open
</span>

<?php } else { ?>

<span class="status_resolved">
Resolved
</span>

<?php } ?>

</div>

</div>

<div class="messages_container">

<?php while($msg = $messages->fetch_assoc()) { ?>

<?php if($msg['sender'] == "Admin"){ ?>

<div class="admin_message">

<div class="message_sender">
Admin
</div>

<div class="message_text">
<?php echo nl2br(htmlspecialchars($msg['message'])); ?>
</div>

<div class="message_time">
<?php echo $msg['sent_at']; ?>
</div>

</div>

<?php } else { ?>

<div class="customer_message">

<div class="message_sender">
Customer
</div>

<div class="message_text">
<?php echo nl2br(htmlspecialchars($msg['message'])); ?>
</div>

<div class="message_time">
<?php echo $msg['sent_at']; ?>
</div>

</div>

<?php } ?>

<?php } ?>

</div>

<?php if($ticket['status'] == "Open"){ ?>

<div class="reply_section">

<form method="POST">

<textarea
name="message"
placeholder="Type your reply..."
required
></textarea>

<button
type="submit"
name="send_message">
Send Reply
</button>

</form>

<form method="POST">

<button
type="submit"
name="resolve_ticket"
class="resolve_btn"
onclick="return confirm('Mark this ticket as resolved?');">
Mark Resolved
</button>

</form>

</div>

<?php } else { ?>

<div class="resolved_notice">
This ticket has been resolved.
</div>

<?php } ?>

</div>

</body>
</html>