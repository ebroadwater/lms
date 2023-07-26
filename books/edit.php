<?php 
	session_start();
	require_once "../pdo.php";
	require_once "../util.php";

	if (!isset($_SESSION['user_id']) || $_SESSION['is_staff'] === false){
		die("ACCESS DENIED");
		return;
	}
	if (isset($_POST['cancel'])){
		header("Location: ".$_SESSION['from']);
		unset($_SESSION['from']);
		return;
	}
	if (!isset($_GET['book_id'])){
		$_SESSION['error'] = "Invalid book_id";
		header("Location: ".$_SESSION['from']);
		unset($_SESSION['from']);
		return;
	}
	$book_id = htmlentities($_GET['book_id']);

	$book = getBook($pdo, $book_id);
	if ($book === false){
		$_SESSION['error'] = "Could not load book";
		header("Location: ".$_SESSION['from']);
		unset($_SESSION['from']);
		return;
	}

	$author_list_start = explode(";", $book['Authors']);
	$author_id_list = explode(",", $book['Author_ids']);
	$author_translator_list = array();
	$author_list_name = array(); //contains author's first and last names 
	foreach($author_list_start as $au){
		$one = explode(":", $au);
		array_push($author_translator_list, $one[1]);
		array_push($author_list_name, $one[0]);
	}

	if (isset($_POST['save']) && isset($_POST['title']) && isset($_POST['publisher']) && isset($_POST['yr_published']) 
		&& isset($_POST['total_copies']) && isset($_POST['author_fname1']) && isset($_POST['author_lname1']) 
		&& isset($_POST['genre_name1']) && isset($_POST['format']) && isset($_POST['available_copies'])){
			$msg = validateBook();
			if (is_string($msg)){
				$_SESSION['error'] = $msg;
				header("Location: edit.php?book_id=".$book['book_id']);
				return;
			}
			$msg = validateAuthor();
			if (is_string($msg)){
				$_SESSION['error'] = $msg;
				header("Location: edit.php?book_id=".$book['book_id']);
				return;
			}
			$msg = validateGenre();
			if (is_string($msg)){
				$_SESSION['error'] = $msg;
				header("Location: edit.php?book_id=".$book['book_id']);
				return;
			}
			//Validate image
			if (isset($_POST['book-cover'])){
				$msg = validateImage('book-cover');
				if (is_string($msg)){
					$_SESSION['error'] = $msg;
					header("Location: edit.php?book_id=".$book['book_id']);
					return;
				}
			}
			//Upload image
			$msg = insertImage($pdo, 'book-cover', $book_id);
			if (is_string($msg)){
				$_SESSION['error'] = $msg;
				header("Location: edit.php?book_id=".$book['book_id']);
				return;
			}
			//Update Publisher
			$publisher_id = insertPublisher($pdo);
			//Update Book
			$stmt = $pdo->prepare('UPDATE Book SET title= :ti, publisher_id=:pid, year_published=:yr, total_copies=:tc,
									available_copies=:ac, format=:fo WHERE book_id=:bid');
			$stmt->execute(array(
				':ti' => $_POST['title'], 
				':pid' => $publisher_id, 
				':yr' => $_POST['yr_published'], 
				':tc' => $_POST['total_copies'], 
				':ac' => $_POST['available_copies'], 
				':fo' => $_POST['format'], 
				':bid' => $book_id
			));
			//Delete from BookAuthor and BookGenre
			$stmt = $pdo->prepare('DELETE FROM BookAuthor WHERE book_id=:bid');
			$stmt->execute(array(
				':bid' => $book_id
			));
			$stmt = $pdo->prepare('DELETE FROM BookGenre WHERE book_id=:bid');
			$stmt->execute(array(
				':bid' => $book_id
			));
			//Insert BookGenre and BookAuthor
			insertGenre($pdo, $book_id);
			insertAuthor($pdo, $book_id);
			//Insert description, series, edition
			insertOptionalFields($pdo, $book_id);
			
			$_SESSION['success'] = "Book updated"; 
			header("Location: ".$_SESSION['from']);
			unset($_SESSION['from']);
			return;
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Edit Book</title>
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
		<h1 class="login-form">Edit Book</h1>
		<?php 
			flashMessages();
		?>
		<form method="POST" enctype="multipart/form-data">
			<div class="login-page">
				<p>
					<strong>Title: </strong>
					<input type="text" name="title" id="book_title-edit" size="50" value="<?php echo htmlentities($book['title']);?>">
				</p>
				<p>
					<strong>Author(s): </strong><input type="submit" id="addAuthor-edit" value="+" class="plus">
					<div id="author_fields_edit">
					<?php
						$index = 0;
						$i = 1;
						foreach ($author_list_name as $author){
							$name = explode(",", $author);
							$fname = str_replace(' ', '', htmlentities($name[1]));
							$lname = str_replace(' ', '', htmlentities($name[0]));
							$is_translator = htmlentities($author_translator_list[$index]);

							echo("<div id='author".$i."_edit'><p>First Name: <input type='text' name='author_fname".$i."' value='".$fname."'/> ");
							echo("Last Name: <input type='text' name='author_lname".$i."' value='".$lname."'/></p>");
							if ($index > 0){
								echo('<input type="button" class="minus" value="-" onclick="$(\'#author'.$i.'_edit\').remove(); countAuthor--; return false;"/>');
							}
							echo("<input type='checkbox' name='is_translator".$i."' value='Translator' id='tra".$i."-edit'");
							if ($is_translator === 1){
								echo(" checked");
							}
							echo("/><label for='tra".$i."-edit'>Translator</label></p></div>");
							$index++;
							$i++;
						}
						$countAu = $i;
					?>
					</div>
				</p>
				<p>
					<strong>Series (optional): </strong>
					<input type="text" name="series" id="book_series-edit" size="44" value="<?php if (isset($book['series'])){echo htmlentities($book['series']);}?>">
				</p>
				<p>
					<strong>Publisher: </strong>
					<input type="text" name="publisher" id="book_publisher-edit" class="pub" size="44" value="<?php echo htmlentities($book['Publisher']);?>">
				</p>
				<p>
					<strong>Year Published: </strong>
					<input type="text" name="yr_published" id="book_pub_yr-edit" size="16" value="<?php echo htmlentities($book['year_published']);?>">
				</p>
				<p>
					<strong>Edition (optional): </strong>
					<input type="text" name="edition" id="book_edition-edit" size="44" value="<?php if (isset($book['edition'])){echo htmlentities($book['edition']);}?>">
				</p>
				<p>
					<strong>Genre(s): </strong><input type="submit" id="addGenre-edit" value="+" class="plus">
					<div id="genre_fields_edit">
					<?php
						$i = 1;
						$index = 0;
						$genre_list = explode(',', $book['Genres']);
						foreach($genre_list as $genre){
							$name = htmlentities($genre);
							echo("<div id='genre".$i."_edit'><p>Name: ");
							echo("<input type='text' name='genre_name".$i."' class='genre' value='".$name."'/> ");
							if ($index > 0){
								echo('<input type="button" class="minus" value="-" onclick="$(\'#genre'.$i.'_edit\').remove(); countGenre--; return false;"/>');
							}
							echo("</p></div>");
							$index++;
							$i++;
						}
						$countGe = $i;
					?>
					</div>
				</p>
				<p>
					<strong>Total Copies: </strong>
					<input type="number" name="total_copies" value="<?php echo htmlentities($book['total_copies']);?>">
				</p>
				<p>
					<strong>Available Copies: </strong>
					<input type="number" name="available_copies" value="<?php echo htmlentities($book['available_copies']);?>">
				</p>
				<label for="format-type-edit">Type:</label>
				<select name="format" id="format-type-edit">
					<option value="book"<?php if (htmlentities($book['format']) == "book"){ echo("selected");}?>>Book</option>
					<option value="ebook"<?php if (htmlentities($book['format']) == "ebook"){ echo("selected");}?>>eBook</option>
					<option value="audiobook"<?php if (htmlentities($book['format']) == "audiobook"){ echo("selected");}?>>Audiobook</option>
				</select>
				<p>
					<strong>Description (optional): </strong><br><br>
					<textarea name="description" rows="8" cols="80"><?php if (isset($book['description'])){echo htmlentities($book['description']);}?></textarea>
				</p>
				<input type="hidden" name="MAX_FILE_SIZE" value="4000000" />
				<p>
					<strong>Book Cover (optional): </strong>
					<input type="file" name="book-cover">
					<?php if (isset($book['image_file'])){echo("<p>Current file: ".htmlentities($book['image_file'])."</p>");}?>
				</p>
				<p>
					<input type="submit" class="button" name="save" value="Save">
					<input type="submit" class="button" name="cancel" value="Cancel">
				</p>
			</div>
		</form>
		<script>
			countAuthor = <?= $countAu ?>;
			countGenre = <?= $countGe ?>;
			$(document).ready(function(){
				window.console && console.log("Document ready called");
				$('#addAuthor-edit').click(function(){
					event.preventDefault();
					if (countAuthor >= 5){
						alert("Maximum of five authors entered");
						return;
					}
					countAuthor++;
					window.console && console.log("Adding author " + countAuthor);
					var source = $('#author-template-edit').html();
					$('#author_fields_edit').append(source.replace(/@COUNT@/g, countAuthor));
				});
				$('#addGenre-edit').click(function(){
					event.preventDefault();
					if (countGenre >= 7){
						alert("Maximum of seven genres entered");
						return;
					}
					countGenre++;
					window.console && console.log("Adding genre " + countGenre);
					var source = $('#genre-template-edit').html();
					$('#genre_fields_edit').append(source.replace(/@COUNT@/g, countGenre));

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
		<script id="author-template-edit" type="text">
			<div id="edit_author@COUNT@">
				<p>
					First Name:
					<input type="text" name="author_fname@COUNT@" value=""/>
					Last Name: 
					<input type="text" name="author_lname@COUNT@" value=""/>
					<input type="button" class="minus" value="-" onclick="$('#edit_author@COUNT@').remove(); countAuthor--; return false;"/>
				</p>
				<p>
					<input type="checkbox" name="is_translator@COUNT@" value="Translator" id="edit_tra@COUNT@"/>
					<label for="edit_tra@COUNT@">Translator</label>
				</p>
			</div>
		</script>
		<script id="genre-template-edit" type="text">
			<div id="edit_genre@COUNT@">
				<p>
					Name:
					<input type="text" name="genre_name@COUNT@" class="genre" value=""/>
					<input type="button" class="minus" value="-" onclick="$('#edit_genre@COUNT@').remove(); countGenre--; return false;"/>
				</p>
			</div>
		</script>
	</body>
</html>