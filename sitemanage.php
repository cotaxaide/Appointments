<?php
//ini_set('display_errors', '1');

// ---------------------------- VERSION HISTORY -------------------------------
// File Version 9.08
// 	Fixed changing scheduler data sets password to all asterisks
// File Version 9.07
// 	Modification of attachments for individual shortcodes
// File Version 9.02
// 	Added administration for heartbeat interval
// File Version 9.0
// 	Fixed %xx chars in Appt Manager listing
// 	Passwords hidden with ******** but can still be changed
// 	Added option to disallow internet user to add themselves to the CB list
// File Version 8.0
//	Added ClearTrace (error log) function
//	Added ability to modify default system confirmation/reminder message
//	Added attachment list for the [ATTACHMENTS] shortcode
//	Removed default notice
// File Version 7.0
// 	Added sticky table headers
// 	Moved character coding to common routines
// 	Allow shortcodes in Internet instructions
// File Version 6.01a
// 	Viewing email message had a "from undefined" after the message
// File Version 6.01
// 	Added textarea for taxpayer self-registering instruction for the site
// 	Restructured site options into an array
// 	On Site tab, moved site name and tabs to header so always visible
// File Version 5.02a
// 	Apostrophe in site message would not save
// 	Added a test to assure non-A/Ms could not access
// File Version 5.02
// 	Added a link to view the trace file
// File Version 5.01b
//	Added indication that a cron job has not yet run
//	Prevent changing sites after a search
// File Version 5.01a
//	Error message to change to administrator wrongly appears
//	Added setting system greeting if not already set
// File Version 5.00

// Set up environment
require "environment.php";

// If the UserIndex has not been set as a session variable, the user needs to sign in
if (! isset($_SESSION["User"])) {
	header('Location: index.php'); // prevents direct access to this page (must sign in first).
	exit;
}

// Global variables
$isAdministrator = ($_SESSION["User"]["user_options"] == "A");
$isAppointmentManager = ($_SESSION["User"]["user_options"] == "M");
$DEBUG = "" . $_SESSION["DEBUG"];
$DEBUG = true;
$Errormessage = "";
$TodayDate = Date("Y-m-d");
$LocationList[0] = 0;
$Usermessage = "";
$SiteAction = "Access";
$Site1Name = "Access";
$SiteContact = "";
$SiteSumres = "";
$Site10dig = "";
$SiteClosed = "";
$SiteOpen = "";
$SiteUserHome = "";
$AttachContent = "";
$ThisSiteOption = "";
$ThisAddress = ["","","","","","",""];
$UserPreferred = "";
$SiteView = "Site";
$Administrators = "";
$AppointmentManagers = 0;
$Alert = "";
$Dagger = chr(134);
$AFlag = "&#x26EF;";
$MFlag = "&#x2605;";
$SFlag = "&#x2606;";
$VFlag = "&#x26AF;";
$Phone_icon = "&#x260F;";
$Name_icon = "<i class='material-icons' style='font-size:1em;'>people</i>"; //"&#x263A;";
$Email_icon = "&#x2709;";
$checkboxNo = "";
$checkboxYes = "&#x2714;";

// Get relevant info about the current user
$SiteCurrent = $SiteUserHome = $_SESSION["User"]["user_home"];
$UserPreferred = $_SESSION["User"]["user_index"];
$ThisIndex = $UserUser = $_SESSION["User"]["user_index"];
$ThisFirst = $UserFirst = $_SESSION["User"]["user_first"];
$ThisLast = $UserLast = $_SESSION["User"]["user_last"];
$ThisHome = $UserHome = $_SESSION["User"]["user_home"];
$ThisName = $_SESSION["User"]["user_name"];
$ThisPhone = $_SESSION["User"]["user_phone"];
$ThisEmail = $_SESSION["User"]["user_email"];
$ThisUserOptions = $_SESSION["User"]["user_options"];
$SystemGreeting = $_SESSION["SystemGreeting"];
$SystemNotice = $_SESSION["SystemNotice"];
$SystemConfirm = $_SESSION["SystemConfirm"];
$SystemAttach = $_SESSION["SystemAttach"];
$SystemURL = $_SESSION["SystemURL"];
$SystemEmail = $_SESSION["SystemEmail"] ?? "no.reply@tax.aide.reservations.no.email";
$SystemHeartbeat = $_SESSION["SystemHeartbeat"] ?? 0;
if (! isset($_SESSION["UserSort"])) $_SESSION["UserSort"] = "user_last";
// Check user permission
if ((! $isAdministrator) AND (! $isAppointmentManager)) {
	header('Location: index.php'); // prevents direct access to this page (must sign in first).
	if ($_SESSION["TRACE"]) error_log("MANAGE: $ThisName, $UserFirst $UserLast lacks access permission");
	exit;
}

// Create a default system confirmation message
$ConfirmMessage  = "Welcome, [TPNAME]:";
$ConfirmMessage .= "\n\n";
$ConfirmMessage .= "This is to confirm your appointment with AARP Tax-Aide to assist in preparing your tax return.";
$ConfirmMessage .= "\n\n";
$ConfirmMessage .= "You are scheduled for [TIME] on [DATE]\n";
$ConfirmMessage .= "at the [SITENAME]\n";
$ConfirmMessage .= "[ADDRESS]\n";
$ConfirmMessage .= "[CITY], [STATE] [ZIP]";
$ConfirmMessage .= "\n\n";
$ConfirmMessage .= "You can find additional information and a handy checklist at our web site at [STATESITE]";
$ConfirmMessage .= "\n\n";
$ConfirmMessage .= "If you find that you no longer need this appointment, please contact us at [PHONE]";
$ConfirmMessage .= " so that we can use this time for another person who does need our service.";
$ConfirmMessage .= "\n\n";
$ConfirmMessage .= "We look forward to seeing you on [DATE]";
$ConfirmMessage .= "\n\n";
$ConfirmMessage .= "Your AARP Tax-Aide friends at the [SITENAME].";
if ($SystemConfirm == "") {
	$SystemConfirm = $ConfirmMessage;
	$_SESSION["SystemConfirm"] = _Clean_Chars($SystemConfirm);
}

// Get POST variables if changes were submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
	if (isset($_POST["SiteCurrent"])) { // Initial entry is also a POST from this page
		$SiteCurrent =  htmlspecialchars(stripslashes(trim($_POST["SiteCurrent"])));
		$SiteView =     htmlspecialchars(stripslashes(trim($_POST["SiteView"])));
		$SiteAction =   htmlspecialchars(stripslashes(trim($_POST["SiteAction"])));
		$Site1Name =    htmlspecialchars(stripslashes(trim($_POST["Site1Name"])));
		$Site1Address = htmlspecialchars(stripslashes(trim($_POST["Site1Address"])));
		$SiteContact =  htmlspecialchars(stripslashes(trim($_POST["SiteContact"])));
		$SiteSumres =   htmlspecialchars(stripslashes(trim($_POST["SiteSumres"])));
		$Site10dig =    htmlspecialchars(stripslashes(trim($_POST["Site10dig"])));
		$SiteOptions =  htmlspecialchars(stripslashes(trim($_POST["SiteOptions"])));
		$SiteOpen =     htmlspecialchars(stripslashes(trim($_POST["SiteOpen"])));
		$SiteClosed =   htmlspecialchars(stripslashes(trim($_POST["SiteClosed"])));
		$SiteMessage =  htmlspecialchars(stripslashes(trim($_POST["SiteMessage"])));
		$SiteReminder = htmlspecialchars(stripslashes(trim($_POST["SiteReminder"])));
		$SiteInstructions =                           trim($_POST["SiteInstructions"]);
		$SiteAttach =                                 trim($_POST["SiteAttach"]);
		$SiteLastRem =  htmlspecialchars(stripslashes(trim($_POST["SiteLastRem"])));
		$UserCurrent =  htmlspecialchars(stripslashes(trim($_POST["UserCurrent"])));
		$UserFirst =    htmlspecialchars(stripslashes(trim($_POST["UserFirst"])));
		$UserLast =     htmlspecialchars(stripslashes(trim($_POST["UserLast"])));
		$UserName =     htmlspecialchars(stripslashes(trim($_POST["UserName"])));
		$UserHome =     htmlspecialchars(stripslashes(trim($_POST["UserHome"])));
		$UserAppt =     htmlspecialchars(stripslashes(trim($_POST["UserAppt"])));
		$UserEmail =    htmlspecialchars(stripslashes(trim($_POST["UserEmail"])));
		$UserPhone =    htmlspecialchars(stripslashes(trim($_POST["UserPhone"])));
		$UserPass =     			     (trim($_POST["UserPass"]));
		$UserOptions =  htmlspecialchars(stripslashes(trim($_POST["UserOptions"])));
		$UserSort =     htmlspecialchars(stripslashes(trim($_POST["UserSort"])));
		$SystemGreeting =                             trim($_POST["SystemGreeting"]);
		$SystemNotice =                               trim($_POST["SystemNotice"]);
		$SystemConfirm =                              trim($_POST["SystemConfirm"]);
		$SystemAttach =                               trim($_POST["SystemAttach"]);
		$SystemURL =    htmlspecialchars(stripslashes(trim($_POST["SystemURL"])));
		$SystemEmail =  htmlspecialchars(stripslashes(trim($_POST["SystemEmail"])));
		$SystemHeartbeat =  htmlspecialchars(stripslashes(trim($_POST["SystemHeartbeat"])));
	}

	$UserFullName = "$UserFirst" . " " . "$UserLast";
	if ($SiteClosed == "") $SiteClosed = $SiteOpen;
	if ($SiteOpen == "") $SiteOpen = $SiteClosed = $NullDate;
	if ($UserHome == 1) $UserHome = 0; // protection for unassigned
	$_SESSION["SiteCurrent"] = $SiteCurrent;

	// Process request
	if ($_SESSION["TRACE"]) error_log("MANAGE: $ThisName, Action=$SiteAction");
	switch ($SiteAction) {

		case "FindByPhone":
		case "FindByEmail":
		case "FindByName":
		case "FindBySound":
			Do_Search();
			$SiteUserHome = $SiteCurrent;
			break;

		case "AddSite": 
			if ($Site1Name == "") {
				$Errormessage .= "Invalid site name";
				break;
			}

			$query = "SELECT site_name FROM $SITE_TABLE WHERE `site_name` = '$Site1Name'";
			$locs = mysqli_query($dbcon, $query);
			if (mysqli_num_rows($locs) != 0) {
				$Errormessage .= "$Site1Name already exists - please choose another name.";
				break;
				}

			// All good - add it
			$query = "INSERT INTO $SITE_TABLE SET";
			$query .= " `site_name` = '$Site1Name'";
			$query .= ", `site_address` = '$Site1Address'";
			$query .= ", `site_inet` = ''";
			$query .= ", `site_contact` = '$SiteContact'";
			$query .= ", `site_help` = ''"; // not used
			$query .= ", `site_sumres` = '$SiteSumres'";
			$query .= ", `site_10dig` = 'checked'"; // default
			$addedby = ($isAdministrator) ? 0 : $SiteUserHome;
			$query .= ", `site_addedby` = $addedby";
			$SiteMessage = ($SiteMessage) ? $SiteMessage : $SystemConfirm;
			$query .= ", `site_message` = '$SiteMessage'";
			$query .= ", `site_reminder` = '$SiteReminder'";
			$query .= ", `site_instructions` = '$SiteInstructions'";
			$query .= ", `site_attach` = '$SiteAttach'";
			$query .= ", `site_lastrem` = '$SiteLastRem'";
			$query .= ", `site_open` = '$SiteOpen'";
			$query .= ", `site_closed` = '$SiteClosed'";
			$SaveQuery = $query;
			mysqli_query($dbcon, $query);

			// Get the new site number so a site and site manager can be linked to it
			$query = "SELECT * FROM $SITE_TABLE WHERE `site_name` = '$Site1Name'";
			$locs = mysqli_query($dbcon, $query);
			while ($row = mysqli_fetch_array($locs)) {
				$SiteIndex = $row["site_index"];
			}
			if (isset($SiteIndex)) {
				$SiteUserHome = $SiteCurrent = $SiteIndex;
				Update_Site_Options($SiteOptions);
				// Make the current user a manager for this site
				Write_Site_Option(0, $SiteIndex, $_SESSION["User"]["user_index"], "M");
				// Give new site scheduling access to the manager's site
				Write_Site_Option($_SESSION["User"]["user_home"], $SiteIndex, 0, $ACCESS_ALL);
			}
			else {
				$Errormessage .= "Could not add $Site1Name. Try it again.";
				if ($_SESSION["TRACE"]) error_log("MANAGE: $Errormessage, Query=$SaveQuery");
			}
			break;

		case "AddUser": 
			if ($UserFirst == "") {
				$Errormessage .= "Invalid first name";
				break;
			}
			if ($UserLast == "") {
				$Errormessage .= "Invalid last name";
				break;
			}
			if ($UserHome == "") {
				$Errormessage .= "Invalid site name";
				break;
			}
			if ($UserEmail == "") {
				$Errormessage .= "Invalid email";
				break;
			}
			
			// Check to see if email is already present
			$emailTest = Unique_Email(0, $UserEmail);

			if ($emailTest["count"] == 1) {
				$idqindex = $emailTest["user_index"];
				$idqhome = $emailTest["user_home"];
				$idqname = $emailTest["user_first"] . " " . $emailTest["user_last"];
				if ($idqhome == "0") { // no home site, this is a taxpayer, now mine
					$query = "UPDATE $USER_TABLE SET";
					$query .= " `user_home` = '$UserHome'"; 
					$query .= " , `user_appt_site` = 0"; 
					$query .= " , `user_options` = 15"; 
					$query .= " WHERE `user_index` = $idqindex";
					mysqli_query($dbcon, $query);
				}
				else if($idqhome == $UserHome) { // already mine
					$Errormessage .= "$UserFirst, whose email is $UserEmail, ";
					$Errormessage .= "already has an account as $idqname.";
				}
				else { // already has a home site
					$query = "SELECT * FROM $SITE_TABLE";
					$query .= " WHERE `site_index` = '$idqhome'";
					$ids = mysqli_query($dbcon, $query);
					$row = mysqli_fetch_array($ids);
					$sitename = $row["site_name"];
					$Errormessage .= "$UserFirst, whose email is $UserEmail, ";
					$Errormessage .= "already has an account as $idqname at $sitename.";
					$Errormessage .= " They will have to transfer this scheduler to you.";
				}
				break;
			}
			if ($emailTest["count"] == -1) {
				$idqname = $emailTest["user_first"] . " " . $emailTest["user_last"];
				$Errormessage .= "This email is already in use by $idqname";
				break;
			}

			// All good - add it
			$query = "INSERT INTO $USER_TABLE (`user_first`, `user_last`, `user_name`, `user_email`, `user_phone`, `user_home`, `user_options`, `user_pass`, `user_appt_site`, `user_sitelist`)";
			$RandomPassword = false;
			if (! $UserPass) {
				$str = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789";
				$UserPass = substr(str_shuffle($str),rand(0,strlen($str)-8),8);
				$RandomPassword = true;
			}
			$UserOptions = $ACCESS_ALL; // add as a scheduler
			$query .= " VALUES ('$UserFirst', '$UserLast', '$UserName', '$UserEmail', '$UserPhone', $UserHome, '$UserOptions', '$UserPass', 0, '|')"; 
			mysqli_query($dbcon, $query);

			$UserCurrent = $UserPreferred = 0;
			$query = "SELECT * FROM $USER_TABLE WHERE `user_first` = '$UserFirst' AND `user_email` = '$UserEmail'";
			$ans = mysqli_query($dbcon, $query);
			while ($row = mysqli_fetch_array($ans)) {
				$UserPreferred = $row["user_index"];
				$UserFullName = $row["user_first"] . " " . $row["user_last"];
			}
			if ($UserPreferred > 0) {
				if ($RandomPassword) { // only send email if a random password was created
					$to = $UserEmail;
					$subject = "You may now use the AARP reservation system";
					$headers = "From: AARP Tax-Aide appointment manager <$ThisEmail>\r\n";
					$headers .= "Reply-To: $ThisEmail\r\n";
					$headers .= "Return-Path: $ThisEmail\r\n";
					$headers .= "Errors-To: $ThisEmail\r\n";
					$message = "Welcome, " . $UserFullName . ", to the Tax-Aide appointment system.\n\n";
					$message .= "You have been added to the appointment scheduling system";
					$message .= " by " . $_SESSION["User"]["user_first"] . " " .  $_SESSION["User"]["user_last"] . "\n\n";
					$message .= "You have been given a short user name of " . $_SESSION["User"]["user_name"] . ", used for event logging.\n\n";
					$message .= "Sign in to the appointment system.\n";
					$message .= "Your Appointment System password is \"" . $UserPass . "\".\n";
					if (substr($to,-5,5) == ".test") {
						$Alert .= "The following email would have been sent:\\n\\n" . str_replace("\n","\\n",$message);
					}
					else {
						if (mail($to,$subject,$message,$headers)) {
							$Usermessage = "Added $UserFullName and emailed an initial password to them.";
						}
						else {
							$Usermessage = "Email to $UserFullName failed.";
						}
					}
				}
				$UserCurrent = $UserPreferred;
			}
			else {
				$Errormessage .= "Could not add " . _Show_Chars($UserFullName, "text") . ". Review your data and try again.";
			}
			$SiteUserHome = $SiteCurrent; // stay in the same site
			break;

		case "ChangeSite":
			$query = "UPDATE $SITE_TABLE SET";
			$query .= " `site_name` = '$Site1Name'"; 
			$query .= ", `site_address` = '$Site1Address'";
			$query .= ", `site_contact` = '$SiteContact'";
			$query .= ", `site_sumres` = '$SiteSumres'";
			$query .= ", `site_10dig` = '$Site10dig'";
			$query .= ", `site_message` = '$SiteMessage'";
			$query .= ", `site_reminder` = '$SiteReminder'";
			$query .= ", `site_instructions` = '$SiteInstructions'";
			$query .= ", `site_attach` = '$SiteAttach'";
			$query .= ", `site_lastrem` = '$SiteLastRem'";
			$query .= " WHERE `site_index` = $SiteCurrent";
			mysqli_query($dbcon, $query);
			Update_Site_Options($SiteOptions);
			// no break
		case "SwitchSite":
			$SiteUserHome = $SiteCurrent;
			$UserPreferred = $UserCurrent;
			break;

		case "ChangeUser":
			$emailTest = Unique_Email($UserCurrent, $UserEmail);
			if ($emailTest["count"] == -1) {
				$idqname = $emailTest["user_first"] . " " . $emailTest["user_last"];
				$Errormessage .= "This email is already in use by $idqname";
				break;
			}
			else {
				$query = "UPDATE $USER_TABLE SET";
				$query .= " `user_first` = '$UserFirst'"; 
				$query .= ", `user_last` = '$UserLast'";
				$query .= ", `user_name` = '$UserName'";
				$query .= ", `user_appt_site` = $UserAppt";
				$query .= ", `user_home` = $UserHome";
				if ($UserHome == 0) { // clear permissions
					$query .= ", `user_options` = ''";
				}
				$query .= ", `user_email` = '$UserEmail'";
				$query .= ", `user_phone` = '$UserPhone'";
				$query .= ", `user_pass` = '$UserPass'";
				$query .= " WHERE `user_index` = $UserCurrent";
				mysqli_query($dbcon, $query);
			}

			if ($UserOptions == 0) $UserCurrent = $_SESSION["User"]["user_index"];
			// no break
		case "ViewUser":
			$UserPreferred = $UserCurrent;
			$SiteUserHome = $SiteCurrent;
			break;
		case "SortUser":
			$_SESSION["UserSort"] = $UserSort;
			$SiteUserHome = $SiteCurrent;
			break;

		case "DeleteSite":
			$query = "SELECT * FROM $APPT_TABLE";
			$query .= " WHERE `appt_location` = $SiteCurrent";
			$query .= " AND `appt_date` != $NullDate";
			$result = mysqli_query($dbcon, $query);
			$count = mysqli_num_rows($result);
			if ($count > 1) { // One callback record will remain after deleting all appointments
				if ($_SESSION["TRACE"]) error_log("MANAGE: " . $ThisName . ", Delete " . $Site1Name . " is denied.");
				$Errormessage .= "You cannot delete this site because there is data in the appointment table.";
				$Errormessage .= " Go to the Manage Appointments screen and use the";
				$Errormessage .= " Configure Appointment Slots tool with the";
				$Errormessage .= " 'Start over' option to clear the data.";
				break;
			}
			$query = "DELETE FROM $SITE_TABLE";
			$query .= " WHERE `site_index` = $SiteCurrent";
			mysqli_query($dbcon, $query);
			$query = "DELETE FROM $ACCESS_TABLE";
			$query .= " WHERE `acc_location` = $SiteCurrent";
			$query .= " OR `acc_owner` = $SiteCurrent";
			mysqli_query($dbcon, $query);
			$query = "DELETE FROM $SCHED_TABLE";
			$query .= " WHERE `sched_location` = $SiteCurrent";
			mysqli_query($dbcon, $query);
			$query = "DELETE FROM $APPT_TABLE";
			$query .= " WHERE `appt_location` = $SiteCurrent";
			mysqli_query($dbcon, $query);
			$query = "UPDATE $USER_TABLE SET";
			$query .= " `user_home` = 0";
			$query .= ", `user_appt_site` = 0";
			$query .= ", `user_options` = ''";
			$query .= " WHERE `user_home` = $SiteCurrent";
			$query .= " AND `user_options` != 'A'";
			mysqli_query($dbcon, $query);
			$query = "UPDATE $USER_TABLE SET";
			$query .= " `user_home` = $SiteUserHome";
			$query .= " WHERE `user_home` = $SiteCurrent";
			$query .= " AND `user_options` = 'A'";
			mysqli_query($dbcon, $query);
			$query = "UPDATE $USER_TABLE SET";
			$query .= " `user_appt_site` = 0";
			$query .= " WHERE `user_appt_site` = $SiteCurrent";
			mysqli_query($dbcon, $query);
			if ($_SESSION["TRACE"]) error_log("MANAGE: " . $ThisName . ", Site $Site1Name ($SiteCurrent) deleted.");
			break;

		case "DeleteUser":
			$query = "DELETE FROM $USER_TABLE";
			$query .= " WHERE `user_index` = $UserCurrent";
			$query .= " AND `user_email` = '$UserEmail'";
			mysqli_query($dbcon, $query);
			$SiteUserHome = $SiteCurrent;
			$UserPreferred = $_SESSION["User"]["user_index"];
			break;

		case "DeleteUserByDate":
			$query = "DELETE FROM $USER_TABLE";
			$query .= " WHERE `user_lastlogin` < '$UserCurrent'";
			$query .= " AND `user_appt_site` = $SiteCurrent";
			mysqli_query($dbcon, $query);
			$SiteUserHome = $SiteCurrent;
			$UserPreferred = $_SESSION["User"]["user_index"];
			break;

		case "ChangeSystem":
			$query = "UPDATE $SYSTEM_TABLE SET";
			$query .= " `system_greeting` = '$SystemGreeting'"; 
			$query .= ", `system_notice` = '$SystemNotice'";
			$query .= ", `system_confirm` = '$SystemConfirm'";
			$query .= ", `system_attach` = '$SystemAttach'";
			$query .= ", `system_url` = '$SystemURL'";
			$query .= ", `system_email` = '$SystemEmail'";
			$query .= ", `system_heartbeat` = '$SystemHeartbeat'";
			$query .= " WHERE `system_index` = 1";
			mysqli_query($dbcon, $query);
			$_SESSION["SystemGreeting"] = $SystemGreeting;
			$_SESSION["SystemNotice"] = $SystemNotice;
			$_SESSION["SystemConfirm"] = $SystemConfirm;
			$_SESSION["SystemAttach"] = $SystemAttach;
			$_SESSION["SystemURL"] = $SystemURL;
			$_SESSION["SystemEmail"] = $SystemEmail;
			$_SESSION["SystemHeartbeat"] = $SystemHeartbeat;
			break;

		case "StartTrace":
			$query = "UPDATE $SYSTEM_TABLE SET";
			$query .= " `system_trace` = 'T'"; 
			$query .= " WHERE `system_index` = 1";
			mysqli_query($dbcon, $query);
			$_SESSION["TRACE"] = true;
			if ($_SESSION["TRACE"]) error_log("MANAGE: " . $ThisName . ", " . $SiteAction);
			break;

		case "StopTrace":
			$query = "UPDATE $SYSTEM_TABLE SET";
			$query .= " `system_trace` = ''"; 
			$query .= " WHERE `system_index` = 1";
			mysqli_query($dbcon, $query);
			$_SESSION["TRACE"] = false;
			break;

		case "ViewTrace":
			header('Location: viewtrace.php');
			break;

		case "ClearTrace":
			unlink('appt_error_log');
			break;

		case "SignOut":
			session_unset();
			header('Location: index.php');
			exit();
	}
}

// Make an array of user-managed and shared sites
// Your user home and unassigned is always a managed site
$MyManagedSites = array(1, $_SESSION["User"]["user_home"]);
$MySharedSites = array();
if ($_SESSION["User"]["user_home"] == $SiteUserHome) {
	array_push($MySharedSites, $SiteUserHome);
}

// ACCESS_TABLE has 2 entry types:
// acc_owner is the site giving permissions to:
// 1. individuals - acc_user is the person's index in the USER_TABLE
// 2. other sites - acc_location is the site index in the SITE_TABLE
// acc_option is the level of permission given to case 1 or 2 above.
$query = "SELECT * FROM $ACCESS_TABLE WHERE";
$query .= " `acc_user` = " . $_SESSION['User']['user_index'];
$query .= " OR `acc_owner` = " . $SiteUserHome;
$query .= " ORDER BY acc_location";
$locs = mysqli_query($dbcon, $query);
while ($row = mysqli_fetch_array($locs)) {
	// Add other managed sites to the list
	// (other site managers have given permission to this person to manage their site)
	if (($row['acc_user'] == $_SESSION['User']['user_index']) and ($row['acc_option'] == "M")) {
		array_push($MyManagedSites,$row['acc_owner']);
		array_push($MySharedSites,$row['acc_owner']);
	}
	// Add all sites to whom permissions were given to the shared sites list
	if (in_array($row['acc_owner'], $MyManagedSites)) {
		array_push($MySharedSites, $row['acc_location']);
		$testopt = $row['acc_option'];
		$SiteOption[$row['acc_location']] = ($testopt) ? $testopt : 0;
	}
}

// Build a list of sites based on whatever may have changed above
$j = 0;
$SiteIndexList = "";
$query = "SELECT * FROM $SITE_TABLE";
$query .= " ORDER BY site_name";
$locs = mysqli_query($dbcon, $query);
Get_Site_Options(); 
$OptionList = "";
while ($row = mysqli_fetch_array($locs)) {
	$NewIndex   = htmlspecialchars_decode($row["site_index"] ?? '');
	$NewLoc = htmlspecialchars_decode($row["site_name"] ?? '');
	$NewAddress = explode("|",htmlspecialchars_decode($row["site_address"] ?? ''));
	$NewContact = htmlspecialchars_decode($row["site_contact"] ?? '');
	$NewSumres = htmlspecialchars_decode($row["site_sumres"] ?? '');
	$New10dig = htmlspecialchars_decode($row["site_10dig"] ?? '');
	$NewInternet = htmlspecialchars_decode($row["site_inet"] ?? '');
	$NewOpen = htmlspecialchars_decode($row["site_open"] ?? '');
	$NewClosed = htmlspecialchars_decode($row["site_closed"] ?? '');
	$NewMessage = htmlspecialchars_decode($row["site_message"] ?? '');
	$NewReminder = htmlspecialchars_decode($row["site_reminder"] ?? '');
	$NewLastRem = htmlspecialchars_decode($row["site_lastrem"] ?? '');
	$NewInstructions = $row["site_instructions"];
	$NewAttach = $row["site_attach"];

	if ($SiteUserHome < 1) $SiteUserHome = $NewIndex;
	$SiteCurrent = $SiteUserHome;

	if ($isAdministrator) {
		array_push($MyManagedSites, $NewIndex);
		array_push($MySharedSites, $NewIndex);
	}

	if ($NewIndex == $SiteUserHome) {
		$ThisLoc = $NewLoc;
		$ThisAddress = $NewAddress;
		$ThisContact = $NewContact;
		$ThisSumres = $NewSumres;
		$This10dig = $New10dig;
		$ThisInternet = $NewInternet;
		$ThisOpen = ($NewOpen == $NullDate) ? "" : $NewOpen;
		$ThisClosed = ($NewClosed == $NullDate) ? "" : $NewClosed;
		if (! $NewMessage) $NewMessage = $_SESSION["SystemConfirm"];;
		$ThisMessage = $NewMessage;
		$ThisReminder = $NewReminder;
		$ThisLastRem = $NewLastRem;
		$ThisInstructions = $NewInstructions;
		$ThisAttach = $NewAttach;
		$ThisSiteOptions = $ThisInternet; // start of option list is internet option
	}

	else {
		$SiteIndexList .= "" . $NewIndex . ", ";
		if (! isset($ThisSiteAccess[$NewIndex])) $ThisSiteAccess[$NewIndex] = 0;
		if ($ThisSiteAccess[$NewIndex] > 0) $OptionList .= "|" . $NewIndex . ":" . $ThisSiteAccess[$NewIndex];
	}

	$LocationList[0] = ++$j;
	$LocationList[$j] = $NewLoc;
	$LocationIndex[$j] = $NewIndex;
	$LocationLookup["S" . $NewIndex] = $j;
}
@$ThisSiteOptions .= $OptionList;
//$Errormessage .= "M:";foreach ($MyManagedSites as $j) $Errormessage .= ", " . $j; $Errormessage .= "\\n"; // DEBUG
//$Errormessage .= "S:";foreach ($MySharedSites  as $j) $Errormessage .= ", " . $j; $Errormessage .= "\\n"; // DEBUG

// Build a new list of users
$j = 0;
$LastEmail = "";
$LastManagerEmail = "";
$ManagerRoster = "";
$ManagerEmail = "";
$UListAll = ""; // DEBUG
$query = "SELECT DISTINCT * FROM $USER_TABLE";
$query .= " LEFT JOIN $ACCESS_TABLE";
$query .= " ON $USER_TABLE.user_index = $ACCESS_TABLE.acc_user";
$query .= " LEFT JOIN $SITE_TABLE";
$query .= " ON $USER_TABLE.user_home = $SITE_TABLE.site_index";
$query .= " ORDER BY site_name, user_last, user_first";
$usrs = mysqli_query($dbcon, $query);
while ($row = mysqli_fetch_array($usrs)) {
	$NewIndex = htmlspecialchars_decode($row["user_index"] ?? '');
	$NewFirst = htmlspecialchars_decode($row["user_first"] ?? '');
	$NewName = htmlspecialchars_decode($row["user_name"] ?? '');
	$NewLast = htmlspecialchars_decode($row["user_last"] ?? '');
	$NewHome = htmlspecialchars_decode($row["user_home"] ?? '');
	$NewApptSite = htmlspecialchars_decode($row["user_appt_site"] ?? '');
	$NewEmail = htmlspecialchars_decode($row["user_email"] ?? '');
	$NewPhone = htmlspecialchars_decode($row["user_phone"] ?? '');
	$NewPass = $row["user_pass"] ?? '';
	$NewOptions = htmlspecialchars_decode($row["user_options"] ?? '');
	$AccOwner = $row["acc_owner"];
	$AccUser = $row["acc_user"];
	$AccOptions = $row["acc_option"];
	$AccLocation = $row["acc_location"];

	// Show as unassigned site if not in a shared site
	if ($NewOptions != "A") {
		if (($NewHome == 0) AND ($NewApptSite != 0)
		AND (! in_array($NewApptSite, $MySharedSites))) {
			$NewApptSite = 1;
		}
		else if (($NewHome != 0) AND ($NewApptSite == 0)
			AND (! in_array($NewHome, $MySharedSites))
			AND ($AccOptions != "M")) {
				$NewHome = 1;
			}
		else if (($NewHome == 0) AND ($NewApptSite == 0)) $NewApptSite = 1; // database error
		else if (($NewHome != 0) AND ($NewApptSite != 0)) $NewApptSite = 0; // database error
	}

	// Check for multiple records for the same person from access table
	if ($NewEmail == $LastEmail) {
		// Is this of interest to us?
		if (($AccUser > 0) AND ($AccOwner == $SiteUserHome)) {
			$UList[0] = --$j; // replace current information
		}
		else continue; // somebody else's permissions, skip it
	}

	// For DEBUGing
	// $k = $UList[0] + 1; $UListAll .= $k . "="  . $NewName . "<br />"; // DEBUG
	// $so = " H:" . $NewHome . "(" . $SiteOption[$NewHome] . ")"; // DEBUG
	// $NewLast .= "[$SiteUserHome] $NewHome($NewOptions) $so $AccOwner($AccOptions)"; // DEBUG

	// Create manager roster for Administrators
	if ($isAdministrator) {
		if (($NewOptions == "A") OR ($NewOptions == "M")) {
			if ($NewEmail != $LastManagerEmail) { // skip duplicates
				$rosterphone = ($NewPhone) ? $NewPhone : "?";
				$rosterloc = ($NewHome) ? $LocationList[$LocationLookup["S" . $NewHome]] : "?";
				$NewFirstC = _Show_Chars($NewFirst, "html");
				$NewLastC = _Show_Chars($NewLast, "html");
				$ManagerRoster .= "<tr><td>$NewFirstC</td><td>$NewLastC</td><td>$NewEmail</td><td>$rosterphone</td><td>$rosterloc</td></tr>";
				$ManagerEmail .= $NewEmail . ";";
				$LastManagerEmail = $NewEmail;
			}
		}
	}

	// Add the user to the user list
	$UList[0] = ++$j;
	$UList[$j] = $NewFirst . " " . $NewLast . " " . (($NewOptions == "M") ? $MFlag: "") . (($NewOptions == "A") ? $AFlag : "");
	$UHomeSite[$j] = $NewHome;
	$UApptSite[$j] = $NewApptSite;
	$UIndex[$j] = $NewIndex;
	$UPhone[$j] = $NewPhone;
	$UEmail[$j] = $LastEmail = $NewEmail;
	$ULogin[$j] = htmlspecialchars_decode($row["user_lastlogin"] ?? '');
	$UMonth = +substr($ULogin[$j],5,2);
	if ($UMonth < 10) $UMonth = "&nbsp;&nbsp;" . $UMonth;
	$UDate[$j] = ($UMonth == "&nbsp;&sbsp;0") ? "?" : ($UMonth . "/" . substr($ULogin[$j],0,4));
	//$UDate[$j] = substr($ULogin[$j],5,2) . "/" . substr($ULogin[$j],0,4);
	//if ($UDate[$j] == "0/0") $UDate[$j] = "?";
	$UFlag[$j] = "";
	$UOptions[$j] = $NewOptions;
	$OldIndex = $NewIndex;
	if ($UOptions[$j] == "A" ) {
		$Administrators .= (($Administrators == "") ? "" : ",") . $UIndex[$j];
		$UFlag[$j] = $AFlag;
	}
	if (! isset($SiteOption[$NewHome])) $SiteOption[$NewHome] = 0;

	// Set up default permissions for this user's site
	if ($NewHome == $SiteUserHome) { // Home site
		if ($UOptions[$j] > 0) $UFlag[$j] = $SFlag;
	      	if ($UOptions[$j] == "M") {
			$AppointmentManagers .= (($AppointmentManagers == "") ? "" : ",") . +$UIndex[$j];
			$UFlag[$j] = $MFlag;
		}
	}

	else if ((($AccOwner == $SiteUserHome)
	   or ($SiteOption[$NewHome] > 0)
	   or ($AccOptions == "M"))
	and ($UOptions[$j] != "A")) { // permissions for other sites
		
		$UOptions[$j] = 0; // initally, no permissions

		if ($AccOwner == $SiteUserHome) {
			// User permissions given on the user page	
			if ($AccOptions == "M") {
				$UOptions[$j] = "M";
				$AppointmentManagers .= (($AppointmentManagers == "") ? "" : ",") . +$UIndex[$j];
				$UFlag[$j] = $MFlag;
			}
			else if ($AccOptions > 0) {
				$UOptions[$j] = $AccOptions;
				$UFlag[$j] = $SFlag;
			}
		}
	}

	else if ($UOptions[$j] != "A") {
		$UOptions[$j] = 0;
	}

	// Looking at a specfic person...
	if ($UserPreferred == 0) $UserPreferred = $_SESSION['User']['user_index'];
	if ($NewIndex == $UserPreferred) {
		$ThisUser = $NewIndex;
		$ThisFirst = $NewFirst;
		$ThisLast = $NewLast;
		$ThisName = $NewName;
		$ThisFullName = $ThisFirst . " " . $ThisLast;
		$ThisHome = ($NewHome) ? $NewHome : $NewApptSite;
		for ($i = 1; $i <= $LocationList[0]; $i++) {
			if ($LocationIndex[$i] == $ThisHome) {
				$ThisHomeName = $LocationList[$i];
			}
		}
		$ThisApptSite = $NewApptSite;
		$ThisEmail = $NewEmail;
		$ThisPhone = $NewPhone;
		$ThisPass = $NewPass;
		$ThisUserOptions = $UOptions[$j];
	}

	$OldIndex = $NewIndex;
}
// END OF MAIN LOOP

// -----------------------------------------------------------------------------
function Unique_Email($testId, $testEmail) {
//	testId is the id that belongs to the email
//	testEmail is the email to be tested for uniqueness
// returns and array with count and id information for later use:
//	1 = email matches the id, return user information
//	0 = no match to any id
//	-1 = email does not match the id
// -----------------------------------------------------------------------------
	global $dbcon;
	global $USER_TABLE;

	$emailTest = [];
	$emailTest["count"] = 0;
	$query = "SELECT * FROM $USER_TABLE";
	$query .= " WHERE `user_email` = '$testEmail'";
	$idq = mysqli_query($dbcon, $query);
	$count = mysqli_num_rows($idq);
	if ($count == 0) return $emailTest;
	while ($row = mysqli_fetch_array($idq)) {
		$emailTest = $row;
		if ($emailTest["user_index"] != $testId) {
			$emailTest["count"] = -1;
			return $emailTest;
		}
	}
	$emailTest["count"] = 1;
	return $emailTest;
}

// -----------------------------------------------------------------------------
function Update_Site_Options($optionlist) {
//	optionlist is in the form of: X:n|a:b|c:d|e:f|
//		where X is the internet user scheduling option
//		n is the internet user appointment scheduling limit
//		a,c,e are site numbers that are allowed some access to this site
//		b,d,f are codes that define the allowed access
// -----------------------------------------------------------------------------
	global $SiteCurrent;
	global $Usermessage;
	global $DEBUG;
	global $ACCESS_TABLE;
	global $SITE_TABLE;
	global $SiteInternet;
	global $SiteOpen;
	global $SiteClosed;
	global $SiteMessage;
	global $dbcon;
	global $Errormessage;
	global $ThisName;

	// Remove prior site options
	$query = "DELETE FROM $ACCESS_TABLE WHERE `acc_owner` = $SiteCurrent AND `acc_user` = 0";
	mysqli_query($dbcon, $query);

	// Make an array of access options
	$acc_options = explode("|", $optionlist);
	if (count($acc_options) > 0) {
		if ($_SESSION["TRACE"]) error_log("MANAGE: $ThisName, Site options=$optionlist");
		
		// Set the internet user option
		$SiteInternet = $acc_options[0];
		$query = "UPDATE $SITE_TABLE SET";
		$query .= " `site_inet` = '$SiteInternet'"; 
		$query .= " , `site_open` = '$SiteOpen'"; 
		$query .= " , `site_closed` = '$SiteClosed'"; 
		$query .= " WHERE `site_index` = $SiteCurrent";
		mysqli_query($dbcon, $query);

		// Set the other site access options
		for ($j = 1; $j < count($acc_options); $j++) {
			$ooption = explode(":",$acc_options[$j]);
			Write_Site_Option($ooption[0], $SiteCurrent, 0, "$ooption[1]");
		}
	}
}

// -----------------------------------------------------------------------------
function Get_Site_Options() {
//	Fill array $ThisSiteAccess[] with permission options for each site that has permissions
// -----------------------------------------------------------------------------
	global $SiteUserHome;
	global $ACCESS_TABLE;
	global $ThisSiteAccess;
	global $dbcon;
	$Mylist = "";

	$query = "SELECT * FROM $ACCESS_TABLE";
	$query .= " WHERE `acc_owner` = $SiteUserHome";
	$optlist = mysqli_query($dbcon, $query);
	while ($row = mysqli_fetch_array($optlist)) {
		$NewLocation = $row["acc_location"];
		$NewOption = $row["acc_option"];
		$ThisSiteAccess[$NewLocation] = $NewOption;
	}
}

// -----------------------------------------------------------------------------
function Write_Site_Option($othersite, $owner, $userid, $option) {
//	owner = the index number of the site which is giving a permission
//	othersite = the index number of the site which is given the permission
//	userid = the index number of a particular user involved (optional)
//	option = the option code
// -----------------------------------------------------------------------------
	global $DEBUG;
	global $ACCESS_TABLE;
	global $Errormessage;
	global $dbcon;

	// Delete and re-add with new options
	$query = "DELETE FROM $ACCESS_TABLE";
	$query .= " WHERE `acc_location` = '" . $othersite . "'";
	$query .= " AND `acc_owner` = '" . $owner . "'";
	$query .= " AND `acc_user` = '" . $userid . "'";
	mysqli_query($dbcon, $query);

	if ((($option > 0) OR ($option == "M")) AND ($option != "A")) {
		$query = "INSERT INTO $ACCESS_TABLE (`acc_location`, `acc_owner`, `acc_user`, `acc_option`)";
		$query .= " VALUES ( $othersite, $owner, $userid, '$option')";
		mysqli_query($dbcon, $query);
	}
}

// -----------------------------------------------------------------------------
function List_Attachments() {
// -----------------------------------------------------------------------------
	global $ThisAttach;
	global $AttachContent;
	$AttachContent = "";
	$attSysList = explode("|", $_SESSION["SystemAttach"]);
	if (sizeof($attSysList) < 2) return;
	echo "<br />[ATTACHMENTS] (Check which below)";
	echo "\n<span onchange=\"Change_Site_Attachments();\">";
	for ($attindex = 0; $attindex < sizeof($attSysList)-1; $attindex++) {
		$attrow = explode("=", _Show_Chars($attSysList[$attindex], "html"));
		$attChecked = (strpos($ThisAttach, $attrow[0]) === false) ? "" : "checked" ;
		echo "\n<br /><input type=\"checkbox\" class=\"siteattcheckbox\" title=\"$attrow[1]\" $attChecked> $attrow[0]";
		$AttachContent .= "\n$attrow[0] ($attrow[1])";
	}
	echo "\n</span>";
}

// -----------------------------------------------------------------------------
function List_Sites($selections) {
//	selections:	"mine" = all those my permissions allow
//			"options" = full list for site permissions list
//			"users" = full list for assignment to a user
// -----------------------------------------------------------------------------
	global $LocationList;
	global $LocationIndex;
	global $SiteUserHome;
	global $ThisHome;
	global $ThisApptSite;
	global $ThisSiteAccess;
	global $MyManagedSites;
	global $isAdministrator;
	global $SFlag, $VFlag;
	global $ACCESS_ALL, $VIEW_APP;
	global $checkboxYes;
	global $Errormessage;
	global $SiteCurrent;

	$foundone = false;

	// Add location checkboxes
	if ($LocationList[0] > 0) {
		for ($j = 1; $j <= $LocationList[0]; $j++) {

			$select_this = "";
			$mine = false;
			switch ($selections) {
				case "mine":
					if (in_array($LocationIndex[$j],$MyManagedSites) or $isAdministrator) $mine = true;
					$id = "id='AS" . $LocationIndex[$j] . "' "; 
					if ($LocationIndex[$j] == $SiteCurrent) $select_this = "selected ";
					break;
				case "options":
					if ($LocationIndex[$j] == $SiteUserHome) continue 2; // jump to next $j
					if ($LocationList[$j] == "Unassigned") continue 2; // jump to next $j
					if (! $foundone) echo "<tr><th>Allow</th><th>Site Name</th></tr>";
					$foundone = true;
					$accval= $ThisSiteAccess[$LocationIndex[$j]];
					$accsval = ($accval == $ACCESS_ALL) ? $checkboxYes : "";
					$ids = "id='OSS_" . $LocationIndex[$j] . "' onclick='Change_Other_Sites(this.id)'"; 
					echo "\t<tr><td " . $ids . ">" . $accsval . "</td>\n";
					echo "\t<td>" . $LocationList[$j] . "</td></tr>\n";
					continue 2; // jump to next $j
					break;
				case "users":
					if (($LocationIndex[$j] > 0) or $isAdministrator) $mine = true;
					$id = "id='US" . $LocationIndex[$j] . "' "; 
					if ($ThisHome == 1) {
						if ($LocationIndex[$j] == $ThisApptSite) $select_this = "selected ";
					}
					else {
						if ($LocationIndex[$j] == $ThisHome) $select_this = "selected ";
					}
					break;
			}
			if ($mine) {
				echo "<option " . $id . $select_this . "value='" . $LocationIndex[$j] . "'>" . $LocationList[$j] . "</option>\n";
			}
		}
	}
}

// -----------------------------------------------------------------------------
function List_Users($ShowTPs) {
// ShowTPs = Site, lists Users assigned to site on User Options page table
//	   = Taxpayers, lists Users that have Internet IDs only table
// -----------------------------------------------------------------------------
	global $UList;
	global $UserPreferred;
	global $UIndex;
	global $UPhone;
	global $UEmail;
	global $UHomeSite;
	global $UApptSite;
	global $UFlag;
	global $ULogin;
	global $UDate; // user friendly version of $ULogin
	global $UOptions;
	global $SiteCurrent;
	global $LocationList;
	global $MySharedSites;
	global $LocationLookup;
	global $LocationIndex;
	global $isAdministrator;
	global $VIEW_CB;
	global $VIEW_APP;
	global $ADD_CB;
	global $ADD_APP;
	global $USE_RES;
	global $checkboxNo, $checkboxYes;

	if ($ShowTPs == "Taxpayers") { // make an associative array for sorting
		$TPsort = [];
		$TPcount = 0;
		for ($j = 1; $j <= $UList[0]; $j++) {
			if ($UHomeSite[$j] == 0) {
				if ($UApptSite[$j] != $SiteCurrent) continue;
				$TPcount++;
				switch ($_SESSION["UserSort"]) {
					case "user_lastlogin":
						$TPsort[$j] = $ULogin[$j];
						break;
					case "user_first":
						$TPsort[$j] = $UList[$j];
						break;
					case "user_email":
						$TPsort[$j] = $UEmail[$j];
						break;
					case "user_phone":
						$TPsort[$j] = $UPhone[$j];
						break;
					case "user_last": // this is the default sorting from the db query
					default:
						$TPsort[$j] = $j;
				}
			}
		}
		asort($TPsort);
		foreach ($TPsort as $j => $value) {
			echo "<tr>\n";
			$displaytext = _Show_Chars($UList[$j], "text");
			echo "\t<td id=\"TPNAME_"  . $UIndex[$j] . "\" onclick=\"Show_User(" . $UIndex[$j] . ", this.id);\">" . $displaytext . "</td>\n";
			echo "\t<td id=\"TPPHONE_" . $UIndex[$j] . "\" onclick=\"Show_User(" . $UIndex[$j] . ", this.id);\">" . $UPhone[$j]  . "</td>\n";
			echo "\t<td id=\"TPEMAIL_" . $UIndex[$j] . "\" onclick=\"Show_User(" . $UIndex[$j] . ", this.id);\">" . $UEmail[$j]  . "</td>\n";
			echo "\t<td id=\"TPDATE_"  . $UIndex[$j] . "\" onclick=\"Show_User(" . $UIndex[$j] . ", this.id);\">" . $UDate[$j]   . "</td>\n";
			echo "\t<td><button class='inet_button' onclick='Delete_TP(\"TP\", " . $UIndex[$j] . ")'>&nbsp;Delete&nbsp;</button></td></tr>\n";
		}
	return;
	}

	// The following is for the site listing with permissions
	if (($UList[0] > 0) && ($SiteCurrent > 1)) {
		$select_this = "";
		$lastlabeltext = "";

		// do 2 loops if Site user list so that current site list is first
		$loops = ($ShowTPs == "Site") ? 2 : 1;
		for ($loop = 1; $loop <= $loops; $loop++) {

			for ($j = 1; $j <= $UList[0]; $j++) {

				// do we want to display this person?
				$us = +$UHomeSite[$j]; // 1 = unassigned
				$uo = $UOptions[$j];
				$ss = ((in_array($us, $MySharedSites)) OR ($uo === "A") OR ($uo === "M"));

				if (! $ss) continue;
				if (($loop == 1) and ($us != $SiteCurrent)) continue;
				if (($loop == 2) and (($us == $SiteCurrent) or ($us == 0))) continue;
				if (($SiteCurrent != 1) AND ($us < 2)) continue;
				if (($SiteCurrent == 1) AND ($us > 1)) continue;

				// get the name to be displayed
				$displaytext = _Show_Chars($UList[$j], "text");
	
				// set a background color for the user and the site name for a group heading
				$optiontext = " style='background-color:";
				$labeltext = ($us) ? $LocationList[$LocationLookup["S" . $us]] : "";
				$optiontext .= (($us == $SiteCurrent) || ($us < 2)) ? "lightgreen;'" : "yellow;'";
	
				// add the option
				if ($labeltext != $lastlabeltext) {
					echo "<tr class='user_list_site'><td colspan='7'>" . $labeltext . "</td></tr>\n";
					$lastlabeltext = $labeltext;
				}
	
				// determine the user's options
				$onclick = "";
				$ccbb = array($checkboxNo, "user_change_no");
				$vcbb = array($checkboxNo, "user_change_no");
				$capp = array($checkboxNo, "user_change_no");
				$vapp = array($checkboxNo, "user_change_no");
				$ures = array($checkboxNo, "user_change_no");
				$onclicknonadmin = " onclick='Change_User_Role(this.id)' ";
				switch (true) {
					case ($uo === "A"): 
						if (! $isAdministrator) $onclicknonadmin = "";
						// no break
					case ($uo === "M"): 
						$ccbb[0] = $vcbb[0] = $capp[0] = $vapp[0] = $ures[0] = $checkboxYes;
						if ($uo === "A") $uotext = "Administrator"; 
						if ($uo === "M") $uotext = "Appt Manager";
						break;

					default:
						$uo = (int)$uo; // needed to prevent PHP warning message for bit-and ops below
						$ccbb[1] = $vcbb[1] = $capp[1] = $vapp[1] = "user_change_yes";
						if ($uo & $ADD_CB)  {
							$ccbb[0] = $checkboxYes;
							$vcbb[1] = "user_change_no";
						}
						if ($uo & $VIEW_CB) {
							$vcbb[0] = $checkboxYes;
						}
						if ($uo & $ADD_APP) {
							$capp[0] = $checkboxYes;
							$vapp[1] = "user_change_no";
							$ures[1] = "user_change_yes";
						}
						if ($uo & $VIEW_APP) {
							$vapp[0] = $checkboxYes;
						}
						if ($uo & $USE_RES) {
							$ures[0] = $checkboxYes;
						}
						$uotext = ($uo > 0) ? "Scheduler" : "";
						$onclick = " onclick='Set_User_Role(this.id)' ";
				}
	
				// print the user's line
				$showuserclick = "";
				if (($loop == 1) or $isAdministrator) {
					$showuserclick = " onclick='Show_User(" . $UIndex[$j] . ", this.id);'";
					$showuserclick .= " title='Last login: " . $UDate[$j] . "'";
				}
				echo "<tr" . $optiontext . "><td id='NAME_" . $UIndex[$j] . "'" . $showuserclick . ">" . $displaytext . "</td>\n";
				echo "<td id='ROLE_" . $UIndex[$j] . "' " . $onclicknonadmin . ">" . $uotext . "</td>\n";
				echo "<td id='CCBB_" . $UIndex[$j] . "' " . $onclick . "class='" . $ccbb[1] . "'>" . $ccbb[0] . "</td>\n";
				echo "<td id='VCBB_" . $UIndex[$j] . "' " . $onclick . "class='" . $vcbb[1] . "'>" . $vcbb[0] . "</td>\n";
				echo "<td id='CAPP_" . $UIndex[$j] . "' " . $onclick . "class='" . $capp[1] . "'>" . $capp[0] . "</td>\n";
				echo "<td id='VAPP_" . $UIndex[$j] . "' " . $onclick . "class='" . $vapp[1] . "'>" . $vapp[0] . "</td>\n";
				echo "<td id='URES_" . $UIndex[$j] . "' " . $onclick . "class='" . $ures[1] . "'>" . $ures[0] . "</td>\n";
				echo "<td id='HOME_" . $UIndex[$j] . "'>" . $us . "</td></tr>\n";
			}
		}
	}
}

//===========================================================================================
function Do_Search() {
//===========================================================================================
	global $Errormessage;
	global $SiteAction;
	global $UserPhone;
	global $UserEmail;
	global $UserName;
	global $USER_TABLE;
	global $SearchList;
	global $UserOptions;
	global $NullDate;
	global $dbcon;

	$query = "SELECT * FROM $USER_TABLE";
	switch ($SiteAction) {
		case "FindByPhone":
			$query .= " WHERE `user_phone` LIKE '%$UserPhone%'";
			$query .= " ORDER BY `user_phone`, `user_last`, `user_first`";
			break;
		case "FindByEmail":
			$query .= " WHERE `user_email` LIKE '%$UserEmail%'";
			$query .= " ORDER BY `user_email`, `user_last`, `user_first`";
			break;
		case "FindByName":
			$n = _Clean_Chars($UserName);
			$query .= " WHERE `user_name` LIKE '%$n%'";
			$query .= " OR `user_first` LIKE '%$n%'";
			$query .= " OR `user_last` LIKE '%$n%'";
			$query .= " ORDER BY `user_last`, `user_first`, `user_name`";
			break;
	}
	$SearchList = mysqli_query($dbcon, $query);
	/*
	//$j = 0;
	while($row = mysqli_fetch_array($search)) {
		$Site = $row['user_home'];
		$id = array("$First", "$Last", "$Name", "$Site");
		$SearchList[$j++] = $id;
	}

	while($row = mysqli_fetch_array($appointments)) {
		$Name = htmlspecialchars_decode($row['appt_name'] ?? '');
		$Name = _Show_Chars($Name, "text");
		$Date = $row['appt_date'];
		$Time = $row['appt_time'];
		$Site = $row['appt_location'];
		$Appt = $row['appt_no'];
		$Del = $row['appt_type'];
		if ($Date == $NullDate) {
			$Time = ($Del == "D") ? "deleted" : "callback";
		}
		$id = array("$Name","$Date","$Time","$Site","$Appt"); 
		$SearchList[$j++] = $id;
	}	
	 */

	$search = [];
}

//===========================================================================================
function Show_Search() {
//===========================================================================================
	global $Errormessage;
	global $SearchList;
	global $LocationLookup;
	global $LocationList;
	global $SiteAction;
	global $isAdministrator;
	global $MyManagedSites;
	global $dbcon, $USER_TABLE, $ThisName;

	switch ($SiteAction) {
		case "FindByName":
			$colhead = "";
			break;
		case "FindByPhone":
			$colhead = "Phone";
			break;
		case "FindByEmail":
			$colhead = "Email";
			break;
		default: return;
	}

	$noMatchFound = true;

	if (isset($SearchList)) { //&& count($SearchList) > 0) {
		while($row = mysqli_fetch_array($SearchList)) {
			if ($noMatchFound) {
				echo "<table id='user_search_table'>\n";
				echo "<tr class='sticky' style='background-color:#AAFFAA;'><th></th><th>First</th><th>Last</th><th>User&nbsp;Name</th><th>$colhead</th><th>User&nbsp;Role</th><th>Assigned Site</th></tr>\n";
				$noMatchFound = false;
			}
			$Name = _Show_Chars(htmlspecialchars_decode($row['user_name'] ?? ''), "text");
			$First = _Show_Chars(htmlspecialchars_decode($row['user_first'] ?? ''), "text");
			$Last = _Show_Chars(htmlspecialchars_decode($row['user_last'] ?? ''), "text");

			$sitenumber = $row['user_home'];
			if ($sitenumber > 1) {
				$showtab = "Schedulers";
				switch ($row['user_options']) {
					case "A": $usertype = "Administrator"; break;
					case "M": $usertype = "Appt&nbsp;Manager"; break;
					default: $usertype = "Scheduler"; break;
				}
			}
			else {
				$showtab = "Taxpayers";
				$sitenumber = $row['user_appt_site'];
				$usertype = "Taxpayer";
			}
			if ($sitenumber == 0) $sitenumber = 1; // Undefined site number
			if (isset($LocationLookup["S" . $sitenumber])) { 
				$sitename = $LocationList[$LocationLookup["S" . $sitenumber]];
			}
			else { // data error - correct it.
				$query = "UPDATE $USER_TABLE SET";
				$query .= " `user_appt_site` = 0"; 
				$query .= " , `user_sitelist` = '|'"; 
				$query .= " WHERE `user_index` = " . $row['user_index'];
				mysqli_query($dbcon, $query);
				$sitename = $LocationList[1];
				if ($_SESSION["TRACE"]) error_log("MANAGE: " . $ThisName . ", Site index " . $sitenumber . " removed.");
			}
			
			switch ($SiteAction) {
				case "FindByName":
					$colhead = "";
					break;
				case "FindByPhone":
					$colhead = $row['user_phone'];
					break;
				case "FindByEmail":
					$colhead = $row['user_email'];
					break;
			}
			if (($sitenumber == 1) || (in_array($sitenumber, $MyManagedSites)) || ($sitename == "(unassigned)")) {
				$oktochange = "<b>&#x2611;</b>";
				$showuserclick = "onclick='Show_User(" .  $row['user_index'] . ", \"$showtab\")'";
				$showclass = "";
			}
			else {
				$oktochange = "&#x2610;";
				$showuserclick = "";
				$showclass = "class='user_matchType' style='display: none;'";
			}
			echo "<tr $showclass $showuserclick>\n";
			echo "<td>$oktochange&nbsp;</td><td>$First</td><td>$Last</td><td>$Name</td><td>$colhead</td><td>$usertype</td><td>$sitename</td></tr>\n";
		}
		echo "</table><hr />\n";
	}
	if ($noMatchFound) echo "No match found<hr />\n";
}
?>

<!DOCTYPE html>
<html lang="en">
<!--==========================================================================================================-->
<!--==========================================================================================================-->
<!--============================================= WEB PAGE HEADER ============================================-->
<!--==========================================================================================================-->
<!--==========================================================================================================-->
<head>
<title>AARP Appointments</title>
<meta name=description content="AARP Site Management">
<meta name="viewport" content="width=device-width">
<link rel="SHORTCUT ICON" href="appt.ico">
<link rel="stylesheet" href="appt.css">
<link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons">
<script src="functions.js"></script>
<script>
	//===========================================================================================
	// Notes:
	// 	List boxes return a sequential number (1-n)
	//	Site numbers passed to and from the database should always use db site indexes
	//		dbindex = SiteIndex[listindex];
	//===========================================================================================

	var old_sitedata = [];
	var new_sitedata = [];
	var old_systemdata = [];
	var old_userdata = [];
	var new_userdata = [];
<?php
	global $DEBUG;
	if ($DEBUG) echo "\tvar DEBUG = $DEBUG;\n";
	global $TodayDate;
	echo "	var TodayDate = '$TodayDate';\n";
	global $Errormessage;
	echo "  var Errormessage = \"$Errormessage\";\n";
	global $Usermessage;
	echo "	var Usermessage = \"$Usermessage\";\n";
	//global $ConfirmMessage;
	//echo "	var ConfirmMessage = \"$ConfirmMessage\";\n";
	global $SiteIndexList;
	echo "	var SiteIndex = [" . $SiteIndexList . "];\n";
	global $ThisSiteOptions;
	echo "	var original_site_options = '" . $ThisSiteOptions . "';\n";
	global $ThisUserOptions;
	echo "	var new_user_options = old_user_options = '" . $ThisUserOptions . "';\n";
	global $Administrators;
	echo "	var Administrators = [" . $Administrators . "];\n";
	global $AppointmentManagers;
	echo "	var AppointmentManagers = [" . $AppointmentManagers . "];\n";
	global $VIEW_CB;
	echo "	var VIEW_CB = $VIEW_CB;\n";
	global $ADD_CB;
	echo "	var ADD_CB = $ADD_CB;\n";
	global $VIEW_APP;
	echo "	var VIEW_APP = $VIEW_APP;\n";
	global $ADD_APP;
	echo "	var ADD_APP = $ADD_APP;\n";
	global $USE_RES;
	echo "	var USE_RES = $USE_RES;\n";
	global $VFlag;
	echo "	var VFlag = '$VFlag';\n";
	global $SFlag;
	echo "	var SFlag = '$SFlag';\n";
	global $Dagger;
	echo "	var Dagger = '$Dagger';\n";
?>
	var ALL_OPTIONS = VIEW_CB | ADD_CB | VIEW_APP | ADD_APP | USE_RES;
	var initialization_flag = true;
	var system_change_flag = false;
	var selected_address = [];
	var site_change_flag = false;
	var site_add_flag = false;
	var user_change_flag = false;
	var user_role_change_flag = false;
	var user_add_flag = false;
	var new_user_role = "S";
	var checkboxYes = "";
	var checkboxNo = "";
	var AFlag = "";
	var MFlag = "";
	var reloadflag = false;
	var attachContent = "";

	// Set default email
	var DefaultEmail = "no.reply@tax.aide.reservations.no.email";

	// Create a default system greeting message
	var GreetingMessage = "";
	GreetingMessage += "AARP Foundation Tax-Aide, an AARP Foundation program, helps low- to moderate-income taxpayers";
	GreetingMessage += " have more discretionary income for everyday essentials, such as food and housing,";
	GreetingMessage += " by assisting with tax services and ensuring they receive applicable tax credits and deductions.";
	GreetingMessage += "\n\n";
	GreetingMessage += "AARP Foundation Tax-Aide is available free to taxpayers with low and moderate income,";
	GreetingMessage += " with special attention to those 60 and older.";
	GreetingMessage += " Through a cadre of trained and IRS accredited volunteers,";
	GreetingMessage += " AARP Foundation Tax-Aide has helped low- to moderate-income";
	GreetingMessage += " individuals for more than 40 years in every state and the District of Columbia.";
	GreetingMessage += "\n\n";
	GreetingMessage += "AARP Foundation Tax-Aide is offered in cooperation with the IRS.";
	GreetingMessage += "\n\n";
	GreetingMessage += "<div id=\"disclaimer\">";
	GreetingMessage += "<b>Please note:</b> There are some tax issues that are beyond the scope of our volunteer training.";
	GreetingMessage += " Most things that a retired or low-income person has will be OK.";
	GreetingMessage += "</div>";


	//===========================================================================================
	function Initialize() {
	//===========================================================================================
		// Save current site information
		old_sitedata["sitename"] = site_current_name.value;
		Build_Site_Address(); // creates new_sitedata["address"]
		old_sitedata["address"] = new_sitedata["address"];
		old_sitedata["contact"] = site_contact.value;
		// sumres and 10dig expect the word "checked" in the database
		old_sitedata["sumres"] = (site_sumres.checked) ? "checked" : "" ;
		old_sitedata["10dig"] = (site_10dig.checked) ? "checked" : "" ;
		// reminderoption can use T/F since it's not passed to the database
		old_sitedata["reminderoption"] = site_reminder_option.checked ;
		old_sitedata["reminder"] = site_reminder.value;
		old_sitedata["lastrem"] = site_lastrem.value;
		old_sitedata["message"] = site_message.value;
		old_sitedata["options"] = original_site_options;
		old_sitedata["open"] = site_open.value;
		old_sitedata["closed"] = site_closed.value;
		old_sitedata["instructions"] = site_instructions.value;
		old_sitedata["attach"] = site_attach.value;
		Build_User_Data(); // verifies and fills new_userdata array
		old_userdata = new_userdata;
		old_systemdata["SystemGreeting"] = system_greeting.value;
		old_systemdata["SystemConfirm"] = system_confirm.value;
		old_systemdata["SystemAttach"] = system_attach.value = SystemAttach.value;
		old_systemdata["SystemNotice"] = system_notice.value;
		old_systemdata["SystemURL"] = system_url.value;
		old_systemdata["SystemEmail"] = system_email.value;
		old_systemdata["SystemHeartbeat"] = system_heartbeat.value;

		checkboxYes = optYes.innerHTML;
		checkboxNo = optNo.innerHTML;
		AFlag = optA.innerHTML;
		MFlag = optM.innerHTML;

		// set up display
		Restore_System_Data();
		Restore_Site_Address();
		Restore_Site_Email();
		Restore_Site_Options();
		Restore_Site_Attachments();
		Restore_User_Data();

		// Initialize new site data and show the current tab
		for (j in old_sitedata) new_sitedata[j] = old_sitedata[j];
		system_change_flag = false;
		site_change_flag = false;
		user_change_flag = false;
		if ((site_name.value == 1) && (SiteForm.SiteView.value != "System")) {
			ShowPage("Taxpayers");
		}
		else {
			ShowPage(SiteForm.SiteView.value);
		}


		// Show any messages from the server
		if (Errormessage != "") alert (Errormessage); // Error message from php
		if (Usermessage != "") alert (Usermessage); // Error message from php
		<?php
		global $Alert;
		global $SiteAction;
		if (substr($SiteAction,0,6) == "FindBy") echo "user_search_box.style.visibility = 'visible';\n";
		if ($Alert != "") echo "alert('" . $Alert . "');";
		?>
		initialization_flag = false;
	}

	//===========================================================================================
	function ShowPage(pageid) {
	//===========================================================================================
		// Do not allow display of Site or Schedulers tabs for Unassigned site
		if (old_sitedata["sitename"] === "Unassigned") {
			menuTabSite.style.textDecoration = "line-through";
			menuTabUsers.style.textDecoration = "line-through";
			<?php global $isAdministrator;
			if (! $isAdministrator) echo 'pageid = "Taxpayers";';
			?>
		}

		// Make sure there are no changes pending before changing pages
		Check_For_Changes("before going to the new tab.");

		switch (pageid) {
			case "Schedulers":
				site_page.style.display = "none";
				user_page.style.display = "block";
				internet_page.style.display = "none";
				system_page.style.display = "none";
				system_selected.style.display = "none";
				site_selections.style.display = "block";
				menuTabSys.style.borderBottom = "1px solid black";
				menuTabSite.style.borderBottom = "1px solid black";
				menuTabUsers.style.borderBottom = "none";
				menuTabTPs.style.borderBottom = "1px solid black";
				Resize_Div("Schedulers");
				break;
			case "Taxpayers":
				site_page.style.display = "none";
				user_page.style.display = "none";
				internet_page.style.display = "block";
				system_page.style.display = "none";
				system_selected.style.display = "none";
				site_selections.style.display = "block"
				menuTabSys.style.borderBottom = "1px solid black";
				menuTabSite.style.borderBottom = "1px solid black";
				menuTabUsers.style.borderBottom = "1px solid black";
				menuTabTPs.style.borderBottom = "none";
				Resize_Div("Taxpayers");
				break;
			case "System":
				site_page.style.display = "none";
				user_page.style.display = "none";
				internet_page.style.display = "none";
				system_page.style.display = "block";
				system_selected.style.display = "block";
				site_selections.style.display = "none"
				menuTabSys.style.borderBottom = "none";
				menuTabSite.style.borderBottom = "1px solid black";
				menuTabUsers.style.borderBottom = "1px solid black";
				menuTabTPs.style.borderBottom = "1px solid black";
				user_information.style.display = "none";
				Resize_Div("System Options");
				break;
			default: // "Site" or none
				site_page.style.display = "block";
				user_page.style.display = "none";
				internet_page.style.display = "none";
				system_page.style.display = "none";
				system_selected.style.display = "none";
				site_selections.style.display = "block";
				menuTabSys.style.borderBottom = "1px solid black";
				menuTabUsers.style.borderBottom = "1px solid black";
				menuTabSite.style.borderBottom = "none";
				menuTabTPs.style.borderBottom = "1px solid black";
				user_information.style.display = "none";
				Resize_Div("Site Options");
				pageid = "Site";
				break;
		}

		// if site is Unassigned, change so that a new site selection will go to Site tab
		SiteForm.SiteView.value = (old_sitedata["sitename"] === "Unassigned") ? "Site" : pageid ;
	}

	//===========================================================================================
	function Change_System_Data() {
	//===========================================================================================
		if (system_greeting.value == "") system_greeting.value = GreetingMessage;
		if (system_email.value == "") system_email.value = DefaultEmail;
		if (system_confirm.value == "") system_confirm.value = ConfirmMessage;
		if ((system_heartbeat.value > 0) && (system_heartbeat.value < 5000)) system_heartbeat.value = 5000;
		gobutton.href = system_url.value;
		Make_Attachment_List();
		Display_System_Buttons();
	}

	//===========================================================================================
	function Make_Attachment_List() {
	//===========================================================================================
		Attachment_List = "";
		AttachTitleList = document.getElementsByClassName("system_attach_title");
		AttachURLList = document.getElementsByClassName("system_attach_url");
		var al = AttachTitleList.length;
		for (i = 0; i < al; i++) {
			if ((at = _Clean_Chars(AttachTitleList[i].value)) === "") continue;
			if ((au = _Clean_Chars(AttachURLList[i].value)) === "") continue;
			Attachment_List += (at + "=" + au + "|");
		}
		system_attach.value = Attachment_List;

		// Add a blank line if not already there
		if ((at !== "") && (au !== "")) Add_Attachment("", "").focus();
	}

	//===========================================================================================
	function Show_Attachment_List() {
	//===========================================================================================
		// Remove current list
		while (sat_body.lastChild) sat_body.removeChild(sat_body.lastChild);

		// Add the new list
		Attachment_Items = system_attach.value.split("|");
		for (i = 0; i < Attachment_Items.length; i++) {
			Attachment_Item_Parts = Attachment_Items[i].split("=");
			Add_Attachment(Attachment_Item_Parts[0], Attachment_Item_Parts[1]);
		}
	}

	//===========================================================================================
	function Add_Attachment(title, url) {
	//===========================================================================================
		if (url == undefined) url = "";

		// Create and add the row
		new_attach_row = document.createElement("tr");
		sat_body.appendChild(new_attach_row);

			// Create and add the first column and input field
			new_attach_title = document.createElement("td");
			new_attach_row.appendChild(new_attach_title);
				new_attach_i1 = document.createElement("input");
				new_attach_title.appendChild(new_attach_i1);
					new_attach_i1.value = title;
					new_attach_i1.className = "system_attach_title";

			// Create and add the second column and input field
			new_attach_url = document.createElement("td");
			new_attach_row.appendChild(new_attach_url);
				new_attach_i2 = document.createElement("input");
				new_attach_url.appendChild(new_attach_i2);
					new_attach_i2.type = "url";
					new_attach_i2.value = url;
					new_attach_i2.className = "system_attach_url";

			// Create and add the third column and button
			new_attach_url = document.createElement("td");
			new_attach_row.appendChild(new_attach_url);
			if (url != "") {
				new_attach_i3 = document.createElement("a");
				new_attach_url.appendChild(new_attach_i3);
					new_attach_i3.href = url;
					new_attach_i3.target = "_blank";
						new_attach_i3b = document.createElement("button")
						new_attach_i3.appendChild(new_attach_i3b);
							new_attach_i3b.innerHTML = "Verify";
							new_attach_i3b.className = "system_attach_button";
			}
		return(new_attach_i2);
	}

	//===========================================================================================
	function Restore_System_Data() {
	//===========================================================================================
		system_greeting.value = old_systemdata["SystemGreeting"];
		system_notice.value = old_systemdata["SystemNotice"];
		system_url.value = old_systemdata["SystemURL"];
		system_email.value = old_systemdata["SystemEmail"];
		system_confirm.value = old_systemdata["SystemConfirm"];
		system_attach.value = old_systemdata["SystemAttach"];
		system_heartbeat.value = old_systemdata["SystemHeartbeat"];
		Show_Attachment_List();
		Display_System_Buttons();
	}

	//===========================================================================================
	function Display_System_Buttons() {
	//	Displays the Save Changes button if current entries don't match the original
	//===========================================================================================
		system_change_flag = false;

		// Check for a change in the system data
		if (system_greeting.value != old_systemdata["SystemGreeting"]) system_change_flag = true; 
		if (system_notice.value != old_systemdata["SystemNotice"]) system_change_flag = true; 
		if (system_confirm.value != old_systemdata["SystemConfirm"]) system_change_flag = true; 
		if (system_attach.value != old_systemdata["SystemAttach"]) system_change_flag = true; 
		if (system_url.value != old_systemdata["SystemURL"]) system_change_flag = true; 
		if (system_email.value != old_systemdata["SystemEmail"]) system_change_flag = true; 
		if (system_heartbeat.value != old_systemdata["SystemHeartbeat"]) system_change_flag = true; 

		// Determine what buttons to show
		if (system_change_flag) {
			system_change.style.display = "inline";
			system_cancel.style.display = "inline";
		}
		else {
			system_change.style.display = "none";
			system_cancel.style.display = "none";
		}
	}

	//===========================================================================================
	function Build_Site_Address() {
	//	Builds a string of address information if needed for restoration later
	//	Also used to send to the database as a site information string
	//===========================================================================================
		// build an address string from the input fields
		new_sitedata["address"] = "";

		// Street address
		if ((! initialization_flag) && (site_address.value != "") && (site_address.value.match(/^[\w ]+$/) == null)) {
			alert("Street address is not in the correct format");
			return;
		}
		new_sitedata["address"] += site_address.value;

		// City ----------------------
		if ((! initialization_flag) && (site_city.value != "") && (site_city.value.match(/^[\w ]+$/) == null)) {
			alert("City is not in the correct format");
			return;
		}
		new_sitedata["address"] += "|" + site_city.value;

		// State ----------------------
		if ((! initialization_flag) && (site_state.value != "") && (site_state.value.match(/^[A-Za-z]{2}$/) == null)) {
			alert("State is not in the correct format");
			return;
		}
		new_sitedata["address"] += "|" + site_state.value.toUpperCase();

		// Zip Code ----------------------
		if ((! initialization_flag) && (site_zip.value != "") && (site_zip.value.match(/^\d{5}(-\d{4})?$/) == null)) {
			alert("Zip code is not in the correct format");
			return;
		}
		new_sitedata["address"] += "|" + site_zip.value;

		// Phone ----------------------
		if (! initialization_flag) {
			site_phone.title = "Enter phone number as a " + ((site_10dig.checked) ? "" : "7- or ") + "10-digit number with optional preceding \"1\"";
			results = _Verify_Phone(site_phone.value, false, site_10dig.checked);
			if (results[0] > 1) { // no entry is OK 
				alert(results[2]);
				return;
				}
			site_phone.value = results[1];
		}
		new_sitedata["address"] += "|" + site_phone.value;

		// Email ----------------------
		if (! initialization_flag) {
			results = _Verify_Email(site_email.value, "alert");
			if (results[0] == 1) { // no email
				site_sendemail.checked = false;
				Change_Site_Message();
			}
			if (results[0] > 1) return; // no email is OK
			site_email.value = results[1];
		}
		new_sitedata["address"] += "|" + site_email.value;

		// Website ----------------------
		if ((! initialization_flag) && (site_website.value != "") && (site_website.value.match(/^http[s]?:\/\/[\w\.\/\-]+$/) == null)) {
			alert("Website address is not in the correct format");
			return;
		}
		new_sitedata["address"] += "|" + site_website.value;

		// Contact -------------------
		new_sitedata["contact"] = site_contact.value;

		// Site Options ----------------
		new_sitedata["sumres"] = (site_sumres.checked) ? "checked" : "";
		new_sitedata["10dig"] = (site_10dig.checked) ? "checked" : "";
		new_sitedata["reminder"] = site_reminder.value;
		new_sitedata["lastrem"] = site_lastrem.value;

		// Open and Closed dates -------------------
		if ((site_closed.value == "") && (site_open.value < TodayDate)) site_open.value = TodayDate;
		if (site_closed.value < site_open.value) site_closed.value = site_open.value;
		new_sitedata["open"] = site_open.value;
		new_sitedata["closed"] = site_closed.value;

		// Internet site instructions -------------------
		new_sitedata["instructions"] = site_instructions.value;

		// Attachment list -------------------
		new_sitedata["attach"] = site_attach.value;

		Display_Site_Buttons();
		return (new_sitedata["address"]);
	}

	//===========================================================================================
	function Build_User_Data() {
	//	Builds an associative array of the initial values for a user for later restoration
	//===========================================================================================
		// build an address string from the input fields
		new_userdata["fail"] = false;

		// First Name ----------------------
		if ((! initialization_flag) && ((acc_first.value == "") || (acc_first.value.match(/^[A-Za-z\'\&\.\s\-\_]+$/) == null))) {
			alert("First name is required and is not in the correct format. Can only have letters, _, -, ', periods and spaces.");
			new_userdata["fail"] = true;
			return;
		}
		new_userdata["first"] = acc_first.value;

		// Last Name ----------------------
		if ((! initialization_flag) && ((acc_last.value == "") || (acc_last.value.match(/^[A-Za-z\s\'\&\.\-\_]+$/) == null))) {
			alert("Last name is required and is not in the correct format. Can only have letters, _, -, ', periods and spaces.");
			new_userdata = [];
			new_userdata["fail"] = true;
			return;
		}
		new_userdata["last"] = acc_last.value;

		// User Name ---------------------
		new_userdata["newname"] = "";
		if (acc_name.value == "") {
			Change_User_Name(); // create it
			// remember it was blank for later comparison for button display, but we will still display it
			new_userdata["newname"] = acc_name.value;
			alert ("The User Name was blank and a default one was created from the first and last names. You may change it if you wish."); 
		}
		// the default may have been changed, so check it
		if (acc_name.value.match(/^[\&\.\w\-]+$/) == null) {
			alert("User name is required and is not in the correct format. Can only have letters, numbers, _, or -.");
			new_userdata = [];
			new_userdata["fail"] = true;
			return;
		}
		// if it was blank, remember so a later test knows it got changed
		new_userdata["name"] = (new_userdata["newname"] == "") ?  acc_name.value : "";

		// Home ----------------------
		// No verification test necessary since it's selected from a list of sites			'
		new_userdata["home"] = acc_home.value;

		// Phone ----------------------
		if (! initialization_flag) {
			results = _Verify_Phone(acc_phone.value, "alert", <?php global $This10dig; echo "'$This10dig'"; ?>);
			if (results[0] > 1) { // no entry is OK
				new_userdata = [];
				new_userdata["fail"] = true;
				return;
			}
			acc_phone.value = results[1];
		}
		new_userdata["phone"] = acc_phone.value;

		// Email ----------------------
		if (! initialization_flag) {
			results = _Verify_Email(acc_email.value, "alert");
			if (results[0]) {
				new_userdata = [];
				new_userdata["fail"] = true;
				return;
			}
			acc_email.value = results[1];
		}
		new_userdata["email"] = acc_email.value;

		// Password ----------------------
		new_userdata["pass"] = acc_pass.value;
	}

	//===========================================================================================
	function Add_New_Site() {
	//===========================================================================================
		// Are any changes pending?
		Check_For_Changes("before adding a new site.");

		// Change display in preparation to add a site
		site_address.value = "";
		site_contact.value = "";
		site_others.checked = false;
		site_city.value = "";
		site_state.value = "";
		site_zip.value = "";
		site_phone.value = "";
		site_email.value = "";
		site_website.value = "";
		site_sendemail.checked = false;
		new_sitedata["address"] = ""; // clear saved address
		site_instructions.value = "";
		site_attach.value = "";
		Change_Site_Message(); // Closes message section
		site_sumres.checked = false;
		site_clients.checked = false;
		site_clients_cbonly.checked = false;
		site_clients_options.style.display = "none";
		Change_Other_Sites(''); // Closes inet section
		site_10dig.checked = true;
		site_clients_inet.style.border = "";
		new_sitedata["options"] = "";
		site_add.style.visibility = "hidden";
		site_add_flag = true;
		Resize_Div("New Site");
				
		Display_Site_Buttons();
		site_new_name.focus();
	}

	//===========================================================================================
	function Restore_Site_Address() {
	//===========================================================================================
		// Get address information for the currently selected site
		selected_address = old_sitedata["address"].split("|");

			site_current_name.value = old_sitedata["sitename"];
			site_add_flag = false;
			site_change_flag = false;
			
			// copy values from the selected address into the input fields
			switch (selected_address.length) {
				case 7: site_website.value = selected_address[6];
				case 6: site_email.value = selected_address[5];
				case 5: site_phone.value = selected_address[4];
				case 4: site_zip.value = selected_address[3];
				case 3: site_state.value = selected_address[2];
				case 2: site_city.value = selected_address[1];
				case 1: site_address.value = selected_address[0];
			}

		site_contact.value = old_sitedata["contact"];
	}

	//===========================================================================================
	function Change_Site_Message() {
	//===========================================================================================
		if (site_sendemail.checked) {
			if (site_email.value == "") {
				message = "You must have a site email to enable this option";
				alert(message);
				site_sendemail.checked = false;
				Change_Site_Message();
				return;
			}
			site_email_options.style.display =
			view_m_button.style.display = "inline";
			var msg = site_message.value;
			if (msg.substr(0, 4) == "NONE") msg = msg.substr(4);
			site_message.value = (msg) ? msg : "";
			site_email_options.style.display = "inline";
			site_email_optbox.style.border = "1px solid grey";
		}
		else {
			site_email_options.style.display =
			view_m_button.style.display = "none"; 
			var msg = site_message.value;
			if (msg.substr(0, 4) != "NONE") msg = "NONE" + site_message.value;
			site_message.value = msg;
			site_email_options.style.display = "none";
			site_email_optbox.style.border = "";
			site_reminder_option.checked = false;
		}
		
		if (site_reminder_option.checked) {
			if (site_reminder.value == "") site_reminder.value = 7;
			if (site_lastrem.value == "") site_lastrem.value = 14;
		}
		else {
			site_reminder.value = "";
			site_lastrem.value = "";
		}
		site_reminder.style.disabled = (site_reminder.value == "");
		site_lastrem.style.disabled = (site_lastrem.value == "");
	}


	// ============================================================================
	function Restore_Site_Attachments() {
	// ============================================================================
		siteAttList = document.getElementsByClassName("siteattcheckbox");
		if (siteAttList.length == 0) return;
		systAttList = SystemAttach.value.split("|");
		for (attindex = 0; attindex < systAttList.length - 1; attindex++) {
			sysTitle = systAttList[attindex].split("=")[0];
			siteAttList[attindex].checked = old_sitedata["attach"].includes(sysTitle);
		}
		Change_Site_Attachments();
	}

	// ============================================================================
	function Change_Site_Attachments() {
	//	attachContent is formatted for the "View" buttons
	// ============================================================================
		new_sitedata["attach"] = "";
		attachContent = "";
		var attachBreak = "";
		siteAttList = document.getElementsByClassName("siteattcheckbox");
		systAttList = SystemAttach.value.split("|");
		for (attindex = 0; attindex < siteAttList.length; attindex++) {
			if (siteAttList[attindex].checked) {
				systAttLine = _Show_Chars(systAttList[attindex], "text").split("=");
				new_sitedata["attach"] += (systAttLine[0] + "|");
				attachContent += (attachBreak + " - " + systAttLine[0] + " (" + systAttLine[1] + ")");
				attachBreak = "<br />"; // add a line break before the next attachment
			}
		}
		site_attach.value = new_sitedata["attach"];
	}

	//===========================================================================================
	function Restore_Site_Email() {
	//===========================================================================================
		//Restore the message
		site_message.value = old_sitedata["message"]
		site_sendemail.checked = (old_sitedata["message"].substr(0, 4) != "NONE");

		// Restore email options
		site_email_options.style.display =
		view_m_button.style.display = (site_sendemail.checked) ? "inline" : "none"; 
		site_email_optbox.style.border = (site_sendemail.checked) ? "1px solid grey" : "";

		// Restore reminders
		site_reminder_option.checked = old_sitedata["reminderoption"];
		site_reminder.value = old_sitedata["reminder"];
		site_lastrem.value = old_sitedata["lastrem"];
	}

	//===========================================================================================
	function Restore_Site_Options() {
	//===========================================================================================
		// Get address information for the currently selected site
		var selected_options = [];
		var site_clients_inetopts = [0,0];

		selected_options = old_sitedata["options"].split("|");
		site_clients_inetopts = selected_options[0].split(":");
		site_clients_limit.value = (site_clients_inetopts[1]) ? site_clients_inetopts[1] : 1;

		// show which internet boxes should be checked and/or disabled
		switch (site_clients_inetopts[0]) {
			case "S": // Schedule reservation even if callback list big
				site_clients_cbalways.checked = true;
				site_clients.checked = true;
				site_clients_options.style.display = "table-cell";
				break;
			case "R": // Restrict reservations if callback list big
				site_clients_cbrestrict.checked = true;
				site_clients.checked = true;
				site_clients_options.style.display = "table-cell";
				break;
			case "C": // Callback list only
				site_clients_cbonly.checked = true;
				site_clients.checked = true;
				site_clients_options.style.display = "table-cell";
				site_clients_limit.value = 0;
				break;
			case "N": // Cannot add to callback list
				site_clients_cbnone.checked = true;
				site_clients.checked = true;
				site_clients_options.style.display = "table-cell";
				break;
			default: // No internet scheduling allowed
				site_clients.checked = false;
				site_clients_options.style.display = "none";
		}

		// clear all site permission boxes
		for (j = 0; j < SiteIndex.length; j++) {
			jsite = SiteIndex[j];
			if (jsite < 2) continue; // skip undefined
			ossptr = document.getElementById("OSS_" + jsite);
			ossptr.innerHTML = "";
			site_others.checked = false;
		}
		// restore original site permission boxes
		for (j = 1; j < (selected_options.length); j++) {
			jsite = selected_options[j].split(":")[0];
			ossptr = document.getElementById("OSS_" + jsite);
			ossptr.innerHTML = checkboxYes;
			site_others.checked = true;
		}

		// Restore internet options
		site_open.value = old_sitedata["open"];
		site_closed.value = old_sitedata["closed"];
		// For sumres and 10dig, the checked text is used by PHP, don't save as T/F
		site_sumres.checked = (old_sitedata["sumres"]) ? "checked" : "" ;
		site_10dig.checked = (old_sitedata["10dig"]) ? "checked" : "" ;
		site_instructions.value = old_sitedata["instructions"];
		site_clients_options.style.display = (site_clients.checked) ? "inline" : "none";
		if (! site_clients.checked) site_clients_cbonly.checked = false;

		// Add borders to active option areas
		site_clients_inet.style.border = (site_clients.checked) ? "1px solid grey" : "";
		view_i_button.style.display = (site_clients.checked) ? "inline" : "none"; 
		site_others_access.style.border = (site_others.checked) ? "1px solid grey" : "";
	}

	//===========================================================================================
	function Change_Other_Sites(whichbox) {
	// whichbox is the id of the box that was changed
	// build the site option string:
	//     null or C or R or S ":" # appts "|" string of site IDs ":47" separated by "|"
	//===========================================================================================
		// Get address information for the currently selected site
		// Toggle the checkmark
		if (whichbox) {
			ossptr = document.getElementById(whichbox);
			if (ossptr.innerHTML == "") {
				ossptr.innerHTML = checkboxYes;
			}
			else ossptr.innerHTML = "";
		}

		// assure self-schedule option boxes are consistant
		view_i_button.style.display = (site_clients.checked) ? "inline" : "none"; 
		site_clients_options.style.display = (site_clients.checked) ? "inline" : "none";
		site_clients_inet.style.border = (site_clients.checked) ? "1px solid grey" : "";
		site_others_access.style.border = (site_others.checked) ? "1px solid grey" : "";
		if (! site_clients.checked) {
			site_clients_cbalways.checked = false;
			site_clients_cbnone.checked = false;
			site_clients_cbonly.checked = false;
			site_clients_cbrestrict.checked = false;
		}

		// prevent negative number for appoointment limit
		if (+site_clients_limit.value <= 0) site_clients_limit.value = 1;

		// build an option string from the input fields
		new_sitedata["options"] = "";
		if (site_clients_cbonly.checked) {
			new_sitedata["options"] = "C";
			site_clients_limit.value = 0; // cannot make appointments
		}
		else if (site_clients_cbalways.checked) new_sitedata["options"] = "S";
		else if (site_clients_cbnone.checked) new_sitedata["options"] = "N";
		else if (site_clients.checked) {
			new_sitedata["options"] = "R"; // default
			site_clients_cbrestrict.checked = true;
			}
		if (new_sitedata["options"] !== "") new_sitedata["options"] += ":" + site_clients_limit.value;

		if (site_others.checked) { // build the rest of the option string

			for (j = 0; j < SiteIndex.length; j++) {
				jsite = SiteIndex[j];
				if (jsite < 2) continue; // skip undefined
	
				ossptr = document.getElementById("OSS_" + jsite);
				siteid = (ossptr.innerHTML == checkboxYes) ? ALL_OPTIONS : 0;

				if (siteid) {
					new_sitedata["options"] += "|" + jsite + ":" + siteid;
				}
			}
		}

		Display_Site_Buttons();
	}

	//===========================================================================================
	function Display_Site_Buttons() {
	//	Displays the Save Changes button if current entries don't match the original	'
	//===========================================================================================
		site_change_flag = false;

		// Ignore changes if we are adding a new site or during initialization
		if ((! site_add_flag) && (! initialization_flag)) {

			// Check for a change in the site name
			if (site_current_name.value != old_sitedata["sitename"]) site_change_flag = true; 

			// Check for a change in the address data
			if ((old_sitedata["address"] != "") && (new_sitedata["address"] != old_sitedata["address"])) site_change_flag = true; 

			// Check for a change in the contact name
			if (new_sitedata["contact"] != old_sitedata["contact"]) site_change_flag = true; 

			// Check for a change in site options
			if (new_sitedata["sumres"] != old_sitedata["sumres"]) site_change_flag = true;
			if (new_sitedata["10dig"] != old_sitedata["10dig"]) site_change_flag = true;
			if (new_sitedata["reminder"] != old_sitedata["reminder"]) site_change_flag = true;
			if (new_sitedata["lastrem"] != old_sitedata["lastrem"]) site_change_flag = true;

			// Check for a change in the access option data
			if (new_sitedata["options"] != old_sitedata["options"]) site_change_flag = true; 

			// Check for a change in the email message
			if (site_message.value != old_sitedata["message"]) site_change_flag = true; 

			// Check for a change in the site internet access dates
			if (site_open.value != old_sitedata["open"]) site_change_flag = true; 
			if (site_closed.value != old_sitedata["closed"]) site_change_flag = true; 

			// Check for a change in the site instructions
			if (site_instructions.value != old_sitedata["instructions"]) site_change_flag = true;

			// Check for a change in the site attachments
			if (site_attach.value != old_sitedata["attach"]) site_change_flag = true;
		}

		// Determine what buttons to show
		var isUnassigned = (SiteForm.SiteCurrent.value == 1);
		if (site_add_flag) {
			site_selections.style.visibility = "hidden";
			site_new.style.display = "block";
			site_current.style.display = "none";
			site_address_block.style.display = "block";
			site_addit.style.display = "inline";
			site_change.style.display = "none";
			site_cancel.style.display = "inline";
			site_delete.style.display = "none";

		}
		else if (site_change_flag) {
			site_selections.style.visibility = "visible";
			site_new.style.display = "none";
			site_current.style.display = "inline";
			site_addit.style.display = "none";
			site_change.style.display = "inline";
			site_cancel.style.display = "inline";
			site_delete.style.display = (isUnassigned) ? "none" : "inline";
		}
		else {
			site_selections.style.visibility = "visible"
			site_new.style.display = "none";
			site_current.style.display = "inline";
			site_address_block.style.display = (isUnassigned) ? "none" : "block";
			site_addit.style.display = "none";
			site_change.style.display = "none";
			site_cancel.style.display = "none";
			site_delete.style.display = (isUnassigned) ? "none" : "inline";
		}
		osite_list.style.display = (site_others.checked) ?  "block" : "none";

	}

	//===========================================================================================
	function View_Message() {
	// Displays the email message a user would recieve
	//===========================================================================================
 		// convert text to HTML
		vm = _Clean_Chars(site_message.value);
		vm = _Show_Chars(vm, "html");
		vmdate = "1/1/" + (+TodayDate.substr(0,4) + 1);

		// replace shortcodes
		vm = vm.replace(/\[TPNAME\]/g,      "Jack & Jill Taxpayer");
		vm = vm.replace(/\[TIME\]/g,        "2:30 am");
		vm = vm.replace(/\[DATE\]/g,        vmdate);
		vm = vm.replace(/\[SITENAME\]/g,    site_current_name.value);
		vm = vm.replace(/\[STATESITE\]/g,   "<?php echo $_SESSION['SystemURL']; ?>");
		vm = vm.replace(/\[ADDRESS\]/g,     site_address.value);
		vm = vm.replace(/\[CONTACT\]/g,     site_contact.value);
		vm = vm.replace(/\[CITY\]/g,        site_city.value);
		vm = vm.replace(/\[STATE\]/g,       site_state.value);
		vm = vm.replace(/\[ZIP\]/g,         site_zip.value);
		vm = vm.replace(/\[PHONE\]/g,       site_phone.value);
		vm = vm.replace(/\[EMAIL\]/g,       site_email.value);
		vm = vm.replace(/\[WEBSITE\]/g,     site_website.value);
		vm = vm.replace(/\[ATTACHMENTS\]/g, attachContent);
		var system_list = system_attach.value.split("|");
		for (lax = 0; lax < system_list.length-1; lax++) {
			sap = system_list[lax].split("=");
			testShortcode = "[" + sap[0] + "]";
			replacement = sap[0] + " (" + sap[1] + ")";
			vm = vm.replace(testShortcode, replacement);
		}

		Show_It(vm);
	}

	//===========================================================================================
	function View_Instructions($how) {
	// Displays the instructions as presented on the appointment selection screen
	//===========================================================================================
 		// convert text to HTML
		vm = _Clean_Chars(site_instructions.value);
		vm = _Show_Chars(vm, "html");

		// replace shortcodes
		if (vm == "") vm = "(No message to view)";
		vm = vm.replace(/\[TPNAME\]/g,      "Jack & Jill Taxpayer");
		vm = vm.replace(/\[TIME\]/g,        "");
		vm = vm.replace(/\[DATE\]/g,        "");
		vm = vm.replace(/\[SITENAME\]/g,    site_current_name.value);
		vm = vm.replace(/\[STATESITE\]/g,   "<?php echo $_SESSION['SystemURL']; ?>");
		vm = vm.replace(/\[ADDRESS\]/g,     site_address.value);
		vm = vm.replace(/\[CONTACT\]/g,     site_contact.value);
		vm = vm.replace(/\[CITY\]/g,        site_city.value);
		vm = vm.replace(/\[STATE\]/g,       site_state.value);
		vm = vm.replace(/\[ZIP\]/g,         site_zip.value);
		vm = vm.replace(/\[PHONE\]/g,       site_phone.value);
		vm = vm.replace(/\[EMAIL\]/g,       site_email.value);
		vm = vm.replace(/\[WEBSITE\]/g,     site_website.value);
		vm = vm.replace(/\[ATTACHMENTS\]/g, attachContent);
		var system_list = system_attach.value.split("|");
		for (lax = 0; lax < system_list.length-1; lax++) {
			sap = system_list[lax].split("=");
			testShortcode = "[" + sap[0] + "]";
			replacement = sap[0] + " (" + sap[1] + ")";
			vm = vm.replace(testShortcode, replacement);
		}
		Show_It(vm);
	}
	//===========================================================================================
	function Show_It(content) {
	//	Show the content in a new window
	//===========================================================================================
		Roster = window.open("","","menubar=1, scrollbars=1, resizeable=1, height=200, width=" + screen.width/2);
		Roster.document.writeln("<!DOCTYPE html>");
		Roster.document.writeln("<head>");
		Roster.document.writeln("<style>");
		Roster.document.writeln("body {width: 8in;}");
		Roster.document.writeln("</style>");
		Roster.document.writeln("</head>");
		Roster.document.writeln("<body>");
		Roster.document.writeln(content);
		Roster.document.writeln("</body>");
	}

	//===========================================================================================
	function Restore_User_Data() {
	//===========================================================================================
		// Restore old data
		acc_first.value = old_userdata["first"];
		acc_last.value = old_userdata["last"];
		acc_name.value = old_userdata["name"] + old_userdata["newname"]; // One's a ""
		acc_home.value = old_userdata["home"];
		acc_email.value = old_userdata["email"];
		acc_phone.value = old_userdata["phone"];
		acc_pass.value = old_userdata["pass"];

		new_user_options = old_user_options;

		<?php global $isAdministrator;
		if ($isAdministrator) {
			echo "acc_home.disabled = false;\n";
		}
		else {
			echo "acc_home.disabled = ((site_name.value != acc_home.value) && (acc_home.value != 1)) ? true : false;\n";
		}
		?>

		Display_User_Buttons();
	}

	//===========================================================================================
	function Change_User_Name() {
	//	Called when a user's first or last name is changed to create a short default "user name"
	//	The user name can then be changed if desired
	//===========================================================================================
		// remove spaces and use first name and first letter of last name
		if (acc_name.value == "") {
			if ((acc_first.value == "") || (acc_last.value == "")) return; // can't make the name
			t1 = acc_first.value.replace(/[\'\s]/g,""); // get rid of blanks, periods and apostrophes
			t2 = acc_last.value.replace(/[\'\s]/g,"").charAt(0);
			acc_name.value = t1 + t2;
		}
		Display_User_Buttons();
	}

	//===========================================================================================
	function Change_User_Role(cellid) {
	//	Called when a user's role option is changed
	//===========================================================================================
		// changing roles causes a second pass through this function
		if (user_role_change_flag) { user_role_change_flag = false; return; }
		user_role_change_flag = true;


		cellptr = document.getElementById(cellid);
		cellsplit = cellid.split("_");
		celltype = cellsplit[0];
		SiteForm.UserCurrent.value = uid = cellsplit[1];
		oldrole = cellptr.innerHTML;

		<?php global $isAdministrator;
		if (! $isAdministrator) {
			echo "if (oldrole == 'Administrator') {\n";
			echo "alert('You can\'t change an Administrator\'s role.');\n";
			echo "return;\n";
			echo "}\n";
			echo "if ((oldrole == 'Appt Manager') && (AppointmentManagers.length == 1)) {\n";
			echo "alert('You can\'t remove the only Appt Manager for this site.\\n\\nAdd a new one first.');\n";
			echo "return;\n";
			echo "}\n";
		}
		else {
			echo "if ((oldrole == 'Administrator') && (Administrators.length == 1)) {\n";
			echo "alert('You can\'t remove the only administrator');\n";
			echo "return;\n";
			echo "}\n";
		}
		?>

		if ((oldrole == "Administrator") || (oldrole == "Appt Manager")) {
			reloadflag = true;
			//var cellNAME = document.getElementById("NAME_" + uid);
			//var uname = cellNAME.innerHTML;
			//cellNAME.innerHTML = uname.substr(0, uname.length - 2);
		}
		
		switch (celltype) {
			case "ROLE":
				// get current role
				roleA = "Administrator";
				roleA = (oldrole == roleA) ? ("<b>" + roleA + "</b>") : roleA;
				roleM = "Appt Manager";
				roleM = (oldrole == roleM) ? ("<b>" + roleM + "</b>") : roleM;
				roleS = "Scheduler";
				roleS = (oldrole == roleS) ? ("<b>" + roleS + "</b>") : roleS;
				roleN = "None";
				roleN = (oldrole == "") ? ("<b>" + roleN + "</b>") : roleN;

				// clear the text and make a list of options to click
				cellptr.innerHTML = "";
				
				<?php global $isAdministrator; if ($isAdministrator) { // only Admins can change this
					echo "var cellHOME = document.getElementById('HOME_' + uid);\n";
					echo "if (cellHOME.innerHTML == SiteForm.SiteCurrent.value) {\n";
					echo "\tvar opt = document.createElement('span');\n";
					echo "\topt.id = cellid + '_Administrator';\n";
					echo "\topt.onclick = function() { Set_User_Role(this.id) }\n";
					echo "\topt.innerHTML = roleA + '<br />';\n";
					echo "cellptr.appendChild(opt);\n";
					echo "}\n";
				}
				?>

				var opt = document.createElement("span");
				opt.id = cellid + "_Appt Manager";
				opt.onclick = function() { Set_User_Role(this.id) };
				opt.innerHTML = roleM + "<br />";
				cellptr.appendChild(opt);

				var opt = document.createElement("span");
				opt.id = cellid + "_Scheduler";
				opt.onclick = function() { Set_User_Role(this.id) };
				opt.innerHTML = roleS + "<br />";
				cellptr.appendChild(opt);

				var opt = document.createElement("span");
				opt.id = cellid + "_None";
				opt.onclick = function() { Set_User_Role(this.id) };
				opt.innerHTML = roleN;
				cellptr.appendChild(opt);

				break;
		}
	}

	//===========================================================================================
	function Set_User_Role(cellid) {
	//	Called when a user's role option is selected
	//===========================================================================================
		// What cell was clicked and who is the user
		cellptr = document.getElementById(cellid);
		//if (cellptr.className == "change_user_no") return;

		// Get needed info from the cellid coding
		cellsplit = cellid.split("_");
		celltype = cellsplit[0];
		SiteForm.UserCurrent.value = uid = cellsplit[1];
		newrole = cellsplit[2];
		
		// Get the cell pointers
		var cellNAME = document.getElementById("NAME_" + uid);
		var cellROLE = document.getElementById("ROLE_" + uid);
		var cellVCBB = document.getElementById("VCBB_" + uid);
		var cellCCBB = document.getElementById("CCBB_" + uid);
		var cellVAPP = document.getElementById("VAPP_" + uid);
		var cellCAPP = document.getElementById("CAPP_" + uid);
		var cellURES = document.getElementById("URES_" + uid);
		var cellHOME = document.getElementById("HOME_" + uid);

		switch (celltype) {
			case "ROLE":
				// remove option nodes put there by Change_User_Roles()
				while (cellROLE.hasChildNodes()) cellROLE.removeChild(cellROLE.firstChild);

				// show the new role
				cellROLE.innerHTML = newrole;

				// adjust the spcific option cells that follow
				cellclass = "change_user_yes";
				checkmark = checkboxYes;
				switch (newrole) {
					case "None":
						newrole = "";
						checkmark = checkboxNo;
						// no break;
					case "Scheduler":
						cellclick = true;
						break;
					case "Appt Manager":
						optcode = "M";
						cellclass = "change_user_no";
						cellclick = false;
						//cellNAME.innerHTML += " " + MFlag;
						reloadflag = true;
						break;
					case "Administrator":
						optcode = "A";
						cellclass = "change_user_no";
						cellclick = false;
						//cellNAME.innerHTML += " " + AFlag;
						reloadflag = true;
						break;
				}
				cellCCBB.innerHTML = checkmark;
				//cellCCBB.className = cellclass;
				cellCCBB.onclick = (cellclick) ? function() { Set_User_Role(this.id) } : "";
				cellCCBB.style.cursor = (cellclick) ? "pointer" : "not-allowed";
				cellCCBB.disabled = (cellclick) ? false : true;

				cellVCBB.innerHTML = checkmark;
				//cellVCBB.className = cellclass;
				cellVCBB.onclick = (cellclick) ? function() { Set_User_Role(this.id) } : "";
				cellVCBB.style.cursor = (cellclick) ? "pointer" : "not-allowed";
				cellVCBB.disabled = (cellclick) ? false : true;

				cellCAPP.innerHTML = checkmark;
				//cellCAPP.className = cellclass;
				cellCAPP.onclick = (cellclick) ? function() { Set_User_Role(this.id) } : "";
				cellCAPP.style.cursor = (cellclick) ? "pointer" : "not-allowed";
				cellCAPP.disabled = (cellclick) ? false : true;

				cellVAPP.innerHTML = checkmark;
				//cellVAPP.className = cellclass;
				cellVAPP.onclick = (cellclick) ? function() { Set_User_Role(this.id) } : "";
				cellVAPP.style.cursor = (cellclick) ? "pointer" : "not-allowed";
				cellVAPP.disabled = (cellclick) ? false : true;
				
				cellURES.innerHTML = checkmark;
				//cellURES.className = cellclass;
				cellURES.onclick = (cellclick) ? function() { Set_User_Role(this.id) } : "";
				cellURES.style.cursor = (cellclick) ? "pointer" : "not-allowed";
				cellURES.disabled = (cellclick) ? false : true;
				
				break;

			case "CAPP":
			case "CCBB":
			case "VCBB":
			case "VAPP":
			case "URES":
				// toggle the marker
				cellptr.innerHTML = (cellptr.innerHTML == checkboxNo) ?	checkboxYes : checkboxNo;
		}

		// Check consistency: to change, must also be able to view
		viewclick = true;
		if (cellCCBB.innerHTML == checkboxYes) {
			cellVCBB.innerHTML = checkboxYes;
			viewclick = false;
		}
		cellVCBB.onclick = (viewclick) ? function() { Set_User_Role(this.id) } : "";
		cellVCBB.style.cursor = (viewclick) ? "pointer" : "not-allowed";
		cellVCBB.style.color = (viewclick) ? "black" : "grey";
		cellVCBB.disabled = (viewclick) ? false : true;

		viewclick = true;
		uresclick = false;
		if (cellCAPP.innerHTML == checkboxYes) {
			cellVAPP.innerHTML = checkboxYes;
			if (celltype == "CAPP") cellURES.innerHTML = checkboxYes;
			viewclick = false;
			uresclick = true;
		}
		else {
			cellURES.innerHTML = checkboxNo;
		}
		cellVAPP.onclick = (viewclick) ? function() { Set_User_Role(this.id) } : "";
		cellVAPP.style.cursor = (viewclick) ? "pointer" : "not-allowed";
		cellVAPP.style.color = (viewclick) ? "black" : "grey";
		cellVAPP.disabled = (viewclick) ? false : true;

		cellURES.onclick = (uresclick) ? function() { Set_User_Role(this.id) } : "";
		cellURES.style.cursor = (uresclick) ? "pointer" : "not-allowed";
		cellURES.style.color = (uresclick) ? "black" : "grey";
		cellURES.disabled = (uresclick) ? false : true;

		// Compute the option code for this user
		if (cellROLE.innerHTML == "Administrator") optcode = "A";
		else if (cellROLE.innerHTML == "Appt Manager") optcode = "M";
		else {
			optcode = 0;
			if (cellVCBB.innerHTML == checkboxYes) optcode |= VIEW_CB;
			if (cellCCBB.innerHTML == checkboxYes) optcode |= ADD_CB;
			if (cellVAPP.innerHTML == checkboxYes) optcode |= VIEW_APP;
			if (cellCAPP.innerHTML == checkboxYes) optcode |= ADD_APP;
			if (cellURES.innerHTML == checkboxYes) optcode |= USE_RES;
			cellROLE.innerHTML = (optcode) ? "Scheduler" : "";
		}

		// Send it to the database
		Send_Option_Update(cellHOME.innerHTML, uid, optcode);
	}

	//===========================================================================================
	function Send_Option_Update(usite, uid, useroptions) {
	//===========================================================================================
		// using AJAX to submit the change vs a save change button
		var xmlhttp = new XMLHttpRequest();
		xmlhttp.onreadystatechange = function() {
           		if (this.readyState == 4 && this.status == 200) {
				if (this.responseText != "") {
					alert(this.responseText);
					reloadflag = true;
					Action_Request("SwitchSite");
				}
				else {
					if (reloadflag) Action_Request("SwitchSite");
				}
			}
		}
		q = usite + "_" + uid + "_" + useroptions;
		xmlhttp.open("GET", "changeopts.php?q=" + q, true);
		xmlhttp.send();
	}

	//===========================================================================================
	function Change_User_Options() {
	//===========================================================================================
		// Get address information for the currently selected site
		<?php global $isAdministrator;
		if ($isAdministrator) {
			echo "acc_home.disabled = false;\n";
		}
		else {
			echo "acc_home.disabled = ((site_name.value != acc_home.value) && (acc_home.value != 1)) ? true : false;\n";
		}
		?>

		Display_User_Buttons();
	}

	//===========================================================================================
	function Add_User_Info() {
	//===========================================================================================
		// Are any changes pending?
		if (user_change_flag) {
			message = "Click OK to save changes to ";
			message += old_userdata["first"] + " " + old_userdata["last"]; 
			message += " before adding a new user.\n\n";
			message += "If you click cancel, changes will be lost";
			if (confirm(message)) {
				Action_Request('ChangeUser');
				return;
				}
		}

		// Clear existing fields
		new_user_header.style.display = "block";
		old_user_header.style.display = "none";
		user_information.style.display = "block";
		appt_tp.style.display = "none";
		acc_first.disabled = false;
		acc_last.disabled = false;
		acc_name.disabled = false;
		acc_email.disabled = false;
		acc_first.value = "";
		acc_last.value = "";
		acc_name.value = "";
		acc_email.value = "";
		acc_phone.value = "";
		acc_pass.value = "";
		sendemail.style.display = "none";

		// Default options to Scheduler with full access at current site
		si = site_name.options[site_name.selectedIndex].value;
		acc_home.value = si;
		new_user_role = "S";
		Change_User_Options();

		// Other niceties
		user_add_flag = true;
		Display_User_Buttons();
		acc_first.focus();
	}

	//===========================================================================================
	function Display_User_Buttons() {
	//	Displays the Save Changes button if current entries don't match the original
	//===========================================================================================
		user_change_flag = false;

		// Ignore changes if we are adding a new user
		if (! user_add_flag) {
			// Check for a change in the user name, etc
			if (acc_first.value != old_userdata["first"]) user_change_flag = true; 
			if (acc_last.value != old_userdata["last"]) user_change_flag = true; 
			if (acc_name.value != old_userdata["name"]) user_change_flag = true; 
			if (acc_home.value != old_userdata["home"]) user_change_flag = true; 
			if (acc_email.value != old_userdata["email"]) user_change_flag = true; 
			if (acc_phone.value != old_userdata["phone"]) user_change_flag = true; 
			if (acc_pass.value != old_userdata["pass"]) user_change_flag = true; 
			if (maketp.checked || makeappt.checked) user_change_flag = true; 
		}

		// Determine what buttons to show
		if (user_add_flag) {
			user_addit.style.display = "inline";
			user_change.style.display = "none";
			user_cancel.style.display = "inline";
			user_delete.style.display = "none";
			user_close.style.display = "none";
		}
		else if (user_change_flag) {
			user_addit.style.display = "none";
			user_change.style.display = "inline";
			user_cancel.style.display = "inline";
			user_delete.style.display = "inline";
			user_close.style.display = "none";
		}
		else {
			user_addit.style.display = "none";
			user_change.style.display = "none";
			user_cancel.style.display = "none";
			user_delete.style.display = "inline";
			user_close.style.display = "inline";
		}
		user_delete.style.display = (user_add_flag || acc_home.disabled) ? "none" : "inline";
	}

	//===========================================================================================
	function Show_User(show, id) { // displays or hides the user administration window
	// show		user's database index, if 0 close the user window
	// id		id of the source of the request
	//===========================================================================================
		//if (user_change_flag) return;
		//
		// If this is a search, switch to the Schedulers or Taxpayers tab
		if ((id == "Schedulers") || (id == "Taxpayers")) ShowPage(id);

		// Show the appropriate header
		new_user_header.style.display = "none";
		old_user_header.style.display = "block";

		// Request the user's data or close the window
		if (show) {
			SiteForm.UserCurrent.value = show;
			Action_Request("ShowUser");
		}
		else user_information.style.display = "none";
	}

	//===========================================================================================
	function Sort_INet(dbitem) {
	//	dbitem = the database item on which to sort
	//===========================================================================================
		UserSort.value = dbitem;
		Action_Request("SortUser");
	}

	//===========================================================================================
	function Delete_TP(type,uid) {
	// type = "TP" for a single TP
	//	= "DATE" for TPs earlier than the given date
	// uid = the db's user index
	//===========================================================================================
		switch (type) {
			case "TP":
				// emulate the Show_User function which calls the DB
				acc_first.value = document.getElementById("TPNAME_" + uid).innerHTML;
				acc_email.value = document.getElementById("TPEMAIL_" + uid).innerHTML;
				acc_last.value = "";
				old_user_options = "";
				SiteForm.UserCurrent.value = uid;
				Action_Request("DeleteUser"); // emulate clicking the Delete button
				break;
			case "DATE":
				// not yet implemented
		}
	}	

	//===========================================================================================
	function Check_For_Changes(messageReason) {
	//===========================================================================================
		if (initialization_flag) return; // Can't have changes yet, but flags may be set

		if (site_change_flag) {
			message = "Click OK to save changes to " + site_current_name.value; 
			message += " " + messageReason + ".\n\n";
			message += "If you click cancel, changes will be lost";
			if (confirm(message)) Action_Request("ChangeSite");
			else Action_Request("CancelSite");
			site_change_flag = false;
		}

		if (site_add_flag) {
			message = "Click OK to save changes to " + site_new_name.value; 
			message += " " + messageReason + ".\n\n";
			message += "If you click cancel, changes will be lost";
			if (confirm(message)) Action_Request("AddSite");
			else Action_Request("CancelSite");
			site_add_flag = false;
		}

		if (user_change_flag) {
			message = "Click OK to save changes to ";
			message += old_userdata["first"] + " " + old_userdata["last"]; 
			message += " " + messageReason + "\n\n";
			message += "If you click cancel, changes will be lost";
			if (confirm(message)) Action_Request("ChangeSite");
			else Action_Request('CancelUser');
			user_change_flag = false;
		}

		if (system_change_flag) {
			message = "Click OK to save changes you made to the system.";
			message += " " + messageReason + "\n\n";
			message += "If you click cancel, changes will be lost";
			if (confirm(message)) Action_Request("ChangeSystem");
			else Action_Request('CancelSystem')
			system_change_flag = false;
		}
	}

	//===========================================================================================
	function Action_Request(action) {
	//===========================================================================================
		switch (action) {

			case "AddSite":
				if (site_new_name.value == "") {
					alert("Please enter a site name.");
					return;
				}
				scn = site_new_name.value.replace(/\'/g,"").replace(/\"/g,"");
				if (scn !== site_new_name.value) {
					alert("Please don't use quotes or apostrophes in the site name.");
					return;
				}
				//Check_For_Changes("before adding a new site."); // check done earlier
				SiteForm.Site1Name.value = site_new_name.value;

				Build_Site_Address();
				SiteForm.Site1Address.value = new_sitedata["address"];
				SiteForm.SiteContact.value = new_sitedata["contact"];
				SiteForm.SiteSumres.value = new_sitedata["sumres"];
				SiteForm.Site10dig.value = new_sitedata["10dig"];
				SiteForm.SiteMessage.value = _Clean_Chars(site_message.value);
				SiteForm.SiteReminder.value = new_sitedata["reminder"];
				SiteForm.SiteLastRem.value = new_sitedata["lastrem"];
				SiteForm.SiteOptions.value = new_sitedata["options"];
				SiteForm.SiteOpen.value = new_sitedata["open"];
				SiteForm.SiteClosed.value = new_sitedata["closed"];
				SiteForm.SiteInstructions.value = _Clean_Chars(site_instructions.value);
				SiteForm.SiteAttach.value = new_sitedata["attach"];
				break;

			case "ChangeSite":
				if (site_current_name.value == "") {
					alert("Please enter a site name.");
					return;
				}
				scn = site_current_name.value.replace(/\'/g,"").replace(/\"/g,"");
				if (scn !== site_current_name.value) {
					alert("Please don't use quotes or apostrophes in the site name.");
					return;
				}
				SiteForm.Site1Name.value = site_current_name.value;
				SiteForm.Site1Address.value = new_sitedata["address"];
				SiteForm.SiteContact.value = new_sitedata["contact"];
				SiteForm.SiteSumres.value = new_sitedata["sumres"];
				SiteForm.Site10dig.value = new_sitedata["10dig"];
				SiteForm.SiteMessage.value = _Clean_Chars(site_message.value);
				SiteForm.SiteReminder.value = new_sitedata["reminder"];
				SiteForm.SiteLastRem.value = new_sitedata["lastrem"];
				SiteForm.SiteOptions.value = new_sitedata["options"];
				SiteForm.SiteOpen.value = new_sitedata["open"];
				SiteForm.SiteClosed.value = new_sitedata["closed"];
				SiteForm.SiteInstructions.value = _Clean_Chars(site_instructions.value);
				SiteForm.SiteAttach.value = new_sitedata["attach"];
				break;

			case "SwitchSite":
				Check_For_Changes("before changing to another site.");
				// Don't change site if the current one is selected unless reload is specified
				if ((! reloadflag) && (SiteForm.SiteCurrent.value == site_name.value)) return;

				// Set up for the newly selected site
				SiteForm.SiteCurrent.value = site_name.value;
				SiteForm.UserCurrent.value = acc_user_list.innerHTML;
				break;

			case "AddUser":
				Check_For_Changes("before adding a new user.");
				Build_User_Data(); // verify input
				if (new_userdata["fail"]) return; // already got an error message
				SiteForm.UserFirst.value = _Clean_Chars(acc_first.value);
				SiteForm.UserLast.value = _Clean_Chars(acc_last.value);
				SiteForm.UserName.value = acc_name.value;
				SiteForm.UserHome.value = (acc_home.value == 1) ? 0 : acc_home.value;
				SiteForm.UserEmail.value = acc_email.value;
				SiteForm.UserPhone.value = acc_phone.value;
				SiteForm.UserPass.value = acc_pass.value;
				break;

			case "ChangeUser":
				Build_User_Data(); // verify input
				if (new_userdata["fail"]) return; // got an error message
				SiteForm.UserFirst.value = _Clean_Chars(acc_first.value);
				SiteForm.UserLast.value = _Clean_Chars(acc_last.value);
				SiteForm.UserName.value = acc_name.value;
				// is this a taxpayer or a scheduler to be moved to be a taxpayer?
				if (((SiteForm.SiteView.value == "Taxpayers") || maketp.checked) && (! makeappt.checked)) {
					SiteForm.UserAppt.value = acc_home.value;
					SiteForm.UserHome.value = 0;
				}
				else {
					SiteForm.UserAppt.value = 0;
					SiteForm.UserHome.value = acc_home.value;
				}
				SiteForm.UserEmail.value = acc_email.value;
				SiteForm.UserPhone.value = acc_phone.value;

				// Change password to just the first part
				passPart = acc_pass.value.split(Dagger);
				acc_pass.value = (passPart[0] != "") ? passPart[0] : (passPart[1] ?? "") ;
				SiteForm.UserPass.value = acc_pass.value;
				break;

			case "ViewUser":
				Check_For_Changes("before changing to a different user.");
				SiteForm.UserCurrent.value = acc_user_list.innerHTML;
				break;

			case "FindUser":
				if (FindByPhone.checked) {
					action = "FindByPhone";
					UserPhone.value = FindByVal.value;
					break;
					}
				if (FindByEmail.checked) {
					action = "FindByEmail";
					UserEmail.value = FindByVal.value;
					break;
					}
				if (FindByName.checked) {
					action = "FindByName";
					UserName.value = FindByVal.value;
					break;
					}
				return; // Not a valid option

			case "SortUser":
				break;

			case "DeleteSite":
				<?php global $isAdministrator, $isManager;
				if ($isAdministrator OR $isManager) {
					echo "if (SiteForm.SiteCurrent.value == " . $_SESSION["User"]["user_home"] . ") {\n";
						echo "alert(\"You cannot remove this site. Change your home site first, then log out and log back in again.\");";
						echo "return;\n}\n";
				};
				?>
				message = "OK to totally remove " + old_sitedata["sitename"] + "?\n\n";
				message += "This will also remove all associated people!";
				if (! confirm (message)) return; // didn't say OK
				SiteForm.Site1Name.value = site_current_name.value;
				break;

			case "DeleteUser":
				<?php global $isAdministrator, $UserFirst, $UserLast;
				if ($isAdministrator) {
					echo "if ((old_user_options == 'M') && (AppointmentManagers.length < 2)) {\n";
						echo "message = 'Are you sure you want to remove ' + acc_first.value + ' ' + acc_last.value + ' as a site appointment manager before another has been assigned that position!';\n";
						echo "if (! confirm (message)) {\n";
							echo "Restore_User_Data();\n";
							echo "return;\n";
						echo "}\n";
					echo "}\n\n"; // end of 'M' test

					// additional test for administrators
					echo "if ((SiteForm.UserCurrent.value == " . $_SESSION["User"]["user_index"] . ") && (Administrators.length < 2)) {\n";
						echo "message = 'You cannot remove yourself as an administrator until another has been assigned that position!';\n";
						echo "alert (message);\n";
						echo "Restore_User_Data();\n";
						echo "return;\n";
					echo "}\n\n";
				}

				else { // $isAppointmentManager
					echo "if ((old_user_options == 'M') && (AppointmentManagers.length < 2)) {\n";
						echo "message = 'You cannot remove ' + acc_first.value + ' ' + acc_last.value + ' as a site appointment manager until another has been assigned that position!';\n";
						echo "alert (message);\n";
						echo "Restore_User_Data();\n";
						echo "return;\n";
					echo "}\n\n";
				}
				?>
				message = "OK to totally remove " + acc_first.value + " " + acc_last.value + "?";
				if (! confirm (message)) return; // didn't say OK
				SiteForm.UserEmail.value = acc_email.value;
				break;

			case "DeleteUserByDate":
				if (inet_delete_date.value == "") {
					alert("Please specify a date.");
					return;
				}
				message = "OK to totally remove all your taxpayers who have not logged in since ";
				message = message + inet_delete_date.value + "?";
				if (! confirm (message)) return; // didn't say OK
				SiteForm.UserCurrent.value = inet_delete_date.value;
				break;

			case "CancelSite":
				for (j in old_sitedata) new_sitedata[j] = old_sitedata[j];
				site_add_flag = false;
				site_change_flag = false;
				site_add.style.visibility = "visible";
				Restore_Site_Address();
				Restore_Site_Email();
				Restore_Site_Options();
				Restore_Site_Attachments();
				Display_Site_Buttons();
				return;

			case "CancelUser":
				Restore_User_Data();
				user_add_flag = false;
				user_change_flag = false;
				user_information.style.display = "none";
				return;

			case "SignOut":
				if (user_add_flag || user_change_flag || system_change_flag || site_change_flag) {
					alert("Please either save or cancel changes before leaving.");
					return;
				}
				break;

			case "GoToAppointments":
				if (user_add_flag || user_change_flag || system_change_flag || site_change_flag) {
					alert("Please either save or cancel changes before leaving.");
					return;
				}
				SiteForm.action = "appointment.php"; // submits the form to this module
				break;

			case "ChangeSystem":
				results = _Verify_Email(system_email.value, "alert");
				if (results[0]) return;
				SiteForm.SystemEmail.value = system_email.value;
				SiteForm.SystemHeartbeat.value = system_heartbeat.value;
				SiteForm.SystemGreeting.value = _Clean_Chars(system_greeting.value);
				SiteForm.SystemNotice.value = _Clean_Chars(system_notice.value);
				SiteForm.SystemConfirm.value = _Clean_Chars(system_confirm.value);
				SiteForm.SystemAttach.value = _Clean_Chars(system_attach.value);
				SiteForm.SystemURL.value = system_url.value;
				system_change_flag = false;
				break;

			case "CancelSystem":
				Restore_System_Data();
				system_change_flag = false;
				break;

			case "ShowUser":
				Check_For_Changes("before changing to a new user.");
				action = "ViewUser";
				break;

			case "StartTrace":
			case "StopTrace":
			case "ViewTrace":
			case "ClearTrace":
				break;

			default:
				alert("Invalid request");
				return;
		}
	SiteForm.SiteAction.value = action;
	SiteForm.submit();
	}

	//===========================================================================================
	function Print_Roster() {
	//===========================================================================================
		Show_It("<table style=\"white-space:nowrap\"><?php echo $ManagerRoster ?></table>");
	}

	//===========================================================================================
	function Show_History() {
	//===========================================================================================
		change_history.style.display = (change_history.style.display == 'none') ? 'block' : 'none';
	}

	//===========================================================================================
	function Show_SearchBox(ID) {
	//	ID	element ID which called the function
	//===========================================================================================
		switch (ID) {
			case "FindByName":
			case "SearchApptName":
				FindByName.checked = true;
				break;
			case "FindByEmail":
			case "SearchApptEmail":
				FindByEmail.checked = true;
				break;
			case "FindByPhone":
			case "SearchAppt":
			case "SearchApptPhone":
			default:
				FindByPhone.checked = true;
		}
		FindByVal.placeholder = (FindByPhone.checked) ? "333-555-1234" : "";
		user_search_box.style.visibility = "visible";
		FindByVal.focus();
	}
	
	//===========================================================================================
	function Show_Matches() {	// show or hide other site matches
	//===========================================================================================
		matchSet = document.getElementsByClassName("user_matchType");
		for (j = 0; j < matchSet.length; j++) {
			matchSet[j].style.display = (showMine.checked) ? "none" : "table-row";
		}
	}

	//===========================================================================================
	function Resize_All_Divs() {	// show or hide other site matches
	//===========================================================================================
		Resize_Div("System Options");
		Resize_Div("Schedulers");
		Resize_Div("Taxpayers");
		Resize_Div("Site Options");
		Resize_Div("New Site");
	}

	//===========================================================================================
	function Resize_Div(tabname) {	// adjust size of certain divs that overflow
	//===========================================================================================
		switch (tabname) {
		case "Schedulers":
			divname = "user_list_div";
			bottomAdjust = 210;
			break;
		case "Taxpayers":
			divname = "inet_list_div";
			bottomAdjust = 210;
			break;
		case "Site Options":
			divname = "site_options_div";
			bottomAdjust = 250;
			break;
		case "New Site":
			divname = "site_options_div";
			bottomAdjust = 270;
			break;
		case "System Options":
			divname = "system_options_div";
			bottomAdjust = 250;
			break;
		}
		window_height = window.innerHeight;
		divElement = document.getElementById(divname);
		div_top = divElement.offsetTop;
		divElement.style.maxHeight = Math.max(0, (window_height - div_top - bottomAdjust)) + "px";
	}
	
//===========================================================================================
function Test_For_Enter(id, e) {
// id = the id of the element being checked
// e = the event being checked for a key code
//===========================================================================================
	if ((e.keyCode || e.charCode) == 13) { // Enter key code (charCode for old browers)
		if (id == "FindByVal") Action_Request('FindUser');
	}
}

</script>

</head>

<!-- ============================================HTML========================================= -->
<!-- ============================================HTML========================================= -->
<!-- ============================================HTML========================================= -->
<body onload="Initialize()" onresize="Resize_All_Divs();">

<div id="Main">

	<div class="appt_page_header">
		<h1>Site and User Administration</h1>
		<?php echo "You are signed in as " . _Show_Chars($_SESSION["User"]["user_first"] . " " . $_SESSION["User"]["user_last"], "text");?>

		<div class="menu-buttons">
			<div class="menuButton" onclick="Action_Request('GoToAppointments')">Manage appointments</div>

			<div class='menuButton' id='SearchAppt'>Search
				<div class='menuButtonList'>
				<div class='menuButtonListItem' id='SearchApptPhone' onclick='Show_SearchBox(this.id)'><?php echo $Phone_icon;?> Search by Phone Number</div>
				<div class='menuButtonListItem' id='SearchApptName' onclick='Show_SearchBox(this.id)'><?php echo $Name_icon;?> Search by Name</div>
					<div class='menuButtonListItem' id='SearchApptEmail' onclick='Show_SearchBox(this.id)'><?php echo $Email_icon;?> Search by Email</div>
				</div>
			</div>
<?php
	global $isAdministrator, $Errormessage;
	if ($isAdministrator) {
		echo "<div class='menuButton' id='SearchAppt'>Tools\n";
			echo "\t<div class='menuButtonList'>\n";
				$mailto = "mailto:" . str_replace("&","%26",$ManagerEmail);
				echo "\t\t<div class='menuButtonListItem' id='mail_managers'><a href='" . $mailto . "'>Email to managers</a></div>\n";
				echo "\t\t<div class='menuButtonListItem' id='roster' onclick='Print_Roster();'>Manager roster</div>\n";
				if ($_SESSION["TRACE"]) {
					echo "\t\t<div class='menuButtonListItem' id='turn_trace_off' onclick='Action_Request(\"StopTrace\");'>Turn trace off</div>\n";
				}
				else {
					echo "\t\t<div class='menuButtonListItem' id='turn_trace_on' onclick='Action_Request(\"StartTrace\");'>Turn trace on</div>\n";
				}
				echo "\t\t<div class='menuButtonListItem' id='show_trace' onclick='Action_Request(\"ViewTrace\");'>View trace</div>\n";
				echo "\t\t<div class='menuButtonListItem' id='clear_trace' onclick='Action_Request(\"ClearTrace\");'>Clear error log</div>\n";
				echo "\t\t<div class='menuButtonListItem' id='php_info' onclick='window.open(\"show_phpinfo.php\");'>View PHP settings</div>\n";
			echo "\t</div>\n";
		echo "</div>\n";
	}
?>
		<div class="menuButton" onclick="Action_Request('SignOut');">Sign out</div>

	</div>

	<div id="site_selections" class="safe">
		<b>Site being managed:</b> <select id="site_name" class="site_name" 
			title="Select the site you wish to manage"
			onchange="Action_Request('SwitchSite');">
			<?php
			List_Sites("mine");
			?>
			</select>
	</div>

	<div id="system_selected" class="safe">
		<b>System options being managed</b>
	</div>

		<br />

		<table id="menuTabDiv">
			<tr>
				<?php
					$class = ($isAdministrator) ? "menuTabAdmin" : "menuTab";
					echo "<td id=\"menuTabSys\" class=\"$class\"";
						if (! $isAdministrator) echo " style=\"display: none;\"";
						echo " onclick=\"ShowPage('System');\">System Options</td>\n";
					echo "<td id=\"menuTabSite\"  class=\"$class\" onclick=\"ShowPage('Site');\">Site Options</td>\n";
					echo "<td id=\"menuTabUsers\" class=\"$class\" onclick=\"ShowPage('Schedulers');\">Schedulers</td>\n";
					echo "<td id=\"menuTabTPs\"   class=\"$class\" onclick=\"ShowPage('Taxpayers');\">Taxpayers</td>\n";
				?>
		</table>


<?php	
//	global $change_history;
//	global $isAdministrator;
//	if ($isAdministrator and ($_SESSION["NewVersion"] > $_SESSION["SystemVersion"])) {
//		echo "\t<div id='new_version_notify'>";
//		echo "\t\tA new version " . $_SESSION["NewVersion"] . " is available.<br />\n";
//		echo "\t\t<button id='new_version_button' onclick=\"Show_History();\">See/hide changes</button>\n";
//		echo "\t\t" . $change_history;
//		echo "\t</div>\n";
//	}

	if ($isAdministrator and $_SESSION["TRACE"]) {
		echo "<div id='trace_notify'>Trace is ON\n";
		echo "<br /><button id='trace_notify_button' onclick='Action_Request(\"StopTrace\");'>Turn trace OFF</button>\n";
		echo "</div>\n";
	}
?>

</div>

<div id="access_admin">

<div id="admin_pages">

<!-- ================================= System Tab ======================================== -->
<div id="system_page" style="display: none;">
<div id="system_wrapper" class="safe" style="border: 1px solid black; border-top: none;">

	<div id="system_page_div" class="safe">

		<br />

		<table id="system_options_table">
			<tr>	<td>OPTION</td><td></td><td>VALUE or STATUS</td></tr>
			<tr>	<td>System Version:</td><td></td>
				<td colspan="2" class="left"><?php echo $_SESSION["SystemVersion"]; ?></td></tr>
			<tr>	<td>State Web Site [STATESITE]:</td><td></td>
				<td><input id="system_url" type="url" value="<?php echo $_SESSION['SystemURL']; ?>"
					title="Enter the web address for the state's web site"
					onchange = "Change_System_Data()" />&nbsp;</td>
				<td><a id="gobutton" href="<?php echo $_SESSION['SystemURL']; ?>" target="_blank"> <button style="margin:0 0 0 1em;">Go there</button></a>
				</td></tr>
	
			<tr>	<td>Default Email:</td><td></td>
				<td colspan="2"><input id="system_email" type="email"
					title="the default From: address for email messages"
					value="<?php echo $_SESSION['SystemEmail'];?>"
					onchange="Change_System_Data()" />
				</td></tr>

			<tr>	<td>Daily View update interval:</td><td></td>
				<td class="left"><input id="system_heartbeat" type="number"
					title="the interval in milliseconds for update to changes in Daily View appointment slots. Off = 0."
					value="<?php echo $_SESSION['SystemHeartbeat'];?>"
					onchange="Change_System_Data()" />
					milliseconds. (0 = off) or no less than 5000 (5 seconds))
				</td></tr>

			<tr>	<td>Periodic email reminder:</td><td></td>
				<td colspan="2" class="left"><?php
					if ($_SESSION['SystemReminders']) echo "Last ran on " . $_SESSION['SystemReminders'] . "."; 
					else echo "(No timed reminder task has run.)"
				?>
				</td></tr>

		</table>
	</div> <!-- system_page_div -->

	<hr />

	<div id="system_options_div" class="safe" style="overflow: auto;">
		<div id="system_greeting_div">
			Login Page Greeting
			<!-- do not split the folowing line -->
			<textarea id="system_greeting" onchange="Change_System_Data()"><?php echo _Show_Chars($_SESSION["SystemGreeting"], "text");?></textarea>
		</div>

		<div id="system_greeting_info_div">
			<br />The &quot;greeting&quot; field to the left and the &quot;notice&quot; field below appear on the sign-in page.
			Both fields can contain HTML coding to display text, for example:
			<ul class="left">
				<li> Use &lt;b&gt;some text&lt;/b&gt; to make <b>some text</b> bold.</li>
				<li> Use &lt;i&gt;some text&lt;/i&gt; to make <i>some text</i> italic.</li>
				<li> Use &lt;u&gt;some text&lt;/u&gt; to make <u>some text</u> underlined.</li>
				<li> Nest them: &lt;u&gt;&lt;b&gt;&lt;i&gt;some text&lt;/i&gt;&lt;/b&gt;&lt;u&gt; to make <u><b><i>some text</i></b></u> do combinations.</li>
			</ul>
			To restore the default greeting, simply clear the box to the left and click the save button at the bottom of the window.</li>
		</div>

		<div id="system_notice_div">
		<br />
			Login Page Notice:
			<!-- do not split the folowing line -->
			<textarea id="system_notice" onchange = "Change_System_Data()"><?php echo _Show_Chars($_SESSION["SystemNotice"], "text");?></textarea>
		</div>

		<div id="system_notice_info_div">
			<br /><br />The system notice is generally blank during the normal season but can be used to indicate that
			the season is over or that some special conditions need to be brought to the attention of users
			(e.g. during the COVID-19 epidemic, system is temporarily off-line, etc.)
		</div>

		<div id="system_confirm_div">	
		<br />
			Default confirmation email:
			<!-- do not split the folowing line -->
			<textarea id="system_confirm" onchange="Change_System_Data()"><?php echo _Show_Chars($_SESSION["SystemConfirm"], "text");?></textarea>
		</div>

		<div id="system_confirm_info_div">
			<br /><br />The default confirmation email should contain only ascii text, no HTML coding. 
			It can contain the following shortcodes which will be replaced with text when emailed:
			<br />[STATESITE] (state web site as defined above)
			<br />[SITENAME] (site's name)
			<br />[ADDRESS] (site's street address)
			<br />[CITY], [STATE], [ZIP] (site's address)
			<br />[CONTACT] (site's contact name)
			<br />[PHONE] (site's contact phone)
			<br />[EMAIL] (site's email)
			<br />[DATE], [TIME] (date and time of appointment)
			<br />[ATTACHMENTS] (defined below)
			<br />To restore the sample confirmation message, clear the box to the left and
				click the save button at the bottom of the window.
		</div>

		<div id="system_attach_div">	
			<br />
			<!-- system_attach is hidden temporary storage -->
			Attachments list: <input id="system_attach" style="display: none;" />
			<table id="system_attach_table" onchange="Change_System_Data()">
				<thead>
				<tr>	<th>Attachment title</th><th>Attachment URL</th></tr>
				</thead>
				<tbody id="sat_body"></tbody>
			</table>
		</div>

		<div id="system_attach_info_div">
			<br /><br />The attachment list provides links to documents and/or web sites.
			These attachments can be selected by site and applied to email confirmation and
			reminder messages.
			<br />For the URL, include the http:// or https:// prefix.
			<br />To remove an entry, clear either the title or URL field and save.
		</div>

	</div> <!-- system_options_div -->

</div> <!-- system_wrapper -->

	<div id="system_option_buttons">
		<button id="system_change" class="safe"    onclick="Action_Request('ChangeSystem');">Save changes</button>
		<button id="system_cancel" class="warning" onclick="Restore_System_Data();">Cancel</button>
	</div> <!-- system_option_buttons -->

</div> <!-- system_page -->

<!-- ================================= Site Tab ======================================== -->
<div id="site_page">

	<div id="site_options" class="safe">

		<button id='site_add' onclick='Add_New_Site()'>Add a new site</button>

		<div id="site_new">
			<table id="site_new_table" class="address_table">
				<tr><td>New site name:</td><td><input id="site_new_name" type="text" /></td>
					<td>[SITENAME]</td></tr>
			</table>
		</div>

		<div id="site_options_div" style="overflow: auto; overflow-y: auto;">

		<div id="site_current" onchange="Build_Site_Address();">
			<table id="site_current_table" class="address_table">
				<tr><td>Current site name:</td><td><input id="site_current_name" title="Site name (include the city first in the name)" type="text" 
					value="<?php global $ThisLoc; echo $ThisLoc; ?>" /></td><td>[SITENAME]</td></tr>
			</table>
		</div>

		<div id="site_address_block" onchange="Build_Site_Address();">
			<table id="site_address_table" class="address_table">
				<tr><td>Address:</td><td><input id="site_address" title="Site address" type="text"
					value="<?php global $ThisAddress; echo $ThisAddress[0];?>" /></td>
					<td>[ADDRESS]</td></tr>
				<tr><td>City:</td><td><input id="site_city" title="Site city address" type="text"
					value="<?php global $ThisAddress; echo $ThisAddress[1];?>" />
					State: <input id="site_state" title="Site state" type="text"
					value="<?php global $ThisAddress; echo $ThisAddress[2];?>" />
					Zip: <input id="site_zip" title="Site zip code" type="text"
					title="If you use 9-digit zips, enter as 12345-6789."
					value="<?php global $ThisAddress; echo $ThisAddress[3];?>" /></td>
					<td>[CITY] [STATE] [ZIP]</td></tr>
				<tr><td>Contact:</td><td><input id="site_contact" title="Site contact person" type="text"
					value="<?php global $ThisContact; echo $ThisContact;?>" /></td>
					<td>[CONTACT]</td></tr>
				<tr><td>Appointment phone:</td><td><input id="site_phone" title="Site phone for appointments" type="text"
					title="Enter as 7 or 10 digits with dashes as 456-7890 or 123-456-7890"
					value="<?php global $ThisAddress; echo $ThisAddress[4];?>" />
					Email: <input id="site_email" title="Site email address for appointments or questions" type="email"
					title="Enter as abcdef@ghi.klm"
					value="<?php global $ThisAddress; echo $ThisAddress[5];?>" /></td>
					<td>[PHONE] [EMAIL]</td></tr>
				<tr><td>Website:</td><td><input id="site_website" title="Site website if there is one" type="url"
					title="Enter as http://abcdef.ghi"
					value="<?php global $ThisAddress; echo $ThisAddress[6];?>" /></td>
					<td>[WEBSITE]</td></tr>
				<tr><td>Email messages:
					<span id="view_m_button">
						<br /><button id="view_message_button" style="margin-top: 4em; margin-left: 7em;" onclick="View_Message()">&nbsp;View&nbsp;</button></span>
					</td>
					<td colspan="2" id="site_email_optbox" onchange="Change_Site_Message();">
						<input id="site_sendemail" title="Check box to send confirmation emails" type="checkbox" />
						Send confirmation email if taxpayer has an email.
						<span id="site_email_options">
						<table style="width: calc(100%-1em); margin-left: 1em;">
							<tr><td colspan="2">
								<input id="site_reminder_option" type="checkbox"
								title="Check box to send reminder emails"
									<?php global $ThisReminder; echo (($ThisReminder > 0) ? 'checked="checked"' : ''); ?> />
								Send a reminder email
								<input id="site_reminder" type="number"
									title="Send this number of days prior to the appointment"
									value="<?php global $ThisReminder; echo $ThisReminder; ?>" />
								days prior to the appointment,
								<br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;unless it&apos;s been
								<input id="site_lastrem" type="number"
									title="Wait for this long before sending another reminder message"
									value="<?php global $ThisLastRem; echo $ThisLastRem; ?>" />
								days or less since the last email.
								</td></tr>
							<tr><td>
								<!-- do not split the folowing line -->
								<textarea id="site_message"><?php global $ThisMessage; $ThisMessage = _Show_Chars($ThisMessage, "text"); echo ($ThisMessage);?></textarea></td>
								<td>The message can contain any of the shortcodes listed above as well as the following:
								<br /><br />[TPNAME] (Taxpayer&apos;s name(s))
								<br />[DATE] [TIME] (Appointment date &amp; time)
								<?php
								global $ThisAttach;
								if ($_SESSION["SystemURL"]) echo "<br />[STATESITE] (State website)\n";
								$ThisAttach = _Show_Chars($ThisAttach, "text");
								echo "<input id=\"site_attach\" style=\"display: none;\" value=\"$ThisAttach\">";
								List_Attachments();
								 ?>
								</td></tr>
						</table></span></td></tr>
				<tr><td>Site options:</td>
					<td colspan="2" id="site_option_list">
						<input id="site_sumres" type="checkbox" <?php global $ThisSumres; echo $ThisSumres; ?> />
						Allow scheduling of reserved slots in Summary View (empty slots are always chosen first)
						<br /><input id="site_10dig" type="checkbox" <?php global $This10dig; echo $This10dig; ?>
						title = " " />
						Require 10-digit phone numbers with optional toll prefix (recommended)</td></tr>
				<tr><td>Internet access:
					<span id="view_i_button">
						<br /><button id="view_instructions_button" style="margin-top: 16em; margin-left: 7em;" onclick="View_Instructions()">&nbsp;View&nbsp;</button>
						</span> </td>

					<td colspan="2" id="site_clients_inet" onchange="Change_Other_Sites('');">
						<input id="site_clients" type="checkbox" /> Allow internet taxpayers to make their own appointment.
						<span id="site_clients_options">
						<table style="width: 100%; margin-left: 1em;">
							<tr><td colspan="2" style="padding-left: 1.6em;">
								<table>
									<tr><td style="text-align: right; width: 0;">Internet&nbsp;access&nbsp;open:&nbsp;<br />through:&nbsp;</td>
									<td><input id="site_open" type="date" value="<?php global $ThisOpen; echo $ThisOpen;?>" />
									<br /><input id="site_closed" type="date" value="<?php global $ThisClosed; echo $ThisClosed;?>" ></td></tr>
								</table></td></tr>
							<tr><td colspan="2" style="padding-left: 1.6em;">
								Callback list options:
									<br /><input id="site_clients_cbalways" type="radio" name="site_clients_cbopt" value="S" />
										Always allow to add to callback list.
									<br /><input id="site_clients_cbrestrict" type="radio" name="site_clients_cbopt" value="R" />
										Only add to callback list if callback list is longer than available appointments (recommended).
									<br /><input id="site_clients_cbonly" type="radio" name="site_clients_cbopt" value="C" />
										Cannot schedule own appointment. Can only add to callback list.
									<br /><input id="site_clients_cbnone" type="radio" name="site_clients_cbopt" value="N" />
										Can only schedule own appointment. Cannot add to callback list.
									<br /><br />Limit a taxpayer&apos;s internet scheduling to
								<input id="site_clients_limit" type="number" /> appointment(s), including at other sites.</td></tr>
							</table>
						<table style="width: calc(100%-1em); margin-left: 1em;">
							<tr><td colspan="2">Additional instructions for the taxpayer for this site:</td></tr>
							<tr>
							<!-- do not split the folowing line -->
							<td><textarea id="site_instructions"><?php global $ThisInstructions; $ThisInstructions = _Show_Chars($ThisInstructions, "text"); echo $ThisInstructions;?></textarea></td>
							<td>This message will be displayed after the general instructions on the taxpayer
								self-appointment page.
								<br /><br />[TPNAME], [DATE] &amp; [TIME] shortcodes are not supported in this message.
								<br /><br />Leave blank to omit the message.</td></tr>
						</table>
						</span></td></tr>
				<tr><td>Other site access:</td>
					<td id="site_others_access" colspan="2" onchange="Change_Other_Sites('');">
						<input id="site_others" type="checkbox" <?php global $OptionList; if ($OptionList) echo " checked"?> />
							Allow other sites to view or schedule your taxpayers.
						<span id="osite_list">
						<table id="osite_name_table">
						<?php
						List_Sites("options");
						?>
						</table>
						</span></td></tr>
			</table>
		</div>
		</div> <!-- site_description -->

	</div> <!-- site_options -->

		<div id="site_option_buttons">
			<button id="site_addit"  class="safe"    onclick="Action_Request('AddSite');">Save new site</button>
			<button id="site_change" class="safe"    onclick="Action_Request('ChangeSite');">Save changes</button>
			<button id="site_cancel" class="warning" onclick="Action_Request('CancelSite');">Cancel</button>
			<button id="site_delete" class="danger"  onclick="Action_Request('DeleteSite');">Delete this site</button>
		</div>

</div> <!-- site_page -->

<!-- ================================= Site User Tab ======================================== -->

<div id="user_page">

	<div id="user_options" class="safe">

		<?php global $checkboxYes, $checkboxNo, $SiteCurrent;
		if ($SiteCurrent > 1) {
			echo "<button id='user_new' onclick='Add_User_Info()'>Add a new scheduler</button>";
		}

		// option marker containers - change them in the PHP ajax code
		echo "<span style='display:none;'>";
		echo "<span id='optYes'>" . $checkboxYes . "</span><span id='optNo'>" . $checkboxNo . "</span>\n";
		?>
		</span>

		<div id="user_list_div">
			<table id="user_list_table">
				<tr><thead class="safe">
					<th rowspan="2" class="sticky">
					Name <?php global $MFlag; echo "<br />(<span id='optM'>$MFlag</span>&nbsp;=&nbsp;Home&nbsp;Site&nbsp;Appt&nbsp;Manager, <span id='optA'>$AFlag</span>&nbsp;=&nbsp;Administrator)"; ?></th>
					<th rowspan="2" class="sticky">Role<br />at this site</th>
					<th colspan="2" class="sticky">Callback List</th>
					<th colspan="3" class="sticky">Appointments</th></tr>
				<tr>
					<th class="sticky" style="top: 1.1em;">change</th>
					<th class="sticky" style="top: 1.1em;">view</th>
					<th class="sticky" style="top: 1.1em;">change</th>
					<th class="sticky" style="top: 1.1em;">view</th>
					<th class="sticky" style="top: 1.1em;">use&nbsp;res</th></tr>
				</thead>
				<?php
				List_Users("Site");
				?>
			</table>
		</div> <!-- user_list_div -->

	</div> <!-- user_uptions -->

</div> <!-- user_page -->



<!-- ================================= Internet User Tab ======================================== -->

<div id="internet_page">

	<div id="inet_options" class="safe">
		<?php
		global $SiteCurrent, $isAdministrator;
		if (($SiteCurrent > 1) || ($isAdministrator)) {
			echo "<div class='safe right' >";
			echo "Delete taxpayers who have not signed in since <input id='inet_delete_date' type='date' />";
			echo "<button onclick='Action_Request(\"DeleteUserByDate\");'> Delete </button></div>";
		}
		?>

		<div id="inet_list_div">
			<table id="inet_list_table">
				<?php
				echo "<thead class='safe'><tr>\n";
				echo "<th class='sticky'><span class='inet_sort' onclick=\"Sort_INet('user_first');\" ";
				echo " title='Sort by first name'>";
				echo ($_SESSION["UserSort"] == 'user_first') ? "&#x25bc;" : "&#x25bd;";
				echo "</span>&nbsp;&nbsp;Name&nbsp;&nbsp;";
				echo "<span class='inet_sort' onclick=\"Sort_INet('user_last');\"";
				echo " title='Sort by last name'>";
				echo ($_SESSION["UserSort"] == 'user_last') ? "&#x25bc;" : "&#x25bd;";
				echo "</span></th>\n";
				echo "<th class='sticky'>Phone&nbsp;&nbsp;";
				echo "<span class=\"inet_sort\" onclick=\"Sort_INet('user_phone');\"";
				echo " title='Sort by user phone number'>";
				echo ($_SESSION["UserSort"] == 'user_phone') ? "&#x25bc;" : "&#x25bd;";
				echo "</span></th>\n";
				echo "<th class='sticky'>Email&nbsp;&nbsp;";
				echo "<span class=\"inet_sort\" onclick=\"Sort_INet('user_email');\"";
				echo " title='Sort by user email'>";
				echo ($_SESSION["UserSort"] == 'user_email') ? "&#x25bc;" : "&#x25bd;";
				echo "</span></th>\n";
				echo "<th class='sticky'>Last&nbsp;Used&nbsp;&nbsp;";
				echo "<span class=\"inet_sort\" onclick=\"Sort_INet('user_lastlogin');\"";
				echo " title='Sort by last login date'>";
				echo ($_SESSION["UserSort"] == 'user_lastlogin') ? "&#x25bc;" : "&#x25bd;";
				echo "</span></th>\n";
				echo "<th class='sticky'>Delete?</th></tr></thead>\n";
				List_Users("Taxpayers");
				?>
			</table>
		</div> <!-- inet_list_div -->

	</div> <!-- inet_options -->

</div> <!-- internet page -->


<div id="user_information" style="display:<?php global $SiteAction; if ($SiteAction == "ViewUser") echo "block"; else echo "none"; ?>;">
	<center>
	<div id="old_user_header">
		<b>User options for <span id="acc_user_list"><?php global $ThisFullName; echo _Show_Chars($ThisFullName, "text"); ?></span></b>
	</div>

	<div id="new_user_header">
		<b>Adding a new user...</b>
	</div>
	</center>

	<hr />

	<div id="access_user">
		<table>
			<tr><td>First Name:</td><td><input id="acc_first" type="text"
				<?php global $ThisFirst, $ThisHome, $SiteUserHome, $isAdmiinistrator;
				echo "value=\"" . _Show_Chars($ThisFirst, "text") . "\"";
				if (($ThisHome != $SiteUserHome) and ($ThisHome != 1) and (! $isAdministrator)) echo " disabled"; ?>
				onkeyup="Change_User_Name();" />
				Last Name: <input id="acc_last" type="text"
				<?php global $ThisLast, $ThisHome, $SiteUserHome, $isAdmiinistrator;
				echo "value=\"" . _Show_Chars($ThisLast, "text") . "\"";
				if (($ThisHome != $SiteUserHome) and ($ThisHome != 1) and (! $isAdministrator)) echo " disabled"; ?>
				onkeyup="Change_User_Name();" /></td></tr>
			<tr><td>User Name:</td><td><input id="acc_name" type="text"
				<?php global $ThisName, $ThisHome, $SiteUserHome, $isAdmiinistrator;
				echo "value='" . $ThisName . "'";
				if (($ThisHome != $SiteUserHome) and ($ThisHome != 1) and (! $isAdministrator)) echo " disabled"; ?>
				onkeyup="Change_User_Name();" />
				Phone: <input id="acc_phone" type="text"
				<?php global $ThisPhone, $ThisHome, $SiteUserHome, $isAdmiinistrator;
				echo "value='" . $ThisPhone . "'";
				//if (($ThisHome != $SiteUserHome) and ($ThisHome != 1) and (! $isAdministrator)) echo " disabled"; ?>
				onkeyup="Change_User_Name();" />
				</td></tr>
			<tr><td>Password:</td><td><input id="acc_pass"
				<?php global $ThisPass, $ThisHome, $SiteUserHome, $ThisUserOptions, $isAdministrator;
				//if ((($ThisHome != $SiteUserHome) and ($ThisHome != 1) and (! $isAdministrator))
				//	OR (($ThisUserOptions == "A") and (! $isAdministrator))) echo "style=' display:none;'";
				echo " type='" . (($isAdministrator) ? "text" : "password") . "'";
				echo " value='" . $ThisPass . "'";
				?>
				onkeyup="Change_User_Name();" />
			<tr><td>Email:</td><td><input id="acc_email" type="text"
				<?php global $ThisFullName, $ThisEmail, $ThisHome, $SiteUserHome, $isAdministrator;
				echo "value='" . $ThisEmail . "'";
				if (($ThisHome != $SiteUserHome) and ($ThisHome != 1) and (! $isAdministrator)) echo " disabled";
				echo " onchange='Display_User_Buttons();' />";
				$sendemail = str_replace("&", "%26", $ThisFullName . "[" . $ThisEmail . "]");
				echo "<button id='sendemail'><a href='mailto:$sendemail'>Send Email</a></button></td></tr>";
				?>
			<tr><td>Home Site:</td><td><select id="acc_home"
				title="Select the user's home site"
				onchange="Display_User_Buttons();">
				<?php
				List_Sites("users");
				?>
				</select></td></tr>
			<tr><td></td><td onchange="Display_User_Buttons();">
				<?php
				global $ThisHome, $ThisUserOptions, $ThisApptSite, $SiteUserHome;
				if ($ThisApptSite > 1) {
					echo "<span id='tp-appt'><input id='makeappt' type='checkbox'> Move to the scheduler list</span>\n";
					echo "<span id='appt_tp' style='display:none;'><input id='maketp' type='checkbox'> Move to the taxpayer list</span>\n";
				}
				else {
					$d = (($ThisHome == $SiteUserHome) and ($ThisUserOptions != "A") and ($ThisUserOptions != "M")) ? "" : "style='display:none;'";
					echo "<span id='tp-appt' style='display:none;'><input id='makeappt' type='checkbox'> Move to the scheduler list</span>\n";
					echo "<span id='appt_tp' $d><input id='maketp' type='checkbox'> Move to the taxpayer list</span>\n";
				}

				?>
				</td></tr>
		</table>
	</div> <!-- access_user -->

	<hr />

	<div id="user_option_buttons">
		<button id="user_addit"  class="safe"    onclick="Action_Request('AddUser');">Save new user</button>
		<button id="user_change" class="safe"    onclick="Action_Request('ChangeUser');">Save changes</button>
		<button id="user_cancel" class="warning" onclick="Action_Request('CancelUser');">Cancel</button>
		<button id="user_delete" class="danger"  onclick="Action_Request('DeleteUser');">Delete this user</button>
		<button id="user_close" class="safe"  onclick="Show_User(0, this.id)">Close this window</button>
	</div> <!-- user_option_buttons -->

</div> <!-- user_information -->

</div> <!-- admin_pages -->

<div id="SiteDiv" style="display:none;">
<form id="SiteForm" method="post" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']);?>">
	<br />Current: <input id="SiteCurrent" name="SiteCurrent" value="<?php global $SiteUserHome; echo $SiteUserHome;?>" />
	<br />View: <input id="SiteView" name="SiteView" value="<?php global $SiteView; echo $SiteView;?>" />
	<br />Action: <input id="SiteAction" name="SiteAction" />
	<br />Name: <input id="Site1Name" name="Site1Name" />
	<br />Address: <input id="Site1Address" name="Site1Address" />
	<br />Contact: <input id="SiteContact" name="SiteContact" />
	<br />Sumres: <input id="SiteSumres" name="SiteSumres" />
	<br />10dig: <input id="Site10dig" name="Site10dig" />
	<br />SOptions: <input id="SiteOptions" name="SiteOptions" />
	<br />Open: <input id="SiteOpen" name="SiteOpen" />
	<br />Closed: <input id="SiteClosed" name="SiteClosed" />
	<br />Message: <input id="SiteMessage" name="SiteMessage" />
	<br />Reminder: <input id="SiteReminder" name="SiteReminder" />
	<br />LastRem: <input id="SiteLastRem" name="SiteLastRem" />
	<br />Instructions: <input id="SiteInstructions" name="SiteInstructions" />
	<br />Attachments: <input id="SiteAttach" name="SiteAttach" value="<?php global $SiteAttach; echo $SiteAttach;?>" />
	<br />UserCurrent: <input id="UserCurrent" name="UserCurrent" value="<?php global $UserPreferred; echo $UserPreferred;?>" />
	<br />UserF: <input id="UserFirst" name="UserFirst" />
	<br />UserL: <input id="UserLast" name="UserLast" />
	<br />UName: <input id="UserName" name="UserName" />
	<br />UHome: <input id="UserHome" name="UserHome" />
	<br />UAppt: <input id="UserAppt" name="UserAppt" />
	<br />UEmail: <input id="UserEmail" name="UserEmail" />
	<br />UPhone: <input id="UserPhone" name="UserPhone" />
	<br />UPass: <input id="UserPass" name="UserPass" />
	<br />UOptions: <input id="UserOptions" name="UserOptions" />
	<br />USort: <input id="UserSort" name="UserSort" />
	<br />SGreet: <input id="SystemGreeting" name="SystemGreeting" value="<?php echo $_SESSION['SystemGreeting']; ?>" />
	<br />SNotice: <input id="SystemNotice" name="SystemNotice" value="<?php echo $_SESSION['SystemNotice']; ?>" />
	<br />SConfirm: <input id="SystemConfirm" name="SystemConfirm" value="<?php echo $_SESSION['SystemConfirm']; ?>" />
	<br />SAttach: <input id="SystemAttach" name="SystemAttach" value="<?php echo $_SESSION['SystemAttach']; ?>" />
	<br />SysURL: <input id="SystemURL" name="SystemURL" />
	<br />SysEmail: <input id="SystemEmail" name="SystemEmail" />
	<br />SysHeartbeat: <input id="SystemHeartbeat" name="SystemHeartbeat" />
</form>
</div> <!-- SiteDiv -->

</div> <!-- access_admin -->

<div id="user_search_box">
	<b>Registered Person Search</b>
	<br />
	<div>
		Search by:
		<input id="FindByPhone" type="radio" name="FindOption" onchange="Show_SearchBox(this.id);" /> Phone
		<input id="FindByName" type="radio" name="FindOption" onchange="Show_SearchBox(this.id);" /> Name
		<input id="FindByEmail" type="radio" name="FindOption" onchange="Show_SearchBox(this.id);" /> Email
	</div>
	<!-- 20em width below overrides css calculated. The max-width setting will not override calculation -->
	<input id="FindByVal" type="text" width="20em" onkeyup="Test_For_Enter(this.id,event)" />
	<br />
	<input id='showMine' type='checkbox' checked='checked' onchange='Show_Matches();' />
	Uncheck to see matches at other sites;
	click on a check-boxed name to open the user administration box.
	<hr />
	<div id="user_search_results">
		<?php Show_Search() ?>
	</div>
	<button id="FindButton" onclick="Action_Request('FindUser');">Search</button>
	<button id="HideTest" onclick="user_search_box.style.visibility='hidden';">Close</button>
</div> <!-- user_search_box -->

</div> <!-- main -->

</html>
</body>
