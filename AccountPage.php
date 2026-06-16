<?php
include("Start_session.php");
if(!isset($_SESSION["login_id"])){
header("Location: Log In Page.php");
exit();
}

$login_id=$_SESSION["login_id"];
//Get info
$stmt = $conn->prepare("
   
    SELECT login_info.login_username, login_info.login_password, personal_info.Firstname, personal_info.Surname, personal_info.DateOfBirth,personal_info.suburb
    FROM login_info
    LEFT JOIN personal_info 
    ON login_info.login_id = personal_info.login_id
    WHERE login_info.login_id = ?

");

$stmt->bind_param("i", $login_id);
$stmt->execute();

$result = $stmt->get_result();
$user = $result->fetch_assoc();

?>

<html>
<head>
    <title>NexTrade</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="CSS/CssAccountPage.css?v=<?php echo time(); ?>">
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
<li><a href="Delete_Account.php" onclick="return confirm('Are you sure you want to delete your account? This cannot be undone.');">Delete Account</a></li>
</ul>
</nav>
<div class="content">

    <div class="personal-info">
        <form method="POST">
        <h2>Personal Information</h2>

        <div class="info_row">
         <label class="info_label">Username:</label>
        <input type="text" name="username" value="<?php echo $user["login_username"]; ?>" class="info_value">
        </div>


        <div class="info_row">
            <span class="info_label">Full Name</span>
            <span class="info_value">
                <?php echo $user['Firstname']; ?>
                <?php echo $user['Surname']; ?>
            </span>
        </div>

        <div class="info_row">
        <label class="info_label">Firstname</label>
        <input type="text" name="firstname" value="<?php echo $user["Firstname"]; ?>" class="info_value">
        </div>

        <div class="info_row">
        <label class="info_label">Surname</label>
        <input type="text" name="surname" value="<?php echo $user["Surname"]; ?>" class="info_value">
        </div>

        <div class="info_row">
        <label class="info_label">Date of Birth</label>
        <input type="text" name="dob" value="<?php echo $user["DateOfBirth"]; ?>" class="info_value">
        </div>

        <div class="info_row">
<label class="info_label">Address</label>
<input type="text" id="autocomplete" placeholder="Enter your address" class="info_value">
</div>

<div class="info_row">
<label class="info_label">Suburb</label>
<input type="text" id="suburb" name="suburb" value="<?php echo $user["suburb"]; ?>" class="info_value"  readonly>
</div>
        

        <button class="update_btn">
            Update Information
        </button>
        </form>

    </div>

</div>
</div>
<a href="Customer_Support.php" class="floating_support">
    💬
</a>
<script>

let autocomplete;

function initAutocomplete() {

    autocomplete = new google.maps.places.Autocomplete(
        document.getElementById("autocomplete"),
        {
            componentRestrictions: { country: "za" },
            fields: ["address_components"],
            types: ["address"]
        }
    );

    autocomplete.addListener("place_changed", fillSuburb);
}

function fillSuburb() {

    const place = autocomplete.getPlace();

    let suburb = "";

    if (!place.address_components) {
        return;
    }

    for (const component of place.address_components) {

        const types = component.types;

        if (
            types.includes("sublocality") ||
            types.includes("sublocality_level_1") ||
            types.includes("locality")
        ) {
            suburb = component.long_name;
        }
    }

    document.getElementById("suburb").value = suburb;
}

</script>

<script async defer
src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB25yCnW9vFG4vc03SC4WF1T2dvoNZHq2M&libraries=places&callback=initAutocomplete">
</script>
</body>
</html>

<?php

$errors = [];

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $firstname = trim($_POST["firstname"]);
    $surname = trim($_POST["surname"]);
    $dob = $_POST["dob"];
    $username = trim($_POST["username"]);
    $suburb = trim($_POST["suburb"]);

   
//Make sure no one has same username
    if(!empty($username)){

        $stmt1 = $conn->prepare("
            SELECT login_username 
            FROM login_info 
            WHERE login_username = ?
            AND login_id != ?
        ");

        $stmt1->bind_param("si", $username, $login_id);
        $stmt1->execute();

        $result1 = $stmt1->get_result();

        if($result1->num_rows > 0){
            $errors[] = "Username is already taken.";
        }

        $stmt1->close();
    }

 

    if(!empty($errors)){
        die("Errors: " . implode(", ", $errors));
    }

   
  //Make sure username dont match old username 

    if(!empty($username) && $username != $user["login_username"]){

        $stmt2 = $conn->prepare("
            UPDATE login_info
            SET login_username = ?
            WHERE login_id = ?
        ");

        $stmt2->bind_param("si", $username, $login_id);
        $stmt2->execute();

        $stmt2->close();
    }

   //Upadte personal info

    $stmt3 = $conn->prepare("
        UPDATE personal_info
        SET Firstname = ?,
         Surname = ?,
        DateOfBirth = ?,
        suburb = ?
        WHERE login_id = ?
    ");

   $stmt3->bind_param(
    "ssssi",
    $firstname,
    $surname,
    $dob,
    $suburb,
    $login_id
);

    $stmt3->execute();

    $stmt3->close();

    header("Location: AccountPage.php");
    exit();
}
?>