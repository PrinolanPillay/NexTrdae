
<?php
include("Start_session.php");

if($_SERVER["REQUEST_METHOD"]=="POST"){
 
    $firstname = trim($_POST["Firstname"]);
    $surname = trim($_POST["Surname"]);
    $dob = $_POST["DOB"];
    $username = trim($_POST["username"]);
    $passwordRaw = $_POST["password"];
    $re_enter_password= $_POST["re_enter_password"];
    $email= !empty($_POST["email"]) ? trim($_POST["email"]) : NULL;
    $phone_number=$_POST["phone_number"];
    $suburb = trim($_POST["suburb"]);
    $errors = [];
   



   

if (strlen($passwordRaw) < 8) {
    $errors[] = "Password must be at least 8 characters";
}
if (!preg_match("/[A-Z]/", $passwordRaw)) {
    $errors[] = "Password must have one uppercase letter";
}
if (!preg_match("/[a-z]/", $passwordRaw)) {
    $errors[] = "Password must have one lowercase letter";
}
if (!preg_match("/[0-9]/", $passwordRaw)) {
    $errors[] = "Password must have atleast one number";
}
if($re_enter_password !== $passwordRaw){
 $errors[] = "Passwords do not match";
}
if (!preg_match("/^0[0-9]{9}$/", $phone_number)) {
    $errors[] = "Phone number must be 10 digits long and start with a '0'.";
}
/* EMAIL VALIDATION */
if ($email !== NULL) {

    // Check email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Please enter a valid email address.";
    }

    // Check if email already exists
    $stmt5 = $conn->prepare("SELECT email FROM personal_info WHERE email = ?");
    $stmt5->bind_param("s", $email);
    $stmt5->execute();
    $result = $stmt5->get_result();

    if ($result->num_rows > 0) {
        $errors[] = "Email address is already in use.";
    }

    $stmt5->close();
}


//Hashed password
$password=password_hash($passwordRaw,
        PASSWORD_DEFAULT);;
$stmt1 = $conn->prepare("SELECT login_username FROM login_info WHERE login_username = ?");
$stmt1->bind_param("s", $username);
$stmt1->execute();
$result = $stmt1->get_result();
if ($result->num_rows > 0) {
    $errors[]="User name is already taken";
} 
$stmt1->close();
  
  
$stmt4 = $conn->prepare("SELECT phone_number FROM personal_info WHERE phone_number = ?");
$stmt4->bind_param("s", $phone_number);
$stmt4->execute();
$result = $stmt4->get_result();
if ($result->num_rows > 0) {
    $errors[]="Phone number is already in use";
} 
$stmt4->close();

if (empty($errors)) {
    



    $stmt2 = $conn->prepare("INSERT INTO login_info (login_username, login_password) VALUES (?, ?)");
    $stmt2->bind_param("ss", $username, $password);
    $stmt2->execute();
    $login_id = $conn->insert_id;
    $stmt2->close();


  $stmt3 = $conn->prepare("
INSERT INTO personal_info 
(Firstname, Surname, DateOfBirth, login_id, phone_number, email, suburb) 
VALUES (?, ?, ?, ?, ?, ?, ?)
");

$stmt3->bind_param(
    "sssisss",
    $firstname,
    $surname,
    $dob,
    $login_id,
    $phone_number,
    $email,
    $suburb
);
$stmt3->execute();
$stmt3->close();

    echo "User registered successfully";

    $conn->close();
}
}
?>
<html>
<head>
    <title>NexTrade</title>
     <meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="CSS/Css_Sign_Up.css?v=<?php echo time(); ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="uploads/icon.png">
</head>
<body>
    <div class="sign_up_container">
        <h1>Sign Up</h1>
        <?php if (!empty($errors)): ?>
    <div class="error_box">
        <ul>
            <?php foreach($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>
<form method="post" >
    <div class="input_group">
<label>First Name:</label>
<input type="text" name="Firstname" placeholder="Enter your first name"required>
</div>
<div class="input_group">
<label>Surname:</label>
<input type="text" name="Surname" placeholder="Enter your surname"required>
</div>
<div class="input_group">
<label>Date of Birth:</label>
<input type="date" name="DOB" placeholder="Enter your date of birth" required>
</div>
<div class="input_group">
<label>Email:</label>
<input type="email" name="email" placeholder="Enter your email">
</div>
<div class="input_group">
<label>Phone Number:</label>
<input type="text" name="phone_number" placeholder="Enter your phone number" required>
</div>

<div class="input_group">
<label>Address:</label>
<input type="text" id="autocomplete" placeholder="Enter your address" required>
</div>

<div class="input_group">
<label>Suburb:</label>
<input type="text" id="suburb" name="suburb" readonly required>
</div>
<div class="input_group">
<label>Username: </label>
<input type="text" name="username"  placeholder="Enter a username" required >
</div>
<div class="input_group">
<label>Password: </label>
<input type="password" name="password" placeholder="Enter a password" required>
</div>
<div class="input_group">
<label>Re-enterPassword:</label>
<input type="password" name="re_enter_password" placeholder="Re-enter password"required>
</div>

<input type="submit" value="Sign Up" class="sign_up_btn"> 

<div class="signup_text">
Got an account?  <a href='Log In Page.php'>Log In</a>
</div>
</form>
</div>
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyB25yCnW9vFG4vc03SC4WF1T2dvoNZHq2M&libraries=places"></script>

<script>
    const geocoder = new google.maps.Geocoder();

document.getElementById("autocomplete").addEventListener("blur", function() {

    const address = this.value;

    if (!address) return;

    geocoder.geocode(
        {
            address: address,
            componentRestrictions: { country: "ZA" }
        },
        function(results, status) {

            if (status !== "OK" || !results[0]) {
                return;
            }

            let suburb = "";

            results[0].address_components.forEach(component => {

                const types = component.types;

                if (
                    types.includes("sublocality") ||
                    types.includes("sublocality_level_1") ||
                    types.includes("neighborhood") ||
                    types.includes("locality")
                ) {
                    suburb = component.long_name;
                }
            });

            document.getElementById("suburb").value = suburb;
        }
    );
});
//Autocomplete
let autocomplete;

function initAutocomplete() {

    autocomplete = new google.maps.places.Autocomplete(
        document.getElementById("autocomplete"),
        {
            types: ['address'],
            componentRestrictions: { country: "za" }
        }
    );

    autocomplete.addListener("place_changed", fillSuburb);
}

function fillSuburb() {

    const place = autocomplete.getPlace();

    let suburb = "";

    for (const component of place.address_components) {

        const types = component.types;

        if (
            types.includes("sublocality") ||
            types.includes("sublocality_level_1") ||
            types.includes("neighborhood") ||
            types.includes("locality")
        ) {
            suburb = component.long_name;
            break;
        }
    }

    document.getElementById("suburb").value = suburb;
}

window.onload = initAutocomplete;

</script>
</body>
 </html>
