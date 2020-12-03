<?PHP
//Version 5.02b
//	Absorb duplicates
//Version 5.02a
//ini_set('display_errors', '1');

// Set up environment
require "environment.php";

$Errormessage = "";
// If the UserIndex has not been set as a session variable, the user needs to sign in
if (@$_SESSION["UserIndex"] == 0) {
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
	$getanother = 0;
	$old_date = "";
	$last_entry = "";
	$last_entry_count = 0;
	$user_record = [];
	$user_email = [];
	$user_count = [];
	$logfile = fopen("appt_error_log", "r") or die("Unable to open file!");
	while (!feof($logfile) /* AND ($getanother < 500) */ ) {
		$event_entry = fgets($logfile);
		if (! $event_entry) continue;
		if (trim($event_entry) == "") continue;
		if (substr($event_entry, 0, 8) == "Warning:") continue;
		if ($event_entry == $last_entry) {
			$last_entry_count++;
			continue;
		}
		else {
			//echo "[" . $last_entry_count-1 . " dups]";
			$last_entry_count = 0;
			$last_entry = $event_entry;
		}
		$end_of_timestamp = strpos($event_entry, "]");
		$event_timestamp[0] = $event_timestamp[1] = "";
		$event_timestamp = explode(" ", substr($event_entry, 1, $end_of_timestamp - 2));
		$event = substr($event_entry, $end_of_timestamp + 1);
		if ($event_timestamp[0] != $old_date) {
			// Date changed, show all the data
			show_all();

			if ($old_date) {
				// Close yesterday's box
				echo "</div> <!-- " . $old_date . " -->";
			}

			// Start today's box
			$old_date = $event_timestamp[0];
			echo "\n\n<div class='daybox'><div>$old_date</div>";

			// Clear yesterday's data
			$user_record = [];
			$user_count = [];
		}

		// Parse the new record
		if (strpos($event, "PHP ")) {
			@$user_record[$event_user] .= "\n<br /><span class='errorbox' style='float: none;'>$event_timestamp[1]: $event</span>";
			continue;
		}
		$colon_location = strpos($event, ":");
		$event_module = substr($event, 1, $colon_location - 1);
		$comma_location = strpos($event, ",");
		$event_user = trim(substr($event, $colon_location + 1, $comma_location - $colon_location - 1));
		$event_data = trim(substr($event, $comma_location + 1));
		//echo "\n<br />$event($event_module, $event_user, $event_data)";

		// Add the event to the user record
		switch ($event_module) {
		case "INDEX":
			if (strstr($event_data, "Login")) { 
				break;
			}
			if (strstr($event_data, "using email")) {
				// Show raw event
				//echo "\n<div class='userbox'>$event_timestamp[1] on $event_timestamp[0]";
				//echo "\n<br />$event_module<br />$event_user<br />$event_data</div>";
				$email = substr($event_data, 12);
				$user_email[$email] = $event_user;
				$user_email[$event_user] = $email;
				$user_count[$event_user] = @$user_count[$event_user] + 1;
				if (array_key_exists($event_user, $user_record)) {
					//show_box("userbox", $event_user, $user_record[$event_user]);
					$user_record[$event_user . " (" . $user_count[$event_user] . ")"] = "\n<br />$event_timestamp[1]: [I] " . $user_record[$event_user];
					$user_email[$event_user . " (" . $user_count[$event_user] . ")"] = $user_email[$event_user];
				}
				$user_record[$event_user] = "";
				break;
			}
			@$user_record[$event_user] .= "\n<br />$event_timestamp[1]: [I] $event_data";
			break;
		case "APPT":
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
			@$user_record["SYSTEM"] .= "\n<br />$event_timestamp[1]: $event_data";
			break;
		default:
			// Show raw event
			//echo "\n<div class='errorbox'>$event_timestamp[1] on $event_timestamp[0]";
			//echo "\n<br />$event_module<br />User: $event_user<br />Event: $event_data</div>";
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
	asort($user_record);
	foreach ($user_record as $j => $e) {
		if ($j == "SYSTEM") {
			$class = 'systembox';
		}
		else if (strstr($e, "[A]") AND strstr($e, "=ViewUser")) {
			$class = 'inetbox';
		}
		else {
			$class = 'userbox';
		}

		// Show the box
		show_box($class, $j, $e);
	}
}

function show_box($c, $j, $e) {
	global $user_record;
	global $user_email;

	echo "\n\n<div class=$c><center><b>$j";
	$m = @$user_email[$j];
	if ($m) echo " ($m)";
	echo "</b></center>" .  substr($e, 7) . "</div>";
}

?>

<!--================================================================================================================-->
<!DOCTYPE html>

<head>
<title>AARP Appointment Error Log</title>
<meta name=description content="AARP Appointments">
<link rel="SHORTCUT ICON" href="appt.ico">
<link rel="stylesheet" href="appt.css">
<style>
	.daybox {
		border: 2px solid black;
		margin: 5px;
		background-color: lavender;
		font-size: 120%;
		font-weight: bold;
		text-align: center;
		display: inline-block;
		width: 100%;
	}
	.systembox,
	.errorbox,
	.inetbox,
	.userbox {
		border: 2px solid black;
		margin: 0.5em;
		background-color: palegreen;
		text-align: left;
		font-size: 80%;
		font-weight: normal;
		float: left;
		padding: 3px;
	}
	.systembox {
		background-color: yellow;
	}
	.inetbox {
		background-color: blue;
		color: white;
	}
	.errorbox {
		border: 2px solid red;
		background-color: hotpink;
		color: white;
	}
	.userbox {
		background-color: palegreen;
	}
	#ManageBox {
		border: 3px solid green;
		background-color: lightgreen;
		position: fixed;
		top: 5px;
		right: 20px;
		z-index: 2;
	}
	#ManageButton {
		padding: 1em;
		font-size: 120%;
		font-weight: bold;
	}
</style>
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
		<?php echo "You are signed in as " . str_replace("!", "&apos;", $_SESSION["UserFullName"]); ?>
	</div>
</div>

<div>
	<?php logfile_print(); ?>
</div>

<div id="ManageBox">
	<a href="sitemanage.php">
		<button id="ManageButton">Close log</button>
	</a>
</div>

</body>
