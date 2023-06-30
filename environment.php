<?php
// Version 8.00
// 	Changed NullDate from 0000-00-00 to 1900-01-01
// Version 7.00
// 	Added functions.php
// Version 5.01

// error_log("ENVIRON DEBUG: Starting environment.php");

// Set up subdirectories if not already done
if (! is_dir("appt_session_dir")) mkdir("appt_session_dir");
if (! is_dir("Images")) mkdir("Images");

// Move images into Images directory
$files = scandir(".");
foreach ($files as $file) {
	if (stripos($file, ".png")
	OR stripos($file, ".gif")
	OR stripos($file, ".jpg")) {
		if (copy($file, "Images/" . $file)) unlink($file); 
	}
}

// Start a session if not already done
if (session_id() == "") {
	// error_log("ENVIRON DEBUG: Starting session");
	ini_set("session.save_path", "appt_session_dir");
	ini_set("error_log", "appt_error_log");
	session_start();
	// Set up database if it has not been done
	if (! file_exists("opendb.php")) {
	// error_log("ENVIRON DEBUG: Running setup.php");
		header('Location: setup.php');
	// error_log("ENVIRON DEBUG: returned from Running setup.php");
	}
	else {
		// error_log("ENVIRON DEBUG: requiring setup.php");
		require_once("setup.php");
	}
}

// Start a cron job - doesn't work, must do it manually
if (! file_exists('crontab.txt')) {
	// error_log("ENVIRON DEBUG: starting cron job");
	file_put_contents( 'crontab.txt', '20 2 * * * ' . getcwd() . '/crontab.bat' . PHP_EOL );
	file_put_contents( 'crontab.bat', 'cd ' . getcwd() . PHP_EOL );
	file_put_contents( 'crontab.bat', PHP_BINARY . ' ' . 'reminders.php >> appt_error_log' . PHP_EOL, FILE_APPEND );
	chmod( 'crontab.bat', 0755);
	$execresult = [];
	//exec('crontab crontab.txt >> appt_error_log', $execresult, $returncode);
	if ($_SESSION["TRACE"]) error_log("ENVIRON: SYSTEM, created cron job files");
}

// error_log("ENVIRON DEBUG: Setting variables");
//
// Create a time stamp
$TimeStamp = Date("Y-m-d H:i:s");
$TodayDate = Date("Y-m-d");
$NullDate = "1900-01-01";
$NullTime = "00:00:00";

// Define some globals
$VIEW_CB = 1;
$ADD_CB = 2;
$VIEW_APP = 4;
$ADD_APP = 8;
$USE_RES = 32;
$ACCESS_ALL = $VIEW_CB + $ADD_CB + $VIEW_APP + $ADD_APP + $USE_RES;
$MANAGER = 512;
$ADMINISTRATOR = 1024;

include "functions.php";

// check for new version
/*
$_SESSION["NewVersion"] = $_SESSION["SystemVersion"];
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

// The following variables are supported in the $_SESSION[] array:
// NewVersion (for future use)
// SystemVersion
// SystemGreeting
// SystemNotice
// SystemConfirm
// SystemInfo (for future use)
// SystemURL
// SystemEmail
// SystemReminders
// SystemAttach
// TRACE
// SummaryAll
// User (array - all user record items except password)
// UserSiteList
// MaxWaitSequence
?>
