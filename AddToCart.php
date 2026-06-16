<?php
include("Start_session.php");

if(!isset($_SESSION["login_id"])){
    header("Location: Log In Page.php");
    exit();
}

if(!isset($_POST['product_id'])){
    header("Location: Main_Page.php");
    exit();
}

$product_id = (int)$_POST['product_id'];
$login_id = $_SESSION['login_id'];

if(!isset($_SESSION['cart'])){
    $_SESSION['cart'] = [];
}

/* CHECK STOCK */

$stmt = $conn->prepare("
SELECT quantity
FROM product_list
WHERE product_id = ?
");

$stmt->bind_param("i", $product_id);
$stmt->execute();

$product = $stmt->get_result()->fetch_assoc();

if(!$product || $product['quantity'] <= 0){
    $_SESSION['error'] = "Product is out of stock.";
    header("Location: Cart.php");
    exit();
}

/* REDUCE STOCK SAFELY */

$stmt = $conn->prepare("
UPDATE product_list
SET quantity = quantity - 1
WHERE product_id = ?
AND quantity > 0
");

$stmt->bind_param("i", $product_id);
$stmt->execute();

if($stmt->affected_rows === 0){
    $_SESSION['error'] = "Product is out of stock.";
    header("Location: Cart.php");
    exit();
}

/* RESERVATION EXPIRY */

$reserved_until = date(
    "Y-m-d H:i:s",
    strtotime("+1 hour")
);

/* CHECK IF RESERVATION ALREADY EXISTS */

$stmt = $conn->prepare("
SELECT reservation_id, quantity
FROM cart_reservations
WHERE login_id = ?
AND product_id = ?
");

$stmt->bind_param(
    "ii",
    $login_id,
    $product_id
);

$stmt->execute();

$reservation = $stmt->get_result()->fetch_assoc();

/* UPDATE OR INSERT RESERVATION */

if($reservation){

    $stmt = $conn->prepare("
    UPDATE cart_reservations
    SET quantity = quantity + 1,
        reserved_until = ?
    WHERE reservation_id = ?
    ");

    $stmt->bind_param(
        "si",
        $reserved_until,
        $reservation['reservation_id']
    );

    $stmt->execute();

} else {

    $stmt = $conn->prepare("
    INSERT INTO cart_reservations
    (login_id, product_id, quantity, reserved_until)
    VALUES (?, ?, 1, ?)
    ");

    $stmt->bind_param(
        "iis",
        $login_id,
        $product_id,
        $reserved_until
    );

    $stmt->execute();
}

/* UPDATE SESSION CART */

$_SESSION['cart'][$product_id] =
    ($_SESSION['cart'][$product_id] ?? 0) + 1;

/* REDIRECT */

header("Location: Cart.php");
exit();
?>