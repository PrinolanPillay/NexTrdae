<?php
include("Start_session.php");

if(!isset($_SESSION["login_id"])){
    header("Location: Log In Page.php");
    exit();
}
$login_id = $_SESSION["login_id"];

// 1. Delete products first (child table)
$stmt1 = $conn->prepare("DELETE FROM product_list WHERE login_id = ?");
$stmt1->bind_param("i", $login_id);
$stmt1->execute();

// 2. Delete personal info (child table)
$stmt2 = $conn->prepare("DELETE FROM personal_info WHERE login_id = ?");
$stmt2->bind_param("i", $login_id);
$stmt2->execute();

// 3. Now delete the account (parent table)
$stmt3 = $conn->prepare("DELETE FROM login_info WHERE login_id = ?");
$stmt3->bind_param("i", $login_id);

if ($stmt3->execute()) {

    session_destroy(); // log user out

    header("Location: HomePage.php");
    exit();

} else {
    echo "Error deleting account.";
}

?>