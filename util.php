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