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
	</head>
	<body>
		<ul class="nav">
			<li class="nav-link">
			<?php 
				echo('<a href="index.php">Home</a>');
				echo('<a href="books/add.php">Add Book</a>');
				echo('<a href="members/member-add.php">Add Member</a>');
				echo('<a href="members/profile.php?user_id='.$_SESSION['user_id'].'"> My Profile</a>');
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
				echo("<th>Actions</th></tr>");
				foreach($members as $mem){
					echo("<tr><td>".htmlentities($mem['first_name'])." ".htmlentities($mem['last_name']));
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
					echo("<div class='actions' style='justify-content:center;'>");
					echo('<a href="members/profile.php?user_id='.$mem['user_id'].'" class="button" style="width:60px; margin:0; font-size:0.9em;">View</a>');
					echo(' | ');
					echo('<a href="members/members-edit.php?user_id='.$mem['user_id'].'" class="button" id="edit-hover" style="width:60px; margin:0;font-size:0.9em;">Edit</a>');
					echo(' | ');
					echo('<form><input type="button" name="delete" class="button" id="book-del-btn" style="width:60px; margin:0;font-size:0.9em; text-align:center;" value="Delete" onclick="deleteAlert('.$mem['user_id'].')"></form>');
					echo("</div>");
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
		<script>
			function deleteAlert(user_id){
				if (confirm("Are you sure you want to delete this member?")){
					const xmlhttp = new XMLHttpRequest();
					xmlhttp.open("POST", "members/member-delete.php?user_id=" + user_id);
					xmlhttp.send();
					window.location.reload();
				}
			}
		</script>
	</body>
</html>
