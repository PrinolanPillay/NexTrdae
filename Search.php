<?php
include("Start_session.php");

$search = $_GET['search'] ?? '';

$stmt = $conn->prepare("
    SELECT product_name,
           product_description,
           product_price,
           product_image,
           discounted_price,
           product_id
    FROM product_list
    WHERE product_name LIKE ?
    OR product_description LIKE ?
    OR product_group LIKE ?
    ORDER BY product_id DESC
");

$term = "%".$search."%";

$stmt->bind_param("sss", $term, $term, $term);
$stmt->execute();

$result = $stmt->get_result();
?>

<html>
<head>
<title>NexTrade - Search</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="CSS/Css_Search.css?v=<?php echo time(); ?>">
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
<?php if(isset($_SESSION["login_id"])): ?>
<a href="AccountPage.php">Account</a>
<?php else: ?>
<a href="Log In Page.php">Log in</a>
<?php endif; ?>
</li>
</ul>

<div class="search_container">
    <form action="Search.php" method="GET">
        <input
            type="text"
            name="search"
            class="search_bar"
            placeholder="Search products..."
            value="<?php echo htmlspecialchars($search); ?>"
        >
        <button type="submit" class="search_btn">🔍</button>
    </form>
</div>

</header>

<div class="slider_heading">
    <h1>
        Search Results
        <?php if(!empty($search)): ?>
            for "<?php echo htmlspecialchars($search); ?>"
        <?php endif; ?>
    </h1>
</div>



<div class="search_results">
<?php if($result->num_rows > 0): ?>

    <?php while($row = $result->fetch_assoc()) { ?>

        <a href="Item_page.php?id=<?php echo $row['product_id']; ?>" class="item_link">

            <div class="item">

                <img src="<?php echo htmlspecialchars($row['product_image']); ?>" alt="<?php echo htmlspecialchars($row['product_name']); ?>">

                <div class="item_title">
                    <?php echo htmlspecialchars($row['product_name']); ?>
                </div>

                <div class="price_container">

                    <?php if(!empty($row['discounted_price'])): ?>

                        <span class="new_price">
                            R<?php echo number_format($row['discounted_price'], 2); ?>
                        </span>

                        <span class="old_price">
                            R<?php echo number_format($row['product_price'], 2); ?>
                        </span>

                    <?php else: ?>

                        <span class="new_price">
                            R<?php echo number_format($row['product_price'], 2); ?>
                        </span>

                    <?php endif; ?>

                </div>

            </div>

        </a>

    <?php } ?>

<?php else: ?>

    <div class="no_results">
        <h2>No products found</h2>
        <p>Try searching with different keywords.</p>
    </div>

<?php endif; ?>

</div>

<?php if(isset($_SESSION["login_id"])): ?>
<a href="Cart.php" class="floating_cart">
    🛒
</a>
<a href="Customer_Support.php" class="floating_support">
    💬
</a>
<?php endif; ?>
<?php
$stmt->close();
$conn->close();
?>
</body>
</html>