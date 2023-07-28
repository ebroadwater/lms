<?php 
	session_start();
	require_once "../pdo.php";
	require_once "../util.php";

	if (!isset($_SESSION['user_id'])){
		$_SESSION['error'] = "Permission denied";
		header("Location: ../index.php");
		return;
	}
	if (!isset($_GET['user_id'])){
		$_SESSION['error'] = "Profile not found";
		header("Location: ../index.php");
		return;
	}
	$staff = 0;
	$loggedin = isset($_SESSION['user_id']);
	if ($loggedin){
		$staff = $_SESSION['is_staff'];
	}
	$own_profile = false;
	$user = NULL;
	//Own profile
	if ($_SESSION['user_id'] == $_GET['user_id']){
		$own_profile = true;
	}
	else{
		//Try to view another profile
		if (!$_SESSION['is_staff'] || $_SESSION['is_staff'] == 0){
			$_SESSION['error'] = "Permission denied";
			header("Location: ../index.php");
			return;
		}
		$stmt = $pdo->prepare('SELECT first_name, last_name, email, is_staff FROM users WHERE user_id=:uid');
		$stmt->execute(array(
			':uid' => $_GET['user_id']
		));
		$user = $stmt->fetch(PDO::FETCH_ASSOC);
		if ($user === false){
			$_SESSION['error'] = "Could not find user";
			header("Location: ../members.php");
			return;
		}
	}
	//Get items on hold
	$stmt = $pdo->prepare('SELECT * FROM Hold WHERE user_id=:uid');
	$stmt->execute(array(
		':uid' => $_REQUEST['user_id']
	));
	$hold_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
	<head>
		<title>Profile</title>
		<?php require_once "../head.php";?>
		<link rel='stylesheet' href='../static/css/starter.css'>
	</head>
	<body>
		<ul class="nav">
			<li class="nav-link">
			<?php 
				echo('<a href="../index.php">Home</a>');
				if ($loggedin){
					if ($staff){
						echo('<a href="../books/add.php">Add Book</a>');
						echo('<a href="member-add.php">Add Member</a>');
						echo('<a href="../members.php">Members</a>');
					}
					echo('<a href="logout.php">Log Out</a>');
				}
				else{
					echo('<a href="login.php">Log In</a>');
					echo('<a href="signup.php">Sign Up</a>');
				}
			?>
			</li>
		</ul>
		<?php
			if ($own_profile){
				echo("<h1>My Account</h1>");
			}
			else{
				echo("<h1>".htmlentities($user['first_name'])." ".htmlentities($user['last_name'])."</h1>");
			}
		?>
		<form method="GET" action="members-edit.php">
			<input type="submit" value="Edit Profile" name="edit_profile">
			<input type="hidden" value="<?php echo $_REQUEST['user_id']?>" name="user_id">
		</form>
		<h3>Items Checked Out</h3>
		<div>

		</div>
		<h3>Items on Hold</h3>
		<div style="border:solid 1px black; display:flex; flex-direction:column;">
			<?php
				if ($hold_list === false || count($hold_list) < 1){
					echo("<p>None</p>");
				} 
				else{
					foreach($hold_list as $hold){
						echo("<div style='border:solid 1px black; display:flex; padding:10px;'>");
						$book = getBook($pdo, htmlentities($hold['book_id']));
						if ($book === false){
							$_SESSION['error'] = "Could not load book";
							header("Location: ".$_SERVER['REQUEST_URI']);
							return;
						}
						$author_list_start = explode(";", $book['Authors']);
						$author_id_list = explode(",", $book['Author_ids']);
						$author_translator_list = array();
						$author_list = array();
						foreach($author_list_start as $au){
							$one = explode(":", $au);
							array_push($author_translator_list, $one[1]);
							array_push($author_list, $one[0]);
						}
						echo("<p>".htmlentities($book['title'])." - ");
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
						$exp = strtotime(htmlentities($hold['end_time']));
						$new_format = date("l, m-d-Y g:i A", $exp);
						echo("<br><br><strong>Expires: </strong>".$new_format."</p>");
						echo("</div>");
						}
					}
			?>
		</div>
	</body>
</html>