<?php
include("Start_session.php");

if(!isset($_SESSION["login_id"])){
    header("Location: Log In Page.php");
    exit();
}

/* VERIFY ADMIN */
$adminStmt = $conn->prepare("
SELECT access_level
FROM admin
WHERE login_id = ?
");

$adminStmt->bind_param("i", $_SESSION["login_id"]);
$adminStmt->execute();

if($adminStmt->get_result()->num_rows == 0){
    header("Location: HomePage.php");
    exit();
}

/* GET ORDER ID */
if(!isset($_GET['id']) || !is_numeric($_GET['id'])){
    header("Location: Admin_Order.php");
    exit();
}

$order_id = (int)$_GET['id'];

$conn->begin_transaction();

try {

    /* DELETE REVIEWS FOR THIS ORDER */
    $deleteReviews = $conn->prepare("
    DELETE reviews
    FROM reviews

    INNER JOIN order_items
    ON reviews.order_item_id = order_items.order_item_id

    WHERE order_items.order_id = ?
    ");

    $deleteReviews->bind_param("i", $order_id);
    $deleteReviews->execute();

    /* DELETE ORDER ITEMS */
    $deleteItems = $conn->prepare("
    DELETE FROM order_items
    WHERE order_id = ?
    ");

    $deleteItems->bind_param("i", $order_id);
    $deleteItems->execute();

    /* DELETE ORDER */
    $deleteOrder = $conn->prepare("
    DELETE FROM orders
    WHERE order_id = ?
    ");

    $deleteOrder->bind_param("i", $order_id);
    $deleteOrder->execute();

    $conn->commit();

    header("Location: Admin_Order.php?deleted=1");
    exit();

} catch (Exception $e) {

    $conn->rollback();

    echo "Error deleting order: " . $e->getMessage();
}
?>