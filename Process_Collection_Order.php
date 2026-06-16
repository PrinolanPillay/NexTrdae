<?php
include("Start_session.php");

if(!isset($_SESSION["login_id"])){
    header("Location: Log In Page.php");
    exit();
}

if(empty($_SESSION['cart'])){
    echo "Cart is empty";
    exit();
}

$login_id = $_SESSION["login_id"];
$cart = $_SESSION['cart'];

$grand_total = 0;

// Calculate total
foreach($cart as $product_id => $quantity){

    $stmt = $conn->prepare("
        SELECT product_price 
        FROM product_list 
        WHERE product_id = ?
    ");

    $stmt->bind_param("i", $product_id);
    $stmt->execute();

    $product = $stmt->get_result()->fetch_assoc();

    if($product){
        $grand_total += $product['product_price'] * $quantity;
    }
}

// Create order
$stmt = $conn->prepare("
    INSERT INTO orders 
    (login_id, total_price, payment_status, created_at)
    VALUES (?, ?, 'Pay On Collection', NOW())
");

$stmt->bind_param("id", $login_id, $grand_total);
$stmt->execute();

$order_id = $stmt->insert_id;

// Insert order items
foreach($cart as $product_id => $quantity){

    $stmt = $conn->prepare("
        SELECT product_price 
        FROM product_list 
        WHERE product_id = ?
    ");

    $stmt->bind_param("i", $product_id);
    $stmt->execute();

    $product = $stmt->get_result()->fetch_assoc();

    if($product){

        $price = $product['product_price'];

        $stmt = $conn->prepare("
         INSERT INTO order_items
	(
		order_id,
	product_id,
	quantity,
	price,
	order_status,
	reserved_until
	)
	VALUES
	(
	?,
	?,
	?,
	?,
	'Reserved',
	DATE_ADD(NOW(), INTERVAL 24 HOUR)
	)");
        $stmt->bind_param(
            "iiid",
            $order_id,
            $product_id,
            $quantity,
            $price
        );

        $stmt->execute();

        $reserved_until = date("Y-m-d H:i:s", strtotime("+24 hours"));

        $stmt = $conn->prepare("
            UPDATE cart_reservations
            SET reserved_until = ?
            WHERE login_id = ?
            AND product_id = ?
        ");

        $stmt->bind_param(
            "sii",
            $reserved_until,
            $login_id,
            $product_id
        );

        $stmt->execute();
    }
}

// Clear cart session only
$_SESSION['cart'] = [];

header("Location: Orders_Page.php");
exit();
?>