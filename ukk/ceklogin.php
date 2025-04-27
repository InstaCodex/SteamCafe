<?php
require "functions.php";

if(isset($_SESSION['login'])){
    //yaudah
} else {
// belum login
header('location:../login.php');
}
?>