<?PHP
//Version 5.00
//ini_set('display_errors', '1');

// Set up environment
require "environment.php";

$Errormessage = "";

// get site information
$query = "SELECT * FROM $SITE_TABLE";
$sites = mysqli_query($dbcon, $query);
while($row = mysqli_fetch_array($sites)) {
	$siteIndex = "S" . $row["site_index"];
	$siteName[$siteIndex] = $row["site_name"]; 
	$siteContact[$siteIndex] = $row["site_contact"]; 

	$msg = $row["site_message"]; 
	if (substr($msg, 0, 4) == "NONE") $msg = "";
	$siteMessage[$siteIndex] = $msg;

	$days = $row["site_reminder"];
	$sd = "";
	if ($days) {
		$sd = strtotime("+" . $row["site_reminder"] . " days", time());
	}
	$siteReminder[$siteIndex] = $sd;
	$siteLastRem[$siteIndex] = $row["site_lastrem"];

	$sa = $row["site_address"]; 
	$sa = explode("|", $row["site_address"]);
	$siteAddress[$siteIndex] = $sa[0];
	$siteCity[$siteIndex] = $sa[1];
	$siteState[$siteIndex] = $sa[2];
	$siteZip[$siteIndex] = $sa[3];
	$sitePhone[$siteIndex] = $sa[4];
	$siteEmail[$siteIndex] = $sa[5];
	$siteWeb[$siteIndex] = $sa[6];
}

// Get each appointment and see if an email needs to be sent
$query = "SELECT * FROM $APPT_TABLE";
$appointments = mysqli_query($dbcon, $query);
while($row = mysqli_fetch_array($appointments)) {
	$apptEmail = $row["appt_email"];
	if ($apptEmail == "") continue; // no email to send to
	$apptDate = $row["appt_date"];
	if ($apptDate == $NullDate) continue; // on callback or deleted list
	$apptDate = strtotime($apptDate);
	$siteIndex = "S" . $row["appt_location"];
	$graceDate = strtotime("-" . $siteLastRem[$siteIndex] . " days");
	if ($siteMessage[$siteIndex] == "") continue; // site messaging not enabled
	if ($siteReminder[$siteIndex] == "") continue; // site reminder not enabled
	if ($apptDate >= $siteReminder[$siteIndex]) continue; // not time to send yet
	$apptSent = strtotime($row["appt_emailsent"]);
	if ($apptSent > $graceDate) continue; // already sent recently

	// OK to send email
	$apptStatus = $row["appt_status"];
	$apptIndex = $row["appt_no"];
	$apptDate = date("D, M j", $apptDate);
	$apptTime = date("g:i a", strtotime($row["appt_time"]));
	$apptName = str_replace("!", "'", htmlspecialchars_decode($row["appt_name"]));
	$apptName = str_replace("&amp;", "&", $apptName);

	$to = $apptEmail;

	$from = (isset($siteEmail[$siteIndex]) AND ($siteEmail[$siteIndex] != "")) ? $siteEmail[$siteIndex] : $_SESSION['SystemEmail'];
	if ($from == "") $from = "no-reply@tax-aide-reservations.no-email";
	$from = htmlspecialchars_decode($from);

	$headers = "From: " . $siteName[$siteIndex] . " Tax-Aide <" . $from . ">";
	if ($_SESSION['TRACE']) {
		error_log("REMIND: SYSTEM, Email to ". $apptEmail . " " . $headers);
	}
	
	$subject = "Your Tax-Aide appointment";

	$message = htmlspecialchars_decode($siteMessage[$siteIndex]);
	$message = str_replace("&amp;", "&", $message);
	$message = str_replace("%%", "\n", $message);
	$message = str_replace("[TPNAME]", $apptName, $message);
	$message = str_replace("[TIME]", $apptTime, $message);
	$message = str_replace("[DATE]", $apptDate, $message);
	$message = str_replace("[SITENAME]", $siteName[$siteIndex], $message);
	$message = str_replace("[ADDRESS]", $siteAddress[$siteIndex], $message);
	$message = str_replace("[CITY]", $siteCity[$siteIndex], $message);
	$message = str_replace("[STATE]", $siteState[$siteIndex], $message);
	$message = str_replace("[ZIP]", $siteZip[$siteIndex], $message);
	$message = str_replace("[PHONE]", $sitePhone[$siteIndex], $message);
	$message = str_replace("[EMAIL]", $siteEmail[$siteIndex], $message);
	$message = str_replace("[WEBSITE]", $siteWeb[$siteIndex], $message);
	$message = str_replace("[STATESITE]", $_SESSION['SystemURL'], $message);
	$message = str_replace("[CONTACT]", $siteContact[$siteIndex], $message);
	//$message = wordwrap($message, 70, "\r\n");

	$success = mail($to,$subject,$message,$headers);
	if (! $success) {
		$Errormessage .= "Not able to send email to $apptName at $apptEmail.";
		$emerr = error_get_last()['message'];
		if ($_SESSION['TRACE']) error_log("REMIND: " . $apptName . " at " . $apptEmail . ", Email error: ". $emerr);
	}
	else {
		$statusTime = date("m/d_h:ia");
		$apptStatus = "$statusTime: Email sent to $apptEmail (SYSTEM)%%$apptStatus";
		$query = "UPDATE $APPT_TABLE SET";
		$query .= "  `appt_status` = '$apptStatus'";
		$query .= ", `appt_emailsent` = '$TodayDate'";
		$query .= " WHERE `appt_no` = $apptIndex";
		mysqli_query($dbcon, $query);
	}
}

// update reminder run time
$sysremTime = date("m/d \a\\t h:i a");
if ($_SESSION['TRACE']) error_log("REMIND: SYSTEM, reminders ran at $sysremTime");
$query = "UPDATE $SYSTEM_TABLE SET";
$query .= " `system_reminders` = '$sysremTime'";
mysqli_query($dbcon, $query);

exit;
?>
