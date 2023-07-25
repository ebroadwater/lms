<?php 
	session_start();
	require_once "pdo.php";
	require_once "util.php";

	$staff = 0;
	$loggedin = isset($_SESSION['user_id']);
	if ($loggedin){
		$staff = $_SESSION['is_staff'];
	}
	if (!isset($_GET['type']) && !isset($_GET['search'])){
		$_SESSION['error'] = "Invalid request, search parameters missing";
		header("Location: index.php");
		return;
	}
	$secondpart = "";
	$book_list = null;

	if (strlen($_GET['q']) > 0){
		$type = $_REQUEST['type'];
		if ($type == "title"){
			$secondpart = "WHERE a.title LIKE :temp ";
		}
		else if ($type == "series"){
			$secondpart = "WHERE a.series LIKE :temp ";
		}
		else if ($type == "genre"){
			$secondpart = "WHERE c.name LIKE :temp ";
		}
		else if ($type == "author"){
			$secondpart = "WHERE f.first_name LIKE :temp OR f.last_name LIKE :temp ";
		}
		else{
			$secondpart = "WHERE a.title LIKE :temp OR a.series LIKE :temp OR a.year_published LIKE :temp OR 
							c.name LIKE :temp OR d.name LIKE :temp OR f.first_name LIKE :temp OR f.last_name LIKE :temp ";
		}
	}
	$book_list = makeSearch($pdo, $secondpart, $_GET['q']);
	
?>
<!DOCTYPE html>
<html>
	<head>
		<title>LMS Catalog</title>
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
					echo('<a href="members/members-edit.php?user_id='.$_SESSION['user_id'].'">Profile</a>');
					echo('<a href="logout.php">Log Out</a>');
				}
				else{
					echo('<a href="login.php">Log In</a>');
					echo('<a href="signup.php">Sign Up</a>');
				}
			?>
			</li>
		</ul>
		<h1>LMS Catlog</h1>
		<?php 
			flashMessagesCenter();
		?>
		<form method="GET">
			<div class="search-bar-view">
				<label for="type-text-view">Type:</label>
				<select name="type" id="type-text-view">
					<option value="keyword">Keyword</option>
					<option value="title"<?php if ($_GET['type'] == "title"){ echo("selected");}?>>Title</option>
					<option value="author"<?php if ($_GET['type'] == "author"){ echo("selected");}?>>Author</option>
					<option value="series"<?php if ($_GET['type'] == "series"){ echo("selected");}?>>Series</option>
					<option value="genre"<?php if ($_GET['type'] == "genre"){ echo("selected");}?>>Genre</option>
				</select>
				<input type="text" id="search-text" name="q" value="<?php echo $_GET['q']?>">
				<input type="submit" name="search" class="button" value="Search">
			</div>	
		</form>
		<?php 
			$count = count($book_list);
			echo("<h4>Number of results: ".$count."</h4>");
		?>
		<div class="book-page">
			<?php 
				foreach($book_list as $book){
					echo("<div class='book-row'>");
						echo("<div class='book-info'>");
							echo("<div class='book-row'>");
								echo("<h4><a href='books/view.php?book_id=".htmlentities($book['book_id'])."'>".htmlentities($book['title'])."</a></h4>");
							echo("</div>");
							echo("<div class='book-row'>");
								$author_list = explode(";", $book['Authors']);
								$author_id_list = explode(",", $book['Author_ids']);
								$index = 0;
								foreach($author_list as $author){
									echo("<p><a href='catalog.php?type=author&q=");
									$name = explode(",", $author);
									$fname = str_replace(' ', '', htmlentities($name[1]));
									$lname = str_replace(' ', '', htmlentities($name[0]));
									$q = $fname."+".$lname;
									echo($q."&search=Search'>");
									echo("<i>".htmlentities($author));
									if ($index + 1 < count($author_list)){
										echo(",  ");
									}
									echo("</i></a></p>");
									$index++;
								}
							echo("</div>");
							echo("<div class='book-row'>");
								echo("<p>Publisher: ".$book['Publisher']."</p>");
							echo("</div>");
							echo("<div class='book-row'>");
								echo("<p>Available Copies: ".$book['available_copies']."/".$book['total_copies']."</p>");
							echo("</div>");
						echo("</div>");
					echo("</div>");
					$count++;
				}
			?>
		</div>
	</body>
</html>