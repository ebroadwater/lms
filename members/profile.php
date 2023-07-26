<?php 
	session_start();
	require_once "../pdo.php";
	require_once "../util.php";

	$staff = 0;
	$loggedin = isset($_SESSION['user_id']);
	if ($loggedin){
		$staff = $_SESSION['is_staff'];
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>My Account</title>
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
		<h1>My Account</h1>
		<form method="GET" action="members-edit.php">
			<input type="submit" value="Edit Profile" name="edit_profile">
			<input type="hidden" value="<?php echo $_SESSION['user_id']?>" name="user_id">
		</form>
		<h3>Items Checked Out</h3>
		<h3>Items on Hold</h3>
		<h3>Items Checked Out</h3>
	</body>
</html>