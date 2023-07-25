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
		strlen($_POST['total_copies']) < 1){
			return "All fields are required";
	}
	if (!is_numeric($_POST['yr_published'])){
		return "Year must be numeric";
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
function insertBook($pdo){
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
	//Book
	$stmt = $pdo->prepare('INSERT INTO Book (title, publisher_id, year_published, total_copies, available_copies) 
							VALUES (:ti, :pid, :yr, :co, :av)');
	$stmt->execute(array(
		'ti' => $_POST['title'], 
		'pid' => $publisher_id, 
		'yr' => $_POST['yr_published'], 
		':co' => $_POST['total_copies'], 
		':av' => $_POST['total_copies']
	));
	$book_id = $pdo->lastInsertId();

	//If description and series included 
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
	//Genre
	for ($i = 1; $i <= 7; $i++){
		if (isset($_POST['genre_name'.$i])){
			$genre_id = false;
			$stmt = $pdo->prepare('SELECT genre_id FROM Genre WHERE name= :na');
			$stmt->execute(array(
				':na' => $_POST['genre_name'.$i]
			));
			$genre_row = $stmt->fetch(PDO::FETCH_ASSOC);
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
	//Author
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

			//If middle name defined
			if (isset($_POST['author_mname'.$i]) && strlen($_POST['author_mname'.$i]) > 0){
				$stmt = $pdo->prepare('UPDATE Author SET middle_name= :mn WHERE author_id=:aid');
				$stmt->execute(array(
					':mn' => $_POST['author_mname'.$i], 
					':aid' => $author_id
				));
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
function makeSearch($pdo, $secondpart, $query){
	$firstpart = "SELECT a.book_id, a.title, a.series, a.year_published, a.total_copies, a.available_copies, a.description, 
	GROUP_CONCAT(DISTINCT d.name) Publisher, GROUP_CONCAT(DISTINCT c.name) Genres, 
	GROUP_CONCAT(DISTINCT f.last_name, ', ', f.first_name SEPARATOR ';') Authors, 
	GROUP_CONCAT(DISTINCT f.author_id) Author_ids
	FROM Book a INNER JOIN BookGenre b ON a.book_id = b.book_id INNER JOIN Genre c ON b.genre_id = c.genre_id 
	INNER JOIN Publisher d ON a.publisher_id= d.publisher_id INNER JOIN BookAuthor e ON e.book_id = a.book_id 
	INNER JOIN Author f ON f.author_id = e.author_id ";

	$thirdpart = "GROUP BY a.book_id, a.title, a.series, a.year_published, a.total_copies, a.available_copies, a.description";

	$query_list = explode(" ", $query);
	$stmt = $pdo->prepare($firstpart.$secondpart.$thirdpart);
	if (count($query_list) <= 1){
		$stmt->execute(array(
			':temp' => "%".$query."%"
		));
		$book_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
		return $book_list;
	}
	else{
		$book_list = array();
		foreach($query_list as $q){
			$stmt->execute(array(
				':temp' => "%".$q."%"
			));
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
	$stmt = $pdo->prepare("SELECT a.book_id, a.title, a.series, a.year_published, a.total_copies, a.available_copies, a.description, 
	GROUP_CONCAT(DISTINCT d.name) Publisher, GROUP_CONCAT(DISTINCT c.name) Genres, 
	GROUP_CONCAT(DISTINCT f.last_name, ', ', f.first_name SEPARATOR ';') Authors, 
	GROUP_CONCAT(DISTINCT f.author_id) Author_ids
	FROM Book a INNER JOIN BookGenre b ON a.book_id = b.book_id INNER JOIN Genre c ON b.genre_id = c.genre_id 
	INNER JOIN Publisher d ON a.publisher_id= d.publisher_id INNER JOIN BookAuthor e ON e.book_id = a.book_id 
	INNER JOIN Author f ON f.author_id = e.author_id WHERE a.book_id= :bid 
	GROUP BY a.book_id, a.title, a.series, a.year_published, a.total_copies, a.available_copies, a.description");

	$stmt->execute(array(
		':bid' => $book_id
	));
	$book = $stmt->fetch(PDO::FETCH_ASSOC);
	return $book;
}
//ALTER TABLE some_table AUTO_INCREMENT=1