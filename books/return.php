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
		header("Location: view.php?book_id=".$_REQUEST['book_id']);
		return;
	}
	if (isset($_POST['cancel'])){
		header("Location: view.php?book_id=".$_REQUEST['book_id']);
		return;
	}
	$date = new DateTime();
	$date->setTimezone(new DateTimeZone('America/New_York'));
	$start = date_format($date, "Y-m-d H:i:s");

	$is_checked_out = false;
	$is_overdue = false;
	$stmt = $pdo->prepare('SELECT * FROM Checkout WHERE user_id=:uid AND book_id=:bid AND is_returned=:ir');
	$stmt->execute(array(
		':uid' => $_REQUEST['user_id'], 
		':bid' => $_REQUEST['book_id'], 
		':ir' => 0
	));
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	if (count($result) > 0){
		$is_checked_out = true;
		//Check if overdue
		if ($result['end_time'] < $start){
			$is_overdue = true;
		}
	}
	if (isset($_POST['return'])){
		if (!$is_checked_out){
			$_SESSION['error'] = "User does not have book checked out. Cannot return.";
			header("Location: ".$_SERVER['REQUEST_URI']);
			return;
		}
		//Update Checkout
		$stmt = $pdo->prepare('UPDATE Checkout SET end_time=:et, is_returned=:ir WHERE checkout_id=:cid');
		$stmt->execute(array(
			':et' => $start, 
			':ir' => 1, 
			':cid' => htmlentities($result['checkout_id'])
		));
		//Update available copies
		$copies = htmlentities($book['available_copies']) + 1;
		updateAvailableCopies($pdo, $copies, $book['book_id']);

		$_SESSION['success'] = "Book returned successfully";
		header("Location: ../members/profile.php?user_id=".htmlentities($_REQUEST['user_id']));
		return;
	}
	
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Return <?php echo $book['title']?></title>
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
		<h1>Return</h1>
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
				echo("<p style='color:red; text-align:center;'>WARNING: <br><br>Cannot return book because user does not have it checked out. ");
				echo("Please check later.</p>");
			}
			else{
				echo('<p style="text-align:center;"><strong>Are you sure you want to return this book? </strong></p>');
				if ($is_overdue){
					$date1 = DateTime::createFromFormat ( "Y-m-d H:i:s", $result["end_time"] );
					$fine = $date1->diff($date);
					$fine = $fine->days;
					echo('<p style="text-align:center; margin-top: 30px; line-height:30px; color:red;">OVERDUE</p>');
					echo('<p style="text-align:center; margin-top: 15px;">There is a $1.00 fine for each day past the due date.</p>');
					echo('<p style="text-align:center; margin-top: 15px; line-height:30px;">Fine: <strong>$'.$fine.'.00</strong></p>');
				}
				echo('<form method="POST" style="text-align:center;">');
				echo('<input type="hidden" name="book_id" value="'.htmlentities($_REQUEST['book_id']).'">');
				echo('<input type="submit" name="return" value="Return">');
				echo('<input type="submit" name="cancel" value="Cancel" style="margin-left:10px;"></form>');
			}
		?>
		</div>
		<p style="text-align:center; margin-top:40px;"><a href='
		<?php if (isset($_SESSION['from'])){echo $_SESSION['from']; unset($_SESSION['from']);} else{echo "view.php?book_id=".htmlentities($_REQUEST['book_id']);}?>
		'>Go Back</a></p>
	</body>
</html>