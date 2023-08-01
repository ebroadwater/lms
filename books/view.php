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
		<h1><?php echo $book['title'];?></h1>
		<?php 
			flashMessagesCenter();
		?>
		<?php 
			echo("<div style='display:flex;flex-direction:column;'>");
			echo("<div style='display:flex;'>");
			if (isset($book['image_file'])){
				$file_name = htmlentities($book['image_file']);
				if (strlen($file_name) > 0 && $file_name !== NULL){
					echo("<img src='../static/images/".$file_name."' width='300' height='450'/>");
				}
			}
			echo("<div style='display:flex; margin-left:80px; width:80%; align-items:center;'>");
		?>
		<div style="width:50%; display:flex; flex-direction:column;">
		<h3 style="text-align:left;">Author(s): </h3> <?php echo(listAuthors($book, FALSE));?><br>
		<h3 style="text-align:left">Format:</h3> <?php echo("<p>".ucfirst(htmlentities($book['Format']))."</p>");?>
		<h3 style="text-align:left;">Available Copies:</h3>
		<?php 
			echo("<p>".htmlentities($book['available_copies'])." of ".htmlentities($book['total_copies'])." available </p>");
		?>
		</div>
		<div style="width:50%; padding-right:15px; display:flex; flex-direction:column;">
		<?php 
			if (htmlentities($book['series']) !== null && htmlentities($book['series']) !== ""){
				echo("<h3 style='text-align:right;'>Series:</h3>");
				echo("<a href='../catalog.php?type=series&q=".$book['series']."&search=Search'>".htmlentities($book['series'])."</a>");
			}
		?>
		<h3 style='text-align:left;'>Publisher:</h3>
		<?php 
			echo("<p>".htmlentities($book['Publisher'])." (".htmlentities($book['year_published']).")</p>");
		?>
		<?php 
			if (htmlentities($book['edition']) !== null && htmlentities($book['edition']) !== ""){
				echo('<h3 style="text-align:left;">Edition:</h3>');
				echo("<p>".htmlentities($book['edition'])."</p>");
			}
		?>
		<h3 style='text-align:left;'>Genres:</h3>
		<ul>
		<?php 
			$genres = explode(',', $book['Genres']);
			foreach($genres as $genre){
				echo("<li>".$genre."</li>");
			}
			echo("</div>");
			echo("</div>");
		?>
		</ul>
		</div>
		<div style='display:flex;'>
		<div style='display:block;'>
		<h4>Description:</h4>
		<?php 
			echo("<p style='width:85%;text-align:center; margin:auto; font-size:1.2em;'>".htmlentities($book['description'])."</p>");
			echo("</div>");
		?>
		</div>
		<div style="display:flex; margin-top:25px; justify-content:center; text-align:center; gap:15px;">
		<p>
			<form method="GET" action="place_hold.php">
				<input type="submit" value="Place Hold" name="place_hold" class="button" style="width:100px;">
				<input type="hidden" value="<?php echo htmlentities($book['book_id'])?>" name="book_id">
			</form>
			<form method="GET" action="checkout.php">
				<input type="submit" value="Check Out" name="checkout" class="button" style="width:100px;">
				<input type="hidden" value="<?php echo htmlentities($book['book_id'])?>" name="book_id">
				<input type="hidden" value="<?php echo $_SESSION['user_id'];?>" name="user_id">
			</form>
			<!-- ADD RENEW OPTION -->
		</p>
		</div>
		<?php
			echo("<p style='text-align:center;'><a href='".$back."'>Go Back</a></p>");
		?>
	</body>
</html>
