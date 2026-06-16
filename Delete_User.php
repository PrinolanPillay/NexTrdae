<?php
include("Start_session.php");

if(!isset($_SESSION["login_id"])){
    header("Location: Log In Page.php");
    exit();
}

if(isset($_GET['id'])){

    $user_id = $_GET['id'];

    /* DELETE FROM personal_info FIRST */
    $stmt = $conn->prepare("
    DELETE FROM personal_info 
    WHERE login_id = ?
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    /* DELETE FROM login_info */
    $stmt = $conn->prepare("
    DELETE FROM login_info 
    WHERE login_id = ?
    ");

    $stmt->bind_param("i", $user_id);
    $stmt->execute();

}

header("Location: Admin_User_List.php");
exit();
?>