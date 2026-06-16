	<?php 
include("Start_session.php");

if(!isset($_SESSION["login_id"])){
    header("Location: Log In Page.php");
    exit();
}

// CREATE CART IF IT DOESN'T EXIST
if(!isset($_SESSION['cart'])){
    $_SESSION['cart'] = [];
}

$stmt = $conn->prepare("
SELECT product_id, quantity
FROM cart_reservations
WHERE login_id = ?
AND reserved_until < NOW()
");

$stmt->bind_param(
    "i",
    $_SESSION['login_id']
);

$stmt->execute();

$expired = $stmt->get_result();

while($row = $expired->fetch_assoc()){

    $product_id = $row['product_id'];
    $qty = $row['quantity'];

    $update = $conn->prepare("
    UPDATE product_list
    SET quantity = quantity + ?
    WHERE product_id = ?
    ");

    $update->bind_param(
        "ii",
        $qty,
        $product_id
    );

    $update->execute();

    unset($_SESSION['cart'][$product_id]);
}

$stmt = $conn->prepare("
DELETE FROM cart_reservations
WHERE login_id = ?
AND reserved_until < NOW()
");

$stmt->bind_param(
    "i",
    $_SESSION['login_id']
);

$stmt->execute();








// UPDATE QUANTITY
if(isset($_POST['action'])){

    $product_id = (int)$_POST['product_id'];
    $login_id = $_SESSION['login_id'];

    /* INCREASE */
    if($_POST['action'] == "increase"){

        $stmt = $conn->prepare("
        SELECT quantity
        FROM product_list
        WHERE product_id = ?
        ");

        $stmt->bind_param("i", $product_id);
        $stmt->execute();

        $product = $stmt->get_result()->fetch_assoc();

        if($product && $product['quantity'] > 0){

            $stmt = $conn->prepare("
            UPDATE product_list
            SET quantity = quantity - 1
            WHERE product_id = ?
            AND quantity > 0
            ");

            $stmt->bind_param("i", $product_id);
            $stmt->execute();

            if($stmt->affected_rows > 0){

             $_SESSION['cart'][$product_id] =
    ($_SESSION['cart'][$product_id] ?? 0) + 1;

                $reserved_until = date(
                    "Y-m-d H:i:s",
                    strtotime("+1 hour")
                );

                $stmt = $conn->prepare("
                UPDATE cart_reservations
                SET quantity = quantity + 1,
                    reserved_until = ?
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
                if($stmt->affected_rows == 0){

    $insert = $conn->prepare("
    INSERT INTO cart_reservations
    (login_id, product_id, quantity, reserved_until)
    VALUES (?, ?, 1, ?)
    ");

    $insert->bind_param(
        "iis",
        $login_id,
        $product_id,
        $reserved_until
    );

    $insert->execute();
}
            }
        }
    }

    /* DECREASE */
    if($_POST['action'] == "decrease"){

        if(isset($_SESSION['cart'][$product_id])){

            $_SESSION['cart'][$product_id]--;

            $stmt = $conn->prepare("
            UPDATE product_list
            SET quantity = quantity + 1
            WHERE product_id = ?
            ");

            $stmt->bind_param("i", $product_id);
            $stmt->execute();

            $stmt = $conn->prepare("
            UPDATE cart_reservations
            SET quantity = quantity - 1
            WHERE login_id = ?
            AND product_id = ?
            ");

            $stmt->bind_param(
                "ii",
                $login_id,
                $product_id
            );

            $stmt->execute();

            $stmt = $conn->prepare("
            SELECT quantity
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

            $reservation =
                $stmt->get_result()->fetch_assoc();

            if(
                !$reservation ||
                $reservation['quantity'] <= 0
            ){

                $stmt = $conn->prepare("
                DELETE FROM cart_reservations
                WHERE login_id = ?
                AND product_id = ?
                ");

                $stmt->bind_param(
                    "ii",
                    $login_id,
                    $product_id
                );

                $stmt->execute();
            }

            if($_SESSION['cart'][$product_id] <= 0){

                unset($_SESSION['cart'][$product_id]);

            }
        }
    }

    /* REMOVE */
    if($_POST['action'] == "remove"){

        $qty =
            $_SESSION['cart'][$product_id] ?? 0;

        if($qty > 0){

            $stmt = $conn->prepare("
            UPDATE product_list
            SET quantity = quantity + ?
            WHERE product_id = ?
            ");

            $stmt->bind_param(
                "ii",
                $qty,
                $product_id
            );

            $stmt->execute();

            $stmt = $conn->prepare("
            DELETE FROM cart_reservations
            WHERE login_id = ?
            AND product_id = ?
            ");

            $stmt->bind_param(
                "ii",
                $login_id,
                $product_id
            );

            $stmt->execute();

            unset($_SESSION['cart'][$product_id]);
        }
    }

    header("Location: Cart.php");
    exit();
}

$cart = $_SESSION['cart'];



$ids_array = array_map(
    'intval',
    array_keys($cart)
);
$ids = implode(",", $ids_array);



$result = null;

$sold_out_items = [];

if(!empty($ids)){

    $query = "SELECT * FROM product_list WHERE product_id IN ($ids)";
    $result = $conn->query($query);
    
    $cart = $_SESSION['cart'];

   $ids_array = array_map(
    'intval',
    array_keys($cart)
);

    if(!empty($ids_array)){

        $ids = implode(",", $ids_array);

       $query = "
SELECT p.*, r.reserved_until
FROM product_list p
LEFT JOIN cart_reservations r
    ON p.product_id = r.product_id
    AND r.login_id = ".$_SESSION['login_id']."
WHERE p.product_id IN ($ids)
";

$result = $conn->query($query);

       

    }else{

        $result = null;

    }
}
?>

<html>
<head>
    <title>NexTrade</title>
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="CSS/Css_Cart.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="uploads/icon.png">
</head>

<body>

<header class="header">

<a href="index.php" class="logo">NexTrade</a>

<ul class="navlist">
<li><a href="Grouped_page.php?id=electronics">Electronics</a></li>
<li><a href="Grouped_page.php?id=fashion">Fashion</a></li>
<li><a href="Grouped_page.php?id=home_furniture">Home & Furniture</a></li>
<li><a href="Grouped_page.php?id=vehicle">Vehicle</a></li>
<li><a href="Grouped_page.php?id=books_education">Books & Education</a></li>
<li><a href="Grouped_page.php?id=kids_baby_items">Kids & Baby Items</a></li>
<li><a href="Grouped_page.php?id=hobbies_entertainment">Hobbies & Entertainment</a></li>
<li><a href="Grouped_page.php?id=beauty_health">Beauty & Health</a></li>

<?php if (!isset($_SESSION["login_id"])): ?>
<li><a href="Log In Page.php">Want to Sell?</a></li>
<?php endif; ?>

<li>
<?php if(isset($_SESSION["login_id"])): ?>
<a href="AccountPage.php">Account</a>
<?php else: ?>
<a href="Log In Page.php">Log in</a>
<?php endif; ?>
</li>

</ul>

</header> 
<?php if(!empty($sold_out_items)): ?>

    <div class="sold_out_message">

        <?php foreach($sold_out_items as $item): ?>

            <p>
                "<?php echo $item; ?>" was removed from your cart because it is sold out.
            </p>

        <?php endforeach; ?>

    </div>

<?php endif; ?>

<?php if(empty($cart)): ?>

<div class="empty_cart">

    <h3>Your cart is empty</h3>

    <p>Add items to your cart to see them here.</p>

    <a href="index.php" class="shop_btn">
        Continue Shopping
    </a>

</div>

<?php else: ?>
    <h2>Your Cart</h2>
<?php 

$grand_total = 0;

if($result):

while($row = $result->fetch_assoc()):

    $quantity = $cart[$row['product_id']];
    $total = $row['product_price'] * $quantity;

    $grand_total += $total;
?>

<div class="cart_item">

    <div class="cart_left">

        <h3><?php echo $row['product_name']; ?></h3>

        <p>Price: R<?php echo $row['product_price']; ?></p>
 <p class="reserved_time">
        Reserved Until:
       <?php
if(!empty($row['reserved_until'])){
    echo date(
        "d M Y H:i",
        strtotime($row['reserved_until'])
    );
} else {
    echo "No reservation";
}
?>
       
    </p>
        <div class="quantity_controls">

            <!-- DECREASE BUTTON -->
            <form method="POST">

                <input type="hidden" 
                       name="product_id"
                       value="<?php echo $row['product_id']; ?>">

                <button type="submit"
                        name="action"
                        value="decrease">
                    -
                </button>

            </form>

            <span><?php echo $quantity; ?></span>

            <!-- INCREASE BUTTON -->
            <form method="POST">

                <input type="hidden"
                       name="product_id"
                       value="<?php echo $row['product_id']; ?>">

                <button type="submit"
                        name="action"
                        value="increase">
                    +
                </button>

            </form>

        </div>

    </div>

    <div class="cart_right">

        <p>Total: R<?php echo $total; ?></p>

        <!-- REMOVE BUTTON -->
        <form method="POST">

            <input type="hidden"
                   name="product_id"
                   value="<?php echo $row['product_id']; ?>">

            <button type="submit"
                    name="action"
                    value="remove"
                    class="remove_btn">
                Remove Item
            </button>

        </form>

    </div>

</div>

<?php 
endwhile;
endif;
?>

<div class="cart_total">
    Total: R<?php echo number_format($grand_total, 2); ?>
</div>
<?php endif; ?>

<script src="https://www.paypal.com/sdk/js?client-id=ASfnJB5_9CeMREhclSjAYJPFRiZ8PIDdAunIE7kNHre5FBC7tFX4vY0s6cSTDu1GAjUUYCmRYrmAtv-4&components=buttons"></script>
    <?php if(!empty($cart)): ?>

<div class="paypal_section">

    <h3>Secure Checkout</h3>

    <p>
        Complete your purchase safely using PayPal.
    </p>

    <div id="paypal-button-container"></div>

</div>
    <?php if(!empty($cart)): ?>

<div class="collection_section">
    <h3>Pay on Collection</h3>
    <p>Reserve your items for 24 hours and pay when collecting.</p>

   <form method="POST" action="Process_Collection_Order.php">
    <button type="submit" class="collection_btn">
        Pay on Collection
    </button>

</form>
</div>

<?php endif; ?>

<script>

paypal.Buttons({

    createOrder: function(data, actions) {

        return actions.order.create({

            purchase_units: [{

                amount: {
                    value: '<?php echo number_format($grand_total / 18, 2, ".", ""); ?>'
                }

            }]

        });

    },

    onApprove: function(data, actions) {

        return actions.order.capture().then(function(details) {

            fetch("Process_Order.php", {
              method: "POST"
                                })
            .then(response => response.text())
            .then(data => {

            if(data.trim() == "success"){

        window.location.href = "Orders_Page.php";

    }else{

         console.log(data);

    alert(data);

    }

});
        });

    },

    onError: function(err) {

        console.log(err);

        alert("Payment failed");

    }

}).render('#paypal-button-container');

</script>

<?php endif; ?>
</body>
</html>