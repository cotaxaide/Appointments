<?php
// Version 4.01

// Set up environment
if (! is_dir("appt_session_dir")) mkdir("appt_session_dir");
if (session_id() == "") {
	ini_set("session.save_path", "appt_session_dir");
	ini_set("error_log", "appt_error_log");
	session_start();

	// Move images into Images directory
	if (! is_dir("Images")) mkdir("Images");
	$files = scandir(".");
	foreach ($files as $file) {
		if (stripos($file, ".png") OR stripos($file, ".gif")) {
			if (copy($file, "Images/" . $file)) unlink($file); 
		}
	}
}
?>
