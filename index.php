<?php 
	session_start();
	require_once "pdo.php";
	require_once "util.php";

	$staff = 0;
	$loggedin = isset($_SESSION['user_id']);
	if ($loggedin){
		$staff = $_SESSION['is_staff'];
	}
	$formats_list = listFormats($pdo);
	if ($formats_list === false){
		$_SESSION['error'] = "Unable to fetch formats";
		header("Location: index.php");
		return;
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Emma's LMS Homepage</title>
		<?php require_once "head.php";?>
	</head>
	<body>
		<ul class="nav">
			<li class="nav-link">
			<?php 
				echo('<a href="index.php">Home</a>');
				if ($loggedin){
					if ($staff){
						echo('<a href="books/add.php">Add Book</a>');
						echo('<a href="members/member-add.php">Add Member</a>');
						echo('<a href="members.php">Members</a>');
					}
					echo('<a href="members/profile.php?user_id='.$_SESSION['user_id'].'">Profile</a>');
					echo('<a href="logout.php">Log Out</a>');
				}
				else{
					echo('<a href="login.php">Log In</a>');
					echo('<a href="signup.php">Sign Up</a>');
				}
			?>
			</li>
		</ul>
		<h1>LMS Homepage</h1>
		<?php 
			flashMessagesCenter();
		?>
		<form method="GET" action="catalog.php">
			<div class="search-bar">
				<div style="float:left;margin-right:20px;">
					<label for="type-text" class="above">Type:</label>
					<select name="type" id="type-text" class="above">
						<option value="keyword">Keyword</option>
						<option value="title">Title</option>
						<option value="author">Author</option>
						<option value="series">Series</option>
						<option value="genre">Genre</option>
					</select>
				</div>
				<div style="float:left;">
					<label for="format-type" class="above">Format:</label>
					<select name="format" id="format-type" class="above">
						<option value="all">All Formats</option>
						<!-- <option value="book">Book</option>
						<option value="title">eBook</option>
						<option value="audiobook">Audiobook</option> -->
						<?php
							if ($formats_list){
								foreach($formats_list as $format){
									$name = htmlentities($format['name']);
									echo("<option value='".$name."'>".ucfirst($name)."</option>");
								}
							}
						?>
					</select>
				</div>
				<input type="text" id="search-text" name="q">
				<input type="submit" name="search" class="button" value="Search">
			</div>	
		</form>
		<?php 
			if ($staff){
				echo("<div class='admin-view'>");
				echo("<h4>Today's Transactions</h4>");
				echo("<div class='admin-view-row'>");
				echo("</div>");
				echo("<h4>All Transactions</h4>");
				echo("<div class='admin-view-row'>");
				echo("</div>");
				echo("<h4>Overdue Books</h4>");
				echo("<div class='admin-view-row'>");
				echo("</div>");
				echo("</div>");
			}
			else{
				echo("<h4>Explore favorites titles!</h4>");
				echo("<div class='home-view-row'>");
				echo("<img src='static/images/100-years-of-solitude.png' width='170' height='250'/>");
				echo("<img src='static/images/lexicon.png' width='170' height='250'/>");
				echo("<img src='static/images/the-lightning-thief.png' width='170' height='250'/>");
				echo("<img src='static/images/the-power.png' width='170' height='250'/>");
				echo("</div>");
			}
		?>
	</body>
</html>