<?php
	session_start();
	require_once "pdo.php";
	require_once "util.php";

	if (!isset($_SESSION['user_id'])){
		die("ACCESS DENIED");
		return;
	}
	if (!$_SESSION['is_staff']){
		die("ACCESS DENIED");
		return;
	}
	if (!isset($_REQUEST['user_id'])){
		$_SESSION['error'] = "Invalid user_id";
		header("Location: members.php");
		return;
	}
	$stmt = $pdo->prepare('DELETE FROM users WHERE user_id=:uid');
	$stmt->execute(array(
		':uid' => $_REQUEST['user_id']
	));
	$_SESSION['success'] = "Profile deleted";
	header("Location: members.php");
	return;