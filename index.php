<?php
// Version 4.07

// Set up environment
require "environment.php";

// Required files
// sets $USER_TABLE, $SITE_TABLE, $APPT_TABLE and $ACCESS_TABLE
// plus sets session variables for the signed-in user
if (file_exists("opendb.php")) require "opendb.php";
else {
	header('Location: setup.php');
	exit;
}

// Set to true for debugging
$_SESSION["DEBUG"] = $DEBUG = false;

// Variables used globally:
$Errormessage = "";
$Usermessage = "";
$LocationList = [];
$LocationList[0] = 0;
$LocationInternet = [];
$FormLoginEmail = "";
$UserIdentified = (isset($_SESSION["UserIndex"]) AND ($_SESSION["UserIndex"] > 0));
$UserIndex = $UserIdentified ? $_SESSION["UserIndex"] : "";
$UserName = $UserIdentified ? $_SESSION["UserName"] : "";
$UserFirst = $UserIdentified ? $_SESSION["UserFirst"] : "";
$UserLast = $UserIdentified ? $_SESSION["UserLast"] : "";
$UserEmail = $UserIdentified ? $_SESSION["UserEmail"] : "";
$UserPhone = $UserIdentified ? $_SESSION["UserPhone"] : "";
$UserHome = $UserIdentified ? $_SESSION["UserHome"] : "";
$UserOptions = $UserIdentified ? $_SESSION["UserOptions"] : "";
$UserSiteList = $UserIdentified ? $_SESSION["UserSiteList"] : "";
$UserPass = "";
$Alert = "";
$gotophp = "index.php";
$isAdministrator = ($UserOptions == "A") ? true:false;

// Get system greeting, notice and info from the system table
$sysgreeting = "";
$sysnotice = "";
$query = "SELECT * FROM $SYSTEM_TABLE";
$sys = mysqli_query($dbcon, $query);
while ($row = mysqli_fetch_array($sys)) {
	$_SESSION["SystemGreeting"] = $sysgreeting = htmlspecialchars_decode($row['system_greeting']);
	$_SESSION["SystemNotice"] = $sysnotice = htmlspecialchars_decode($row['system_notice']);
	$_SESSION["SystemURL"] = $sysURL = htmlspecialchars_decode($row['system_url']);
	$_SESSION["SystemVersion"] = $row['system_version'];
	$_SESSION["TRACE"] = $row['system_trace'];

	// replace shortcode in greeting and notice
	if ($sysURL) {
		$replacement = "<a href=\"" . $sysURL . "\" target=\"_blank\">" . $sysURL . "</a>";
		$sysgreeting = str_replace("[STATESITE]", $replacement, $sysgreeting);
		$sysnotice = str_replace("[STATESITE]", $replacement, $sysnotice);
	}
}

// Get a list of locations

$j = 0;
$query = "SELECT * FROM $SITE_TABLE";
$query .= " WHERE `site_inet` <> ''";
$query .= " ORDER BY `site_name`";
$locs = mysqli_query($dbcon, $query);
while ($row = mysqli_fetch_array($locs)) {
	if ($row['site_closed'] >= $TodayDate) {
		$LocationList[0] = ++$j;
		$LocationList[$j] = htmlspecialchars_decode($row["site_name"]);
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
			$_SESSION["UserIndex"] = 0;
			$UserIndex = 0;
			break;
		default:
	}

	// Process request
	if (@$_SESSION["UserIndex"] > 0) { // i.e. Logged in
		$UserIndex = $_SESSION["UserIndex"];
		$UserOptions = $_SESSION["UserOptions"];

		switch ($FormLoginAction) {

			case "ChangeEmail":
				if (($FormLoginEmail == "") or (! filter_var($FormLoginEmail, FILTER_VALIDATE_EMAIL))) {
					$Errormessage .= "Please try again. \"$FormLoginEmail\" appears not to be in a recognized email format.";
					$UserEmail = "";
					break;
				}

				// See if email is already in use by other than current user
				$emailTest = Unique_Email($UserIndex, $FormLoginEmail);

				if ($emailTest["count"] == -1) {
					$Errormessage .= "Your email was not changed.";
					$Errormessage .= "<br />This email is already being used by " . $emailTest['user_first'] . " " . $emailTest['user_last'];
					break;
				}
				else {
					$query = "UPDATE `$USER_TABLE`";
					$query .= " SET `user_email` = '$FormLoginEmail'";
					$query .= " WHERE `user_index` = '$UserIndex'";
					mysqli_query($dbcon, $query);

					$idoemail = $_SESSION["UserEmail"];
					$query = "UPDATE `$APPT_TABLE`";
					$query .= " SET `appt_email` = '$FormLoginEmail'";
					$query .= " WHERE `appt_email` = '$idoemail'";
					mysqli_query($dbcon, $query);
					$_SESSION["UserEmail"] = $FormLoginEmail;
					$Usermessage .= "Your email has been changed as requested from $idoemail to $FormLoginEmail.";
					break;
				}

			case "ChangePhone":
				// Add the new phone number
				$query = "UPDATE `$USER_TABLE`";
				$query .= " SET `user_phone` = '$FormLoginPhone'";
				$query .= " WHERE `user_index` = '$UserIndex'";
				mysqli_query($dbcon, $query);
				$idophone = $_SESSION["UserPhone"];
				if ($idophone != "") {
					$query = "UPDATE `$APPT_TABLE`";
					$query .= " SET `appt_phone` = '$FormLoginPhone'";
					$query .= " WHERE `appt_phone` = '$idophone'";
					mysqli_query($dbcon, $query);
				}
				$_SESSION["UserPhone"] = $FormLoginPhone;
				$Usermessage .= "Your phone number has been changed";
				if ($idophone !=  "") $Usermessage .= " from $idophone";
				$Usermessage .= " to $FormLoginPhone.";
				break;
 		
			case "ChangePW":
				mysqli_query($dbcon, "UPDATE `$USER_TABLE` SET `user_pass` = '$FormLoginName' WHERE `user_index` = '$UserIndex' LIMIT 1");
				$Usermessage .= "Your password has been changed as requested.";
				break;
 		}
	}

	else { // User is not signed in

		// Look for a matching email
		$query = "SELECT * FROM $USER_TABLE";
		$query .= " WHERE `user_email` = '$FormLoginEmail'";
		$idq = mysqli_query($dbcon, $query);
		$count = mysqli_num_rows($idq);
		if ($count == 1) {
			while ($id = mysqli_fetch_array($idq)) {
				$_SESSION["UserIndex"] = $UserIndex = $id['user_index'];
				$_SESSION["UserFirst"] = $UserFirst = $id['user_first'];
				$_SESSION["UserLast"] = $UserLast = $id['user_last'];
				//$UserFirst = str_replace($id['user_first'], "!", "'");
				//$_SESSION["UserFirst"] = $UserFirst;
				//$UserLast = str_replace($id['user_last'], "!", "'");
				//$_SESSION["UserLast"] = $UserLast;
				$_SESSION["UserName"] = $UserName = $id['user_name'];
				$_SESSION["UserFullName"] = $UserFirst . " " . $UserLast;
				$_SESSION["UserHome"] = $UserHome = $id['user_home'];
				$_SESSION["UserOptions"] = $UserOptions = $id['user_options'];
				$_SESSION["UserEmail"] = $UserEmail = $id['user_email'];
				$_SESSION["UserPhone"] = $UserPhone = $id['user_phone'];
				$_SESSION["UserSiteList"] = $UserSiteList = $id['user_sitelist'];
				$UserPass = $id['user_pass'];
			}
			if ($_SESSION["TRACE"]) error_log("INDEX: " . $UserName . ", assigned to " . $UserEmail); 
		}

		if ($FormLoginEmail == $UserEmail) {

			switch ($FormLoginAction) { 

				case "NewUser":
					$Errormessage .= "An account has already been set up with that email address.";
					$UserIndex = $_SESSION["UserIndex"] = 0;
					break;

				case "Login":
					// check password for a leading dagger
					if (substr($UserPass,0,1) == chr(134)) {
						$UserPass = substr($UserPass,1);
						$Usermessage .= "Please change your password to something easier to remember.";
					}

					if ($FormLoginPass == $UserPass) {
						$gotophp = "appointment.php";
						$_SESSION["UserIndex"] = $UserIndex;
						$_SESSION["UserName"] = $UserName;
						$_SESSION["UserFirst"] = $UserFirst;
						$_SESSION["UserLast"] = $UserLast;
						$_SESSION["UserFullName"] = $UserFirst . " " . $UserLast;
						$_SESSION["UserEmail"] = $UserEmail;
						$_SESSION["UserPhone"] = $UserPhone;
						$_SESSION["UserHome"] = $UserHome;
						$_SESSION["UserOptions"] = $UserOptions;
						$_SESSION["UserSiteList"] = $UserSiteList;
						$FormLoginAction = "";

						// Update user table time stamp
						$query = "UPDATE `$USER_TABLE`";
						$query .= " SET `user_lastlogin` = '$TimeStamp'";
						$query .= " WHERE `user_index` = $UserIndex";
						$query .= " LIMIT 1";
						mysqli_query($dbcon, $query);
						if ($_SESSION["TRACE"]) error_log("INDEX: " . $UserName . ", logged in.");
					}
					else { // No match
						$_SESSION["UserIndex"] = $UserIndex = 0;

					}
					break;

				case "GetPW":
					if ($UserEmail != $FormLoginEmail) {
						$Errormessage .= "That email is not in our system";
						break;
					}
					$str = "ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789";
					$UserPass = substr(str_shuffle($str),rand(0,strlen($str)-8),8);
					$UserPass0 = chr(134) . $UserPass; // Add a leading dagger for use later
					$query = "UPDATE `$USER_TABLE`";
					$query .= " SET `user_pass` = '$UserPass0'";
					$query .= " WHERE `user_index` = $UserIndex";
					//$query .= " AND `user_first` = '$UserFirst'";
					$query .= " LIMIT 1";
					mysqli_query($dbcon, $query);

					$to = $UserEmail;
					$subject = "Your request from the AARP reservation system";
					$from = "aarp@cotaxaide.org";
					$headers = "From: AARP Appointment Manager";
					$message = "Greetings " . $UserFirst . " " . $UserLast . ".\n\n";
					$message .= "As requested, your password has been reset to \"" . $UserPass . "\".\n";
					$message .= "Please change your password the next time you sign in.\n\n.";
					if (substr($to,-5,5) == ".test") {
						$Alert .= "The following email would have been sent:\\n\\n" . str_replace("\n","\\n",$message);
					}
					else {
						mail($to,$subject,$message,$headers);
					}
					$Usermessage .= "You should receive an email message in the next few minutes with a new temporary password.";
					$UserIndex = $_SESSION["UserIndex"] = 0;
					break;

				case "LogOut":
					break;

				default:
					$Errormessage .= "Invalid request";
					$_SESSION["UserIndex"] = 0;
			}
		}

		if ($UserIndex == 0) {
			$UserName = "";
			if ($FormLoginAction == "NewUser") { // Set up a new account for a self-registering (internet) user
				$_SESSION["UserIndex"] = 0;
				if (! filter_var($FormLoginEmail, FILTER_VALIDATE_EMAIL)) {
					$Errormessage .= "Your email is not in a recognized format.  Please correct and re-submit.";
					$UserEmail = "";
				}

    				if ($Errormessage == "") {
					$query = "INSERT INTO `$USER_TABLE`";
					$query .= " (`user_name`,`user_email`,`user_phone`,`user_pass`,`user_last`,`user_first`,`user_home`,`user_appt_site`,`user_options`,`user_sitelist`)";
					$query .= " VALUES ('$FormLoginName','$FormLoginEmail','$FormLoginPhone','$FormLoginPass','$FormLoginLast','$FormLoginFirst',0,0,'','|')";
					$res = mysqli_query($dbcon, $query);
					if ($res) {
						$idx = mysqli_query($dbcon, "SELECT * FROM $USER_TABLE WHERE `user_email` = '$FormLoginEmail' LIMIT 1");
						$id = mysqli_fetch_array($idx);
						//$UserPass = $id1['user_pass'];
						$_SESSION["UserIndex"] = $UserIndex = $id['user_index'];
						$_SESSION["UserName"] = $UserName = $id['user_name'];
						$_SESSION["UserFirst"] = $UserFirst = $id['user_first'];
						$_SESSION["UserLast"] = $UserLast = $id['user_last'];
						$_SESSION["UserFullName"] = $UserFirst . " " . $UserLast;
						$_SESSION["UserEmail"] = $UserEmail = $id['user_email'];
						$_SESSION["UserPhone"] = $UserPhone = $id['user_phone'];
						$_SESSION["UserSiteList"] = $UserSiteList = $id['user_sitelist'];

						$_SESSION["UserOptions"] = 0;
						$_SESSION["UserHome"] = 0;
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

<script>
<?php
	global $DEBUG, $UserIndex, $UserOptions;
	$d = ($DEBUG) ? "true":"false";
	echo "	var DEBUG = $d;\n";
	$uid = ($UserIndex > 0) ? $UserIndex : 0;
	echo "	var uid = $uid;\n";
	$uhm = (($UserIndex > 0) and (($UserOptions == 'A') OR ($UserOptions == 'M'))) ? "true":"false";
	echo "	var uhm = $uhm;\n";
?>
	var c0 = ""; // email cookie
	var c1 = ""; // password cookie

	/* ******************************************************************************************************************* */
	function Initialize() {
	/* ******************************************************************************************************************* */
		LoginDiv.style.display = (DEBUG) ? "block":"none";
		init_login.style.display = (!uid) ? "block":"none";
		newlogin_data.style.display = (!uid) ? "block":"none";
		init_newuser.style.display = (!uid) ? "block":"none";
		init_getpw.style.display = (!uid) ? "block":"none";
		init_chgpw.style.display = (uid) ? "block":"none";
		init_chgem.style.display = (uid) ? "block":"none";
		init_chgph.style.display = (uid) ? "block":"none";
		init_appt.style.display = (uid) ? "block":"none";
		init_site.style.display = (uhm) ? "block":"none";
		init_logout.style.display = (uid) ? "block":"none";
		if (!uid) LoginName0.focus();
		Change_Option("login"); // collapse all boxes except login
		<?php
		global $Alert;
		if ($Alert != "") echo "alert('" . $Alert . "');";
		?>

		// Read login cookies if present
		Read_Cookie();
		LoginRememberMe.checked = ((c0 > "") && (c1 > "")) ? true:false;
	}

	/* ******************************************************************************************************************* */
	function Change_Option(id) {
	/* ******************************************************************************************************************* */
		if (id != "login") init_message.innerHTML = "";
		if (id != "login") error_message.innerHTML = "";
		newinit_data.style.display = (id == "newuser") ? "block":"none"; 
		chgpw_data.style.display = (id == "chgpw") ? "block":"none"; 
		chgem_data.style.display = (id == "chgem") ? "block":"none"; 
		chgph_data.style.display = (id == "chgph") ? "block":"none"; 
		getpw_data.style.display = (id == "getpw") ? "block":"none"; 
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
				Set_Cookie();
				break;
			case "NewUser":
				if (LoginFName1.value == "") {
					alert("Please enter your first name.");
					return;
				}
				if (LoginLName1.value == "") {
					alert("Please enter your last name.");
					return;
				}
				if (LoginEmail1.value == "") {
					alert("Please enter your email.");
					return;
				}
				if (LoginPhone1.value == "") {
					alert ("Please enter your phone number.");
					return;
				}
				LoginForm.LoginPhone.value = LoginPhone1.value;
				if (Test_Phone()) return;
				LoginPhone1.value = LoginForm.LoginPhone.value 
				
				if ((LoginPass1a.value == "") || (LoginPass1b.value == "")) {
					alert ("Please enter your password, twice.");
					return;
				}
				if (LoginPass1a.value != LoginPass1b.value) {
					alert("Password entries don't match");
					return;
				}
					
				LoginForm.LoginPass.value = LoginPass1a.value;
				LoginForm.LoginFirst.value = Clean_Name(LoginFName1.value);
				LoginForm.LoginLast.value = Clean_Name(LoginLName1.value);
				LoginForm.LoginName.value = LoginForm.LoginFirst.value.replace(/[\s!]/g,"") + LoginLName1.value.substr(0,1);
				LoginForm.LoginEmail.value = LoginEmail1.value;
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
				Set_Cookie();
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
				LoginForm.LoginEmail.value = LoginEmail3a.value;
				break;
			case "ChangePhone":
				if (LoginPhone3.value == "") {
					alert("You must enter a new phone number");
					return;
				}
				LoginForm.LoginPhone.value = LoginPhone3.value;
				badphone = Test_Phone();
				if (badphone) return;
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
		}

		LoginForm.LoginAction.value = Action;
		if (DEBUG) alert(Action + " being submitted");
		LoginForm.submit();
	}
	
	//===========================================================================================
	function Clean_Name(nametotest) {
	//===========================================================================================
	nametotest = nametotest.replace(/'/g,"!");
	nametotest = nametotest.replace(/"/g,"");
	return (nametotest);
	}

	//===========================================================================================
	function Test_Phone() {
	//===========================================================================================
		if ((phnum = LoginForm.LoginPhone.value.trim().replace(/[\s-()]/g,"")) == "") return(1);
		patt = /[^0-9]/;
		if (patt.test(phnum)) {
			alert("The phone number may contain only digits and dashes");
			return(2);
		}
		toll = (phnum.charAt(0) == "1") ? 1:0;
		if ((phnum.length != 10 + toll) & (phnum.length != 7 + toll)) {
			alert("Please enter the phone number as a 7 or 10-digit number with an optional \"1\" preceding.");
			return(3);
		}
		if (phnum.length == 7 + toll) {
			LoginForm.LoginPhone.value = ((toll == 1) ? "1-":"") + phnum.substr(0 + toll,3) + "-" + phnum.substr(3 + toll);
		}
		if (phnum.length == 10 + toll) {
			LoginForm.LoginPhone.value = ((toll == 1) ? "1-":"") + phnum.substr(0 + toll,3) + "-" + phnum.substr(3 + toll,3) + "-" + phnum.substr(6 + toll);
		}
		return(0);
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
	function Set_Cookie() {
	//===========================================================================================
		// Is the RememberMe box checked or unchecked?
		if (LoginRememberMe.checked) {
			var d = new Date(); // set cookie expiration date
				d.setTime(d.getTime() + (2*366*24*60*60*1000)); // 2 years
			var CookieExpires = "expires=" + d.toUTCString() + ";";
			c0 = LoginName0.value + ';';
			c1 = LoginPass0.value + ';';
		}
		else {
			CookieExpires = "expires=Thu, 01 Jan 1970 00:00:00 GMT;"; // unchecked
			c0='"";';
			c1='"";';
		}

		document.cookie = "TA_Appt_0=" + c0 + CookieExpires + " path=/;";
		document.cookie = "TA_Appt_1=" + c1 + CookieExpires + " path=/;";

		// Did the cookie get set correctly?
		//Read_Cookie();
		if (LoginRememberMe.checked) {
			if ((c0 == "") || (c1 == "")) alert("Cookie could not be set. Your password will not be remembered.");
		}
	}

	//===========================================================================================
	function Read_Cookie() {
	//===========================================================================================
		var cookie = document.cookie;
		var cookieList = cookie.split(";");
		for (cookieIdx = 0; cookieIdx < cookieList.length; cookieIdx++) {
			var cn = cookieList[cookieIdx];
			while (cn.charAt(0)==' ') cn = cn.substring(1);
			var cookieVar = cn.split("=");
			if (cookieVar[0] == "TA_Appt_0") LoginName0.value = c0 = cookieVar[1];
			if (cookieVar[0] == "TA_Appt_1") LoginPass0.value = c1 = cookieVar[1];
		}
	}

	//===========================================================================================
	function Show_History() {
	//===========================================================================================
		change_history.style.display = (change_history.style.display == 'none') ? 'block':'none';
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
		<?php if ($UserIdentified) echo "You are signed in as " . str_replace("!", "&apos;", $_SESSION["UserFullName"]); ?>
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
<table id="layouttbl"><tr>
<td id="info_block">
	
	<div id="aarp_foundation">
		<?php global $sysgreeting; echo $sysgreeting; ?>
	</div>

	<div id="sitelist">
		<?php
		global $LocationList;
		if ($LocationList[0] != 0) {
			echo "<br />The following sites are accepting reservations on-line:<br />";
			echo "<ul>";
			for ($j = 1; $j <= $LocationList[0]; $j++) {
				echo "<li>" . $LocationList[$j] . "</li>";
			}
			echo "</ul>";
		}
		else {
			echo "<br />We are not accepting reservations at this time.";
		}
		?>
	</div>

	<br />Appointments at other sites are by phone &ndash; see the <a href="http://www.aarp.org/applications/VMISLocator/searchTaxAideLocations.action" target="_blank">AARP Locator web site</a>.

</td>

<td id="init_options">
	<div id="init_option_box">

		<div id="init_login" class="init_option">
			<table><tr>
				<td><img src="Images/opendoor.png" style="width:5em; cursor: pointer;" id="login" onclick="Action_Request('Login')"></td>
				<td style="text-align: center;">Sign In
					<table id="newlogin_data" class="init_data">
					<tr><td>Email: </td><td><input type="email" id="LoginName0" /></td></tr>
					<tr><td>Password: </td><td><input type="password" id="LoginPass0" onkeyup="Test_For_Enter(this.id,event);" /></td></tr>
					<tr><td colspan="2"><input id="LoginRememberMe" type="checkbox" /> Remember me on this PC.</td></tr>
					<tr><td colspan="2" class="action_button"><button id="Login" onclick="Action_Request(this.id)">Sign in</button></td></tr>
				</table>
				<span style="text-align: left;">
				<img src="Images/password.png" id="getpw" style="width:8em; cursor:pointer;" onclick="Change_Option(this.id)">
				</span>
				</td>
			</table>

			<div id="init_getpw">
			<table id="getpw_data" class="init_data">
				<tr><td>Enter&nbsp;your&nbsp;email: </td><td><input id="LoginGetPWEmail" type="email" onkeyup="Test_For_Enter(this.id,event);"/></td></tr>
				<!--
				<tr><td>Enter your first name: </td><td><input id="LoginGetPWFirst" type="email" /></td></tr>
				-->
				<tr>
					<td colspan="2" class="action_button"><button id="GetPW" onclick="Action_Request(this.id)">Email new password</button>
					<button onclick="Change_Option('Login')">Cancel</button></td>
				</tr>
			</table>
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
				<tr><td>Password: </td><td><input id="LoginPass1a" /></td></tr>
				<tr><td>Re-enter Password: </td><td><input id="LoginPass1b" onkeyup="Test_For_Enter(this.id,event);" /></td></tr>
				<tr><td colspan="2" class="action_button">
					<button id="NewUser" onclick="Action_Request(this.id)">Create your ID</button>
					<button onclick="Change_Option('Login')">Cancel</button>
					</td></tr>
			</table>
		</div>
		
		<table style="cursor: pointer;">
		<tr id="init_appt" class="init_option" onclick="Action_Request('Appt');">
			<td><img src="Images/makeappt.png" style="width: 4em;" id="Appt"></td>
			<td style="vertical-align: middle;">Make or Change appointments</td></tr>

		<tr id="init_site" class="init_option" onclick="Action_Request('Site');">
			<td><img src="Images/options.png" style="width: 4em;" id="Site"></td>
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
<span id="error_message"><?php global $Errormessage; if ($Errormessage != "") echo "<br />$Errormessage\n"; ?></span>
</td>
</table>

<?php
	global $sysnotice;
	if ($sysnotice) {
		echo "<div id=\"sysnotice\">\n";
		echo $sysnotice;
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
		echo "<tr><td>Session Index:</td><td></td><td>$UserIndex</td><td>" . $_SESSION['UserIndex'] . "</td></tr>";
		echo "<tr><td>FormLoginAction:</td><td>$FormLoginAction</td><td>(N/A)</td><td>(N/A)</td></tr>";
		echo "<tr><td>FormLoginName:</td><td>$FormLoginName</td><td>$UserName</td><td>" . $_SESSION['UserName'] . "</td></tr>";
		echo "<tr><td>FormLoginHome:</td><td>$FormLoginHome</td><td>$UserHome</td><td>" . $_SESSION['UserHome'] . "</td></tr>";
		echo "<tr><td>FormLoginPass:</td><td>$FormLoginPass</td><td>$UserPass</td><td>" . $_SESSION['UserPass'] . "</td></tr>";
		echo "<tr><td>FormLoginEmail:</td><td>$FormLoginEmail</td><td>$UserEmail</td><td>" . $_SESSION['UserEmail'] . "</td></tr>";
		echo "<tr><td>FormLoginPhone:</td><td>$FormLoginPhone</td><td>$UserPhone</td><td>" . $_SESSION['UserPhone'] . "</td></tr>";
		echo "<tr><td>FormLoginFirst:</td><td>$FormLoginFirst</td><td>$UserFirst</td><td>" . $_SESSION['UserFirst'] . "</td></tr>";
		echo "<tr><td>FormLoginLast:</td><td>$FormLoginLast</td><td>$UserLast</td><td>" . $_SESSION['UserLast'] . "</td></tr>";
		echo "<tr><td>FormLoginFull:</td><td>(N/A)</td><td>(N/A)</td><td>" . $_SESSION['UserFullName'] . "</td></tr>";
		echo "</table>";
	}
?>
</div>
</div> <!-- init_main -->

</body>

