<?php 
session_start();
unset($_SESSION['user_id']);
unset($_SESSION['email']);
unset($_SESSION['first_name']);
unset($_SESSION['last_name']);
unset($_SESSION['is_staff']);
unset($_SESSION['from']);
header("Location: index.php");
