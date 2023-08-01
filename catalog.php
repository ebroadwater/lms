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
	$_SESSION['from'] = $_SERVER['REQUEST_URI'];
	
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
		// $for = "AND a.format= :for ";
		$for = "AND g.name = :for ";
		$include_for = TRUE;
	}
	$book_list = makeSearch($pdo, $secondpart, $_GET['q'], $for);

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
		<h1>LMS Catlog</h1>
		<?php 
			flashMessagesCenter();
		?>
		<form method="GET">
			<div class="search-bar-view">
				<div style="float:left;margin-right:20px;">
					<label for="type-text-view" class="above">Type:</label>
					<select name="type" id="type-text-view" class="above" style="background-color:#d5d5d5;>
						<option value="keyword">Keyword</option>
						<option value="title"<?php if ($_GET['type'] == "title"){ echo("selected");}?>>Title</option>
						<option value="author"<?php if ($_GET['type'] == "author"){ echo("selected");}?>>Author</option>
						<option value="series"<?php if ($_GET['type'] == "series"){ echo("selected");}?>>Series</option>
						<option value="genre"<?php if ($_GET['type'] == "genre"){ echo("selected");}?>>Genre</option>
					</select>
				</div>
				<div style="float:left;">
					<label for="format-type-view" class="above">Format:</label>
					<select name="format" id="format-type-view" class="above" style="background-color:#d5d5d5;>
						<option value="all"<?php if ($_GET['format'] == "all"){ echo("selected");}?>>All Formats</option>
						<?php
							if ($formats_list){
								foreach($formats_list as $format){
									$name = htmlentities($format['name']);
									echo("<option value='".$name."' ");
									if ($_GET['format'] == $name){
										echo("selected");
									}
									echo(">".ucfirst($name)."</option>");
								}
							}
						?>
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
					echo("<div class='book-row' style='border-bottom:1px solid #1C0F13; align-items:center;'>");
						echo("<div style='margin-top:10px; margin-bottom:10px;'>");
							if (isset($book['image_file'])){
								$file_name = htmlentities($book['image_file']);
								if (strlen($file_name) > 0 && $file_name !== NULL){
									echo("<img src='static/images/".$file_name."' width='130' height='200' style='padding-left:60px;margin-right:15px;'/>");
								}
							}
						echo("</div>");
						echo("<div class='book-info'>");
							echo("<div class='book-row'>");
								echo("<h3><a href='books/view.php?book_id=".htmlentities($book['book_id'])."'>".htmlentities($book['title'])."</a></h3>");
							echo("</div>");
							echo("<div class='book-row'>");
								echo(listAuthors($book, TRUE));
							echo("</div>");
							echo("<div class='book-row'>");
								echo("<p>Format: ".ucfirst(htmlentities($book['Format']))."</p>");
							echo("</div>");
							echo("<div class='book-row'>");
								echo("<p><strong>Available Copies: ".htmlentities($book['available_copies'])."/".htmlentities($book['total_copies'])."</strong></p>");
							echo("</div>");
						echo("</div>");
						echo("<div style='margin-top:25px; padding-right:60px;'>");
					
						echo("<div style='display:flex; flex-direction:column; justify-content:center; align-items:center;'>");
							echo("<form method='GET' action='books/place_hold.php'>");
								echo("<input type='hidden' name='book_id' value='".htmlentities($book['book_id'])."'>");
								echo("<input type='submit' value='Place Hold' name='place_hold' class='button' style='margin-bottom:20px;width:100px;'>");
								$_SESSION['from'] = "../catalog.php?type=".htmlentities($_GET['type'])."&format=".htmlentities($_GET['format'])."&q=".htmlentities($_GET['q'])."&search=Search";
							echo("</form>");
							echo("<form method='GET' action='books/checkout.php'>");
								echo("<input type='hidden' name='book_id' value='".htmlentities($book['book_id'])."'>");
								echo("<input type='hidden' name='user_id' value='".$_SESSION['user_id']."'>");
								echo("<input type='submit' value='Check Out' name='checkout' class='button' style='margin-bottom:20px; width:100px;'>");
								$_SESSION['from'] = "../catalog.php?type=".htmlentities($_GET['type'])."&format=".htmlentities($_GET['format'])."&q=".htmlentities($_GET['q'])."&search=Search";
							echo("</form>");
						echo("</div>");
						if ($staff){
							echo("<div style='display:flex; flex-direction:column; justify-content:center; align-items:center;'>");
								echo("<form method='GET' action='books/edit.php'>");
									echo("<input type='hidden' name='book_id' value='".htmlentities($book['book_id'])."'>");
									echo("<input type='submit' value='Edit' name='edit' class='button' id='edit-hover'>");
									$_SESSION['from'] = "../catalog.php?type=".htmlentities($_GET['type'])."&format=".htmlentities($_GET['format'])."&q=".htmlentities($_GET['q'])."&search=Search";
								echo("</form>");
								echo('<form><input type="button" class="button" name="delete" id="book-del-btn" value="Delete" onclick="deleteAlert('.$book['book_id'].')"></form>');
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