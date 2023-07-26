<?php
	session_start();
	require_once "../pdo.php";
	require_once "../util.php";

	if (!isset($_SESSION['user_id'])){
		die("ACCESS DENIED");
		return;
	}
	if (!$_SESSION['is_staff']){
		die("ACCESS DENIED");
		return;
	}
	if (!isset($_REQUEST['book_id'])){
		$_SESSION['error'] = "Invalid book_id";
		header("Location: ../catalog.php");
		return;
	}
	$book = getBook($pdo, $_REQUEST['book_id']);
	//Delete BookAuthor and BookGenre
	$stmt = $pdo->prepare('DELETE FROM BookAuthor WHERE book_id=:bid; DELETE FROM BookGenre WHERE book_id=:bid; DELETE FROM Book WHERE book_id=:bid;');
	$stmt->execute(array(
		':bid' => $_REQUEST['book_id']
	));
	$_SESSION['success'] = "Book deleted";
	return;