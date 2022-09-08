<?php
//===========================================================================================
function _Clean_Chars($sval) {
//	Removes troublesome charaters from an input text entry
//===========================================================================================
	if ($sval == "") return ("");
	$sval = str_replace("&", "%26", $sval);
	$sval = str_replace("'", "%27", $sval);
	$sval = str_replace("\"", "%22", $sval);
	$sval = str_replace("\n", "%0A", $sval);
	$sval = str_replace("\\", "%5C", $sval);
	return ($sval);
}

//===========================================================================================
function _Show_Chars($sval, $format) {
//	Restores characters removed by _Clean_Chars()
//	$format options:
//		html	replaces coded characters with HTML codes
//		text	replaces coded characters with ASCII equivalents
//===========================================================================================
	if ($sval == "") return ("");
	switch ($format) {
	case "html":
		$sval = str_replace("%26", "&amp;", $sval);
		$sval = str_replace("%27", "&apos;", $sval);
		$sval = str_replace("%22", '&quot;', $sval);
		$sval = str_replace("%0A", "<br />", $sval);
		$sval = str_replace("%5C", "\\", $sval);
		break;
	case "text":
		$sval = str_replace("%26", "&", $sval);
		$sval = str_replace("%27", "'", $sval);
		$sval = str_replace("%22", '"', $sval);
		$sval = str_replace("%0A", "\n", $sval);
		$sval = str_replace("%5C", "\\", $sval);
		break;
	}
	return($sval);
}
?>
