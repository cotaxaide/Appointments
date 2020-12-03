<?PHP
//Version 5.02c
//	Remove excess callback records

// Set up environment
require "environment.php";

// Delete records
$query = "DELETE FROM $APPT_TABLE";
$query .= " WHERE `appt_date` = '00-00-0000' AND `appt_time` = '00:00:00' AND `appt_name` = ''"; 
$return = mysqli_query($dbcon, $query);
if ($_SESSION['TRACE']) error_log("FIXDB1: SYSTEM, fixdb1.php ran.");
echo "database was cleaned successfully.";
exit;
?>
