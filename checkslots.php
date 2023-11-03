<?php
//ini_set('display_errors', '1');
// This is an AJAX file used to update user options

// ---------------------------- VERSION HISTORY -------------------------------
//File Version 9.00

// Set up environment
require "environment.php";

// If the UserIndex has not been set as a session variable, the user needs to log in
if (! ($_SESSION["User"]["user_index"] > 0)) {
	header('Location: index.php');
}

// Get current value of system_heartbeat to see if it has been turned off
if ($_SESSION['SystemHeartbeat'] == 0) {
	echo "STOP";
	return;
}

// Still active, so return the current appointment slot data
$RESERVED = "&amp;laquo; R E S E R V E D &amp;raquo;";

// Get the POSTed string of appointment numbers
// Negative numbers are slots that are reserved in the current display
$q = $_POST['q'] ?? '';
$xmlout = "<table>";

if ($q) {
	$qArray = explode(',', $q);
	$queryList = "";
	$resArray = []; // key is "APPT"+Appt number,
	//remember which are RESERVED vs blank records
	for ($i = 0; $i < sizeOf($qArray); $i++) {
		$absApptNo = abs($qArray[$i]);
		$resArray[ "APPT" . $absApptNo ] = ($qArray[$i] < 0); // true if RESERVED
		$queryList .= (($queryList) ? "," : "" ) . "$absApptNo";
	}

	//get the requested records and return data if there is any change
	$query = "SELECT * FROM `$APPT_TABLE`";
	$query .= " WHERE `appt_no` IN ($queryList)";
	//error_log($query); echo ""; return;
	$appointments = mysqli_query($dbcon, $query);
	if ($appointments != NULL) while ($row = mysqli_fetch_array($appointments)) {
		$databaseName = $row['appt_name'];
		$displayIsReserved = $resArray["APPT" . $row['appt_no']];
		//if (($databaseName == RESERVED) and $displayIsReserved) continue; // no change - skip it
		//if (($databaseName == ") and (! $displayIsReserved)) continue; // blank, no change - skip it

		// The slot has changed, report the change
		// Coding could be simpler using just XML but a table helps debugging
		$xmlout .= "<tr class=\"xmlslot\">";
		$xmlout .= ("<td class=\"xmlappt\">" . $row['appt_no'] . "</td>");
		$xmlout .= ("<td class=\"xmlname\">" . $databaseName . "</td>");
		$xmlout .= ("<td class=\"xmlemail\">" . $row['appt_email'] . "</td>");
		$xmlout .= ("<td class=\"xmlphone\">" . $row['appt_phone'] . "</td>");
		$xmlout .= ("<td class=\"xmltags\">" . $row['appt_tags'] . "</td>");
		$xmlout .= ("<td class=\"xmlneed\">" . $row['appt_need'] . "</td>");
		$xmlout .= ("<td class=\"xmlinfo\">" . $row['appt_info'] . "</td>");
		$xmlout .= ("<td class=\"xmlstatus\">" . $row['appt_status'] . " </td>");
		$xmlout .= "</tr>";
	}
$xmlout .= "</table>";
echo $xmlout;
}




