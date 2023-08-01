<?php
function flashMessages(){
	if (isset($_SESSION['error'])){
		echo('<p style="color: red; margin-left: 100px;">'.htmlentities($_SESSION['error'])."</p>\n");
		unset($_SESSION['error']);
	}
	if (isset($_SESSION['success'])){
		echo('<p style="color: green; margin-left: 100px;">'.htmlentities($_SESSION['success'])."</p>\n");
		unset($_SESSION['success']);
	}
}
function flashMessagesCenter(){
	if (isset($_SESSION['error'])){
		echo('<p style="color: red; text-align: center;">'.htmlentities($_SESSION['error'])."</p>\n");
		unset($_SESSION['error']);
	}
	if (isset($_SESSION['success'])){
		echo('<p style="color: green; text-align: center;">'.htmlentities($_SESSION['success'])."</p>\n");
		unset($_SESSION['success']);
	}
}
function validateBook(){
	if (strlen($_POST['title']) < 1 || strlen($_POST['publisher']) < 1 || strlen($_POST['yr_published']) < 1 ||
		strlen($_POST['total_copies']) < 1 || strlen($_POST['format']) < 1 || strlen($_POST['available_copies']) < 1){
			return "All fields are required";
	}
	if (!is_numeric($_POST['yr_published'])){
		return "Year must be numeric";
	}
	if ($_POST['available_copies'] > $_POST['total_copies']){
		return "Available copies cannot be greater than the total";
	}
}
function validateAuthor(){
	for($i = 1; $i <= 5; $i++){
		if (isset($_POST['author_fname'.$i]) && isset($_POST['author_lname'.$i])){
			$fname = $_POST['author_fname'.$i];
			$lname = $_POST['author_lname'.$i];
			if (strlen($fname) < 1 || strlen($lname) < 1){
				return "All fields are required";
			}
		}
	}
}
function validateGenre(){
	for ($i = 1; $i < 7; $i++){
		if (isset($_POST['genre_name'.$i])){
			if (strlen($_POST['genre_name'.$i]) < 1){
				return "All fields are required";
			}
		}
	}
}
function validateImage($image_file){
	if ($_FILES[$image_file]['error'] != 0 || $_FILES[$image_file]['error'] !== UPLOAD_ERR_OK){
		return "Failed to upload image";
	}
	$fileExtensionsAllowed = ['jpeg','jpg','png']; // These will be the only file extensions allowed 

	$fileSize = $_FILES[$image_file]['size'];
	$fileType = $_FILES[$image_file]['type'];
	$fileName = $_FILES[$image_file]['name'];
	$filearray = explode('.',$fileName);
	$fileExtension = strtolower(end($filearray));

	if (! in_array($fileExtension,$fileExtensionsAllowed)) {
		return "This file extension is not allowed. Please upload a JPEG or PNG file";
	}

	if ($fileSize > 4000000) {
		return "File exceeds maximum size (4MB)";
	}
}
function insertPublisher($pdo){
	//Publisher
	$publisher_id = false;

	$stmt = $pdo->prepare('SELECT publisher_id FROM Publisher WHERE name=:na');
	$stmt->execute(array(
		':na' => $_POST['publisher']
	));
	$publisher = $stmt->fetch(PDO::FETCH_ASSOC);
	if ($publisher !== false){
		$publisher_id = $publisher['publisher_id'];
	}
	//If publisher not in DB, add it
	if ($publisher_id === false){
		$stmt = $pdo->prepare('INSERT INTO Publisher (name) VALUES (:na)');
		$stmt->execute(array(
			':na' => $_POST['publisher']
		));
		$publisher_id = $pdo->lastInsertId();
	}
	return $publisher_id;
}
function insertBook($pdo, $image_file){
	//Publisher
	$publisher_id = insertPublisher($pdo);
	//Get Format
	$format = getFormat($pdo, $_POST['format']);
	//Book
	$stmt = $pdo->prepare('INSERT INTO Book (title, publisher_id, year_published, total_copies, available_copies, format_id) 
							VALUES (:ti, :pid, :yr, :co, :av, :fo)');
	$stmt->execute(array(
		'ti' => $_POST['title'], 
		'pid' => $publisher_id, 
		'yr' => $_POST['yr_published'], 
		':co' => $_POST['total_copies'], 
		':av' => $_POST['available_copies'], 
		':fo' => $format['format_id']
	));
	$book_id = $pdo->lastInsertId();
	//Description, series, edition
	insertOptionalFields($pdo, $book_id);
	//Upload image
	$msg = insertImage($pdo, $image_file, $book_id);
	if (is_string($msg)){
		return $msg;
	}
	//Genre
	insertGenre($pdo, $book_id);
	//Author
	insertAuthor($pdo, $book_id);
}
function insertOptionalFields($pdo, $book_id){
	if (isset($_POST['series']) && strlen($_POST['series']) > 0){
		$stmt = $pdo->prepare('UPDATE Book SET series= :se WHERE book_id= :bid');
		$stmt->execute(array(
			':se' => $_POST['series'], 
			':bid' => $book_id
		));
	}
	if (isset($_POST['description']) && strlen($_POST['description']) > 0){
		$stmt = $pdo->prepare('UPDATE Book SET description= :de WHERE book_id= :bid');
		$stmt->execute(array(
			':de' => $_POST['description'], 
			':bid' => $book_id
		));
	} 
	if (isset($_POST['edition']) && strlen($_POST['edition']) > 0){
		$stmt = $pdo->prepare('UPDATE Book SET edition= :ed WHERE book_id= :bid');
		$stmt->execute(array(
			':ed' => $_POST['edition'], 
			':bid' => $book_id
		));
	} 
}
function insertGenre($pdo, $book_id){
	for ($i = 1; $i <= 7; $i++){
		if (isset($_POST['genre_name'.$i])){
			$genre_id = false;
			$stmt = $pdo->prepare('SELECT genre_id FROM Genre WHERE name= :na');
			$stmt->execute(array(
				':na' => $_POST['genre_name'.$i]
			));
			$genre_row = $stmt->fetch(PDO::FETCH_ASSOC);
			$stmt->closeCursor();
			if ($genre_row !== false){
				$genre_id = $genre_row['genre_id'];
			}
			if ($genre_id === false){
				$stmt = $pdo->prepare('INSERT INTO Genre (name) VALUES (:na)');
				$stmt->execute(array(
					':na' => $_POST['genre_name'.$i]
				));
				$genre_id = $pdo->lastInsertId();
			}
			//BookGenre
			$stmt = $pdo->prepare('INSERT INTO BookGenre (book_id, genre_id) VALUES (:bid, :gid)');
			$stmt->execute(array(
				':bid' => $book_id, 
				':gid' => $genre_id
			));
		}
	}
}
function insertAuthor($pdo, $book_id){
	for ($i = 1; $i <= 5; $i++){
		if (isset($_POST['author_fname'.$i]) && isset($_POST['author_lname'.$i])){
			$fname = $_POST['author_fname'.$i];
			$lname = $_POST['author_lname'.$i];
			$is_translator = 0;

			if (isset($_POST['is_translator'.$i]) && strlen($_POST['is_translator'.$i]) > 0){
				$is_translator = 1;
			}
			$author_id = false;

			//Check if Author already exists
			$stmt = $pdo->prepare('SELECT author_id FROM Author WHERE first_name=:fn AND last_name=:ln AND is_translator=:tr');
			$stmt->execute(array(
				':fn' => $fname, 
				':ln' => $lname, 
				':tr' => $is_translator
			));
			$row = $stmt->fetch(PDO::FETCH_ASSOC);
			if ($row !== false){
				$author_id = $row['author_id'];
			}
			if ($author_id === false){
				$stmt = $pdo->prepare('INSERT INTO Author (first_name, last_name, is_translator) VALUES (:fn, :ln, :tr)');
				$stmt->execute(array(
					':fn' => $fname, 
					':ln' => $lname, 
					':tr' => $is_translator
				));
				$author_id = $pdo->lastInsertId();
			}
			//BookAuthor
			$stmt = $pdo->prepare('INSERT INTO BookAuthor (book_id, author_id) VALUES (:bid, :aid)');
			$stmt->execute(array(
				':bid' => $book_id, 
				':aid' => $author_id
			));
		}
	}
}
function insertImage($pdo, $image_file, $book_id){
	if ($image_file != '' && strlen($image_file) > 0 && $_FILES[$image_file]['size'] != 0){

		$uploadDirectory = "/Applications/MAMP/htdocs/lms/static/images/";

		$fileName = $_FILES[$image_file]['name'];
		$fileTmpName  = $_FILES[$image_file]['tmp_name'];

		$uploadPath = $uploadDirectory . basename($fileName); 

		$didUpload = move_uploaded_file($fileTmpName, $uploadPath);

		if ($didUpload) {
			$stmt = $pdo->prepare('UPDATE Book SET image_file= :im WHERE book_id= :bid');
			$stmt->execute(array(
				':im' => $fileName, 
				':bid' => $book_id
			));
		}
		else{
			return "An error occurred. Please contact the administrator.";
		}
	}
}
function makeSearch($pdo, $secondpart, $query, $for){
	$firstpart = "SELECT a.book_id, a.title, a.series, a.year_published, a.total_copies, a.available_copies, a.description, a.image_file, a.edition, a.format_id,
	GROUP_CONCAT(DISTINCT d.name) Publisher, GROUP_CONCAT(DISTINCT c.name) Genres, 
	GROUP_CONCAT(DISTINCT f.last_name, ', ', f.first_name, ': ', f.is_translator SEPARATOR ';') Authors, 
	GROUP_CONCAT(DISTINCT f.author_id) Author_ids, 
	GROUP_CONCAT(DISTINCT g.name) Format
	FROM Book a INNER JOIN BookGenre b ON a.book_id = b.book_id INNER JOIN Genre c ON b.genre_id = c.genre_id 
	INNER JOIN Publisher d ON a.publisher_id= d.publisher_id INNER JOIN BookAuthor e ON e.book_id = a.book_id 
	INNER JOIN Author f ON f.author_id = e.author_id INNER JOIN Format g ON g.format_id=a.format_id ";

	$thirdpart = "GROUP BY a.book_id, a.title, a.series, a.year_published, 
	a.total_copies, a.available_copies, a.description, a.image_file, a.edition";

	if ($secondpart == ""){
		$stmt = $pdo->prepare($firstpart.$thirdpart);
		//All formats
		if ($for == ""){
			$stmt->execute();
		}
		else{
			$stmt = $pdo->prepare($firstpart.$for.$thirdpart);
			$stmt->execute(array(
				':for' => $_GET['format']
			));
		}
		$book_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $book_list;
	}

	$query_list = explode(" ", $query);
	if (count($query_list) <= 1){
		if ($for == ""){
			$stmt = $pdo->prepare($firstpart.$secondpart.$thirdpart);
			$stmt->execute(array(
				':temp' => "%".$query."%"
			));
		}
		else{
			$stmt = $pdo->prepare($firstpart.$secondpart.$for.$thirdpart);
			$stmt->execute(array(
				':temp' => "%".$query."%", 
				':for' => $_GET['format']
			));
		}
		$book_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $book_list;
	}
	else{
		$book_list = array();
		foreach($query_list as $q){
			if ($for == ""){
				$stmt = $pdo->prepare($firstpart.$secondpart.$thirdpart);
				$stmt->execute(array(
					':temp' => "%".$query."%"
				));
			}
			else{
				$stmt = $pdo->prepare($firstpart.$secondpart.$for.$thirdpart);
				$stmt->execute(array(
					':temp' => "%".$query."%", 
					':for' => $_GET['format']
				));
			}
			while($row = $stmt->fetch(PDO::FETCH_ASSOC)){
				$check = true;
				foreach($book_list as $b){
					if ($b['book_id'] == $row['book_id']){
						$check = false;
					}
				}
				if ($check){
					array_push($book_list, $row);
				}
			}
		}
		return $book_list;
	}
}
function getBook($pdo, $book_id){
	$stmt = $pdo->prepare("SELECT a.book_id, a.title, a.series, a.year_published, a.total_copies, a.available_copies, a.description, a.image_file, a.edition, a.format_id,
	GROUP_CONCAT(DISTINCT d.name) Publisher, GROUP_CONCAT(DISTINCT c.name) Genres, 
	GROUP_CONCAT(DISTINCT f.last_name, ', ', f.first_name, ': ', f.is_translator SEPARATOR ';') Authors, 
	GROUP_CONCAT(DISTINCT f.author_id) Author_ids, 
	GROUP_CONCAT(DISTINCT g.name) Format
	FROM Book a INNER JOIN BookGenre b ON a.book_id = b.book_id INNER JOIN Genre c ON b.genre_id = c.genre_id 
	INNER JOIN Publisher d ON a.publisher_id= d.publisher_id INNER JOIN BookAuthor e ON e.book_id = a.book_id 
	INNER JOIN Author f ON f.author_id = e.author_id INNER JOIN Format g ON g.format_id = a.format_id WHERE a.book_id= :bid 
	GROUP BY a.book_id, a.title, a.series, a.year_published, a.total_copies, a.available_copies, a.description, a.image_file, a.edition");

	$stmt->execute(array(
		':bid' => $book_id
	));
	$book = $stmt->fetch(PDO::FETCH_ASSOC);
	return $book;
}
function listAuthors($book, $is_dir){
	$str = "";
	$author_list_start = explode(";", $book['Authors']);
	$author_id_list = explode(",", $book['Author_ids']);
	$author_translator_list = array();
	$author_list = array();
	foreach($author_list_start as $au){
		$one = explode(":", $au);
		array_push($author_translator_list, $one[1]);
		array_push($author_list, $one[0]);
	}
	$index = 0;
	foreach($author_list as $author){
		if ($is_dir){
			$str = $str."<p><a href='catalog.php?type=author&q=";
		}
		else{
			$str = $str."<p><a href='../catalog.php?type=author&q=";
		}
		$name = explode(",", $author);
		$fname = str_replace(' ', '', htmlentities($name[1]));
		$lname = str_replace(' ', '', htmlentities($name[0]));
		$q = $fname."+".$lname;
		$str = $str.$q."&search=Search'>";
		$str = $str."<i>".htmlentities($author);
		if ($author_translator_list[$index] == 1){
			$str = $str."(Translator)";
		}
		if ($index + 1 < count($author_list)){
			$str = $str.", \n";
		}
		$str = $str."</i></a></p>";
		$index++;
	}
	return $str;
}
function listFormats($pdo){
	$stmt = $pdo->prepare('SELECT * FROM Format ORDER BY format_id');
	$stmt->execute();
	$formats = $stmt->fetchAll(PDO::FETCH_ASSOC);
	return $formats;
}
function getFormat($pdo, $name){
	$stmt = $pdo->prepare('SELECT * FROM Format WHERE `name`=:fo');
	$stmt->execute(array(
		':fo' => $name
	));
	$format = $stmt->fetch(PDO::FETCH_ASSOC);
	$stmt->closeCursor();
	return $format;
}
function listBookandAuthor($pdo, $book_id){
	$book = getBook($pdo, $book_id);

	$author_list_start = explode(";", $book['Authors']);
	$author_id_list = explode(",", $book['Author_ids']);
	$author_translator_list = array();
	$author_list = array();
	foreach($author_list_start as $au){
		$one = explode(":", $au);
		array_push($author_translator_list, $one[1]);
		array_push($author_list, $one[0]);
	}
	$str = "";
	$str = $str."<i>";
	$str = $str.htmlentities($book['title']);
	$str = $str." </i>- ";

	$index = 0;
	foreach($author_list as $author){
		$name = explode(",", $author);
		$fname = htmlentities($name[1]);
		$lname = htmlentities($name[0]);
		$str = $str.$fname." ".$lname;
		if ($author_translator_list[$index] == 1){
			$str = $str." (Translator)";
		}
		if ($index + 1 < count($author_list)){
			$str = $str." and ";
		}
		$index++;
	}
	return $str;
}
function updateAvailableCopies($pdo, $copies, $book_id){
	$stmt = $pdo->prepare('UPDATE Book SET available_copies=:ac WHERE book_id=:bid');
	$stmt->execute(array(
		':ac' => $copies, 
		':bid' => $book_id
	));
}
function listInfo($book){
	$author_list_start = explode(";", $book['Authors']);
	$author_id_list = explode(",", $book['Author_ids']);
	$author_translator_list = array();
	$author_list = array();
	foreach($author_list_start as $au){
		$one = explode(":", $au);
		array_push($author_translator_list, $one[1]);
		array_push($author_list, $one[0]);
	}
	echo("<p><a href='../books/view.php?book_id=".htmlentities($book['book_id'])."'>".htmlentities($book['title'])."</a> - ");
	$index = 0;
	foreach($author_list as $author){
		$name = explode(",", $author);
		$fname = htmlentities($name[1]);
		$lname = htmlentities($name[0]);
		echo($fname." ".$lname);
		if ($author_translator_list[$index] == 1){
			echo " (Translator)";
		}
		if ($index + 1 < count($author_list)){
			echo(" and ");
		}
		$index++;
	}
	echo("</p>");
}
//ALTER TABLE some_table AUTO_INCREMENT=1