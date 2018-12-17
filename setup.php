<?php
//session_start();
// --------------------------- VERSION HISTORY ----------------------------------
$VERSION = "4.02";
$_SESSION['SystemVersion'] = $VERSION;
$DEBUG = false;

// This routine is the first to run when a new installation is encountered.
// It is run by the index.php routine when the opendb.php file is not present.
// This routine:
// 1. creates the opendb.php file so that other php routines have access to the database
//    by including it first in the respective routine.
// 2. makes sure that the database has the correct current set of tables and table columns
// 3. after the above, opens the sitemanage.php routine so that the Administrator
//    can further set up their sites and schedulers.

//--------------------------------- GLOBAL VARIABLES -----------------------------------
$initialize = "none";
$opendb_filename = "opendb.php";
if ($DEBUG) $opendb_filename = "opendb_test.php";

$USER_TABLE = "taxappt_users";
$ACCESS_TABLE = "taxappt_access";
$APPT_TABLE = "taxappt_appts";
$SITE_TABLE = "taxappt_sites";
$SYSTEM_TABLE = "taxappt_system";

$admin_first = "";
$admin_last = "";
$admin_email = "";
$admin_phone = "";
$admin_sitename = "";
$host = "localhost";
$dbname = "";
$dbuserid = "";
$dbpassword = "";
$systemURL = "";
$errormessage = "";

if ($DEBUG) echo "checking for $opendb_filename<br />";
if (is_file($opendb_filename)) include $opendb_filename;

if ($dbname) {
	if ($DEBUG) echo "$opendb_filename was found with dbname = $dbname.<br />";

	// Confirm that tables and table columns match the current specifications
	Configure_Database();

	// exit this routine and go to site management
	if ($DEBUG) exit("EXITED DUE TO DEBUGGING!");
	return;
}
else {
	if ($DEBUG) echo "$opendb_filename doesn&apos;t exist - create it.<br />";

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
		if ($DEBUG) echo "Connecting to $dbname on $host using $dbuserid:$dbpassword</br>";
		$dbcon = mysqli_connect($host, $dbuserid, $dbpassword, $dbname);

		if (mysqli_connect_errno()) {
			$initialize = "fail";
			$errormessage = mysqli_connect_error();
		}
		else {
			if ($DEBUG) echo "Success<br />";
			$initialize = "pass";
			mysqli_select_db($dbcon, $dbname);
			Create_Opendb_FIle($host, $dbuserid, $dbpassword, $dbname);
			if ($DEBUG) echo "CONFIGURING $dbname<br />";
			Configure_Database(); // add the tables
			if ($DEBUG) echo "ADDING ADMIN $admin_first $admin_last<br />";
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

			// Add done, go to the management routine
			header('Location: sitemanage.php');
		}
	}
} // end of main routine

//----------------------------------------------------------------------------
function Configure_Database() {
// This function describes the database structure and calls other functions
// to add tables and columns as needed.
//----------------------------------------------------------------------------
global $USER_TABLE, $ACCESS_TABLE, $APPT_TABLE, $SITE_TABLE, $SYSTEM_TABLE;

Configure_Table($SYSTEM_TABLE, "system_index", "INT AUTO_INCREMENT PRIMARY KEY NOT NULL");
Configure_Column($SYSTEM_TABLE, "system_version", "VARCHAR(50)");
Configure_Column($SYSTEM_TABLE, "system_greeting", "TEXT");
Configure_Column($SYSTEM_TABLE, "system_notice", "TEXT");
Configure_Column($SYSTEM_TABLE, "system_info", "TEXT");
Configure_Column($SYSTEM_TABLE, "system_url", "TEXT");
Configure_Column($SYSTEM_TABLE, "system_trace", "VARCHAR(1)");
Configure_Table($ACCESS_TABLE, "acc_index", "BIGINT AUTO_INCREMENT PRIMARY KEY NOT NULL");
Configure_Column($ACCESS_TABLE, "acc_location", "BIGINT UNSIGNED");
Configure_Column($ACCESS_TABLE, "acc_user", "BIGINT UNSIGNED");
Configure_Column($ACCESS_TABLE, "acc_owner", "BIGINT UNSIGNED");
Configure_Column($ACCESS_TABLE, "acc_option", "TINYTEXT");
Configure_Table($USER_TABLE, "user_index", "BIGINT AUTO_INCREMENT PRIMARY KEY NOT NULL");
Configure_Column($USER_TABLE, "user_name", "VARCHAR(50)");
Configure_Column($USER_TABLE, "user_email", "VARCHAR(256)");
Configure_Column($USER_TABLE, "user_phone", "VARCHAR(20)");
Configure_Column($USER_TABLE, "user_pass", "VARCHAR(100)");
Configure_Column($USER_TABLE, "user_last", "VARCHAR(50)");
Configure_Column($USER_TABLE, "user_first", "VARCHAR(50)");
Configure_Column($USER_TABLE, "user_home", "BIGINT");
Configure_Column($USER_TABLE, "user_appt_site", "BIGINT");
Configure_Column($USER_TABLE, "user_options", "TEXT");
Configure_Column($USER_TABLE, "user_sitelist", "TEXT");
Configure_Column($USER_TABLE, "user_lastlogin", "TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP");
Configure_Table($SITE_TABLE, "site_index", "BIGINT AUTO_INCREMENT PRIMARY KEY NOT NULL");
Configure_Column($SITE_TABLE, "site_name", "TEXT");
Configure_Column($SITE_TABLE, "site_address", "TEXT");
Configure_Column($SITE_TABLE, "site_inet", "VARCHAR(1)");
Configure_Column($SITE_TABLE, "site_contact", "TEXT");
Configure_Column($SITE_TABLE, "site_message", "TEXT");
Configure_Column($SITE_TABLE, "site_addedby", "BIGINT");
Configure_Column($SITE_TABLE, "site_open", "DATE");
Configure_Column($SITE_TABLE, "site_closed", "DATE");
Configure_Column($SITE_TABLE, "site_schedule", "TEXT");
Configure_Column($SITE_TABLE, "site_help", "TEXT");
Configure_Column($SITE_TABLE, "site_sumres", "TEXT");
Configure_Table($APPT_TABLE, "appt_no", "BIGINT AUTO_INCREMENT PRIMARY KEY NOT NULL");
Configure_Column($APPT_TABLE, "appt_date", "DATE DEFAULT '0000-00-00' NOT NULL");
Configure_Column($APPT_TABLE, "appt_time", "TIME");
Configure_Column($APPT_TABLE, "appt_name", "TEXT");
Configure_Column($APPT_TABLE, "appt_location", "BIGINT");
Configure_Column($APPT_TABLE, "appt_email", "VARCHAR(256)");
Configure_Column($APPT_TABLE, "appt_phone", "VARCHAR(50)");
Configure_Column($APPT_TABLE, "appt_need", "TEXT");
Configure_Column($APPT_TABLE, "appt_status", "TEXT");
Configure_Column($APPT_TABLE, "appt_tracking", "TEXT");
Configure_Column($APPT_TABLE, "appt_wait", "BIGINT");
Configure_Column($APPT_TABLE, "appt_change", "DATETIME");
Configure_Column($APPT_TABLE, "appt_by", "TEXT");
Configure_Column($APPT_TABLE, "appt_type", "VARCHAR(1)");
Update_Version();
}

//----------------------------------------------------------------------------
function Configure_Table($tablename, $indexname, $optionlist) {
//----------------------------------------------------------------------------
	global $dbname;
	global $DEBUG;
	global $dbcon;

	// add the table if it doesn't exist
	if ($DEBUG) echo "<br />TBL: $dbname.$tablename, $indexname, $optionlist <br />";
	$query = "CREATE TABLE `$tablename`"; 
	$query .= " (`$indexname` $optionlist)";
	if ($DEBUG) echo "QRY: $query<br />";
	mysqli_query($dbcon, $query);
	if ($DEBUG) echo "RES: " . mysqli_error($dbcon) . "<br />";
}

//----------------------------------------------------------------------------
function Configure_Column($tablename, $columnname, $optionlist) {
//----------------------------------------------------------------------------
	global $dbname;
	global $DEBUG;
	global $dbcon;

	// determine if the column exists
	if ($DEBUG) echo "&nbsp;&nbsp;&nbsp;COL: $tablename, $columnname, $optionlist <br />";
	$query = "SELECT `$columnname` FROM `$tablename`";
	$query .= " WHERE 1";
	if ($DEBUG) echo "&nbsp;&nbsp;&nbsp;QRY: $query<br />";
	mysqli_query($dbcon, $query);
	if ($DEBUG) echo "&nbsp;&nbsp;&nbsp;RES: " . mysqli_error($dbcon) . "<br />";
	$action = (mysqli_error($dbcon) != "") ? "ADD":"MODIFY";

	// either add or modify the column's characteristics
	$query = "ALTER TABLE `$tablename`";
	$query .= " $action COLUMN `$columnname` $optionlist";
	if ($DEBUG) echo "&nbsp;&nbsp;&nbsp;QRY: $query<br />";
	mysqli_query($dbcon, $query);
	if ($DEBUG) echo "&nbsp;&nbsp;&nbsp;RES: " . mysqli_error($dbcon) . "<br />";
	if ($DEBUG) echo "&nbsp;&nbsp;&nbsp;---<br />";
}

//----------------------------------------------------------------------------
function Update_Version() {
//----------------------------------------------------------------------------
	global $DEBUG;
	global $SYSTEM_TABLE;
	global $VERSION;
	global $systemURL;
	global $dbcon;

	$query = "INSERT INTO $SYSTEM_TABLE";
	$query .= " SET `system_index` = 1";
	$query .= ", `system_version` = '$VERSION'";
	$notice = Notice($systemURL);
	$query .= ", `system_notice` = '$notice'";
	$query .= ", `system_url` = '$systemURL'";
	$query .= " ON DUPLICATE KEY";
	$query .= " UPDATE `system_version` = '$VERSION'";
	if ($DEBUG) echo "<br />QRY: $query<br />";
	mysqli_query($dbcon, $query);
	if ($DEBUG) echo "RES: " . mysqli_error($dbcon) . "<br />";
}

//----------------------------------------------------------------------------
function Notice($systemURL) {
//----------------------------------------------------------------------------
	$notice = "AARP reservations are now closed for this year.\n";
	$notice .= "<br />";
	$notice .= "Please come back when we re-open next January.\n";
	return ($notice);
}

//----------------------------------------------------------------------------
function Create_Opendb_File($host, $dbuserid, $dbpassword, $dbname) {
//----------------------------------------------------------------------------
	global $opendb_filename;
	global $dbtimezone;
	global $VERSION;
	global $DEBUG;
	$ds = "$";

	// Open the file for writing
	if ($DEBUG) echo "Creating $opendb_filename<br />";
	$fileptr = fopen($opendb_filename, "w");
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
	fwrite($fileptr, "require \"session.php\";\n");
	fwrite($fileptr, "?>\n");
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

	if ($DEBUG) echo "INPUT: $admin_sitename, $admin_first, $admin_last, $admin_email, $admin_phone<br />";

	$query = "INSERT INTO $SITE_TABLE";
	$query .= " SET `site_index` = 1";
	$query .= ", `site_name` = 'Unassigned'";
	$query .= ", `site_address` = '||||||'";
	$query .= ", `site_inet` = ''";
	$query .= ", `site_contact` = ''";
	$query .= ", `site_message` = ''";
	$query .= ", `site_addedby` = 0";
	$query .= ", `site_open` = '0000-00-00'";
	$query .= ", `site_closed` = '0000-00-00'";
	$query .= ", `site_schedule` = ''";
	$query .= ", `site_help` = ''";
	$query .= ", `site_sumres` = ''";
	if ($DEBUG) echo "<br />QRY: $query<br />";
	mysqli_query($dbcon, $query);
	if ($DEBUG) echo "RES: " . mysqli_error($dbcon) . "<br />";

	$query = "INSERT INTO $SITE_TABLE";
	$query .= " SET `site_index` = 2";
	$query .= ", `site_name` = '$admin_sitename'";
	$query .= ", `site_address` = '||||||'";
	$query .= ", `site_inet` = ''";
	$query .= ", `site_contact` = ''";
	$query .= ", `site_message` = ''";
	$query .= ", `site_addedby` = 0";
	$query .= ", `site_open` = '0000-00-00'";
	$query .= ", `site_closed` = '0000-00-00'";
	$query .= ", `site_schedule` = ''";
	$query .= ", `site_help` = ''";
	$query .= ", `site_sumres` = ''";
	if ($DEBUG) echo "<br />QRY: $query<br />";
	mysqli_query($dbcon, $query);
	if ($DEBUG) echo "RES: " . mysqli_error($dbcon) . "<br />";

	$query = "INSERT INTO $USER_TABLE";
	$query .= " SET `user_index` = 1";
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
	if ($DEBUG) echo "<br />QRY: $query<br />";
	mysqli_query($dbcon, $query);
	if ($DEBUG) echo "RES: " . mysqli_error($dbcon) . "<br />";
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
	<br />This is done using the hosting site&apos;s tools and may differ depending on the host. This is used for the software to gain access to the database. The user must have full access permissions to the database. You and your schedulers will have their own login credentials to access the software.</li>
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

