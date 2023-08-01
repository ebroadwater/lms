<?php
	session_start();
	require_once "../pdo.php";
	require_once "../util.php";

	if (!isset($_SESSION['user_id'])){
		die("ACCESS DENIED");
		return;
	}
	if (!isset($_REQUEST['user_id'])){
		$_SESSION['error'] = "Invalid user_id";
		header("Location: ../members.php");
		return;
	}
	$date = new DateTime();
	$date->setTimezone(new DateTimeZone('America/New_York'));
	$start = date_format($date, "Y-m-d H:i:s");

	//Get current overdue items
	$stmt = $pdo->prepare('SELECT * FROM Checkout WHERE user_id=:uid AND is_returned=0 AND end_time < :ti');
	$stmt->execute(array(
		':uid' => $_REQUEST['user_id'], 
		':ti' => $start
	));
	$checkout_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
	if (count($checkout_list) > 0){
		//Current charges
		$stmt = $pdo->prepare('SELECT charges FROM users WHERE user_id=:uid');
		$stmt->execute(array(
			':uid' => $_REQUEST['user_id']
		));
		$bill = $stmt->fetch(PDO::FETCH_ASSOC);
		$charges = $bill['charges'];
		foreach($checkout_list as $check){
			$date1 = DateTime::createFromFormat ( "Y-m-d H:i:s", $check["end_time"] );
			$fine = $date1->diff($date);
			$fine = $fine->days;
			//Add to charges
			$charges = $charges + $fine;
		}
		$stmt = $pdo->prepare('UPDATE users SET charges=:ch WHERE user_id=:uid');
		$stmt->execute(array(
			':ch' => $charges,
			':uid' => $_REQUEST['user_id']
		));
	}
	return;