<?php 
	session_start();
	require_once "pdo.php";
	require_once "util.php";

	$salt = 'XyZzy12*_';

	if (isset($_POST['cancel'])){
		header("Location: index.php");
		return;
	}
	if (isset($_POST['login']) && isset($_POST['email']) && isset($_POST['password'])){
		unset($_SESSION['email']);
		unset($_SESSION['user_id']);
		$check = hash('md5', $salt.$_POST['password']);
		$stmt = $pdo->prepare('SELECT * FROM users WHERE email=:em AND password=:pw');
		$stmt->execute(array(
			':em' => $_POST['email'], 
			':pw' => $check
		));
		$row = $stmt->fetch(PDO::FETCH_ASSOC);
		if ($row !== false){
			$_SESSION['user_id'] = $row['user_id'];
			$_SESSION['email'] = $row['email'];
			$_SESSION['first_name'] = $row['first_name'];
			$_SESSION['last_name'] = $row['last_name'];
			$_SESSION['is_staff'] = $row['is_staff'];

			if (isset($_SESSION['from'])){
				header("Location: ". $_SESSION['from']);
				unset($_SESSION['from']);
				return;
			}
			header("Location: index.php");
			return;
		}
		else{
			$_SESSION['error'] = "Incorrect password";
			header("Location: login.php");
			return;
		}
	}
?>
<!DOCTYPE html>
<html>
	<head>
		<title>LMS Login Page</title>
		<?php require_once "head.php";?>
		<link rel='stylesheet' href='static/css/starter.css'>
	</head>
	<body>
		<ul class="nav">
			<li class="nav-link">
			<?php 
				echo('<a href="index.php">Home</a>');
				echo('<a href="login.php">Log In</a>');
				echo('<a href="signup.php">Sign Up</a>');
			?>
			</li>
		</ul>
		<h1 class="login-form">Log In</h1>
		<?php 
			flashMessages();
		?>
		<form method="POST">
			<div class="login-page">
				<strong>Email: </strong><input type="text" class="loginput" name="email" id="login_email" style="margin-bottom:20px"><br>
				<strong>Password: </strong><input type="password" class="loginput" name="password" id="login_password" style="margin-bottom:20px">
				<p>
					<input type="submit" class="button" name="login" value="Log In" onclick="return doValidate();">
					<input type="submit" class="button" name="cancel" value="Cancel">
				</p>
			</div>
		</form>
		<script>
			function doValidate(){
				console.log("Validating...");
				try{
					pw = document.getElementById("login_password").value;
					em = document.getElementById("login_email").value;

					if (pw == null || pw == "" || em == null || em == ""){
						alert("Both fields must be filled out");
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