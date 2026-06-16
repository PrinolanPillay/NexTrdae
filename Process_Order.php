<?php

include("Start_session.php");

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* CHECK LOGIN */

if(!isset($_SESSION["login_id"])){

    die("User not logged in");
}

/* CHECK CART */

if(empty($_SESSION['cart'])){

    die("Cart is empty");
}

$user_id = $_SESSION["login_id"];

$cart = $_SESSION['cart'];

/* GET PRODUCTS FROM CART */

$ids = implode(",", array_keys($cart));

$query = "
SELECT *
FROM product_list
WHERE product_id IN ($ids)
";

$result = $conn->query($query);

if(!$result){

    die($conn->error);
}

/* CALCULATE GRAND TOTAL */

$grand_total = 0;

while($row = $result->fetch_assoc()){

    $quantity = $cart[$row['product_id']];

    $grand_total += ($row['product_price'] * $quantity);
}

/* CREATE ORDER */

$payment_status = "Paid";

$stmt = $conn->prepare("
INSERT INTO orders(

    login_id,
    total_price,
    payment_status

)

VALUES (?, ?, ?)
");

if(!$stmt){

    die($conn->error);
}

$stmt->bind_param(
    "ids",
    $user_id,
    $grand_total,
    $payment_status
);

if(!$stmt->execute()){

    die($stmt->error);
}

/* GET ORDER ID */

$order_id = $conn->insert_id;

/* GET PRODUCTS AGAIN */

$result = $conn->query($query);

if(!$result){

    die($conn->error);
}

/* INSERT ORDER ITEMS */

while($row = $result->fetch_assoc()){

    $product_id = $row['product_id'];

    $quantity = $cart[$product_id];

    $price = $row['product_price'];

    $tracking_number = "";

    $order_status = "Paid";

    /* INSERT INTO ORDER ITEMS */

    $stmt2 = $conn->prepare("
    INSERT INTO order_items(

        order_id,
        product_id,
        quantity,
        price,
        tracking_number,
        order_status

    )

    VALUES (?, ?, ?, ?, ?, ?)
    ");

if(!$stmt2){
    die($conn->error);
}
    $stmt2->bind_param(
        "iiidss",
        $order_id,
        $product_id,
        $quantity,
        $price,
        $tracking_number,
        $order_status
    );

   $stmt2->execute();

   
}

/* CLEAR CART */

unset($_SESSION['cart']);


/* Clear reservation*/
$stmt = $conn->prepare("
DELETE FROM cart_reservations
WHERE login_id = ?
");

$stmt->bind_param(
    "i",
    $_SESSION['login_id']
);

$stmt->execute();
/* SUCCESS */

echo "success";

?>