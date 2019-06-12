// Common routines
// Version 5.00
//
// _Verify_Phone
// _Verify_Email
// _Verify_Date
// _Verify_Time
//
// -----------------------------------------------------------------
function _Verify_Phone (phone_no, do_alert, require_10dig) {
//	phone_no	phone number to be verified and prettyfied
//	do_alert	T/F, create an alert if not valid
//	require_10dig	T/F, require 10-digits (toll optional)
//
//	Returns array (code, new_phone, error message)
//		code:	0 = valid phone number
//			1 = empty phone number
//			2 = invalid phone number
//		new_phone has digits with dashes inserted
//		error message to be displayed
// -----------------------------------------------------------------
	var return_array = [];
	var toll = 0;
	var extension = "";
	var num_dig = 0;
	var error_message = "";
	if (do_alert === "undefined") do_alert = false;
	if (require_10dig === "undefined") require_10dig = false;
	return_array[0] = 2;
	return_array[1] = phone_no;

	// is there an extension number?
	var ext_start = phone_no.indexOf(" ext");
	if (ext_start == -1) ext_start = phone_no.indexOf(" x");
	if (ext_start >= 0) {
		extension = phone_no.substr(ext_start);
		phone_no = phone_no.substr(0, ext_start);
	}

	// strip dashes and spaces
	phone_no = phone_no.replace(/\D/g,"");

	// is there a toll digit
	toll = (phone_no.charAt(0) == "1") ? 1 : 0;
	num_dig = phone_no.length - toll;

	// test for the correct number of digits
	if (num_dig === 0) {
		return_array[0] = 1;
	}
	else if ((num_dig == 7) && (! require_10dig)) {
		phone_no = ((toll == 1) ? "1-" : "")
			+ phone_no.substr(0 + toll,3) + "-"
			+ phone_no.substr(3 + toll);
		return_array[0] = 0;
	}
	else if (num_dig == 10) {
		phone_no = ((toll == 1) ? "1-" : "")
			+ phone_no.substr(0 + toll,3) + "-"
			+ phone_no.substr(3 + toll,3) + "-"
			+ phone_no.substr(6 + toll);
		return_array[0] = 0;
	}
	else {
		return_array[0] = 2;
		error_message = "Please enter the phone number as a ";
		if (! require_10dig) error_message += "7- or ";
	       	error_message += "10-digit number with an optional preceding \"1\".";
		if (do_alert) alert(error_message);
		return_array[2] = error_message;
	}
	return_array[1] = phone_no + extension;
	return return_array;
}

// -----------------------------------------------------------------
function _Verify_Email (v_email, do_alert) {
//	phone_no	phone number to be verified and prettyfied
//	do_alert	T/F, create an alert if not valid
//
//	Returns array (code, new_email, error message)
//		code:	0 = valid email
//			1 = empty email
//			2 = invalid email
//		new_email has leading and trailing blanks removed
// -----------------------------------------------------------------
	var return_array = [];
	var error_message = "";
	if (do_alert === "undefined") do_alert = false;
	return_array[0] = 2;

	v_email = v_email.trim(); // remove spaces
	return_array[1] = v_email;
	patt = /^[\w\.\-\&]+\@[\w\.\-\&]+\.[\w\.\-\&]+$/;
	if (v_email === "") return_array[0] = 1; 
	else if (patt.test(v_email)) return_array[0] = 0;
	else {
		error_message = "The entered email does not appear to be a valid format.";
		if (do_alert) alert(error_message);
		return_array[2] = error_message;
	}
	return return_array;
}

// -----------------------------------------------------------------
function _Verify_Date(v_date, do_alert) {
//	v_date		Date to be tested, 20dd-dd-dd format
//	do_alert	T/F, create an alert if not valid
//
//	Returns array (code, new_date, error message)
//		code:	0 = valid date
//			1 = empty date
//			2 = invalid date
// -----------------------------------------------------------------
	var return_array = [];
	var error_message = "";
	if (do_alert === "undefined") do_alert = false;
	return_array[0] = 2;
	return_array[1] = v_date;
	patt = /^20\d\d-\d\d-\d\d/;
	if (v_date === "") return_array[0] = 1;
	else if (patt.test(v_date)) return_array[0] = 0;
	else {
		error_message = "Please enter the date in YYYY-MM-DD format or use the drop-down list if your browser supports one.";
		if (Do_alert) alert(error_message);
	}
	return_array[2] = error_message;
	return return_array;
}
	
// -----------------------------------------------------------------
function _Verify_Time(v_time, do_alert) {
//	v_time		Time to be tested
//	do_alert	T/F, create an alert if not valid
//
//	Returns array (code, new_time, error message)
//		code:	0 = valid time
//			1 = empty time
//			2 = invalid time
//		error message
// -----------------------------------------------------------------
	var return_array = [];
	var error_message = "";
	if (do_alert === "undefined") do_alert = false;
	return_array[0] = 2;
	return_array[1] = v_time;
	patt = /^\d?\d{1}\:\d{2}/;
	if (v_time === "") return_array[0] = 1;
	else if (patt.test(v_time)) return_array[0] = 0;
	else if (v_time === "24:00") {
		return_array[0] = 0;
		return_array[1] = "00:00";
	}
	else {
		error_message = "Please enter the time in 24 hour format (2 pm = 14:00) or use the drop-down list if your browser supports one.";
		if (Do_alert) alert(error_message);
	}
	return_array[2] = error_message;
	return return_array;
}
