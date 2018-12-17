<?PHP
//Version 4.01

// Set up environment
require "environment.php";
//ini_set('display_errors', '1');

$Errormessage = "";
// If the UserIndex has not been set as a session variable, the user needs to sign in
if (@$_SESSION["UserIndex"] == 0) {
	header('Location: index.php'); // prevents direct access to this page (must sign in first).
	exit;
}

// Connect to database
// sets $USER_TABLE, $ACCESS_TABLE, $APPT_TABLE, $SITE_TABLE
require "opendb.php";

// file name for download
$LocationList = explode("|",@$_SESSION["UserSiteList"]);
$filename = date('Ymd') . "_appts" . ".xls";
header("Content-Disposition: attachment; filename=\"$filename\"");
header("Content-Type: application/vnd.ms-excel");

$NullDate = "0000-00-00";
$CBList = "";
$DelList = "";
$RESERVED = "&laquo; R E S E R V E D &raquo;";

// Print column headers
echo "LOCATION\tDATE\tTIME\tNAME\tEMAIL\tPHONE\tNEED\tSTATUS\tWAIT #\tSITE #\n";

$query = "SELECT * FROM $APPT_TABLE";
$query .= " LEFT JOIN $SITE_TABLE";
$query .= " ON $APPT_TABLE.appt_location = $SITE_TABLE.site_index"; 
$query .= " ORDER BY `site_name`, `appt_date`, `appt_time`, `appt_wait`";
$appointments = mysqli_query($dbcon, $query);
$OldLocation = 0;
while($row = mysqli_fetch_array($appointments)) {
	$Location = $row["appt_location"];
	$Site = $row["site_name"];
	if ($Location != $OldLocation) {
		echo $CBList;
		$CBList = "";
		echo $DelList;
		$DelList = "";
		$OldLocation = $Location;
	}
	if (in_array($Location, $LocationList)) {
		$Appt = $row["appt_no"];
		$Date = $row["appt_date"];
		$Time = substr($row["appt_time"],0,5);
		$Name = str_replace("!","'",htmlspecialchars_decode($row["appt_name"]));
		$Email = $row["appt_email"];
		$Phone = $row["appt_phone"];
		$Type = $row["appt_type"];
		$Need = str_replace("%%","; ",$row["appt_need"]);
		$Status = str_replace("%%","; ",$row["appt_status"]);

		if (($Name != "") and ($Name != $RESERVED)) {
			if ($Date == $NullDate) {
				if ($Type == "D") {
					$DelList .= "$Site\tDeleted\tList\t$Name\t$Email\t$Phone\t$Need\t$Status\t\t$Location\n";
				}
				else {
					$Wait = $row["appt_wait"];
					$CBList .= "$Site\tCallback\tList\t$Name\t$Email\t$Phone\t$Need\t$Status\t$Wait\t$Location\n";
				}
			}
			else {
				echo "$Site\t$Date\t$Time\t$Name\t$Email\t$Phone\t$Need\t$Status\t\t$Location\n";
			}
		}
	}
}
echo $CBList;
echo $DelList;
$appointments = []; // release memory
exit;
?>
