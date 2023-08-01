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
	$_SESSION['from'] = "../members/profile.php?user_id=".htmlentities($_REQUEST['user_id']);
	$date = new DateTime();
	$date->setTimezone(new DateTimeZone('America/New_York'));
	$start = date_format($date, "Y-m-d H:i:s");

	//Get items on hold - not-expired
	$stmt = $pdo->prepare('SELECT * FROM Hold WHERE user_id=:uid AND end_time >= :et');
	$stmt->execute(array(
		':uid' => $_REQUEST['user_id'], 
		':et' => $start
	));
	$hold_list = $stmt->fetchAll(PDO::FETCH_ASSOC);

	//Get items checked out 
	$stmt = $pdo->prepare('SELECT * FROM Checkout WHERE user_id=:uid AND is_returned=0');
	$stmt->execute(array(
		':uid' => $_REQUEST['user_id']
	));
	$checkout_list = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
		<?php 
			flashMessagesCenter();
		?>
		<form method="GET" action="members-edit.php">
			<input type="submit" value="Edit Profile" name="edit_profile" class="button" style="width:100px;">
			<input type="hidden" value="<?php echo $_REQUEST['user_id']?>" name="user_id">
		</form>
		<h3>Items Checked Out</h3>
		<div style="border:solid 1px #3B3923; display:flex; flex-direction:column; padding:10px;">
			<?php
				if ($checkout_list === false || count($checkout_list) < 1){
					echo("<p>None</p>");
				} 
				else{
					foreach($checkout_list as $check){
						echo("<div style='border-bottom:solid 1px #3B3923;display:flex; padding:10px;'>");
						echo("<div style='display:flex; flex-direction:column;'>");
						$book = getBook($pdo, htmlentities($check['book_id']));
						if ($book === false){
							$_SESSION['error'] = "Could not load book";
							header("Location: ".$_SERVER['REQUEST_URI']);
							return;
						}
						listInfo($book);
						$exp = strtotime(htmlentities($check['end_time']));
						$new_format = date("l, m-d-Y g:i A", $exp);
						echo("<p><strong>Due Date: </strong>".$new_format);

						if ($check['end_time'] < $start && $check['is_returned'] == 0){
							echo("<span style='color:red; margin-left:18px;'>Overdue</span");
						}
						echo("</p>");
						echo("<div style='display:inline-flex; gap:10px;'><form method='GET' action='../books/return.php'>");
						echo("<input type='submit' value='Return' name='return' class='button' style='width:80px;'>");
						echo("<input type='hidden' value='".$_REQUEST['user_id']."' name='user_id'>");
						echo("<input type='hidden' value='".htmlentities($book['book_id'])."' name='book_id'>");
						echo("</form>");
		
						echo("<form method='GET' action='../books/renew.php'>");
						echo("<input type='submit' value='Renew' name='renew' class='button' style='width:80px;'>");
						echo("<input type='hidden' value='".$_REQUEST['user_id']."' name='user_id'>");
						echo("<input type='hidden' value='".htmlentities($book['book_id'])."' name='book_id'>");
						echo("</form></div>");
						echo("</div>");
						echo("</div>");
						// echo("</div>");
					}
				}
			?>
		</div>
		<h3>Items on Hold</h3>
		<div style="border:solid 1px black; display:flex; flex-direction:column; padding:10px;">
			<?php
				if ($hold_list === false || count($hold_list) < 1){
					echo("<p>None</p>");
				} 
				else{
					foreach($hold_list as $hold){
						echo("<div style='border-bottom:solid 1px #3B3923; display:flex; padding:10px;'>");
						echo("<div style='display:flex; flex-direction:column;'>");
						$book = getBook($pdo, htmlentities($hold['book_id']));
						if ($book === false){
							$_SESSION['error'] = "Could not load book";
							header("Location: ".$_SERVER['REQUEST_URI']);
							return;
						}
						listInfo($book);
						$exp = strtotime(htmlentities($hold['end_time']));
						$new_format = date("l, m-d-Y g:i A", $exp);
						echo("<p><strong>Expires: </strong>".$new_format."</p>");
						echo("<form method='GET' action='../books/checkout.php'>");
						echo("<input type='submit' value='Check Out' name='checkout' class='button' style='width:80px;'>");
						echo("<input type='hidden' value='".$_REQUEST['user_id']."' name='user_id'>");
						echo("<input type='hidden' value='".htmlentities($book['book_id'])."' name='book_id'>");
						echo("</form>");
						echo("</div>");
						echo("</div>");
					}
				}
			?>
		</div>
		<h3>Bill</h3>
		<div style="border:solid 1px black; display:flex; flex-direction:column; padding:10px;">
			<?php 
				$stmt = $pdo->prepare('SELECT charges FROM users WHERE user_id=:uid');
				$stmt->execute(array(
					':uid' => $_REQUEST['user_id']
				));
				$bill = $stmt->fetch(PDO::FETCH_ASSOC);
				echo ("$".number_format(htmlentities($bill['charges']), 2));
			?>
		</div>
		<script>
			user_id = <?= $_REQUEST['user_id'] ?>;
			window.onload = updateUserCharges();
			function updateUserCharges(){
				const xmlhttp = new XMLHttpRequest();
				xmlhttp.open("POST", "member-charges.php?user_id=" + user_id);
				xmlhttp.send();
			}
		</script>
	</body>
</html>