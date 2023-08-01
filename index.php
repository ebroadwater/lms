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
	$date = new DateTime();
	$date->setTimezone(new DateTimeZone('America/New_York'));
	if ($staff){
		$date = new DateTime();
		$date->setTimezone(new DateTimeZone('America/New_York'));
		$start = date_format($date, "Y-m-d H:i:s");

		$stmt = $pdo->prepare("SELECT a.start_time, a.end_time, a.book_id, a.user_id, a.is_returned, b.first_name, b.last_name, b.email FROM Checkout a
								INNER JOIN users b ON b.user_id=a.user_id INNER JOIN Book c ON a.book_id=c.book_id WHERE a.is_returned=:ir");
		$stmt->execute(array(
			':ir' => 0
		));
		$checked_out_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

		$stmt = $pdo->prepare('SELECT * FROM Hold WHERE end_time >= :st');
		$stmt->execute(array(
			':st' => $start
		));
		$hold_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
				<div style="margin-right:20px;">
					<label for="type-text" class="above">Type:</label>
					<select name="type" id="type-text" class="above" style="background-color:#d5d5d5;">
						<option value="keyword">Keyword</option>
						<option value="title">Title</option>
						<option value="author">Author</option>
						<option value="series">Series</option>
						<option value="genre">Genre</option>
					</select>
				</div>
				<div >
					<label for="format-type" class="above">Format:</label>
					<select name="format" id="format-type" class="above" style="background-color:#d5d5d5;>
						<option value="all">All Formats</option>
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
					// echo("<h4>Today's Transactions</h4>");
					// echo("<div class='admin-view-row'>");
						
					// echo("</div>");
					// echo("<h4>All Transactions</h4>");
					// echo("<div class='admin-view-row'>");
					// echo("</div>");
					echo("<h4>Overdue Books</h4>");
					echo("<div class='admin-view-row'>");
						echo("<div style='display:flex; flex-direction:column;'>");
						if ($checked_out_list){
							foreach($checked_out_list as $check){
								if ($check['end_time'] < $start){
									$date1 = DateTime::createFromFormat ( "Y-m-d H:i:s", $check["end_time"] );
									$fine = $date1->diff($date);
									$fine = $fine->days;
									$book = getBook($pdo, $check['book_id']);
									echo("<div style='display:flex; margin:20px; align-items:center;'>");
										if (isset($book['image_file'])){
											$file_name = htmlentities($book['image_file']);
											if (strlen($file_name) > 0 && $file_name !== NULL){
												echo("<img src='static/images/".$file_name."' width='170' height='250' style='margin-right:30px;'/>");
											}
										}
										echo("<div>");
										echo(listInfo($book));
										echo("<p>Member: <a href='members/profile.php?user_id=".htmlentities($check['user_id'])."'>".htmlentities($check['first_name'])." ".htmlentities($check['last_name'])." (".htmlentities($check['email']).")</a></p>");
										echo("<p>Day(s) Overdue: ".$fine."</p>");
										echo("</div>");
									echo("</div>");
								}
							}
						}
						echo("</div>");
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