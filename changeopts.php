<?php
//ini_set('display_errors', '1');
// This is an AJAX file used to update user options

// ---------------------------- VERSION HISTORY -------------------------------
//File Version 5.01
//	Change to administrator error message that appeared when at user home site

// Set up environment
require "environment.php";

// If the UserIndex has not been set as a session variable, the user needs to log in
if (! ($_SESSION["UserIndex"] > 0)) {
	header('Location: index.php');
}

// Input options
$q = explode("_", $_GET["q"]);
$usite = $q[0];
$uid = $q[1];
$useroptions = $q[2];
$SiteCurrent = $_SESSION["SiteCurrent"];
if ($_SESSION["TRACE"]) error_log("CHGOPT: " . $_SESSION["UserName"] . ", Site=" . $SiteCurrent . ", Usite=" . $usite . ", Uid=" . $uid . ", Uopt=" . $useroptions);

// echo "curr=($SiteCurrent)\nuid=($uid)\nusite=($usite)\nuseroptions=($useroptions)\n\n"; // DEBUG

if ($usite == $SiteCurrent) { // goes in user table
	$query = "UPDATE $USER_TABLE SET";
	$query .= " `user_options` = '$useroptions'";
	$query .= " WHERE `user_index` = $uid";
	$result = mysqli_query($dbcon, $query);
	//echo "UPDATE: ($result)\n"; // DEBUG
}
else {

	// check for admin
	if ($useroptions == "A") {
		echo "Go to user's home site to make them an Administrator";
		return;
	}
}

// delete any entry already there
$query = "DELETE FROM $ACCESS_TABLE";
$query .= " WHERE `acc_user` = $uid";
$query .= " AND `acc_owner` = $SiteCurrent";
$result = mysqli_query($dbcon, $query);
//echo "DELETE: ($result)\n"; // DEBUG

// then add the new one if non-zero
if (($useroptions > 0) or ($useroptions === "M")) {
	$query = "INSERT INTO $ACCESS_TABLE (`acc_location`, `acc_user`, `acc_owner`, `acc_option`)";
	$query .= " VALUES (0, $uid, $SiteCurrent, '$useroptions')";
	$result = mysqli_query($dbcon, $query);
	//echo "ADD: ($result)\n"; // DEBUG
}
