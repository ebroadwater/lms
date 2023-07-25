<?php 
	session_start();
	require_once "../pdo.php";
	require_once "../util.php";

	if (!isset($_SESSION['user_id'])){
		die("ACCESS DENIED");
		return;
	}
	if (!$_SESSION['is_staff']){
		die("ACCESS DENIED");
		return;
	}
	$salt = 'XyZzy12*_';

	$stmt = $pdo->prepare('SELECT email FROM users');
	$stmt->execute();
	$emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

	if (isset($_POST['cancel'])){
		header("Location: ../members.php");
		return;
	}
	if (isset($_POST['add']) && isset($_POST['first_name']) && isset($_POST['last_name']) && isset($_POST['email']) &&
		isset($_POST['password']) && isset($_POST['confirm-password']) && isset($_POST['role'])){
			if (strcmp($_POST['password'], $_POST['confirm-password']) !== 0){
				$_SESSION['error'] = "Passwords do not match";
				header("Location: member-add.php");
				return;
			}
			foreach($emails as $em){
				if ($em['email'] === $_POST['email']){
					$_SESSION['error'] = "Account with this email already exists";
					header("Location: member-add.php");
					return;
				}
			}
			$staff = 0;
			if ($_POST['role'] === "Librarian"){
				$staff = 1;
			}
			$hash = hash('md5', $salt.$_POST['password']);
			$stmt = $pdo->prepare('INSERT INTO users (first_name, last_name, email, is_staff, password)
									VALUES (:fn, :ln, :em, :st, :pwd)');
			$stmt->execute(array(
				':fn' => $_POST['first_name'], 
				':ln' => $_POST['last_name'], 
				':em' => $_POST['email'], 
				':st' => $staff, 
				':pwd' => $hash
			));
			$_SESSION['success'] = "Account created";
			header("Location: ../members.php");
			return;
	}

?>
<!DOCTYPE html>
<html>
	<header>
		<title>LMS Add Member</title>
		<?php require_once "../head.php";?>
		<link rel='stylesheet' href='../static/css/starter.css'>
	</header>
	<body>
		<h1 class="login-form">Add Member</h1>
		<?php 
			flashMessages();
		?>
		<form method="POST">
			<div class="login-page">
				<strong>First Name: </strong><input type="text" class="loginput" name="first_name" id="add_fname"><br>
				<strong>Last Name: </strong><input type="text" class="loginput" name="last_name" id="add_lname"><br>
				<strong>Email: </strong><input type="text" class="loginput" name="email" id="add_email"><br>
				<strong>Password: </strong><input type="password" class="loginput" name="password" id="add_password"><br>
				<strong>Confirm Password: </strong><input type="password" class="loginput" name="confirm-password" id="add_confirm_password"><br>
				<strong>Role: </strong>
				<input type="radio" name="role" value="Member" id="add-member">
				<label for="add-member">Member</label>
				<input type="radio" name="role" value="Librarian" id="add-librarian">
				<label for="add-librarian">Librarian</label>
				<p>
					<input type="submit" class="button" name="add" value="Add" onclick="return doValidate();">
					<input type="submit" class="button" name="cancel" value="Cancel">
				</p>
			</div>
		</form>
		<script>
			function doValidate(){
				try{
					fname = document.getElementById('add_fname').value;
					lname = document.getElementById('add_lname').value;
					em = document.getElementById('add_email').value;
					pw = document.getElementById('add_password').value;
					cpw = document.getElementById('add_confirm_password').value;

					if (fname == null || fname == "" || lname == null || lname == "" || em == null || em == "" 
						|| pw == null || pw == "" || cpw == null || cpw == ""){
							alert("All fields are required");
							return false;
					}
					if (!document.getElementById('add-member').checked && !document.getElementById('add-librarian').checked){
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