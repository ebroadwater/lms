<?php 
	session_start();
	require_once "../pdo.php";
	require_once "../util.php";

	if (!isset($_SESSION['user_id'])){
		$_SESSION['from'] = $_SERVER['REQUEST_URI'];
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

	if (!isset($_REQUEST['book_id'])){
		$_SESSION['error'] = "No book specified";
		header("Location: ../index.php");
	}
	$book = getBook($pdo, $_REQUEST['book_id']);
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
	$date->modify('+14 day');
	$time = date_format($date, "Y-m-d H:i:s");

	$is_available = true;
	if (htmlentities($book['available_copies']) < 1){
		$is_available = false;
	} 
	//Check if user has a hold on the Book that isn't expired
	$stmt = $pdo->prepare('SELECT * FROM Hold WHERE user_id=:uid AND book_id=:bid AND end_time >= :et');
	$stmt->execute(array(
		':uid' => $_REQUEST['user_id'], 
		':bid' => $_REQUEST['book_id'], 
		':et' => $start
	));
	$on_hold = false;
	$hold_books = $stmt->fetchAll(PDO::FETCH_ASSOC);
	if (count($hold_books) > 0){
		$on_hold = true;
	}
	if (isset($_POST['submit'])){
		if (!$is_available && !$on_hold){
			$_SESSION['error'] = "Unable to check out. There are currently no copies available.";
			header("Location: ".$_SERVER['REQUEST_URI']);
			return;
		}
		//Check that user didn't already check out book
		$stmt = $pdo->prepare('SELECT * FROM Checkout WHERE user_id=:uid AND book_id=:bid');
		$stmt->execute(array(
			':uid' => $_SESSION['user_id'], 
			':bid' => $_REQUEST['book_id']
		));
		$row = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if (count($row) > 0){
			$_SESSION['error'] = "You already have this book checked out";
			header("Location: ".$_SERVER['REQUEST_URI']);
			return;
		}
		//Check that user doesn't have more than 5 books checked out at once 
		$stmt = $pdo->prepare('SELECT * FROM Checkout WHERE user_id=:uid');
		$stmt->execute(array(
			':uid' => $_REQUEST['user_id']
		));
		$already_checked_out = $stmt->fetchAll(PDO::FETCH_ASSOC);
		if (count($already_checked_out) >= 5){
			$_SESSION['error'] = "Unable to check out. You already have 5 books checked out.";
			header("Location: ".$_SERVER['REQUEST_URI']);
			return;
		}
		//Insert into Checkout
		$stmt = $pdo->prepare('INSERT INTO Checkout (start_time, end_time, book_id, user_id) VALUES (:st, :et, :bid, :uid)');
		$stmt->execute(array(
			':st' => $start, 
			':et' => $time, 
			':bid' => $_REQUEST['book_id'], 
			':uid' => $_REQUEST['user_id']
		));
		//Update Hold if user has Book on hold
		if ($on_hold){
			$stmt = $pdo->prepare('UPDATE Hold SET end_time=:et WHERE user_id=:uid AND book_id=:bid');
			$stmt->execute(array(
				':et' => $start, 
				':uid' => $_REQUEST['user_id'], 
				':bid' => $_REQUEST['book_id']
			));
		}
		else{
			$copies = htmlentities($book['available_copies']) - 1;
			//Update available copies for Book
			updateAvailableCopies($pdo, $copies, $_REQUEST['book_id']);
		}

		$_SESSION['success'] = "Book checked out successfully";
		header("Location: ../members/profile.php?user_id=".$_REQUEST['user_id']);
		return;
	}
	// CONSIDER notifications not to email but to user inbox on the site -- alerts when using, unread messages, etc
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Checkout <?php echo $book['title']?></title>
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
		<h1>Checkout</h1>
		<?php 
			flashMessagesCenter();
		?>
		<p style="font-size:1.2em; text-align:center; margin-top:70px;">
			<?php 
				echo listBookandAuthor($pdo, $_REQUEST['book_id']);
			?> 
		</p>
		<br>
		<?php
			if ($on_hold){
				echo("<p style='text-align:center; color:red;'>You currently have this book on hold, checking it out will remove this hold.</p>");
			}
		?>
		<div style="border:solid 1px black; width:40%; margin:auto; padding:30px; margin-top:25px; text-align:center;">
		<?php
			//If not avaialable 
			if (!$is_available && !$on_hold){
				echo("<p style='color:red; text-align:center;'>UNAVAILABLE: <br><br>All copies are currently checked out or on hold. ");
				echo("Please check later.</p>");
			}
			else{
				echo('<p style="text-align:center;"><strong>Note: </strong>Once confirmed, you will have it checked out for <strong>2 weeks</strong>.</p>');
				echo('<p style="text-align:center; margin-top: 30px; line-height:30px;">Books can later be renewed. <br>If returned late, you will be fined <strong>$1.00</strong> for each extra day.</p>');
				echo('<form method="POST" style="text-align:center;">');
				echo('<p style="margin-top:35px; margin-bottom:25px;">Due Date: <strong>'.date_format($date, "l, m-d-Y g:i A (T)").'</strong></p>');
				echo('<input type="hidden" name="book_id" value="'.htmlentities($_REQUEST['book_id']).'">');
				echo('<input type="submit" name="submit" value="Submit">');
				echo('<input type="submit" name="cancel" value="Cancel" style="margin-left:10px;"></form>');
			}
		?>
		</div>
		<p style="text-align:center; margin-top:40px;"><a href='view.php?book_id=<?php echo htmlentities($_REQUEST['book_id']);?>'>Go Back</a></p>
	</body>
</html>