<?php
// ---------------------------- VERSION HISTORY -------------------------------
//File Version 9.00
//  Corrected APPT BY and APPT BY INTERNET columns
//  Changed APPT BY column to APPT BY WHO
//  Changed empty columns to a blank vs null to hide overflow from previous column
//  Added ability to get report for a single date
//File Version 8.03b
//  Added _Show_Chars to NAME
//File Version 8.03a
//  Corrected bug with record type printing to date field
//  Removed dividers between Appointment, Callback, and Deleted to 
//     retain spreadsheet integrity
//  Placing Date or Time on the skip list will cause Callback and
//     Deleted to not be included in the spreadsheet 
//  Removed debug code
//
ini_set("error_log", "appt_error_log");
require_once("opendb.php");
require_once("functions.php");

/* Initialize the arrays that hold the spreadsheet rows for the main report,
 * the list of callbacks, and the list of deleted appointments. Also, set up
 * an array that will capture the fields that will be skipped as per user
 * selections.
 */
$excelRow         = array(array());
$excelRowIndex    = 0;
$callbackRow      = array(array());
$callbackRowIndex = 0;
$deletedRow       = array(array());
$deletedRowIndex  = 0;
$skipList         = array();
$colList = '';
$NullDate = "1900-01-01";
$oneDate = "";

// Polyfill for PHP str_contains function that will be available in PHP 8.0
if (! function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}

$locationList = $_GET['UserSiteList'];  //list of sites
$fieldList =    $_GET['ExportList'];    //List of Excel columns

/* Trim the extraneous | from the location list and create the comma
 * separated site index list that will be used later in the SQL query
 * Explode the location list and field list into arrays.
 */
$locationList  = trim($locationList, "|");
$locationList = str_replace("|", ",", $locationList);

// Remove the ONEDATE field if present and save the date for later filtering
if (($oneDatePos = strpos($fieldList, "|ONEDATE:")) !== FALSE) { // is it found?
	$oneDate = substr($fieldList, $oneDatePos + 9);
	$fieldList = substr($fieldList, 0, $oneDatePos);
}
$fieldList = explode("|", $fieldList);

/* Parse the list of column names selected by the user and associate those
 * columns to fields in the taxappt_appts and taxappt_sites tables. In order
 * to do that we have to remove the asterisks from the front of the column names.
 * As we remove the asterisks, save those columns in a skip list so we can
 * apply the skip list rules to rows that should not be shown.
 */
$i=0;
foreach ($fieldList as $mysqlField) {
    $pos = strpos($mysqlField, '*');
    if ($pos !== false) {
        $mysqlField = trim($mysqlField,'*');
        $fieldList[$i] = $mysqlField;
        $skipList[] = $mysqlField;
    }
    $i++;
    switch ($mysqlField) {
        case "LOCATION":
            $colList .= "site_name, ";
            break;
        case "NAME":
            $colList .= "appt_name, ";
            break;
        case "TIME":
            $colList .= "appt_time, ";
            break;
        case "DATE":
            $colList .= "appt_date, ";
            break;
        case "PHONE":
            $colList .= "appt_phone, ";
            break;
        case "EMAIL":
            $colList .= "appt_email, ";
            break;
        case "LAST REMINDER":
            $colList .= "appt_change, ";
            break;
        case "TAGS":
            $colList .= "appt_tags, ";
            break;
        case "FOOTNOTES":
            $colList .= "appt_need, ";
            break;
        case "INFO":
            $colList .= "appt_info, ";
            break;
        case "CONTACT HISTORY":
            $colList .= "appt_status, ";
            break;
        case "APPT BY WHO":
            $colList .= "appt_by, ";
            break;
        case "APPT BY INTERNET":
            $colList .= "appt_inet, ";
            break;
        default:
            break;
    }
}

/* The field list for the query will consist of field names 
 * separated by ", ". We also need the appointment type and the appointment
 * wait (so we can tell if it's a callback) so we leave the extra comma
 * and append appt_type and appt_wait to the query.
 */
$colList = $colList .= "appt_type, appt_wait";

/* The following database query will return an associated array of 
 * just what we need for the Excel file associated by their database
 * column names.
 */
$query = "SELECT * FROM taxappt_appts";
$query .= " LEFT JOIN taxappt_sites";
$query .= " ON taxappt_appts.appt_location = taxappt_sites.site_index"; 
$query .= " WHERE site_index IN ($locationList) AND appt_name != '' ";
if ($oneDate != "") $query .= " AND appt_date = '$oneDate'";
$query .= " ORDER BY site_name, appt_date, appt_time";

$appointments = mysqli_query($dbcon, $query);

$callback    = FALSE;
$deleted     = FALSE;
$skipThisRow = FALSE;
while($row = mysqli_fetch_assoc($appointments)) {
    $site_name   = $row["site_name"]   ?? " ";
    $appt_name   = $row["appt_name"]   ?? " ";
    $appt_time   = $row["appt_time"]   ?? " ";
    $appt_date   = $row["appt_date"]   ?? " ";
    $appt_phone  = $row["appt_phone"]  ?? " ";
    $appt_email  = $row["appt_email"]  ?? " ";
    $appt_change = $row["appt_change"] ?? " ";
    $appt_tags   = $row["appt_tags"]   ?? " ";
    $appt_need   = $row["appt_need"]   ?? " ";
    $appt_info   = $row["appt_info"]   ?? " ";
    $appt_status = $row["appt_status"] ?? " ";
    $appt_by     = $row["appt_by"]     ?? " ";
    $appt_type   = $row["appt_type"]   ?? " ";
    $appt_wait   = $row["appt_wait"]   ??   0;
    $appt_inet   = (str_contains($row["appt_status"],"(USER")) ? "Inet" : " ";

/* Identify those records that are callbacks or deleted and set the callback
 * or deleted flag. These flags will be used to place the record in the
 * proper spreadsheet section.
 */    
    if ($appt_date == $NullDate) {
    	if (strpos($appt_type, 'A') !== FALSE) continue; // Skip archived records
    	if (strpos($appt_type, 'D') !== FALSE) $deleted = TRUE;
	else $callback = TRUE;
    }

/* There is now a full record available and we need to decide which, if any
 * spreadsheet section to write the record to. $fieldList is in the correct 
 * order for the columns to appear on the spreadsheet, so walk through 
 * that array and write the columns. We also apply the skip list rules as 
 * appropriate. The records are written to the callback, deleted, or main 
 * sections of the spreadsheet.
 */
    foreach ($fieldList as $col) {
        switch ($col) {
            case "LOCATION":
                if (in_array("LOCATION", $fieldList)) {
                    if ($callback) {
                        $callbackRow[$callbackRowIndex]["LOCATION"] = $site_name;
                    } else if ($deleted) {
                        $deletedRow[$deletedRowIndex]["LOCATION"] = $site_name;
                    } else {        
                        $excelRow[$excelRowIndex]["LOCATION"] = $site_name;
                    }
                }
                break;
            case "NAME":
                if (in_array("NAME", $fieldList)) {
		    $appt_name = _Show_Chars($appt_name, "text");
                    if (str_contains($appt_name, "R E S E R V E D")) {
                        $skipThisRow = TRUE;                        
		    } else if ($callback) {
                        $callbackRow[$callbackRowIndex]["NAME"] = $appt_name; 
                    } else if ($deleted) {
                        $deletedRow[$deletedRowIndex]["NAME"] = $appt_name;
                    } else {
                        $excelRow[$excelRowIndex]["NAME"] = $appt_name;
                    }
                }
                break;
            case "TIME":                   
                if (in_array("TIME", $fieldList)) {
                    if ($callback) {
                        $callbackRow[$callbackRowIndex]["TIME"] = ' ';
                    } else if ($deleted) {
                        $deletedRow[$deletedRowIndex]["TIME"] = ' ';
                    } else {
                        $excelRow[$excelRowIndex]["TIME"] = $appt_time;
                    }
                }
                break;
            case "DATE":
                if (in_array("DATE", $fieldList)) {
                    if ($callback) {
                        $callbackRow[$callbackRowIndex]["DATE"] = ' ';
                    } else if ($deleted) {
                        $deletedRow[$deletedRowIndex]["DATE"] = ' ';
                    } else {
                        $excelRow[$excelRowIndex]["DATE"] = $appt_date;
                    }
                }
                break;
            case "PHONE":
                if (in_array("PHONE", $fieldList)) {
                    if ($callback) {
                        $callbackRow[$callbackRowIndex]["PHONE"] = $appt_phone;
                    } else if ($deleted) {
                        $deletedRow[$deletedRowIndex]["PHONE"] = $appt_phone;
                    } else if (in_array("PHONE", $skipList) && $appt_phone == "000-000-0000") {
                        $skipThisRow = TRUE;
                    } else {    
                        $excelRow[$excelRowIndex]["PHONE"] = $appt_phone;
                    }
                }
                break;
            case "EMAIL":
                if (in_array("EMAIL", $fieldList)) {
                    if ($callback) {
                        $callbackRow[$callbackRowIndex]["EMAIL"] = $appt_email;
                    } else if ($deleted) {
                        $deletedRow[$deletedRowIndex]["EMAIL"] = $appt_email;
                    } else if (in_array("EMAIL", $skipList) && $appt_email == ' ') {
                        $skipThisRow = TRUE;
                    } else {    
                        $excelRow[$excelRowIndex]["EMAIL"] = $appt_email;
                    }
                }
                break;
            case "LAST REMINDER":
                if (in_array("LAST REMINDER", $fieldList)) {
                    if ($callback) {
                        $callbackRow[$callbackRowIndex]["LAST REMINDER"] = _Show_Chars($appt_change, 'text');
                    } else if ($deleted) {
                        $deletedRow[$deletedRowIndex]["LAST REMINDER"] = _Show_Chars($appt_change, 'text');
                    } else if (in_array("LAST REMINDER", $skipList) && $appt_change != ' ') {
                        $skipThisRow = TRUE;
                        } else {    
                        $excelRow[$excelRowIndex]["LAST REMINDER"] = _Show_Chars($appt_change, 'text');
                    }
                }
                break;
            case "TAGS":
                if (in_array("TAGS", $fieldList)) {
                    if ($callback) {
                        $callbackRow[$callbackRowIndex]["TAGS"] = _Show_Chars($appt_tags, 'text');
                    } else if ($deleted) {
                        $deletedRow[$deletedRowIndex]["TAGS"] = _Show_Chars($appt_tags, 'text');
                    } else if (in_array("TAGS", $skipList) && $appt_tags == ' ') {
                        $skipThisRow = TRUE;
                    } else {    
                        $excelRow[$excelRowIndex]["TAGS"] = _Show_Chars($appt_tags, 'text');
                    }
                }
                break;
            case "FOOTNOTES":
                if (in_array("FOOTNOTES", $fieldList)) {
                    if ($callback) {
                        $callbackRow[$callbackRowIndex]["FOOTNOTES"] = _Show_Chars($appt_need, 'text');
                    } else if ($deleted) {
                        $deletedRow[$deletedRowIndex]["FOOTNOTES"] = _Show_Chars($appt_need, 'text');
                    } else if (in_array("FOOTNOTES", $skipList) && $appt_need == ' ') {
                        $skipThisRow = TRUE;
                    } else {    
                        $excelRow[$excelRowIndex]["FOOTNOTES"] = _Show_Chars($appt_need, 'text');
                    }
                }
                break;
            case "INFO":
                if (in_array("INFO", $fieldList)) {
                    if ($callback) {
                        $callbackRow[$callbackRowIndex]["INFO"] = _Show_Chars($appt_info, 'text');
                    } else if ($deleted) {
                        $deletedRow[$deletedRowIndex]["INFO"] = _Show_Chars($appt_info, 'text');
                    } else if (in_array("INFO", $skipList) && $appt_info == ' ') {
                        $skipThisRow = TRUE;
                    } else {    
                        $excelRow[$excelRowIndex]["INFO"] = _Show_Chars($appt_info, 'text');
                    }
                }
                break;
            case "CONTACT HISTORY":
                if (in_array("CONTACT HISTORY", $fieldList)) {
                    if ($callback) {
                        $callbackRow[$callbackRowIndex]["CONTACT HISTORY"] = _Show_Chars($appt_status, 'text');
                    } else if ($deleted) {
                        $deletedRow[$deletedRowIndex]["CONTACT HISTORY"] = _Show_Chars($appt_status, 'text');
                    } else if (in_array("CONTACT HISTORY", $skipList) && $appt_status != ' ') {
                        $skipThisRow = TRUE;
                    } else {    
                        $excelRow[$excelRowIndex]["CONTACT HISTORY"] = _Show_Chars($appt_status, 'text');
                    }
                }
                break;
            case "APPT BY WHO":
                if (in_array("APPT BY WHO", $fieldList)) {
                    if ($callback) {
                        $callbackRow[$callbackRowIndex]["APPT BY WHO"] = _Show_Chars($appt_by, 'text');
                    } else if ($deleted) {
                        $deletedRow[$deletedRowIndex]["APPT BY WHO"] = _Show_Chars($appt_by, 'text');
                    } else if (in_array("APPT BY WHO", $skipList) && $appt_by == ' ') {
                        $skipThisRow = TRUE;
                    } else {    
                        $excelRow[$excelRowIndex]["APPT BY WHO"] = _Show_Chars($appt_by, 'text');
                    }
                }
                break;
            case "APPT BY INTERNET":
                if (in_array("APPT BY INTERNET", $fieldList)) {
                    if ($callback) {
                        $callbackRow[$callbackRowIndex]["APPT BY INTERNET"] = $appt_inet;
                    } else if ($deleted) {
                        $deletedRow[$deletedRowIndex]["APPT BY INTERNET"] = $appt_inet;
                    } else if (in_array("APPT BY INTERNET", $skipList) && $appt_inet == ' ') {
                        $skipThisRow = TRUE;
                    } else {    
                        $excelRow[$excelRowIndex]["APPT BY INTERNET"] = $appt_inet;
                    }
                }
                break;
            default:
                break;
        } //switch ($col) 
    } // foreach ($fieldList as $col)
    if ($callback) {
        $callbackRowIndex++;
        $callback = FALSE;
    } else if ($deleted) {
        $deletedRowIndex++;
        $deleted = FALSE;        
    } else if ($skipThisRow) {           
        unset ($excelRow[$excelRowIndex]);
        $skipThisRow = FALSE;      
    } else {
        $excelRowIndex++;   
    }
} // while($row = mysqli_fetch_assoc($appointments))

// Set the output filename and MIME type for an Excel CSV file.
$filename = date('Ymd') . "_appts" . ".csv";
header('Content-type: application/ms-excel');
header('Content-Disposition: attachment; filename='. $filename);

/* Open the csv output file and write the header row to the file followed
 * by the spreadsheet rows contained in the corresponding arrays.
 * Add a "TYPE" column to indicate the section to which the row belongs.
 */
$fp = fopen("php://output", "w");
array_unshift($fieldList, "TYPE");
fputcsv($fp, $fieldList, ",","\"");
foreach ($excelRow as $spreadsheetRow) {
    array_unshift($spreadsheetRow, "Appointment");
    fputcsv($fp, $spreadsheetRow, ",","\"");
}
if ($oneDate == "") {
    if (!(in_array("DATE", $skipList) || in_array("TIME", $skipList))){
        foreach ($callbackRow as $spreadsheetRow2) {
            array_unshift($spreadsheetRow2, "Callback");
            fputcsv($fp, $spreadsheetRow2, ",","\"");
        }
        foreach ($deletedRow as $spreadsheetRow3) {
            array_unshift($spreadsheetRow3, "Deleted");
            fputcsv($fp, $spreadsheetRow3, ",","\"");
        }
    }
}
fclose($fp);
mysqli_close($dbcon);
?>
