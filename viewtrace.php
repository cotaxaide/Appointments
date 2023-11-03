<?PHP
//Version 9.02
//	Added login event to user event block
//	Improved event block sequencing
//Version 9.0
//	Changes due to user SESSION consolidation
//Version 8.04a
//	Some reformatting
//Version 5.02b
//	Absorb duplicates
//Version 5.02a
//ini_set('display_errors', '1');

// Set up environment
require "environment.php";

$Errormessage = "";
// If the UserIndex has not been set as a session variable, the user needs to sign in
if (! isset($_SESSION["User"])) {
	header('Location: index.php'); // prevents direct access to this page (must sign in first).
	exit;
}

//Get POST variables if action requested
if ($_SERVER["REQUEST_METHOD"] == "POST") {
}

function logfile_print() {
	global $user_record;
	global $user_email;
	global $user_count;

	// open the trace file
	$raw_flag = false;
	$PW_flag = false;
	$getanother = 0;
	$old_date = "";
	$last_entry = "";
	$last_entry_count = 0;
	$user_record = [];
	$user_email = [];
	$user_email["_SYSTEM_"] = "";
	$user_count = [];
	$logfile = fopen("appt_error_log", "r") or die("Unable to open file!");
	while (! feof($logfile) /* AND ($getanother < 500) */ ) {
		 
		// Eliminate records of no interest
		$event_entry = fgets($logfile);
		if (trim($event_entry) == "") continue;
		if (substr($event_entry, 0, 8) == "Warning:") continue;
		if (strstr($event_entry, ", Login")) continue;

		// Eliminate duplicate events but count them
		if ($event_entry == $last_entry) {
			$last_entry_count++;
			continue;
		}
		$last_entry_count = 0;
		$last_entry = $event_entry;

		// Close span block if the actual message was printed
		if (substr($event_entry, 0, 1) == "[") {
			if ($raw_flag) @$user_record[$event_user] .= "</span>";
			$raw_flag = false;
		}
		if ($raw_flag) {
			@$user_record[$event_user] .= "<br />$event_entry";
			continue;
		}

		// Get the date, time, and event out of the record
		$end_of_timestamp = strpos($event_entry, "]");
		$event_timestamp[0] = $event_timestamp[1] = "";
		$event_timestamp = explode(" ", substr($event_entry, 1, $end_of_timestamp - 2));
		$event = substr($event_entry, $end_of_timestamp + 1);

		// Show and clear data when a new date begins
		if ($event_timestamp[0] != $old_date) {
			// Date changed, show all the data
			show_all();

			if ($old_date) {
				// Close yesterday's box
				echo "</div> <!-- " . $old_date . " -->";
			}

			// Clear yesterday's data
			$user_record = [];
			$user_count = [];
			$user_inet = [];

			// Start today's box
			$old_date = $event_timestamp[0]; // old date is now the new date
			echo "\n\n<div class='trace_daybox' title='$old_date' onclick=\"Top$old_date.focus();\">";
			echo "\n<div id=\"Top$old_date\">$old_date</div>";
		}

		// Print the actual message if from the PHP interpreter
		if (strpos($event, "PHP ")) {
			@$user_record[$event_user] .= "\n<br /><span class='trace_errorbox' style='float: none;'>$event_timestamp[1]: $event";
			$raw_flag = true;
			continue;
		}

		// Parse the event part of the activity record
		$event_module = $event_user = $event_data = "";
		
		$colon_location = strpos($event, ":");
		$event_module = substr($event, 1, $colon_location - 1);
		$comma_location = strpos($event, ",");
		$event_user = trim(substr($event, $colon_location + 1, $comma_location - $colon_location - 1));
		if ($event_user == "SYSTEM") $event_user = "_SYSTEM_";
		$event_data = trim(substr($event, $comma_location + 1));
		if (! isset($user_count[$event_user])) $user_count[$event_user] = 0;
		//echo "\n<br />$event($event_module, $event_user, $event_data)"; // for debugging

		// Add the event to the user record
		switch ($event_module) {
		case "INDEX":
			// Show raw event for debugging
			// echo "\n<div class='userbox'>$event_timestamp[1] on $event_timestamp[0]";
			// echo "\n<br />$event_module<br />$event_user<br />$event_data</div>";

			// Set up correlation between email and login name
			if (strstr($event_data, "using email")) {
				$email = substr($event_data, 12);
				$user_email[$email] = $event_user;
				$user_email[$event_user] = $email;
				break;
			}

			// If PW requested, start a new event block
			if ($event_data == "GetPW") {
				$email = $event_user;
				if (! isset($user_email[$email])) {
					$user_email[$email] = "Unknown";
					$user_email["Unknown"] = $email;
				}
				$PW_flag = true;
			}

			// If user name has an "@", it's likely email so swap
			if (strstr($event_user, "@")) {
				$email = $event_user;
				$event_user = $user_email[$email] ?? "Email error 1";
			}

			// Start a new event block for a new login
			if (strstr($event_data, "logged in") OR $PW_flag) { // Start a new activity set for the user
				$PW_flag = false;
				if (array_key_exists($event_user, $user_record)) {
					// Move the old event block to it's own array key
					$user_count[$event_user] = ($user_count[$event_user] ?? 0) + 1;
					$user_record[$event_user . " (" . $user_count[$event_user] . ")"] = $user_record[$event_user];
					$user_email[$event_user . " (" . $user_count[$event_user] . ")"] = $user_email[$event_user];
				}
				$user_record[$event_user] = "";
			}
			@$user_record[$event_user] .= "\n<br />$event_timestamp[1]: [I] $event_data";
			break;

		case "APPT":
			if (strstr($event_data, "ViewUser")) {
				$user_inet[$email] = true;
				$user_inet[$event_user] = true;
			}
			@$user_record[$event_user] .= "\n<br />$event_timestamp[1]: [A] $event_data";
			break;

		case "EXPORT":
			@$user_record[$event_user] .= "\n<br />$event_timestamp[1]: [A] $event_data";
			break;
			
		case "MANAGE":
			@$user_record[$event_user] .= "\n<br />$event_timestamp[1]: [M] $event_data";
			break;
			
		case "CHGOPT":
			@$user_record[$event_user] .= "\n<br />$event_timestamp[1]: [C] $event_data";
			break;
			
		case "PATT":
			@$user_record[$event_user] .= "\n<br />$event_timestamp[1]: [P] $event_data";
			break;
			
		case "REMIND";
			if (strstr($event_data, "reminders ran")) $event_data = "Reminders ran";
			@$user_record["_SYSTEM_"] .= "\n<br />$event_timestamp[1]: $event_data";
			break;
			
		case "PHP Fatal error":
			echo "\n<div class='errorbox'>@$event_timestamp[1]: $event";
			$raw_flag = true;
			break;
			
		default:
			// Show raw event
			echo "\n<div class='errorbox'>@$event_timestamp[1]: $event</div>";
		}

		$getanother++;
	}

	// finish up
	show_all();
	echo "</div>";

	// close the trace file
	fclose($logfile);
}

function show_all() {
	global $user_record;

	// Sort blocks by first entry time
	asort($user_record);
	foreach ($user_record as $user => $record) {
		show_box($user, $record);
	}

	// Clear the records for the day (but keep emails)
	$user_record = [];
	$user_count = [];
}

function show_box($user, $record) {
	global $user_record;
	global $user_email;
	global $user_count;

	if ($record == "") return;
	$class = get_tclass($user, $record);
	$email = $user_email[$user] ?? "Email unknown";

	// If no sequence number, add the next for that user
	if (strstr($user, " (") == false) {
		$user_count[$user] = ($user_count[$user] ?? 0) + 1;
		$user .= " ($user_count[$user])";
	}

	// Output the box
	echo "<div class=\"$class\"><center><b>$user";
	if ($email) echo " ($email)";
	echo "</b></center>" .  substr($record, 7) . "</div>";
}

function get_tclass($user, $record) {
	if ($user == "_SYSTEM_") {
		$tclass = 'trace_systembox';
	}
	else if (strstr($record, "[A]") AND strstr($record, "view=ViewUser")) {
		$tclass = 'trace_inetbox';
	}
	else if (strstr($record, "[I]") AND strstr($record, "NewUser")) {
		$tclass = 'trace_inetbox';
	}
	else if (strstr($record, "[A]") OR strstr($record, "[M]")) {
		$tclass = 'trace_userbox';
	}
	else {
		$tclass = 'trace_inetbox';
	}
	return $tclass;
}

?>

<!--================================================================================================================-->
<!DOCTYPE html>

<head>
<title>AARP Appointment Error Log</title>
<meta name=description content="AARP Appointments">
<link rel="SHORTCUT ICON" href="appt.ico">
<link rel="stylesheet" href="appt.css">
<script>
	/* ******************************************************************************************************************* */
	function Initialize() {
	/* ******************************************************************************************************************* */
	}
</script>
</head>
<!-- =================================================== WEB PAGE BODY ============================================ -->

<body onload="Initialize()">
<div id="Main">
	<div class="page_header">
		<h1>Tax-Aide Appointments Error Log</h1>
		<?php echo "You are signed in as " . str_replace("!", "&apos;", $_SESSION["User"]["user_full_name"]) . "\n"; ?>
	</div>
</div>

<div>
	<?php logfile_print(); ?>
</div>

<div id="trace_manage_box">
	<a href="sitemanage.php">
		<button id="trace_close_log_button">Close log</button>
	</a>
</div>

</body>

