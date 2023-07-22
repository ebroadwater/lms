<?php 
	session_start();
	require_once "pdo.php";
	require_once "util.php";

	if (!isset($_SESSION['user_id']) || $_SESSION['is_staff'] === false){
		die("ACCESS DENIED");
		header("Location: index.php");
		return;
	}

?>
<!DOCTYPE html>
<html>
	<header>
		<title>Edit Members</title>
		<?php require_once "head.php";?>
	</header>
	<body>

	</body>
</html>
