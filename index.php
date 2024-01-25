<?php
// Version 9.07
// 	Added to check junk email if requesting a temporary password
// Version 9.04
// 	Corrected error message when logging in
// 	Added a failed login message
// Version 9.02
// 	Changes to support Daily View heartbeat AJAX updating
// Version 9.00
// 	Changed temporary PW from character string to 6-digit verification code
// 	Changed user SESSION variables to be all in one array
// Version 8.03
//	Added ExcelExport to User database table (vs cookie)
// Version 8.01
// 	Better checking and error handling of new user sign-up input fields
// Version 7.01
// 	Now using function js and php files
// 	Uncommented session clearing statements to make "logged in" name go away
// Version 5.02a
// 	Prevent restartup without password on same PC
// 	Do not save password on PC with cookie, only userid
// 	Change Remember me box default to unchecked unless remembered
// 	Clear Remember me box if login name changes from remembered
// Version 5.01

if (! file_exists("opendb.php")) {
	require "environment.php";
	exit;
}

// Set up environment
require "environment.php";

// Set to true for debugging
$_SESSION["DEBUG"] = $DEBUG = false;

// Variables used globally:
$Errormessage = "";
$Usermessage = "";
$LocationList = [];
$LocationInternet = [];
$FormLoginEmail = "";
$UserIdentified = (isset($_SESSION["User"]) AND ($_SESSION["User"]["user_index"] > 0));
$UserIndex = $UserIdentified ? $_SESSION["User"]["user_index"] : "";
$UserName = $UserIdentified ? $_SESSION["User"]["user_name"] : "";
$UserFirst = $UserIdentified ? $_SESSION["User"]["user_first"] : "";
$UserLast = $UserIdentified ? $_SESSION["User"]["user_last"] : "";
$UserEmail = $UserIdentified ? $_SESSION["User"]["user_email"] : "";
$UserPhone = $UserIdentified ? $_SESSION["User"]["user_phone"] : "";
$UserHome = $UserIdentified ? $_SESSION["User"]["user_home"] : "";
$UserOptions = $UserIdentified ? $_SESSION["User"]["user_options"] : "";
$UserSiteShow = $UserIdentified ? $_SESSION["User"]["user_sitelist"] : "";
$UserExcelExport = $UserIdentified ? $_SESSION["User"]["user_excel_export"] : "";
$UserPass = "";
$UserPassPart = [];
$Dagger = chr(134);
$Alert = "";
$gotophp = "index.php";
$isAdministrator = ($UserOptions == "A") ? true : false;

// Get system greeting, notice and info from the system table
$sysgreeting = "";
$sysnotice = "";
$query = "SELECT * FROM $SYSTEM_TABLE";
$sys = mysqli_query($dbcon, $query);
while ($row = mysqli_fetch_array($sys)) {
	$_SESSION["SystemGreeting"] = $sysgreeting = $row['system_greeting'];
	$_SESSION["SystemNotice"] = $sysnotice = $row['system_notice'];
	$_SESSION["SystemURL"] = $sysURL = htmlspecialchars_decode($row['system_url'] ?? '');
	$_SESSION["SystemVersion"] = $row['system_version'];
	$_SESSION["SystemHeartbeat"] = $row['system_heartbeat'];
	$_SESSION["SystemAttach"] = $row['system_attach'];
	$_SESSION["TRACE"] = $row['system_trace'];

	// replace shortcode in greeting and notice
	if ($sysURL) {
		$replacement = "<a href=\"" . $sysURL . "\" target=\"_blank\">" . $sysURL . "</a>";
		$sysgreeting = str_replace("[STATESITE]", $replacement, $sysgreeting);
		$sysnotice = str_replace("[STATESITE]", $replacement, $sysnotice);
	}
}

// Get a list of locations which are accepting on-line appointments
$j = 0;
$LocationList[0] = 0;
$query = "SELECT * FROM $SITE_TABLE";
$query .= " WHERE `site_inet` <> ''";
$query .= " ORDER BY `site_name`";
$locs = mysqli_query($dbcon, $query);
while ($row = mysqli_fetch_array($locs)) {
	if ($row['site_closed'] >= $TodayDate) {
		$LocationList[0] = ++$j;
		$LocationList[$j] = htmlspecialchars_decode($row["site_name"] ?? '');
		$LocationInternet[$j] = $row["site_inet"];
		$LocationOpen[$j] = $row["site_open"];
		$LocationClosed[$j] = $row["site_closed"];
		if ($LocationOpen[$j] > $TodayDate) {
			$d = date("M j, Y", strtotime($LocationOpen[$j]));
			$LocationList[$j] .=  " (starting on $d)";
		}
	}
}

//Get POST variables if action requested
if ($_SERVER["REQUEST_METHOD"] == "POST") {
	@$FormLoginAction = htmlspecialchars(stripslashes(trim($_POST["LoginAction"])));
	@$FormLoginName = htmlspecialchars(stripslashes(trim($_POST["LoginName"])));
	@$FormLoginEmail = htmlspecialchars(stripslashes(strtolower(trim($_POST["LoginEmail"]))));
	@$FormLoginPhone = htmlspecialchars(stripslashes(trim($_POST["LoginPhone"])));
	@$FormLoginFirst = htmlspecialchars(stripslashes(trim($_POST["LoginFirst"])));
	@$FormLoginLast = htmlspecialchars(stripslashes(trim($_POST["LoginLast"])));
	@$FormLoginPass = htmlspecialchars(stripslashes(trim($_POST["LoginPass"])));
	@$FormLoginHome = htmlspecialchars(stripslashes(trim($_POST["LoginHome"])));

	$log_id = ($UserName) ? $UserName : $FormLoginEmail;
	if ($_SESSION["TRACE"]) error_log("INDEX: " . $log_id . ", " . $FormLoginAction); 

	// sign out before signing in
	switch ($FormLoginAction) {
		case "LogOut":
			$UserOptions = 0;
			$UserIdentified = false;
			$FormLoginEmail = "";
			$Errormessage = "";
			$Usermessage = "";
			//session_unset();
			//session_destroy();
			// no break
		case "NewUser":
		case "Login":
		case "GetPW":
			$_SESSION["User"]["user_index"] = 0;
			$UserIndex = 0;
			break;
		default:
	}

	// Process request
	if ($_SESSION["User"]["user_index"] > 0) { // i.e. Already logged in
		$UserIndex = $_SESSION["User"]["user_index"];
		$UserOptions = $_SESSION["User"]["user_options"];

		switch ($FormLoginAction) {

			case "ChangeEmail":

				// See if email is already in use by other than current user
				$emailTest = Unique_Email($UserIndex, $FormLoginEmail);

				if ($emailTest["count"] == -1) {
					$Errormessage .= "Your email was not changed.";
					$Errormessage .= "<br />This email is already being used by "; 
					$Errormessage .= $emailTest['user_first'] . " " . $emailTest['user_last'];
					break;
				}
				else {
					$query = "UPDATE `$USER_TABLE`";
					$query .= " SET `user_email` = '$FormLoginEmail'";
					$query .= " WHERE `user_index` = '$UserIndex'";
					mysqli_query($dbcon, $query);

					$idoemail = $_SESSION["User"]["user_email"];
					$query = "UPDATE `$APPT_TABLE`";
					$query .= " SET `appt_email` = '$FormLoginEmail'";
					$query .= " WHERE `appt_email` = '$idoemail'";
					mysqli_query($dbcon, $query);
					$_SESSION["User"]["user_email"] = $FormLoginEmail;
					$Usermessage .= "Your email has been changed as requested <br />from $idoemail <br />to $FormLoginEmail.";
					break;
				}

			case "ChangePhone":
				// Add the new phone number - proper format is assumed
				$query = "UPDATE `$USER_TABLE`";
				$query .= " SET `user_phone` = '$FormLoginPhone'";
				$query .= " WHERE `user_index` = '$UserIndex'";
				mysqli_query($dbcon, $query);
				$idophone = $_SESSION["User"]["user_phone"];
				if ($idophone != "") {
					$query = "UPDATE `$APPT_TABLE`";
					$query .= " SET `appt_phone` = '$FormLoginPhone'";
					$query .= " WHERE `appt_phone` = '$idophone'";
					mysqli_query($dbcon, $query);
				}
				$_SESSION["User"]["user_phone"] = $FormLoginPhone;
				$Usermessage .= "Your phone number has been changed<br />";
				if ($idophone !=  "") $Usermessage .= " from $idophone";
				$Usermessage .= " to $FormLoginPhone.";
				break;
 		
			case "ChangePW":
				if ($FormLoginName != "") {
					$query = "UPDATE `$USER_TABLE`";
					$query .= " SET `user_pass` = '$FormLoginName'";
					$query .= " WHERE `user_index` = $UserIndex";
					$query .= " LIMIT 1";
					mysqli_query($dbcon, $query);
					$Usermessage .= "Your password has been changed as requested.";
				}
				break;
 		}
	}

	else { // User is not signed in

		$_SESSION["User"]["user_full_name"] = "";

		// Look for a matching email
		$query = "SELECT * FROM $USER_TABLE";
		$query .= " WHERE `user_email` = '$FormLoginEmail'";
		$idq = mysqli_query($dbcon, $query);
		$count = mysqli_num_rows($idq);
		if ($count == 1) {
			while ($id = mysqli_fetch_array($idq)) {
				$_SESSION["User"] = $id;
				$UserIndex = $id['user_index'];
				$UserFirst = $id['user_first'];
				$UserLast = $id['user_last'];
				$UserName = $id['user_name'];
				$_SESSION["User"]["user_full_name"] = $UserFirst . " " . $UserLast;
				$UserOptions = $id['user_options'];
				$UserHome = ($UserOptions) ? $id['user_home'] : 0;
				$UserEmail = $id['user_email'];
				$UserPhone = $id['user_phone'];
				if ($UserHome == 0) { // is an internet user
					// set the site list to their last appointment site
					$id['user_sitelist'] = "|";
					if ($id['user_appt_site'] > 0) $id['user_sitelist'] .= $id['user_appt_site'] . "|";
				}
				$UserSiteShow = $id['user_sitelist'];
				$UserExcelExport = $id['user_excel_export'];
				$UserPass = $id['user_pass'];
			}
			if ($_SESSION["TRACE"]) error_log("INDEX: " . $UserName . ", using email " . $UserEmail); 
		}
		else {
			if ($_SESSION["TRACE"]) error_log("INDEX: Unknown, attempted login using unknown email $FormLoginEmail."); 
		}

		if (strtolower($FormLoginEmail) == strtolower($UserEmail)) {

			switch ($FormLoginAction) { 

				case "NewUser":
					$Errormessage .= "An account already exists with that email address.";
					$UserIndex = $_SESSION["User"]["user_index"] = 0;
					break;

				case "Login":
					// check password for dagger separators
					$UserPassPart = explode($Dagger, $UserPass);
					$UserPassPart[1] = $UserPassPart[1] ?? "";

					// If using the original correct password, remove the verification code.
					if ($FormLoginPass == $UserPassPart[0]) {
						$UserPass = $UserPassPart[0];
						$LoginHow = "password";
					}
					else if ($UserPassPart[1] ?? '') {
						if (($UserPassPart[2] ?? '') != $TodayDate) {
							$Errormessage .= "Your 6-digit code has expired. Please request a new one.";
							$UserPass = $UserPassPart[0];
						}
						if (! $Errormessage) {
							$Usermessage .= "Please change your password to something easier to remember.";
							$LoginHow = "verification code";
						}
					}

					// Does password match either the original or the verification code
					if (($FormLoginPass == $UserPassPart[0]) OR ($FormLoginPass == $UserPassPart[1])) {
						$gotophp = "appointment.php";
						$_SESSION["User"]["user_index"] = $UserIndex;
						$_SESSION["User"]["user_name"] = $UserName;
						$_SESSION["User"]["user_first"] = $UserFirst;
						$_SESSION["User"]["user_last"] = $UserLast;
						$_SESSION["User"]["user_full_name"] = $UserFirst . " " . $UserLast;
						$_SESSION["User"]["user_email"] = $UserEmail;
						$_SESSION["User"]["user_phone"] = $UserPhone;
						$_SESSION["User"]["user_home"] = $UserHome;
						$_SESSION["User"]["user_options"] = $UserOptions;
						$_SESSION["User"]["user_sitelist"] = $UserSiteShow;
						$_SESSION["User"]["user_excel_export"] = $UserExcelExport;
						$FormLoginAction = "";

						// Update user table time stamp and password
						$query = "UPDATE `$USER_TABLE` SET";
						$query .= " `user_lastlogin` = '$TimeStamp'";
						$query .= ", `user_pass` = '$UserPass'";
						$query .= " WHERE `user_index` = $UserIndex";
						$query .= " LIMIT 1";
						mysqli_query($dbcon, $query);
						if ($_SESSION["TRACE"]) error_log("INDEX: $UserName, logged in using $LoginHow.");
					}
					else { // No password match
						$_SESSION["User"]["user_index"] = $UserIndex = 0;
						$Errormessage .= "Your email or password is not correct.";
						if ($_SESSION["TRACE"]) error_log("INDEX: $UserName ($UserEmail) failed attempted login.");
					}
					break;

				case "GetPW":
					$str = "123456789";
					$Passcode = substr(str_shuffle($str), rand(0, strlen($str)-6), 6);
					$UserPassPart = explode($Dagger, $UserPass);
					$UserPass0 = $UserPassPart[0] . $Dagger . $Passcode . $Dagger . $TodayDate;
					$query = "UPDATE `$USER_TABLE`";
					$query .= " SET `user_pass` = '$UserPass0'";
					$query .= " WHERE `user_index` = $UserIndex";
					$query .= " LIMIT 1";
					mysqli_query($dbcon, $query);

					$to = $UserEmail;
					$subject = "Your request from the AARP reservation system";
					$from = "no-reply@tax-aide-reservations.null";
					$headers = "From: Tax-Aide Appointment Manager<$from>";
					$message = "Greetings $UserFirst $UserLast.\n\n";
					$message .= "As requested, your verification code is\n\n $Passcode\n\n";
					$message .= "The verification code is only good for today.\n\n";
					$message .= "Please change your password the next time you sign in.\n\n";
					if (substr($to,-5,5) == ".test") {
						$Alert .= "The following email would have been sent:\\n\\n" . str_replace("\n","\\n",$message);
					}
					else {
						mail($to, $subject, $message, $headers);
						if ($_SESSION["TRACE"]) {
							error_log("INDEX: " . $log_id . ", Email sent to " . $to . " " . $headers); 
						}
					}
					$Usermessage .= "You should receive an email message in the next few minutes. Use that for a new temporary password.";
					$Usermessage .= " It may go to your junk email folder, so check there too.";
					$UserIndex = $_SESSION["User"]["user_index"] = 0;
					$FormLoginAction = "LogOut";

				case "LogOut":
					break;

				default:
					$Errormessage .= "Invalid request";
					$_SESSION["User"]["user_index"] = 0;
			}
		}

		else { // Emails don't match

			switch ($FormLoginAction) { 

				case "GetPW":
					$Errormessage .= "That email is not in our system";
					break;
			}
		}

		if ($UserIndex == 0) {
			$UserName = "";
			if ($FormLoginAction == "NewUser") { // Set up a new account for a self-registering (internet) user
				$_SESSION["User"]["user_index"] = 0;

    				if ($Errormessage == "") {
					$query = "INSERT INTO `$USER_TABLE`";
					$query .= " (`user_name`,`user_email`,`user_phone`,`user_pass`,`user_last`,`user_first`,`user_home`,`user_appt_site`,`user_options`,`user_sitelist`)";
					$query .= " VALUES ('$FormLoginName','$FormLoginEmail','$FormLoginPhone','$FormLoginPass','$FormLoginLast','$FormLoginFirst',0,0,'','|')";
					$res = mysqli_query($dbcon, $query);
					if ($res) {
						$idx = mysqli_query($dbcon, "SELECT * FROM $USER_TABLE WHERE `user_email` = '$FormLoginEmail' LIMIT 1");
						$id = mysqli_fetch_array($idx);
						//$UserPass = $id1['user_pass'];
						$_SESSION["User"]["user_index"] = $UserIndex = $id['user_index'];
						$_SESSION["User"]["user_name"] = $UserName = $id['user_name'];
						$_SESSION["User"]["user_first"] = $UserFirst = $id['user_first'];
						$_SESSION["User"]["user_last"] = $UserLast = $id['user_last'];
						$_SESSION["User"]["user_full_name"] = $UserFirst . " " . $UserLast;
						$_SESSION["User"]["user_email"] = $UserEmail = $id['user_email'];
						$_SESSION["User"]["user_phone"] = $UserPhone = $id['user_phone'];
						$_SESSION["User"]["user_sitelist"] = $UserSiteShow = $id['user_sitelist'];
						$_SESSION["User"]["user_excel_export"] = $UserExcelExport = $id['user_excel_export'];
						$_SESSION["User"]["user_options"] = 0;
						$_SESSION["User"]["user_home"] = 0;
						$FormLoginAction = "";
						$gotophp = "appointment.php";
					}
					else {
						$Errormessage .= "Couldn't set up " . $FormLoginFirst . ".";
					}
    				}
			}

   	    		else {
				if (($FormLoginAction != "LogOut") and ($FormLoginAction != "GetPW") and ($Errormessage == "")) {
					$Errormessage .= "Either your email or password is incorrect.";
				}
			}
		}
	}
}

else { // Initial access with no action yet taken
	if (! $UserIdentified) {
		$UserIndex = 0;
		$UserOptions = 0;
		$UserIdentified = false;
	}
}

// -----------------------------------------------------------------------------
function Unique_Email($testId, $testEmail) {
//	testId is the id that belongs to the email
//	testEmail is the email to be tested for uniqueness
// returns and array with count and id information for later use:
//	1 = email matches the id, return user information
//	0 = no match to any id
//	-1 = email does not match the id
// -----------------------------------------------------------------------------
	global $dbcon;
	global $USER_TABLE;

	$emailTest = [];
	$emailTest["count"] = 0;
	$query = "SELECT * FROM $USER_TABLE";
	$query .= " WHERE `user_email` = '$testEmail'";
	$idq = mysqli_query($dbcon, $query);
	$count = mysqli_num_rows($idq);
	if ($count == 0) return $emailTest;
	while ($row = mysqli_fetch_array($idq)) {
		$emailTest = $row;
		if ($emailTest["user_index"] != $testId) {
			$emailTest["count"] = -1;
			return $emailTest;
		}
	}
	$emailTest["count"] = 1;
	return $emailTest;
}

?>

<!--================================================================================================================-->
<!--================================================= START OF WEB PAGE ============================================-->
<!--================================================================================================================-->
<!DOCTYPE html>

<head>
<title>AARP Appointments</title>
<meta name=description content="AARP Appointments">
<link rel="SHORTCUT ICON" href="appt.ico">
<link rel="stylesheet" href="appt.css">
<script src="functions.js"></script>
<script>
<?php
	global $DEBUG, $UserIndex, $UserOptions;
	$d = ($DEBUG) ? "true" : "false";
	echo "	var DEBUG = $d;\n";
	$uid = ($UserIndex > 0) ? $UserIndex : 0;
	echo "	var uid = $uid;\n";
	$uhm = (($UserIndex > 0) and (($UserOptions == 'A') OR ($UserOptions == 'M'))) ? "true" : "false";
	echo "	var uhm = $uhm;\n";
?>
	var c0 = ""; // email cookie
	var c1 = ""; // password cookie

	/* ******************************************************************************************************************* */
	function Initialize() {
	/* ******************************************************************************************************************* */
		LoginDiv.style.display = (DEBUG) ? "block" : "none";
		init_login.style.display = (!uid) ? "block" : "none";
		newlogin_data.style.display = (!uid) ? "block" : "none";
		init_newuser.style.display = (!uid) ? "block" : "none";
		init_getpw.style.display = (!uid) ? "block" : "none";
		init_chgpw.style.display = (uid) ? "block" : "none";
		init_chgem.style.display = (uid) ? "block" : "none";
		init_chgph.style.display = (uid) ? "block" : "none";
		init_appt.style.display = (uid) ? "block" : "none";
		init_site.style.display = (uhm) ? "block" : "none";
		init_logout.style.display = (uid) ? "block" : "none";
		Change_Option("login"); // collapse all boxes except login
		<?php
		global $Alert;
		if ($Alert != "") echo "alert('" . $Alert . "');";
		?>

		// Read login cookies if present
		var cookie = _Read_Cookies();
		if (typeof cookie["TA_Appt_0"] === "undefined") {
			LoginName0.focus();
			LoginRememberMe.checked = false;
		}
		else {
			LoginName0.value = c0 = cookie["TA_Appt_0"];
			LoginRememberMe.checked = true;
			LoginPass0.focus();
		}
	}

	/* ******************************************************************************************************************* */
	function Change_Login(newid) {
	/* ******************************************************************************************************************* */
		// Clear remember me checkbox if remembered email is changed
		LoginRememberMe.checked = (newid.toLowerCase().trim() === c0.toLowerCase().trim());
	}

	/* ******************************************************************************************************************* */
	function Change_Option(id) {
	/* ******************************************************************************************************************* */
		if (id != "login") init_error.innerHTML = "";
		else if (id != "login") init_message.innerHTML = "";
		newinit_data.style.display = (id == "newuser") ? "block" : "none"; 
		chgpw_data.style.display = (id == "chgpw") ? "block" : "none"; 
		chgem_data.style.display = (id == "chgem") ? "block" : "none"; 
		chgph_data.style.display = (id == "chgph") ? "block" : "none"; 
		getpw_data.style.display = (id == "getpw") ? "block" : "none"; 
		switch (id) {
			case "getpw": {
				LoginGetPWEmail.value = LoginName0.value; 
				break;
				}
			case "newuser": {
				LoginEmail1.value = LoginName0.value;
				break;
				}
			case "chgpw": {
				LoginPass2a.focus();
				break;
				}
			case "chgem": {
				LoginEmail3a.focus();
				break;
				}
			case "chgph": {
				LoginPhone3.focus();
				break;
				}
			case "newuser": {
				LoginFName1.focus();
				break;
				}
		}

	}

	/* ******************************************************************************************************************* */
	function Action_Request(Action) {
	/* ******************************************************************************************************************* */
		if (DEBUG) alert (Action);
		switch (Action) {
		case "Login":
			if (LoginName0.value == "" || LoginPass0.value == "") {
				alert("Please enter both email and password");
				return;
			}
			LoginForm.LoginEmail.value = LoginName0.value;
			LoginForm.LoginPass.value = LoginPass0.value;
			var cValue = (LoginRememberMe.checked) ? LoginName0.value : "" ;
			_Set_Cookie("TA_Appt_0", cValue);
			break;

		case "NewUser":
			result = _Verify_Name(LoginFName1.value, "alert");
			switch (result[0]) {
			case 0: LoginFName1.value = result[1]; break;
			case 1: alert("Please enter your first name."); return;
			case 2: return;
			}

			result = _Verify_Name(LoginLName1.value, "alert");
			switch (result[0]) {
			case 0: LoginLName1.value = result[1]; break;
			case 1: alert("Please enter your last name."); return;
			case 2: return;
			}

			result = _Verify_Email(LoginEmail1.value, "alert");
			switch (result[0]) {
			case 0: LoginEmail1.value = result[1]; break;
			case 1: alert("Please enter your email."); return;
			case 2: return;
			}

			result = _Verify_Phone(LoginPhone1.value, "alert", "10-dig");
			switch (result[0]) {
			case 0: LoginPhone1.value = result[1]; break;
			case 1: alert("Please enter your phone number."); return;
			case 2: return;
			}
				
			if ((LoginPass1a.value == "") || (LoginPass1b.value == "")) {
				alert ("Please enter your password, twice.");
				return;
			}
			if (LoginPass1a.value != LoginPass1b.value) {
				alert("Password entries don't match");
				return;
			}
				
			LoginForm.LoginPass.value = LoginPass1a.value;
			LoginForm.LoginFirst.value = _Clean_Chars(LoginFName1.value);
			LoginForm.LoginLast.value = _Clean_Chars(LoginLName1.value);
			LoginForm.LoginName.value = LoginForm.LoginFirst.value + LoginLName1.value.substr(0,1);
			LoginForm.LoginEmail.value = LoginEmail1.value;
			LoginForm.LoginPhone.value = LoginPhone1.value;
			break;

		case "GetPW":
			LoginForm.LoginEmail.value = LoginGetPWEmail.value;
			break;

		case "ChangePW":
			if (LoginPass2a.value == "") {
				alert("New password cannot be blank");
				return;
			}
			if (LoginPass2a.value != LoginPass2b.value) {
				alert("New password entries don't match");
				LoginPass2a.value = "";
				LoginPass2b.value = "";
				return;
			}
			LoginPass0.value = LoginForm.LoginName.value = LoginPass2a.value;
			break;

		case "ChangeEmail":
			if (LoginEmail3a.value == "") {
				alert("You must enter a new email");
				return;
			}
			if (LoginEmail3a.value != LoginEmail3b.value){
				alert("Your email entries must match");
				return;
			}
			results = _Verify_Email(LoginEmail3a.value, "alert");
			if (results[0]) return; // bad email
			LoginForm.LoginEmail.value = LoginEmail3a.value = results[1];
			break;

		case "ChangePhone":
			results = _Verify_Phone(LoginPhone3.value, "alert", "10-dig");
			if (results[0]) return; // bad phone
			LoginForm.LoginPhone.value = LoginPhone3.value = results[1];
			break;

		case "LogOut":
			break;

		case "CanAppt":
			LoginForm.action = "<?php global $gotophp; echo $gotophp; ?>";
			break;

		case "Site":
			$gotophp = "sitemanage.php";
			LoginForm.action = "sitemanage.php";
			break;

		case "Appt":
			$gotophp = "appointment.php";
			LoginForm.action = "appointment.php";
			break;
		} // end switch

		LoginForm.LoginAction.value = Action;
		if (DEBUG) alert(Action + " being submitted");
		LoginForm.submit();
	}
	
	//===========================================================================================
	function Test_For_Enter(id, e) {
	// id = the id of the element being checked
	// e = the event being checked for a key code
	//===========================================================================================
		if ((e.keyCode || e.charCode) == 13) { // Enter key code (charCode for old browers)
			switch (id) {
			case "LoginPass0":      Action_Request("Login");       break;
			case "LoginPass1b":     Action_Request("NewUser");     break;
			case "LoginPass2b":     Action_Request("ChangePW");    break;
			case "LoginEmail3b":    Action_Request("ChangeEmail"); break;
			case "LoginPhone3":     Action_Request("ChangePhone"); break;
			case "LoginGetPWEmail": Action_Request("GetPW");       break;
			}
		}
	}
	
	//===========================================================================================
	function Show_History() {
	//===========================================================================================
		change_history.style.display = (change_history.style.display == 'none') ? 'block' : 'none';
	}

</script>
</head>

<!-- =================================================== WEB PAGE BODY ============================================ -->
<!-- =================================================== WEB PAGE BODY ============================================ -->
<!-- =================================================== WEB PAGE BODY ============================================ -->

<body onload="Initialize()">
<div id="Main">
	<div class="page_header">
		<h1>Tax-Aide Appointments</h1>
		<?php if ((isset($_SESSION["User"]["user_full_name"]) AND ($_SESSION["User"]["user_full_name"]) != "")) {
			echo "You are signed in as " . _Show_Chars($_SESSION["User"]["user_full_name"], "text");
			}?>
	</div>
</div>

<?php	global $change_history;
	global $UserOptions;
	if (($UserOptions == "A") and isset($_SESSION["NewVersion"]) and ($_SESSION["NewVersion"] > $_SESSION["SystemVersion"])) {
		echo "\t<div id='new_version_notify'>";
		echo "\t\tA new version " . $_SESSION["NewVersion"] . " is available.<br />\n";
		echo "\t\t<button id='new_version_button' onclick=\"Show_History();\">See/hide changes</button>\n";
		echo "\t\t" . $change_history;
		echo "\t</div>";
	}
?>

<hr />

<div id="init_main">
<table id="init_table"><tr>
<td id="info_block">
	
	<div id="init_greeting">
		<?php
			if (isset($_SESSION["SystemGreeting"])) {
				$g = _Show_Chars($_SESSION["SystemGreeting"], "html");
				$g = str_replace("&quot;", '"', $g); // Change to real quotes here.
				echo $g;
			}
		?>
	</div>

	<div id="init_sitelist">
		<?php
		global $LocationList, $isAdministrator;
		if ($LocationList[0] != 0) {
			echo "<br />The following sites are accepting reservations on-line:<br />";
			echo "<ul>";
			for ($j = 1; $j <= $LocationList[0]; $j++) {
				// Hide site names beginning with "^"
				$hide = (substr($LocationList[$j],0,1) == "^");
				if (! $hide) echo "<li>" . $LocationList[$j] . "</li>";
			}
			echo "</ul>";
		}
		else {
			echo "<br />We are not accepting reservations at this time.";
		}
		?>
	</div>

	<br />Appointments at other sites are by phone &ndash; see the <a href="https://www.aarp.org/money/taxes/aarp_taxaide/locations.html" target="_blank">AARP Locator web site</a>.

</td>

<td id="init_options">
	<div id="init_option_box">
		<div id="init_login" class="init_option">
			<table><tr>
				<td><img src="Images/opendoor.png" style="width:5em; cursor: pointer;" id="Login_image" onclick="Action_Request('Login')"></td>
				<td style="text-align: center;">Sign In
					<table id="newlogin_data" class="init_data">
					<tr><td>Email: </td><td><input type="email" id="LoginName0" onchange="Change_Login(this.value);" /></td></tr>
					<tr><td>Password: </td>
						<td><input type="password" id="LoginPass0"
							onkeyup="Test_For_Enter(this.id,event);"
							onmouseover="this.type='text'"
							onmouseout="this.type='password'" />
						</td></tr>
					<tr><td colspan="2"><input id="LoginRememberMe" type="checkbox" />Remember me on this PC.</td></tr>
					<tr><td colspan="2" class="action_button"><button id="Login" onclick="Action_Request(this.id)">Sign in</button></td></tr>
				</table>
				<span style="text-align: left;">
				<img src="Images/password.png" id="getpw" style="width:8em; cursor:pointer;" onclick="Change_Option(this.id)">
				</span>
				</td>
			</table>

			<div id="init_getpw">
			<span id="getpw_data" class="init_data">
				<center>
				We will email to you a 6-digit verification code for you to use as a temporary password.
					The code is only good for today.
				<br /><br />Enter&nbsp;your&nbsp;email:
					<input id="LoginGetPWEmail" type="email" style="width: 22em;" onkeyup="Test_For_Enter(this.id,event);"/>
				<br /><button id="GetPW" class="action_button" onclick="Action_Request(this.id)">Send verification code</button>
					<button class="action_button" onclick="Change_Option('Login')">Cancel</button>
				</center>
			</span>
			</div>
		</div>
		
		<div id="init_newuser" class="init_option">
			<table style="width:100%;">
				<tr style="text-align: center;">
					<td style="vertical-align:middle;">Don't have an ID yet? . . . . .</td>
				
					<td><img id="newuser" src="Images/signup.png" style="width:4em; cursor:pointer;" onclick="Change_Option(this.id);"></td></tr>
			</table>
			<table id="newinit_data" class="init_data">
				<tr><td>Your first name: </td><td><input id="LoginFName1" /></td></tr>
				<tr><td>Your last name: </td><td><input id="LoginLName1" /></td></tr>
				<tr><td>Email: </td><td><input id="LoginEmail1" type="email" /></td></tr>
				<tr><td>Phone: </td><td><input id="LoginPhone1" type="email" /></td></tr>
				<tr><td>Password: </td><td><input id="LoginPass1a" type="password"/></td></tr>
				<tr><td>Re-enter Password: </td><td><input id="LoginPass1b" type="password" onkeyup="Test_For_Enter(this.id,event);" /></td></tr>
				<tr><td colspan="2" class="action_button">
					<button id="NewUser" onclick="Action_Request(this.id)">Create your ID</button>
					<button onclick="Change_Option('Login')">Cancel</button>
					</td></tr>
			</table>
		</div>
		
		<table style="cursor: pointer;">
		<tr id="init_appt" class="init_option" onclick="Action_Request('Appt');">
			<td><img src="Images/makeappt.png" style="width: 4em;"></td>
			<td style="vertical-align: middle;">Make or Change appointments</td></tr>

		<tr id="init_site" class="init_option" onclick="Action_Request('Site');">
			<td><img src="Images/options.png" style="width: 4em;"></td>
			<td style="vertical-align: middle;">Manage options and permissions</td></tr>
		</table>
		
		<div id="init_chgpw" class="init_option" style="text-align: center;">
				<hr />Change your information:<br />
				<table style="width: 100%; cursor: pointer;"><tr>
					<td><img src="Images/lock.png" style="width: 4em; height: 4em;" onclick="Change_Option('chgpw')">
						<br />Password</td>
					<td><img src="Images/email.png" style="width: 4em; height: 4em;" onclick="Change_Option('chgem')">
						<br />Email</td>
					<td><img src="Images/phone.png" style="width: 4em; height: 4em;" onclick="Change_Option('chgph')">
						<br />Phone</td>
					</tr></table>
			<table id="chgpw_data" class="init_data">
				<tr><td>New Password: </td><td><input id="LoginPass2a" type="password" /></td></tr>
				<tr><td>Re-enter Password: </td><td><input id="LoginPass2b" type="password" onkeyup="Test_For_Enter(this.id,event);" /></td></tr>
				<tr><td colspan="2" class="action_button">
					<button id="ChangePW" onclick="Action_Request(this.id)">Change password</button>
					<button onclick="Change_Option('Login')">Cancel</button></td></tr>
			</table>
		</div>
		
		<div id="init_chgem" class="init_option">
			<table id="chgem_data" class="init_data">
				<tr><td>Enter new email: </td><td><input id="LoginEmail3a" type="email" /></td></tr>
				<tr><td>Re-enter new email: </td><td><input id="LoginEmail3b" type="email" onkeyup="Test_For_Enter(this.id,event);" /></td></tr>
				<tr><td colspan="2" class="action_button">
					<button id="ChangeEmail" onclick="Action_Request(this.id)">Change email</button>
					<button onclick="Change_Option('Login')">Cancel</button></td></tr>
			</table>
		</div>
		
		<div id="init_chgph" class="init_option">
			<table id="chgph_data" class="init_data">
				<tr><td>Enter new phone: </td><td><input id="LoginPhone3" onkeyup="Test_For_Enter(this.id,event);" /></td></tr>
				<tr><td colspan="2" class="action_button">
					<button id="ChangePhone" onclick="Action_Request(this.id)">Change phone number</button>
					<button onclick="Change_Option('Login')">Cancel</button></td></tr>
			</table>
		</div>
		
		<div id="init_logout" class="init_option" style="text-align: center; cursor: pointer;">
			<hr />
			<img src="Images/signout.png" style="width:4em;" id="LogOut" onclick="Action_Request(this.id)">
		</div>

	</div> <!-- user-option-box -->

<span id="init_message"><?php global $Usermessage; if ($Usermessage != "") echo "<br />$Usermessage\n"; ?></span>
<span id="init_error"><?php global $Errormessage; if ($Errormessage != "") echo "<br />$Errormessage\n"; ?></span>
</td>
</table>

<?php
	global $sysnotice;
	if ($sysnotice) {
		echo "<div id=\"sysnotice\">\n";
		echo _Show_Chars($sysnotice, "html");
		echo "\n</div>\n";
	}
?>

<div id="LoginDiv" style="display:none">
<form id="LoginForm" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]);?>">
	<br />Action: <input id="LoginAction" Name="LoginAction" width="30" />
	<br />Name: <input id="LoginName" Name="LoginName" width="30" />
	<br />PassW: <input id="LoginPass" Name="LoginPass" width="30" />
	<br />Email: <input id="LoginEmail" Name="LoginEmail" width="30" />
	<br />Phone: <input id="LoginPhone" Name="LoginPhone" width="30" />
	<br />First: <input id="LoginFirst" Name="LoginFirst" width="30" />
	<br />Last: <input id="LoginLast" Name="LoginLast" width="30" />
	<br />Home: <input id="LoginHome" Name="LoginHome" width="30" />
	<!--
	<br />Options: <input id="LoginOptions" Name="LoginOptions" width="30" />
	-->
	</form>
<?php
	global $FormLoginAction;
	global $FormLoginName;
	global $FormLoginPass;
	global $FormLoginEmail;
	global $FormLoginPhone;
	global $FormLoginFirst;
	global $FormLoginLast;
	global $UserIndex;
	global $UserName;
	global $UserPass;
	global $UserHome;
	global $UserEmail;
	global $UserPhone;
	global $UserFirst;
	global $UserLast;

	if ($DEBUG) {
		echo "<table border=1>";
		echo "<tr><th>VARIABLE</th><th>SUBMITTED</th><th>DB MATCH</th><th>SESSION VARIABLE</th></tr>";
		echo "<tr><td>Session Index:</td><td></td><td>$UserIndex</td><td>" . $_SESSION['User']['user_index'] . "</td></tr>";
		echo "<tr><td>FormLoginAction:</td><td>$FormLoginAction</td><td>(N/A)</td><td>(N/A)</td></tr>";
		echo "<tr><td>FormLoginName:</td><td>$FormLoginName</td><td>$UserName</td><td>" . $_SESSION['User']['user_name'] . "</td></tr>";
		echo "<tr><td>FormLoginHome:</td><td>$FormLoginHome</td><td>$UserHome</td><td>" . $_SESSION['User']['user_home'] . "</td></tr>";
		echo "<tr><td>FormLoginPass:</td><td>$FormLoginPass</td><td>$UserPass</td><td>" . $_SESSION['User']['user_pass'] . "</td></tr>";
		echo "<tr><td>FormLoginEmail:</td><td>$FormLoginEmail</td><td>$UserEmail</td><td>" . $_SESSION['User']['user_email'] . "</td></tr>";
		echo "<tr><td>FormLoginPhone:</td><td>$FormLoginPhone</td><td>$UserPhone</td><td>" . $_SESSION['User']['user_phone'] . "</td></tr>";
		echo "<tr><td>FormLoginFirst:</td><td>$FormLoginFirst</td><td>$UserFirst</td><td>" . $_SESSION['User']['user_first'] . "</td></tr>";
		echo "<tr><td>FormLoginLast:</td><td>$FormLoginLast</td><td>$UserLast</td><td>" . $_SESSION['User']['user_last'] . "</td></tr>";
		echo "<tr><td>FormLoginFull:</td><td>(N/A)</td><td>(N/A)</td><td>" . $_SESSION['User']['user_full_name'] . "</td></tr>";
		echo "</table>";
	}
?>
</div>
</div> <!-- init_main -->

</body>

