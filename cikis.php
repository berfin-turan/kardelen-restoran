<?php
include 'baglan.php';
$_SESSION=array();
session_destroy();
header("Location: login.php");
exit;
?>