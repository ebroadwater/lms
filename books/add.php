<?php 
	session_start();
	require_once "../pdo.php";
	require_once "../util.php";

	if (!isset($_SESSION['user_id']) || $_SESSION['is_staff'] === false){
		die("ACCESS DENIED");
		header("Location: ../index.php");
		return;
	}
	if (isset($_POST['cancel'])){
		header("Location: ../index.php");
		return;
	}
	if (isset($_POST['add']) && isset($_POST['title']) && isset($_POST['publisher']) && isset($_POST['yr_published']) 
		&& isset($_POST['total_copies']) && isset($_POST['author_fname1']) && isset($_POST['author_lname1']) 
		&& isset($_POST['genre_name1']) && isset($_POST['format']) && isset($_POST['available_copies'])){
			$msg = validateBook();
			if (is_string($msg)){
				$_SESSION['error'] = $msg;
				header("Location: add.php");
				return;
			}
			$msg = validateAuthor();
			if (is_string($msg)){
				$_SESSION['error'] = $msg;
				header("Location: add.php");
				return;
			}
			$msg = validateGenre();
			if (is_string($msg)){
				$_SESSION['error'] = $msg;
				header("Location: add.php");
				return;
			}
			if (isset($_POST['book-cover'])){
				$msg = validateImage('book-cover');
				if (is_string($msg)){
					$_SESSION['error'] = $msg;
					header("Location: add.php");
					return;
				}
			}
			$result = insertBook($pdo, 'book-cover');
			if (is_string($result)){
				$_SESSION['error'] = $result;
				header("Location: add.php");
				return;
			}

			$_SESSION['success'] = "Book added"; 
			header("Location: ../index.php");
			return;
	}
	$formats_list = listFormats($pdo);
	if ($formats_list === false){
		$_SESSION['error'] = "Unable to fetch formats";
		header("Location: add.php");
		return;
	}

?>
<!DOCTYPE html>
<html>
	<head>
		<title>Add New Book</title>
		<?php require_once "../head.php";?>
		<link rel='stylesheet' href='../static/css/starter.css'>
	</head>
	<body>
		<ul class="nav">
			<li class="nav-link">
			<?php 
				echo('<a href="../index.php">Home</a>');
				echo('<a href="add.php">Add Book</a>');
				echo('<a href="../members/member-add.php">Add Member</a>');
				echo('<a href="../members.php">Members</a>');
				echo('<a href="../logout.php">Log Out</a>');
			?>
			</li>
		</ul>
		<h1 class="login-form">Add New Book</h1>
		<?php 
			flashMessages();
		?>
		<form method="POST" enctype="multipart/form-data">
			<div class="login-page">
				<p><strong>Title: </strong><input type="text" name="title" id="book_title" size="50"></p>
				<p>
					<strong>Author(s): </strong><input type="submit" id="addAuthor" value="+" class="plus">
					<div id="author_fields">
						<div id="author1">
							<p>
								First Name:
								<input type="text" name="author_fname1" value=""/>
								Last Name: 
								<input type="text" name="author_lname1" value=""/>
							</p>
							<p>
								<input type="checkbox" name="is_translator1" value="Translator" id="tra1"/>
								<label for="tra1">Translator</label>
							</p>
						</div>
					</div>
				</p>
				<p><strong>Series (optional): </strong><input type="text" name="series" id="book_series" size="44"></p>
				<p><strong>Publisher: </strong><input type="text" name="publisher" id="book_publisher" class="pub" size="44"></p>
				<p><strong>Year Published: </strong><input type="text" name="yr_published" id="book_pub_yr" size="16"></p>
				<p><strong>Edition (optional): </strong><input type="text" name="edition" id="book_edition" size="44"></p>
				<p>
					<strong>Genre(s): </strong><input type="submit" id="addGenre" value="+" class="plus">
					<div id="genre_fields">
						<div id="genre1">
							<p>
								Name:
								<input type="text" name="genre_name1" class="genre" value=""/>
							</p>
						</div>
					</div>
				</p>
				<p><strong>Total Copies: </strong><input type="number" name="total_copies"></p>
				<p><strong>Available Copies: </strong><input type="number" name="available_copies"></p>
				<label for="format-type">Type:</label>
				<select name="format" id="format-type">
					<?php
						if ($formats_list){
							foreach($formats_list as $format){
								$name = htmlentities($format['name']);
								echo("<option value='".$name."'>".ucfirst($name)."</option>");
							}
						}
					?>
				</select>
				<p><strong>Description (optional): </strong><br><br><textarea name="description" rows="8" cols="80"></textarea></p>
				<input type="hidden" name="MAX_FILE_SIZE" value="4000000" />
				<p><strong>Book Cover (optional): </strong><input type="file" name="book-cover"></p>
				<p>
					<input type="submit" class="button" name="add" value="Add">
					<input type="submit" class="button" name="cancel" value="Cancel">
				</p>
			</div>
		</form>
		<script>
			countAuthor = 1;
			countGenre = 1;
			$(document).ready(function(){
				window.console && console.log("Document ready called");
				$('#addAuthor').click(function(){
					event.preventDefault();
					if (countAuthor >= 5){
						alert("Maximum of five authors entered");
						return;
					}
					countAuthor++;
					window.console && console.log("Adding author " + countAuthor);
					var source = $('#author-template').html();
					$('#author_fields').append(source.replace(/@COUNT@/g, countAuthor));
				});
				$('#addGenre').click(function(){
					event.preventDefault();
					if (countGenre >= 7){
						alert("Maximum of seven genres entered");
						return;
					}
					countGenre++;
					window.console && console.log("Adding genre " + countGenre);
					var source = $('#genre-template').html();
					$('#genre_fields').append(source.replace(/@COUNT@/g, countGenre));

					$('.genre').autocomplete({
						source: "../genre.php"
					});
				});

				$('.genre').autocomplete({
					source: "../genre.php"
				});

				$('.pub').autocomplete({
					source: "../publisher.php"
				});
			});
		</script>
		<script id="author-template" type="text">
			<div id="author@COUNT@">
				<p>
					First Name:
					<input type="text" name="author_fname@COUNT@" value=""/>
					Last Name: 
					<input type="text" name="author_lname@COUNT@" value=""/>
					<input type="button" class="minus" value="-" onclick="$('#author@COUNT@').remove(); countAuthor--; return false;"/>
				</p>
				<p>
					<input type="checkbox" name="is_translator@COUNT@" value="Translator" id="tra@COUNT@"/>
					<label for="tra@COUNT@">Translator</label>
				</p>
			</div>
		</script>
		<script id="genre-template" type="text">
			<div id="genre@COUNT@">
				<p>
					Name:
					<input type="text" name="genre_name@COUNT@" class="genre" value=""/>
					<input type="button" class="minus" value="-" onclick="$('#genre@COUNT@').remove(); countGenre--; return false;"/>
				</p>
			</div>
		</script>
	</body>
</html>