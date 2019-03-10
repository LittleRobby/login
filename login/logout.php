<?php
include "functions/init.php";

unset($_SESSION['email']);
session_destroy();
if(isset($_COOKIE['email'])) {
    //unset($_COOKIE['email']);
    setcookie('email', '', time()-600);
}
redirect("index.php");
