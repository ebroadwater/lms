<?php 
	session_start();
	require_once "../pdo.php";
	require_once "../util.php";

	if (!isset($_SESSION['user_id'])){
		// header("Location: ../login.php?location=".urlencode($_SERVER['REQUEST_URI']));
		$_SESSION['from'] = $_SERVER['REQUEST_URI'];
		header("Location: ../login.php");
		return;
	}
	if (!isset($_GET['book_id'])){
		$_SESSION['error'] = "No book specified";
		header("Location: ../index.php");
	}
	if (isset($_POST['cancel'])){
		header("Location: view.php?book_id=".$_REQUEST['book_id']);
		return;
	}
	$staff = 0;
	$loggedin = isset($_SESSION['user_id']);
	if ($loggedin){
		$staff = $_SESSION['is_staff'];
	}
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
	
	$book = getBook($pdo, $_REQUEST['book_id']);
	if ($book === false){
		$_SESSION['error'] = "Could not load book";
		header("Location: view.php?book_id=".$_REQUEST['book_id']);
		return;
	}
	$date = new DateTime();
	$date->setTimezone(new DateTimeZone('America/New_York'));
	$start = date_format($date, "Y-m-d H:i:s");
	$date->modify('+3 day');
	$time = date_format($date, "Y-m-d H:i:s");

	$is_available = true;
	if (htmlentities($book['available_copies']) < 1){
		$is_available = false;
	} 
	$have_hold = false;
	$stmt = $pdo->prepare('SELECT * FROM Hold WHERE user_id=:uid AND book_id=:bid AND end_time >= :et');
	$stmt->execute(array(
		':uid' => $_SESSION['user_id'],
		':bid' => $_REQUEST['book_id'], 
		':et' => $start
	));
	$hold = $stmt->fetchAll(PDO::FETCH_ASSOC);
	if (count($hold) >0){
		$have_hold = true;
	}
	if (isset($_POST['submit'])){
		//Check if the book is available 
		if (!$is_available){
			$_SESSION['error'] = "Unable to place hold. There are no copies currently available.";
			header("Location: view.php?book_id=".htmlentities($_REQUEST['book_id']));
			return;
		} 
		//Check that user doesn't already have a hold on the book
		if ($have_hold){
			$_SESSION['error'] = "You already have a hold on this book";
			header("Location: ../index.php");
			return;
		}
		$stmt = $pdo->prepare('INSERT INTO Hold (start_time, end_time, book_id, user_id) VALUES (:st, :et, :bid, :uid)');
		$stmt->execute(array(
			':st' => $start,
			':et' => $time, 
			':bid' => $_POST['book_id'],
			':uid' => $_SESSION['user_id']
		));
		$copies = htmlentities($book['available_copies']) - 1;
		//Update available copies for Book
		updateAvailableCopies($pdo, $copies, $_REQUEST['book_id']);

		$_SESSION['success'] = "Hold placed successfully";
		header("Location: ../members/profile.php?user_id=".$_SESSION['user_id']);
		return;
	}

?>
<!DOCTYPE html>
<html>
	<head>
		<title>Place Hold</title>
		<?php require_once "../head.php";?>
		<link rel='stylesheet' href='../static/css/starter.css'>
	</head>
	<body>
		<ul class="nav">
			<li class="nav-link">
			<?php 
				echo('<a href="../index.php">Home</a>');
				if ($loggedin){
					if ($staff){
						echo('<a href="add.php">Add Book</a>');
						echo('<a href="../members/member-add.php">Add Member</a>');
						echo('<a href="../members.php">Members</a>');
					}
					echo('<a href="../members/profile.php?user_id='.$_SESSION['user_id'].'">Profile</a>');
					echo('<a href="../logout.php">Log Out</a>');
				}
				else{
					echo('<a href="../login.php">Log In</a>');
					echo('<a href="../signup.php">Sign Up</a>');
				}
			?>
			</li>
		</ul>
		<h1>Place Hold</h1>
		<?php 
			flashMessagesCenter();
		?>
		<h2 style="font-size:1.2em; text-align:center; margin-top:80px;">
			<?php 
				echo listBookandAuthor($pdo, $_REQUEST['book_id']);
			?> 
		</h2>
		<br>
		<?php 
			if ($have_hold){
				echo("<p style='text-align:center;'>You already have a hold on this book</p>");
			}
		?>
		<div style="border:solid 1px black; width:40%; margin:auto; padding:30px; margin-top:25px; text-align:center;">
		<?php
			//If not avaialable 
			if (!$is_available){
				echo("<p style='color:red; text-align:center;'>UNAVAILABLE: <br><br>All copies are currently checked out or on hold. ");
				echo("Please check later.</p>");
			}
			else{
				echo('<p style="text-align:center;"><strong>Note: </strong>Once confirmed, you will have <strong>3 days</strong> to pick it up.</p>');
				echo('<form method="POST" style="text-align:center;">');
				echo('<p>Expires: <strong>'.date_format($date, "l, m-d-Y g:i A (T)").'</strong></p>');
				echo('<br><input type="hidden" name="book_id" value="'.htmlentities($_REQUEST['book_id']).'">');
				echo('<input type="submit" name="submit" value="Submit" class="button">');
				echo('<input type="submit" name="cancel" value="Cancel" class="button" style="margin-left:10px;"></form>');
			}
		?>
		</div>
		<p style="text-align:center; margin-top:40px;"><a href='view.php?book_id=<?php echo htmlentities($_REQUEST['book_id']);?>'>Go Back</a></p>
	</body>
</html>