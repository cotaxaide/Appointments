<?php
// This routine:
// - makes sure that the database has the correct current set of tables and table columns
// - creates the opendb.php file so that all php routines have access to the database

//--------------------------------- VERSION HISTORY -----------------------------------
$VERSION = "9.08";
//	Corrections to appointment.php, showslots.php, sitemanage.php, appt.css
//$VERSION = "9.07";
//	Corrections to appointment.php
//$VERSION = "9.06";
//	Corrections to appointment.php
//$VERSION = "9.05";
//	Correction in appointment.php
//$VERSION = "9.04";
//	Corrections to index.php and appointment.php
//$VERSION = "9.03";
//	Configure_Table for ACCESS_TABLE moved before Configure_Columns
//$VERSION = "9.02";
//	Added heartbeat to system table for updating Daily View
//$VERSION = "9.01";
//	Added NOT NULL to all fields without a default value
//	Increased appt_type from 1 to 10 characters
//$VERSION = "8.05";
//	Correction to excelexport.php module
//	Updated link to AARP site locator in index.php
//$VERSION = "8.04";
//	Corrected sending too many reminder messages
//	Added revised excel export module
//	fixdbv8 commented out - causes corruption in high traffic
//$VERSION = "8.03";
//	Added user_excel_export field to appt_users to save export list
//	Changed detection of existing column due to PHP 8 changes
//	Added missing global in Add_Admin function
//$VERSION = "8.02";
//	PHP 8.1 changes to htmlspecialchars_decode to use ?? operator
//$VERSION = "8.01";
//	Removed underscores from default system email
//	NullDate changed from 0000-00-00 to 1900-01-01 for strict databases
//	Removed parameter from mysqli_connect_errno, not allowed in PHP v8.0
//	Added system_confirm, system_
//	require_once "fixdbv8.php";
//$VERSION = "7.05";
//	Minor warning message correction
//$VERSION = "7.04";
//	Minor display issues
//$VERSION = "7.03";
//	Reminders not converting character coding
//$VERSION = "7.02";
//	Debugging messages cluttering trace
//	Additional instructions not showing proper site
//	Updated appointment.php, showslots.php, excelexport.php
//$VERSION = "7.01";
//	Make table headings sticky
//	Prevent windows from hiding save/delete/etc buttons
//	Moved character de/coding to seperate functions.php collections
//	Moved appt list creation from appointment.php to showslots.php
//	Updated index.php, sitemanage.php, appointment.php, excelexport.php
//$VERSION = "6.01";
//	Added a site-specific note to the self-appointment window
//$VERSION = "5.02b";
//	Updated appointment.php, reminders.php, viewtrace.php
//$VERSION = "5.02a";
//	Updated index.php, sitemanage.php, appointment.php, reminders.php, viewtrace.php
//$VERSION = "5.02";
//	Updated appointment.php, sitemanage.php, added viewtrace.php
//$VERSION = "5.01c";
//	Updated appointment.php, environment.php
//$VERSION = "5.01b";
//	Updated index,php, appointment.php, sitemanage.php
//$VERSION = "5.01a";
//	Notice() was not returning the default notice
//	Defaulting text columns to "" caused errors with some mySql installations
//	System mail and url columns not properly populated on update
//$VERSION = "5.00";

//--------------------------------- GLOBAL VARIABLES -----------------------------------
$initialize = "none";

$USER_TABLE = "taxappt_users";
$ACCESS_TABLE = "taxappt_access";
$APPT_TABLE = "taxappt_appts";
$SITE_TABLE = "taxappt_sites";
$SYSTEM_TABLE = "taxappt_system";
$SCHED_TABLE = "taxappt_scheds";

$admin_first = "";
$admin_last = "";
$admin_email = "";
$admin_phone = "";
$admin_sitename = "";
$system_email = "";
$host = "localhost";
$dbname = "";
$dbuserid = "";
$dbpassword = "";
$systemURL = "";
$sysNotice = "";
$sysConfirm = "";
$errormessage = "";
$NullDate = "1900-01-01";

	// error_log("SETUP DEBUG: starting setup.php, Session ID = " . session_id());
if (file_exists("opendb.php")) {

	// open it
	//error_log("SETUP DEBUG: opening opendb.php, Session ID = " . session_id());
	require_once "opendb.php";

	//  confirm that tables and table columns match the current specifications
	$query = "SELECT * FROM $SYSTEM_TABLE";
	$result = mysqli_query($dbcon, $query);
	$row = mysqli_fetch_array($result); // only one row

	// populate SESSION variables
	$_SESSION["SystemVersion"] = $row['system_version'];
	$_SESSION["SystemGreeting"] = $row['system_greeting'];
	$_SESSION["SystemNotice"] = $sysNotice = $row['system_notice'];
	$_SESSION["SystemConfirm"] = $sysConfirm = $row['system_confirm'] ?? "";
	$_SESSION["SystemAttach"] = $row['system_attach'] ?? "";
	$_SESSION["SystemInfo"] = $row['system_info'];
	$_SESSION["SystemURL"] = $systemURL = $row['system_url'];
	$_SESSION["SystemHeartbeat"] = $row['system_heartbeat'];
	$_SESSION["TRACE"] = $row['system_trace'];
	// New in version 5.00
	$system_email = (isset($row['system_email'])) ? $row['system_email'] : "" ;
	if (($system_email == "") || ($system_email = "no_reply@tax_aide_reservations.no_email")) {
		$system_email = "do-not-reply@tax-aide-reservations.no-reply.email";
	}
	$_SESSION["SystemEmail"] = $system_email;
	$_SESSION["SystemReminders"] = (isset($row['system_reminders'])) ? $row['system_reminders'] : "" ;
	if ($_SESSION["SystemVersion"] != $VERSION) {
		Configure_Database();
		// New in version 8.00
		if ($_SESSION["SystemVersion"] < "8.00") {
			$query = "UPDATE $APPT_TABLE SET";
			$query .= " `appt_date` = '$NullDate'";
			$query .= " WHERE `appt_date` = '0000-00-00'";
			mysqli_query($dbcon, $query);
			$query = "UPDATE $APPT_TABLE SET";
			$query .= " `appt_emailsent` = '$NullDate'";
			$query .= " WHERE `appt_emailsent` = '0000-00-00'";
			$query .= " OR `appt_emailsent` IS NULL";
			mysqli_query($dbcon, $query);
		}
		if ($_SESSION["TRACE"]) error_log("SETUP: Database updated from version " . $_SESSION["SystemVersion"] . " to $VERSION.");
		exit("Your database has been updated from version " . $_SESSION["SystemVersion"] . " to $VERSION.\n\nPlease close your browser and restart the appointment system.");
		}
	return;
}

// opendb.php does not exist
// error_log("SETUP DEBUG: no opendb.php, Session ID = " . session_id());
if (isset($_SERVER["HTTP_REFERER"]) && (strpos($_SERVER["HTTP_REFERER"], "reminders.php"))) return; // this is being run from reminders.php
if ($_SERVER["REQUEST_METHOD"] == "POST") {
	$admin_first = htmlspecialchars(stripslashes(trim($_POST["admin_first"])));
	$admin_last = htmlspecialchars(stripslashes(trim($_POST["admin_last"])));
	$admin_email = htmlspecialchars(stripslashes(trim($_POST["admin_email"])));
	$admin_phone = htmlspecialchars(stripslashes(trim($_POST["admin_phone"])));
	$admin_sitename = htmlspecialchars(stripslashes(trim($_POST["admin_sitename"])));
	$host = htmlspecialchars(stripslashes(trim($_POST["host"])));
	$dbname = htmlspecialchars(stripslashes(trim($_POST["dbname"])));
	$dbuserid = htmlspecialchars(stripslashes(trim($_POST["dbuserid"])));
	$dbpassword = htmlspecialchars(stripslashes(trim($_POST["dbpassword"])));
	$dbtimezone = htmlspecialchars(stripslashes(trim($_POST["dbtimezone"])));
	$systemURL = htmlspecialchars(trim($_POST["systemURL"]));
	$admin_name = $admin_first . substr($admin_last,0,1); 

	//Try to connect to the database
	if (! $host) $host = "localhost";
	$dbcon = mysqli_connect($host, $dbuserid, $dbpassword, $dbname);

	if (mysqli_connect_errno()) {
		$initialize = "fail";
		$errormessage = mysqli_connect_error();
	}
	else {
		$initialize = "pass";
		mysqli_select_db($dbcon, $dbname);
		Create_Opendb_FIle($host, $dbuserid, $dbpassword, $dbname);
		Configure_Database(); // add the tables
		Add_Admin($admin_sitename, $admin_first, $admin_last, $admin_email, $admin_phone, $admin_name);

		$_SESSION['SystemVersion'] = $VERSION;
		$_SESSION['UserIndex'] = 1;
		$_SESSION['UserName'] = $admin_name;
		$_SESSION['UserHome'] = 2;
		$_SESSION['UserPass'] = "admin";
		$_SESSION['UserEmail'] = $admin_email;
		$_SESSION['UserPhone'] = $admin_phone;
		$_SESSION['UserFirst'] = $admin_first;
		$_SESSION['UserLast'] = $admin_last;
		$_SESSION['UserFullName'] = "$admin_first $admin_last";
		$_SESSION['UserOptions'] = "A";
		// All done, go to the management routine
		// error_log("SETUP DEBUG: Going to sitemanage.php");
		header('Location: sitemanage.php');
		// error_log("SETUP DEBUG: Came back from sitemanage.php after a header");
	}
}

//----------------------------------------------------------------------------
function Configure_Database() {
// This function describes the database structure and calls other functions
// to add tables and columns as needed.
//----------------------------------------------------------------------------
// error_log("SETUP DEBUG: Making the database");

global $SYSTEM_TABLE, $NullDate;
Configure_Table($SYSTEM_TABLE, "system_index", "INT AUTO_INCREMENT PRIMARY KEY");
Configure_Column($SYSTEM_TABLE, "system_version", "VARCHAR(50) NOT NULL");
Configure_Column($SYSTEM_TABLE, "system_greeting", "TEXT NOT NULL");
Configure_Column($SYSTEM_TABLE, "system_notice", "TEXT NOT NULL");
Configure_Column($SYSTEM_TABLE, "system_confirm", "TEXT NOT NULL");
Configure_Column($SYSTEM_TABLE, "system_attach", "TEXT NOT NULL");
Configure_Column($SYSTEM_TABLE, "system_info", "TEXT NOT NULL");
Configure_Column($SYSTEM_TABLE, "system_url", "TEXT NOT NULL");
Configure_Column($SYSTEM_TABLE, "system_email", "TEXT NOT NULL");
Configure_Column($SYSTEM_TABLE, "system_reminders", "TEXT NOT NULL");
Configure_Column($SYSTEM_TABLE, "system_trace", "VARCHAR(1) NOT NULL");
Configure_Column($SYSTEM_TABLE, "system_heartbeat", "INT NOT NULL");

global $ACCESS_TABLE;
Configure_Table($ACCESS_TABLE, "acc_index", "BIGINT AUTO_INCREMENT PRIMARY KEY");
Configure_Column($ACCESS_TABLE, "acc_owner", "BIGINT UNSIGNED NOT NULL");
Configure_Column($ACCESS_TABLE, "acc_location", "BIGINT UNSIGNED NOT NULL");
Configure_Column($ACCESS_TABLE, "acc_user", "BIGINT UNSIGNED NOT NULL");
Configure_Column($ACCESS_TABLE, "acc_option", "TINYTEXT NOT NULL");

global $USER_TABLE;
Configure_Table($USER_TABLE, "user_index", "BIGINT AUTO_INCREMENT PRIMARY KEY");
Configure_Column($USER_TABLE, "user_name", "VARCHAR(50) NOT NULL");
Configure_Column($USER_TABLE, "user_email", "VARCHAR(256) NOT NULL");
Configure_Column($USER_TABLE, "user_phone", "VARCHAR(25) NOT NULL");
Configure_Column($USER_TABLE, "user_pass", "VARCHAR(100) NOT NULL");
Configure_Column($USER_TABLE, "user_last", "VARCHAR(50) NOT NULL");
Configure_Column($USER_TABLE, "user_first", "VARCHAR(50) NOT NULL");
Configure_Column($USER_TABLE, "user_home", "BIGINT NOT NULL");
Configure_Column($USER_TABLE, "user_appt_site", "BIGINT NOT NULL");
Configure_Column($USER_TABLE, "user_options", "TEXT NOT NULL");
Configure_Column($USER_TABLE, "user_sitelist", "TEXT NOT NULL");
Configure_Column($USER_TABLE, "user_excel_export", "TEXT NOT NULL");
Configure_Column($USER_TABLE, "user_lastlogin", "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

global $SITE_TABLE;
Configure_Table($SITE_TABLE, "site_index", "BIGINT AUTO_INCREMENT PRIMARY KEY");
Configure_Column($SITE_TABLE, "site_name", "TEXT NOT NULL");
Configure_Column($SITE_TABLE, "site_address", "TEXT NOT NULL");
Configure_Column($SITE_TABLE, "site_inet", "TEXT NOT NULL");
Configure_Column($SITE_TABLE, "site_contact", "TEXT NOT NULL");
Configure_Column($SITE_TABLE, "site_message", "TEXT NOT NULL");
Configure_Column($SITE_TABLE, "site_attach", "TEXT NOT NULL");
Configure_Column($SITE_TABLE, "site_addedby", "BIGINT NOT NULL");
Configure_Column($SITE_TABLE, "site_open", "DATE NOT NULL");
Configure_Column($SITE_TABLE, "site_closed", "DATE NOT NULL");
Configure_Column($SITE_TABLE, "site_instructions", "TEXT NOT NULL");
Configure_Column($SITE_TABLE, "site_reminder", "TEXT NOT NULL");
Configure_Column($SITE_TABLE, "site_lastrem", "TEXT NOT NULL");
Configure_Column($SITE_TABLE, "site_help", "TEXT NOT NULL");
Configure_Column($SITE_TABLE, "site_sumres", "TEXT NOT NULL");
Configure_Column($SITE_TABLE, "site_10dig", "TEXT NOT NULL");

global $APPT_TABLE;
Configure_Table($APPT_TABLE, "appt_no", "BIGINT AUTO_INCREMENT PRIMARY KEY");
Configure_Column($APPT_TABLE, "appt_date", "DATE DEFAULT " . "'" . $NullDate . "'");
Configure_Column($APPT_TABLE, "appt_time", "TIME NOT NULL");
Configure_Column($APPT_TABLE, "appt_name", "TEXT NOT NULL");
Configure_Column($APPT_TABLE, "appt_location", "BIGINT NOT NULL");
Configure_Column($APPT_TABLE, "appt_email", "VARCHAR(256) NOT NULL");
Configure_Column($APPT_TABLE, "appt_emailsent", "DATE DEFAULT " . "'" . $NullDate . "'");
Configure_Column($APPT_TABLE, "appt_phone", "VARCHAR(50) NOT NULL");
Configure_Column($APPT_TABLE, "appt_tags", "TEXT NOT NULL");
Configure_Column($APPT_TABLE, "appt_need", "TEXT NOT NULL");
Configure_Column($APPT_TABLE, "appt_info", "TEXT NOT NULL");
Configure_Column($APPT_TABLE, "appt_status", "TEXT NOT NULL");
Configure_Column($APPT_TABLE, "appt_wait", "BIGINT NOT NULL");
Configure_Column($APPT_TABLE, "appt_change", "DATETIME NOT NULL");
Configure_Column($APPT_TABLE, "appt_by", "TEXT NOT NULL");
Configure_Column($APPT_TABLE, "appt_tracking", "TEXT");
Configure_Column($APPT_TABLE, "appt_type", "VARCHAR(10) NOT NULL");

global $SCHED_TABLE;
Configure_Table($SCHED_TABLE, "sched_index", "INT AUTO_INCREMENT PRIMARY KEY");
Configure_Column($SCHED_TABLE, "sched_location", "BIGINT NOT NULL");
Configure_Column($SCHED_TABLE, "sched_name", "TEXT NOT NULL");
Configure_Column($SCHED_TABLE, "sched_pattern", "TEXT NOT NULL");
Update_Version();
}

//----------------------------------------------------------------------------
function Configure_Table($tablename, $indexname, $optionlist) {
//----------------------------------------------------------------------------
	global $dbname;
	global $DEBUG;
	global $dbcon;

	// add the table if it doesn't exist
	$query = "CREATE TABLE IF NOT EXISTS `$tablename`"; 
	$query .= " (`$indexname` $optionlist)";
	mysqli_query($dbcon, $query);
}

//----------------------------------------------------------------------------
function Configure_Column($tablename, $columnname, $optionlist) {
//----------------------------------------------------------------------------
	global $dbname;
	global $DEBUG;
	global $dbcon;

	// determine if the column exists
	$query = "SHOW COLUMNS FROM `$tablename`";
	$query .= " LIKE '$columnname'";
	$result = mysqli_query($dbcon, $query);
    	$action = (mysqli_num_rows($result) > 0) ? "MODIFY" : "ADD" ;
	
	//either add or modify the column's characteristics
	$query = "ALTER TABLE `$tablename`";
	$query .= " $action COLUMN `$columnname` $optionlist";
	//error_log("ADM: " . $query); // for debugging
	mysqli_query($dbcon, $query);
}

//----------------------------------------------------------------------------
function Update_Version() {
//----------------------------------------------------------------------------
	global $DEBUG;
	global $SYSTEM_TABLE;
	global $VERSION;
	global $systemURL;
	global $dbcon;
	global $system_email;
	global $sysConfirm;
	global $sysNotice;

	$query = "INSERT INTO $SYSTEM_TABLE";
	$query .= " SET `system_index` = 1";
	$query .= ", `system_version` = '$VERSION'";
	$query .= ", `system_email` = '$system_email'";
	$query .= ", `system_notice` = '$sysNotice'";
	$query .= ", `system_confirm` = '$sysConfirm'";
	$query .= ", `system_url` = '$systemURL'";
	$query .= " ON DUPLICATE KEY";
	$query .= " UPDATE `system_version` = '$VERSION'";
	mysqli_query($dbcon, $query);
}

//----------------------------------------------------------------------------
function Create_Opendb_File($host, $dbuserid, $dbpassword, $dbname) {
//----------------------------------------------------------------------------
	global $dbtimezone;
	global $VERSION;
	global $DEBUG;
	$ds = "$";

	// Open the file for writing
	$fileptr = fopen("opendb.php", "w");
	fwrite($fileptr, "<?php\n");
	fwrite($fileptr, "//Variables for connecting to your database.\n");
	fwrite($fileptr, "//These variable values come from your database naming\n");
	$text = $ds . "hostname = '" . $host . "';\n";
	fwrite($fileptr, $ds . "hostname = '" . $host . "';\n");
	fwrite($fileptr, $ds . "dbname = '" . $dbname . "';\n\n");

	fwrite($fileptr, "//These variable values come from your database user list\n");
	fwrite($fileptr, $ds . "username = '" . $dbuserid . "';\n");
	fwrite($fileptr, $ds . "dbpassword = '" . $dbpassword . "';\n\n");

	fwrite($fileptr, "//Connecting to your database\n");
	fwrite($fileptr, $ds . "dbcon = mysqli_connect(" . $ds . "hostname, " . $ds . "username, " . $ds . "dbpassword, " . $ds . "dbname);\n");
	fwrite($fileptr, "if (mysqli_connect_errno()) DIE (\"Unable to connect to database! (\" . mysqli_connect_error() . \")\");\n");
	fwrite($fileptr, "mysqli_select_db(" . $ds . "dbcon, " . $ds . "dbname);\n\n");
	fwrite($fileptr, "date_default_timezone_set('" . $dbtimezone . "');\n\n");
	// fwrite($fileptr, "require_once \"session.php\";\n"); // removed in version 5.00
	fwrite($fileptr, "?>");
	fclose($fileptr);
}

//----------------------------------------------------------------------------
function Add_Admin($admin_sitename, $admin_first, $admin_last, $admin_email, $admin_phone, $admin_name) {
// This function:
// 	1. adds the "Unassigned" site as the first
// 	2. adds the Administrator's site as the second
// 	3. adds the Administrator to the User Table.
//----------------------------------------------------------------------------
	global $USER_TABLE, $ACCESS_TABLE, $APPT_TABLE, $SITE_TABLE, $SYSTEM_TABLE;
	global $DEBUG;
	global $dbcon;
	global $NullDate;

	$query = "INSERT INTO $SITE_TABLE SET";
	$query .= "  `site_index` = 1";
	$query .= ", `site_name` = 'Unassigned'";
	$query .= ", `site_address` = '||||||'";
	$query .= ", `site_inet` = ''";
	$query .= ", `site_contact` = ''";
	$query .= ", `site_message` = ''";
	$query .= ", `site_addedby` = 0";
	$query .= ", `site_open` = '$NullDate'";
	$query .= ", `site_closed` = '$NullDate'";
	$query .= ", `site_help` = ''";
	$query .= ", `site_sumres` = ''";
	$query .= ", `site_10dig` = ''";
	mysqli_query($dbcon, $query);

	$query = "INSERT INTO $SITE_TABLE SET";
	$query .= "  `site_index` = 2";
	$query .= ", `site_name` = '$admin_sitename'";
	$query .= ", `site_address` = '||||||'";
	$query .= ", `site_inet` = ''";
	$query .= ", `site_contact` = ''";
	$query .= ", `site_message` = ''";
	$query .= ", `site_addedby` = 0";
	$query .= ", `site_open` = '$NullDate'";
	$query .= ", `site_closed` = '$NullDate'";
	$query .= ", `site_help` = ''";
	$query .= ", `site_sumres` = ''";
	$query .= ", `site_10dig` = ''";
	mysqli_query($dbcon, $query);

	$query = "INSERT INTO $USER_TABLE SET";
	$query .= "  `user_index` = 1";
	$query .= ", `user_name` = '$admin_name'";
	$query .= ", `user_email` = '$admin_email'";
	$query .= ", `user_phone` = '$admin_phone'";
	$query .= ", `user_pass` = 'admin'";
	$query .= ", `user_last` = '$admin_last'";
	$query .= ", `user_first` = '$admin_first'";
	$query .= ", `user_home` = 2";
	$query .= ", `user_appt_site` = 0";
	$query .= ", `user_options` = 'A'";
	$query .= ", `user_sitelist` = 0";
	mysqli_query($dbcon, $query);
}

?>

<!--================================================================================================================-->
<!--================================================= START OF WEB PAGE ============================================-->
<!--================================================================================================================-->
<!DOCTYPE html>

<head>
<title>AARP Appointments</title>
<meta http-equiv=Content-Type content="text/html" charset="us-ascii">
<meta name=description content="AARP Appointments">
<link rel="SHORTCUT ICON" href="appt.ico">
<link rel="stylesheet" href="appt.css">

<script>

var tzchecked = <?php global $dbtimezone; $tz = ($dbtimezone) ? "true":"false"; echo "$tz;\n"; ?>

//----------------------------------------------------------------------------
function Check_Inputs() {
//----------------------------------------------------------------------------
	
	// Time Zone ----------------------
	SetupForm.submit_button.disabled = (tzchecked) ? false:true;

	// First Name ----------------------
	if (SetupForm.admin_first.value == "") SetupForm.submit_button.disabled = true;
	else if (SetupForm.admin_first.value.match(/^[A-Za-z\.\s\-\_]+$/) == null) {
		alert("First name is required and is not in the correct format. Can only have letters, _, -, periods and spaces.");
		SetupForm.submit_button.disabled = true;
		return;
		}

	// Last Name ----------------------
	if (SetupForm.admin_last.value == "") SetupForm.submit_button.disabled = true;
	else if (SetupForm.admin_last.value.match(/^[A-Za-z\s\.\-\_]+$/) == null) {
		alert("Last name is required and is not in the correct format. Can only have letters, _, -, periods and spaces.");
		SetupForm.submit_button.disabled = true;
		return;
		}

	// Phone ----------------------
	if (SetupForm.admin_phone.value == "") SetupForm.submit_button.disabled = true;
	else if ((phnum = SetupForm.admin_phone.value.trim().replace(/-/g,"")) != "") {
		patt = /[^0-9]/;
		if (patt.test(phnum)) {
			alert("The phone number may contain only digits and dashes");
			SetupForm.submit_button.disabled = true;
			return(2);
		}
		toll = (phnum.charAt(0) == "1") ? 1:0;
		if ((phnum.length != 10 + toll) & (phnum.length != 7 + toll)) {
			alert("Please enter the phone number as a 7 or 10-digit number with an optional \"1\" preceding.");
			return(3);
		}
		if (phnum.length == 7 + toll) {
			SetupForm.admin_phone.value = ((toll == 1) ? "1-":"") + phnum.substr(0 + toll,3) + "-" + phnum.substr(3 + toll);
		}
		if (phnum.length == 10 + toll) {
			SetupForm.admin_phone.value = ((toll == 1) ? "1-":"") + phnum.substr(0 + toll,3) + "-" + phnum.substr(3 + toll,3) + "-" + phnum.substr(6 + toll);
		}
	}

	// Email ----------------------
	if (SetupForm.admin_email.value == "") SetupForm.submit_button.disabled = true;
	else if (SetupForm.admin_email.value.match(/^[\w\.-_]+\@[\w\.-_]+\.[\w\.-_]+$/) == null) {
		alert("Email is required and is not in the correct format. Must be of the form a@b.c where a, b, and c can have letters, numbers, -, _, or periods.");
		SetupForm.submit_button.disabled = true;
		return;
	}

	// Home ----------------------
	if (SetupForm.admin_sitename.value == "") SetupForm.submit_button.disabled = true;
	else if (SetupForm.admin_sitename.value.match(/^[A-Za-z\s\d\.\-\_]+$/) == null) {
	if (SetupForm.admin_sitename.value == "") SetupForm.submit_button.disabled = true;
		alert("Administrator home site is required and is not in the correct format. Can only have letters, numbers, _, -, periods and spaces.");
		SetupForm.submit_button.disabled = true;
	}

	SetupForm.submit_button.style.backgroundColor = (SetupForm.submit_button.disabled) ? "hotpink":"lightgreen";
}

</script>

</head>

<!-- =================================================== WEB PAGE BODY ============================================ -->
<!-- =================================================== WEB PAGE BODY ============================================ -->
<!-- =================================================== WEB PAGE BODY ============================================ -->

<body>
<div id="Main">
	<div class="page_header">
		<h1>Tax-Aide Appointments</h1>
		Database setup
	</div>

<hr />

<div id="SetupDiv">
<h1>To set up your database, you will need to do 3 things:</h1>
<ol>
<li><b>Create a database</b>
	<br />This is done using the hosting site&apos;s tools and may differ depending on the host. All you need to set up is the database. This process will create the tables that the database uses. The host may be called &quot;localhost&quot; or may be an IP address.</li>
<li><b>Create a database user id and password</b>
	<br />This is done using the hosting site&apos;s tools and may differ depending on the host. The user name and password is used by the software to gain access to the database and is not related to you as the software user. This user must have full access permissions to the database. You and your schedulers will have their own login credentials to access the software.</li>
<li><b>Provide the information about you as primary administrator</b>
	<br />You must have at least one administrator. Site names should always begin with the city. If you aren&apos;t associated with a site, you can use a dummy name, like &quot;District&quot; or &quot;State&quot;. Your initial password will be &quot;admin&quot;.</li>
</ol>
<center>

<?php
global $initialize;
global $errormessage;
if ($initialize == "fail") {
	echo "<div id=\"setup_dbfail\" />\n";
	echo "\tNot able to access your database.\n";
	echo "\t<br />Please check your parameters and try again.\n";
	echo "<br /><br />$errormessage";
	echo "</div>\n";
}
?>

<form id="SetupForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
	<table id="SetupTable" onchange="Check_Inputs();">

		<tr><td colspan="3"><h1>Information about your database:</h1></td></tr>
		<tr><td>Host:</td>
			<td><input type="text" id="host" name="host"
				value="<?php global $host; echo $host;?>" /></td>
			<td>(Could be an IP address if other than &quot;localhost&quot;)</td></tr>
		<tr><td>Database Name:</td>
			<td><input type="text" id="dbname" name="dbname"
				value="<?php global $dbname; echo $dbname;?>" /></td></tr>
		<tr><td>Username:</td>
			<td><input type="text" id="dbuserid" name="dbuserid"
				value="<?php global $dbuserid; echo $dbuserid;?>" /></td></tr>
		<tr><td>Password:</td>
			<td><input type="text" id="dbpassword" name="dbpassword"
				value="<?php global $dbpassword; echo $dbpassword;?>" /></td></tr>
		<tr><td>Time Zone:</td>
			<td colspan="2" onclick="tzchecked=true;">
				<input type="radio" id="tzeastern" name="dbtimezone"
					<?php global $dbtimezone; if ($dbtimezone == "America/NewYork") echo " checked ";?>
					value="America/New_York"    />&nbsp;Eastern&nbsp;&nbsp;
				<input type="radio" id="tzcentral" name="dbtimezone"
					<?php global $dbtimezone; if ($dbtimezone == "America/Chicago") echo " checked ";?>
					value="America/Chicago"     />&nbsp;Central&nbsp;&nbsp;
				<input type="radio" id="tzmountain" name="dbtimezone"
					<?php global $dbtimezone; if ($dbtimezone == "America/Denver") echo " checked ";?>
					value="America/Denver"      />&nbsp;Mountain&nbsp;&nbsp;
				<input type="radio" id="tzmountainnoDST" name="dbtimezone"
					<?php global $dbtimezone; if ($dbtimezone == "America/Phoenix") echo " checked ";?>
					value="America/Phoenix"      />&nbsp;Mountain no DST&nbsp;&nbsp;
			<br />	<input type="radio" id="tzpacific" name="dbtimezone"
					<?php global $dbtimezone; if ($dbtimezone == "America/Los_Angeles") echo " checked ";?>
					value="America/Los_Angeles" />&nbsp;Pacific&nbsp;&nbsp;
				<input type="radio" id="tzalaska" name="dbtimezone"
					<?php global $dbtimezone; if ($dbtimezone == "America/Anchorage") echo " checked ";?>
					value="America/Anchorage"   />&nbsp;Alaska
				<input type="radio" id="tzhawaii" name="dbtimezone"
					<?php global $dbtimezone; if ($dbtimezone == "America/Adak") echo " checked ";?>
					value="America/Adak"        />&nbsp;Hawaii&nbsp;&nbsp;
				<input type="radio" id="tzhawaiinoDST" name="dbtimezone"
					<?php global $dbtimezone; if ($dbtimezone == "America/Honolulu") echo " checked ";?>
					value="America/Honolulu"    />&nbsp;Hawaii no DST&nbsp;&nbsp;
				</td></tr>

		<tr><td colspan="3"><h1>Information about you:</h1></td></tr>
		<tr><td>First Name:</td>
			<td><input type="text" id="admin_first" name="admin_first"
				value="<?php global $admin_first; echo $admin_first;?>" /></td></tr>
		<tr><td>Last Name:</td>
			<td><input type="text" id="admin_last" name="admin_last"
				value="<?php global $admin_last; echo $admin_last;?>" /></td></tr>
		<tr><td>Email:</td>
			<td><input type="email" id="admin_email" name="admin_email"
				value="<?php global $admin_email; echo $admin_email;?>" /></td></tr>
		<tr><td>Phone:</td>
			<td><input type="tel" id="admin_phone" name="admin_phone"
				value="<?php global $admin_phone; echo $admin_phone;?>" /></td>
			<td>(7 or 10 digits with optional leading 1)</td></tr>
		<tr><td>Your Home Site Name:</td>
			<td colspan="2"><input type="text" id="admin_sitename" name="admin_sitename"
				value="<?php global $admin_sitename; echo $admin_sitename;?>" /></td></tr>
		<tr><td>Your public website URL:</td>
			<td colspan="2"><input type="url" id="systemURL" name="systemURL"
				value="<?php global $systemURL; echo $systemURL;?>" /> (Optional)</td></tr>
	</table>
	<br /><input id="submit_button" name="submit_button" type="submit" value="Configure the database" />
</form>
</center>
</div> <!-- SetupDiv -->

</div> <!-- Main -->
</body>
</html>
