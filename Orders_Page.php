<?php
include("Start_session.php");

if(!isset($_SESSION["login_id"])){
    header("Location: Log In Page.php");
    exit();
}

$login_id = $_SESSION["login_id"];

// CONFIRM DELIVERED

if(isset($_POST['confirm_delivered'])){

    $order_item_id = $_POST['order_item_id'];

    $check_query = "

    SELECT 
    orders.login_id AS buyer_id,
    product_list.login_id AS seller_id,
    order_items.buyer_delivered,
    order_items.seller_delivered

    FROM order_items

    INNER JOIN orders
    ON order_items.order_id = orders.order_id

    INNER JOIN product_list
    ON order_items.product_id = product_list.product_id
    
    WHERE order_items.order_item_id = ?

    ";

    $stmt = $conn->prepare($check_query);

    $stmt->bind_param(
        "i",
        $order_item_id
    );

    $stmt->execute();

    $result = $stmt->get_result();

    if($result->num_rows > 0){

        $delivery_row = $result->fetch_assoc();

        $buyer_id = $delivery_row['buyer_id'];

        $seller_id = $delivery_row['seller_id'];

        $buyer_delivered = $delivery_row['buyer_delivered'];

        $seller_delivered = $delivery_row['seller_delivered'];



        //BUYER CONFIRM

        if($login_id == $buyer_id){

            $update = $conn->prepare("
            UPDATE order_items
            SET buyer_delivered = 1
            WHERE order_item_id = ?
            ");

            $update->bind_param(
                "i",
                $order_item_id
            );

            $update->execute();

            $buyer_delivered = 1;
        }

        //SELLER CONFIRM

        if($login_id == $seller_id){

            $update = $conn->prepare("
            UPDATE order_items
            SET seller_delivered = 1
            WHERE order_item_id = ?
            ");

            $update->bind_param(
                "i",
                $order_item_id
            );

            $update->execute();

            $seller_delivered = 1;
        }

        // BOTH CONFIRMED

        if($buyer_delivered == 1 && $seller_delivered == 1){

            $final = $conn->prepare("
            UPDATE order_items
            SET order_status = 'Delivered'
            WHERE order_item_id = ?
            ");

            $final->bind_param(
                "i",
                $order_item_id
            );

            $final->execute();
        }
    }
}
// EXPIRE PAY ON COLLECTION ORDERS

$expired_orders = $conn->query("
SELECT order_item_id, product_id, quantity
FROM order_items
WHERE order_status = 'Reserved'
AND reserved_until < NOW()
");

while($expired = $expired_orders->fetch_assoc()){

    $stmt = $conn->prepare("
        UPDATE product_list
        SET quantity = quantity + ?
        WHERE product_id = ?
    ");

    $stmt->bind_param(
        "ii",
        $expired['quantity'],
        $expired['product_id']
    );

    $stmt->execute();

    $stmt = $conn->prepare("
        UPDATE order_items
        SET order_status = 'Expired'
        WHERE order_item_id = ?
    ");

    $stmt->bind_param(
        "i",
        $expired['order_item_id']
    );

    $stmt->execute();
}

// UPDATE TRACKING + STATUS

if(isset($_POST['update_tracking'])){

    $order_item_id = $_POST['order_item_id'];

    $tracking_number = $_POST['tracking_number'];

    $tracking_url = $_POST['tracking_url'];

    $order_status = $_POST['order_status'];

    // VERIFY SELLER

    $check_query = "

    SELECT 
    order_items.*,
    product_list.login_id

    FROM order_items

    INNER JOIN product_list
    ON order_items.product_id = product_list.product_id

    WHERE order_items.order_item_id = ?
    AND product_list.login_id = ?

    ";

    $check_stmt = $conn->prepare($check_query);

    $check_stmt->bind_param(
        "ii",
        $order_item_id,
        $login_id
    );

    $check_stmt->execute();

    $check_result = $check_stmt->get_result();

    //UPDATE ONLY IF USER IS SELLER

    if($check_result->num_rows > 0){

        $order_data = $check_result->fetch_assoc();

        // LOCK ORDER AFTER BOTH CONFIRMED

        if(
            $order_data['buyer_delivered'] == 1 &&
            $order_data['seller_delivered'] == 1 &&
            $order_data['order_status'] == "Delivered"
        ){

            // ORDER LOCKED 

        }else{

            $stmt = $conn->prepare("
            UPDATE order_items
            SET tracking_number = ?,
                tracking_url = ?,
                order_status = ?
            WHERE order_item_id = ?
            ");

            $stmt->bind_param(
                "sssi",
                $tracking_number,
                $tracking_url,
                $order_status,
                $order_item_id
            );

            $stmt->execute();
        }
    }
}
// SUBMIT REVIEW

if(isset($_POST['submit_review'])){

    $order_item_id = $_POST['order_item_id'];
   $rating = max(
    1,
    min(
        5,
        (int)$_POST['rating']
    )
);
    $review_text = trim($_POST['review_text']);

    // VERIFY BUYER 

    $query = "

    SELECT
    orders.login_id AS buyer_id,
    product_list.login_id AS seller_id,
    order_items.product_id,
    order_items.order_status

    FROM order_items

    INNER JOIN orders
    ON order_items.order_id = orders.order_id

    INNER JOIN product_list
    ON order_items.product_id = product_list.product_id

    WHERE order_items.order_item_id = ?

    ";

    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $order_item_id);
    $stmt->execute();

    $result = $stmt->get_result();

    if($result->num_rows > 0){

        $row = $result->fetch_assoc();

        if(
            $row['buyer_id'] == $login_id &&
            $row['order_status'] == 'Delivered'
        ){

            // CHECK IF ALREADY REVIEWED

            $check = $conn->prepare("
            SELECT review_id
            FROM reviews
            WHERE order_item_id = ?
            ");

            $check->bind_param(
                "i",
                $order_item_id
            );

            $check->execute();

            if($check->get_result()->num_rows == 0){

                // INSERT REVIEW 

                $insert = $conn->prepare("
                INSERT INTO reviews
                (
                    order_item_id,
                    product_id,
                    seller_id,
                    buyer_id,
                    rating,
                    review_text
                )
                VALUES
                (?, ?, ?, ?, ?, ?)
                ");

                $insert->bind_param(
                    "iiiiis",
                    $order_item_id,
                    $row['product_id'],
                    $row['seller_id'],
                    $login_id,
                    $rating,
                    $review_text
                );

                $insert->execute();

                // UPDATE SELLER RATING

                $updateSeller = $conn->prepare("
                UPDATE personal_info
                SET
                    total_reviews = total_reviews + 1,
                    rating_sum = rating_sum + ?
                WHERE login_id = ?
                ");

                $updateSeller->bind_param(
                    "ii",
                    $rating,
                    $row['seller_id']
                );

                $updateSeller->execute();
                header("Location: Orders_Page.php");
                exit();
            }
        }
    }
}
?>


<html>

<head>
    <title>NexTrade</title>
 <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet"
href="CSS/Css_Order_Page.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="uploads/icon.png">

</head>

<body>

<div class="layout">

<nav class="sidebar">

<h1 class="heading">My Account</h1>

<ul class="navlist">

<li><a href="AccountPage.php">Personal Details</a></li>

<li><a href="Security_Page.php">Security</a></li>

<li><a href="Orders_Page.php">Orders</a></li>

<li><a href="MyProductPage.php">My Products</a></li>

<li><a href="index.php">HomePage</a></li>

<li><a href="LogOut.php">Logout</a></li>

<li>
<a href="Delete_Account.php"
onclick="return confirm('Are you sure you want to delete your account? This cannot be undone.');">
Delete Account
</a>
</li>

</ul>

</nav>

<div class="content">

<h1 class="page_title">Orders</h1>

<?php
//Order info
$query = "

SELECT 

orders.order_id,
orders.login_id AS buyer_id,
orders.total_price,
orders.payment_status,
orders.created_at,
order_items.order_item_id,
order_items.quantity,
order_items.price,
order_items.order_status,
order_items.reserved_until,
order_items.tracking_number,
order_items.tracking_url,
order_items.buyer_delivered,
order_items.seller_delivered,
product_list.product_id,
product_list.product_name,
product_list.product_image,
product_list.login_id AS seller_id,
reviews.rating,
reviews.review_text



FROM order_items

INNER JOIN orders
ON order_items.order_id = orders.order_id

INNER JOIN product_list
ON order_items.product_id = product_list.product_id

LEFT JOIN reviews
ON order_items.order_item_id = reviews.order_item_id

WHERE orders.login_id = ?
OR product_list.login_id = ?

ORDER BY orders.created_at DESC

";

$stmt = $conn->prepare($query);

$stmt->bind_param(
    "ii",
    $login_id,
    $login_id
);

$stmt->execute();

$result = $stmt->get_result();

if($result->num_rows == 0){

?>

<div class="no_orders">

    <h3>No Orders Yet</h3>

    <p>No purchases or sales found.</p>

</div>

<?php

}else{

while($row = $result->fetch_assoc()){

$is_buyer = ($row['buyer_id'] == $login_id);

$is_seller = ($row['seller_id'] == $login_id);

?>

<div class="order_box">

<div class="order_header">

<?php if($is_seller): ?>

<p class="sold_label">Sold</p>

<?php endif; ?>

<?php if($is_buyer): ?>

<p class="purchased_label">Purchased</p>

<?php endif; ?>

<h2>

Order #<?php echo $row['order_id']; ?>

</h2>

<p>

Date:
<?php echo $row['created_at']; ?>

</p>

<p>

Payment:
<?php echo $row['payment_status']; ?>

</p>

<p>

Status:
<?php echo $row['order_status']; ?>
<?php if($row['payment_status'] == "Pay On Collection" && !empty($row['reserved_until'])): ?>

<p>
Reserved Until:
<?php echo date("d M Y H:i", strtotime($row['reserved_until'])); ?>
</p>

<?php endif; ?>
</p>

<?php if(!empty($row['tracking_number'])): ?>

<p>

Tracking Number:
<?php echo $row['tracking_number']; ?>

</p>

<?php endif; ?>

<?php if(!empty($row['tracking_url'])): ?>

<a href="<?php echo $row['tracking_url']; ?>"
   target="_blank"
   class="track_button">

Track Package

</a>

<?php endif; ?>

</div>

<div class="order_item">

<img src="<?php echo $row['product_image']; ?>"
     class="order_image">

<div class="order_info">

<h3>

<?php echo $row['product_name']; ?>

</h3>

<p>

Quantity:
<?php echo $row['quantity']; ?>

</p>

<p>

Price:
R<?php echo $row['price']; ?>

</p>
<?php if($is_seller): ?>

<div class="seller_review_box">

    <h4>Buyer Review</h4>
    <br>

    <?php if(!empty($row['rating'])): ?>

        <p>
            Rating:
            <?php echo $row['rating']; ?> / 5 Stars
        </p>

        <p>
            Review:
            <?php echo htmlspecialchars($row['review_text']); ?>
        </p>

    <?php else: ?>

        <p>No review left yet.</p>

    <?php endif; ?>

</div>

<?php endif; ?>

</div>

</div>

<?php if($is_seller): ?>

<form method="POST" class="tracking_form">

<input type="hidden"
       name="order_item_id"
       value="<?php echo $row['order_item_id']; ?>">

<input type="text"
       name="tracking_number"
       placeholder="Tracking Number"
       value="<?php echo $row['tracking_number']; ?>">

<input type="url"
       name="tracking_url"
       placeholder="Shipping Tracking URL"
       value="<?php echo $row['tracking_url']; ?>">

<select name="order_status">

<option value="Paid"
<?php if($row['order_status']=="Paid") echo "selected"; ?>>

Paid

</option>

<option value="Shipped"
<?php if($row['order_status']=="Shipped") echo "selected"; ?>>

Shipped

</option>

</select>

<button type="submit"
        name="update_tracking">

Update Order

</button>

</form>

<?php endif; ?>

<?php

$buyer_confirmed = $row['buyer_delivered'];
$seller_confirmed = $row['seller_delivered'];

?>

<div class="delivery_confirmation">

<p>

Buyer Confirmed:
<?php echo ($buyer_confirmed ? "Yes" : "No"); ?>

</p>

<p>

Seller Confirmed:
<?php echo ($seller_confirmed ? "Yes" : "No"); ?>

</p>

<?php if( $row['order_status'] == "Shipped" || $row['order_status'] == "Delivered" ||( $row['payment_status'] == "Pay On Collection" &&$row['order_status'] == "Reserved" && !empty($row['reserved_until']) && strtotime($row['reserved_until']) > time() )): ?>

<?php

$already_confirmed = false;

if($is_buyer && $buyer_confirmed){
    $already_confirmed = true;
}

if($is_seller && $seller_confirmed){
    $already_confirmed = true;
}

?>

<?php if(!$already_confirmed): ?>

<form method="POST">

<input type="hidden"
       name="order_item_id"
       value="<?php echo $row['order_item_id']; ?>">

<button type="submit"
        name="confirm_delivered"
        class="delivered_button">

Confirm Collection / Delivery

</button>

</form>

<?php else: ?>

<p class="confirmed_text">

You confirmed delivery.

</p>

<?php endif; ?>

<?php endif; ?>

<?php

if(
    $is_buyer &&
    $row['order_status'] == "Delivered"
){

    $review_check = $conn->prepare("
    SELECT review_id
    FROM reviews
    WHERE order_item_id = ?
    ");

    $review_check->bind_param(
        "i",
        $row['order_item_id']
    );

    $review_check->execute();

    $already_reviewed =
    $review_check->get_result()->num_rows > 0;

    if(!$already_reviewed):

?>

<form method="POST" class="review_form">

    <input type="hidden"
           name="order_item_id"
           value="<?php echo $row['order_item_id']; ?>">

    <label>Rating</label>

    <select name="rating" required>

        <option value="5">5 Stars</option>
        <option value="4">4 Stars</option>
        <option value="3">3 Stars</option>
        <option value="2">2 Stars</option>
        <option value="1">1 Star</option>

    </select>

    <textarea
        name="review_text"
        placeholder="Write a review..."
        required></textarea>

    <button type="submit"
            name="submit_review">

        Submit Review

    </button>

</form>

<?php else: ?>

<p class="review_submitted">
Review Submitted
</p>

<?php
    endif;
}
?>
</div>

</div>

<?php
}
}
?>

</div>

</div>
<a href="Customer_Support.php" class="floating_support">
    💬
</a>
</body>

</html>