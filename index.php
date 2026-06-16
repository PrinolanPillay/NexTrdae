<?php

include("Start_session.php");

$stmt = $conn->prepare("
SELECT 
    p.*,
    SUM(oi.quantity) AS total_sold,
    MAX(o.created_at) AS last_sold
FROM order_items oi
JOIN orders o ON oi.order_id = o.order_id
JOIN product_list p ON oi.product_id = p.product_id
GROUP BY p.product_id, p.product_name
ORDER BY last_sold DESC
LIMIT 10;
");

$stmt->execute();

$result = $stmt->get_result();


/* LATEST 10 PRODUCTS */

$latest_stmt = $conn->prepare("
SELECT product_name, product_price, discounted_price, product_image, product_id
FROM product_list
ORDER BY product_id DESC
LIMIT 10
");

$latest_stmt->execute();
$latest_products = $latest_stmt->get_result();
?>


<html>
<head>
<title>NexTrade</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <link rel="stylesheet" href="CSS/CssHomePage.css?v=<?php echo time(); ?>">
<link rel="icon" type="image/png" sizes="512x512" href="uploads/icon.png">

</head>
<body class="body">
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
<?php if(isset($_SESSION["login_id"]))    :?>
<a href="AccountPage.php">Account</a>
<?php else:?>
<a href="Log In Page.php">Log in</a>
<?php endif;?>
</li>
</ul>
<div class="search_container">
    <form action="Search.php" method="GET">
        <input type="text" name="search" placeholder="Search products..." class="search_bar" required>
        <button type="submit" class="search_btn">🔍</button>
    </form>
</div>
</header> 
<div class="slider_heading">
    <h1>Todays Hot Deals</h1>
</div>
<div class="slider-container">
    <button id="backbtn" onclick="moveSlide(-1)">⬅</button>
<div class="slider" id="slider">
    <?php while($row = $result->fetch_assoc()) { ?>
    <a href="Item_page.php?id=<?php echo $row['product_id']; ?>" class="item_link">
<div class="item">
<img src="<?php echo $row['product_image']; ?>">
<div class="item_title"><?php echo $row['product_name']; ?></div>
<div>
    <?php if(!empty($row['discounted_price'])) :?>
<span class="new_price">R<?php echo $row['discounted_price']; ?></span>
<span class="old_price">R<?php echo $row['product_price']; ?></span>
<?php else:?>
<span class="new_price">R<?php echo $row['product_price']; ?></span>
<?php endif;?>
</div>
</div>
</a>
<?php } ?>
</div>
  <button id="nextbtn"onclick="moveSlide(1)">➡</button>
</div>
</div>

<div class="slider_heading">
    <h1>Recently Added Products</h1>
</div>

<div class="slider-container">
   <button id="latestBackBtn" onclick="moveLatestSlide(-1)">⬅</button>

    <div class="slider" id="latestSlider">
        <?php while($latest = $latest_products->fetch_assoc()) { ?>
            <a href="Item_page.php?id=<?php echo $latest['product_id']; ?>" class="item_link">
                <div class="item">
                    <img src="<?php echo $latest['product_image']; ?>">

                    <div class="item_title">
                        <?php echo htmlspecialchars($latest['product_name']); ?>
                    </div>

                    <div>
                        <?php if(!empty($latest['discounted_price'])) : ?>
                            <span class="new_price">
                                R<?php echo $latest['discounted_price']; ?>
                            </span>

                            <span class="old_price">
                                R<?php echo $latest['product_price']; ?>
                            </span>
                        <?php else: ?>
                            <span class="new_price">
                                R<?php echo $latest['product_price']; ?>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </a>
        <?php } ?>
    </div>

    
<button id="latestNextBtn" onclick="moveLatestSlide(1)">➡</button>
</div>

<script>
const slider = document.getElementById("slider");
const backBtn = document.getElementById("backbtn");
const nextBtn = document.getElementById("nextbtn");

function updateButtons() {

    // Hide back buttons
    if (slider.scrollLeft <= 0) {
        backBtn.style.display = "none";
    } else {
        backBtn.style.display = "block";
    }


    if (slider.scrollLeft + slider.clientWidth >= slider.scrollWidth - 5) {
        nextBtn.style.display = "none";
    } else {
        nextBtn.style.display = "block";
    }
}

function moveSlide(direction) {

    const item = slider.querySelector(".item");

    if (!item) return;

    const step = item.offsetWidth + 15;

    slider.scrollLeft += direction * step * 4;

   
    setTimeout(updateButtons, 300);
}


slider.addEventListener("scroll", updateButtons);


window.addEventListener("load", updateButtons);

const latestSlider = document.getElementById("latestSlider");
const latestBackBtn = document.getElementById("latestBackBtn");
const latestNextBtn = document.getElementById("latestNextBtn");

function updateLatestButtons() {

    if (latestSlider.scrollLeft <= 0) {
        latestBackBtn.style.display = "none";
    } else {
        latestBackBtn.style.display = "block";
    }

    if (
        latestSlider.scrollLeft + latestSlider.clientWidth >=
        latestSlider.scrollWidth - 5
    ) {
        latestNextBtn.style.display = "none";
    } else {
        latestNextBtn.style.display = "block";
    }
}

function moveLatestSlide(direction) {

    const item = latestSlider.querySelector(".item");

    if (!item) return;

    const step = item.offsetWidth + 15;

    latestSlider.scrollLeft += direction * step * 4;

    setTimeout(updateLatestButtons, 300);
}

latestSlider.addEventListener("scroll", updateLatestButtons);
window.addEventListener("load", updateLatestButtons);
</script>
<?php if(isset($_SESSION["login_id"])): ?>

<a href="Cart.php" class="floating_cart">
    🛒
</a>
<a href="Customer_Support.php" class="floating_support">
    💬
</a>


<?php endif; ?>
</body>




</html>