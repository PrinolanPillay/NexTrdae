<?php
session_start();

$conn= new mysqli("sql309.infinityfree.com","if0_42061477","KsEungITOpg1JD","if0_42061477_trade");

if($conn->connect_error){
    die("Connection error: ".$conn->connect_error);
}

?>