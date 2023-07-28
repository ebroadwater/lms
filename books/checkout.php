<?php 
	session_start();
	require_once "../pdo.php";
	require_once "../util.php";

	if (!isset($_SESSION['user_id'])){
		$_SESSION['from'] = $_SERVER['REQUEST_URI'];
		header("Location: ../login.php");
		return;
	}
	if (!isset($_GET['book_id'])){
		$_SESSION['error'] = "No book specified";
		header("Location: ../index.php");
	}
	$book = getBook($pdo, $_GET['book_id']);
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Checkout <?php echo $book['title']?></title>
		<?php require_once "../head.php";?>
		<link rel='stylesheet' href='../static/css/starter.css'>
	</head>
	<body>
		<h1>Checkout</h1>
	</body>
</html>