<?php
	session_start();
	require_once "pdo.php";
	require_once "util.php";

	if (!isset($_SESSION['user_id'])){
		die("ACCESS DENIED");
		return;
	}
	header('Content-Type: application/json; charset=utf-8');
	$stmt = $pdo->prepare('SELECT name FROM Genre WHERE name LIKE :prefix');
	$stmt->execute(array(
		':prefix' => $_GET['term']."%"
	));
	$retval = array();
	while ( $row = $stmt->fetch(PDO::FETCH_ASSOC) ) {
	$retval[] = $row['name'];
	}

	echo(json_encode($retval, JSON_PRETTY_PRINT));
