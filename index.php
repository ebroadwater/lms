<?php 
	session_start();
	require_once "pdo.php";
?>
<!DOCTYPE html>
<html>
	<header>
		<title>Emma's LMS Homepage</title>
		<!-- <link rel='stylesheet' href='starter.css'> -->
		<?php require_once "head.php";?>
	</header>
	<body>
		<ul class="nav">
			<li class="nav-link">
			<?php 
				$loggedin = isset($_SESSION['user_id']);

				if ($loggedin){
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
	</body>
</html>