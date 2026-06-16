<?php
include("Start_session.php");

if(!isset($_SESSION["login_id"])){
    header("Location: Log In Page.php");
    exit();
}

$login_id = $_SESSION["login_id"];
$support_id = $_GET["id"];

/* VERIFY OWNERSHIP */

$stmt = $conn->prepare("
SELECT *
FROM customer_support
WHERE support_id = ?
AND login_id = ?
");

$stmt->bind_param("ii",$support_id,$login_id);
$stmt->execute();

$ticket = $stmt->get_result()->fetch_assoc();

if(!$ticket){
    die("Access denied");
}

/* SEND MESSAGE */

if(isset($_POST["send_message"])
&& $ticket['status'] == "Open"){

    $message = trim($_POST["message"]);

    $stmt = $conn->prepare("
    INSERT INTO support_messages
    (support_id,sender,message)
    VALUES (?, 'Customer', ?)
    ");

    $stmt->bind_param("is",$support_id,$message);
    $stmt->execute();

    header("Location: Support_Chat.php?id=".$support_id);
    exit();
}

/* RESOLVE */

if(isset($_POST["resolve_ticket"])){

    $stmt = $conn->prepare("
    UPDATE customer_support
    SET status='Resolved'
    WHERE support_id=?
    ");

    $stmt->bind_param("i",$support_id);
    $stmt->execute();

    header("Location: Support_Chat.php?id=".$support_id);
    exit();
}

/* GET MESSAGES */

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

<html>
<head>
<title>NexTrade -Support Chat</title>
<link rel="stylesheet" href="CSS/Css_Support_Chat.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="uploads/icon.png">
</head>

<body>

<div class="chat_container">

    <div class="chat_header">
<a href="Customer_Support.php" class="back_btn">
    ← Back to Tickets
</a>
        <h2>
            <?php echo htmlspecialchars($ticket['subject']); ?>
        </h2>

        <div class="ticket_info">

            <span>
                Ticket #<?php echo $ticket['support_id']; ?>
            </span>

            <span class="status_<?php echo strtolower($ticket['status']); ?>">
                <?php echo $ticket['status']; ?>
            </span>

        </div>

    </div>

    <div class="messages_container">

        <?php while($msg = $messages->fetch_assoc()) { ?>

            <div class="<?php echo strtolower($msg['sender']); ?>_message">

                <div class="message_sender">
                    <?php echo htmlspecialchars($msg['sender']); ?>
                </div>

                <div class="message_text">
                    <?php echo nl2br(htmlspecialchars($msg['message'])); ?>
                </div>

                <?php if(isset($msg['sent_at'])){ ?>
                    <div class="message_time">
                        <?php echo date("d M Y H:i", strtotime($msg['sent_at'])); ?>
                    </div>
                <?php } ?>

            </div>

        <?php } ?>

    </div>

    <?php if($ticket['status']=="Open"){ ?>

        <div class="reply_section">

            <form method="POST">

                <textarea
                    name="message"
                    required
                    placeholder="Type your message..."
                ></textarea>

                <button
                    type="submit"
                    name="send_message">
                    Send Message
                </button>

            </form>

            <form method="POST">

                <button
                    type="submit"
                    name="resolve_ticket"
                    class="resolve_btn">
                    Mark Ticket Resolved
                </button>

            </form>

        </div>

    <?php } else { ?>

        <div class="resolved_notice">
            ✓ This ticket has been resolved
        </div>

    <?php } ?>

</div>

<script>
window.onload = function() {
    const chat = document.querySelector('.messages_container');
    if(chat){
        chat.scrollTop = chat.scrollHeight;
    }
};
</script>

</body>
</html>