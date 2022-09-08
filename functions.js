// Common routines version history:
// Version 8.00
// 	Changed email verification test pattern
// 	Added _Verify_Name
// Version 7.00
// 	Added _Get_Position
// 	Added _Clean_Chars
// 	Added _Show_Chars
// 	Added _Set_Cookie
// 	Added _Read_Cookies
// Version 5.00
//
// ----------------------------------------------------------------
// Functions included
// 	_Verify_Phone
// 	_Verify_Name
// 	_Verify_Email
// 	_Verify_Date
// 	_Verify_Time
// 	_Get_Position
// 	_Clean_Chars
// 	_Show_Chars
// 	_Set_Cookie
// 	_Read_Cookies
// ----------------------------------------------------------------
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
function _Verify_Name (v_name, do_alert) {
//	v_name 		name to be verified
//	do_alert	T/F, create an alert if not valid
//
//	Returns array (code, new_name, error message)
//		code:	0 = valid name
//			1 = empty name
//			2 = invalid name
//		new_name has leading and trailing blanks removed
// -----------------------------------------------------------------
	var return_array = [];
	var error_message = "";
	if (do_alert === "undefined") do_alert = false;
	return_array[0] = 2;

	v_name = v_name.trim(); // remove spaces
	return_array[1] = v_name;
	patt = /^[a-zA-Z\s\-\'\.\"]+$/;
	if (v_name === "") return_array[0] = 1; 
	else if (patt.test(v_name)) return_array[0] = 0;
	else {
		error_message = "The entered name cannot contain numbers or unusual characters.";
		if (do_alert) alert(error_message);
		return_array[2] = error_message;
	}
	return return_array;
}

// -----------------------------------------------------------------
function _Verify_Email (v_email, do_alert) {
//	v_email		email to be verified and prettyfied
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
	patt = /^\w+([\.-]?\w+)*@\w+([\.-]?\w+)*(\.\w{2,})+$/;
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

//----------------------------------------------------------------------------------------
function _Get_Position(thisElement) {
// This function gets coordinates for the element
//
//	Returns array (xPosition, yPosition) of the element
//----------------------------------------------------------------------------------------

	var xPosition = 0;
	var yPosition = 0;
 
	while (thisElement) {
		if (thisElement.tagName === "BODY") {
			// deal with browser quirks with body/window/document and page scroll
			var xScrollPos = thisElement.scrollLeft || document.documentElement.scrollLeft;
			var yScrollPos = thisElement.scrollTop || document.documentElement.scrollTop;
 
			xPosition += (thisElement.offsetLeft - xScrollPos + thisElement.clientLeft);
			yPosition += (thisElement.offsetTop - yScrollPos + thisElement.clientTop);
			}
		else {
			xPosition += (thisElement.offsetLeft - thisElement.scrollLeft + thisElement.clientLeft);
			yPosition += (thisElement.offsetTop - thisElement.scrollTop + thisElement.clientTop);
		}

		thisElement = thisElement.offsetParent;
	}

	return { x: xPosition, y: yPosition };
}

//===========================================================================================
function _Clean_Chars(sval) {
//	Removes troublesome charaters from an input text entry
//===========================================================================================
	if (sval === "") return ("");
	sval = sval.replace(/&/g, "%26");
	sval = sval.replace(/'/g, "%27");
	sval = sval.replace(/"/g, "%22");
	sval = sval.replace(/\n/g, "%0A");
	sval = sval.replace(/\\(.)/g, "%5C$1");
	return (sval);
}

//===========================================================================================
function _Show_Chars(sval, format) {
//	Restores characters removed by _Clean_Chars()
//	format options:
//		html	replaces coded characters with HTML codes
//		text	replaces coded characters with ASCII equivalents
//===========================================================================================
	if (sval === "") return ("");
	switch (format) {
	case "html":
		sval = sval.replace(/%26/g, "&amp;");
		sval = sval.replace(/%27/g, "&apos;");
		sval = sval.replace(/%22/g, "&quot;");
		sval = sval.replace(/%0A/g, "<br />");
		sval = sval.replace(/%5C(.)/g, "\\$1");
		break;
	case "text":
		sval = sval.replace(/%26/g, "&");
		sval = sval.replace(/%27/g, "'");
		sval = sval.replace(/%22/g, '"');
		sval = sval.replace(/%%/g, "%0A");
		sval = sval.replace(/%0A/g, "\n");
		sval = sval.replace(/%5C(.)/g, "\\$1");
		sval = sval.replace(/&laquo;/g, String.fromCharCode(171));
		sval = sval.replace(/&raquo;/g, String.fromCharCode(187));
		break;
	}
	return(sval);
}

//===========================================================================================
function _Set_Cookie(cName, cValue) {
//===========================================================================================
	// Is the RememberMe box checked or unchecked?
	if (cValue) {
		var d = new Date(); // set cookie expiration date
			d.setTime(d.getTime() + (2.2*366*24*60*60*1000)); // 2.2 years
		var CookieExpires = ";expires=" + d.toUTCString() + ";";
	}
	else {
		CookieExpires = ";expires=Thu, 01 Jan 1970 00:00:00 GMT;"; // unchecked
	}

	document.cookie = cName + "=" + cValue + CookieExpires + " path=/;";
}

//===========================================================================================
function _Read_Cookies() {
//	returns an array with the cookie name as index
//===========================================================================================
	var result = [];
	var cookie = document.cookie;
	var cookieList = cookie.split(";");
	for (cookieIdx = 0; cookieIdx < cookieList.length; cookieIdx++) {
		var cn = cookieList[cookieIdx];
		while (cn.charAt(0)==' ') cn = cn.substring(1);
		var cookieVar = cn.split("=");
		result[cookieVar[0]] = cookieVar[1];
	}
	return result;
}
