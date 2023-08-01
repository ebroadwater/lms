<?php 
	session_start();
	require_once "../pdo.php";
	require_once "../util.php";

	$profile = false;
	$staff = false;

	if (!isset($_SESSION['user_id'])){
		die("ACCESS DENIED");
		return;
	}
	if (!isset($_GET['user_id'])){
		$_SESSION['error'] = "Missing user_id";
		header("Location: ../index.php");
		return;
	}
	if ($_SESSION['user_id'] === $_GET['user_id']){
		$profile = true;
	}
	if ($_SESSION['is_staff']){
		$staff = true;
	}
	if (!$profile && !$staff){
		die("ACCESS DENIED");
		return;
	}
	$stmt = $pdo->prepare('SELECT * FROM users WHERE user_id=:uid');
	$stmt->execute(array(
		':uid' => $_GET['user_id']
	));
	$row = $stmt->fetch(PDO::FETCH_ASSOC);
	if ($row === false){
		$_SESSION['error'] = "Could not load profile";
		if ($staff){
			header("Location: ../members.php");
			return;
		}
		if ($profile){
			header("Location: ../index.php");
			return;
		}
	}
	if (isset($_POST['cancel'])){
		if ($staff){
			header("Location: ../members.php");
			return;
		}
		header("Location: ../index.php");
		return;
	}
	if (isset($_POST['save']) && isset($_REQUEST['user_id'])){
		$stmt = $pdo->prepare('UPDATE users SET first_name=:fn, last_name=:ln, email=:em, charges=:ch WHERE user_id=:uid');
		$stmt->execute(array(
			':fn' => $_POST['first_name'], 
			':ln' => $_POST['last_name'], 
			':em' => $_POST['email'], 
			':ch' => $_POST['charges'],
			':uid' => $_REQUEST['user_id']
		));
		if (isset($_POST['role'])){
			$is_staff = 0;
			if ($_POST['role'] == "Librarian"){
				$is_staff = 1;
			}
			$stmt = $pdo->prepare('UPDATE users SET is_staff=:st WHERE user_id=:uid');
			$stmt->execute(array(
				':st' => $is_staff,
				':uid' => $_REQUEST['user_id']
			));
		}
		if (isset($_POST['password']) && strlen($_POST['password']) > 0){
			if (!isset($_POST['confirm-password']) || strlen($_POST['confirm-password']) < 1){
				$_SESSION['error'] = "Must confirm password";
				header("Location: members-edit.php");
				return;
			}
		}
		if (isset($_POST['password']) && isset($_POST['confirm-password'])){
			if ($_POST['password'] !== $_POST['confirm-password']){
				$_SESSION['error'] = "Passwords do not match";
				if ($staff){
					header("Location: members-edit.php");
					return;
				}
				header("Location: members-edit.php");
				return;
			}
			$salt = 'XyZzy12*_';
			$hash = hash('md5', $salt.$_POST['password']);
			$stmt = $pdo->prepare('UPDATE users SET password=:pw WHERE user_id=:uid');
			$stmt->execute(array(
				':pw' => $hash, 
				':uid' => $_REQUEST['user_id']
			));
		}
		$_SESSION['success'] = "Profile updated";
		if ($staff){
			header("Location: ../members.php");
			return;
		}
		header("Location: ../index.php");
		return;
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>Edit Profile</title>
		<?php require_once "../head.php";?>
		<link rel='stylesheet' href='../static/css/starter.css'>
	</head>
	<body>
		<ul class="nav">
			<li class="nav-link">
			<?php 
				echo('<a href="../index.php">Home</a>');
				echo('<a href="../books/add.php">Add Book</a>');
				echo('<a href="member-add.php">Add Member</a>');
				echo('<a href="members.php">Members</a>');
				echo('<a href="../logout.php">Log Out</a>');
			?>
			</li>
		</ul>
		<?php 
			if ($profile){
				echo("<h1 class='login-form'>My Profile</h1>");
			}
			else{
				echo("<h1 class='login-form'>Editing Profile for ".htmlentities($_SESSION['first_name'])." ".htmlentities($_SESSION['last_name'])."</h1>");
			}
			flashMessages();
		?>
		<form method="POST">
			<div class="login-page">
				<strong>First Name: </strong>
				<input type="text" class="loginput" name="first_name" id="edit_fname" size="40" value="<?=htmlentities($row['first_name'])?>"><br>
				<strong>Last Name: </strong>
				<input type="text" class="loginput" name="last_name" id="edit_lname" size="40" value="<?=htmlentities($row['last_name'])?>"><br>
				<strong>Email: </strong>
				<input type="text" class="loginput" name="email" id="edit_email" size="40" value="<?=htmlentities($row['email'])?>"><br>
				<?php 
					if ($profile){
						echo("<strong>Change Password: </strong>");
						echo('<input type="password" class="loginput" name="password" id="edit_password" size="40" value=""><br>');
						echo("<strong>Confirm Password: </strong>");
						echo('<input type="password" class="loginput" name="confirm-password" id="edit_confirm_password" size="40" value=""><br>');
					}
					if ($staff){
						echo("<strong>Role: </strong>");
						echo("<input type='radio' id='member' name='role' value='Member'");
						if (!htmlentities($row['is_staff'])){
							echo("checked>");
						}
						echo("<label for='member'>Member</label>");
						echo("<input type='radio' id='librarian' name='role' value='Librarian'");
						if (htmlentities($row['is_staff'])){
							echo("checked>");
						}
						echo("<label for='librarian'>Librarian</label>");

						echo("<br><br><strong>Charges: </strong>");
						echo('<input type="number" name="charges" id="edit_charges" step="0.01" value="'.number_format(htmlentities($row['charges']), 2).'">');
					}
				?>
				<p>
					<input type="hidden" name="user_id" value="<?=htmlentities($_GET['user_id'])?>">
					<input type="submit" class="button" name="save" value="Save" onclick="return doValidate();">
					<input type="submit" class="button" name="cancel" value="Cancel">
				</p>
			</div>
		</form>
		<script>
			function doValidate(){
				try{
					fname = document.getElementById('edit_fname').value;
					lname = document.getElementById('edit_lname').value;
					em = document.getElementById('edit_email').value;
					ch = document.getElementById('edit_charges').value;

					if (fname == null || fname == "" || lname == null || lname == "" || em == null || em == ""
						|| ch == null || ch == ""){
						alert("All fields are required");
						return false;
					}
					if (!em.includes("@")){
						alert("Invalid email address");
						return false;
					}
					return true;
				}catch(e){
					return false;
				}
				return false;
			}
		</script>
	</body>
</html>