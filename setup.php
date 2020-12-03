<?php
// This routine:
// - makes sure that the database has the correct current set of tables and table columns
// - creates the opendb.php file so that all php routines have access to the database

//--------------------------------- VERSION HISTORY -----------------------------------
$VERSION = "6.01";
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
$errormessage = "";

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
	$_SESSION["SystemInfo"] = $row['system_info'];
	$_SESSION["SystemURL"] = $systemURL = $row['system_url'];
	$_SESSION["TRACE"] = $row['system_trace'];
	// New in version 5.00
	$system_email = (isset($row['system_email'])) ? $row['system_email'] : "" ;
	if ($system_email == "") $system_email = "no_reply@tax_aide_reservations.no_email";
	$_SESSION["SystemEmail"] = $system_email;
	$_SESSION["SystemReminders"] = (isset($row['system_reminders'])) ? $row['system_reminders'] : "" ;
	if ($_SESSION["SystemVersion"] != $VERSION) {
		Configure_Database();
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

global $SYSTEM_TABLE;
Configure_Table($SYSTEM_TABLE, "system_index", "INT AUTO_INCREMENT PRIMARY KEY");
Configure_Column($SYSTEM_TABLE, "system_version", "VARCHAR(50)");
Configure_Column($SYSTEM_TABLE, "system_greeting", "TEXT");
Configure_Column($SYSTEM_TABLE, "system_notice", "TEXT");
Configure_Column($SYSTEM_TABLE, "system_info", "TEXT");
Configure_Column($SYSTEM_TABLE, "system_url", "TEXT");
Configure_Column($SYSTEM_TABLE, "system_email", "TEXT");
Configure_Column($SYSTEM_TABLE, "system_reminders", "TEXT");
Configure_Column($SYSTEM_TABLE, "system_trace", "VARCHAR(1)");

global $ACCESS_TABLE;
Configure_Table($ACCESS_TABLE, "acc_index", "BIGINT AUTO_INCREMENT PRIMARY KEY");
Configure_Column($ACCESS_TABLE, "acc_location", "BIGINT UNSIGNED");
Configure_Column($ACCESS_TABLE, "acc_user", "BIGINT UNSIGNED");
Configure_Column($ACCESS_TABLE, "acc_owner", "BIGINT UNSIGNED");
Configure_Column($ACCESS_TABLE, "acc_option", "TINYTEXT");

global $USER_TABLE;
Configure_Table($USER_TABLE, "user_index", "BIGINT AUTO_INCREMENT PRIMARY KEY");
Configure_Column($USER_TABLE, "user_name", "VARCHAR(50)");
Configure_Column($USER_TABLE, "user_email", "VARCHAR(256)");
Configure_Column($USER_TABLE, "user_phone", "VARCHAR(25)");
Configure_Column($USER_TABLE, "user_pass", "VARCHAR(100)");
Configure_Column($USER_TABLE, "user_last", "VARCHAR(50)");
Configure_Column($USER_TABLE, "user_first", "VARCHAR(50)");
Configure_Column($USER_TABLE, "user_home", "BIGINT");
Configure_Column($USER_TABLE, "user_appt_site", "BIGINT");
Configure_Column($USER_TABLE, "user_options", "TEXT");
Configure_Column($USER_TABLE, "user_sitelist", "TEXT");
Configure_Column($USER_TABLE, "user_lastlogin", "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");

global $SITE_TABLE;
Configure_Table($SITE_TABLE, "site_index", "BIGINT AUTO_INCREMENT PRIMARY KEY");
Configure_Column($SITE_TABLE, "site_name", "TEXT");
Configure_Column($SITE_TABLE, "site_address", "TEXT");
Configure_Column($SITE_TABLE, "site_inet", "TEXT");
Configure_Column($SITE_TABLE, "site_contact", "TEXT");
Configure_Column($SITE_TABLE, "site_message", "TEXT");
Configure_Column($SITE_TABLE, "site_addedby", "BIGINT");
Configure_Column($SITE_TABLE, "site_open", "DATE");
Configure_Column($SITE_TABLE, "site_closed", "DATE");
Configure_Column($SITE_TABLE, "site_instructions", "TEXT");
Configure_Column($SITE_TABLE, "site_reminder", "TEXT");
Configure_Column($SITE_TABLE, "site_lastrem", "TEXT");
Configure_Column($SITE_TABLE, "site_help", "TEXT");
Configure_Column($SITE_TABLE, "site_sumres", "TEXT");
Configure_Column($SITE_TABLE, "site_10dig", "TEXT");

global $APPT_TABLE;
Configure_Table($APPT_TABLE, "appt_no", "BIGINT AUTO_INCREMENT PRIMARY KEY");
Configure_Column($APPT_TABLE, "appt_date", "DATE");
Configure_Column($APPT_TABLE, "appt_time", "TIME");
Configure_Column($APPT_TABLE, "appt_name", "TEXT");
Configure_Column($APPT_TABLE, "appt_location", "BIGINT");
Configure_Column($APPT_TABLE, "appt_email", "VARCHAR(256)");
Configure_Column($APPT_TABLE, "appt_emailsent", "DATE");
Configure_Column($APPT_TABLE, "appt_phone", "VARCHAR(50)");
Configure_Column($APPT_TABLE, "appt_tags", "TEXT");
Configure_Column($APPT_TABLE, "appt_need", "TEXT");
Configure_Column($APPT_TABLE, "appt_info", "TEXT");
Configure_Column($APPT_TABLE, "appt_status", "TEXT");
Configure_Column($APPT_TABLE, "appt_wait", "BIGINT");
Configure_Column($APPT_TABLE, "appt_change", "DATETIME");
Configure_Column($APPT_TABLE, "appt_by", "TEXT");
Configure_Column($APPT_TABLE, "appt_type", "VARCHAR(1)");

global $SCHED_TABLE;
Configure_Table($SCHED_TABLE, "sched_index", "INT AUTO_INCREMENT PRIMARY KEY");
Configure_Column($SCHED_TABLE, "sched_location", "BIGINT");
Configure_Column($SCHED_TABLE, "sched_name", "TEXT");
Configure_Column($SCHED_TABLE, "sched_pattern", "TEXT");
Update_Version();
}

//----------------------------------------------------------------------------
function Configure_Table($tablename, $indexname, $optionlist) {
//----------------------------------------------------------------------------
	global $dbname;
	global $DEBUG;
	global $dbcon;

	// add the table if it doesn't exist
	$query = "CREATE TABLE `$tablename`"; 
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
	$query = "SELECT `$columnname` FROM `$tablename`";
	$query .= " WHERE 1";
	mysqli_query($dbcon, $query);
	$action = (mysqli_error($dbcon) != "") ? "ADD" : "MODIFY";

	// either add or modify the column's characteristics
	$query = "ALTER TABLE `$tablename`";
	$query .= " $action COLUMN `$columnname` $optionlist";
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
	$sysNotice = Notice();

	$query = "INSERT INTO $SYSTEM_TABLE";
	$query .= " SET `system_index` = 1";
	$query .= ", `system_version` = '$VERSION'";
	$query .= ", `system_email` = '$system_email'";
	$query .= ", `system_notice` = '$sysNotice'";
	$query .= ", `system_url` = '$systemURL'";
	$query .= " ON DUPLICATE KEY";
	$query .= " UPDATE `system_version` = '$VERSION'";
	mysqli_query($dbcon, $query);
}

//----------------------------------------------------------------------------
function Notice() {
//----------------------------------------------------------------------------
	$sysNotice = (isset($_SESSION["SystemNotice"])) ? $_SESSION["SystemNotice"] : "" ;

	if ($sysNotice = "") {
		$sysNotice = "AARP reservations are now closed for this year.\n";
		$sysNotice .= "<br />";
		$sysNotice .= "Please come back when we re-open next January.\n";
	}
	return $sysNotice;
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
	fwrite($fileptr, "if (mysqli_connect_errno(" . $ds . "dbcon)) DIE (\"Unable to connect to database! (\" . mysqli_connect_error() . \")\");\n");
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

	$query = "INSERT INTO $SITE_TABLE SET";
	$query .= "  `site_index` = 1";
	$query .= ", `site_name` = 'Unassigned'";
	$query .= ", `site_address` = '||||||'";
	$query .= ", `site_inet` = ''";
	$query .= ", `site_contact` = ''";
	$query .= ", `site_message` = ''";
	$query .= ", `site_addedby` = 0";
	$query .= ", `site_open` = '0000-00-00'";
	$query .= ", `site_closed` = '0000-00-00'";
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
	$query .= ", `site_open` = '0000-00-00'";
	$query .= ", `site_closed` = '0000-00-00'";
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
