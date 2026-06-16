<?php
          ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
include("Start_session.php");

if(!isset($_SESSION["login_id"])){
    header("Location: Log In Page.php");
    exit();
}

if (!isset($_GET['id'])) {
    echo "No product selected.";
    exit();
}

$product_id = $_GET['id'];
$login_id = $_SESSION["login_id"];

/* CHECK IF USER IS ADMIN */
$adminStmt = $conn->prepare("
SELECT admin_access
FROM login_info
WHERE login_id = ?
");

$adminStmt->bind_param("i", $login_id);
$adminStmt->execute();

$adminResult = $adminStmt->get_result();
$adminRow = $adminResult->fetch_assoc();

$isAdmin = $adminRow['admin_access'];

/* ADMIN CAN DELETE ANY PRODUCT */
if($isAdmin == 1){

    $stmt = $conn->prepare("
    DELETE FROM product_list
    WHERE product_id = ?
    ");

    $stmt->bind_param("i", $product_id);

} else {

    /* NORMAL USER CAN ONLY DELETE OWN PRODUCTS */
    $stmt = $conn->prepare("
    DELETE FROM product_list
    WHERE product_id = ? AND login_id = ?
    ");

    $stmt->bind_param("ii", $product_id, $login_id);
}

/* EXECUTE DELETE */
if ($stmt->execute()) {
if($isAdmin==1){
    // RETURN TO PREVIOUS PAGE
    if(isset($_SERVER['HTTP_REFERER'])){
        header("Location: " . $_SERVER['HTTP_REFERER']);
    } }
    else {
        header("Location: MyProductPage.php");
    }

    exit();

} else {

    echo "Error deleting product.";

}
?>