<?php 
	session_start();
	require_once "pdo.php";
	require_once "util.php";

	$salt = 'XyZzy12*_';

	$stmt = $pdo->prepare('SELECT email FROM users');
	$stmt->execute();
	$emails = $stmt->fetchAll(PDO::FETCH_ASSOC);

	if (isset($_POST['cancel'])){
		header("Location: index.php");
		return;
	}
	if (isset($_POST['signup']) && isset($_POST['first_name']) && isset($_POST['last_name']) && isset($_POST['email']) &&
		isset($_POST['password']) && isset($_POST['confirm-password'])){
			if (strcmp($_POST['password'], $_POST['confirm-password']) !== 0){
				$_SESSION['error'] = "Passwords do not match";
				header("Location: signup.php");
				return;
			}
			foreach($emails as $em){
				if ($em['email'] === $_POST['email']){
					$_SESSION['error'] = "Account with this email already exists";
					header("Location: signup.php");
					return;
				}
			}
			$hash = hash('md5', $salt.$_POST['password']);
			$stmt = $pdo->prepare('INSERT INTO users (first_name, last_name, email, is_staff, password)
									VALUES (:fn, :ln, :em, :st, :pwd)');
			$stmt->execute(array(
				':fn' => $_POST['first_name'], 
				':ln' => $_POST['last_name'], 
				':em' => $_POST['email'], 
				':st' => 0, 
				':pwd' => $hash
			));
			$_SESSION['success'] = "Account created";
			header("Location: index.php");
			return;
	}

?>
<!DOCTYPE html>
<html>
	<header>
		<title>LMS Sign Up</title>
		<?php require_once "head.php";?>
	</header>
	<body>
		<h1 class="login-form">Sign Up</h1>
		<?php 
			flashMessages();
		?>
		<form method="POST">
			<div class="login-page">
				<strong>First Name: </strong><input type="text" class="loginput" name="first_name" id="signup_fname"><br>
				<strong>Last Name: </strong><input type="text" class="loginput" name="last_name" id="signup_lname"><br>
				<strong>Email: </strong><input type="text" class="loginput" name="email" id="signup_email"><br>
				<strong>Password: </strong><input type="password" class="loginput" name="password" id="signup_password"><br>
				<strong>Confirm Password: </strong><input type="password" class="loginput" name="confirm-password" id="signup_confirm_password">
				<p>
					<input type="submit" class="button" name="signup" value="Sign Up" onclick="return doValidate();">
					<input type="submit" class="button" name="cancel" value="Cancel">
				</p>
			</div>
		</form>
		<script>
			function doValidate(){
				try{
					fname = document.getElementById('signup_fname').value;
					lname = document.getElementById('signup_lname').value;
					em = document.getElementById('signup_email').value;
					pw = document.getElementById('signup_password').value;
					cpw = document.getElementById('signup_confirm_password').value;

					if (fname == null || fname == "" || lname == null || lname == "" || em == null || em == "" 
						|| pw == null || pw == "" || cpw == null || cpw == ""){
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