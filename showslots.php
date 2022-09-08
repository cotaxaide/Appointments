<?php
// Version 8.01
// 	Minor messaging changes for User View
//	Added attachments to User View instructions
// Version 7.01
// 	Initial creation from part of appointment.php to reduce file size

//===========================================================================================
function Show_Slots() {
//===========================================================================================
	global $Debug, $Errormessage;
	global $dbcon;
	global $FirstSlotDate;
	global $Date;
	global $DateList;
	global $NullDate;
	global $NullTime;
	global $TodayDate;
	global $APPT_TABLE;
	global $MyTimeStamp;
	global $BgColor;
	global $ApptNo;
	global $ApptTimeDisplay;
	global $ApptName;
	global $ApptPhone;
	global $ApptEmail;
	global $ApptSite;
	global $Appt10dig;
	global $ApptTags;
	global $ApptNeed;
	global $ApptInfo;
	global $ApptStatus;
	global $DeleteCode;
	global $SingleSite;
	global $FormApptDate;
	global $FormApptOldNo;
	global $FormApptLoc;
	global $CustEList;
	global $CustPList;
	global $OpenAppt;
	global $LocationShow;
	global $LocationList;
	global $LocationLookup;
	global $LocationMessage;
	global $LocationInstructions;
	global $LocationName;
	global $LocationInet;
	global $LocationInetLimit;
	global $LocationIsOpen;
	global $LocationOpen;
	global $LocationClosed;
	global $LocationSumRes;
	global $Location10dig;
	global $LocationCBList, $LocationEmpty;
	global $LocationAttachHTML;
	global $ShowDagger;
	global $SitePermissions, $ADD_APP, $ADD_CB, $USE_RES;
	global $UserPermissions, $MaxPermissions;
	global $ADMINISTRATOR, $MANAGER;
	global $WaitSequence;
	global $LastSlotNumber;
	global $HeaderText;
	global $ApptView;
	global $UserFirst;
	global $UserLast;
	global $UserEmail;
	global $UserOptions;
	global $UserPhone;
	global $UserHome;
	global $UsedCBSlots;
	global $YR, $MO, $DY, $YMD, $MON, $DOW;
	global $ApptGroupList, $ApptGroupListIndex;
	global $RESERVED;
		
		$SlotNumber = 0;
		$SlotIndex = 0;
		$ApptNo = "";
		$ApptTimeDisplay = "";
		$ApptName = "";
		$ApptPhone = "";
		$ApptEmail = "";
		$ApptSite = "";
		$Appt10dig = "";
		$ApptTags = "";
		$ApptNeed = "";
		$ApptInfo = "";
		$ApptStatus = "";
		$LastSlotNumber = 0;
		$WaitSequence = 0;

	// -----------------------------------------------------------------------------------------
	if ($ApptView == "ViewDeleted") {
	// -----------------------------------------------------------------------------------------
		echo "<div class='slotlist'>\n";
		echo "<table id='daily_table' class='apptTable'>";

		//Fetching from the database table.
		$query = "SELECT * FROM $APPT_TABLE";
		$query .= " WHERE `appt_date` = '$NullDate'";
		$query .= " AND `appt_type` = 'D'";
		$query .= " ORDER BY `appt_location`, `appt_name`";
		$appointments = mysqli_query($dbcon, $query);
		$SaveWaitSequence = 0;
		$OldLoc = 0;
		while($row = @mysqli_fetch_array($appointments)) {
			$Name = htmlspecialchars_decode($row["appt_name"] ?? '');
			$Phone = $row["appt_phone"];
			$Tags = htmlspecialchars_decode($row["appt_tags"] ?? '');
			$Need = htmlspecialchars_decode($row["appt_need"] ?? '');
			$Info = htmlspecialchars_decode($row["appt_info"] ?? '');
			$Status = htmlspecialchars_decode($row["appt_status"] ?? '');
			$Email = $row["appt_email"];
			$Appt = $row["appt_no"];
			//$Hour = substr($Time, 0, 2);
			$Location = $row["appt_location"];
			$LocationIndex = $LocationLookup["S" . $Location];
			$Location10digreq = @$Location10dig[$LocationIndex];
			if ($LocationIndex AND $LocationShow[$LocationIndex]) {

				// New location header
				if ($Location != $OldLoc) { // Add a new group header
					echo "<tr class='apptLoc'>\n";
					echo "<th class='sticky left' colspan='2'>Deleted List (" . $LocationName[$LocationIndex] . ")</th>";
					if ($OldLoc) {
						echo "<th></th><th></th><th></th><th></th></tr>\n";
					}
					else { // Column title for first header row
						echo "<th class='apptPhone sticky center'>Phone</th>\n";
						echo "<th class='apptNeed sticky'>Note</th>\n";
						echo "<th class='apptNeed sticky'>Info</th>\n";
						echo "<th class='apptStatus sticky'>Status</th></tr>\n";
					}
					$OldLoc = $Location;
					$SlotIndex = 0;
				}

				// Show the record
				$SlotNumber++; // Index number into a local array of displayed appointments
				$SlotIndex++; // Visual index on the screen, resets on change of group
				$myclass = "";
				// Add tags to the name if they are present
				List_Slot($Appt, $SlotNumber, $SlotIndex, $myclass, $Name, $Phone, $Tags, $Need, $Info, $Status);

				// Add the record to the arrays
				$LastSlotNumber = $SlotNumber;
				$ApptNo .= ", \"$Appt\"";
				$ApptName .= ", \"$Name\"";
				$ApptPhone .= ", \"$Phone\"";
				$ApptEmail .= ", \"" . htmlspecialchars_decode($Email ?? '') . "\"";
				$ApptSite .= ", \"$LocationIndex\"";
				$Appt10dig .= ", \"$Location10digreq\"";
				$ApptTags .= ", \"$Tags\"";
				$ApptNeed .= ", \"$Need\"";
				$ApptInfo .= ", \"$Info\"";
				$ApptStatus .= ", \"$Status\"";
				$ApptTimeDisplay .= ", \"$NullTime\"";
			}
		}

		echo "</table>\n";
		echo "</div>\n";
		$appointments = []; // release memory

	} // End of Deleted view
		
	// -----------------------------------------------------------------------------------------
	if (($ApptView == "ViewDaily") or ($ApptView == "ViewCallback")) { // Daily view
	// -----------------------------------------------------------------------------------------

		//Start the table
		echo "<div class='slotlist'>\n";
		echo "<table id='daily_table' class='apptTable'>\n";
		$AddSlotLoc = "";
		$HomeIndex = $LocationLookup["S" . $UserHome];
		if ($ApptView == "ViewCallback") {
			$FirstSlotDate = $NullDate;
			$HeaderText = "Callback List:";
		}
		else {
			$ShowDate = Format_Date($FirstSlotDate, true); // set $MON which is global
			$HeaderText = "Appointments for $ShowDate:";
			if ($FirstSlotDate == $NullDate) {
				$HeaderText = "No appointments found";
				echo "<tr class='apptGroup'><td colspan='7'>$HeaderText</td></tr>\n";
				echo "</table>\n";
				echo "</div>\n";
				return;
			}
		}
		echo "<tr class='apptGroup'>\n";
		echo "<th colspan='2' class='sticky left'>$HeaderText</th>\n";
		echo "<th class='center apptPhone sticky'>Phone</th>\n";
		echo "<th class='apptNeed sticky'>Note</th>\n";
		echo "<th class='apptNeed sticky'>Info</th>\n";
		echo "<th class='apptStatus sticky'>Status</th>\n";


		//Fetching from the database table.
		$query = "SELECT * FROM $APPT_TABLE";
		$query .= " WHERE `appt_date` = '$FirstSlotDate'";
		if ($ApptView == "ViewCallback") {
			$query .= " ORDER BY `appt_time`, `appt_date`, `appt_location`, `appt_wait`";
		}
		else {
			$query .= " ORDER BY `appt_time`, `appt_date`, `appt_location`, `appt_no`";
		}
		$appointments = mysqli_query($dbcon, $query);

		$OldLoc = 0;
		$OldTime = 0;
		$SaveAppt = []; // Stores the appt number of the last empty callback list appt encountered
		$ApptGroupList = array();
		$ApptGroupListIndex = 0;
		$SaveWaitSequence = 0;
		while($row = @mysqli_fetch_array($appointments)) {
			$Type = $row["appt_type"];
			$Date = $row["appt_date"];
			$Time = $row["appt_time"];
			$Name = htmlspecialchars_decode($row["appt_name"] ?? '');
			$Phone = $row["appt_phone"];
			$Tags = htmlspecialchars_decode($row["appt_tags"] ?? '');
			$Need = htmlspecialchars_decode($row["appt_need"] ?? '');
			$Info = htmlspecialchars_decode($row["appt_info"] ?? '');
			$Status = $row["appt_status"];
			$Email = $row["appt_email"];
			$Appt = $row["appt_no"];
			//$Hour = substr($Time, 0, 2); // may be a problem with $Time here, causes error
			$WaitSequence = $row["appt_wait"];
			$Location = $row["appt_location"];
			$LocationIndex = $LocationLookup["S" . $Location];
			@$Location10digreq = $Location10dig[$LocationIndex];
			$SkipThisEntry = false;
			if ($LocationIndex and (@$LocationShow[$LocationIndex]) and ($Type != $DeleteCode)) {

				// New time
				if ($Time == $NullTime) { // This is a callback list entry
					if ($Name == "") {
						// Skip if no name has been assigned. We'll add one later
						$SkipThisEntry = true;
						// Save the appt number of the blank callback entry for the site
						$SaveAppt[$Location] = $Appt; 
						$SaveWaitSequence = $WaitSequence;
					}
					if ($ApptView == "ViewCallback") $ShowTime = "Callback List";
				}
				else {
					$ShowTime = Format_Time($Time, false);
				}
	
				// New location and/or time header
				if (($Time != $OldTime) OR ($Location != $OldLoc)) { // Add a new group header

					List_Group();

					if (($OldTime == $NullTime) and ($OldLoc != 0)) { // end of a site callback list
						if ($OldLoc == 0) {
							$OldLoc = $Location;
							$OldLocIndex = $LocationIndex;
						}
						// update the empty record if one exists
						$SlotNumber++; // Index number into a local array of displayed appointments
						$SlotIndex++; // Visual index on the screen, resets on change of group
						if ($SaveWaitSequence < $_SESSION["MaxWaitSequence"]) $SaveWaitSequence = ++$_SESSION["MaxWaitSequence"];
						if ($SaveAppt[$OldLoc]) {
							$query = "UPDATE $APPT_TABLE SET";
							$query .= "  `appt_wait` = $SaveWaitSequence";
							$query .= ", `appt_phone` = ''";
							$query .= ", `appt_tags` = ''";
							$query .= ", `appt_need` = ''";
							$query .= ", `appt_info` = ''";
							$query .= ", `appt_status` = ''";
							$query .= ", `appt_email` = ''";
							$query .= ", `appt_location` = $OldLoc";
							$query .= ", `appt_change` = '$MyTimeStamp'";
							$query .= " WHERE `appt_no` = $SaveAppt[$OldLoc]";
							mysqli_query($dbcon, $query);
						}

						$myclass = "apptOpen";
						List_Slot($SaveAppt[$OldLoc], $SlotNumber, $SlotIndex, $myclass, "", "", "", "", "", "");

						// Add the slot to the site arrays
						$LastSlotNumber = $SlotNumber;
						$ApptNo .= ", \"$SaveAppt[$OldLoc]\"";
						$ApptName .= ", \"\"";
						$ApptPhone .= ", \"\"";
						$ApptEmail .= ", \"\"";
						$ApptSite .= ", \"$OldLocIndex\"";
						$Appt10dig .= ", \"$Location10digreq\"";
						$ApptTags .= ", \"\"";
						$ApptNeed .= ", \"\"";
						$ApptInfo .= ", \"\"";
						$ApptStatus .= ", \"\"";
						$ApptTimeDisplay .= ", \"$ShowTime\"";
						$SaveAppt[$OldLoc] = 0; // Used the empty record
					}
					$TimeHeader = "$ShowTime (" . $LocationName[$LocationIndex] . ")";
					if (($ApptView == "ViewDaily") AND ($UserPermissions & ($MANAGER | $ADMINISTRATOR))) {
						$Lno = $LocationList[$LocationIndex];
						$SitePermission = @$SitePermissions["S" . $Lno];
						if (($UserOptions === "A") or (@$SitePermission & $MANAGER)) {
							$Stime = substr($Time, 0, 5);
							$TimeHeader .= " <div class=\"Do1Slot\"";
							$TimeHeader .= " onclick=\"Do1Slot('add', '$Lno', '$FirstSlotDate', '$Stime');\"";
							$TimeHeader .= " title=\"Add a new appointment slot for " . $ShowTime . ".\">+</div>";
							$TimeHeader .= " <div class=\"Do1Slot\"";
							$TimeHeader .= " onclick=\"Do1Slot('rmv', '$Lno', '$FirstSlotDate', '$Stime');\"";
							$TimeHeader .= " title=\"Remove an unused appointment slot for " . $ShowTime . ".\">-</div>";
							if (($AddSlotLoc == "") OR ($Lno == $LocationList[$HomeIndex])) $AddSlotLoc = $Lno;
						}
					}
					echo "<tr class='apptLoc bold left'><th colspan='2'>$TimeHeader</th>\n";
					$TimeHeaderCB = Add_CB_Status("", $LocationIndex);
					echo "<td colspan='4'>$TimeHeaderCB</td></tr>\n";
					$OldTime = $Time;
					$OldLoc = $Location;
					$OldLocIndex = $LocationIndex;
					$SlotIndex = 0;
				}

				if (! $SkipThisEntry) { 
					$SlotNumber++; // Index number into a local array of displayed appointments
					$SlotIndex++; // Visual index on the screen, resets to 1 on change of group

					$myclass = ($Name == "") ? " apptOpen" : "apptInUse"; // apptOpen class sets green color
					if ($ApptView == "ViewDaily") {
						$GroupType = 1; // has a name in it
						if ($Name == "") $GroupType = 2;
						if ($Name == $RESERVED) $GroupType = 3;
						$ApptGroupItem = array($GroupType, $Appt, $SlotNumber, $SlotIndex, $myclass, $Name, $Phone, $Tags, $Need, $Info, $Status);
						$ApptGroupListIndex++;
						$ApptGroupList[$ApptGroupListIndex] = $ApptGroupItem;
						$ApptGroupItem = $ApptGroupList[$ApptGroupListIndex];
						// List_Slot will be called via List_Group when the location or time changes
					}
					else { // ViewCallback
						List_Slot($Appt, $SlotNumber, $SlotIndex, $myclass, $Name, $Phone, $Tags, $Need, $Info, $Status);
					}

					$LastSlotNumber = $SlotNumber;
					$ApptNo .= ", \"$Appt\"";
					$ApptName .= ", \"$Name\"";
					$ApptPhone .= ", \"$Phone\"";
					$ApptEmail .= ", \"$Email\"";
					$ApptSite .= ", \"$LocationIndex\"";
					$Appt10dig .= ", \"$Location10digreq\"";
					$ApptTags .= ", \"$Tags\"";
					$ApptNeed .= ", \"$Need\"";
					$ApptInfo .= ", \"$Info\"";
					$ApptStatus .= ", \"$Status\"";
					$ApptTimeDisplay .= ", \"$ShowTime\"";
				}
			}
		}

		List_Group();

	
		if (($ApptView == "ViewCallback") and ($OldLoc != "")) { // end of last callback list, add a final blank slot
			$SlotNumber++; // Index number into a local array of displayed appointments
			$SlotIndex++; // Visual index on the screen, resets on change of group
			if ($OldLoc == 0) $OldLoc = $Location;
			if ($SaveWaitSequence < $_SESSION["MaxWaitSequence"]) $SaveWaitSequence = ++$_SESSION["MaxWaitSequence"];
			// add a blank record if one exists
			if ($SaveAppt[$OldLoc]) {
				$query = "UPDATE $APPT_TABLE SET";
				$query .= "  `appt_wait` = $SaveWaitSequence";
				$query .= ", `appt_phone` = ''";
				$query .= ", `appt_tags` = ''";
				$query .= ", `appt_need` = ''";
				$query .= ", `appt_info` = ''";
				$query .= ", `appt_status` = ''";
				$query .= ", `appt_email` = ''";
				$query .= ", `appt_location` = $OldLoc";
				$query .= ", `appt_change` = '$MyTimeStamp'";
				$query .= " WHERE `appt_no` = $SaveAppt[$OldLoc]";
				mysqli_query($dbcon, $query);
			}

			$myclass = "apptOpen";
			List_Slot($SaveAppt[$OldLoc], $SlotNumber, $SlotIndex, $myclass, "", "", "", "", "", "");

			// Add the slot to the site arrays
			$LastSlotNumber = $SlotNumber;
			$ApptNo .= ", \"$SaveAppt[$OldLoc]\"";
			$ApptName .= ", \"\"";
			$ApptPhone .= ", \"\"";
			$ApptEmail .= ", \"\"";
			$ApptSite .= ", \"$OldLocIndex\"";
			$ApptTags .= ", \"\"";
			$ApptNeed .= ", \"\"";
			$ApptInfo .= ", \"\"";
			$ApptStatus .= ", \"\"";
			$ApptTimeDisplay .= ", \"$ShowTime\"";
			$SaveAppt[$OldLoc] = 0; // We used it
		}

		echo "</table>\n";

		// Add the new slot options for the callback list
		if ($ApptView == "ViewCallback") {
			if ($UserPermissions & ($ADMINISTRATOR | $MANAGER)) {
			echo "<br /><br />\n";
				echo "Add <input id='SlotsToAdd' type='number' size='1' maxlength='2' /> additional reserved entries for the ";
				echo "<select id=\"LocationToAdd\">\n";
				echo "<option value=\"0\">Choose a site</option>\n";
				if ($OldLoc == "") $OldLoc = $LocationList[$HomeIndex];
				List_Locations($OldLoc);
				echo "</select>\n";
				echo "<button onclick='AddCBSlots()'>(Click to add)</button>\n";
			}
			else if ($LocationShow[$HomeIndex] AND ($UserPermissions & $ADD_CB)) {
				echo "<br /><br />\n";
				echo "<button onclick='AddCBSlots()'>Click to add...</button>\n";
				echo "<input id='SlotsToAdd' type='number' size='1' maxlength='2' /> additional blank entries for the " . $LocationName[$HomeIndex];
			}
		}
		
		// Add the new time group options to the daily view
		if (($ApptView == "ViewDaily") AND ($UserPermissions & ($ADMINISTRATOR | $MANAGER))) {
			echo "<br /><br />\n";
			if ($AddSlotLoc == "") $AddSlotLoc = $LocationList[$HomeIndex];
			echo "Add a new time group at\n";
			echo "<input id='TimeToAdd' type='time' /> with\n";
			echo "<input id='NewSlotsToAdd' type='number' size='1' maxlength='2' value='1'> slot(s) to the\n";
			echo "<select id=\"LocationToAdd\">\n";
			echo "<option value=\"0\">Choose a site</option>\n";
			List_Locations($AddSlotLoc);
			echo "</select>\n";
			echo "<button onclick='AddNewTime(\"$FirstSlotDate\")'>(Click to add)</button>\n";
		}
		echo "</div>\n";

	} // End of Daily/Wait views

	// -----------------------------------------------------------------------------------------
	if (($ApptView == "ViewUser") OR ($ApptView == "ViewSummary")) { // User or Summary views 
	// -----------------------------------------------------------------------------------------

		$InetLimited = false;
		$onCallback = false;
		if ($ApptView == "ViewUser") { // Show an instruction header
			echo "<div class='custTable'>\n";
			echo "<b>Welcome " . _Show_Chars($UserFirst, "html") . ",";
			$LocationChosen = $_SESSION["UserLoc"];
			if ($LocationShow[0] > 0) {
				for ($i = 1; $i <= $LocationList[0]; $i++) {
					if ($LocationShow[$i] > 0) $LocationChosen = $LocationList[$i];
				}
				echo "<br />To sign up for an appointment:</b><br /><ol>\n";
				if ($LocationList[0] > 1) {
					echo "<li>Select the location you want to consider from the list on the left.";
					if ($ShowDagger) echo "<br />(Locations marked with a &quot;&dagger;&quot; will need to speak with you first.)";
					echo "</li>\n";
				}
				echo "<li>Click on a green time in the list below.</li>\n";
				echo "<li>In the information box that appears, enter your (and spouse&apos;s) names. (If your phone number or email address needs to be changed, please do so from the <a href=\"index.php\">login page</a>.)</li>\n";
				echo "<li>In the notes section, indicate which year (if not the current year) and if it is an amended return. Also, enter any other information you think we might need (interpreter, access issues, alternative phone, etc).</li>\n";
				echo "<li>Click on the &quot;Save&quot; button to finalize the appointment.</li>\n";

				// Check to be sure email notification is enabled
				if ($LocationChosen > 0) {
					$msg = @$LocationMessage[$LocationLookup["S" . $LocationChosen]];
					if (substr($msg, 0, 4) != "NONE") {
						echo "<li>You will be sent confirmation of your appointment to the email address you entered. (Check your SPAM/junk mail folder too.)</li>\n";
					}
				}

				echo "</ol>\n";

				// Add the site's instruction block
				if ($LocationChosen > 0) {
					$Loc = $LocationLookup["S" . $LocationChosen];
					if ($Loc) $msg = $LocationInstructions[$Loc];
					if ($msg) {
						$msg = Add_Shortcodes($Loc, $msg);
						echo "<div class='custCB'>";
						$LocName = $LocationName[$Loc];
						echo "<b>Additional information for the $LocName:</b><br />";
						echo "<div style='padding-left: 1em;'>";
						echo $msg;
						echo "</div></div><br />";
					}
				}
			}
			else {
				echo "<br /><br />Sorry, no Tax-Aide sites are currently accepting appointments on line.<br /><br />\n";
			}
			echo "</div>\n";

			// If signed up, show a list of appointments for this user
			echo "<div id='custList'>\n";
			echo "<b>You are scheduled at the following time(s):</b><br />\n";
			if ($CustEList == "") {
				echo "(No appointments scheduled yet.";
				$inst = (($LocationShow[0] > 0) ? "Choose one below.)" : ")" );
				echo $inst;
			}
			else {
				echo $CustEList;
				// count the number of colons (time) in the list to see if reservation limit was reached
				if ($LocationChosen) {
					$Loc = $LocationLookup["S" . $LocationChosen];
					$InetLimited = ((substr_count($CustEList, ":")/2) >= $LocationInetLimit[$Loc]);
					$Teststr = "callback list at the " . $LocationName[$Loc];
					$onCallback = ((substr_count($CustEList, $Teststr) > 0));
				}
			}
			if ($CustPList != "") {
				echo "<br /><b>Other appointments made from your same phone number:</b><br />\n";
				echo $CustPList;
			}
			echo "<br /></div><br />\n";

			// If no locations are available, stop here
			if ($LocationShow[0] == 0) return;
		}

		// Fetching from the database table.
		$UsedColumns = 0;
		$OldDate = "";
		$OldMonth = "";
		$OldTime = "";
		$OldLocation = 0;
		$OpenSlots = 0;
		$OpenSlotNumber = 0;
		$OpenLocationIndex = 0;
		$query = "SELECT * FROM $APPT_TABLE";
		$query .= " ORDER BY `appt_date`, `appt_location`, `appt_time`, `appt_wait`";
		$appointments = mysqli_query($dbcon, $query);

		// Add option and color key line
		//echo "<center>\n"; // not needed
		if ($ApptView == "ViewSummary") {
			echo "<div><table id='summary_table_key'>\n";
			echo "<tr><td>\n";
			// Show the all dates option only to appt managers
			if ($MaxPermissions & ($MANAGER | $ADMINISTRATOR)) {
				echo "<input id='sumOpt' type='checkbox' ";
				if (@$_SESSION["SummaryAll"]) echo "checked='checked' ";
				echo "onchange='Change_SummaryAll()' />&nbsp;Show&nbsp;earlier&nbsp;dates";
			}
			echo "</td>\n";
			echo "<td>
				<span class='apptKey' style='border:0; width:4.5em;'>Color&nbsp;Key:</span>
				<span class='apptKey apptOpen'><center>Open</center></span>
				<span class='apptKey apptPartOpen'><center>Some&nbsp;reserved</center></span>
				<span class='apptKey apptWarn'><center>All&nbsp;reserved</center></span>
				<span class='apptKey apptFull'><center>Full</center></span></td></tr>\n";
			echo "</table></div><hr />";
			echo "<div class='slotsummary'>\n";
		}
		else {
			echo "<div class='slotuser'>\n";
		}


		// Start a table
		echo "<table id='summary_table' class='apptTable'>\n";

		while ($row = @mysqli_fetch_array($appointments)) {
			$Date = $row["appt_date"];
			$Time = $row["appt_time"];
			$Location = $row["appt_location"];
			$DateTimeLoc = $Date . $Time . $Location;
			$Name = htmlspecialchars_decode($row["appt_name"] ?? '');
			$Phone = $row["appt_phone"];
			$Tags = htmlspecialchars_decode($row["appt_tags"] ?? '');
			$Need = htmlspecialchars_decode($row["appt_need"] ?? '');
			$Info = htmlspecialchars_decode($row["appt_info"] ?? '');
			$Status = htmlspecialchars_decode($row["appt_status"] ?? '');
			$Type = $row["appt_type"];
			$Email = $row["appt_email"];
			$Appt = $row["appt_no"];
			@$LocationIndex = $LocationLookup["S" . $Location];
			$NoTR = true; // suppresses the first </tr>

			if (isset($LocationName[$LocationIndex])
			and (isset($LocationShow[$LocationIndex]))
			and ($LocationShow[$LocationIndex] > 0)
			and (($ApptView != "ViewUser") OR ($LocationIsOpen[$LocationIndex]))
			and (($Date >= $TodayDate) or @$_SESSION["SummaryAll"] or ($Date == $NullDate))) {

				// Change restricted site to callback if more on CB list than slots available
				if (($LocationIndex > 0)
				AND ($LocationInet[$LocationIndex] == "R")
				AND (@$LocationCBList[$LocationIndex] >= @$LocationEmpty[$LocationIndex])) {
					$LocationInet[$LocationIndex] = "C";
				}

				// Find an empty callback slot if this is a self-scheduled callback appointment
				if ($UserHome == 0) {
					// If site only allows sign-up for Callback list, just add a button to that effect
					if (($Time == $NullTime) and ($Name == "") and ($Type != $DeleteCode)) {
						$OpenSlotNumber = $Appt;
						$OpenLocationIndex = $LocationIndex;
						if (($Appt > 0) and $LocationIndex and ($LocationInet[$LocationIndex] == "C")) {
							echo "</table>\n";
							//echo "</center>\n";
							echo "<div id='custCB'>\n";
							echo "<br />The " . $LocationName[$LocationIndex] . " needs to speak with you before scheduling an appointment.";
							echo "<br />Please click on the following button to give us your contact information:";
							echo "<br /><button id='custButton' onclick='Add_Appointment($Appt, \"$NullDate\", \"$NullTime\", \"$NullDate\", \"$NullTime\");'>Click to request a callback</button>\n";
							echo "</div>\n";
							return;
						}
					}
				}

				if ($Date != $NullDate) {
					$ShowTime = Format_Time($Time, false);
					$ShowDate = Format_Date($Date, true); // set $MON which is global

					if ($Date != $OldDate) {
						if ($MON != $OldMonth) {
							if (! $NoTR) echo "</tr>\n"; // close the prior row but not on the first change
							//if (! $InetLimited) echo "<tr>\n<td class='calMonth center'>$MON</td>\n<td>";
							if (! $InetLimited) echo "<tr>\n<td class='calMonth center'>$MON</td>\n<td>";
							if ($LocationIndex) {
								$LTitle = $LocationName[$LocationIndex];
								$LTitle = Add_CB_Status($LTitle, $LocationIndex);
							}
							else $LTitle = "";
							if (! $InetLimited) echo "<div class='apptGroup apptGroupSummary'>" . $LTitle . "</div>\n";
							$OldLocation = $Location;
							$OldMonth = $MON;
						}

						$myclass = "";
						$ApptAvail = @$DateList[$Date];
						if ($Date == $FirstSlotDate) {
				    			$myclass = ($ApptAvail > 0) ? " apptOpen" : " apptFull noSelect";
						}
						if ($ApptView == "ViewSummary") {
							echo "</td></tr>\n<tr>\n";
							$clickop = "";
							$myclass = "schedDateSummary";
							if ($SitePermissions["S" . $Location] & $ADD_APP) {
								$clickop = "onclick='New_Date(\"" . $Date . "\", 1)'";
							}
							else {
								$myclass = "noSelect"; // hide pointer
							}
							echo "<td $clickop class='" . $myclass . "'>$ShowDate</td>\n<td>";
						}
						else { // $ApptView is "ViewUser"
							// only show dates that have open appointments
							if (! $InetLimited and (@$DateList[$Date] > 0) and ($Date >= $FirstSlotDate)) {
								echo "</td></tr>\n<tr class='schedDateUser'>\n";
								echo "<td>$ShowDate</td>\n<td>";
							}
						}
						$OldDate = $Date;
						$OldTime = "";
					}

					if ($Location != $OldLocation) {
						$LTitle = $LocationName[$LocationIndex];
						$LTitle = Add_CB_Status($LTitle, $LocationIndex);
						echo "<div class='apptGroup apptGroupSummary'>" . $LTitle . "</div>\n";
						$OldLocation = $Location;
						$OldTime = "";
					}

					if ($Time != $OldTime) {
						if (@$DateList[$DateTimeLoc] OR @$_SESSION["SummaryAll"]) {
							$DTBCount = +@$DateList[$DateTimeLoc . "Busy"];
							$DTCount = +@$DateList[$DateTimeLoc . "Count"];
							$DTRCount = +@$DateList[$DateTimeLoc . "ResCount"];
							$DTOCount = $DTCount - $DTRCount;
							$ClickToAdd = "onclick=\"Add_Appointment('" . $DateList[$DateTimeLoc] . "', '$Date', '$Time', '$ShowDate', '$ShowTime')\"";
							$DClass = "apptOpen";

							if ($ApptView == "ViewUser") {
								if ($DTOCount) {
									$title = "title=\"There " . isare($DTOCount) . " $DTOCount open appointment" . (($DTOCount == 1) ? '' : 's') . " available for this time period.\"";
									$DXCount = $DTOCount;
									$OpenSlots += $DTOCount;
								}
							}
							else {  // $ApptView is "ViewSummary"
								// Determine display class
								if (+$DTRCount > 0) $DClass = "apptPartOpen"; // some reserved
								if (+$DTRCount == $DTCount) $DClass = "apptWarn"; // all reserved
								if ((+$DTCount == 0) OR (($Date < $TodayDate) AND (! @$_SESSION["SummaryAll"]))) {
									$DClass = "apptFull";
								}

								$UseReservedAllowed = ($LocationSumRes[$LocationIndex] != "");
								$CanUseReserved = ($MaxPermissions & $USE_RES);
								$DoNotUseReserved = (($DClass == "apptWarn")
									AND ((! $UseReservedAllowed) OR (! ($CanUseReserved))));
								$DXCount = (($UseReservedAllowed) ? $DTCount : $DTOCount) + 0;
								$DTHeader = "";
								$DTTitle = "";
								if ($DTOCount) $DTTitle = "$DTOCount open";
								if ($DTRCount) $DTTitle .= (($DTTitle) ? "\n" : "") . "$DTRCount reserved";
								if ($DTBCount) $DTTitle .= (($DTTitle) ? "\n" : "") . "$DTBCount assigned";

								switch (true) {
									case ($Date < $TodayDate):
										$DClass .= " noSelect";
										$DTHeader = "You cannot schedule appointments for an earlier date.\n";
										$ClickToAdd = "";
										break;
									case (@$SitePermissions["S" . $Location] & $ADD_APP): // permission to add appt?
										if ($DoNotUseReserved) {
											$DClass .= " noSelect";
											if ($DTRCount) {
												$DTHeader = "$DTRCount remaining appointment";
												$DTHeader .= (($DTRCount == 1) ? " is " : "s are ") . "reserved.";
												if ($CanUseReserved) {
													$DTHeader .= "\nYou must go to the date view to use reserved appointment times.\"";
												}
												else {
													$DTHeader .= "\nYou do not have permission to use reserved slots.\"";
												}
												$DTOText = $DTRText = $DTBText = "";
											}
											$ClickToAdd = "";
										}
										$OpenSlots += $DTOCount;
										break;
									default:
										$DClass .= " noSelect";
										$DTHeader = "You do not have permission to schedule appointments at this site.\n";
										$ClickToAdd = "";
								}

								$title = "title=\"$DTHeader" . (($DTHeader) ? "\n" : "") . $DTTitle . "\"";
							}
							if (! $InetLimited) {
								echo "\t<div class='apptFloat $DClass' $ClickToAdd $title>$ShowTime ($DXCount)</div>\n";
							}
						}
						else {
							if ($ApptView == "ViewSummary") {
								$fullclass = "apptFull";
								$title = +@$DateList[$DateTimeLoc . 'Busy'] . " assigned";
								echo "\t<div class='apptFloat $fullclass noSelect' title='$title'>$ShowTime</div>\n";
							}
							//else { // ViewUser
							//	$title = "All appointments have been filled.";
							//	$fullclass = "apptFull";
							//}
						}
						$OldTime = $Time;
					}
				}
			}
		}
		echo "</table>\n";
		//echo "</center>\n";

		switch (true) {
			case (($UserHome == 0 ) and ($SingleSite == "")): // no location was checked
				echo "<br /><div id='custCB'>\n";
				echo "<br />Please select a site from the left hand column.";
				echo "<br />&nbsp;</div>\n";
				break;
			case (($UserHome == 0) and (! $OpenSlots)):
				echo "<div id='custCB'>\n";
				echo "<br /><b>The " . $LocationName[$LocationLookup["S" . $SingleSite]] . " has no open appointments at this time.</b>";
				if ($onCallback) {
					echo "<br /><br />You are on the callback list should an opening become available,";
					echo "<br />&nbsp;";
				}
				else if ($LocationIsOpen[$LocationLookup["S" . $SingleSite]]) {
					echo "<br /><br />If you would like to be placed on the callback list should an opening become available,";
					echo " please click on the following button to give us your contact information:";
					echo "<br /><button id='custButton' onclick='Add_Appointment($OpenSlotNumber, \"$NullDate\", \"$NullTime\", \"$NullDate\", \"$NullTime\");'>Click to request a callback</button>\n";
				}
				else echo "<br />&nbsp;";
				echo "</div>\n";
				break;
			case (($UserHome == 0) and $InetLimited):
				echo "<br /><div id='custCB'>\n";
				echo "<br /><b>You will need to cancel a current appointment if you wish to make another.</b>";
				if ($onCallback) {
					echo "<br /><br />You are on the callback list. Someone should be contacting you shortly.";
					echo "<br />&nbsp;";
				}
				else {
					echo "<br /><br />If you need additional appointments, please request a callback.";
					echo "<br />Click on the following button to give us your contact information:";
					echo "<br /><button id='custButton' onclick='Add_Appointment($OpenSlotNumber, \"$NullDate\", \"$NullTime\", \"$NullDate\", \"$NullTime\");'>Click to request a callback</button>\n";
				}
				echo "</div>\n";
				break;
			case (($UserHome == 0) and ($OpenSlotNumber > 0)):
				echo "<br /><div id='custCB'>\n";
				if ($onCallback) {
					echo "<br />You are on the callback list. Someone should be contacting you shortly.";
					echo "<br />&nbsp;";
				}
				else {
					echo "<br />If you are not sure you need an appointment and would like to speak to someone to help decide or answer a question,";
					echo " please click on the following button to give us your contact information:";
					echo "<br /><button id='custButton' onclick='Add_Appointment($OpenSlotNumber, \"$NullDate\", \"$NullTime\", \"$NullDate\", \"$NullTime\");'>Click to request a callback</button>\n";
				}
				echo "</div>\n";
				break;
		}

		echo "</div>";
	} // End of type summary/user views

	$appointments = []; // release memory
}

//===========================================================================================
function List_Group() {
// Sorts daily view output by named, blank, then RESERVED slots
//$ApptGroupItem = array($GroupType, $Appt, $SlotNumber, $SlotIndex, $myclass, $Name, $Phone, $Tags, $Need, $Info, $Status);
//===========================================================================================
	global $ApptGroupList, $ApptGroupListIndex;
	
	$SlotIndex = 0;
	for ($AGLType = 1 ; $AGLType < 4 ; $AGLType++) {
		for ($AGLI = 1 ; $AGLI <= $ApptGroupListIndex ; $AGLI++) {
			$ApptGroupItem = $ApptGroupList[$AGLI];
			if ($ApptGroupItem[0] == $AGLType) {
				$SlotIndex++;
				List_Slot($ApptGroupItem[1], $ApptGroupItem[2], $SlotIndex, $ApptGroupItem[4], $ApptGroupItem[5], $ApptGroupItem[6], $ApptGroupItem[7], $ApptGroupItem[8], $ApptGroupItem[9], $ApptGroupItem[10]);
			}
		}
	}
	$ApptGroupListIndex = 0;
}

//===========================================================================================
function List_Slot($SaveAppt, $SlotNumber, $SlotIndex, $myclass, $Name="", $Phone="", $Tags="", $Need="", $Info="", $Status="") {
//===========================================================================================
	global $Debug, $Errormessage;
	global $MaxPermissions, $ApptView, $ADD_CB, $ADD_APP, $USE_RES;
	global $FormApptNo, $FormApptOldNo;
	global $RESERVED;

	// display only the most recent status note
	$Status = _Show_Chars($Status, "text");
	$a = strpos($Status, "\n");
	$FirstStatus = ($a) ? substr($Status, 0, $a) : $Status;

	// display the notes as a title and add a check mark
	$NeedTitle = "";
	$needmark = "";
	if ($Need != "") {
		$NeedTitle =  _Show_Chars(str_replace("%0A", ", ", $Need), "text"); // replace newlines with a comma
		$NeedTitle = str_replace("'", "`", $NeedTitle); // replace apostrophe with back-apos
		$NeedTitle = "title='" . $NeedTitle . "'";
		$needmark = "&#x2611;"; // checkbox symbol
	}

	// display the info as a title and add an info mark
	$InfoTitle = "";
	$infomark = "";
	if ($Info != "") {
		$InfoTitle =  _Show_Chars(str_replace("%0A", ", ", $Info), "text"); // replace newlines with a comma
		$InfoTitle = str_replace("'", "`", $InfoTitle); // replace apostrophe with back-apos
		$InfoTitle = "title='" . $InfoTitle . "'";
		$infomark = "&#x26a0;"; // warning symbol
	}

	// get rid of special character coding
	$Name = _Show_Chars($Name, "text");
	$addTags = _Show_Chars(str_replace("%0A", ", ", $Tags), "html"); // replace newlines with a comma

	// determine classes for display
	$slotclass = "";
	if ((($FormApptOldNo > 0) and ($FormApptNo == $SaveAppt))
		or (($FormApptOldNo == $SaveAppt) and ($FormApptNo == "NewDate"))) {
		$slotclass = "apptSlotMoved";
		}
	$selfclass = strpos($FirstStatus, '(USER)') ? "user_class" : "";
	if ($selfclass == "") $selfclass = strpos($Status, '(USER.)') ? "userOK_class" : "";

	// test if the user can assign slots
	$canUseRes = ($MaxPermissions & $USE_RES);
	$testres = ($Name == $RESERVED) ? ($MaxPermissions & $USE_RES) : true;
	$titleres = ($testres) ? "" : "You do not have permission to assign a reserved appointment.";
	$rowclass = ($testres) ? "" : "class=\"noSelect\"";
	$addTags = ($addTags) ? " <b>[$addTags]</b>" : "";
	
	if ((($MaxPermissions & $ADD_APP) AND $testres) 
	OR (($ApptView == "ViewCallback") AND ($MaxPermissions & $ADD_CB))) {
		echo "<tr onclick='Change_Appointment(1, $SaveAppt, $SlotNumber, $SlotIndex);'>\n";
	}
	else {
		echo "<tr $rowclass title='$titleres'>\n";
	}
	echo "\t<td class='apptSlot $slotclass'>&nbsp;$SlotIndex&nbsp;&nbsp;</td>\n";
	echo "\t<td id='apptName$SlotNumber' class='apptName $myclass'>";
	if ($ApptView == "ViewDaily") { // add the reserved icon in the name column
		$nameBlank = ($Name == "");
		$nameReserved = ($Name == $RESERVED);
		if (! $nameReserved) $Name = _Show_Chars($Name, "html");

		switch (true) {
			case ($nameBlank AND $canUseRes): // add the icon
				echo "<div class='apptNameDiv'><div class='apptNameRes' title='Reserve this slot' onclick='Change_Appointment(-1, $SaveAppt, $SlotNumber, $SlotIndex);'>R</div></div>";

				break;
			case ($nameReserved AND $canUseRes): // add RESERVED and the icon
				echo "<div class='apptNameDiv'><div class='apptReserved'>$Name</div>
					<div class='apptNameUnres'>R</div>
					<div class='apptNameUnresNot'
						title='Unreserve this slot'
						onclick='Change_Appointment(-1, $SaveAppt, $SlotNumber, $SlotIndex);'>
						<b>/</b></div>
					</div>";
				break;
			case ($nameReserved): // add RESERVED but no icon
				echo "<div class='apptNameDiv noSelect'><div class='apptReserved'>$Name</div></div>";
				break;
			default: // add the name and no icon
				echo ($Name . $addTags);
		}
		echo "</td>\n";
	}
	else echo ($Name . $addTags . "</td>\n");
	echo "\t<td id='apptPhone$SlotNumber' class='apptPhone $myclass'>$Phone</td>\n";
	echo "\t<td id='apptNeed$SlotNumber' class='apptNeed $myclass' $NeedTitle>$needmark</td>\n";
	echo "\t<td id='apptInfo$SlotNumber' class='apptNeed $myclass' $InfoTitle>$infomark</td>\n";
	echo "\t<td id='apptStatus$SlotNumber' class='apptStatus $myclass $selfclass' title='$FirstStatus'>$FirstStatus</td></tr>\n";
}

//===========================================================================================
function Add_Shortcodes($Loc, $message) {
// Adds shortcodes to message
//===========================================================================================
	global $Name, $Time, $Date, $LocationName, $LocationAddress, $LocationContact;
	global $LocationAttachHTML;
	global $SystemAttachList;
	$SiteAddress = explode("|", $LocationAddress[$Loc]);
	$message = _Show_Chars($message, "html");

	// [TPNAME], [DATE] and [TIME] are not supported for this message.
	$message = str_replace("[TPNAME]",      "", $message);
	$message = str_replace("[DATE]",        "", $message);
	$message = str_replace("[TIME]",        "", $message);
	$message = str_replace("[SITENAME]",    $LocationName[$Loc], $message);
	$message = str_replace("[ADDRESS]",     $SiteAddress[0], $message);
	$message = str_replace("[CITY]",        $SiteAddress[1], $message);
	$message = str_replace("[STATE]", 	$SiteAddress[2], $message);
	$message = str_replace("[ZIP]",         $SiteAddress[3], $message);
	$message = str_replace("[PHONE]", 	$SiteAddress[4], $message);
	$message = str_replace("[EMAIL]",       $SiteAddress[5], $message);
	$message = str_replace("[WEBSITE]",     $SiteAddress[6], $message);
	$message = str_replace("[STATESITE]",   $_SESSION["SystemURL"], $message);
	$message = str_replace("[CONTACT]",     $LocationContact[$Loc], $message);
	$message = str_replace("[ATTACHMENTS]", $LocationAttachHTML[$Loc], $message);
	for ($lax = 0; $lax < sizeof($SystemAttachList)-1; $lax++) {
		$sap = explode("=", $SystemAttachList[$lax]);
		$testShortcode = "[$sap[0]]";
		$replacement = "<a href=\"$sap[1]\">$sap[0] ($sap[1])</a>";
		$message = str_replace($testShortcode, $replacement, $message);
	}	
	return $message;
}
?>
