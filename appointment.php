<!DOCTYPE html>
<?php
//ini_set('display_errors', '1');

// ---------------------------- VERSION HISTORY -------------------------------
//File Version 8.01
//	Excelexport interface changed to contributed version
//	Prevent losing changes if closing appt window without saving
//	Small changes required for compatibility with PHP version 8
//	Prevent matches with other uses if phone is 000-000-0000
//	Add changes to support attachments to confirmation, reminder emails and
//	   self-appointment information message
//	Added "Sent documents" and "Responded by email" to status buttons
//File Version 7.02
//	Removed some debugging messages that cluttered the trace file
//File Version 7.01
//	Made table headers sticky
//	Changed blue highlighting for self-scheduled appointments
//	Added flexible exporting
//	Added ability to move all callback records to the deleted list
//	Added ability to copy an appointment
//	Added a "Send Email" button to the appointment screen
//	Added a "NONE" button to Phone number entry line to enter 000-000-0000
//	Moved misc character encoding/decoding to js and php functions modules
//	Moved view appointment window creation to a new php function module
//	Added shortcodes to information message on user appointment window
//File Version 6.01b
//	Corrected apostrophe problem in Notes and Info icon title text
//File Version 6.01a
//	Added site name
//	header on appointment report
//File Version 6.01
//	Added site instruction box for UserView screen
//File Version 5.02c
//	Fixed creation of callback records when not needed
//	Fixed moving of a callback record to a different site callback record
//File Version 5.02b
//	Fixed saving a callback entry moves that entry to the bottom of the list, now retains position
//	Clarified delete title and warning message for callback list due to appointment vs callback confusion
//	Suppressed some indexing errors that did not cause a problem but cluttered the error log
//	Changed email "From:" to a no-reply due to email blocking for some real accounts
//File Version 5.02a
//	Should not be able to add to CB list if site is not open for appointments
//	Fixed apostrophe in site appointment message
//	Prevent user from adding appointments with a different email or phone
//File Version 5.02
//	Fixed undefined variable when viewing Callback list
//	Sites not visible in UserView due to calendar space at 50%
//	Time of an appointment not displaying correctly in UserView
//File Version 5.01c
//	Added the ability to remove appointment records between 2 dates
//File Version 5.01b
//	When adding new time group, change default site to first listed or home site
//	When adding new time group, keep daily view at the same date
//	When adding new time group, allow to add more than 1 slot
//	Keep daily view date visible after a search
//	When daily view date selected, scroll calendar to view that date
//	Removed old MonthUp and MonthDown remnants from prior calendar operation
//	Corrected problem: print excel in Summary View. Switch to Daily View prints excel and hangs
//	Added print function to auto-print the ERO report
//File Version 5.01a
//	Added "@" to suppress some error messages when db is empty
//	Adding or deleting a single slot was jumping to a different day
//File Version 5.00


// Set up environment
require_once "environment.php";

// Set up PHPMailer
//use PHPMailer\PHPMailer\PHPMailer;
//use PHPMailer\PHPMailer\Exception;
//require_once "PHPMailer.php";
//require_once "Exception.php";
//require_once "SMTP.php";

// If the UserIndex has not been set as a session variable, the user needs to sign in
if (@$_SESSION["UserIndex"] == 0) {
	header('Location: index.php'); // prevents direct access to this page (must sign in first).
	exit;
}

// Global variables
$Debug = ""; // Shows in menu in item named DebugText.
$Errormessage = "";
$Date = "";
$MyTimeStamp = Date("Y-m-d H:i:s");
$DisplayDate = "To be caclulated";
$TimeNow = Date("h:ia");
$HeaderText = "To be calculated";
$DateList["init"] = "";
$DateFlag["init"] = "";
$DateLoc["init"] = "";
$BgColor = "#FFFFCC";
$FirstSlotDate = "";
$FirstMonth = 0;
$FirstYear = 0;
$LastMonth = 0;
$LastYear = 0;
$LastSlotNumber = 0;
$LocationList[0] = 0;
$LocationShow[0] = 0;
$LocationAddress[0] = "";
$LocationContact[0] = "";
$LocationMessage[0] = "";
$ShowDagger = false;
$ShowSlotBox = false;
$ApptBox = "hidden";
$MoveBox = "hidden";
$ApptNo = "";
$ApptTimeDisplay = "";
$ApptName = "";
$ApptPhone = "";
$ApptEmail = "";
$ApptMove = "";
$ApptTags = "";
$ApptNeed = "";
$ApptInfo = "";
$ApptStatus = "";
$DeleteCode = "D";
$FormApptNo = "InitialLogin";
$FormApptTime = "";
$FormApptName = "";
$FormApptPhone = "";
$FormApptEmail = "";
$FormApptLoc = "";
$FormApptTags = "";
$FormApptNeed = "";
$FormApptInfo = "";
$FormApptReason = "InitialLogin";
$FormApptStatus = "";
$FormApptTimeStamp = $TimeStamp;
$FormApptOldNo = "";
$MyWebsite = "";
$UserName = "";
$UserTables = "";
$UserEmail = "";
$UserPhone = "";
$UserHome = "";
$WaitSequence = 0;
$MAxPermissions = 0;
$SingleSite = 0;
$EM_Reason = "";
$OtherAppts = "";
$DeletedClassFlag = false;
$CallbackClassFlag = false;
$DateClassFlag = false;
$LocationCBList = [];
$LocationEmpty = [];
$RESERVED = "&laquo; R E S E R V E D &raquo;";

$SystemAttachList = explode("|", $_SESSION["SystemAttach"]);

$UserIndex = $_SESSION["UserIndex"];
$UserName  = $_SESSION["UserName"];
$UserFirst = $_SESSION["UserFirst"];
$UserLast = $_SESSION["UserLast"];
$UserFullName = $_SESSION["UserFullName"];
$UserEmail = $_SESSION["UserEmail"];
$UserPhone = $_SESSION["UserPhone"];
$UserOptions = $_SESSION["UserOptions"];
$UserHome  = $_SESSION["UserHome"];
$UserSiteList = $_SESSION["UserSiteList"];
$UserPermissions = intval($UserOptions);
if ($UserOptions === "A") {
	$UserPermissions = $ACCESS_ALL | $ADMINISTRATOR;
	$isAdministrator = true;
}
if ($UserOptions === "M") {
	$UserPermissions = $ACCESS_ALL | $MANAGER;
	$isManager = true;
}

include "showslots.php";

// Determine the user's initial view
$ApptView = ($UserPermissions < $VIEW_APP) ? "ViewCallback" : "ViewSummary";
if ($UserHome == 0) $ApptView = "ViewUser";
// Get POST variables if changes were submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {
	if (strpos($_SERVER["HTTP_REFERER"], "appointment.php")) {
		// Get the post data and process the change request
		// may need htmlspecialchars_decode() later
		$FormApptDate = htmlspecialchars(stripslashes(trim($_POST["IDApptDate"])));
		$FormApptNo = htmlspecialchars(stripslashes(trim($_POST["IDApptSlot"])));
		$FormApptTime = htmlspecialchars(stripslashes(trim($_POST["IDApptTime"])));
		$FormApptName = htmlspecialchars(stripslashes(trim($_POST["IDApptName"])));
		@$FormApptPhone = htmlspecialchars(stripslashes(trim($_POST["IDApptPhone"])));
		@$FormApptEmail = htmlspecialchars(stripslashes(strtolower(trim($_POST["IDApptEmail"]))));
		$FormApptLoc = htmlspecialchars(stripslashes(trim($_POST["IDApptLoc"])));
		$FormApptTags = htmlspecialchars(stripslashes(trim($_POST["IDApptTags"])));  
		$FormApptNeed = htmlspecialchars(stripslashes(trim($_POST["IDApptNeed"])));
		$FormApptInfo = htmlspecialchars(stripslashes(trim($_POST["IDApptInfo"])));
		$FormApptStatus = htmlspecialchars(stripslashes(trim($_POST["IDApptStatus"])));
		$FormApptOldNo = htmlspecialchars(stripslashes(trim($_POST["IDApptOldSlot"]))); // Appt number being moved
		$FormApptReason = htmlspecialchars(stripslashes(trim($_POST["IDApptReason"])));
		$FormApptSlotDates = htmlspecialchars(stripslashes(trim($_POST["IDApptSlotDates"])));
		$FormApptSlotDays = htmlspecialchars(stripslashes(trim($_POST["IDApptSlotDays"])));
		$FormApptSlotLoc = htmlspecialchars(stripslashes(trim($_POST["IDApptSlotLoc"])));
		$FormApptSlotSets = htmlspecialchars(stripslashes(trim($_POST["IDApptSlotSets"])));
		$FormApptOldTimeStamp = htmlspecialchars(stripslashes(trim($_POST["IDApptTimeStamp"])));
		$ApptView = htmlspecialchars(stripslashes(trim($_POST["IDApptView"])));	
		$FormApptCustSite = htmlspecialchars(stripslashes(trim($_POST["IDApptCustSite"])));	
	}
	else {
	}

	// Set the Summary all-dates option
	if ($FormApptReason == "ViewSummary") $_SESSION["SummaryAll"] = false;
	if ($FormApptReason == "ViewSummaryAll") $_SESSION["SummaryAll"] = true;

	// Determine the user's initial view
	if ($ApptView == "") $ApptView = ($UserPermissions < $VIEW_APP) ? "ViewCallback" : "ViewSummary";
	if ($UserHome == 0) $ApptView = "ViewUser";

	switch ($FormApptNo) {
		// if other than a database slot number,
		// FormApptNo may be text that tells what to do with the slot last chosen
		case "": break;
		case "LogOut":
			session_unset();
			session_destroy();
			header('Location: index.php');
			exit();
		case "MoveLoc": // Changing location during a move
			$ApptMove = $FormApptOldNo; // will be negative during a copy request
			$MoveBox = "visible";
			$FirstSlotDate = "";
			break;
		case "NewLoc":
			$FirstSlotDate = "";
			$_SESSION["CalStartMo"] = 0;
			$_SESSION["CalStartYr"] = 0;
			break;
		case "NewDate":
			$FirstSlotDate = $Date = $FormApptDate;
			break;
		case "Move": // A new date has been chosen
			$FirstSlotDate = $Date = $FormApptDate;
			$ApptMove = $FormApptOldNo;
			$ApptBox = "visible";
			break;
		case "Copy":
			$FirstSlotDate = $Date = $FormApptDate;
			$ApptMove = -$FormApptOldNo; // minus implies copy, so change to get real index
			$ApptBox = "visible";
			break;
		case "FindByTags":
		case "FindByPhone":
		case "FindByEmail":
		case "FindByName":
		case "FindBySound":
			Do_Search();
			$FirstSlotDate = $Date = $FormApptDate;
			break;
		case "SlotAdd":
		case "SlotAdd1":
		case "SlotClear":
		case "SlotClone":
		case "SlotRemove":
		case "SlotRemove1":
		case "SlotRemoveAll":
		case "SlotRemoveDeleted":
		case "SlotDeleteCallback":
		case "SlotRemoveDateRange":
			Configure_Slots();
			$FirstSlotDate = $Date = $FormApptDate;
			break;
		case "AddCBSlots":
			Add_Wait_Slots($FormApptSlotSets, $FormApptSlotLoc);
			break;
		case "PrintExcel":
			if ($_SESSION["TRACE"]) {
				error_log("APPT: $UserName, view=$ApptView, action=$FormApptNo");
			}
			$_SESSION["ExportList"] = $FormApptReason;

			//new version
			$url = "excelexport.php?UserSiteList=" . $UserSiteList . "&ExportList=" . $FormApptReason;
			echo '<meta http-equiv=refresh content="1; '. $url . '">';
			break;
		case "InitialLogin":
			break;
		default: // The appointment number in the database
			$query = "SELECT * FROM $APPT_TABLE";
			$query .= " LEFT JOIN $SITE_TABLE";
			$query .= " ON $APPT_TABLE.appt_location = $SITE_TABLE.site_index";
			$query .= " WHERE `appt_no` = '$FormApptNo'";
			$ts = mysqli_query($dbcon, $query);
			$row = mysqli_fetch_array($ts);
			$nm0 = $row['appt_name'];
			$ts0 = $row['appt_change'];
			$dy0 = $row['appt_date'];
			$tm0 = $row['appt_time'];
			$loc0 = $row['appt_location'];
			$wait0 = $row['appt_wait'];
			$lname0 = $row['site_name'];

			// If name is newly RESERVED, clear other user-specific fields
			if ($FormApptName == htmlspecialchars($RESERVED)) {
				$FormApptPhone = "";
				$FormApptStatus = "";
				$FormApptEmail = "";
				$FormApptTags = "";
				$FormApptNeed = "";
				$FormApptInfo = "";
			}

			// If current view is ViewUser, emulate what otherwise would have been a move
			// The following could not have been changed in this case but are blank
			if (($ApptView == "ViewUser") and ($FormApptReason == "Delete")) {
				$FormApptPhone = $row['appt_phone'];
				$FormApptStatus = $row['appt_status'];
				$FormApptEmail = $row['appt_email'];
				$FormApptTags = $row['appt_tags'];
				$FormApptNeed = $row['appt_need'];
				$FormApptInfo = $row['appt_info'];
			}

			// Set up current time and date variables
			$dt1 = str_replace("-", "/", substr($TodayDate, 5, 5)) . "_" . $TimeNow;
			$dy1 = str_replace("-", "/", substr($dy0, 5, 5));
			$tm1 = substr($tm0, 0, 5);
			$un1 = ($UserHome == 0) ? "USER" : $UserName;

			// check the time stamp to be sure the entry is current or vacant
			if (($FormApptOldTimeStamp >= $ts0) or ($nm0 == "")) {
				// add to the status history
				$typ1 = $row['appt_type'];
				$newstatus = $FormApptStatus;
				switch ($FormApptReason) {
					case "Add":
						if ($FormApptDate == $NullDate) {
							if (($ApptView == "ViewCallback") or ($ApptView == "ViewUser")) {
								$newstatus = "$dt1: Added to Callback list ($un1)";
							}
						}
						else {
							if ($FormApptName == htmlspecialchars($RESERVED)) {
								$newstatus = "$dt1: Reserved by $un1";
							}
							elseif ($FormApptName != "") {
								$newstatus = "$dt1: Added to $dy1 at $tm1 at $lname0 ($un1)";
							}
							else {
								$newstatus = "";
							}
						}
						break;
					case "Copy":
						if ($FormApptDate == $NullDate) {
							$newstatus = "$dt1: Copied to Callback list ($un1)%0A$newstatus";
						}
						else {
							$newstatus = "$dt1: Copied to $dy1 at $tm1 at $lname0 ($un1)%0A$newstatus";
						}
						break;
					case "Move":
						if ($FormApptDate == $NullDate) {
							$newstatus = "$dt1: Moved to Callback list ($un1)%0A$newstatus";
						}
						else {
							$newstatus = "$dt1: Moved to $dy1 at $tm1 at $lname0 ($un1)%0A$newstatus";
						}
						break;
					case "Delete":
						if ($dy0 == $NullDate) {
							$newstatus = "$dt1: Deleted from Callback list ($un1)%0A$newstatus";
						}
						else {
							$newstatus = "$dt1: Deleted from $dy1 at $tm1 ($un1)%0A$newstatus";
						}
						break;
					default:
						break;
				}
				$FormApptStatus = $newstatus;

				// If change was not by USER, change "(USER)" to "(USER.)" to change blue highlighting style
				if ($UserHome != 0) $FormApptStatus = str_replace("(USER)" , "(USER.)" , $FormApptStatus);

				// Was this a special "reserved" callback slot? Recycle it, don't delete it
				if (substr($FormApptName, 0, 14) == "Reserved for #") {
					$FormApptName = "";
					$FormApptPhone = "";
					$FormApptEmail = "";
					$FormApptTags = "";
					$FormApptNeed = "";
					$FormApptInfo = "";
					$FormApptStatus = "";
					$UserFirst = "";
					$UserLast = "";
					$MyTimeStamp = "";
					$DeleteCode = "";
				}

				// Update the slot with any changes
				$query = "UPDATE $APPT_TABLE SET";
				$query .= "  `appt_name` = '$FormApptName'";
				$query .= ", `appt_phone` = '$FormApptPhone'";
				$query .= ", `appt_email` = '$FormApptEmail'";
				$query .= ", `appt_tags` = '$FormApptTags'";
				$query .= ", `appt_need` = '$FormApptNeed'";
				$query .= ", `appt_info` = '$FormApptInfo'";
				$query .= ", `appt_status` = '$FormApptStatus'";
				$query .= ", `appt_by` = '$UserFirst $UserLast'";
				$query .= ", `appt_change` = '$MyTimeStamp'";
				if ((($ApptView == "ViewDeleted") and ($typ1 == $DeleteCode))  // keep it deleted
				or ($FormApptReason == "Delete")) { // delete from active record
					$query .= ", `appt_type` = '$DeleteCode'";
					$query .= ", `appt_date` = '$NullDate'";
					$query .= ", `appt_time` = '$NullTime'";
				}
				else {
					$query .= ", `appt_type` = ''";
					// If user is being added to Callback list, update wait sequence number
					if (($dy0 == $NullDate) and ($wait0 == 0) and (($ApptView == "ViewUser") OR ($ApptView == "ViewCallback"))) {
						$MaxWaitSequence = ++$_SESSION["MaxWaitSequence"];
						$query .= ", `appt_wait` = '$MaxWaitSequence'";
					}
				}
				$query .= " WHERE `appt_no` = $FormApptNo";
				$success = mysqli_query($dbcon, $query);

				if ($success) {
					if ($UserHome == 0) { // Record appointment site in user table
						$query = "UPDATE $USER_TABLE SET";
						$query .= " `user_appt_site` = $loc0";
						$query .= " WHERE `user_index` = $UserIndex";
						mysqli_query($dbcon, $query);
					}

					// Add a new record to replace the one transferred to the deleted list
					if ($FormApptReason == "Delete") {
						InsertNewAppt('', '', '', '', '', '', '', 0, $dy0, $tm0, $loc0, 'SYSTEM');
					}

					// Deal with the old record
					// Note: If moving from a deleted record, this will leave it as a useable callback record
					// At some point, we may want to delete excess empty callback records.
					if (($ApptView != "ViewDelete") and ($FormApptOldNo > 0)) {
						$query = "UPDATE $APPT_TABLE SET";
						$query .= " `appt_name` = ''";
						$query .= ", `appt_phone` = ''";
						$query .= ", `appt_email` = ''";
						$query .= ", `appt_emailsent` = ''";
						$query .= ", `appt_tags` = ''";
						$query .= ", `appt_need` = ''";
						$query .= ", `appt_info` = ''";
						$query .= ", `appt_status` = ''";
						$query .= ", `appt_type` = ''";
						$query .= ", `appt_by` = '$UserFirst $UserLast'";
						$query .= ", `appt_change` = '$MyTimeStamp'";
						$query .= " WHERE `appt_no` = $FormApptOldNo";
						mysqli_query($dbcon, $query);
					}

					// Prepare to send email
					if ($dy0 != $NullDate) {
						$EM_Reason = $FormApptReason;
						$EM_View = $ApptView;
						$EM_Name = _Show_Chars($FormApptName, "text");
						$EM_Email = htmlspecialchars_decode($FormApptEmail ?? '');
						$EM_Day = $dy1;
						$EM_Time = $tm1;
						$EM_Location = $loc0;
					}

					if ($UserHome > 0) {
						$FirstSlotDate = $Date = $FormApptDate; // open in the same date list
					}
					else {
						$FirstSlotDate = $Date = "";
					}
				}
				else {
					if ($FormApptReason) {
						$Errormessage .= "Attempt to $FormApptReason $FormApptName failed.";
					}
				}
			}
			else {
				//if ($_SESSION["TRACE"]) {
				//	error_log("APPT: " . $UserName . ", view=" . $ApptView . ", action=" . $FormApptNo);
				//}
				// Was this a clash in the Callback list?
				if ($dy0 == $NullDate) {
					// Add a new record to accept the new taxpayer
					$newstatus = $FormApptStatus;
					switch ($FormApptReason) {
						case "Add": $newstatus = "$dt1: Added to Callback list ($un1)%0A$newstatus"; break;
						case "Move": $newstatus = "$dt1: Moved to Callback list ($un1)%0A$newstatus"; break;
						case "Copy": $newstatus = "$dt1: Copied to Callback list ($un1)%0A$newstatus"; break;
					}
					$ApptWaitSequence = ++$_SESSION["MaxWaitSequence"];
					InsertNewAppt($FormApptName, $FormApptPhone, $FormApptEmail, $FormApptTags, $FormApptNeed, $FormApptInfo, $newstatus, $ApptWaitSequence, $NullDate, $NullTime, $loc0, $UserFullName);
				}
				else {
					$Errormessage .= "What you just tried to do did not work because someone else just tried to do the same thing.  Please try again.";
				}
			}
	} // end of switch
} // end of POST

if ($_SESSION["TRACE"]) {
	$log_text = "APPT: " . $UserName . ", view=" . $ApptView . ", action=" . $FormApptNo;
	if (isset($FormApptReason)) $log_text .= ", reason=" . $FormApptReason;
	if (isset($FormApptName)) $log_text .=	", name=" . $FormApptName;
	error_log($log_text);
	if ($Errormessage) {
		$log_text = "APPT: " . $UserName . ", error=" . $Errormessage;
		error_log($log_text);
	}
}

// Make a list of locations allowed to this user (this could be changed to an array)
// MaxPermissions is the highest of all - helps control what buttons they see
$MaxPermissions = $UserPermissions;
// Always include the home site
$SitePermissions["S" . $UserHome] = $UserPermissions;
$UserTables = "0|" . $UserHome; // leading text to prevent match at index 0.
$query = "SELECT * FROM $ACCESS_TABLE";
$query .= " ORDER BY `acc_owner`, `acc_user`";
$locs = mysqli_query($dbcon, $query);
while ($row = mysqli_fetch_array($locs)) {
	$accOwner = $row["acc_owner"];
	$accUser = $row["acc_user"];
	$accLocation = $row["acc_location"];
	$accOption = $row["acc_option"];
	$accSOwner = "S" . $accOwner;
	if ($accOwner != $UserHome) {
		if ($accUser == $UserIndex) {
			// Permission for this user for this site
			$SitePermissions[$accSOwner] =  0;
			if ($accOption === "M") $accOption = $ACCESS_ALL | $MANAGER;
			$SitePermissions[$accSOwner] =  $accOption; // added for the user
			$MaxPermissions = $MaxPermissions | $accOption; // bitwise or
		}
		if (@$SitePermissions[$accSOwner] > 0) $UserTables .= "|" . $accOwner;
	}
}
$UserTables .= "|";

$FormShowInit = "";
$j = 0;
$query = "SELECT * FROM $SITE_TABLE";
$query .= " ORDER BY `site_name`";
$locs = mysqli_query($dbcon, $query);
while ($row = mysqli_fetch_array($locs)) {
	$SiteIndex = $row["site_index"];
	$SiteSIndex = "S" . $SiteIndex;
	$SiteName = htmlspecialchars_decode($row["site_name"] ?? '');
	$SiteInet = array(0, 0);
	$SiteInet = explode(":", $row["site_inet"]);
	$SiteSumRes = $row["site_sumres"];
	$Site10dig = $row["site_10dig"];
	$SiteOpen = $row["site_open"];
	$SiteClosed = $row["site_closed"];
	$SiteMessage = htmlspecialchars_decode($row["site_message"] ?? '');
	$SiteInstructions = htmlspecialchars_decode($row["site_instructions"] ?? '');
	$SiteAddress = htmlspecialchars_decode($row["site_address"] ?? '');
	$SiteContact = htmlspecialchars_decode($row["site_contact"] ?? '');
	$SiteAttach = $row["site_attach"];
	$LocationLookup[$SiteSIndex] = 0; // definition only, will be changed below
	$SiteNameList[$SiteSIndex] = $SiteName; // Name of all sites
	if ($SiteIndex > 1) { // skip "Unassigned" site (not relevant here)

		if ($UserOptions === "A") {
			$UserTables .=  "|" . $SiteIndex;
			$SitePermissions[$SiteSIndex] = $ACCESS_ALL | $ADMINISTRATOR;
		}

		if (($UserOptions === "A")
		or (($UserHome == 0) and ($SiteInet !== "") and ($SiteClosed >= $TodayDate))
		or (strpos($UserTables, ("|" . $SiteIndex . "|") ) > 0)) {
			$LocationList[0] = ++$j;
			$LocationList[$j] = $SiteIndex;
			$LocationName[$j] = $SiteName;
			$LocationInet[$j] = $SiteInet[0];
			$LocationInetLimit[$j] = (isset($SiteInet[1])) ? $SiteInet[1] : 0;
			$LocationSumRes[$j] = $SiteSumRes;
			$Location10dig[$j] = $Site10dig;
			$LocationOpen[$j] = $SiteOpen;
			$LocationClosed[$j] = $SiteClosed;
			$LocationIsOpen[$j] = (($TodayDate >= $LocationOpen[$j]) AND ($TodayDate < $LocationClosed[$j]));
			$LocationAddress[$j] = $SiteAddress;
			$LocationContact[$j] = $SiteContact;
			$LocationMessage[$j] = $SiteMessage;
			$LocationInstructions[$j] = $SiteInstructions;
			$LocationLookup[$SiteSIndex] = $j;

			// Create the email attachment text for this site
			$latext = "";
			$lahtml = "";
			if ($SiteAttach) {
				$breaktext = "";
				$breakhtml = "";
				$lahtml = "<ul class=\"attachlist\">";
				for ($lax = 0; $lax < sizeof($SystemAttachList)-1; $lax++) {
					$sap = explode("=", $SystemAttachList[$lax]);
					if (strpos($SiteAttach, $sap[0]) !== false) {
						$latext .= "$breaktext - $sap[0] ($sap[1])";
						$saplink = "<a href=\"$sap[1]\">$sap[1]</a>";
						$lahtml .= "$breakhtml<li> - $sap[0] ($saplink)";
						$breaktext = "\n";
						$breakhtml = "</li>";
					}
				}
				$lahtml .= "</li></ul>";
			}
			$LocationAttachText[$j] = $latext; // used for email
			$LocationAttachHTML[$j] = $lahtml; // used for UserView instructions

			if ($SiteIndex == $UserHome) {
				$MyWebsite = explode("|", $SiteAddress)[6];
			}

			// Open the form with the home site checkmarked if none other is
			if ($FormApptLoc == "") {
				if (! isset($_SESSION["UserSiteList"])) $_SESSION["UserSiteList"] = "|";
				if (($_SESSION["UserSiteList"] == "|") AND ($SiteIndex == $UserHome)) {
					$_SESSION["UserSiteList"] = "|$UserHome|";
				}
				if (strpos(("|" . $_SESSION["UserSiteList"]), "|$SiteIndex|") > 0) {
					$FormShowInit .= ", 1";
				}
				else {
					$FormShowInit .= ", 0";
				}
			}
			//$LocationName[1] .= "<br />$SiteIndex=" . @$_SESSION["UserSiteList"] . "($FormShowInit)"; // DEBUG
		}

	}
}

$newuserloclist = false;
if ($FormApptLoc == "") {
	$newuserloclist = true;
	$FormApptLoc = $LocationList[0] . $FormShowInit;
}
$LocationShow = explode(",", $FormApptLoc);

// If this is a self-schedule and there's an appointment, set to show that site
// Unless the user has selected another site
if (($ApptView == "ViewUser") AND $newuserloclist) { // a site has not been selected yet
	$_SESSION["UserLoc"] = 0; // to be tested later
	$query = "SELECT * FROM $APPT_TABLE";
	$query .= " WHERE `appt_email` = '$UserEmail'";
	$query .= " OR `appt_phone` = '$UserPhone'";
	$appt = mysqli_query($dbcon, $query);
	while ($row = mysqli_fetch_array($appt) AND ($_SESSION["UserLoc"] == 0)) {;
		if ($row["appt_type"] != $DeleteCode) {
			$Loc = $row["appt_location"];
			$LocationIndex = $LocationLookup["S" . $Loc];
			$LocationShow[$LocationIndex] = 1;
			$LocationShow[0] = 1;
			$_SESSION["UserLoc"] = $Loc;
		}
	}
}
$appt = []; // release the memory used;
$locs = []; // release the memory used;

// Send an email if set up previously
if ($EM_Reason != "") Send_Email($EM_Reason, $EM_View, $EM_Name, $EM_Email, $EM_Day, $EM_Time, $EM_Location);

//===========================================================================================
function Create_Menu() {
//===========================================================================================

	global $Debug, $Errormessage;
	global $MaxPermissions;
	global $ADD_CB;
	global $VIEW_CB;
	global $ADD_APP;
	global $MANAGER;
	global $ADMINISTRATOR;
	global $ApptView;
	global $MyWebsite;

	echo "<div class='menu-buttons'>\n";
	if ($ApptView != "ViewUser") {
		if ($MaxPermissions & $ADD_APP) {
			// Search group
			echo "<div class='menuButton' id='SearchAppt'>Search\n";
			echo "\t<div class='menuButtonList'>\n";
			echo "\t\t<div class='menuButtonListItem' id='SearchApptTags' onclick='Show_SearchBox(this.id)'>&#x2690; Search by Tag</div>\n";
			echo "\t\t<div class='menuButtonListItem' id='SearchApptPhone' onclick='Show_SearchBox(this.id)'>&#x260F; Search by Phone Number</div>\n";
			echo "\t\t<div class='menuButtonListItem' id='SearchApptName' onclick='Show_SearchBox(this.id)'>&#x263A; Search by Name</div>\n";
			echo "\t\t<div class='menuButtonListItem' id='SearchApptEmail' onclick='Show_SearchBox(this.id)'>&#x2709; Search by Email</div>\n";
			echo "\t</div>\n";
			echo "</div>\n";

			// Report group
			echo "<div class='menuButton' id='ReportAppt'>Reports\n";
			echo "\t<div class='menuButtonList'>\n";
			if (($ApptView == "ViewDaily") OR ($ApptView == "ViewCallback")) {
				echo "\t\t<div class='menuButtonListItem' id='PrintAppt' onclick='Print_Appointments()'>&#x2611; Print check-in list</div>\n";
				echo "\t\t<div class='menuButtonListItem' id='PrintERO' onclick='Print_ERO_Checklist()'>&#x2615; Print ERO Checklist</div>\n";
			}
			echo "\t\t<div class='menuButtonListItem' id='PrintExcel' onclick='Show_ExportBox()'><img id='Excel-icon' src='Images/Excel-icon.png' /> Export site data to excel</div>\n";
			echo "\t</div>\n";
			echo "</div>\n";
		}
		if ($MaxPermissions & ($MANAGER | $ADMINISTRATOR)) {
			// Tools group
			echo "<div class='menuButton' id='ToolsAppt'>Tools\n";
			echo "\t<div class='menuButtonList'>\n";
			echo "\t\t<div class='menuButtonListItem' id='ConfigAppt' onclick='Show_SlotBox()'>&#x1F557; Configure appointment slots</div>\n";
			echo "\t\t<div class='menuButtonListItem' id='ConfigSite' onclick='Site_Manage()'>&#x2699; Options and permissions</div>\n";
			echo "\t</div>\n";
			echo "</div>\n";
		}

		// Help group
		echo "<div class='menuButton' id='MenuAppt'>Help\n";
		echo "\t<div class='menuButtonList'>\n";
		echo "\t\t<div class='menuButtonListItem' id='HelpAppt' onclick=\"Open_Window('Appointment help.pdf')\"><b><span class='help'>?</span></b> Scheduler tutorial</div>\n";
		if ($MyWebsite) {
			echo "\t\t<div class='menuButtonListItem' id='HelpSite' onclick=\"Open_Window('$MyWebsite')\">&#x1F3E0; Your home website</div>\n";
		}
		echo "\t</div>\n";
		echo "</div>\n";
	}
	echo "<div class='menuButton' id='LogOut' onclick='Log_Out();'>Sign out</div>\n";
	echo "<div class='menuButton' style='z-index: 99' id='DebugText'>" . $Debug . "</div>\n";
	echo "</div>\n";
}

//===========================================================================================
function Calc_Slots() {
//	Calculates the number of appointments for each date and if any are available
//===========================================================================================
	global $Debug, $Errormessage;
	global $DateList, $DateFlag, $DateLoc, $TodayDate, $NullDate, $NullTime, $FirstMonth, $FirstYear, $LastMonth, $LastYear;
	global $APPT_TABLE, $FirstSlotDate;
	global $LocationShow, $LocationLookup, $LocationList, $LocationName;
	global $LocationIndex;
	global $SiteNameList;
	global $VERSION, $Errormessage;
	global $CustEList, $CustPList, $UserEmail, $UserPhone;
	global $FormApptNo, $FormApptReason, $FormApptPhone, $OtherAppts, $FormApptEmail, $FormApptName;
	global $UserPermissions, $MaxPermissions, $ADD_CB, $VIEW_CB, $ADD_APP, $VIEW_APP;
	global $ApptView;
	global $LocationCBList;
	global $LocationEmpty;
	global $RESERVED;
	global $dbcon;
	global $DateClassFlag;
	global $MyTimeStamp;
	global $siteHeight, $siteMaxHeight;
	global $UserName;
	global $Name, $Date, $Appt, $Phone, $Email;
	global $Date, $Time, $TodayDate, $NullDate;
	global $Appt, $Name, $Type;
	global $DeleteCode;

	$OldMO = "";
	$Date = $TodayDate;
	$OldDate = "";
	$AvailableCBSlots = [];
	$CustEList = "";
	$CustPList = "";

	if ($ApptView != "ViewUser") {
		echo "<div id='viewButtons'>\n";
		if ($MaxPermissions > ($ADD_CB + $VIEW_CB)) {
			echo "<button class='viewButton' id='ViewSummary' onclick='Change_View(this.id)'>Summary</button>\n";
		}
		else {
			echo "<button class='viewButton' id='ViewUser' onclick='Change_View(this.id)'>Personal</button>\n";
		}
		if ($MaxPermissions & $ADD_APP) {
			echo "<button class='viewButton' id='ViewDaily' onclick='Change_View(this.id)'>Daily</button>\n";
		}
		if ($MaxPermissions & $VIEW_CB) {
			echo "<button class='viewButton' id='ViewCallback' onclick='Change_View(this.id)'>Callback</button>\n";
		}
		if ($MaxPermissions & $ADD_APP) {
			echo "<button class='viewButton' id='ViewDeleted' onclick='Change_View(this.id)'>Deleted</button>\n";
		}
		echo "</div>\n";
		echo "<div id='viewLabel'>V I E W</div>\n";
	}
	else if ($UserPermissions > 0) {
		echo "<div id='viewButtons'>\n";
		echo "<button class='viewButton' id='ViewUser' style='background-color: lightgreen;' onclick='Change_View(this.id)'>Personal</button>\n";
		if ($UserPermissions > ($ADD_CB + $VIEW_CB)) {
			echo "<button class='viewButton' id='ViewSummary2' onclick='Change_View(this.id)'>Summary</button>\n";
		}
		else {
			echo "<button class='viewButton' id='ViewCallback2' onclick='Change_View(this.id)'>Callback</button>\n";
		}
		echo "</div>\n";
		echo "<div id='viewLabel'>V I E W</div>\n";
	}

	$siteHeight = $siteMaxHeight = "";
	if ($ApptView == "ViewUser") {
		$siteHeight = "100%";
		$siteMaxHeight = "100%";
		$calHidden = "visibility: hidden;";
		$siteTop = "top: 0;";
		$calMinHeight = "min-height: 0%"; // override default
	}
	else {
		$siteHeight = ($LocationList[0] * 1.5) . "em";
		$siteMaxHeight = "50%";
		$calHidden = "visibility: visible;";
		$siteTop = "top: 3em;";
		$calMinHeight = "";
	}
	echo "<div id='subSidebar' style='$siteTop'>\n";
	echo "<div id='viewSites' style='height:$siteHeight; max-height:$siteMaxHeight;'>\n";
	Location_Checkboxes();
	echo "</div>";

	// Initialize the calendar
	echo "<div id='viewCal' style='max-height: calc(100% - $siteHeight); $calHidden $calMinHeight'>\n";
	echo "<div id='CalBoxDiv'>\n";
	if (@$_SESSION["CalStartMo"] == 0) {
		$DPART = explode("-", $TodayDate);
	       	$_SESSION["CalStartYr"] = $DPART[0];
	       	$_SESSION["CalStartMo"] = $DPART[1];
	}

	// Count appointments for the calendar
	$CustEList = "";
	$CustPList = "";
	$_SESSION["MaxWaitSequence"] = 0;
	$query = "SELECT * FROM $APPT_TABLE";
	$query .= " ORDER BY `appt_date`, `appt_time`, `appt_location`, `appt_wait`";
	$appointments = mysqli_query($dbcon, $query);
	while ($row = @mysqli_fetch_array($appointments)) {
		$Appt = $row["appt_no"];
		$Date = $row["appt_date"];
		$Time = $row["appt_time"];
		$Name = htmlspecialchars_decode($row["appt_name"] ?? '');
		$Email = $row["appt_email"];
		$Phone = $row["appt_phone"];
		$Location = $row["appt_location"];
		$LocationSIndex = "S" . $Location;
		$Type = $row["appt_type"];
		$Status = $row["appt_status"];

		// Check for a valid site number. If invalid, delete it
		if (! isset($LocationLookup[$LocationSIndex])) {
			$query = "DELETE FROM $APPT_TABLE";
			$query .= " WHERE `appt_no` = $Appt";
			mysqli_query($dbcon, $query);
			if ($_SESSION["TRACE"]) {
				error_log("APPT: SYSTEM, Deleted record with Name  " . $Name . ", site " . $Location);
			}
			continue;
		}
		$LocationIndex = $LocationLookup[$LocationSIndex];
		if ($Date != $NullDate) {
			Add_to_matchlist($Name, $Email, $Phone, $Appt, $Date, $Time, $LocationSIndex);
		}
		if (! isset($LocationName[$LocationIndex])) continue; // get the next one

		if (($Date == $NullDate) AND ($Name != "") AND (@$LocationShow[$LocationIndex])) {
			Check_UserClassFlags($Type, $Status);
		}

		// Make a list of appointments with the same phone or email for the user view
		if ($ApptView == "ViewUser") User_view_list();

		// $LocationIndex undefined causes an undefined offset error DEBUG
		if (!isset($LocationShow[$LocationIndex])) {
			//error_log("APPTX: SYSTEM, LocationIndex=" . $LocationIndex);
			//error_log("APPTX: SYSTEM, LocationName=" . $LocationName[$LocationIndex]);
			$LocationShow[$LocationIndex] = "";
		}
		if (($Type != $DeleteCode) AND ($LocationShow[$LocationIndex] > 0)){

			// Count callback list slots
			if (! isset($AvailableCBSlots[$LocationIndex])) $AvailableCBSlots[$LocationIndex] = 0;
			if (! isset($LocationCBList[$LocationIndex])) $LocationCBList[$LocationIndex] = 0;
			if ($Date == $NullDate) {
				if ($Name == "") $AvailableCBSlots[$LocationIndex]++;
				else $LocationCBList[$LocationIndex]++; // count how many names there are
			}

			// Analyze used (or reserved) dates
			if ($Date != $NullDate) {
				$DPART = explode("-", $Date);
				$YR = $DPART[0];
				$MO = $DPART[1];
				if ($FirstYear == 0) $FirstYear = $YR;
				if ($FirstMonth == 0) $FirstMonth = $MO;
				$LastMonth = $MO;
				$LastYear = $YR;
				if ($MO != $OldMO) {
					$OldMO = $MO;
					$OldDate = "";
				}
				$OldDate = $Date;
				$DateTimeLoc = $Date . $Time . $Location;

				// DateFlag: 0 = busy, 1 = available, 2 = self-scheduled
				$DateFlag[$Date] = max(@$DateFlag[$Date], 1); // could already be a 2

				// Is USER the latest status entry?
				Check_UserClassFlags("A", $Status);
				if ($DateClassFlag) $DateFlag[$Date] = 2;

				// Find the first empty slot
				if (! isset($DateList[$DateTimeLoc])) $DateList[$DateTimeLoc] = "";
				if (($Name == "") OR (($ApptView != "ViewUser") AND ($Name == $RESERVED))) {
					if ($Date >= $TodayDate) {
						if ($FirstSlotDate == "") $FirstSlotDate = $Date;
						// record the first
						if ($DateList[$DateTimeLoc] == "") $DateList[$DateTimeLoc] = $Appt;
						// override any reserved with an empty slot so it will be chosen first
						if ($Name == "") $DateList[$DateTimeLoc] = $Appt;
					}
					// set various counts to be used in displays
					@$DateList[$Date]++;
					@$DateList[$DateTimeLoc . "Count"]++;
					if ($Name == $RESERVED) {
						@$DateList[$Date . "ResCount"]++;
						@$DateList[$DateTimeLoc . "ResCount"]++;
					}
				}
				else {
					//@$DateList[$Date]++;
					@$DateList[$Date . "Busy"]++;
					@$DateList[$DateTimeLoc . "Busy"]++;
				}
				@$DateLoc[$Date] .= ", $Location";
				@$LocationEmpty[$LocationIndex]++;
			}



			// Find the maximum wait sequence value from the callback lists
			$WaitSeq = $row["appt_wait"];
			if ($WaitSeq > $_SESSION["MaxWaitSequence"]) $_SESSION["MaxWaitSequence"] = $WaitSeq;
		}
	}
	$appointments = []; // release the memory used

	Make_Calendar();

	echo "</div></div></div>\n";

	if ($FirstSlotDate == "") $FirstSlotDate = $Date;

	// Add a blank Callback slot for any site that doesn't have one
	for ($j = 1; $j <= $LocationList[0]; $j++) {
		if ((! @$AvailableCBSlots[$j]) & (@$LocationShow[$j])) {
			InsertNewAppt('', '', '', '', '', '', '', '', $NullDate, $NullTime, $LocationList[$j], 'SYSTEM');
			if ($_SESSION["TRACE"]) {
				error_log("APPT: SYSTEM, Added blank record for site " . $LocationList[$j]);
			}
		}
	}
}

//===========================================================================================
function User_view_list () {
//===========================================================================================
	global $Appt, $Date, $Time, $Name, $Type;
	global $Phone, $UserPhone;
	global $Email, $UserEmail;
	global $TodayDate, $NullDate;
	global $CustEList, $CustPList;
	global $LocationName, $LocationIndex;
	global $DeleteCode;

	if ($Type == $DeleteCode) return;
	if ($Name == "") return;
	if (($Date < $TodayDate) AND ($Date != $NullDate)) return;

	$phonematch = (($Phone != "") AND ($Phone != "000-000-0000") AND ($Phone == $UserPhone)) ? true : false;
	$emailmatch = (($Email != "") AND ($Email == $UserEmail)) ? true : false;
	if ($emailmatch) {
		$At = $LocationName[$LocationIndex];
		if ($Date == $NullDate) {
			$CustEList .= "&bull; On the callback list at the $At\n";
			$CustEList .= " (<span class='custDelete' onclick='Cust_Delete($Appt, \"On the callback list at the $At\")'>&nbsp;Cancel&nbsp;</span>)\n<br />";
		}
		else {
			$ShowTime = Format_Time($Time, false);
			$ShowDate = Format_Date($Date, true);
			$CustEList .= "&bull; $ShowTime on $ShowDate at the $At\n";
			$CustEList .= " (<span class='custDelete' onclick='Cust_Delete($Appt, \"$ShowTime on $ShowDate at the $At\")'>&nbsp;Cancel&nbsp;</span>)\n<br />";
		}
	}
	else if ($phonematch) {
		$At = $LocationName[$LocationIndex];
		if ($Date == $NullDate) {
			$CustPList .= "&bull; " . _Show_Chars($Name, "text");
			$CustPList .= ", on the callback list at the $At\n";
			$CustPList .= " (<span class='custDelete' onclick='Cust_Delete($Appt, \"On Callback list at the $At\")'>&nbsp;Cancel&nbsp;</span>)\n<br />";
		}
		else {
			$ShowTime = Format_Time($Time, false);
			$ShowDate = Format_Date($Date, true);
			$CustPList .= "&bull; " . _Show_Chars($Name, "text");
			$CustPList .= ", at $ShowTime on $ShowDate at the $At\n";
			$CustPList .= " (<span class='custDelete' onclick='Cust_Delete($Appt, \"$ShowTime on $ShowDate at the $At\")'>&nbsp;Cancel&nbsp;</span>)\n<br />";
		}
	}
}

//===========================================================================================
function Add_to_matchlist( $Name, $Email, $Phone, $Appt, $Date, $Time, $LocationSIndex) {
//===========================================================================================
	global $FormApptEmail;
	global $FormApptName;
	global $FormApptDate, $FormApptTime;
	global $FormApptPhone;
	global $FormApptReason;
	global $FormApptNo;
	global $LocationName;
	global $OtherAppts;
	global $SiteNameList;
	global $NullDate;
	global $RESERVED;

	if (($Date == $FormApptDate) && ($Time == $FormApptTime)) return; // Skip, this is me
	if ($Name == $RESERVED) return; // Don't match on reserved slots

	// Is there a name, phone or email match?
	$npematch = false;
	if (($Email != "") AND ($Email == $FormApptEmail)) $npematch = true;
	else if (($Name  != "") AND (strcasecmp($Name, $FormApptName) == 0)) $npematch = true;
	else if ((($Phone != "") AND ($FormApptPhone != ""))
		AND (($Phone == $FormApptPhone)
		OR strpos(" " . $Phone, $FormApptPhone)
		OR strpos(" " . $FormApptPhone, $Phone))) $npematch = true;
	$At = (isset($SiteNameList[$LocationSIndex])) ? $SiteNameList[$LocationSIndex] : "Unknown";

	// Make a list of other appointments with that same phone or email
	if ($npematch AND ($FormApptReason == "Add") AND ($Appt != $FormApptNo)) {
		// Do not convert characters back to ASCII in this loop
		if ($Date == $NullDate) {
			$OtherAppts .= ($Name . "\\n  on Callback list at the $At\\n");
		}
		else {
			$ShowTime = Format_Time($Time, true);
			$ShowDate = str_replace("-", "/", substr($Date, 5, 5));
			$OtherAppts .= ($Name . "\\n  $ShowDate at $ShowTime at the $At\\n");
		}
	}
}

//===========================================================================================
function Location_Checkboxes() {
//===========================================================================================
	global $Debug, $Errormessage;
	global $dbcon, $USER_TABLE;
	global $ApptView, $ADD_APP, $ADD_CB, $VIEW_CB;
	global $LocationList, $LocationShow, $LocationInet, $LocationName;
	global $LocationOpen, $LocationClosed, $TodayDate;
	global $UserOptions, $SitePermissions, $SingleSite;
	global $ShowDagger;
	global $Errormessage;
	global $FormApptLoc;
	global $UserSitelist;
	global $LocationIsOpen;
	global $calBoxTop;

	$NewUserSitelist = "";

	if ($LocationList[0] > 0) {
		echo "<div id='SiteBoxDiv'>";
		echo "<table id='site_table'>\n";
		$SingleSite = "";
		$SiteSelected = false;
		for ($j = 1; $j <= $LocationList[0]; $j++) {
			$checked = "";
			$SiteFlag = "";
			$disabled = "";
			if ((($ApptView != "ViewUser") OR $LocationIsOpen[$j]) AND ($LocationList[0] == 1)) {
			      	$LocationShow[1] = 1; // if only one, show it
			}
			if ($LocationShow[$j] == 1) {
				$checked = "checked='checked'";
				if (! $SingleSite) $SingleSite = $LocationList[$j];
				$NewUserSitelist .= "|" . $LocationList[$j];
				$SiteSelected = true;
			}
			$SiteDBno = "S" . $LocationList[$j];
			$SitePermission = (isset($SitePermissions[$SiteDBno])) ? $SitePermissions[$SiteDBno] : 0;
			$color = "black";

			if ($UserOptions !== "A") {
				switch ($ApptView) {
					case "ViewUser":
						if ($LocationInet[$j] == "C") {
							$SiteFlag = "&dagger;";
							$ShowDagger = true;
						}
						if ($LocationOpen[$j] > $TodayDate) {
							$checked =
							$disabled = "disabled='disabled'";
							$ShowDate = Format_Date($LocationOpen[$j], false);
							$SiteFlag .= "<br />(try again on $ShowDate)";
							$color = "grey";
						}
						if ($LocationClosed[$j] < $TodayDate) {
							$checked =
							$disabled = "disabled='disabled'";
							$SiteFlag .= "<br />(reservations closed)";
							$color = "grey";
						}
						break;
					case "ViewSummary":
						if ($SitePermission == 0) {
							$checked = "disabled";
							$LocationShow[$j] = 0;
						}
						break;
					case "ViewDeleted":
					case "ViewDaily":
						if (! ($SitePermission & $ADD_APP)) {
							$checked = "disabled";
							$LocationShow[$j] = 0;
						}
						break;
					case "ViewCallback":
						if (! ($SitePermission & $VIEW_CB)) {
							$checked = "disabled";
							$LocationShow[$j] = 0;
						}
						break;
				}
			}
			if ($ApptView == "ViewUser") {
				if ($LocationInet[$j]) {
					echo "<tr $disabled><td><input id='Loc$j' type='radio' name='Loc00' $checked value='$j' onchange='Change_Loc(Loc$j, $j)'/></td><td id='LocName$j' style='color: $color;'>" . $LocationName[$j] . " $SiteFlag</td></tr>\n";
			}	}
			else {
				echo "<tr><td><input id='Loc$j' type='checkbox' $checked value='$j' onchange='Change_Loc(Loc$j, $j)'/></td><td id='LocName$j'>" . $LocationName[$j] . "</td></tr>\n";
			}
		}
		echo "</table>\n";
		echo "</div>\n";
	}

	// Save the user's selection of sites
	$NewUserSitelist .= "|";
	if ($NewUserSitelist != @$_SESSION["UserSiteList"]) {
		$MyUserIndex = $_SESSION["UserIndex"];
		$_SESSION["UserSiteList"] = $NewUserSitelist;
		$query = "UPDATE $USER_TABLE SET";
		$query .= " `user_sitelist` = '$NewUserSitelist'";
		$query .= " WHERE `user_index` = $MyUserIndex";
		mysqli_query($dbcon, $query);
	}

}

//===========================================================================================
function Make_Calendar() {
//	Creates a calendar beginning with the earliest appointment to the last appt + 1 month
//===========================================================================================
	global $Debug, $Errormessage;
	global $BgColor;
	global $DateList;
	global $DateFlag;
	global $DateLoc;
	global $DateIndex;
	global $TodayDate;
	global $FirstMonth, $LastMonth;
	global $FirstYear, $LastYear;
	global $ApptView;
	global $MaxPermissions, $ADD_APP;
	global $isAdministrator, $isManager;

	if ($FirstYear == 0) {
		$YR = date("Y");
		$MO = date("m") - 1;
		$StopAt = date("Ym", strtotime("+6 months"));
	}
	else {
		$YR = $FirstYear; //$_SESSION["CalStartYr"];
		$MO = $FirstMonth - 1; //$_SESSION["CalStartMo"] - 1;
		$StopAt = $LastYear . $LastMonth;
	}
	
	while (($YR . $MO) <= ($StopAt)) {
		$MO++;
		if ($MO > 12) {
			$YR++;
			$MO -= 12;
		}

		$YMD = mktime(0, 0, 0, $MO, 1, $YR);
		$MON = date("F", $YMD);
		$DOW = date("w", $YMD);
		$LDM = date("t", $YMD);
		if (strlen($MO) == 1) $MO = "0" . $MO;
		$MonthIndex = $YR . "-" . $MO;

		echo "<table class='calTable'>\n";
		echo "<tr id='ID$MonthIndex' class='calMonth'> <th colspan='5'>" . $MON . "</th> <th colspan='2'>" . $YR . "</th></tr>\n";
		$d_index = 1 - $DOW;
		for ($w = 1; $w < 7; $w++) {
			$w_html = "";
			for ($d = 1; $d < 8; $d++) {
				if (strlen($d_index) == 1) $DY = "0" . $d_index; else $DY = $d_index;
				$DateIndex = $YR . "-" . $MO . "-" . $DY;
				$DateBorder = ($DateIndex == $TodayDate) ? "style='border: 2px solid darkgreen;'" : "";
				if (($d_index < 1) OR ($d_index > $LDM)) {
					$w_html .= "<td id='ID$DateIndex' class=\"calNoAppt\" $DateBorder> </td>\n";
				}
				else {
					$clickop = "";
					$myclass = "";
					$AvailAppts = @$DateList[$DateIndex]; // may not be one of our dates

					# Set up click action if appropriate
					if ($isAdministrator
						OR $isManager
						OR ($MaxPermissions & $ADD_APP)) {
						$clickop = "onclick='New_Date(\"" . $DateIndex . "\", 1)'";
					}
					else {
						$myclass .= " noSelect"; // hide pointer
					}

					// set up title for the date
					if (($AvailAppts == 0) OR ($DateIndex < $TodayDate)) {
						$mytitle = "No appointments are available";
						$myclass .= " calNoAppt";
					}
					else {
						$mytitle = $AvailAppts . " appointment" . plural($AvailAppts) . " available";
						$ResAppts = @$DateList[$DateIndex . "ResCount"];
						if ($ResAppts) {
							$mytitle .= "\n\t$ResAppts of which " . isare($ResAppts) . " reserved";
						}
					}

					$mytitle = "title=\"$mytitle\""; // envelope the mytitle string

					// set up colors, and pointers
					if (@$DateFlag[$DateIndex] > 0) { // there are appointments on this date
						$myclass = "";

						if ($ApptView != "ViewUser") {
							// set background
							$myclass = " apptFull";
							if ($AvailAppts AND ($DateIndex >= $TodayDate)) {
								$myclass = " apptOpen";
							       	if (@$ResAppts) {
									$myclass = ($ResAppts == $AvailAppts) ? " apptWarn" : " apptOpen";
								}
							}

							// highlight user-made appt date text:
							if ($DateFlag[$DateIndex] == 2) $myclass .= " apptUser";
						}
						else { // ViewUser - hide date colors and click ability from user
							$mytitle = "";
							$myclass = " noSelect";
							if ($AvailAppts AND ($DateIndex >= $TodayDate)) {
								$myclass .= " apptOpen";
							}
						}

					}
					$myclass = "class=\"calDate" . $myclass . "\""; // envelope the myclass string

					// Add the day
					$w_html .= "<td id='ID$DateIndex' $myclass $DateBorder $mytitle $clickop>$d_index</td>\n";
				}
				$d_index++;
				if ($d_index > $LDM) $w = 7; // don't do another loop
			}
			// Write the week
			echo "<tr>\n" . $w_html . "</tr>\n";
		}
		echo "</table>\n";
	}
}


//===========================================================================================
function Add_Wait_Slots($SlotCount, $SlotLoc) {
//	This was created for cases where an answering machine presented calls in
//	last-in, first-out order. Once reserved, the answered calls can be
//	added to the callback list in proper reverse order.
//
//	$SlotCount = Reserved slots to be added to the callback list
//===========================================================================================
	global $Debug, $Errormessage;
	global $UserHome;
	global $APPT_TABLE;
	global $MyTimeStamp;
	global $Errormessage;
	global $NullDate;
	global $NullTime;
	global $TodayDate;
	global $TimeNow;
	global $dbcon;
	global $UserFirst, $UserLast;
	global $UserName;
	global $UserFullName;

	for ($j = 1; $j <= $SlotCount; $j++) {
		$dt1 = str_replace("-", "/", substr($TodayDate, 5, 5)) . "_" . $TimeNow;
		$Status = "$dt1: Reserved entry added ($UserName)%0A";
		$ResName = "Reserved for #" . $j;
		$MaxWaitSequence = ++$_SESSION["MaxWaitSequence"];
		InsertNewAppt($ResName, '', '', '', '', '', $Status, $MaxWaitSequence, $NullDate, $NullTime, $SlotLoc, $UserFullName);
	}

	$appointments = []; // release the memory used
}
//===========================================================================================
function isare($val) {
//===========================================================================================
	return (($val == 1) ? "is" : "are");
}
//===========================================================================================
function plural($val) {
//===========================================================================================
	return (($val == 1) ? "" : "s");
}

//===========================================================================================
function Check_UserClassFlags($Type, $Status) {
//===========================================================================================
	global $Debug, $Errormessage;
	global $DeletedClassFlag, $CallbackClassFlag;
	global $DateClassFlag, $DeleteCode;
	$DateClassFlag = false;

	// Is USER the latest status entry?
	$a = strpos($Status, "%0A");
	if ($a) $b = substr($Status, 0, $a);
	else $b = $Status;
	if (strpos($b, "(USER)") > 0) {
		switch ($Type) {
		case "A":
			$DateClassFlag = true;
			break;
		case $DeleteCode:
			$DeletedClassFlag = true;
			break;
		default:
		       	$CallbackClassFlag = true;
		}
	}
}


//===========================================================================================
function Add_CB_Status($LTitle, $LocationIndex) {
// Addes the callback list status onto the given appointment list title line
//===========================================================================================
	global $Debug, $Errormessage;
	global $LocationEmpty, $LocationCBList, $ApptView;
	
	if ($ApptView != "ViewUser") {
		$CBAmount = +@$LocationCBList[$LocationIndex];
		$LColor = (($CBAmount > 0) AND (+@$LocationEmpty[$LocationIndex] <= $CBAmount)) ? "yellow" : "transparent";
		$LTitle .= "<span style='position: absolute; right: 0.5em; background-color: $LColor;'>";
		$LTitle .= "(" . +@$LocationCBList[$LocationIndex] . " on Callback list)</span>";
		}
	return ($LTitle);
}

//===========================================================================================
function List_Locations($SelectLocation) {
// Lists locations that the user can manage
//===========================================================================================
	global $Debug, $Errormessage;
	global $LocationList, $LocationName;
	global $UserOptions, $SitePermissions, $MANAGER;

	for ($j = 1; $j <= $LocationList[0]; $j++) {
		$SiteDBno = "S" . $LocationList[$j];
		$SitePermission = @$SitePermissions[$SiteDBno];
		if (($UserOptions === "A") or (@$SitePermission & $MANAGER)) {
			$Selected = ($LocationList[$j] == $SelectLocation) ? "selected=\"selected\"" : "";
			echo "<option class='locmanage' value='" . $LocationList[$j]. "' $Selected>" . $LocationName[$j] . "</option>\n";
		}
	}
}

//===========================================================================================
function List_Patterns() {
// Lists patterns that the user can use
//===========================================================================================
	global $LocationList;
	global $SiteNameList;
	global $UserOptions;
	global $CurrentLocation;
	global $dbcon, $SCHED_TABLE, $SITE_TABLE;
	global $UserHome;

	$query = "SELECT * FROM $SITE_TABLE";
		$query .= " LEFT JOIN $SCHED_TABLE";
		$query .= " ON $SCHED_TABLE.sched_location = $SITE_TABLE.site_index";
	$query .= " ORDER BY `site_index`, `sched_name`";
	$scheds = mysqli_query($dbcon, $query);
	$oldLoc = 0;
	while ($row = mysqli_fetch_array($scheds)) {
		$patternId = $row["sched_index"];
		$patternLoc = $row["site_index"];
		$patternName = _Show_Chars($row["sched_name"], "text");
		$patternData = $row["sched_pattern"];
		if ($patternLoc != $oldLoc) {
			if ($oldLoc != 0) echo "\n</select>";
			$visible = ($patternLoc == $UserHome) ? "" : "style=\"display: none\"";
			if ($patternLoc != "") {
				echo "\n<select id=\"SBOptions$patternLoc\" class=\"SBOptClass\" $visible onchange=\"Fill_Pattern();\">";
				echo "\n\n<option value=\"\" selected=\"selected\">(no pattern selected)</option>";
			}
			$oldLoc = $patternLoc;
		}
		if (($patternId > 0) AND ($patternLoc != "")) {
			echo "\n\n<option value='$patternLoc|$patternId|$patternData'>$patternName</option>\n";
		}
	}
	if ($patternLoc != "") echo "\n</select>";
}

//===========================================================================================
function Send_Email($Request, $View, $Name, $Email, $Date, $Time, $Location) {
//===========================================================================================
	global $Debug, $Errormessage;
	global $LocationName;
	global $LocationLookup;
	global $LocationMessage;
	global $LocationAddress;
	global $LocationContact;
	global $LocationAttachText;
	global $SystemAttachList;
	global $NullDate;
	global $EM_Reason;
	global $UserEmail;
	global $UserName;
	global $FormApptNo;
	global $FormApptStatus;
	global $dt1, $un1;
	global $TodayDate;
	global $dbcon, $APPT_TABLE;

	// Should we send email
	if ($Email == "") return; // no email address to send to
	$LocIndex = $LocationLookup["S" . $Location];
	$msg = $LocationMessage[$LocIndex];
	if (substr($msg, 0, 4) == "NONE") return; // messaging has been disabled

	$SiteAddress = explode("|", $LocationAddress[$LocIndex]);

	$Time = Format_Time($Time, true);

	$to = htmlspecialchars_decode($Email ?? '');

	$from = (isset($SiteAddress[5]) AND ($SiteAddress[5] != "")) ? $SiteAddress[5] : @$_SESSION["SystemEmail"];
	/*if ($from == "")*/ $from = "no-reply@tax-aide-reservations.no-email";
	$from = htmlspecialchars_decode($from ?? '');

	$headers = "From: " . $LocationName[$LocIndex] . " Tax-Aide <" . $from . ">";
	
	$subject = "Your Tax-Aide appointment";

	switch ($Request) {
		case "Add":
		case "Move":
		case "Copy":
			if ($LocationMessage[$LocIndex] > "A") {
				$message = $LocationMessage[$LocIndex];
			}
			else {
				$message = $_SESSION["DefaultEmail"];
			}
			$message = _Show_Chars($message,"text");

			$message = str_replace("[TPNAME]",      $Name, $message);
			$message = str_replace("[TIME]",        $Time, $message);
			$message = str_replace("[DATE]",        $Date, $message);
			$message = str_replace("[SITENAME]",    $LocationName[$LocIndex], $message);
			$message = str_replace("[ADDRESS]",     $SiteAddress[0], $message);
			$message = str_replace("[CITY]",        $SiteAddress[1], $message);
			$message = str_replace("[STATE]",       $SiteAddress[2], $message);
			$message = str_replace("[ZIP]",         $SiteAddress[3], $message);
			$message = str_replace("[PHONE]",       $SiteAddress[4], $message);
			$message = str_replace("[EMAIL]",       $SiteAddress[5], $message);
			$message = str_replace("[WEBSITE]",     $SiteAddress[6], $message);
			$message = str_replace("[STATESITE]",   $_SESSION["SystemURL"], $message);
			$message = str_replace("[CONTACT]",     $LocationContact[$LocIndex], $message);
			$message = str_replace("[ATTACHMENTS]", $LocationAttachText[$LocIndex], $message);
			for ($lax = 0; $lax < sizeof($SystemAttachList)-1; $lax++) {
				$sap = explode("=", $SystemAttachList[$lax]);
				$testShortcode = "[$sap[0]]";
				$replacement = "$sap[0] ($sap[1])";
				$message = str_replace($testShortcode, $replacement, $message);
			}
			break;
		default:
			return;
	}

	if (substr($to, -5, 5) == ".test") {
		$Errormessage .= "The following email would have been sent:\\n\\n" . str_replace("\n", "\\n", $message);
	}
	else {

		// Test of PHPMailer
		// Could not get this to work. GoDaddy requires too much customization
		//$exmail = new PHPMailer();
		//$exmail->setFrom($from, 'Jeff');
		//$exmail->Subject = 'test email';
		//$exmail->Body = 'test email from PHPMailer';
		//$exmail->addAddress($to);
		//$exmail->addAttachment($filepath, $filename);
		//if ($exmail->send()) $result = "success";
		//else $result = "Error=$exmail->$errorInfo";
		//if ($_SESSION["TRACE"]) {
		//	error_log("APPT: $UserName, PHPMailer From=$from, To=$to, Result=$result");
		//}

		$success = mail($to, $subject, $message, $headers);
		$message = $headers . "\n" . "To: " . $to . "\n" . $message;
		mail("appt@bogarthome.net", $subject, $message, $headers);
		if (! $success) {
			$Errormessage .= "Not able to send email to $Name at $Email.";
			$emerr = $success . ": " . error_get_last()['message'];
			if ($_SESSION["TRACE"]) error_log("APPT: " . $UserName . ", Email error: ". $emerr);
		}
		else {
			$FormApptStatus = "$dt1: Email sent to $Email ($un1)%0A$FormApptStatus";
			$query = "UPDATE $APPT_TABLE SET";
			$query .= "  `appt_status` = '$FormApptStatus'";
			$query .= ", `appt_emailsent` = '$TodayDate'";
			$query .= " WHERE `appt_no` = $FormApptNo";
			mysqli_query($dbcon, $query);
			if ($_SESSION["TRACE"]) {
				error_log("APPT: " . $UserName . ", Email to ". $Email . " " . $headers);
			}
		}
	}
	$EM_Reason = "";
}

//===========================================================================================
function Appt_Box_Buttons() {
//===========================================================================================
	global $Debug, $Errormessage;
	global $MaxPermissions;
	global $ADD_CB;
	global $ADD_APP;
	global $ApptView;

	$SavLabel = "Save";
	$CanLabel = "Close";
	switch ($ApptView) {
	case "ViewUser":
		$d_del = "Disabled";
		$d_mov = "Disabled";
		$d_sav = "";
		$d_eml = "";
		$SavLabel = "Save";
		$CanLabel = "Cancel";
		break;
	case "ViewSummary":
		$d_del = "Disabled";
		$d_mov = "Disabled";
		$d_sav = ($MaxPermissions & $ADD_APP) ? "" : "Disabled";
		$d_eml = "";
		break;
	case "ViewDaily":
		$d_del = ($MaxPermissions & $ADD_APP) ? "" : "Disabled";
		$d_mov = $d_del;
		$d_sav = $d_mov;
		$d_eml = "";
		break;
	case "ViewDeleted":
		$d_del = "style='display:none;'";
		$d_mov = ($MaxPermissions & $ADD_APP) ? "" : "Disabled";
		$d_sav = $d_mov;
		$d_eml = "";
		break;
	case "ViewCallback":
		$d_del = ($MaxPermissions & $ADD_CB) ? "" : "Disabled";
		$d_mov = $d_del;
		$d_sav = $d_mov;
		$d_eml = "";
		break;
	default: $d_del = $d_mov = $d_sav = $d_eml = "Disabled";
	}
	echo "<button id='IDApptSave' class='apptButton' $d_sav onclick='ApptOp(\"Save\")'>$SavLabel</button>\n";
	echo "<button id='IDApptMove' class='apptButton' $d_mov onclick='ApptOp(\"Move1\")'>Move Appt</button>\n";
	echo "<button id='IDApptCopy' class='apptButton' $d_mov onclick='ApptOp(\"Copy1\")'>Copy Appt</button>\n";
	echo "<button id='IDApptCancel' class='apptButton' onclick='ApptOp(\"Cancel\")'
		title='This will close this window - be sure to save any changes first!'>$CanLabel</button>\n";
	echo "<button id='IDApptDelete' class='apptButton' $d_del onclick='ApptOp(\"Delete\")'
		title='Move this record to the deleted list'>Delete</button>\n";
	echo "<button id='IDApptSendEmail' class='apptButton' $d_eml onclick='ApptOp(\"SendEmail\")'
		title='This will close this window - be sure to save any changes first!'>Send Email</button>\n";
	echo "<a id='OpenEmail' href='' target='_blank'></a>\n";
}

//===========================================================================================
function Do_Search() {
//===========================================================================================
	global $Debug, $Errormessage;
	global $FormApptNo;
	global $FormApptPhone;
	global $FormApptEmail;
	global $FormApptTags;
	global $FormApptName;
	global $APPT_TABLE;
	global $SearchList;
	global $UserOptions;
	global $NullDate;
	global $dbcon;
	global $FindByVal;
	global $DeleteCode;

	$query = "SELECT * FROM $APPT_TABLE";
	switch ($FormApptNo) {
		case "FindByPhone":
			$query .= " WHERE `appt_phone` LIKE '%$FormApptPhone%'";
			$FindByVal = $FormApptPhone;
			break;
		case "FindByEmail":
			$query .= " WHERE `appt_email` LIKE '%$FormApptEmail%'";
			$FindByVal = $FormApptEmail;
			break;
		case "FindByTags":
			$query .= " WHERE `appt_tags` LIKE '%$FormApptTags%'";
			$FindByVal = $FormApptTags;
			break;
		case "FindByName":
			$n = _Clean_Chars($FormApptName);
			$query .= " WHERE `appt_name` LIKE '%$n%'";
			$FindByVal = $FormApptName;
			break;
	}
	$query .= "ORDER BY `appt_location`, `appt_date`, `appt_time`";
	$appointments = mysqli_query($dbcon, $query);
	$j = 0;
	while($row = mysqli_fetch_array($appointments)) {
		$Name = $row['appt_name'];
		$Date = $row['appt_date'];
		$Time = $row['appt_time'];
		$Site = $row['appt_location'];
		$Appt = $row['appt_no'];
		$Del = $row['appt_type'];
		if ($Date == $NullDate) {
			$Time = ($Del == $DeleteCode) ? "deleted" : "callback";
		}
		$SearchList[$j++] = array("$Name", "$Date", "$Time", "$Site", "$Appt"); 
	}	

	$appointments = [];
}

//===========================================================================================
function Show_Search() {
//===========================================================================================
	global $Debug, $Errormessage;
	global $SearchList;
	global $SiteNameList;
	global $LocationName;
	global $LocationLookup;
	global $FormApptNo;
	global $UserTables;

	$tablehead = true;

	if (substr($FormApptNo, 0, 6) != "FindBy") return;

	if (isset($SearchList) && count($SearchList) > 0) {
		$site = 0;
		for ($j = 0; $j < count($SearchList); $j++) {
			$found = $SearchList[$j];
			$mysite = strpos($UserTables, "|" . $found[3] . "|");
			$checkbox = ($mysite) ? "&#x2611;" : "&#x2610";
			if ($tablehead) {
				echo "Click on a name with a checked checkbox (&#x2611) to go there:";
				echo "<table id='search_table'>\n";
				echo "<tr><td></td><td></td><td></td><td></td></tr>"; // don't remove - forces formatting
				$tablehead = false;
			}
			if ($site != $found[3]) {
				$LocationSIndex = "S" . $found[3];
				$LocationIndex = $LocationLookup[$LocationSIndex];
				echo "<tr><td colspan='4' class='left'><b>" . $SiteNameList[$LocationSIndex] . ":</b></td></tr>\n";
				$site = $found[3];
			}
			$dy1 = str_replace("-", "/", substr($found[1], 5, 5));
			$nm0 = _Show_Chars($found[0], "text");
			$view = 11; // daily view
			if ($dy1 == "01/01") {
				$tm1 = "On $found[2] list";
				$view = ($found[2] == "callback") ? 12 : 13; // callback:deleted
				$data = "<td>$checkbox</td><td>$nm0</td><td colspan='2'>$tm1</td></tr>\n";
			}
			else {
				$tm1 = Format_Time($found[2], false);
				$data = "<td>$checkbox</td><td>$nm0</td><td>$dy1</td><td>$tm1</td></tr>\n";
			}
			if ($mysite) {
				echo "<tr onclick=\"New_Date('$found[1]', $view, $LocationIndex, $found[4]);\">\n";
				echo $data;
			}
			else {
				echo "<tr>\n";
				echo $data;
			}
		}
		echo "</table><hr />\n";
	}
	if ($tablehead) echo "No match found<hr />\n";
}

//===========================================================================================
function Format_Date($Date, $ShowWeekDay) {
//===========================================================================================
	global $MON;
	$DPART = explode("-", $Date);
	$YR = $DPART[0];
	$MO = $DPART[1];
	$DY = $DPART[2] + 0;
	$YMD = mktime(0, 0, 0, +$MO, +$DY, +$YR);
	$MON = date("F", $YMD);
	$DOW = date("l", $YMD);
	if ($ShowWeekDay) return ("$DOW, $MON $DY, $YR");
	return ("$MON $DY, $YR");
}

//===========================================================================================
function Format_Time($Time, $Realspace) {
//===========================================================================================
	if ($Time == "") $Time = "00:00";
	$Hour = substr($Time, 0, 2);
	$space = ($Realspace) ? " " : "&nbsp;";
	if ($Hour < 12) $HourSuffix = $space . "am"; else $HourSuffix = $space . "pm";
	if ($Hour > 12) {
		$Hour -= 12;
		if ($Hour == 0) $Hour = 12;
		if ($Hour < 10) $Hour = "0" . $Hour;
	}
	$Min  = substr($Time, 3, 2);
	return ($Hour . ":" . $Min . $HourSuffix);
}

//===========================================================================================
function Configure_Slots() {
//===========================================================================================
	global $Debug, $Errormessage;
	global $FormApptNo;
	global $FormApptSlotLoc;
	global $FormApptSlotDays;
	global $FormApptSlotDates;
	global $FormApptSlotSets;
	global $UserName;
	global $APPT_TABLE;
	global $MyTimeStamp;
	global $RESERVED;
	global $dbcon;
	global $FirstSlotDate, $Date, $FormApptDate, $NullDate;
	global $ApptView;
	global $ShowSlotBox;
	global $DeleteCode;


	$ShowSlotBox = true;
	if ($FormApptNo == "SlotRemoveAll") {
		$query = "DELETE FROM $APPT_TABLE";
		$query .= " WHERE `appt_location` = +$FormApptSlotLoc";
		mysqli_query($dbcon, $query);
		return;
	}

	if ($FormApptNo == "SlotRemoveDeleted") {
		$query = "DELETE FROM $APPT_TABLE";
		$query .= " WHERE `appt_location` = +$FormApptSlotLoc";
		$query .= " AND `appt_type` = 'D'";
		mysqli_query($dbcon, $query);
		return;
	}

	if ($FormApptNo == "SlotDeleteCallback") {
		$query = "UPDATE $APPT_TABLE SET";
		$query .= " `appt_type` = 'D'";
		$query .= " WHERE `appt_location` = +$FormApptSlotLoc";
		$query .= " AND `appt_date` = '$NullDate'";
		$query .= " AND `appt_name` <> ''";
		mysqli_query($dbcon, $query);
		return;
	}

	$DateRange = explode(",", $FormApptSlotDates);
	$StartDate = $ThisDate = trim($DateRange[0]);
	$StopDate = trim($DateRange[1]);

	if ($FormApptNo == "SlotRemoveDateRange") {
		$query = "DELETE FROM $APPT_TABLE";
		$query .= " WHERE `appt_location` = +$FormApptSlotLoc";
		$query .= " AND `appt_date` >= '$StartDate'";
		$query .= " AND `appt_date` <= '$StopDate'";
		mysqli_query($dbcon, $query);
		return;
	}

	$SlotSets = explode(",", $FormApptSlotSets);

	if ($FormApptNo == "SlotClone") {
		$query = "SELECT * FROM $APPT_TABLE";
		$query .= " WHERE `appt_location` = +$FormApptSlotLoc";
		$appointments = mysqli_query($dbcon, $query);
		while ($row = mysqli_fetch_array($appointments)) {
			$Appt = $row["appt_no"];
			$OldDate = $row["appt_date"];
			$YMD = explode("-", $OldDate);
			$CloneDate = date("Y-m-d", strtotime($OldDate . "+364 Days"));
			$ThisDOW = date($DeleteCode, strtotime($CloneDate));
			if (strpos($FormApptSlotDays, $ThisDOW)) {
				if (($CloneDate >= $StartDate) AND ($CloneDate <= $StopDate)) {
					$query = "UPDATE $APPT_TABLE SET";
					$query .= "  `appt_name` = ''";
					$query .= ", `appt_date` = '$CloneDate'";
					$query .= ", `appt_phone` = ''";
					$query .= ", `appt_email` = ''";
					$query .= ", `appt_tags` = ''";
					$query .= ", `appt_need` = ''";
					$query .= ", `appt_info` = ''";
					$query .= ", `appt_status` = ''";
					$query .= ", `appt_change` = '$MyTimeStamp'";
					$query .= " WHERE `appt_no` = $Appt";
					mysqli_query($dbcon, $query);
				}
			}
		}
		// Delete everything else prior to the start date
		$query = "DELETE FROM $APPT_TABLE";
		$query .= " WHERE `appt_location` = +$FormApptSlotLoc";
		$query .= " AND `appt_date` < '$StartDate'";
		mysqli_query($dbcon, $query);

		$appointments = [];
		return;
	}

	while ($ThisDate <= $StopDate) {
		$ThisDOW = date($DeleteCode, strtotime($ThisDate));
		if (strpos($FormApptSlotDays, $ThisDOW)) {
			switch ($FormApptNo) {
				case "SlotAdd1":
					$ShowSlotBox = false;
					// no break
				case "SlotAdd":
					for ($i = 1; $i < count($SlotSets); $i += 3) {
						$SlotCount = $SlotSets[$i];
						$SlotTime = $SlotSets[$i+1] . ":00";
						//if ($_SESSION["TRACE"]) error_log("|" . $SlotSets[$i] . "|" . $SlotSets[$i+1] . "|" . $SlotSets[$i+2] . "|"); // DEBUG
						$SlotUnreserved = max(0, +$SlotSets[$i] - +$SlotSets[$i+2]);
						for ($j = 0; $j < $SlotCount; $j++) {
							$res = ($j >= $SlotUnreserved) ? $RESERVED : "";
							InsertNewAppt($res, '', '', '', '', '', '', '', $ThisDate, $SlotTime, +$FormApptSlotLoc, $UserName);
						}
					}
					break;
				case "SlotClear":
					$query = "UPDATE $APPT_TABLE";
					$query .= " SET `appt_name` = ''";
					$query .= ", `appt_phone` = ''";
					$query .= ", `appt_email` = ''";
					$query .= ", `appt_tags` = ''";
					$query .= ", `appt_need` = ''";
					$query .= ", `appt_info` = ''";
					$query .= ", `appt_status` = ''";
					$query .= ", `appt_change` = ''";
					$query .= " WHERE `appt_location` = " . $FormApptSlotLoc;
					$query .= " AND `appt_date` = '$ThisDate'";
					mysqli_query($dbcon, $query);
					break;
				case "SlotRemove1":
					$ShowSlotBox = false;
					// no break
				case "SlotRemove":
					for ($i = 1; $i < count($SlotSets); $i += 3) {
						$SlotCount = $SlotSets[$i];
						$SlotTime = $SlotSets[$i+1] . ":00";
						$query = "DELETE FROM $APPT_TABLE";
						$query .= " WHERE `appt_location` = " . $FormApptSlotLoc;
						$query .= " AND `appt_date` = '$ThisDate'";
						$query .= " AND `appt_time` = '$SlotTime'";
						$query .= " AND `appt_name` = ''";
						$query .= " LIMIT $SlotCount";
						mysqli_query($dbcon, $query);
					}
					break;
			} // end switch
		} // end if
		$ThisDate = date("Y-m-d", strtotime($ThisDate . "+1 Day"));
	} // end while
	$FormApptSlotSets = "";

	// If only one add/remove on one day, go back to that day
	if ((($FormApptNo == "SlotAdd1") OR ($FormApptNo == "SlotRemove1"))
	   AND (count($SlotSets) == 4)) {
		$ApptView = "ViewDaily";
		$FormApptNo = "NewDate";
		$FirstSlotDate = $Date = $FormApptDate = $StartDate;
	}
	return;
}

//===========================================================================================
function InsertNewAppt($iName, $iPhone, $iEmail, $iTags, $iNeed, $iInfo, $iStatus, $iWait, $iDate, $iTime, $iLoc, $iBy) {
//===========================================================================================
	global $dbcon, $APPT_TABLE;
	global $MyTimeStamp;
	global $UserFullName;
	global $Debug;
	if ($iWait == "") $iWait = "0"; // Added for PHP 8.0
	$query = "INSERT INTO `$APPT_TABLE` SET";
	$query .= " `appt_name` = '$iName'";
	$query .= ", `appt_phone` = '$iPhone'";
	$query .= ", `appt_email` = '$iEmail'";
	$query .= ", `appt_tags` = '$iTags'";
	$query .= ", `appt_need` = '$iNeed'";
	$query .= ", `appt_info` = '$iInfo'";
	$query .= ", `appt_status` = '$iStatus'";
	$query .= ", `appt_change` = '$MyTimeStamp'";
	$query .= ", `appt_wait` = '$iWait'"; // Changed for PHP 8.0
	$query .= ", `appt_date` = '$iDate'";
	$query .= ", `appt_time` = '$iTime'";
	$query .= ", `appt_location` = " . +$iLoc;
	$query .= ", `appt_by` = '$iBy'";
	//if ($_SESSION["TRACE"]) error_log($query); // DEBUG
	mysqli_query($dbcon, $query);
}

?>
<!--=================================================== WEB PAGE HEADER ============================================-->
<!--=================================================== WEB PAGE HEADER ============================================-->
<!--=================================================== WEB PAGE HEADER ============================================-->

<head>
<title>AARP Appointments</title>
<meta name="appointments" content="AARP Appointments">
<link rel="SHORTCUT ICON" href="appt.ico">
<link rel="stylesheet" href="appt.css">
<style>
</style>

<script src="functions.js"></script>
<script>
	var Current_Date;
	var ApptCount;
	var ApptNoVal;
	var ApptTimeVal;
	var ApptNameVal;
	var ApptPhoneVal;
	var ApptEmailVal;
	var ApptLocList;
	var ApptTagsVal;
	var ApptNeedVal;
	var ApptInfoVal;
	var ApptStatusVal;
	var CtrlKeyFlag;
	var EROPrint = "";
	var MoveMode = 0;
	var MoveData = "";
	var PatternSaved = "";
	var ReserveMode = false;
	var SiteListHeight = 0;
	var SlotLimit = 25;
	var listEBParent = "";
	var listEBChildren = [];
	var EBselected = null;
	var ExportList = "";
	var ApptBoxOld = "";
	var ApptBoxNew = "";
<?php
	global $RESERVED;
	global $ApptView;
	global $UserName;
	global $UserOptions;
	global $TodayDate;
	global $ViewDate;
	echo "\tvar RESERVED = \"$RESERVED\";\n";
	echo "\tvar ApptView = \"$ApptView\";\n";
	echo "\tvar UserName = \"$UserName\";\n";
	echo "\tvar UserFullName = \"$UserFullName\";\n";
	echo "\tvar UserOptions = \"$UserOptions\";\n";
	echo "\tvar TodayDate = \"$TodayDate\";\n";
	echo "\tvar ViewDate = \"$FirstSlotDate\";\n";
	echo "\tvar SummaryAll = " . (@$_SESSION["SummaryAll"] ? "true" : "false") . "\n";
	echo "\tvar NullDate = \"$NullDate\"\n";
	global $ApptMove;
	global $FormApptOldNo;
	global $FormApptName;
	global $FormApptPhone;
	global $FormApptEmail;
	global $FormApptTags;
	global $FormApptNeed;
	global $FormApptInfo;
	global $FormApptStatus;
	global $UserName;
	global $UserOptions;
	global $Errormessage;
	if ($ApptMove) echo "\tMoveData = '$FormApptOldNo|$FormApptName|$FormApptPhone|$FormApptEmail|$FormApptTags|$FormApptNeed|$FormApptInfo|$FormApptStatus';\n";
	echo "\tvar Errormessage = \"$Errormessage\";\n";
?>
	var OldData = MoveData.split("|");
	if (OldData[0] != "") {
		MoveMode = OldData[0];
	}	
	var Current_Record = "";
	var Comment_ID = "(<?php global $UserName; echo $UserName; ?>)";
	var OpCode;
	var CtrlKey = false;
	var LastPattern = "";

	//===========================================================================================
	function Initialize() {
	//===========================================================================================
		if (MoveMode) {
			ApptForm.IDApptDate.value = Current_Date;
			ApptForm.IDApptOldSlot.value = MoveMode;
			ApptForm.IDApptName.value = OldData[1];
			ApptForm.IDApptPhone.value = OldData[2];
			ApptForm.IDApptEmail.value = OldData[3];
			ApptForm.IDApptTags.value = OldData[4];
			ApptForm.IDApptNeed.value = OldData[5];
			ApptForm.IDApptInfo.value = OldData[6];
			ApptForm.IDApptStatus.value = OldData[7];
			ApptBox.style.visibility = "hidden";
			MoveBox.style.visibility = "visible";
			MoveName.innerHTML = CopyName.innerHTML = _Show_Chars(OldData[1], "html");
			MoveBoxMessage.style.display = (MoveMode > 0) ? "block" : "none";
			CopyBoxMessage.style.display = (MoveMode < 0) ? "block" : "none";
			ViewDaily.style.backgroundColor = "hotpink";
			ViewDeleted.style.backgroundColor = "hotpink";
			if (ApptView == "ViewCallback") ViewCallback.style.backgroundColor = "lightgreen";
		}
		else {
			if (ApptView == "ViewSummary") ViewSummary.style.backgroundColor = "lightgreen";
			if (ApptView == "ViewDaily") ViewDaily.style.backgroundColor = "lightgreen";
			if (ApptView == "ViewCallback") ViewCallback.style.backgroundColor = "lightgreen";
			if (ApptView == "ViewDeleted") ViewDeleted.style.backgroundColor = "lightgreen";
		}
		ApptHistoryBox.style.display = (ApptView == "ViewUser") ? "none" : "inline";
		ApptForm.IDApptLoc.value = ApptLocList;
		ApptView = ApptForm.IDApptView.value;
<?php
		global $Errormessage;
		if ($Errormessage != "") echo "alert(\"$Errormessage\");\n";
		global $FormApptNo;
		if (substr($FormApptNo, 0, 6) == "FindBy") echo "SearchBox.style.visibility = 'visible';\n";
?>
		FindByVal.value = _Show_Chars(FindByVal.value, "text");

		// Move the current date in the calendar into focus
		if (ApptView !== "ViewUser") {
			if ((ViewDate == "") || (ViewDate == NullDate)) ViewDate = TodayDate;
			focusId = "ID" + ViewDate.substr(0,7);
			calptr = document.getElementById(focusId);
			if (calptr !== null) calptr.scrollIntoView();
		}

		// Get cookies
		cookies = _Read_Cookies();

		// Initialize export lists
		listEBParent = document.getElementById("EBlist");
		listEBChildren = EBlist.children;
		if (cookies["ExportList"]) {
			eList = cookies["ExportList"].split("|");
			for (e = eList.length - 1 ; e >= 0 ; e-- ) { // read the list in backwards
				ez = (eList[e].substr(0,1) == "*"); // zero indicator
				et = eList[e].substr(((ez) ? 1 : 0)); // title to match
				for (c = 0 ; c < listEBChildren.length ; c++ ) {
					ct = listEBChildren[c].children[0].childNodes[1].nodeValue.trim();
					if (ct == et) { // move to front
						listEBChildren[c].children[0].children[0].checked = true;
						listEBChildren[c].children[1].children[0].checked = ez;
						listEBParent.insertBefore(listEBChildren[c], listEBParent.childNodes[0]);
					}
				}
			}
		}
		Make_EB_List();
		Hide_ExportBox();

		// if a move, scroll to highlighted line
		mvptr = document.getElementsByClassName("apptSlotMoved");
		if (typeof mvptr[0] !== "undefined") mvptr[0].scrollIntoView({ behavior: 'instant', block: 'center' });

		// Detect control key held down for checking multiple site boxes
		window.onkeydown = function(e) { CtrlKey = e.ctrlKey; }
		window.onkeyup = function(e) { CtrlKey = e.ctrlKey; if (CtrlKeyFlag) ApptOp("Save");}
	}
 
	//===========================================================================================
	function Change_SummaryAll() {
	//===========================================================================================
		SummaryAll = sumOpt.checked;
		Change_View("ViewSummary");
	}
		
	//===========================================================================================
	function Change_View(viewRequest) {
	//===========================================================================================
		ApptView = ApptForm.IDApptView.value = viewRequest;
		ApptForm.IDApptReason.value = "";
		ApptForm.IDApptSlot.value = "";
		switch (viewRequest) {
			case "ViewCallback":
			case "ViewCallback2":
				ApptForm.IDApptReason.value = "ViewCallback";
				ApptForm.IDApptDate.value = NullDate;
				ApptForm.IDApptSlot.value = (MoveMode) ? "MoveLoc" : "NewLoc";
				New_Date(NullDate, 2);
				break;
			case "ViewDeleted":
				if (MoveMode) return;
				ApptForm.IDApptReason.value = "ViewDeleted";
				ApptForm.IDApptDate.value = NullDate;
				New_Date(NullDate, 3);
				break;
			case "ViewUser":
			case "ViewDaily":
				if (MoveMode) return;
				break;
			case "ViewSummary":
			case "ViewSummary2":
				ApptForm.IDApptReason.value = SummaryAll ? "ViewSummaryAll" : "ViewSummary";
				ApptForm.IDApptSlot.value = (MoveMode) ? "MoveLoc" : "NewLoc";
				ApptForm.IDApptOldSlot.value = MoveMode;
				break;
			default:
				return;
		}
		ApptOp("Save");
	}

	//===========================================================================================
	function New_Date(ND, NV, Loc, DBIndex) {
	// ND = new date in YYYY-MM-DD format,
	// NV = 1 to change to daily view
	// NV = 2 to change to callback view
	// NV = 3 to change to deleted view
	// NV = 11,12,13 to set the checkbox for the site Loc, then change to the appropriate view
	// Loc = Location to view
	// DBIndex = DB index for the appointment
	//===========================================================================================
		if (ApptView == "ViewUser") return;
		if (NV > 10) {
			for (j = 1; j <= ApptLocList[0]; j++) {
				jv = document.getElementById("Loc" + j);
				if (j == Loc) jv.checked = true;
				ApptLocList[j] = ((jv) && (jv.checked)) ? 1 : 0;
			}
			ApptForm.IDApptLoc.value = ApptLocList;
			NV -= 10;
		}
		switch (NV) {
			case 0:
				ApptView = ApptForm.IDApptView.value = "ViewUser";
				break;
			case 1:
				ApptView = ApptForm.IDApptView.value = "ViewDaily";
				break;
			case 2:
				ApptView = ApptForm.IDApptView.value = "ViewCallback";
				break;
			case 3:
				ApptView = ApptForm.IDApptView.value = "ViewDeleted";
				break;
		}

		ApptForm.IDApptDate.value = ND;
		if (MoveMode) {
			ApptForm.IDApptSlot.value = (MoveMode > 0) ? "Move" : "Copy" ;
			ApptForm.IDApptOldSlot.value = MoveMode;
		}
		else {
			ApptForm.IDApptSlot.value = "NewDate";
			if (+DBIndex > 0) ApptForm.IDApptOldSlot.value = DBIndex;
		}
		WaitBox.style.display = "block";
		document.getElementById("ApptForm").submit();
	}

	//===========================================================================================
	function Change_Text(which) {
	// converts problem characters into html codes
	//===========================================================================================
		switch (which) {
			case "TagsText":
				ApptForm.IDApptTags.value = _Clean_Chars(ApptForm.TagsText.value);
				break;
			case "NeedText":
				ApptForm.IDApptNeed.value = _Clean_Chars(ApptForm.NeedText.value);
				break;
			case "InfoText":
				ApptForm.IDApptInfo.value = _Clean_Chars(ApptForm.InfoText.value);
				break;
		}
		Test_Email(); // Display or hide Send Email button
	}

	//===========================================================================================
	function Change_Appointment(VIS, DBIDX, SLOT, IDX) {
	// VIS = 0/1 to indicate box visibility
	// 	-1 to toggle reserved status of the slot
	// DBIDX = database index for this time slot
	// SLOT = sequence number for the entire day
	// IDX = sequence number for that time and location (as in column 1)
	//===========================================================================================
		Current_Record = SLOT;
		Current_Name = ApptNameVal[Current_Record];

		if (ReserveMode) return;

		if (MoveMode) {
			if ((Current_Name != "") && (Current_Name != RESERVED)) {
				alert("You must choose an empty or reserved appointment"); return;
			}
			ApptForm.IDApptDate.value = Current_Date;
			ApptForm.IDApptSlot.value = DBIDX;
			ApptForm.IDApptOldSlot.value = MoveMode;
			ApptForm.IDApptReason.value = (MoveMode > 0) ? "Move" : "Copy" ;
			ApptOp("Save");
			VIS = 0;
			MoveMode = 0;
		}

		else if (VIS < 0) { // Reserved status change request
			// ignore the request if the appointment box is open
			if (ApptBox.style.visibility == "visible") return;

			// toggle the reserved status
			ApptForm.IDApptName.value = (Current_Name == RESERVED) ? "" : RESERVED;
			ApptForm.IDApptDate.value = Current_Date;
			ApptForm.IDApptSlot.value = DBIDX;
			ApptForm.IDApptReason.value = "Add";
			ApptOp("Save");
			VIS = 0;
			ReserveMode = true;
		}

		else { // Appointment detail change request
			if ((Current_Name == "") || (Current_Name == RESERVED)) {
				ApptForm.IDApptReason.value = "Add";
				IDApptMove.style.display = "none";
				IDApptCopy.style.display = "none";
				IDApptDelete.style.display = "none";
				IDApptSendEmail.style.display = "none";
			}
			else {
				IDApptMove.style.display = "inline";
				IDApptCopy.style.display = "inline";
				IDApptDelete.style.display = "inline";
				Test_Email(); // Display or hide Send Email button
			}

			switch (ApptView) {
				case "ViewCallback":
					htitle = "Entry " + IDX;
					htitle += " on the Callback List";
					hloc = document.getElementById("LocName" + ApptSiteVal[Current_Record]);
					htitle += " for the " + hloc.innerHTML;
					break;
				case "ViewDeleted":
					htitle = "Entry " + IDX;
					htitle += " on the Deleted List";
					hloc = document.getElementById("LocName" + ApptSiteVal[Current_Record]);
					htitle += " for the " + hloc.innerHTML;
					IDApptDelete.style.display = "none";
					break;
				default:
					htitle = "Appointment " + IDX;
					htitle += " on " + Display_Date + " at " + ApptTimeVal[Current_Record];
					hloc = document.getElementById("LocName" + ApptSiteVal[Current_Record]);
					htitle += " for the " + hloc.innerHTML;
			}
			ApptTitle.innerHTML = htitle;
			MoveName.innerHTML = CopyName.innerHTML = _Show_Chars(Current_Name, "html");
			ApptForm.IDApptDate.value = Current_Date;
			ApptForm.IDApptSlot.value = ApptNoVal[Current_Record];
			ApptForm.IDApptOldSlot.value = "";
			if (Current_Name == RESERVED) ApptForm.IDApptName.value = "";
			else ApptForm.IDApptName.value = _Show_Chars(Current_Name, "text");
			ApptForm.IDApptPhone.value = ApptPhoneVal[Current_Record];
			ApptForm.IDApptEmail.value = _Show_Chars(ApptEmailVal[Current_Record], "text");
			ApptForm.IDApptTags.value = ApptTagsVal[Current_Record];
			ApptForm.TagsText.value = _Show_Chars(ApptForm.IDApptTags.value, "text");
			ApptForm.IDApptNeed.value = ApptNeedVal[Current_Record];
			ApptForm.NeedText.value = _Show_Chars(ApptForm.IDApptNeed.value, "text");
			ApptForm.IDApptInfo.value = ApptInfoVal[Current_Record];
			ApptForm.InfoText.value = _Show_Chars(ApptForm.IDApptInfo.value, "text");
			ApptForm.IDApptStatus.value = ApptStatusVal[Current_Record];
			StatusText.value = _Show_Chars(ApptStatusVal[Current_Record], "text");
		}

		if (VIS) {
			ApptBoxOld = Set_ApptBox_Content();
			ApptBox.style.visibility = "visible";
			IDApptSendEmail.style.display = (IDApptEmail.value) ? "inline" : "none";
			ApptForm.IDApptName.focus();
		}
		else {
			ApptBox.style.visibility = "none";
		}
	}

	//===========================================================================================
	function Set_ApptBox_Content() {
	//	Collects data from the ApptBox fields for comparison between ApptBoxOld and ApptBoxNew
	//===========================================================================================
		ApptBox_Content = "";
		ApptBox_Content += _Clean_Chars(IDApptName.value);
		ApptBox_Content += _Clean_Chars(IDApptPhone.value);
		ApptBox_Content += _Clean_Chars(IDApptEmail.value);
		ApptBox_Content += _Clean_Chars(IDApptTags.value);
		ApptBox_Content += _Clean_Chars(IDApptNeed.value);
		ApptBox_Content += _Clean_Chars(IDApptInfo.value);
		ApptBox_Content += _Clean_Chars(StatusText.value);
		return ApptBox_Content;
	}

	//===========================================================================================
	function Test_Phone(phone) {
	//	phone = true/false
	//		if false, there is no phone number so enter 0s
	//===========================================================================================
		if (! phone) {
			nullPhone = "000-000-0000";
			if (IDApptPhone.value > nullPhone) {
				message = "Do you really want to replace " + IDApptPhone.value;
				message += " with " + nullPhone + "?";
				if (confirm(message)) ApptForm.IDApptPhone.value = nullPhone;
			}
			else ApptForm.IDApptPhone.value = nullPhone;
		}
		result = _Verify_Phone(ApptForm.IDApptPhone.value, "alert", Appt10digVal[Current_Record]);
		ApptForm.IDApptPhone.value = result[1];
		Test_Email(); // Display or hide Send Email button
	}

	//===========================================================================================
	function Test_Email() {
	//===========================================================================================
		result = _Verify_Email(ApptForm.IDApptEmail.value, true);
		ApptForm.IDApptEmail.value = result[1] ;
		ApptBoxNew = Set_ApptBox_Content();
		IDApptSendEmail.style.display = ((result[0]) || (ApptBoxOld != ApptBoxNew)) ? "none" : "inline" ;
	}

	//===========================================================================================
	function Add_Appointment(APPT, APPTDATE, APPTTIME, SHOWDATE, SHOWTIME) {
	// APPT = database appointment number (ApptNo)
	// APPTDATE = date to be added (e.g. 2015-02-15)
	// APPTTIME = time to be added (e.g. 22:15:00)
	// SHOWDATE = APPTDATE in human readable form (e.g. Monday, February 15)
	// SHOWTIME = APPTTIME in human readable form (e.g. 10:15 pm)
	//===========================================================================================
		if (MoveMode) {
			ApptForm.IDApptDate.value = APPTDATE;
			ApptForm.IDApptSlot.value = APPT;
			ApptForm.IDApptOldSlot.value = MoveMode;
			ApptForm.IDApptReason.value = (MoveMode > 0) ? "Move" : "Copy" ;
			ApptView = ApptForm.IDApptView.value = "ViewDaily"; // change to daily view to see effect of add
			ApptOp("Save");
			VIS = 0;
			MoveMode = 0;
		}
		else {
			ApptForm.IDApptSlot.value = APPT;
			ApptForm.IDApptDate.value = APPTDATE;
			ApptForm.IDApptTime.value = APPTTIME;
			ApptForm.IDApptReason.value = "Add";

			if (SHOWDATE == NullDate) {
				ApptTitle.innerText = "Adding to the callback list";
			}
			else {
				ApptTitle.innerText = "Adding appointment for " + SHOWTIME + " on " + SHOWDATE;
			}

			if (ApptView == "ViewUser") {
				ApptHistoryBox.style.display = "none";
				IDApptName.value = _Show_Chars(UserFullName, "text");
				}
			else {
				ApptHistoryBox.style.display = "inline";
			}

			IDApptSendEmail.style.display = "none";
			IDApptMove.style.display = "none";
			IDApptCopy.style.display = "none";
			IDApptDelete.style.display = "none";
			ApptBox.style.visibility = "visible";
		}
	}

	//===========================================================================================
	function Change_Loc(LocID, Loc) {
	//===========================================================================================
		if (MoveMode) {
			ApptForm.IDApptSlot.value = "MoveLoc";
			ApptForm.IDApptOldSlot.value = MoveMode;
			MoveBox.style.visibility = "visible";
		}
		else {
			ApptForm.IDApptSlot.value = "NewLoc";
		}

		ApptForm.IDApptCustSite.value = Loc;
		for (j = 1; j <= ApptLocList[0]; j++) {
			jv = document.getElementById("Loc" + j);
			ApptLocList[j] = ((jv) && (jv.checked)) ? 1 : 0;
		}
		ApptForm.IDApptLoc.value = ApptLocList;

		//Check control key before saving
		
		CtrlKeyFlag = CtrlKey;
		if (! CtrlKey) ApptOp("Save");
	}

	//===========================================================================================
	function AddCBSlots() {
	//	Adds dummy records to the callback list
	//===========================================================================================
		var jmax = +SlotsToAdd.value;
		if (jmax == 0) return;
		if (jmax > 50) {
			alert("You can only add 50 at a time. \n\nClick again if you want to add 50.");
			SlotsToAdd.value = 50;
			return;
		}
		ApptForm.IDApptSlot.value = "AddCBSlots";
		ApptForm.IDApptSlotLoc.value = LocationToAdd.value;
		ApptForm.IDApptSlotSets.value = jmax;
		WaitBox.style.display = "block";
		document.getElementById("ApptForm").submit();
	}

	//===========================================================================================
	function Test_For_Enter(id, e) {
	// id = the id of the element being checked
	// e = the event being checked for a key code
	//===========================================================================================
		if ((e.keyCode || e.charCode) == 13) { // Enter key code (charCode for old browers)
			if (id == "FindByVal") Find_Appointment();
			if (id == "StatusOther") Add_Comment(id);
		}
	}

	//===========================================================================================
	function Find_Appointment() {
	//===========================================================================================
		if (FindByVal.value == "") {
			alert("Sorry, I can't search for nothing.");
			return;
			}
		if (FindByPhone.checked) {
			ApptForm.IDApptPhone.value = FindByVal.value;
			ApptForm.IDApptSlot.value = "FindByPhone";
		}
		else if (FindByTags.checked) {
			ApptForm.IDApptTags.value = _Clean_Chars(FindByVal.value);
			ApptForm.IDApptSlot.value = "FindByTags";
		}
		else if (FindByName.checked) {
			ApptForm.IDApptName.value = _Clean_Chars(FindByVal.value);
			ApptForm.IDApptSlot.value = "FindByName";
		}
		else if (FindByEmail.checked) {
			ApptForm.IDApptEmail.value = FindByVal.value;
			ApptForm.IDApptSlot.value = "FindByEmail";
		}
		ApptForm.IDApptDate.value = ViewDate;
		ApptOp("Find");
	}

	//===========================================================================================
	function Cust_Delete(ApptToDelete, ApptDescription) {
	//===========================================================================================
		var confirmAnswer = confirm("Are you sure you want to remove the following appointment ?\n\n" + ApptDescription + "\n\n(OK will remove the appointment, Cancel will retain the appointment)");
		if (!confirmAnswer) return;
		ApptForm.IDApptOldSlot.value = "";
		ApptForm.IDApptName.value = _Clean_Chars(ApptForm.IDApptName.value);
		ApptForm.IDApptSlot.value = ApptToDelete;
		ApptForm.IDApptReason.value = "Delete";
		WaitBox.style.display = "block";
		document.getElementById("ApptForm").submit();
	}

	//===========================================================================================
	function Log_Out() {
	//===========================================================================================
		ApptForm.IDApptSlot.value = "LogOut";
		ApptOp("LogOut");
	}

	//===========================================================================================
	function ApptOp(OpCode) {
	//===========================================================================================
		switch (OpCode) {
			case "Delete":
				var confirmQ = "Are you sure you want to remove " + ApptForm.IDApptName.value + "'s appointment?";
				if (ApptView == "ViewCallback") {
					confirmQ = "Are you sure you want to remove " + ApptForm.IDApptName.value + "'s callback entry?";
				}
				confirmanswer = confirm(confirmQ);
				if (!confirmanswer) return;
				ApptForm.IDApptName.value = _Clean_Chars(ApptForm.IDApptName.value, "text");
				ApptForm.IDApptReason.value = "Delete";
				ApptForm.IDApptOldSlot.value = "";
				ApptForm.IDApptReason.value = "Delete";
				WaitBox.style.display = "block";
				document.getElementById("ApptForm").submit();
				break;
			case "Copy1": // The first step in copying an appointment
				ApptForm.IDApptName.value = _Clean_Chars(ApptForm.IDApptName.value, "text");
				MoveMode = -ApptForm.IDApptSlot.value; // minus implies copy
				MoveBox.style.visibility = "visible";
				MoveBoxMessage.style.display = "none";
				CopyBoxMessage.style.display = "block";
				ApptBox.style.visibility = "hidden";
				ViewDaily.style.backgroundColor = "hotpink";
				ViewDeleted.style.backgroundColor = "hotpink";
				return;
			case "Move1": // The first step in moving an appointment
				ApptForm.IDApptName.value = _Clean_Chars(ApptForm.IDApptName.value, "text");
				MoveMode = ApptForm.IDApptSlot.value;
				MoveBox.style.visibility = "visible";
				MoveBoxMessage.style.display = "block";
				CopyBoxMessage.style.display = "none";
				ApptBox.style.visibility = "hidden";
				ViewDaily.style.backgroundColor = "hotpink";
				ViewDeleted.style.backgroundColor = "hotpink";
				return;
			case "Cancel":
				if (MoveMode) {
					ViewSummary.style.backgroundColor = "";
					ViewDaily.style.backgroundColor = (ApptView == "ViewDaily") ? "lightgreen" : "";
					ViewDeleted.style.backgroundColor = (ApptView == "ViewDeleted") ? "lightgreen" : "";
					ApptForm.IDApptOldSlot.value = "";
					MoveMode = 0;
				}
				if (ApptView == "ViewUser") {
					message = "Your appointment is not confirmed.";
					message += "\n\nIf you really mean to cancel, click the \"Cancel\" button below to do that.";
					message += "\n\nOr, click \"OK\" to return to the appointment box so you can save the appointment.";
					if (confirm(message)) return;
				}
				if (ApptBox.style.visibility == "visible") {
					ApptBoxNew = Set_ApptBox_Content();
					if (ApptBoxOld == ApptBoxNew) break;
					message = "Your changes have not been saved.";
					message += "\n\nIf you really mean to cancel, click the \"Cancel\" button below to do that.";
					message += "\n\nOr, click \"OK\" to return to the appointment box so you can save the appointment.";
					if (confirm(message)) return;
				}
				break;
			case "Find":
				if (MoveMode) return;
				// no break
			case "LogOut":
			case "Save":
				ApptForm.IDApptPhone.disabled = "";
				ApptForm.IDApptEmail.disabled = "";
				if (StatusOther.value != "") Add_Comment("StatusOther");
				if (ApptBox.style.visibility == "visible") {

					if (ApptForm.IDApptName.value == "") {
						alert("Please enter your name.");
						return;
					}

					if (ApptForm.IDApptPhone.value == "") {
						alert("Please enter your phone number.");
						return;
					}

					ApptForm.IDApptName.value = _Clean_Chars(ApptForm.IDApptName.value);
				}
				WaitBox.style.display = "block";
				// no break
			case "PrintExcel":
			case "Submit":
				document.getElementById("ApptForm").submit();
				break;
			case "SendEmail":
				if (ApptBox.style.visibility == "visible") {
					ApptBoxNew = Set_ApptBox_Content();
					if (ApptBoxOld != ApptBoxNew) {
						message = "Your changes have not been saved.";
						message += "\n\nPlease save changes before sending an email.";
						alert(message);
						return;
					}
				}
				OpenEmail.href = "mailto: " + IDApptName.value + "<" + IDApptEmail.value + ">";
				OpenEmail.click();
				break;
			default:
				alert ("Bad OpCode = " + OpCode + " detected!");
		}
		ApptBox.style.visibility = "hidden";
		MoveBox.style.visibility = "hidden";
		IDApptMove.style.display = "inline";
		IDApptCopy.style.display = "inline";
		if (ApptView != "ViewDeleted") IDApptDelete.style.display = "inline";
	}

	//===========================================================================================
	function Add_Comment(action) {
	//===========================================================================================
		if (document.getElementById("IDApptName").value == "") return;
		switch (action) {
			case "StatusConfirmed":
				message = "Confirmed";
				break;
			case "StatusMessageM":
				message = "Left msg on machine";
				break;
			case "StatusMessageW":
				person = prompt("With whom did you leave the message?", "");
				if ((person < "A") || (person == null)) return;
				message = "Left msg with " + person;
				break;
			case "StatusBusy":
				message = "Busy";
				break;
			case "StatusNoAnswer":
				message = "No answer";
				break;
			case "StatusNNTF":
				message = "No need to file";
				break;
			case "StatusQandA":
				message = "Answered questions";
				break;
			case "StatusEmail":
				message = "Responded by email";
				break;
			case "StatusDocs":
				message = "Sent documents";
				break;
			case "StatusOther":
				if (StatusOther.value < "!") return;
				message = _Clean_Chars(StatusOther.value);
				StatusOther.value = ""; // clear the entry
				break;
			default: return;
		}

		// get the time and date for the time stamp
		var d = new Date();
		var h = d.getHours();
		var a = "am";
		if (h > 11) a = "pm";
		if (h == 0) h = 24;
		if (h > 12) h -= 12;
		h = ("0" + h).substr(-2);
		var m = ("0" + d.getMinutes()).substr(-2);
		var t = "_" + h + ":" + m + a + ": ";
		t = TodayDate.substr(5).replace(/-/, "/") + t;

		ApptForm.IDApptStatus.value = t + message + " (" + UserName + ")%0A" + ApptForm.IDApptStatus.value;
		StatusText.value = _Show_Chars(ApptForm.IDApptStatus.value, "text");
	}

	//===========================================================================================
	function Print_Appointments() {
	//===========================================================================================
		if (MoveMode) return;
		count = 0;
		for (j = 1; j <= ApptLocList[0]; j++) {
			locid = document.getElementById("Loc" + j);
			if ((ApptLocList[j]) && (! locid.disabled)) {
				count++;
				locname = document.getElementById("LocName" + j).innerHTML;
			}

		}
		if (count == 0) return;
		if (count > 1) {alert("Please print only one site at a time."); return;}

		ApptPrint = window.open("", "", "menubar=1, scrollbars=1, resizable=1");
		ApptPrint.document.writeln("<!DOCTYPE html>");
		ApptPrint.document.writeln("<head>");
		ApptPrint.document.writeln("<style>");
		ApptPrint.document.writeln(".CheckinExtra { background-color:#EEEEEE;}");
		ApptPrint.document.writeln("@media print {\n\t.noPrint { display:none;}\n}");
		ApptPrint.document.writeln("</style>");
		ApptPrint.document.writeln("</head>");
		ApptPrint.document.writeln("<body>");
		ApptPrint.document.writeln("<div class='noPrint'><button onclick='ApptPrint.document.print()'>to print, use CTRL+P</button></div>");
		ApptPrint.document.writeln("<center>");
		ApptPrint.document.writeln("<span style='text-align:center; font-weight:bold; font-size:large'>" + locname + "</br />" + Display_Date + "</span>");
		var OldTime = 0;
		var FootNote = 0;
		var FootNoteStart = 0;
		var FootText = "";
		var FootNeedText = "";
		var Paginate = 1;
		var k = 0;
		for (var j = 1; j <= ApptCount; j++) {
			var NewTime = ApptTimeVal[j];

			if (NewTime != OldTime) {

				if (OldTime != 0) {
					ApptPrint.document.writeln("<tr class='CheckinExtra'><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>");
					ApptPrint.document.writeln("<tr class='CheckinExtra'><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>");
					ApptPrint.document.writeln("</table>");
					ApptPrint.document.writeln("</center>");
					if (FootText.length > 0) {
						ApptPrint.document.writeln("	<ol start='" + FootNoteStart + "'>");
						ApptPrint.document.writeln(FootText);
						ApptPrint.document.writeln("	</ol>");
					}
					FootText = "";
					FootNoteStart = 0;
					ApptPrint.document.writeln("</div>\n");

					ApptPrint.document.writeln("<div style='page-break-inside: avoid;'>");
					ApptPrint.document.writeln("<center>");
					k = 0;
				}
				ApptPrint.document.writeln("<br />");
				ApptPrint.document.writeln("<table border style='width:100%; border-collapse:collapse'><tr>");
				ApptPrint.document.writeln("	<td style='width:1%; text-align:center'>Arrival<br />No</td>");
				ApptPrint.document.writeln("	<td style='width:1%; text-align:center'>Appt<br />No</td>");
				ApptPrint.document.writeln("	<td style='width:64%; text-align:center'>Name</td>");
				ApptPrint.document.writeln("	<td style='min-width:7em; text-align:center'>Phone</td>");
				ApptPrint.document.writeln("	<td style='width:8%; text-align:center'>Screener</td>");
				ApptPrint.document.writeln("	<td style='width:8%; text-align:center'>Counselor</td>");
				ApptPrint.document.writeln("	<td style='width:8%; text-align:center'>Quality Reviewer</td>");
				ApptPrint.document.writeln("	<td style='width:10%; text-align:center'>Notes</td></tr>");

				ApptPrint.document.writeln("	<tr></td><td colspan='8' style='font-weight:bold; background-color:lightgrey'>" + NewTime + " Appointments:</td></tr>");
				OldTime = NewTime;
			}

			if ((NewTime == "Callback List") & (ApptNameVal[j] == "")) {
				// Skip this last blank entry
			}
			else {
				k++;
				ApptPrint.document.writeln("	<tr><td> </td>");
				ApptPrint.document.writeln("	<td style='text-align:right'>&nbsp;" + k + "&nbsp;&nbsp;</td>");
				nametoprint = (ApptNameVal[j] == RESERVED) ? "" : _Show_Chars(ApptNameVal[j], "text");
				ApptPrint.document.writeln("	<td>" + nametoprint + "</td>");
				ApptPrint.document.writeln("	<td>" + ApptPhoneVal[j].replace(/-/g, "&#x2011;") + "</td>"); //non-breaking dash
				ApptPrint.document.writeln("	<td> </td>");
				ApptPrint.document.writeln("	<td> </td>");
				ApptPrint.document.writeln("	<td> </td>");
				FootNeedText = ApptNeedVal[j];
				if (FootNeedText.length) {
					FootNote++;
					if (FootNoteStart == 0) FootNoteStart = FootNote;
					ApptPrint.document.writeln("\t<td>" + FootNote + "</td></tr>");
					FootText = FootText + "<li>" + _Show_Chars(FootNeedText, "html") + "</li>";
				}
				else ApptPrint.document.writeln("\t<td></td></tr>");
			}
		}	
		ApptPrint.document.writeln("<tr class='CheckinExtra'><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>");
		ApptPrint.document.writeln("<tr class='CheckinExtra'><td>&nbsp;</td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>");
		ApptPrint.document.writeln("	</table></div>");
		ApptPrint.document.writeln("</center>");
		if (FootText.length > 0) {
			ApptPrint.document.writeln("	<ol start='" + FootNoteStart + "'>");
			ApptPrint.document.writeln(FootText);
			ApptPrint.document.writeln("	</ol>");
			}
		ApptPrint.document.writeln("</body>");
		ApptPrint.print();
	}

	//===========================================================================================
	function Print_ERO_Checklist() {
	//===========================================================================================
		if (MoveMode) return;
		count = 0;
		for (j = 1; j <= ApptLocList[0]; j++) {
			locid = document.getElementById("Loc" + j);
			count += ((ApptLocList[j]) && (! locid.disabled)) ? 1 : 0;
		}
		if (count == 0) return;
		if (count > 1) {
			alert("Please print only one site at a time.");
			return;
		}

		// Create ERO document and write header
		EROPrint = window.open("", "", "menubar=1, scrollbars=1, resizable=1");
		EROPrint.document.writeln("<!DOCTYPE html>");
		EROPrint.document.writeln("<head>");
		EROPrint.document.writeln("<style>");
		EROPrint.document.writeln("body {width: 11in;}");
		EROPrint.document.writeln(".center {text-align: center}");
		EROPrint.document.writeln(".rotate {transform: rotate(90deg); -webkit-transform: rotate(90deg); white-space: nowrap;}");
		EROPrint.document.writeln(".pageout {width: 100%;}");
		EROPrint.document.writeln(".pagebreak {page-break-before: always; width: 100%;}");
		EROPrint.document.writeln(".red {color: red;}");
		EROPrint.document.writeln(".grey {background-color: grey;}");
		EROPrint.document.writeln(".header .noborder {border: none;}");
		EROPrint.document.writeln(".title table {width: 100%; font-size:120%;}");
		EROPrint.document.writeln(".title table tr td:nth-child(1) {text-align: left;}");
		EROPrint.document.writeln(".title table tr td:nth-child(2) {text-align: center;}");
		EROPrint.document.writeln(".title table tr td:nth-child(3) {text-align: right;}");
		EROPrint.document.writeln(".header table {border-collapse: collapse; width: 100%; font-size: 65%; table-layout: fixed;}");
		EROPrint.document.writeln(".header table tr td {border: 2px solid black;}");
		EROPrint.document.writeln(".header .col1 {width: 2em; border: none; text-align: right;}");
		EROPrint.document.writeln(".header .col2 {width: 30em; border: none;}");
		EROPrint.document.writeln(".header .col3 {width: 3em; border: none;}");
		EROPrint.document.writeln(".header .col9 {width: 0.5em; border: none;}");
		EROPrint.document.writeln(".header .col15 {border: none;}");
		EROPrint.document.writeln(".header .row5 {height: 13.5em;}");
		EROPrint.document.writeln(".celltext1 {position: relative; top: -5em;}");
		EROPrint.document.writeln(".celltext6 {position: relative; top: -7em;}");
		EROPrint.document.writeln(".celltext9 {position: relative; top: -6em;}");
		EROPrint.document.writeln(".celltext10 {position: relative; top: -2.5em;}");
		EROPrint.document.writeln(".header table .celldata {height: 1.8em; font-size: 150%; border: 1px solid black;}");
		EROPrint.document.writeln(".header table .celltotal {height: 1.8em; font-size: 150%; !important;}");
		EROPrint.document.writeln("@media print { .noPrint { display:none;} }");
		EROPrint.document.writeln("</style>");
		EROPrint.document.writeln("</head>");
		EROPrint.document.writeln("<body>");

		// Print table header and up to maxlistcount names per page
		var maxlistcount = 15;
		var listcount = 0;
		var pagenumber = 0;
		var onlyheaderprinted = true;
		var emptyfound = false;
		var apptfound = false;
		var reschar = String.fromCharCode(171); // reserved slot
		var nameused = [];
		var ApptsToPrint = ApptCount;
		var Display_Date = +(Current_Date.substr(5,2)) + " / " + +(Current_Date.substr(8,2)) + " / " + Current_Date.substr(0,4);
		for (var namecount = 1; namecount <= ApptsToPrint; namecount++) {

			//find next alphabetical name
			testval = String.fromCharCode(20000);
			nameindex = 0;
			for (var j = 1; j <= ApptsToPrint; j++) {
				apptnametest = ApptNameVal[j].toUpperCase();
				if ((apptnametest == "") || (apptnametest.indexOf("&LAQUO;") >= 0) || (apptnametest.indexOf(reschar) >= 0)) {
					nameused[j] = 1;
					emptyfound = true;
				}
				if ((nameused[j] != 1) && (apptnametest < testval)) {
					nameindex = j;
					testval = apptnametest;
					apptfound = true;
				}
			}
			nameused[nameindex] = 1;
			if (! apptfound) ApptsToPrint = 0;
			
			if ((listcount == 0)) {
				EROPrint.document.writeln("<div class='" + (pagenumber ? 'pagebreak' : 'pageout') + "'>");
				EROPrint.document.writeln("<div class='title'>");
				EROPrint.document.writeln("\t<table>");
				EROPrint.document.writeln("\t\t<tr>");
				EROPrint.document.writeln("\t\t\t<th> __<u>" + Display_Date + "</u>__ Date</th>");
				EROPrint.document.writeln("\t\t\t<th>Activity Reporting, Quality Review& ERO Tracking Log<th>");
				EROPrint.document.writeln("\t\t\t<th>Page __<u>" + ++pagenumber + "</u>__</th>");
				EROPrint.document.writeln("\t\t</tr>");
				EROPrint.document.writeln("\t</table>");
				EROPrint.document.writeln("</div>");
				EROPrint.document.writeln("<div class='header'>");
				EROPrint.document.writeln("\t<table>");
				EROPrint.document.writeln("\t\t<tr>");
				EROPrint.document.writeln("\t\t\t<td class='col1'></td>");
				EROPrint.document.writeln("\t\t\t<td class='col2'></td>");
				EROPrint.document.writeln("\t\t\t<td class='col3'></td>");
				EROPrint.document.writeln("\t\t\t<td class='col3'></td>");
				EROPrint.document.writeln("\t\t\t<td class='col3'></td>");
				EROPrint.document.writeln("\t\t\t<td class='col3'></td>");
				EROPrint.document.writeln("\t\t\t<td class='col3'></td>");
				EROPrint.document.writeln("\t\t\t<td class='col3'></td>");
				EROPrint.document.writeln("\t\t\t<td class='col3'></td>");
				EROPrint.document.writeln("\t\t\t<td rowspan='5' class='col9'></td>");
				EROPrint.document.writeln("\t\t\t<td class='col3'></td>");
				EROPrint.document.writeln("\t\t\t<td class='col3'></td>");
				EROPrint.document.writeln("\t\t\t<td class='col3'></td>");
				EROPrint.document.writeln("\t\t\t<td class='col3'></td>");
				EROPrint.document.writeln("\t\t\t<td class='col15'></td>");
				EROPrint.document.writeln("\t\t</tr>");
				EROPrint.document.writeln("\t\t<tr>");
				EROPrint.document.writeln("\t\t\t<td colspan='2' class='noborder'> </td>");
				EROPrint.document.writeln("\t\t\t<td colspan='7' class='center'>Activity Reporting</td>");
				EROPrint.document.writeln("\t\t\t<td colspan='5' class='center'>E-file Tracking</td>");
				EROPrint.document.writeln("\t\t</tr>");
				EROPrint.document.writeln("\t\t<tr>");
				EROPrint.document.writeln("\t\t\t<td rowspan='3' > </td>");
				EROPrint.document.writeln("\t\t\t<td rowspan='3' class='center'>Taxpayer Name (also list those helped with<br />questions and answers only)<br /><br /><b>Last Name, First Name(s)</b></td>");
				EROPrint.document.writeln("\t\t\t<td colspan='5' class='center'>Type of Service</td>");
				EROPrint.document.writeln("\t\t\t<td rowspan='3'><div class='rotate celltext6'>6) Counselor's Initials</div></td>");
				EROPrint.document.writeln("\t\t\t<td rowspan='3'><div class='rotate celltext6'>7) Quality Reviewer's Initials</div></td>");
				EROPrint.document.writeln("\t\t\t<td rowspan='3'><div class='rotate celltext6'>8) Federal / State to be <b>e-filed</b></div></td>");
				EROPrint.document.writeln("\t\t\t<td rowspan='3'><div class='rotate celltext6'>9) 8879 Signed by all Taxpayers</div></td>");
				EROPrint.document.writeln("\t\t\t<td colspan='2' class='center'><b>ERO&nbsp;ONLY</b></td>");
				EROPrint.document.writeln("\t\t\t<td rowspan='3'><div class='celltext10'>10) Comment Examples:");
				EROPrint.document.writeln("\t\t\t\t<br />&nbsp;&nbsp;&nbsp;<b>NNTF</b> - No Need to File");
				EROPrint.document.writeln("\t\t\t\t<br />&nbsp;&nbsp;&nbsp;<b>OOS</b> - Reason (why return is Out of Scope)");
				EROPrint.document.writeln("\t\t\t\t<br />&nbsp;&nbsp;&nbsp;Amended <b>Tax Year</b>");
				EROPrint.document.writeln("\t\t\t\t<br />&nbsp;&nbsp;&nbsp;Prior <b>Tax Year</b> (use a separate line for each year)");
				EROPrint.document.writeln("\t\t\t\t<br />&nbsp;&nbsp;&nbsp;<b>Federal Only</b> (no State Return)");
				EROPrint.document.writeln("\t\t\t\t<br />&nbsp;&nbsp;&nbsp;Reason for Paper Return");
				EROPrint.document.writeln("\t\t\t\t<br />&nbsp;&nbsp;&nbsp;<b>8453</b> - Reason form is attached for mailing");
				EROPrint.document.writeln("\t\t\t\t<br />&nbsp;&nbsp;&nbsp;<b>8879 HOLD</b> - (and/or state equivalent) Signature(s) Needed");
				EROPrint.document.writeln("\t\t\t\t<br />&nbsp;&nbsp;&nbsp;<b>Taxpayer HOLD</b> - incomplete return - taxpayer will return</div></td>");
				EROPrint.document.writeln("\t\t</tr>");
				EROPrint.document.writeln("\t\t<tr>");
				EROPrint.document.writeln("\t\t\t<td colspan='4' class='center red'>Check for PAPER FILED<br />ONLY</td>");
				EROPrint.document.writeln("\t\t\t<td class='center'>Q&A</td>");
				EROPrint.document.writeln("\t\t\t<td rowspan='2''><div class='rotate celltext9'>Federal Return Sent/Acknowledged</div></td>");
				EROPrint.document.writeln("\t\t\t<td rowspan='2'><div class='rotate celltext9'>State Return Sent/Acknowledged</div></td>");
				EROPrint.document.writeln("\t\t</tr>");
				EROPrint.document.writeln("\t\t<tr class='row5'>");
				EROPrint.document.writeln("\t\t\t<td><div class='rotate celltext1'>1) Federal Return (Current Yr)</div></td>");
				EROPrint.document.writeln("\t\t\t<td><div class='rotate celltext1'>2) Federal Return (Prior Yr)</div></td>");
				EROPrint.document.writeln("\t\t\t<td><div class='rotate celltext1'>3) Federal Return (Amended)</div></td>");
				EROPrint.document.writeln("\t\t\t<td><div class='rotate celltext1'>4) State/Local <b><u>Only Return</u></b></div></td>");
				EROPrint.document.writeln("\t\t\t<td><div class='rotate celltext1'>5) Question & Answer <b><u>Only</u></b></div></td>");
				EROPrint.document.writeln("\t\t</tr>");
				onlyheaderprinted = true;
			}

			if (nameindex) {
				Add_ERO_Line(++listcount, ApptNameVal[nameindex]);
				onlyheaderprinted = false;
			}
		
			if (listcount >= maxlistcount) {
				Add_Totals_Line(); // then print the next page
				listcount = 0;
				emptyfound = false;
			}
		}

		// All done, finish the page with blank lines if needed
		if (listcount || emptyfound || onlyheaderprinted) {
			while (listcount < maxlistcount) Add_ERO_Line(++listcount, "");
			Add_Totals_Line();
		}

		EROPrint.document.writeln("</body>");
		EROPrint.print();
	}

	//===========================================================================================
	function Add_ERO_Line(listcount, name) {
	//===========================================================================================
		EROPrint.document.writeln("\t\t<tr>");
		EROPrint.document.writeln("\t\t\t<td class='celldata'>" + listcount + "</td>");
		EROPrint.document.writeln("\t\t\t<td class='celldata'>" + _Show_Chars(name, "html") + "</td>");
		EROPrint.document.writeln("\t\t\t<td class='celldata'></td>");
		EROPrint.document.writeln("\t\t\t<td class='celldata'></td>");
		EROPrint.document.writeln("\t\t\t<td class='celldata'></td>");
		EROPrint.document.writeln("\t\t\t<td class='celldata'></td>");
		EROPrint.document.writeln("\t\t\t<td class='celldata'></td>");
		EROPrint.document.writeln("\t\t\t<td class='celldata'></td>");
		EROPrint.document.writeln("\t\t\t<td class='celldata'></td>");
		EROPrint.document.writeln("\t\t\t<td class='noborder'></td>");
		EROPrint.document.writeln("\t\t\t<td class='celldata'></td>");
		EROPrint.document.writeln("\t\t\t<td class='celldata'></td>");
		EROPrint.document.writeln("\t\t\t<td class='celldata'></td>");
		EROPrint.document.writeln("\t\t\t<td class='celldata'></td>");
		EROPrint.document.writeln("\t\t\t<td class='celldata'></td> </tr>");
	}

	//===========================================================================================
	function Add_Totals_Line() {
	//===========================================================================================
		EROPrint.document.writeln("\t\t<tr>");
		EROPrint.document.writeln("\t\t\t<td class='celltotal grey'></td>");
		EROPrint.document.writeln("\t\t\t<td class='celltotal'>ACTIVITY TOTALS</td>");
		EROPrint.document.writeln("\t\t\t<td class='celltotal'></td>");
		EROPrint.document.writeln("\t\t\t<td class='celltotal'></td>");
		EROPrint.document.writeln("\t\t\t<td class='celltotal'></td>");
		EROPrint.document.writeln("\t\t\t<td class='celltotal'></td>");
		EROPrint.document.writeln("\t\t\t<td class='celltotal'></td>");
		EROPrint.document.writeln("\t\t\t<td colspan='2' class='celltotal grey'></td>");
		EROPrint.document.writeln("\t\t\t<td class='noborder'></td>");
		EROPrint.document.writeln("\t\t\t<td colspan='5' class='celltotal grey'></td>");
		EROPrint.document.writeln("\t</table>");
		EROPrint.document.writeln("</div> <!-- header -->");
		EROPrint.document.writeln("</div> <!-- pageout -->");
	}

	//===========================================================================================
	function Open_Window(URL) {
	//===========================================================================================
		window.open(URL);
	}

	//===========================================================================================
	function Show_ExportBox() {
	//===========================================================================================
		if (MoveMode) return;
		ExportBox.style.visibility = "visible";
		Make_EB_List();
	}

	//----------------------------------------------------------------------------------------
	function EB_Select_All() {
	//----------------------------------------------------------------------------------------
		for (c = 0 ; c < listEBChildren.length ; c++ ) {
			listEBChildren[c].children[0].children[0].checked = true;
		}
		Make_EB_List();
	}

	//----------------------------------------------------------------------------------------
	function Make_EB_List() {
	//----------------------------------------------------------------------------------------
		ExportList = "";
		for (c = 0 ; c < listEBChildren.length ; c++ ) {
			exportFlag = listEBChildren[c].children[0].children[0].checked;
			nullBox = listEBChildren[c].children[1].children[0];
			nullFlag = nullBox.checked;
			if (exportFlag) {
				nullBox.disabled = false;
				ExportList += ((ExportList) ? "|" : "" );
				ExportList += ((nullFlag) ? "*" : "" );
				ExportList += listEBChildren[c].children[0].innerText.trim();
			}
			else {
				// Uncheck the null box if the field is not exported
				nullBox.disabled = true;
				nullBox.checked = false;
			}
		}
	}

	//----------------------------------------------------------------------------------------
	function EB_dragStart(e) {
	//----------------------------------------------------------------------------------------
  		//e.dataTransfer.effectAllowed = 'move';
  		//e.dataTransfer.setData('text/plain', null);
  		EBselected = e.target;
	}

	//----------------------------------------------------------------------------------------
	function EB_dragOver(e) {
	//----------------------------------------------------------------------------------------
		e.preventDefault();
		el_targ = (e.target.parentNode === listEBParent) ? e.target : e.target.parentNode ;
		targPos = Target_Position(EBselected, el_targ);
		switch (targPos[0]) {
			case "same": 
				EBselected.style.visibility = "hidden";
				return;
			case "before":
				EBselected.style.visibility = "visible";
				listEBParent.insertBefore(EBselected, el_targ.nextElementSibling);
				break;
			case "after":
				EBselected.style.visibility = "visible";
				listEBParent.insertBefore(EBselected, el_targ);
				break;
			default: return;
		}
		listEBChildren = EBlist.children;
	}

	//----------------------------------------------------------------------------------------
	function EB_dragEnd() {
	//----------------------------------------------------------------------------------------
		EBselected.style.visibility = "inherit";
		EBselected = null
		Make_EB_List();
	}

	//----------------------------------------------------------------------------------------
	function Target_Position(el_sel, el_targ) {
	//----------------------------------------------------------------------------------------
		for ( c = 0 ; c < listEBChildren.length ; c++ ) {
			if (listEBChildren[c] === el_sel) sel_index = c;
			if (listEBChildren[c] === el_targ) targ_index = c;
		}
		if ((typeof sel_index === "undefined") || (typeof targ_index === "undefined")) return ["none",,];
		if (sel_index < targ_index) return ["before", sel_index, targ_index];
		if (sel_index === targ_index) return ["same", sel_index, targ_index];
		if (sel_index > targ_index) return ["after", sel_index, targ_index];
	}

	//===========================================================================================
	function Print_Excel() {
	//===========================================================================================
		if (MoveMode) return;
		count = 0;
		for (j = 1; j <= ApptLocList[0]; j++) {
			count += (ApptLocList[j] == 1) ? 1 : 0;
		}
		if (count == 0) { alert("No sites have been selected."); return; }

		ApptForm.IDApptSlot.value = "PrintExcel";
		ApptForm.IDApptReason.value = ExportList; // from Make_EB_List()
		if (ExportList == "") { alert("No export fields have been selected."); return; }
		_Set_Cookie("ExportList", ExportList);
		ApptOp("PrintExcel");
	}

	//===========================================================================================
	function Show_SlotBox() {
	//===========================================================================================
		if (MoveMode) return;
		SList = document.getElementsByClassName("locmanage");
		if (SList.length == 1) SList[0].selected = true;
		SlotBox.style.visibility = "visible";
	}

	//===========================================================================================
	function Do1Slot(action, slotloc, slotdate, slottime) {
	//	Fill and emulate the slot configurator for a single add or remove
	//===========================================================================================
		// check the option
		if (action == "add") SlotAction.value = "SlotAdd1";
		if (action == "rmv") SlotAction.value = "SlotRemove1";

		// set the location
		SlotLocation.value = slotloc;

		// set the date
		SlotStart.value =
		SlotStop.value = slotdate;

		// set the time for 1 slot
		SlotTime1.value = slottime;
		SlotCount1.value = 1;
		SlotCount2.value =
		SlotCount3.value =
		SlotCount4.value =
		SlotCount5.value =
		SlotCount6.value =
		SlotCount7.value =
		SlotCount8.value = "";
		SlotRes1.value = 0;

		// make the change
		Manage_Appointments(true, true, true);
	}
	
	//===========================================================================================
	function AddNewTime(slotdate) {
	//	Fill and emulate the slot configurator for a single added slot to a new date
	//===========================================================================================
		// check input
		if (_Verify_Time(TimeToAdd.value, true)[0]) return;
		if (LocationToAdd.value == 0) { alert("Select a site for the new time group."); return; }
		if (NewSlotsToAdd.value < 1) { alert("Enter the number of slots to add"); return; }
		if (NewSlotsToAdd.value > 10) {
			reply = confirm("Do you really want to add that many?");
			if (! reply) return;
		}

		// Select "Add"
		SlotAction.value = "SlotAdd1";

		// set the location
		SlotLocation.value = LocationToAdd.value;
		// make sure the check box is checked so you can see the addition
		ApptLocList[SlotLocation.selectedIndex] = 1;
		ApptForm.IDApptLoc.value = ApptLocList;

		// set the date
		SlotStart.value =
		SlotStop.value = slotdate;

		// set the time for 1 slot
		SlotTime1.value = TimeToAdd.value;
		SlotCount1.value = NewSlotsToAdd.value;
		SlotCount2.value =
		SlotCount3.value =
		SlotCount4.value =
		SlotCount5.value =
		SlotCount6.value =
		SlotCount7.value =
		SlotCount8.value = "";
		SlotRes1.value = 0;

		// make the change
		Manage_Appointments(true, true, true);
	}
	
	//===========================================================================================
	function Slot_Date_Change() {
	//===========================================================================================
		SlotStop.value = SlotStart.value;
	}

	//===========================================================================================
	function Fill_Pattern() {
	// Fills the options with the patterned selected
	//===========================================================================================
		pattgrp = document.getElementById("SBOptions" + SlotLocation.value);
		var fillPattern = "";
		if (typeof pattgrp !== "undefined") fillPattern = pattgrp.value;

		// create a null pattern if needed
		if (fillPattern === "") fillPattern = "0|0|" + TodayDate + "," + TodayDate + "||";

		var patternOpts = fillPattern.split("|");
		PatternSaved = patternOpts[2] + "|" + patternOpts[3] + "|" + patternOpts[4];

		// populate the dates
		var pattdates = patternOpts[2].split(",");
		// make the year current if it's not
		// Start Date:
		var thisyr = +TodayDate.substr(0, 4);
		var pattdate = pattdates[0].split("-");
		var startyr = +pattdate[0];
		pattdates[0] = Math.max(startyr, thisyr) + "-" + pattdate[1] + "-" + pattdate[2];
		SlotStart.value = pattdates[0];

		// Stop Date:
		var pattdate = pattdates[1].split("-");
		var stopyr = +pattdate[0];
		stopyr = thisyr + ((stopyr > startyr) ? 1 : 0);
		pattdates[1] = stopyr + "-" + pattdate[1] + "-" + pattdate[2];
		SlotStop.value = pattdates[1];

		// populate the days of the week 
		SlotSun.checked = (patternOpts[3].search("Sun") >= 0);
		SlotMon.checked = (patternOpts[3].search("Mon") >= 0);
		SlotTue.checked = (patternOpts[3].search("Tue") >= 0);
		SlotWed.checked = (patternOpts[3].search("Wed") >= 0);
		SlotThu.checked = (patternOpts[3].search("Thu") >= 0);
		SlotFri.checked = (patternOpts[3].search("Fri") >= 0);
		SlotSat.checked = (patternOpts[3].search("Sat") >= 0);

		// populate the time slots
		var patternOptsTimes = patternOpts[4].split(",");
		var potcount = patternOptsTimes.length / 3;
		var potindex = 1;
		for (var i = 1; i <= 8; i++) {
			potnumCell = document.getElementById("SlotCount" + i);
			potnumCell.value = (i <= potcount) ? patternOptsTimes[potindex++] : "";
			potnumCell = document.getElementById("SlotTime" + i);
			potnumCell.value = (i <= potcount) ? patternOptsTimes[potindex++] : "";
			potnumCell = document.getElementById("SlotRes" + i);
			potnumCell.value = (i <= potcount) ? patternOptsTimes[potindex++] : "";
		}

		// Test the data that was in the pattern
		Manage_Appointments(false, false, false);
	}

	//===========================================================================================
	function Manage_Appointments(GO, NoWarning, SetAllDOW) {
	//===========================================================================================
		// Disable GO button until all parameters are validated
		SBGoButton.style.backgroundColor = "yellow";
		pattgrp = document.getElementById("SBOptions" + SlotLocation.value);
		SBPatternDelete.style.display = (pattgrp.selectedIndex > 0) ? "inline" : "none";
		SBPatternSave.style.display = "none";
		SBPatternSaveButtons.style.display = "none";
		SBPatternResponse.innerHTML = "";

		// Is an option button checked?
		ApptForm.IDApptSlot.value = tempSlotvalue = "";
		if (SlotAction.value == "") {
			if (GO) alert("You must check one of the task options.");
			return;
		}
		tempSlotvalue = SlotAction.value;

		// Hide unneeded sections
		// P = patterns, D = Dates/Days, T = times
		var showNone = ["none",      "none",   "none"  ];
		var showP =    ["table_row", "none",   "none"  ];
		var showD =    ["none",      "inline", "none"  ];
		var showPD =   ["table_row", "inline", "none"  ];
		var showPDT =  ["table_row", "inline", "inline"];
		var showSet = showNone;
		var WarnMessage = "";
		var WarnMessageLoc = " for the " + SlotLocation.options[SlotLocation.selectedIndex].innerHTML + ". \n\nOK to proceed?";
		switch (tempSlotvalue) {
			case "SlotAdd":
			case "SlotAdd1":
				showSet = showPDT;
				break;
			case "SlotRemove":
				WarnMessage = "This will remove up to the designated number of unused appointment slots but will retain any busy or reserved slots" + WarnMessageLoc;
			case "SlotRemove1":
				showSet = showPDT;
				break;
			case "SlotRemoveDeleted":
				showSet = showNone;
				WarnMessage = "This will remove all data from the deleted list for the " + WarnMessageLoc + "\n\nCAUTION: DO NOT USE during the work season or you will lose contact history for these taxpayers.";
				break;
			case "SlotDeleteCallback":
				showSet = showNone;
				WarnMessage = "This will move all data from the callback list to the deleted list for the " + WarnMessageLoc;
				break;
			case "SlotClear":
				showSet = showPD;
				SBPatternOption.style.display = "table-row";
				WarnMessage = "This will remove all data from appointment slots but retain the slots as scheduled" + WarnMessageLoc + "\n\nCAUTION: Use this ONLY to remove testing data. DO NOT USE during the work season or you will lose your taxpayer data.";
				break;
			case "SlotClone":
				showSet = showD;
				WarnMessage = "This will copy the appointment structure from the previous year with dates adjusted to retain the day-of-the-week pattern. It will remove all data and structure from the previous year. Check especially the first and last days since they may not be properly created.";
				break;
			case "SlotRemoveAll":
				WarnMessage = "This will remove all appointment data for a clean start, including \"deleted\" entries";
				break;
			case "SlotRemoveDateRange":
				showSet = showD;
				SetAllDOW = true;
				WarnMessage = "This will remove all appointment data from " + SlotStart.value + " through " + SlotStop.value; 
				break;
		}
		SBPatternOption.style.display = showSet[0];
		SBDays.style.display = SBDates.style.display = showSet[1];
		SBSlots.style.display = showSet[2];

		// Should all days of the week be checked?
		if (SetAllDOW) {
			SlotSun.checked =
			SlotMon.checked =
			SlotTue.checked =
			SlotWed.checked =
			SlotThu.checked =
			SlotFri.checked =
			SlotSat.checked = true;
		}

		if (SlotLocation.value == 0) {
			if (GO) alert("A location must be specified");
			return;
		}
		ApptForm.IDApptSlotLoc.value = SlotLocation.value;

		ApptForm.IDApptSlotDays.value = "";
		if (SlotSun.checked) ApptForm.IDApptSlotDays.value += ", Sun";
		if (SlotMon.checked) ApptForm.IDApptSlotDays.value += ", Mon";
		if (SlotTue.checked) ApptForm.IDApptSlotDays.value += ", Tue";
		if (SlotWed.checked) ApptForm.IDApptSlotDays.value += ", Wed";
		if (SlotThu.checked) ApptForm.IDApptSlotDays.value += ", Thu";
		if (SlotFri.checked) ApptForm.IDApptSlotDays.value += ", Fri";
		if (SlotSat.checked) ApptForm.IDApptSlotDays.value += ", Sat";
		if ((ApptForm.IDApptSlotDays.value == "") && (showSet[1] != "none")) {
			if (GO) alert("A day of the week must be specified");
			return;
		}

		if ((SlotStart.value == "") && (showSet[1] != "none")) {
			if (GO) alert("A starting date must be specified");
			return;
		}
		// Check SlotTime values
		if (_Verify_Date(SlotStart.value, true)[0]) { SlotStart.value = TodayDate; return; }
		if (_Verify_Date(SlotStop.value, true)[0]) { SlotStop.value = SlotStart.value; return; }

		if (SlotStop.value < SlotStart.value) SlotStop.value = SlotStart.value;
		ApptForm.IDApptSlotDates.value = SlotStart.value + ", " + SlotStop.value;
		ApptForm.IDApptSlot.value = tempSlotvalue;

		// check the slot values
		var slots = "";
		for (var slotidx = 1; slotidx <= 8; slotidx++) {
			slotcount = document.getElementById("SlotCount" + slotidx);
			slottime = document.getElementById("SlotTime" + slotidx);
			slotres = document.getElementById("SlotRes" + slotidx);
			if (+slotcount.value == 0) {
				slottime.value = "";
				slotres.value = "";
				continue;
			}
			if (slotcount.value > SlotLimit) {
				warnmessage = slotcount + " seems like a large value.\n\nDo you really mean this many slots?";
				reply = confirm(warnmessage);
				if (! reply) return;
				SlotLimit = slotcount.value;
			}
			slots += "," + +slotcount.value + "," + slottime.value + "," + +slotres.value;
		}
		if ((slots == "") && (showSet[2] != "none")) {
			if (GO) alert("You have not assigned any slots");
			return;
		}
		ApptForm.IDApptSlotSets.value = slots;

		// Check on optional reserved slots
		res = "OK";
		toomany = "hotpink";
		res += SlotRes1.style.backgroundColor = (+SlotRes1.value > +SlotCount1.value) ? toomany : "";
		res += SlotRes2.style.backgroundColor = (+SlotRes2.value > +SlotCount2.value) ? toomany : "";
		res += SlotRes3.style.backgroundColor = (+SlotRes3.value > +SlotCount3.value) ? toomany : "";
		res += SlotRes4.style.backgroundColor = (+SlotRes4.value > +SlotCount4.value) ? toomany : "";
		res += SlotRes5.style.backgroundColor = (+SlotRes5.value > +SlotCount5.value) ? toomany : "";
		res += SlotRes6.style.backgroundColor = (+SlotRes6.value > +SlotCount6.value) ? toomany : "";
		res += SlotRes7.style.backgroundColor = (+SlotRes7.value > +SlotCount7.value) ? toomany : "";
		res += SlotRes8.style.backgroundColor = (+SlotRes8.value > +SlotCount8.value) ? toomany : "";
		if (res.search("hotpink") > -1) {
			if (GO) alert("You cannot reserve more than the number of total slots requested.");
			return;
		}

		// Check SlotTime values
		if ((SlotCount1.value > 0) && (_Verify_Time(SlotTime1.value, true)[0])) { SlotTime1.value = ""; return; }
		if ((SlotCount2.value > 0) && (_Verify_Time(SlotTime2.value, true)[0])) { SlotTime2.value = ""; return; }
		if ((SlotCount3.value > 0) && (_Verify_Time(SlotTime3.value, true)[0])) { SlotTime3.value = ""; return; }
		if ((SlotCount4.value > 0) && (_Verify_Time(SlotTime4.value, true)[0])) { SlotTime4.value = ""; return; }
		if ((SlotCount5.value > 0) && (_Verify_Time(SlotTime5.value, true)[0])) { SlotTime5.value = ""; return; }
		if ((SlotCount6.value > 0) && (_Verify_Time(SlotTime6.value, true)[0])) { SlotTime6.value = ""; return; }
		if ((SlotCount7.value > 0) && (_Verify_Time(SlotTime7.value, true)[0])) { SlotTime7.value = ""; return; }
		if ((SlotCount8.value > 0) && (_Verify_Time(SlotTime8.value, true)[0])) { SlotTime8.value = ""; return; }

		PatternNew = IDApptSlotDates.value
			+ "|" + IDApptSlotDays.value
			+ "|" + IDApptSlotSets.value;
		pattgrp = document.getElementById("SBOptions" + SlotLocation.value);
		SBPatternSave.style.display = ((pattgrp.selectedIndex == 0) || (PatternNew == PatternSaved)) ? "none" : "inline";

		// We have all we need
		SBGoButton.style.backgroundColor = "lightgreen";
		SBPatternSaveButtons.style.display = (SlotAction.value == "SlotAdd") ? "block" : "none";

		if (GO) {
			if (WarnMessage && (! NoWarning)) {
				OKtoGO = confirm(WarnMessage);
				if (! OKtoGO) return;
			}

			SBWaitMessage.style.display = "block";
			SBGoButton.style.display = "none";
			SBCancelButton.style.display = "none";
			WaitBox.style.display = "block";
			document.getElementById("ApptForm").submit();
		}
	}

	//===========================================================================================
	function List_Site_Patterns() {
	//===========================================================================================
		pattlist = document.getElementsByClassName("SBOptClass");
		thisid = "SBOptions" + SlotLocation.value;
		for (pattidx = 0; pattidx < pattlist.length; pattidx++) {
			pattlist[pattidx].style.display = (pattlist[pattidx].id == thisid) ? "inline" : "none";
		}
	}

	//===========================================================================================
	function Save_Pattern(request) {
	//===========================================================================================
		if (SlotLocation.value == 0) {
			alert("You must select a site");
			return;
		}
		pattgrp = document.getElementById("SBOptions" + SlotLocation.value);
		if ((request == "SBPatternDelete") && (pattgrp.options.selectedIndex == 0)) {
			alert("You cannot delete this");
			return;
		}

		var patternId = pattgrp.options[pattgrp.selectedIndex].value.split("|")[1];
		var patternName = pattgrp.options[pattgrp.selectedIndex].innerHTML;

		if (request == "SBPatternSaveAs") {
			patternId = 0;
			var patternList = [];
			var promptPatternList = "";
			plidx = 0;
			while (typeof pattgrp.options[plidx] !== "undefined") {
				patternList[plidx] = pattgrp.options[plidx].innerHTML;
				if (plidx) promptPatternList += "\n   - " + patternList[plidx];
				plidx++;
			}
			patternNameText = _Show_Chars(patternName, "text");
			promptMessage = "You currently have the following pattern names:";
			promptMessage += promptPatternList;
			promptMessage += "\n\nPlease enter the name for this pattern:";
			patternName = prompt(promptMessage, patternNameText); 
			if (patternName === null) return;
			pni = patternList.indexOf(patternName);
			if (pni == 0) { alert("You cannot use that name"); return; }
			if (pni >= 0) { // Name already used - change to "Save" and overwrite it
				patternId = pattgrp.options[pni].value.split("|")[1];
				request = "SBPatternSave";
			}
		}

		if (patternName == "") { alert("Please name the appointment pattern."); return; }
		// deal with some special characters
		patternNameClean = _Clean_Chars(patternName);

		pattern = request
			+ "~" + patternId
			+ "~" + SlotLocation.value
			+ "~" + patternNameClean
			//+ "~" + encodeURIComponent(patternNamex)
			+ "~" + IDApptSlotDates.value // part 1 of data
			+ "|" + IDApptSlotDays.value  // part 2 of data
			+ "|" + IDApptSlotSets.value  // part 3 of data
			+ "~" + "$"; // end of request tag
	
		// using AJAX to submit the change vs a save change button
		var xmlhttp = new XMLHttpRequest();
		xmlhttp.onreadystatechange = function() {
          		if (this.readyState == 4 && this.status == 200) {
				if (this.responseText == 0) {
					message = "Saving the appointment schedule did not seem to work.\n";
					SBPatternResponse.innerHTML = "error";
					alert(message);
				}
				var newpattern = IDApptSlotDates.value // part 1 of data
					+ "|" + IDApptSlotDays.value  // part 2 of data
					+ "|" + IDApptSlotSets.value; // part 3 of data
				var newvalue = IDApptSlotLoc.value + "|" + this.responseText + "|" + newpattern;
				switch (request) {
				case "SBPatternSaveAs":
					if (patternName == "") {
						alert("Save As has been cancelled because a name was not entered.");
						return;
						}
					var myloc = false;
					var added = false;
					var newopt = document.createElement("option");
					newopt.innerHTML = patternName;
					newoptName = patternName.toUpperCase();
					newopt.value = newvalue;
					for (optidx = 0; optidx < pattgrp.options.length; optidx++) {
						testName = pattgrp.options[optidx].innerHTML.toUpperCase();
						if (testName > newoptName) {
							pattgrp.insertBefore(newopt, pattgrp.options[optidx]);
							added = true;
							break;	
						}
					}
					if (! added) pattgrp.add(newopt);
					pattgrp.selectedIndex = optidx;
					// no break
				case "SBPatternSave":
					SBPatternResponse.innerHTML = (this.responseText > 0) ? "saved" : "error";
					if (this.responseText > 0) SBPatternSave.style.display = "none";
					pattgrp.options[pattgrp.selectedIndex].value = newvalue;
					PatternSaved = newpattern;
					break;
				case "SBPatternDelete":
					pattgrp.remove(pattgrp.selectedIndex);
					break;
				}
			}
		}
		xmlhttp.open("GET", "patternsave.php?q=" + pattern, true);
		xmlhttp.send();
	}

	//===========================================================================================
	function Site_Manage() {
	//===========================================================================================
		if (MoveMode) return;
		ApptForm.action = 'sitemanage.php';
		document.getElementById("ApptForm").submit();
	}

	//===========================================================================================
	function Show_SearchBox(ID) {
	//===========================================================================================
		if (MoveMode) return;
		switch (ID) {
			case "FindByTagsButton":
			case "SearchApptTags":
				FindByTags.checked = true;
				break;
			case "FindByNameButton":
			case "SearchApptName":
				FindByName.checked = true;
				break;
			case "FindByEmailButton":
			case "SearchApptEmail":
				FindByEmail.checked = true;
				break;
			case "FindByPhoneButton":
			case "SearchAppt":
			case "SearchApptPhone":
				// no break
			default:
				FindByPhone.checked = true;
		}
		FindByTagsButton.style.backgroundColor = FindByTags.checked ? "lightgreen" : "transparent";
		FindByNameButton.style.backgroundColor = FindByName.checked ? "lightgreen" : "transparent";
		FindByEmailButton.style.backgroundColor = FindByEmail.checked ? "lightgreen" : "transparent";
		FindByPhoneButton.style.backgroundColor = FindByPhone.checked ? "lightgreen" : "transparent";
		SearchBox.style.visibility = "visible";
		FindByVal.focus();
	}

	//===========================================================================================
	function Hide_SearchBox() {
	//===========================================================================================
		SearchBox.style.visibility = "hidden";
	}


	//===========================================================================================
	function Hide_SlotBox() {
	//===========================================================================================
		SlotBox.style.visibility = "hidden";
	}

	//===========================================================================================
	function Hide_ExportBox() {
	//===========================================================================================
		ExportBox.style.visibility = "hidden";
	}

	//===========================================================================================
	function Show_History() {
	//===========================================================================================
		change_history.style.display = (change_history.style.display == 'none') ? 'block' : 'none';
	}

	</script>
</head>

<!--================================================= WEB PAGE BODY ==========================================-->
<!--================================================= WEB PAGE BODY ==========================================-->
<!--================================================= WEB PAGE BODY ==========================================-->
<body class="bodyclass" onload='Initialize();'>

<div id="Main">

	<div class="appt_page_header">
		<h1>Tax-Aide Appointments</h1>
		<?php
		global $UserFullName; 
		echo "You are signed in as " . _Show_Chars($UserFullName, "html") . "\n";
		Create_Menu();
		?>
	</div>


	<div id="sidebarDiv">
		<?php
		Calc_Slots();
		global $OtherAppts;
		?>
	</div>

	<div class="slots">
		<?php
		Show_Slots();
		?>

<!--
<?php
//	global $change_history;
//	global $isAdministrator;
//	if ($isAdministrator and ($_SESSION["NewVersion"] > $_SESSION["SystemVersion"])) {
//		echo "\t<div id='new_version_notify'>";
//		echo "\t\tA new version " . $_SESSION["NewVersion"] . " is available.<br />\n";
//		echo "\t\t<button id='new_version_button' onclick=\"Show_History();\">See/hide changes</button>\n";
//		echo "\t\t" . $change_history;
//		echo "\t</div>";
//	}
?>
-->

<script>
<?php
	global $ApptNo;
	global $ApptTimeDisplay;
	global $ApptName;
	global $ApptPhone;
	global $ApptEmail;
	global $ApptSite;
	global $Appt10dig;
	global $ApptTags;
	global $ApptNeed;
	global $ApptInfo;
	global $ApptView;
	global $ApptStatus;
	global $UserOptions;
	global $LastSlotNumber;
	global $HeaderText;
	global $FirstSlotDate;
	global $DeletedClassFlag, $CallbackClassFlag;
	global $UserHome;
	global $DisplayDate;
	$DisplayDate = Format_Date($FirstSlotDate, true); // set $MON which is global
	echo "Display_Date = '$DisplayDate';\n";
	echo "Current_Date = '$FirstSlotDate';\n";
	echo "ApptCount = $LastSlotNumber;\n";
	echo "ApptNoVal = [$ApptNo];\n";
	echo "ApptTimeVal = [$ApptTimeDisplay];\n";
	echo "ApptNameVal = [$ApptName];\n";
	echo "ApptPhoneVal = [$ApptPhone];\n";
	echo "ApptEmailVal = [" . htmlspecialchars_decode($ApptEmail ?? '') . "];\n";
	echo "ApptSiteVal = [$ApptSite];\n";
	echo "Appt10digVal = [$Appt10dig];\n";
	echo "ApptLocList = [$FormApptLoc];\n";
	echo "ApptTagsVal = [$ApptTags];\n";
	echo "ApptNeedVal = [$ApptNeed];\n";
	echo "ApptInfoVal = [$ApptInfo];\n";
	$ApptStatus = htmlspecialchars_decode($ApptStatus ?? '');
	echo "ApptStatusVal = [$ApptStatus];\n";
	//if (($UserHome == 0) AND ($_SESSION["UserLoc"] > 0)) echo "Loc" . $ul . ".checked = true;\n";
	if ($ApptView != "ViewUser") {
		if ($CallbackClassFlag) {
			echo "ViewCallback.style.color = 'blue';\n";
			echo "ViewCallback.style.fontWeight = 'bold';\n";
		}
		if ($DeletedClassFlag) {
			echo "ViewDeleted.style.color = 'blue';\n";
			echo "ViewDeleted.style.fontWeight = 'bold';\n";
		}
	}
?>
</script>

<div id="ApptBox" style="visibility:<?php global $ApptBox; echo $ApptBox; ?>;">
	<span id="Display_Date" style="display: none;">
<?php
	global $DisplayDate;
	echo $DisplayDate;
?>
	</span>
	<div id="ApptTitle">WINDOW TITLE</div>
	<table width="100%"><tr><td>
	<div id="ApptDataDiv">
	<div id="ApptFormBox">
		<form id="ApptForm" action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="post">
		<table id="ApptDataTable">
			<tr>	<td>Name: </td>
				<td><input id="IDApptName" name="IDApptName" class="formtext" type="text"
					title="Enter the taxpayer's name, and spouse if married" 
					onchange="Test_Email();"/></td></tr>
			<tr>	<td>Phone: </td>
				<td><input id="IDApptPhone" name="IDApptPhone" type="text"
					<?php
					global $Location10dig, $LocationLookup, $UserHome;
					global $ApptView, $UserPhone;
					if ($ApptView=='ViewUser') echo ('value="' . $UserPhone . '" disabled="disabled"');
					$title = "Enter taxpayer's phone number";
					if (($UserHome == 0) OR ($Location10dig[$LocationLookup['S' . $UserHome]])) $title .= ", including area code";
					echo " title=\"$title\"";
					?>
					onchange="Test_Phone(true)" />
					<span id="NoPhone" onclick="Test_Phone(false);">NONE</span></td></tr>

			<tr>	<td>Email: </td>
				<td><input id="IDApptEmail" name="IDApptEmail" class="formtext" type="text"
					title="Enter the taxpayer's email" onchange="Test_Email()" 
					<?php global $ApptView, $UserEmail;
					if ($ApptView=='ViewUser') echo ('value="' . htmlspecialchars_decode($UserEmail ?? '') . '" disabled="disabled"'); ?>
					/></td></tr>
			<tr <?php global $ApptView; if ($ApptView == "ViewUser") echo "style='display: none;'" ?>>
				<td>Tags: </td>
				<td style="text-align: left; font-weight: normal; font-size: 80%;">(Will print after the name in the daily view)</td></tr>
			<tr <?php global $ApptView; if ($ApptView == "ViewUser") echo "style='display: none;'" ?>>
				<td></td>
				<td>	<textarea id="TagsText" class="formtext" type="text" onchange="Change_Text(this.id)"></textarea>
					<input class='hidden' id="IDApptTags" name="IDApptTags" type="text" /></td></tr>

			<tr>	<td>Note: </td>
			<?php	global $ApptView;
				if ($ApptView != "ViewUser") {
					echo "<td style='text-align: left; font-weight: normal; font-size: 80%;'>\n";
					echo "(Will print as a footnote on check-in list)</td></tr>\n";
					echo "<tr><td></td>\n";
				}
				?>
				<td><textarea id='NeedText' class='formtext' type='text' onchange='Change_Text(this.id)'></textarea>
				<input class='hidden' id='IDApptNeed' name='IDApptNeed' type='text' /></td></tr>
			<tr <?php global $ApptView; if ($ApptView == "ViewUser") echo "style='display: none;'" ?>>
				<td>Info: </td>
				<td style="text-align: left; font-weight: normal; font-size: 80%;">(Information just for schedulers)</td></tr>
			<tr <?php global $ApptView; if ($ApptView == "ViewUser") echo "style='display: none;'" ?>>
				<td></td>
				<td>	<textarea id="InfoText" class="formtext" type="text" onchange="Change_Text(this.id)"></textarea>
					<input class='hidden' id="IDApptInfo" name="IDApptInfo" type="text" /></td></tr>
			<tr>	<td></td>
				<td>	<input class='hidden' id="IDApptTimeStamp" name="IDApptTimeStamp" type="text"
						value="<?php global $FormApptTimeStamp; echo $FormApptTimeStamp; ?>" />
					<input class='hidden' id="IDApptView" name="IDApptView" type="text"
						value="<?php global $ApptView; echo $ApptView; ?>" />
					<input class='hidden' id="IDApptDate" name="IDApptDate" type="text" />
					<input class='hidden' id="IDApptTime" name="IDApptTime" type="text" />
					<input class='hidden' id="IDApptSlot" name="IDApptSlot" type="text" />
					<input class='hidden' id="IDApptOldSlot" name="IDApptOldSlot" type="text" />
					<input class='hidden' id="IDApptStatus" name="IDApptStatus" type="text" />
					<input class='hidden' id="IDApptLoc" name="IDApptLoc" type="text" />
					<input class='hidden' id="IDApptSlotDates" name="IDApptSlotDates" type="text" />
					<input class='hidden' id="IDApptSlotDays" name="IDApptSlotDays" type="text" />
					<input class='hidden' id="IDApptSlotLoc" name="IDApptSlotLoc" type="text" />
					<input class='hidden' id="IDApptSlotSets" name="IDApptSlotSets" type="text" />
					<input class='hidden' id="IDApptReason" name="IDApptReason" type="text" />
					<input class='hidden' id="IDApptCustSite" name="IDApptCustSite" type="text"
						value="<?php global $FormApptCustSite; echo $FormApptCustSite; ?>" />
				</td></tr>
		</table>
		</form>
	</div>

	<div id="ApptHistoryBox">
		<b>Contact history:</b>
		<br /><button id="StatusConfirmed" class="statusbutton shortbutton" onclick="Add_Comment(this.id)">Confirmed</button>
        	<button id="StatusBusy" class="statusbutton shortbutton" onclick="Add_Comment(this.id)">Busy</button>
        	<button id="StatusNoAnswer" class="statusbutton shortbutton" onclick="Add_Comment(this.id)">No answer</button>
		<button id="StatusMessageM" class="statusbutton longbutton" onclick="Add_Comment(this.id)">Left msg on machine</button>
		<button id="StatusMessageW" class="statusbutton longbutton" onclick="Add_Comment(this.id)">Left msg with ...</button>
		<button id="StatusEmail" class="statusbutton longbutton" onclick="Add_Comment(this.id)">Responded by email</button>
		<button id="StatusQandA" class="statusbutton longbutton" onclick="Add_Comment(this.id)">Answered questions</button>
		<button id="StatusDocs" class="statusbutton longbutton" onclick="Add_Comment(this.id)">Sent documents</button>
		<button id="StatusNNTF" class="statusbutton longbutton" onclick="Add_Comment(this.id)">No need to file</button>
		<br />Other: <input id="StatusOther" type="text"
			title="For any other contact description";
			onchange="Add_Comment(this.id);"
			onkeyup="Test_For_Enter(this.id, event)" />
		<br /><textarea id="StatusText" readonly="readonly"></textarea>
	</div>
	</div> <!-- ApptDataDiv -->

	</td></tr><tr><td>

	<div id="ApptButtonBox">

		<?php
		Appt_Box_Buttons();
		?>
	</div>
	</td></tr></table>
</div>

<div id="SlotBox" style="visibility: <?php global $ShowSlotBox; echo (($ShowSlotBox) ? "visible" : "hidden"); ?>">
	<center>
	<div id="SBTitle">
		Appointment Slot Configurator
		<div id="SlotBoxClose" onclick="Hide_SlotBox()">&times;</div>
	</div>
	
	<table>	<tr><td style="text-align: right">Task:</td>
		<td><select id="SlotAction" value=""  
			onchange="Manage_Appointments(false, false);"
			style="width:100%">
			<option value="SlotAdd"
				title="Add new slots to what is already there">
				Add new appointment slots</option>
			<option value="SlotClear"
				title="Erase names but keep the time and date slots. Useful to remove test data.">
				Clear names from existing slots</option>
			<option value="SlotRemove"
				title="Remove appointment slots that have not been used or reserved">
				Remove unused appointment slots</option>
			<option value="SlotRemoveDeleted"
				title="Clears and removes all in the deleted list">
				Clear and remove the deleted list</option>
			<option value="SlotDeleteCallback"
				title="Moves all records in the callback list to the deleted list">
				Delete all in the callback list</option>
			<option value="SlotRemoveDateRange"
				title="Clears and removes all appointment structure and date from the start date through the stop date for this site">
				Remove all appointment data between specified dates</option>
			<option value="SlotRemoveAll"
				title="Clears and removes all appointment structure and data for this site">
				Start over - remove all appointment data</option>
			<option value="SlotClone"
				title="Copies appointment structure from last year and removes all data earlier than the starting date, including callback and deleted entries">
				Clone schedule from previous year</option>
			<option value="SlotAdd1" style="visibility:hidden; font-size:1px"></option>
			<option value="SlotRemove1" style="visibility:hidden; font-size:1px"></option>
		</select></td></tr>

		<tr><td style="text-align: right">Location:</td>
		<td><select id="SlotLocation" onchange="List_Site_Patterns();"
			style="width: 100%;">
			<!-- <option value="0" prompt="For what site?"></option> -->
			<?php
			List_Locations($_SESSION["UserHome"]);
			?>
		</select></td></tr>

		<tr id="SBPatternOption">
		<td style="text-align: right;"><input type="checkbox"
			onchange="SBPatterns.style.display=(this.checked)?'block':'none';"/></td>
		<td>	Choose a saved appointment pattern:
			<span id="SBPatterns">
					<?php
					List_Patterns();
					?>
				<br /><button id="SBPatternDelete" onclick="Save_Pattern(this.id);">
					Click to delete this appointment pattern</button>
			</span></td></tr>
	</table>

	<span id="SBDates" onchange="Manage_Appointments(false, false);">
		<hr />
		<?php global $TodayDate;
		echo "From&nbsp;<input id='SlotStart' type='date' value='$TodayDate' onchange='Slot_Date_Change();'/>&nbsp;through&nbsp;<input id='SlotStop' type='date' value='$TodayDate' />\n";
		?>
	</span>

	<span id="SBDays" onchange="Manage_Appointments(false, false);">
		on every:
		<table id="SlotBoxTable"><tr>
			<td>Sun
			<br /><input id="SlotSun" type="checkbox" /></td>
			<td>Mon
			<br /><input id="SlotMon" type="checkbox" /></td>
			<td>Tue
			<br /><input id="SlotTue" type="checkbox" /></td>
			<td>Wed
			<br /><input id="SlotWed" type="checkbox" /></td>
			<td>Thu
			<br /><input id="SlotThu" type="checkbox" /></td>
			<td>Fri
			<br /><input id="SlotFri" type="checkbox" /></td>
			<td>Sat
			<br /><input id="SlotSat" type="checkbox" /></td>
			</tr>
		</table>
	</span>

	<span id="SBSlots" onchange="Manage_Appointments(false, false);">
		<br />Number of slots: <input id="SlotCount1" class="slotnum" type="number" />
			at <input id="SlotTime1" class="slottime" type="time" />
			with <input id="SlotRes1" class="slotnum" type="number" /> reserved.
		<br />Number of slots: <input id="SlotCount2" class="slotnum" type="number" />
			at <input id="SlotTime2" class="slottime" type="time" />
			with <input id="SlotRes2" class="slotnum" type="number" /> reserved.
		<br />Number of slots: <input id="SlotCount3" class="slotnum" type="number" />
			at <input id="SlotTime3" class="slottime" type="time" />
			with <input id="SlotRes3" class="slotnum" type="number" /> reserved.
		<br />Number of slots: <input id="SlotCount4" class="slotnum" type="number" />
			at <input id="SlotTime4" class="slottime" type="time" />
			with <input id="SlotRes4" class="slotnum" type="number" /> reserved.
		<br />Number of slots: <input id="SlotCount5" class="slotnum" type="number" />
			at <input id="SlotTime5" class="slottime" type="time" />
			with <input id="SlotRes5" class="slotnum" type="number" /> reserved.
		<br />Number of slots: <input id="SlotCount6" class="slotnum" type="number" />
			at <input id="SlotTime6" class="slottime" type="time" />
			with <input id="SlotRes6" class="slotnum" type="number" /> reserved.
		<br />Number of slots: <input id="SlotCount7" class="slotnum" type="number" />
			at <input id="SlotTime7" class="slottime" type="time" />
			with <input id="SlotRes7" class="slotnum" type="number" /> reserved.
		<br />Number of slots: <input id="SlotCount8" class="slotnum" type="number" />
			at <input id="SlotTime8" class="slottime" type="time" />
			with <input id="SlotRes8" class="slotnum" type="number" /> reserved.
	</span>
	<hr />
	<div id="SBPatternSaveButtons">
		Save this pattern for future use before you &quot;Go&quot;? 
		<button id="SBPatternSave" onclick="Save_Pattern(this.id);">Save</button>
		<button id="SBPatternSaveAs" onclick="Save_Pattern(this.id);">Save as</button>
		<span id="SBPatternResponse"></span>
	</div>
	<div>
		<button id="SBGoButton" onclick="Manage_Appointments(true, false);"
			title="Perform the action (if the button is green)">Go</button>
		<button id="SBCancelButton" onclick="Hide_SlotBox();"
			title="Close this window">Cancel</button>
		<button id="SBResetButton" onclick="Fill_Pattern();"
			title="Restore the appointment pattern back to the one chosen above">Reset</button>
	<span id="SBWaitMessage" class="blink">WORKING!</span>
	</div>
	</center>
</div>

<div id="ExportBox">
	<div id="EBTitle">
		Export Site Data
		<div id="ExportBoxClose" onclick="Hide_ExportBox()">&times;</div>
	</div>

	<div id="EBheader" style="display: fixed;">
		&#x2611; SELECT TO EXPORT <button onclick="EB_Select_All();">Select All</button>
		<br /><span style="padding-left: 5em;">SKIP RECORD IF NULL &#x2611;</span>
	</div>

	<hr />

	<div id="EBlist" onchange="Make_EB_List();">
		<div class="EBmoveable"
			draggable="true"
			ondragend="EB_dragEnd();"
			ondragover="EB_dragOver(event);"
			ondragstart="EB_dragStart(event);">
			<span class="EBselect"><input type="checkbox" /> LOCATION</span>
			<span class="EBomit hidden"><input type="checkbox"></span>
		</div>
		<div class="EBmoveable"
			draggable="true"
			ondragend="EB_dragEnd();"
			ondragover="EB_dragOver(event);"
			ondragstart="EB_dragStart(event);">
			<span class="EBselect"><input type="checkbox"> DATE</span>
			<span class="EBomit"><input type="checkbox" title="Skip if on Callback or Deleted Lists"></span>
		</div>
		<div class="EBmoveable"
			draggable="true"
			ondragend="EB_dragEnd();"
			ondragover="EB_dragOver(event);"
			ondragstart="EB_dragStart(event);">
			<span class="EBselect"><input type="checkbox"> TIME</span>
			<span class="EBomit"><input type="checkbox" title="Skip if on Callback or Deleted Lists"></span>
		</div>
		<div class="EBmoveable"
			draggable="true"
			ondragend="EB_dragEnd();"
			ondragover="EB_dragOver(event);"
			ondragstart="EB_dragStart(event);">
			<span class="EBselect"><input type="checkbox"> NAME</span>
			<span class="EBomit hidden"><input type="checkbox"></span>
		</div>
		<div class="EBmoveable"
			draggable="true"
			ondragend="EB_dragEnd();"
			ondragover="EB_dragOver(event);"
			ondragstart="EB_dragStart(event);">
			<span class="EBselect"><input type="checkbox"> PHONE</span>
			<span class="EBomit"><input type="checkbox" title="Skip if phone is 000-000-0000";></span>
		</div>
		<div class="EBmoveable"
			draggable="true"
			ondragend="EB_dragEnd();"
			ondragover="EB_dragOver(event);"
			ondragstart="EB_dragStart(event);">
			<span class="EBselect"><input type="checkbox"> EMAIL</span>
			<span class="EBomit"><input type="checkbox" title="Skip if no email"></span>
		</div>
		<div class="EBmoveable"
			draggable="true"
			ondragend="EB_dragEnd();"
			ondragover="EB_dragOver(event);"
			ondragstart="EB_dragStart(event);">
			<span class="EBselect"><input type="checkbox"> LAST REMINDER</span>
			<span class="EBomit"><input type="checkbox" title="Skip if there WAS a reminder"></span>
		</div>
		<div class="EBmoveable"
			draggable="true"
			ondragend="EB_dragEnd();"
			ondragover="EB_dragOver(event);"
			ondragstart="EB_dragStart(event);">
			<span class="EBselect"><input type="checkbox"> TAGS</span>
			<span class="EBomit"><input type="checkbox" title="Skip if there are no tags"></span>
		</div>
		<div class="EBmoveable"
			draggable="true"
			ondragend="EB_dragEnd();"
			ondragover="EB_dragOver(event);"
			ondragstart="EB_dragStart(event);">
			<span class="EBselect"><input type="checkbox"> FOOTNOTES</span>
			<span class="EBomit"><input type="checkbox" title="Skip if there are no footnotes"></span>
		</div>
		<div class="EBmoveable"
			draggable="true"
			ondragend="EB_dragEnd();"
			ondragover="EB_dragOver(event);"
			ondragstart="EB_dragStart(event);">
			<span class="EBselect"><input type="checkbox"> INFO</span>
			<span class="EBomit"><input type="checkbox" title="Skip if there is no additional information"></span>
		</div>
		<div class="EBmoveable"
			draggable="true"
			ondragend="EB_dragEnd();"
			ondragover="EB_dragOver(event);"
			ondragstart="EB_dragStart(event);">
			<span class="EBselect"><input type="checkbox"> APPT BY INTERNET</span>
			<span class="EBomit"><input type="checkbox" title="Skip if not self-registerd by internet"></span>
		</div>
		<div class="EBmoveable"
			draggable="true"
			ondragend="EB_dragEnd();"
			ondragover="EB_dragOver(event);"
			ondragstart="EB_dragStart(event);">
			<span class="EBselect"><input type="checkbox"> CONTACT HISTORY</span>
			<span class="EBomit"><input type="checkbox" title="Skip if there WAS contact history"></span>
		</div>
	</div> <!-- EBlist -->
	<div>
		<center>
		<button id="EBPrint" onclick="Print_Excel();">Export</button>
		<button id="EBCancel" onclick="Hide_ExportBox();">Cancel</button>
		</center>
	</div>
</div>

<div id="SearchBox">
	<b>Appointment Search</b>
	<hr />
	<div>
		Search by:
			<span id="FindByTagsButton" onclick="Show_SearchBox(this.id);">
				<input id="FindByTags" type="radio" name="FindOption" <?php if ($FormApptNo == "FindByTags") echo "checked"; ?> />
				Tag</span>
			<span id="FindByPhoneButton" onclick="Show_SearchBox(this.id);">
				<input id="FindByPhone" type="radio" name="FindOption" <?php if ($FormApptNo == "FindByPhone") echo "checked"; ?> />
				Phone</span>
			<span id="FindByNameButton" onclick="Show_SearchBox(this.id);">
				<input id="FindByName" type="radio" name="FindOption" <?php if ($FormApptNo == "FindByName") echo "checked"; ?> />
				Name</span>
			<span id="FindByEmailButton" onclick="Show_SearchBox(this.id);">
				<input id="FindByEmail" type="radio" name="FindOption" <?php if ($FormApptNo == "FindByEmail") echo "checked"; ?> />
				Email</span>
	</div>
	<br />
	<input id="FindByVal" type="text" onkeyup="Test_For_Enter(this.id, event)" value="<?php global $FindByVal; echo $FindByVal;?>" />
	<hr />
	<div id="SearchResults">
	<?php Show_Search() ?>
	</div>
	<button id="FindButton" onclick="Find_Appointment();">Search</button>
	<button id="HideTest" onclick="SearchBox.style.visibility='hidden';">Close</button>
</div>

</div> <!-- Slots -->
</div> <!-- Main -->

<div id="MoveBox" style="visibility:<?php global $MoveBox; echo $MoveBox; ?>;">
	<div id="CopyBoxMessage">
		<b>You are copying the appointment for <span id="CopyName">TITLE</span></b>
		<hr />
		Click on the calendar date and time slot you want the appointment moved to,
		<br />or click on "Cancel" to leave this client where they are without a new copy.
		<br /><button class="MoveCancel" onclick="ApptOp('Cancel')">Cancel</button>
	</div>
	<div id="MoveBoxMessage">
		<b>You are moving the appointment for <span id="MoveName">TITLE</span></b>
		<hr />
		Click on the calendar date and time slot you want the appointment moved to,
		<br />or click on "Cancel" to leave this client where they are.
		<br /><button class="MoveCancel" onclick="ApptOp('Cancel')">Cancel</button>
	</div>
</div>

<div id="WaitBox" style="display: none;">
	<img id="gato" src="Images/gatoloading.gif">
</div>

<?php
	global $ApptView, $OtherAppts, $FormApptPhone, $FormApptEmail, $FormApptName;
	if (($OtherAppts) AND ($ApptView != "ViewUser")) {
		echo "<script>\n";
		$showName = _Show_Chars($FormApptName, "text");
		$msgtext = "Other appointments with name " . $showName;
		if ($FormApptPhone) $msgtext .= "\\nor phone number $FormApptPhone";
		if ($FormApptEmail) $msgtext .= "\\nor email " . _Show_Chars($FormApptEmail, "text");
		// Prepare OtherAppts contents by escaping backslashes and quotes
		$OtherAppts = str_replace("%5C", "%5C%5C", $OtherAppts); // \
		$OtherAppts = str_replace("%22", "%5C%22", $OtherAppts); // "
		$msgtext .= ":\\n\\n" . _Show_Chars($OtherAppts, "text");
		echo "alert(\"" . $msgtext . "\");";
		echo "</script>\n";
	}
?>

</body>
</html>
