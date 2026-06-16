    <?php
        
  
    include("Start_session.php");
    $id = $_GET['id'];
//Get product info
$stmt = $conn->prepare("
SELECT 
product_list.*,
personal_info.Firstname,
personal_info.Surname,
personal_info.email,
personal_info.phone_number,
personal_info.suburb

FROM product_list

JOIN personal_info
ON product_list.login_id = personal_info.login_id

WHERE product_id = ?
");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    $result = $stmt->get_result();
    $product = $result->fetch_assoc();



if(!$product){
    die("Product not found.");
}

// GET SELLER RATING

$rating_stmt = $conn->prepare("
SELECT
    COUNT(*) AS total_reviews,
    AVG(rating) AS average_rating
FROM reviews
WHERE seller_id = ?
");

$rating_stmt->bind_param(
    "i",
    $product['login_id']
);

$rating_stmt->execute();

$rating_result = $rating_stmt->get_result();
$rating_data = $rating_result->fetch_assoc();

$total_reviews = (int)$rating_data['total_reviews'];

$average_rating = 0;

if($total_reviews > 0){
    $average_rating = round(
        $rating_data['average_rating'],
        1
    );
}

// GET RECENT REVIEWS

$reviews_stmt = $conn->prepare("
SELECT
    rating,
    review_text,
    created_at
FROM reviews
WHERE seller_id = ?
ORDER BY created_at DESC
LIMIT 5
");

$reviews_stmt->bind_param(
    "i",
    $product['login_id']
);

$reviews_stmt->execute();

$reviews = $reviews_stmt->get_result();

//Get images of product
    $stmt = $conn->prepare("
    SELECT image_path 
    FROM product_images
    WHERE product_id = ?
");

$stmt->bind_param("i", $product["product_id"]);
$stmt->execute();

$images = $stmt->get_result();


$isOwner = false;
$isAdmin = false;

if(isset($_SESSION["login_id"])){

    if($_SESSION["login_id"] == $product["login_id"]){
        $isOwner = true;
    }


if(isset($_SESSION["admin_access"]) && $_SESSION["admin_access"] == 1){
    $isAdmin = true;
}

}
    ?>


    <html>
    <head>
        <title>NexTrade</title>
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <link rel="stylesheet" href="CSS/Css_Item_Page.css?v=<?php echo time(); ?>">
        <link rel="icon" type="image/png" sizes="512x512" href="uploads/icon.png">
<title>NexTrade</title>


</head>
<body class="body">
<?php if(!$isAdmin): ?>

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

<?php endif; ?>
<div class="item">
   <a href="javascript:void(0)" class="close_btn" onclick="goBack()">×</a>
    <div class="slider-container">

    <button class="prev">&#10094;</button>

    <div class="slider">
        <img src="<?php echo $product['product_image']; ?>"class="slide">
        <?php while($image = $images->fetch_assoc()) { ?>
            <img 
                src="<?php echo $image['image_path']; ?>" 
                class="slide"
            >
        <?php } ?>
    </div>

    <button class="next">&#10095;</button>

</div>
<!--Product info-->
<div class="product-info">

    <div class="item_title">
        <?php echo $product['product_name']; ?>
    </div>

    <div class="price-section">
        <?php if(!empty($product['discounted_price'])) :?>
            <span class="new_price">
                R<?php echo $product['discounted_price']; ?>
            </span>
            <span class="old_price">
                R<?php echo $product['product_price']; ?>
            </span>
        <?php else:?>
            <span class="new_price">
                R<?php echo $product['product_price']; ?>
            </span>
        <?php endif;?>

        <div class="stock_status">

<?php if($product['quantity'] > 0): ?>

    <span class="in_stock">
        In Stock (<?php echo $product['quantity']; ?>)
    </span>

<?php else: ?>

    <span class="sold_out">
        Sold Out
    </span>

<?php endif; ?>

</div>
    </div>

<!--Seller info-->
<div class="seller_info">

    <div class="seller_header">

        <div class="seller_avatar">
            <?php echo strtoupper(substr($product['Firstname'],0,1)); ?>
        </div>

        <div class="seller_details">

            <h3>
                <?php echo $product['Firstname'] . " " . $product['Surname']; ?>
            </h3>

            <span>Seller</span>
 <div class="seller_rating">

<?php if($total_reviews > 0): ?>

    <span class="rating_score">
        ⭐ <?php echo $average_rating; ?>/5
    </span>

    <span class="rating_count">
        (<?php echo $total_reviews; ?> Reviews)
    </span>

<?php else: ?>

    <span class="rating_count">
        No Reviews Yet
    </span>

<?php endif; ?>

</div>

        </div>

    </div>

    <?php if(!empty($product['email'])): ?>

        <p>
            <strong>Email:</strong>
            <?php echo $product['email']; ?>
        </p>

    <?php endif; ?>

    <?php if(!empty($product['phone_number'])): ?>

        <p>
            <strong>Phone Number:</strong>
            <?php echo $product['phone_number']; ?>
        </p>

    <?php endif; ?>
    <?php if(!empty($product['suburb'])): ?>

    <div class="seller_location">

        <p>
            <strong>Location:</strong>
            <?php echo $product['suburb']; ?>
        </p>

        <iframe
            width="70%"
            height="200"
            style="border:0; border-radius:10px;"
            loading="lazy"
            allowfullscreen
            referrerpolicy="no-referrer-when-downgrade"

            src="https://www.google.com/maps?q=<?php echo urlencode($product['suburb']); ?>&output=embed">

        </iframe>

    </div>

<?php endif; ?>

</div>


    <p class="description">
        <?php echo $product['product_description']; ?>
    </p>
  <?php if($isOwner): ?>

    <button type="button" class="own_item_btn" disabled>
        Your Item
    </button>

<?php elseif($product['quantity'] <= 0): ?>

    <button type="button" class="sold_out_btn" disabled>
        Sold Out
    </button>

<?php elseif(!$isAdmin): ?>

    <form method="post" action="AddToCart.php">

        <input type="hidden" 
               name="product_id" 
               value="<?php echo $product['product_id']; ?>">

        <button type="submit">
            Add to Cart
        </button>

    </form>

<?php endif; ?>
</div>

<script>

const slides = document.querySelectorAll(".slide");

const nextBtn = document.querySelector(".next");

const prevBtn = document.querySelector(".prev");

let currentSlide = 0;

slides[currentSlide].classList.add("active");

nextBtn.addEventListener("click", () => {

    slides[currentSlide].classList.remove("active");

    currentSlide++;

    if(currentSlide >= slides.length){
        currentSlide = 0;
    }

    slides[currentSlide].classList.add("active");
});

prevBtn.addEventListener("click", () => {

    slides[currentSlide].classList.remove("active");

    currentSlide--;

    if(currentSlide < 0){
        currentSlide = slides.length - 1;
    }

    slides[currentSlide].classList.add("active");
});


function goBack() {
    if (document.referrer !== "") {
        history.back();
    } else {
        window.location.href = "HomePage.php";
    }
}

</script>
<?php if(isset($_SESSION["login_id"]) && !$isAdmin): ?>

<a href="Cart.php" class="floating_cart">
    🛒
</a>

<?php endif; ?>
    </body>
        </html>
