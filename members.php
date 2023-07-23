<?php 
	session_start();
	require_once "pdo.php";
	require_once "util.php";

	if (!isset($_SESSION['user_id']) || $_SESSION['is_staff'] === false){
		die("ACCESS DENIED");
		header("Location: index.php");
		return;
	}
	$stmt = $pdo->prepare('SELECT * FROM users');
	$stmt->execute();
	$members = $stmt->fetchALl(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html>
	<head>
		<title>LMS Members</title>
		<?php require_once "head.php";?>
		<link rel='stylesheet' href='static/css/starter.css'>
	</head>
	<body>
		<ul class="nav">
			<li class="nav-link">
			<?php 
				echo('<a href="index.php">Home</a>');
				echo('<a href="add.php">Add Book</a>');
				echo('<a href="members.php">Members</a>');
				echo('<a href="logout.php">Log Out</a>');
			?>
			</li>
		</ul>
		<h1>Members</h1>
		<?php 
			flashMessagesCenter();

			if ($members){
				$countMem = 0;
				echo("<div class='member-page'>");
				echo('<table>');
				echo("<tr><th>Name</th>");
				echo("<th>Email</th>");
				echo("<th>Role</th>");
				echo("<th>Action</th></tr>");
				foreach($members as $mem){
					echo("<td>".htmlentities($mem['first_name'])." ".htmlentities($mem['last_name']).'</a> ');
					echo("</td><td>".htmlentities($mem['email']));
					echo("</td><td>");
					
					$role = htmlentities($mem['is_staff']);
					if ($role){
						$role = "Librarian";
					}
					else{
						$role = "Member";
					}
					echo($role."</td><td>");
					echo('<a href="members/edit.php?user_id='.$mem['user_id'].'">Edit</a>  |   ');
					echo('<a href="members/delete.php?user_id='.$mem['user_id'].'">Delete</a>');
					echo("</td></tr>");
				}
				echo("</table>\n");
				echo("</div>");
				echo("<br>");
			}
			else{
				echo("<p>No Members Found</p>");
			}
		?>
	</body>
</html>
