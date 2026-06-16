<?php
include("Start_session.php");

if (!isset($_GET['id'])) {
    echo "No product selected.";
    
    exit();
}
$product_group = $_GET['id'];
$current_page = $_GET['id'] ?? '';

$stmt = $conn->prepare("
SELECT product_name, product_description, product_price, product_image, discounted_price, product_id 
FROM product_list 
WHERE product_group=?
ORDER BY product_id DESC
");
$stmt->bind_param("s", $product_group);
$stmt->execute();
$page_title = ucwords(str_replace("_", " ", $_GET['id']));
$page_title = str_replace(
    ["Home Furniture", "Books Education", "Kids Baby Items", "Hobbies Entertainment", "Beauty Health"],
    ["Home & Furniture", "Books & Education", "Kids & Baby Items", "Hobbies & Entertainment", "Beauty & Health"],
    $page_title
);

$result = $stmt->get_result();

?>


<html>
<head>
<title>NexTrade</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
   <link rel="stylesheet" href="CSS/Grouped_page.css?v=<?php echo time(); ?>">
<link rel="icon" type="image/png" sizes="512x512" href="uploads/icon.png">

</head>
<body class="body">
<header class="header">
<a href="index.php" class="logo">NexTrade</a>
<ul class="navlist">
<li>
<a href="Grouped_page.php?id=electronics" class="<?php echo ($current_page == 'electronics') ? 'active' : ''; ?>">
Electronics
</a>
</li>

<li>
<a href="Grouped_page.php?id=fashion" class="<?php echo ($current_page == 'fashion') ? 'active' : ''; ?>">
Fashion
</a>
</li>

<li>
<a href="Grouped_page.php?id=home_furniture" class="<?php echo ($current_page == 'home_furniture') ? 'active' : ''; ?>">
Home & Furniture
</a>
</li>

<li>
<a href="Grouped_page.php?id=vehicle" class="<?php echo ($current_page == 'vehicle') ? 'active' : ''; ?>">
Vehicle
</a>
</li>

<li>
<a href="Grouped_page.php?id=books_education" class="<?php echo ($current_page == 'books_education') ? 'active' : ''; ?>">
Books & Education
</a>
</li>

<li>
<a href="Grouped_page.php?id=kids_baby_items" class="<?php echo ($current_page == 'kids_baby_items') ? 'active' : ''; ?>">
Kids & Baby Items
</a>
</li>

<li>
<a href="Grouped_page.php?id=hobbies_entertainment" class="<?php echo ($current_page == 'hobbies_entertainment') ? 'active' : ''; ?>">
Hobbies & Entertainment
</a>
</li>

<li>
<a href="Grouped_page.php?id=beauty_health" class="<?php echo ($current_page == 'beauty_health') ? 'active' : ''; ?>">
Beauty & Health
</a>
</li>
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



  <?php
$banner_image = "uploads/" . basename($product_group) . ".png";
?>

<div class="slider_heading">
    <h1><?php echo htmlspecialchars($page_title); ?></h1>

    <img 
        src="<?php echo htmlspecialchars($banner_image); ?>" 
        alt="<?php echo htmlspecialchars($page_title); ?> banner"
        class="group_banner"
    >

</div>
<div class="slider-container">
    <button id="backbtn" onclick="moveSlide(-1)">⬆</button>
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
  <button id="nextbtn" onclick="moveSlide(1)">⬇</button>
</div>


<script>
const slider = document.getElementById("slider");
const backBtn = document.getElementById("backbtn");
const nextBtn = document.getElementById("nextbtn");

function updateButtons() {

    // Hide top button 
    if (slider.scrollTop <= 0) {
        backBtn.style.display = "none";
    } else {
        backBtn.style.display = "block";
    }

    // Hide bottom button
    if (slider.scrollTop + slider.clientHeight >= slider.scrollHeight - 5) {
        nextBtn.style.display = "none";
    } else {
        nextBtn.style.display = "block";
    }
}

function moveSlide(direction) {

    const item = slider.querySelector(".item");
    if (!item) return;

    const step = item.offsetHeight + 15;

    slider.scrollBy({
        top: direction * step * 1,
        behavior: "smooth"
    });

    setTimeout(updateButtons, 300);
}

// Update buttons 
slider.addEventListener("scroll", updateButtons);


window.addEventListener("load", updateButtons);
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