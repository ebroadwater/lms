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
		header("Location: view.php?book_id=".$_GET['book_id']);
		return;
	}
	$staff = 0;
	$loggedin = isset($_SESSION['user_id']);
	if ($loggedin){
		$staff = $_SESSION['is_staff'];
	}
	$book = getBook($pdo, $_REQUEST['book_id']);
	if ($book === false){
		$_SESSION['error'] = "Could not load book";
		header("Location: view.php?book_id=".$_REQUEST['book_id']);
		return;
	}
	$author_list_start = explode(";", $book['Authors']);
	$author_id_list = explode(",", $book['Author_ids']);
	$author_translator_list = array();
	$author_list = array();
	foreach($author_list_start as $au){
		$one = explode(":", $au);
		array_push($author_translator_list, $one[1]);
		array_push($author_list, $one[0]);
	}
	$date = new DateTime();
	$date->modify('+3 day');
	$date->setTimezone(new DateTimeZone('America/New_York'));
	$time = date_format($date, "Y-m-d H:i:s");

	$is_available = true;
	if (htmlentities($book['available_copies']) < 1){
		$is_available = false;
	} 

	if (isset($_POST['submit'])){
		//Check if the book is available 
		if (htmlentities($book['available_copies']) < 1){
			$_SESSION['error'] = "Unable to place hold. There are no copies currently available.";
			header("Location: view.php?book_id=".htmlentities($_REQUEST['book_id']));
			return;
		} 
		//Check that user doesn't already have a hold on the book
		$stmt = $pdo->prepare('SELECT * FROM Hold WHERE user_id=:uid AND book_id=:bid');
		$stmt->execute(array(
			':uid' => $_SESSION['user_id'],
			':bid' => $_POST['book_id']
		));
		$hold = $stmt->fetch(PDO::FETCH_ASSOC);
		if ($hold !== false){
			$_SESSION['error'] = "You already have a hold on this book";
			header("Location: ../index.php");
			return;
		}
		$stmt = $pdo->prepare('INSERT INTO Hold (end_time, book_id, user_id) VALUES (:et, :bid, :uid)');
		$stmt->execute(array(
			':et' => $time, 
			':bid' => $_POST['book_id'],
			':uid' => $_SESSION['user_id']
		));
		$copies = htmlentities($book['available_copies']) - 1;
		//Update available copies for Book
		$stmt = $pdo->prepare('UPDATE Book SET available_copies=:ac WHERE book_id=:bid');
		$stmt->execute(array(
			':ac' => $copies, 
			':bid' => $_REQUEST['book_id']
		));
		$_SESSION['success'] = "Hold placed successfully";
		header("Location: ../index.php");
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
					echo('<a href="../members/members-edit.php?user_id='.$_SESSION['user_id'].'">Profile</a>');
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
			flashMessages();
		?>
		<p style="font-size:1.2em; text-align:center; margin-top:80px;">
			<?php 
				echo("<i>");
				echo htmlentities($book['title']);
				echo(" </i>- ");

				$index = 0;
				foreach($author_list as $author){
					$name = explode(",", $author);
					$fname = htmlentities($name[1]);
					$lname = htmlentities($name[0]);
					echo($fname." ".$lname);
					if ($author_translator_list[$index] == 1){
						echo " (Translator)";
					}
					if ($index + 1 < count($author_list)){
						echo(" and ");
					}
					$index++;
				}
			?> 
		</p>
		<br>
		<div style="border:solid 1px black; width:40%; margin:auto; padding:30px; margin-top:25px; text-align:center;">
		<?php
			//If not avaialable 
			if (!$is_available){
				echo("<p style='color:red; text-align:center;'>UNAVAILABLE: <br><br>All copies are currently checked out or on hold. ");
				echo("Please check later.</p>");
			}
			else{
				echo('<p style="text-align:center;"><strong>Note: </strong>Once confirmed, you will have <strong>3</strong> days to pick it up.</p>');
				echo('<form method="POST" style="text-align:center;">');
				echo('<p>Expires: <strong>'.date_format($date, "l, m-d-Y g:i A (T)").'</strong></p>');
				echo('<input type="hidden" name="book_id" value="'.htmlentities($_REQUEST['book_id']).'">');
				echo('<input type="submit" name="submit" value="Submit">');
				echo('<input type="submit" name="cancel" value="Cancel" style="margin-left:10px;"></form>');
			}
		?>
		</div>
		<p style="text-align:center; margin-top:40px;"><a href='view.php?book_id=<?php echo htmlentities($_REQUEST['book_id']);?>'>Go Back</a></p>
	</body>
</html>