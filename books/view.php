<?php 
	session_start();
	require_once "../pdo.php";
	require_once "../util.php";

	$staff = 0;
	$loggedin = isset($_SESSION['user_id']);
	if ($loggedin){
		$staff = $_SESSION['is_staff'];
	}
	$back = "../index.php";
	if (isset($_SESSION['from'])){
		$back = $_SESSION['from'];
	}
	$book = getBook($pdo, $_GET['book_id']);
	if ($book === false){
		$_SESSION['error'] = "Could not load book";
		header("Location: ".$back);
		unset($_SESSION['from']);
		return;
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<title><?php echo $book['title'];?></title>
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
		<h1><?php echo $book['title'];?></h1>
		<?php 
			flashMessagesCenter();
		?>
		<?php 
			if (isset($book['image_file'])){
				$file_name = htmlentities($book['image_file']);
				if (strlen($file_name) > 0 && $file_name !== NULL){
					echo("<img src='../static/images/".$file_name."' width='170' height='250'/>");
				}
			}
		?>
		<div>
			<p>Author(s): <?php echo(listAuthors($book, FALSE));?> </p>
		</div>
		<p>Format: <?php echo(htmlentities($book['Format']));?></p>
		<h4>Available Copies:</h4>
		<?php 
			echo("<p>".htmlentities($book['available_copies'])." of ".htmlentities($book['total_copies'])." available");
		?>
		<?php 
			if (htmlentities($book['series']) !== null){
				echo("<h4>Series:</h4>");
				echo("<a href='../catalog.php?type=series&q=".$book['series']."&search=Search'>".htmlentities($book['series'])."</a>");
			}
		?>
		<h4>Publisher:</h4>
		<?php 
			echo("<p>".htmlentities($book['Publisher'])." (".htmlentities($book['year_published']).")</p>");
		?>
		<h4>Edition:</h4>
		<?php 
			if (isset($book['edition'])){
				echo("<p>".htmlentities($book['edition'])."</p>");
			}
		?>
		<h4>Genres:</h4>
		<ul>
		<?php 
			$genres = explode(',', $book['Genres']);
			foreach($genres as $genre){
				echo("<li>".$genre."</li>");
			}
		?>
		</ul>
		<h4>Description:</h4>
		<?php 
			echo("<p>".htmlentities($book['description'])."</p>");
		?>
		<p>
			<form method="GET" action="place_hold.php">
				<input type="submit" value="Place Hold" name="place_hold">
				<input type="hidden" value="<?php echo htmlentities($book['book_id'])?>" name="book_id">
			</form>
			<form method="GET" action="checkout.php">
				<input type="submit" value="Check Out" name="checkout">
				<input type="hidden" value="<?php echo htmlentities($book['book_id'])?>" name="book_id">
			</form>
		</p>
		<?php
			echo("<p><a href='".$back."'>Go Back</a></p>");
		?>
	</body>
</html>
