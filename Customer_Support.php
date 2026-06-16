<?php
include("Start_session.php");

if(!isset($_SESSION["login_id"])){
    header("Location: Log In Page.php");
    exit();
}

$login_id = $_SESSION["login_id"];

/* CREATE TICKET */

if(isset($_POST["create_ticket"])){

    $subject = trim($_POST["subject"]);
    $message = trim($_POST["message"]);

    $stmt = $conn->prepare("
    INSERT INTO customer_support
    (login_id, subject, message)
    VALUES (?, ?, ?)
    ");

    $stmt->bind_param("iss",$login_id,$subject,$message);
    $stmt->execute();

    $support_id = $conn->insert_id;

    $msgStmt = $conn->prepare("
    INSERT INTO support_messages
    (support_id,sender,message)
    VALUES (?, 'Customer', ?)
    ");

    $msgStmt->bind_param("is",$support_id,$message);
    $msgStmt->execute();

    header("Location: Support_Chat.php?id=".$support_id);
    exit();
}

/* GET TICKETS */

$stmt = $conn->prepare("
SELECT *
FROM customer_support
WHERE login_id = ?
ORDER BY created_at DESC
");

$stmt->bind_param("i",$login_id);
$stmt->execute();

$result = $stmt->get_result();
?>

<html>
<head>
<title>NexTrade</title>
<link rel="stylesheet" href="CSS/Css_Customer_Support_Page.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="uploads/icon.png">
</head>

<body>

<div class="container">
<a href="index.php" class="back_btn">
    ← Back
</a>
<h1>Customer Support</h1>

<div class="new_ticket">

<h2>Create Ticket</h2>

<form method="POST">

<input
type="text"
name="subject"
placeholder="Subject"
required>

<textarea
name="message"
placeholder="Describe your issue..."
required
></textarea>

<button
type="submit"
name="create_ticket">
Create Ticket
</button>

</form>

</div>

<h2>Your Tickets</h2>

<div class="tickets_container">

<?php while($row = $result->fetch_assoc()) { ?>

<a
href="Support_Chat.php?id=<?php echo $row['support_id']; ?>"
class="ticket_card"
>

<div class="ticket_header">
    <h3><?php echo htmlspecialchars($row['subject']); ?></h3>

    <span class="ticket_status status_<?php echo strtolower($row['status']); ?>">
        <?php echo $row['status']; ?>
    </span>
</div>

<div class="ticket_info">
    Ticket #<?php echo $row['support_id']; ?>
</div>

<div class="ticket_date">
    Created:
    <?php echo date("d M Y H:i", strtotime($row['created_at'])); ?>
</div>

</a>

<?php } ?>

</div>

</div>

</body>
</html>