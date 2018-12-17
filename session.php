<?php
// Version 4.02

// Set up environment
require "environment.php";

if (! isset($_SESSION["SystemVersion"])) require "setup.php";

// The following variables are supported in the $_SESSION[] array:
// CalStartMo
// CalStartYr
// NewVersion
// SystemGreeting
// SystemInfo (for future use)
// SystemNotice
// SystemURL
// SystemVersion
// TRACE
// SummaryAll
// UserIndex
// UserEmail
// UserFirst
// UserFullName
// UserHome
// UserLast
// UserLoc
// UserName
// UserOptions
// UserPass
// UserPhone
// UserData
// UserSiteList
// MaxWaitSequence

// Create a time stamp
$TimeStamp = Date("Y-m-d H:i:s");
$TodayDate = Date("Y-m-d");
$NullDate = "0000-00-00";
$NullTime = "00:00:00";

// Define some globals
$USER_TABLE = "taxappt_users";
$ACCESS_TABLE = "taxappt_access";
$APPT_TABLE = "taxappt_appts";
$SITE_TABLE = "taxappt_sites";
$SYSTEM_TABLE = "taxappt_system";

$VIEW_CB = 1;
$ADD_CB = 2;
$VIEW_APP = 4;
$ADD_APP = 8;
$ACCESS_ALL = $VIEW_CB + $ADD_CB + $VIEW_APP + $ADD_APP;
$MANAGER = 512;
$ADMINISTRATOR = 1024;

// Get system greeting, notice and info
$query = "SELECT * FROM $SYSTEM_TABLE";
$sys = mysqli_query($dbcon, $query);
while ($row = mysqli_fetch_array($sys)) {
	$_SESSION["SystemGreeting"] = $row['system_greeting'];
	$_SESSION["SystemNotice"] = $row['system_notice'];
	$_SESSION["SystemInfo"] = $row['system_info'];
	$_SESSION["SystemURL"] = $row['system_url'];
}

// check for new version
$_SESSION["NewVersion"] = $_SESSION["SystemVersion"];
/*
if (isset($_SESSION["UserOptions"]) and ($_SESSION["UserOptions"] == "A")) {
	copy("http://cotaxaide.org/appt/change_history.txt","change_history.txt");
	$fileptr = fopen("change_history.txt","r");
	if ($fileptr) {
		$newversion = "";
		$change_history = "<div id='change_history' style='display: none;'>\n";
		while (! feof($fileptr)) {
			$line = trim(fgets($fileptr));
			if (substr($line, 0, 7) == "VERSION") {
				$vers = trim(substr($line,8));
				if ($newversion) {
					$change_history .= "\t</ul>\n";
				}
				else {
					$_SESSION["NewVersion"] = $newversion = $vers;
				}
				$current = (+$_SESSION["SystemVersion"] == +$vers) ? " (current version)":"";
				$change_history .= "\t<b>VERSION $vers</b>\n\t$current<ul>\n";
			}
			else {
				if ($line) $change_history .= "\t\t<li>$line</li>\n";
			}
		}
		if ($newversion) $change_history .= "</ul></div>\n";
		else $_SESSION["NewVersion"] = $_SESSION["SystemVersion"];
		fclose($fileptr);
	}
}
 */
?>
