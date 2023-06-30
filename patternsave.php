<?php
// Version 9.0
// 	Modifications due to user SESSION variable consolidation
// Version 5.00
ini_set('display_errors', '1');
// This is an AJAX file used to update user options

// Set up environment
require "environment.php";

// If the UserIndex has not been set as a session variable, the user needs to log in
if (! isset($_SESSION["User"])) {
	header('Location: index.php');
}

// Parse the input
$q = explode("~", $_GET["q"]);
$request = $q[0];
$patternId = $q[1];
$patternLoc = $q[2];
$patternName = $q[3];
$patternData = $q[4];
$patternEnd = $q[5];
if ($_SESSION["TRACE"]) error_log("PATT: " . $_SESSION["User"]["user_name"] . ", Request=" . $request . ", Site=" . $patternLoc . ", Pattern=" . $patternName);
if ($patternEnd !== "$") {
	error_log("PATT: Invalid request=" . $_GET["q"]);
	exit;
}

switch ($request) {
	case "SBPatternSaveAs":
		$query = "INSERT INTO $SCHED_TABLE SET";
		$query .= " `sched_location` = $patternLoc";
		$query .= ", `sched_name` = '$patternName'";
		$query .= ", `sched_pattern` = '$patternData'";
		break;
	case "SBPatternSave":
		$query = "UPDATE $SCHED_TABLE SET";
		$query .= " `sched_location` = $patternLoc";
		$query .= ", `sched_pattern` = '$patternData'";
		$query .= " WHERE `sched_index` = $patternId";
		break;
	case "SBPatternDelete":
		$query = "DELETE FROM $SCHED_TABLE";
		$query .= " WHERE `sched_index` = $patternId";
		break;
	default:
		echo "Invalid request";
		exit;
}
$result = mysqli_query($dbcon, $query);
if ($result != 1) {
	error_log("PATT :" . $_SESSION["User"]["user_name"] . ", " . $request . " failed");
	echo 0;
	return;
}

// Return the index
if ($request == "SBPatternSaveAs") { // get the DB index number
	$query = "SELECT * FROM $SCHED_TABLE";
	$query .= " WHERE `sched_location` = $patternLoc";
	$query .= " AND `sched_name` = '$patternName'";
	$ts = mysqli_query($dbcon, $query);
	$row = mysqli_fetch_array($ts);
	if (sizeof($row)) echo $row["sched_index"];
	else echo 0;
}
else echo $patternId;
