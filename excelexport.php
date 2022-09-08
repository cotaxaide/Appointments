<?php
// Contributed by Mark Doernhoefer
require_once("opendb.php");
require_once("environment.php");

/* Initialize the arrays that hold the spreadsheet rows for the main report,
 * the list of callbacks, and the list of deleted appointments. Also, set up
 * an array that will capture the fields that will be skipped as per user
 * selections.
 */
$excelRow	= array(array());
$excelRowIndex	= 0;
$excelRowType	= array();
$skipList	= array();
$colList = '';

// Polyfill for PHP str_contains function that will be available in PHP 8.0
if (!function_exists('str_contains')) {
	function str_contains($haystack, $needle) {
		return $needle !== '' && mb_strpos($haystack, $needle) !== false;
	}
}

$locationList = $_GET['UserSiteList'];  //list of sites
$fieldList =	$_GET['ExportList'];	//List of Excel columns

/* Trim the extraneous | from the location list and create the comma
 * separated site index list that will be used later in the SQL query
 * Explode the location list and field list into arrays. Initialize the 
 * spreadsheet separators for the callback list and deleted list.
 */
$locationList  = trim ($locationList, "|");
$locationList = str_replace("|", ",", $locationList);
$fieldList	 = explode("|",$fieldList);

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
			$colList .= "appt_emailsent, ";
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
		case "APPT BY INTERNET":
			$colList .= "appt_by, ";
			break;
		default:
			break;
	}
}

/* The following database query will return an associated array of 
 * just what we need for the Excel file associated by their database
 * column names.
 */
$query = "SELECT * FROM taxappt_appts";
$query .= " LEFT JOIN taxappt_sites";
$query .= " ON taxappt_appts.appt_location = taxappt_sites.site_index"; 
$query .= " WHERE site_index IN ($locationList)";
$query .= " ORDER BY site_name, appt_date, appt_time";

$appointments = mysqli_query($dbcon, $query);

while($row = mysqli_fetch_array($appointments, MYSQLI_ASSOC)) {
	$site_name	  = (!empty($row["site_name"]))	     ? $row["site_name"]      : "";
	$appt_name	  = (!empty($row["appt_name"]))	     ? $row["appt_name"]      : "";
	$appt_time	  = (!empty($row["appt_time"]))	     ? $row["appt_time"]      : "";
	$appt_date	  = (!empty($row["appt_date"]))	     ? $row["appt_date"]      : "";
	$appt_phone	  = (!empty($row["appt_phone"]))     ? $row["appt_phone"]     : "";
	$appt_email	  = (!empty($row["appt_email"]))     ? $row["appt_email"]     : "";
	$appt_emailsent   = (!empty($row["appt_emailsent"])) ? $row["appt_emailsent"] : "";
	$appt_tags	  = (!empty($row["appt_tags"]))	     ? $row["appt_tags"]      : "";
	$appt_need	  = (!empty($row["appt_need"]))	     ? $row["appt_need"]      : "";
	$appt_info	  = (!empty($row["appt_info"]))	     ? $row["appt_info"]      : "";
	$appt_status	  = (!empty($row["appt_status"]))    ? $row["appt_status"]    : "";
	$appt_by	  = (!empty($row["appt_by"]))	     ? $row["appt_by"]	      : "";
	$appt_type	  = (!empty($row["appt_type"]))	     ? $row["appt_type"]      : "";
	$appt_wait	  = (!empty($row["appt_wait"]))	     ? $row["appt_wait"]      :  0;

	/* Identify those records that are callbacks or deleted and set the callback
 	* or deleted flag. These flags will be used to place the record in the
 	* proper spreadsheet section.
 	*/	
	if ($appt_date == $NullDate) {
		if ($appt_type == 'D') {
			$deleted = TRUE;
			$excelRowType[$excelRowIndex] = "Deleted";
	 	}
	 	else {
			$callback = TRUE;
			$excelRowType[$excelRowIndex] = "Callback";
		}
	}
	else {
		$deleted = $callback = FALSE;
		$excelRowType[$excelRowIndex] = "Appointment";
	}

	/* There is now a full record available and we need to decide which, if any
 	* spreadsheet to write the record to. $fieldList is in the correct 
 	* order for the columns to appear on the spreadsheet, so walk through 
 	* that array and write the columns. We also apply the skip list rules as 
 	* appropriate. The records are written to the callback, deleted, or main 
 	* sections of the spreadsheet.
 	*/
	foreach ($fieldList as $col) {
		switch ($col) {
		case "LOCATION":
			if (in_array("LOCATION", $fieldList)) {
				$excelRow[$excelRowIndex]["LOCATION"] = _Show_Chars($site_name, 'text');
			}
			break;

		case "NAME":
			if (in_array("NAME", $fieldList)) {
				// Always skip blank or reserved fields
				if (($appt_name == "")
				    || str_contains($appt_name, "R E S E R V E D")
				    || str_contains($appt_name, "Reserved for #")) {
					$excelRowType[$excelRowIndex] = "SKIP";
				}
			$excelRow[$excelRowIndex]["NAME"] = _Show_Chars($appt_name, 'text');
			}
			break;

		case "TIME":				   
			if (in_array("TIME", $fieldList)) {
				if (in_array("TIME", $skipList) && ($callback || $deleted)) {
					$excelRowType[$excelRowIndex] = "SKIP";
				}
				$excelRow[$excelRowIndex]["TIME"] = ($deleted || $callback) ?  '' : $appt_time ;
			}
			break;

		case "DATE":
			if (in_array("DATE", $fieldList)) {
				if (in_array("DATE", $skipList) && ($callback || $deleted)) {
					$excelRowType[$excelRowIndex] = "SKIP";
				}
			$excelRow[$excelRowIndex]["DATE"] = ($deleted || $callback) ? $excelRowType[$excelRowIndex] : $appt_date ;
			}
			break;

		case "PHONE":
			if (in_array("PHONE", $fieldList)) {
				if (in_array("PHONE", $skipList) && ($appt_phone == "000-000-0000")) {
					$excelRowType[$excelRowIndex] = "SKIP";
				}
				$excelRow[$excelRowIndex]["PHONE"] = $appt_phone;
			}
			break;

		case "EMAIL":
			if (in_array("EMAIL", $fieldList)) {
				if (in_array("EMAIL", $skipList) && ($appt_email == '')) {
					$excelRowType[$excelRowIndex] = "SKIP";
				}
				$excelRow[$excelRowIndex]["EMAIL"] = $appt_email;
			}
			break;

		case "LAST REMINDER":
			if (in_array("LAST REMINDER", $fieldList)) {
				if ($appt_emailsent == $NullDate) $appt_emailsent = '';
				if (in_array("LAST REMINDER", $skipList) && ($appt_emailsent != '')) {
					$excelRowType[$excelRowIndex] = "SKIP";
				}
				$excelRow[$excelRowIndex]["LAST REMINDER"] = _Show_Chars($appt_emailsent, 'text');
			}
			break;

		case "TAGS":
			if (in_array("TAGS", $fieldList)) {
				if (in_array("TAGS", $skipList) && $appt_tags == '') {
					$excelRowType[$excelRowIndex] = "SKIP";
				}
				$excelRow[$excelRowIndex]["TAGS"] = _Show_Chars($appt_tags, 'text');
			}
			break;

		case "FOOTNOTES":
			if (in_array("FOOTNOTES", $fieldList)) {
				if (in_array("FOOTNOTES", $skipList) && $appt_need == '') {
					$excelRowType[$excelRowIndex] = "SKIP";
				}
				$excelRow[$excelRowIndex]["FOOTNOTES"] = _Show_Chars($appt_need, 'text');
			}
			break;

		case "INFO":
			if (in_array("INFO", $fieldList)) {
				if (in_array("INFO", $skipList) && $appt_info == '') {
					$excelRowType[$excelRowIndex] = "SKIP";
				}
				$excelRow[$excelRowIndex]["INFO"] = _Show_Chars($appt_info, 'text');
			}
			break;

		case "CONTACT HISTORY":
			if (in_array("CONTACT HISTORY", $fieldList)) {
				if (in_array("CONTACT HISTORY", $skipList) && $appt_status != '') {
					$excelRowType[$excelRowIndex] = "SKIP";
				}
				$excelRow[$excelRowIndex]["CONTACT HISTORY"] = _Show_Chars($appt_status, 'text');
			}
			break;

		case "APPT BY INTERNET":
			$apptByInet = ((substr($appt_status, -6) == '(USER)') || (substr($appt_status, -7) == '(USER.)')) ? 'yes' : '' ;
				if (in_array("APPT BY INTERNET", $fieldList)) {
					if (in_array("APPT BY INTERNET", $skipList) && $apptByInet == '') {
				}
				$excelRow[$excelRowIndex]["APPT BY INTERNET"] = $apptByInet;
			}
			break;

			default:
				break;
		} //switch ($col) 
	} // foreach ($fieldList as $col)

	// Increment the row index
	$excelRowIndex++;   

} // while($row = mysqli_fetch_array($appointments, MYSQLI_ASSOC))


// Set the output filename and MIME type for an Excel CSV file.
$filename = date('Ymd') . "_appts" . ".csv";
header('Content-type: application/ms-excel');
header('Content-Disposition: attachment; filename='.$filename);

/* Open the csv output file and write the header row to the file followed
 * by the spreadsheet rows contained in the corresponding arrays.
 */
$fp = fopen("php://output", "w");
fputcsv($fp, $fieldList, ",","\"");
for ($j = 0; $j < $excelRowIndex; $j++) {
	if ($excelRowType[$j] == "Appointment") fputcsv($fp, $excelRow[$j], ",","\"");
}
for ($j = 0; $j < $excelRowIndex; $j++) {
	if ($excelRowType[$j] == "Callback") fputcsv($fp, $excelRow[$j], ",","\"");
}
for ($j = 0; $j < $excelRowIndex; $j++) {
	if ($excelRowType[$j] == "Deleted") fputcsv($fp, $excelRow[$j], ",","\"");
}
fclose($fp);
mysqli_close($dbcon);
exit;
?>
