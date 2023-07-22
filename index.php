<?php 
	session_start();
	require_once "pdo.php";
	require_once "util.php";

	$staff = isset($_SESSION['is_staff']);
	$loggedin = isset($_SESSION['user_id']);
?>
<!DOCTYPE html>
<html>
	<header>
		<title>Emma's LMS Homepage</title>
		<?php require_once "head.php";?>
	</header>
	<body>
		<ul class="nav">
			<li class="nav-link">
			<?php 
				if ($loggedin){
					if ($staff){
						echo('<a href="add.php">Add Books</a>');
						echo('<a href="member_edit.php">Edit Members</a>');
					}
					echo('<a href="logout.php">Log Out</a>');
				}
				else{
					echo('<a href="login.php">Log In</a></li>');
					echo('<li class="nav-link"><a href="signup.php">Sign Up</a>');
				}
			?>
			</li>
		</ul>
		<h1>LMS Homepage</h1>
		<?php 
			flashMessagesCenter();
		?>
		<form method="GET">
			<div class="search-bar">
				<label for="type-text">Type:</label>
				<select name="type" id="type-text">
					<option value="keyword">Keyword</option>
					<option value="title">Title</option>
					<option value="author">Author</option>
					<option value="series">Series</option>
					<option value="genre">Genre</option>
				</select>
				<input type="text" id="search-text">
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
				// echo("<h4>Edit Members</h4>");
				// echo("<div class='admin-view-row'>");
				// echo("</div>");
				// echo("<h4>Edit Catalog</h4>");
				// echo("<div class='admin-view-row'>");
				// echo("</div>");
				echo("</div>");
			}
			else{
				echo("<h4>Explore favorites titles!</h4>");
				echo("<div class='home-view-row'>");
				echo("<img src='static/images/100yrs.png' width='170' height='250'/>");
				echo("<img src='static/images/lexicon.png' width='170' height='250'/>");
				echo("<img src='static/images/percy_jackson.png' width='170' height='250'/>");
				echo("<img src='static/images/the_power.png' width='170' height='250'/>");
				echo("</div>");
			}
		?>
	</body>
</html>