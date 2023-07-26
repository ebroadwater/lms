<?php 
	session_start();
	require_once "pdo.php";
	require_once "util.php";

	$staff = 0;
	$loggedin = isset($_SESSION['user_id']);
	if ($loggedin){
		$staff = $_SESSION['is_staff'];
	}
	if (!isset($_GET['type']) && !isset($_GET['search']) && !isset($_GET['format'])){
		$_SESSION['error'] = "Invalid request, search parameters missing";
		header("Location: index.php");
		return;
	}
	$secondpart = "";
	$book_list = null;
	$include_for = FALSE;
	$for = "";

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
	if ($_GET['format'] !== "all"){
		$for = "AND a.format= :for ";
		$include_for = TRUE;
	}
	$book_list = makeSearch($pdo, $secondpart, $_GET['q'], $for);
	
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
				<div style="float:left;margin-right:20px;">
					<label for="type-text-view" class="above">Type:</label>
					<select name="type" id="type-text-view" class="above">
						<option value="keyword">Keyword</option>
						<option value="title"<?php if ($_GET['type'] == "title"){ echo("selected");}?>>Title</option>
						<option value="author"<?php if ($_GET['type'] == "author"){ echo("selected");}?>>Author</option>
						<option value="series"<?php if ($_GET['type'] == "series"){ echo("selected");}?>>Series</option>
						<option value="genre"<?php if ($_GET['type'] == "genre"){ echo("selected");}?>>Genre</option>
					</select>
				</div>
				<div style="float:left;">
					<label for="format-type-view" class="above">Format:</label>
					<select name="format" id="format-type-view" class="above">
						<option value="all"<?php if ($_GET['format'] == "all"){ echo("selected");}?>>All Formats</option>
						<option value="book"<?php if ($_GET['format'] == "book"){ echo("selected");}?>>Book</option>
						<option value="ebook"<?php if ($_GET['format'] == "ebook"){ echo("selected");}?>>eBook</option>
						<option value="audiobook"<?php if ($_GET['format'] == "audiobook"){ echo("selected");}?>>Audiobook</option>
					</select>
				</div>
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
								echo(listAuthors($book, TRUE));
							echo("</div>");
							echo("<div class='book-row'>");
								echo("<p>Format: ".htmlentities($book['format'])."</p>");
							echo("</div>");
							echo("<div class='book-row'>");
								echo("<p>Available Copies: ".htmlentities($book['available_copies'])."/".htmlentities($book['total_copies'])."</p>");
							echo("</div>");
							if ($staff){
								echo("<div class='book-row'>");
									// echo("<a class='edit-button' href='books/edit.php?book_id=".htmlentities($book['book_id'])."'>Edit</a>");
									echo("<form method='GET' action='books/edit.php'>");
										echo("<input type='hidden' name='book_id' value='".htmlentities($book['book_id'])."'>");
										echo("<input type='submit' value='Edit' name='edit'>");
										$_SESSION['from'] = "../catalog.php?type=".htmlentities($_GET['type'])."&format=".htmlentities($_GET['format'])."&q=".htmlentities($_GET['q'])."&search=Search";
									echo("</form>");
									echo('<form><input type="button" name="delete" id="book-del-btn" value="Delete" onclick="deleteAlert('.$book['book_id'].')"></form>');
								echo("</div>");
								
							}
						echo("</div>");
					echo("</div>");
					$count++;
				}
			?>
		</div>
		<script>
			function deleteAlert(book_id){
				if (confirm("Are you sure you want to delete this book?")){
					const xmlhttp = new XMLHttpRequest();
					xmlhttp.open("POST", "books/delete.php?book_id=" + book_id);
					xmlhttp.send();
					window.location.reload(); //reload page 
				}
			}
		</script>
	</body>
</html>