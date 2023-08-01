<?php 
	session_start();
	require_once "../pdo.php";
	require_once "../util.php";

	if (!isset($_SESSION['user_id'])){
		if (isset($_GET['from'])){
			$_SESSION['from'] = $_GET['from'];
		}
		header("Location: ../login.php");
		return;
	}
	$staff = $_SESSION['is_staff'];
	$profile = false;
	if ($_SESSION['user_id'] === $_REQUEST['user_id']){
		$profile = true;
	}
	//Must be staff or user's profile
	if ($staff == 0 && !$profile){
		$_SESSION['error'] = "Access denied";
		header("Location: ../index.php");
		return;
	}
	if (!isset($_GET['book_id'])){
		$_SESSION['error'] = "No book specified";
		header("Location: ../index.php");
	}
	$book = getBook($pdo, $_GET['book_id']);
	if ($book === false){
		$_SESSION['error'] = "Unable to load book";
		header("Location: ../members/profile.php?user_id=".$_REQUEST['user_id']);
		return;
	}
	if (isset($_POST['cancel'])){
		header("Location: ../members/profile.php?user_id=".$_REQUEST['user_id']);
		return;
	}
	$date = new DateTime();
	$date->setTimezone(new DateTimeZone('America/New_York'));
	$start = date_format($date, "Y-m-d H:i:s");
	$date->modify('+14 day');
	$new_date = null;

	$is_checked_out = false;
	$stmt = $pdo->prepare('SELECT * FROM Checkout WHERE user_id=:uid AND book_id=:bid AND is_returned=:ir');
	$stmt->execute(array(
		':uid' => $_REQUEST['user_id'], 
		':bid' => $_REQUEST['book_id'], 
		':ir' => 0
	));
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	if (count($result) > 0){
		$is_checked_out = true;
		$exp = strtotime(htmlentities($result['end_time']));
		// $renewed_date = new DateTime($exp);
		$renewed_date = new DateTime('@'.$exp);
		$renewed_date->modify('+14 days');
		$new_date = date_format($renewed_date, "Y-m-d H:i:s");
		$readable_date = date_format($renewed_date, "l, m-d-Y g:i A");
	}

	if (isset($_POST['renew'])){
		if (!$is_checked_out){
			$_SESSION['error'] = "User does not have book checked out. Cannot return.";
			header("Location: ".$_SERVER['REQUEST_URI']);
			return;
		}
		//Update Checkout
		$stmt = $pdo->prepare('UPDATE Checkout SET end_time=:et WHERE checkout_id=:cid');
		$stmt->execute(array(
			':et' => $new_date,  
			':cid' => htmlentities($result['checkout_id'])
		));
		//Update available copies
		$copies = htmlentities($book['available_copies']) + 1;
		updateAvailableCopies($pdo, $copies, $book['book_id']);

		$_SESSION['success'] = "Book renewed successfully";
		header("Location: ../members/profile.php?user_id=".htmlentities($_REQUEST['user_id']));
		return;
	}
	
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Renew <?php echo $book['title']?></title>
		<?php require_once "../head.php";?>
		<link rel='stylesheet' href='../static/css/starter.css'>
	</head>
	<body>
		<ul class="nav">
			<li class="nav-link">
			<?php 
				echo('<a href="../index.php">Home</a>');
				if ($staff){
					echo('<a href="add.php">Add Book</a>');
					echo('<a href="../members/member-add.php">Add Member</a>');
					echo('<a href="../members.php">Members</a>');
				}
				echo('<a href="../members/profile.php?user_id='.$_SESSION['user_id'].'">Profile</a>');
				echo('<a href="../logout.php">Log Out</a>');
			?>
			</li>
		</ul>
		<h1>Renew</h1>
		<?php 
			flashMessagesCenter();
		?>
		<p style="font-size:1.2em; text-align:center; margin-top:70px;">
			<?php 
				echo listBookandAuthor($pdo, $_REQUEST['book_id']);
			?> 
		</p>
		<br>
		<div style="border:solid 1px black; width:40%; margin:auto; padding:30px; margin-top:25px; text-align:center;">
		<?php
			//If not avaialable 
			if (!$is_checked_out){
				echo("<p style='color:red; text-align:center;'>WARNING: <br><br>Cannot renew book because user does not have it checked out. ");
				echo("Please check later.</p>");
			}
			else{
				echo('<p style="text-align:center;"><strong>Are you sure you want to renew this book? </strong></p><br>');
				echo('<p style="text-align:center;"><strong>Note: </strong>Once confirmed, you will have it checked out for <strong>2 more weeks</strong>.</p>');
				echo('<p style="text-align:center; margin-top: 30px; line-height:10px;">If returned late, you will be fined <strong>$1.00</strong> for each extra day.</p>');
				echo('<p style="margin-top:35px; margin-bottom:25px;">Due Date: <strong>'.$readable_date.'</strong></p>');
				echo('<form method="POST" style="text-align:center;">');
				echo('<input type="hidden" name="book_id" value="'.htmlentities($_REQUEST['book_id']).'">');
				echo('<input type="submit" name="renew" value="Renew">');
				echo('<input type="submit" name="cancel" value="Cancel" style="margin-left:10px;"></form>');
			}
		?>
		</div>
		<p style="text-align:center; margin-top:40px;"><a href='
		<?php if (isset($_SESSION['from'])){echo $_SESSION['from']; unset($_SESSION['from']);} else{echo "view.php?book_id=".htmlentities($_REQUEST['book_id']);}?>
		'>Go Back</a></p>
	</body>
</html>