<?php
// Version 9.08
// 	Scheduler without permission to use reserved slots can in Summary view
// 	Allow scheduler with permission to select reserved slots in Summary view
// 	Selection of a person after a search doesn't highlight & focus on their line
// Version 9.02
// 	Added elements and classes to some elements for the new heartbeat function
// 	Also classes to make reports sort properly
// Version 9.00
// 	Added new option to prevent User adding self to CB list
// 	Fixed not showing appointments as scheduled but in phone match list
// Version 8.03
// 	Highlight the appt line changed like when moved
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
	global $ApptMatch;
	global $DeleteCode;
	global $FormApptOldSlot;
	global $CustEList;
	global $CustPList;
	global $Site;
	global $ShowDagger;
	global $ADD_APP, $ADD_CB, $USE_RES;
	global $VIEW_APP, $VIEW_CB;
	global $ADMINISTRATOR, $MANAGER;
	global $WaitSequence;
	global $LastSlotNumber;
	global $SiteListCount;
	global $HeaderText;
	global $ApptView;
	global $UserFirst;
	global $UserLast;
	global $UserEmail;
	global $UserPhone;
	global $UserHome;
	global $UsedCBSlots;
	global $YR, $MO, $DY, $YMD, $MON, $DOW;
	global $ApptGroupList, $ApptGroupListIndex;
	global $RESERVED;
	global $isDeleted, $isArchived;
	global $MaxPermissions;
	global $SaveMatchLoc;
	global $InfoMatch;
		
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
		$ApptMatch = "";
		$LastSlotNumber = 0;
		$WaitSequence = 0;

	// -----------------------------------------------------------------------------------------
	if ($ApptView == "ViewDeleted") {
	// -----------------------------------------------------------------------------------------
		$header = "<div class='slotlist' style=\"top: 0;\">\n";
		$header .= "<table id='daily_table' class='apptTable'>";
		//$header .= "<tr class='apptGroup'>\n";
		//$header .= "<th colspan='2' class='sticky left'>Deleted List:</th>\n";
		//$header .= "<th class='center apptPhone sticky'></th>\n";
		//$header .= "<th class='apptNeed sticky'></th>\n";
		//$header .= "<th class='apptNeed sticky'></th>\n";
		//$header .= "<th class='apptStatus sticky'></th></tr>\n";
		echo $header;

		//Fetching from the database table.
		$query = "SELECT * FROM $APPT_TABLE";
		$query .= " WHERE `appt_date` = '$NullDate'";
		$query .= " AND `appt_type` LIKE 'D%'";
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
			$Location = $row["appt_location"];

			$ThisSite = $Site["S" . $Location];
			$Location10digreq = $ThisSite["10dig"];

			$apptType = explode("|", $row['appt_type']);
			$isDeleted = $apptType[0] ?? "";
			$isArchived = $apptType[1] ?? "";
			if($isArchived) continue;
			
			// Is the user allowed to view the deleted list?
			if (! ($ThisSite["Permissions"] & $VIEW_APP)) continue;

			if ($ThisSite["Show"]) {

				// New location header
				if ($Location != $OldLoc) { // Add a new group header
					echo "<tr class='apptLoc'>\n";
					echo "<th class='sticky left' colspan='2'>Deleted List (" . $ThisSite["Name"] . ")</th>";
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
				$InfoMatch = "";

				// Add tags to the name if they are present
				List_Slot($Appt, $SlotNumber, $SlotIndex, $myclass, $Name, $Phone, $Tags, $Need, $Info, $Status, $Location);

				// Add the record to the arrays
				$LastSlotNumber = $SlotNumber;
				$ApptNo .= ", \"$Appt\"";
				$ApptName .= ", \"$Name\"";
				$ApptPhone .= ", \"$Phone\"";
				$ApptEmail .= ", \"$Email\"";
				$ApptSite .= ", \"$Location\"";
				$Appt10dig .= ", \"$Location10digreq\"";
				$ApptTags .= ", \"$Tags\"";
				$ApptNeed .= ", \"$Need\"";
				$ApptInfo .= ", \"$Info\"";
				$ApptStatus .= ", \"$Status\"";
				$ApptTimeDisplay .= ", \"$NullTime\"";
				$ApptMatch .= ", \"$InfoMatch\"";
			}
		}

		echo "</table>\n";
		echo "</div>\n";
		unset($appointments); // release memory

	} // End of Deleted view
		
	// -----------------------------------------------------------------------------------------
	if (($ApptView == "ViewDaily") or ($ApptView == "ViewCallback")) { // Daily view
	// -----------------------------------------------------------------------------------------

		$top = 0;
		if ($ApptView == "ViewDaily") {
			$ShowDate = Format_Date($FirstSlotDate, true); // set $MON which is global
			$HeaderText = "Appointments for $ShowDate:";
			echo "<div><b>$HeaderText</b></div>\n";
			$top = "1.2em";
		}

		//Start the table
		echo "<div class='slotlist' style=\"top:$top;\">\n";
		echo "<table id='daily_table' class='apptTable'>\n";
		$AddSlotLoc = "";
		$HomeSite = $Site["S" . $UserHome];
		if ($ApptView == "ViewCallback") {
			$FirstSlotDate = $NullDate;
			//$HeaderText = "Callback List:";
			//echo "<tr class='apptGroup'>\n";
			//echo "<th colspan='6' class='sticky left'>$HeaderText</th>\n";
		}
		else {
			if ($FirstSlotDate == $NullDate) { // End the table
				$HeaderText = "No appointments found";
				echo "<tr class='apptGroup'><td colspan='7'>$HeaderText</td></tr>\n";
				echo "</table>\n";
				echo "</div>\n";
				return;
			}
		}

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
		$SaveAppt = array(); // Stores the appt number of the last empty callback list appt encountered
		$ApptGroupList = array();
		$ApptGroupListIndex = 0;
		$SaveWaitSequence = 0;
		while($row = @mysqli_fetch_array($appointments)) {
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
			$WaitSequence = $row["appt_wait"];
			$Location = $row["appt_location"];

			$ShowThisEntry = true;
			$ThisSite = $Site["S" . $row["appt_location"]];
			$Location10digreq = $ThisSite["10dig"];

			$apptType = explode("|", $row['appt_type']);
			$isDeleted = $apptType[0] ?? "";
			$isArchived = $apptType[1] ?? "";
			if ($isArchived) continue;

			// Is this person allowed to see entries for this site?
			// OK to list the appointments?
			if ($ApptView == "ViewDaily") $OKtoView = $ThisSite["Permissions"] & $VIEW_APP;
			else $OKtoView = $ThisSite["Permissions"] & $VIEW_CB;
			if (! $OKtoView) continue;

			if ($ThisSite["Show"] and (! $isDeleted)) {

				// New time
				if ($Time == $NullTime) { // This is a callback list entry
					if ($Name == "") {
						// Skip if no name has been assigned. We'll add one later
						$ShowThisEntry = false;
						// Save the appt number of the blank callback entry for the site
						$SaveAppt[$Location] = $Appt; 
						$SaveWaitSequence = $WaitSequence;
					}
					if ($ApptView == "ViewCallback") $ShowTime = "Callback List";
				}
				else {
					$ShowTime = Format_Time($Time, false);
				}
	
				// New location and/or time ?
				if (($Time != $OldTime) OR ($Location != $OldLoc)) {

					// List the previous group
					if ($OldLoc != 0) List_Group($OldLoc);

					// If a callback list, add an empty record at the end of the group
					if (($OldTime == $NullTime) and ($OldLoc != 0)) { // end of a site callback list
						if ($OldLoc == 0) {
							$OldLoc = $Location;
							$OldLocIndex = $Location;
						}
						// update the empty record if one exists
						$SlotNumber++; // Index number into a local array of displayed appointments
						$SlotIndex++; // Visual index on the screen, resets on change of group
						if ($SaveWaitSequence < $_SESSION["MaxWaitSequence"]) $SaveWaitSequence = ++$_SESSION["MaxWaitSequence"];
						if ($SAOL = $SaveAppt[$OldLoc]) {
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
							$query .= " WHERE `appt_no` = $SAOL";
							mysqli_query($dbcon, $query);
						}

						$myclass = "apptOpen";
						$InfoMatch = "";

						List_Slot($SaveAppt[$OldLoc], $SlotNumber, $SlotIndex, $myclass, "", "", "", "", "", "", $OldLoc);

						// Add the slot to the site arrays
						$LastSlotNumber = $SlotNumber;
						$ApptNo .= ", \"$SAOL\"";
						$ApptName .= ", \"\"";
						$ApptPhone .= ", \"\"";
						$ApptEmail .= ", \"\"";
						$ApptSite .= ", \"$OldLoc\"";
						$Appt10dig .= ", \"$Location10digreq\"";
						$ApptTags .= ", \"\"";
						$ApptNeed .= ", \"\"";
						$ApptInfo .= ", \"\"";
						$ApptStatus .= ", \"\"";
						$ApptTimeDisplay .= ", \"$ShowTime\"";
						$ApptMatch .= ", \"$InfoMatch\"";

						// Indicate the empty record has been used
						$SaveAppt[$OldLoc] = 0;
					}

					$TimeHeader = "$ShowTime (" . $ThisSite["Name"] . ")";

					// Add + and - buttons to the header for Managers and Administrators
					if (($ApptView == "ViewDaily") AND ($ThisSite["Permissions"] & ($MANAGER | $ADMINISTRATOR))) {
						$SiteIndex = $ThisSite["Index"];
						$Stime = substr($Time, 0, 5);
						$TimeHeader .= " <div class=\"Do1Slot\"";
						$TimeHeader .= " onclick=\"Do1Slot('add', '$SiteIndex', '$FirstSlotDate', '$Stime');\"";
						$TimeHeader .= " title=\"Add a new appointment slot for " . $ShowTime . ".\">+</div>";
						$TimeHeader .= " <div class=\"Do1Slot\"";
						$TimeHeader .= " onclick=\"Do1Slot('rmv', '$SiteIndex', '$FirstSlotDate', '$Stime');\"";
						$TimeHeader .= " title=\"Remove an unused appointment slot for " . $ShowTime . ".\">-</div>";
						if (($AddSlotLoc == "") OR ($SiteIndex == $UserHome)) $AddSlotLoc = $SiteIndex;
					}

					// Print the header
					$header = "<tr class='apptLoc bold left'><th class='sticky' colspan='2'>$TimeHeader</th>\n";

					// Add the Callback list count to the header
					$TimeHeaderCB = Add_CB_Status("", $ThisSite);
					$header .= "<th class='center apptPhone sticky'>Phone</th>\n";
					$header .= "<th class='apptNeed sticky'>Note</th>\n";
					$header .= "<th class='apptNeed sticky'>Info</th>\n";
					$header .= "<th class='apptStatus sticky'>Status</th></tr>\n";
					echo $header;

					// Prepare to detect the next time/location grouo
					$OldTime = $Time;
					$OldLoc = $Location;
					$SlotIndex = 0;
				}

				if ($ShowThisEntry) { 
					$InfoMatch = "";
					$SlotNumber++; // Index number into a local array of displayed appointments
					$SlotIndex++; // Visual index on the screen, resets to 1 on change of group

					$myclass = ($Name == "") ? " apptOpen" : "apptInUse"; // apptOpen class sets green color
					if ($ApptView == "ViewDaily") {
						$GroupType = 1; // has a name in it
						if ($Name == "") $GroupType = 2;
						if ($Name == $RESERVED) $GroupType = 3;
						$ApptGroupItem = array($GroupType, $Appt, $SlotNumber, $SlotIndex, $myclass, $Name, $Phone, $Tags, $Need, $Info, $Status, $Location);
						$ApptGroupListIndex++;
						$ApptGroupList[$ApptGroupListIndex] = $ApptGroupItem;
						$ApptGroupItem = $ApptGroupList[$ApptGroupListIndex];
						// List_Slot will be called via List_Group when the location or time changes
					}
					else { // ViewCallback
						// Add a message about other appointments
						if (($Phone != "") and ($Phone != "000-000-0000")) {
							if (($size = sizeof(($marr = $SaveMatchLoc["Phone"][$Phone]))) > 1) {
								for ($j = 0; $j < $size; $j++) {
									$m = explode("|", $marr[$j]);
									if ($m[0] != $ThisSite["Index"]) {
										$mboth = ($m[1] == "CE") ? " and email" : "" ;
										$mtype = (($m[1] == "C") || $mboth) ? "is on the callback list" : "has an appointment" ;
										$InfoMatch .= "<br /> - $m[2] $mtype at the " . $Site["S" . $m[0]]["Name"] . " with the same phone number$mboth.";
									}
								}
							}
						}
						if ($Email != "") {
							if (($size = sizeof(($marr = $SaveMatchLoc["Email"][$Email]))) > 1) {
								for ($j = 0; $j < $size; $j++) {
									$m = explode("|", $marr[$j]);
									if ($m[0] != $ThisSite["Index"]) {
										$mtype = ($m[1] == "C") ? "is on the callback list" : "has an appointment" ;
										$InfoMatch .= "<br /> - $m[2] $mtype at the " . $Site["S" . $m[0]]["Name"] . " with the same email.";
									}
								}
							}
						}
						List_Slot($Appt, $SlotNumber, $SlotIndex, $myclass, $Name, $Phone, $Tags, $Need, $Info, $Status, $Location);
					}

					$LastSlotNumber = $SlotNumber;
					$ApptNo .= ", \"$Appt\"";
					$ApptName .= ", \"$Name\"";
					$ApptPhone .= ", \"$Phone\"";
					$ApptEmail .= ", \"$Email\"";
					$ApptSite .= ", \"$Location\"";
					$Appt10dig .= ", \"$Location10digreq\"";
					$ApptTags .= ", \"$Tags\"";
					$ApptNeed .= ", \"$Need\"";
					$ApptInfo .= ", \"$Info\"";
					$ApptStatus .= ", \"$Status\"";
					$ApptTimeDisplay .= ", \"$ShowTime\"";
					$ApptMatch .= ", \"$InfoMatch\"";
				}
			}
		}

		List_Group($OldLoc);
	
		if (($ApptView == "ViewCallback") and ($OldLoc != "")) { // end of last callback list, add a final blank slot
			$SlotNumber++; // Index number into a local array of displayed appointments
			$SlotIndex++; // Visual index on the screen, resets on change of group
			if ($OldLoc == 0) $OldLoc = $Location;
			if ($SaveWaitSequence < $_SESSION["MaxWaitSequence"]) $SaveWaitSequence = ++$_SESSION["MaxWaitSequence"];
			// add a blank record if one exists
			if ($SAOL = $SaveAppt[$OldLoc]) {
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
				$query .= " WHERE `appt_no` = $SAOL";
				mysqli_query($dbcon, $query);
			}

			$myclass = "apptOpen";
			$InfoMatch = "";

			List_Slot($SaveAppt[$OldLoc], $SlotNumber, $SlotIndex, $myclass, "", "", "", "", "", "", $OldLoc);

			// Add the slot to the site arrays
			$LastSlotNumber = $SlotNumber;
			$ApptNo .= ", \"$SAOL\"";
			$ApptName .= ", \"\"";
			$ApptPhone .= ", \"\"";
			$ApptEmail .= ", \"\"";
			$ApptSite .= ", \"$OldLoc\"";
			$ApptTags .= ", \"\"";
			$ApptNeed .= ", \"\"";
			$ApptInfo .= ", \"\"";
			$ApptStatus .= ", \"\"";
			$ApptTimeDisplay .= ", \"$ShowTime\"";
			$ApptMatch .= ", \"\"";
			$SaveAppt[$OldLoc] = 0; // We used it
		}

		echo "</table>\n";

		// Add the new slot options for the callback list
		if ($ApptView == "ViewCallback") {
			if (($MaxPermissions & $ADD_CB) | $ADMINISTRATOR | $MANAGER) {
				echo "<br /><br />\n";
				echo "Add <input id='SlotsToAdd' type='number' style='width: 3em' /> additional reserved entries for the ";
				echo "<select id=\"LocationToAdd\">\n";
				echo "<option value=\"0\">Choose a site</option>\n";
				if ($OldLoc == "") $OldLoc = $UserHome;
				List_Locations($ADD_CB);
				echo "</select>\n";
				echo "<button onclick='AddCBSlots()'>(Click to add)</button>\n";
			}
			else if ($ThisSite["Show"] AND ($ThisSite["Permissions"] & $ADD_CB)) {
				echo "<br /><br />\n";
				echo "<button onclick='AddCBSlots()'>Click to add...</button>\n";
				//NFGecho "<input id='SlotsToAdd' type='number' size='1' maxlength='2' /> additional blank entries for the " . $LocationName[$HomeIndex];
				echo "<input id='SlotsToAdd' type='number' size='1' maxlength='2' /> additional blank entries for the " . $Site["S" . $UserHome]["Name"];
			}
		}
		
		// Add the new time group options to the daily view
		if (($ApptView == "ViewDaily") AND ($MaxPermissions & ($ADMINISTRATOR | $MANAGER))) {
			echo "<br /><br />\n";
			if ($AddSlotLoc == "") $AddSlotLoc = $UserHome;
			echo "Add a new time group at\n";
			echo "<input id='TimeToAdd' type='time' /> with\n";
			echo "<input id='NewSlotsToAdd' type='number' style='width:3em;' value='1'> slot(s) to the\n";
			echo "<select id=\"LocationToAdd\">\n";
			echo "<option value=\"0\">Choose a site</option>\n";
			List_Locations($ADD_APP);
			echo "</select>\n";
			echo "<button onclick='AddNewTime(\"$FirstSlotDate\")'>(Click to add)</button>\n";

		}

		// Add placeholder for displaying debugging responses from AJAX heartbeat
		if ($ApptView == "ViewDaily") {
			echo '<div id="xmlTest"></div>';
		}

		// Close the view div
		echo "</div>\n";


	} // End of Daily/Callback views

	// -----------------------------------------------------------------------------------------
	if (($ApptView == "ViewUser") OR ($ApptView == "ViewSummary")) { // User or Summary views 
	// -----------------------------------------------------------------------------------------

		$InetLimited = false;
		$onCallback = false;
		$InetLocationSelected = 0;
		if ($ApptView == "ViewUser") { // Show an instruction header
			echo "<div class='custTable'>\n";
			echo "<b>Welcome " . _Show_Chars($UserFirst, "html") . ",";
			if ($SiteListCount > 0) {
				foreach ($Site as $SiteKey => $ThisSite) {
					if ($ThisSite["Show"]) $InetLocationSelected = $ThisSite["Index"];
				}
				echo "<br />To sign up for an appointment:</b><br /><ol>\n";
				if ($InetLocationSelected === 0) {
					echo "<li>Select the location you want to consider from the list on the left.";
					if ($ShowDagger) echo "<br />(Locations marked with a &quot;&dagger;&quot; will need to speak with you first.)";
					echo "</li>\n";
				}
				echo "<li>Click on a green time in the list below.</li>\n";
				echo "<li>In the information box that appears, enter your (and spouse&apos;s) names. (If your phone number or email address needs to be changed, please do so from the <a href=\"index.php\">login page</a>.)</li>\n";
				echo "<li>In the notes section, indicate which year (if not the current year) and if it is an amended return. Also, enter any other information you think we might need (interpreter, access issues, alternative phone, etc).</li>\n";
				echo "<li>Click on the &quot;Save&quot; button to finalize the appointment.</li>\n";

				// Add a final step if email notification is enabled
				if ($InetLocationSelected) {
					$ThisSite = $Site["S" . $InetLocationSelected];
					$msg = $ThisSite["Message"];
					if (substr($msg, 0, 4) != "NONE") {
						echo "<li>You will be sent confirmation of your appointment to the email address you entered. (Check your SPAM/junk mail folder too.)</li>\n";
					}
				}

				echo "</ol>\n";

				// Add the site's instruction block
				if ($InetLocationSelected) {
					$msg = $ThisSite["Instructions"];
					if ($msg) {
						$msg = Add_Shortcodes($InetLocationSelected, $msg);
						echo "<div class='custCB'>";
						echo "<b>Additional information for the" . $ThisSite["Name"] . ":</b><br />";
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
			echo "<b>You are currently scheduled at the following time(s):</b><br />\n";
			if ($CustEList == "") {
				echo "(No appointments scheduled yet.";
				$inst = (($SiteListCount > 0) ? " Choose one below.)" : ")" );
				echo $inst;
			}
			else {
				echo $CustEList;
				// count the number of colons (time) in the list to see if reservation limit was reached
				if ($InetLocationSelected) {
					$InetLimited = ((substr_count($CustEList, ":") / 2) >= $ThisSite["InetLimit"]);
					$Teststr = "callback list at the " . $ThisSite["Name"];
					$onCallback = ((substr_count($CustEList, $Teststr) > 0));
				}
			}
			if ($CustPList != "") {
				echo "<br /><b>Other appointments made from your same phone number:</b><br />\n";
				echo $CustPList;
			}
			echo "<br /></div><br />\n";
			// If no locations are available, stop here
			if ($SiteListCount == 0) return;
		}

		// Fetching from the database table.
		$UsedColumns = 0;
		$OldDate = "";
		$OldMonth = "";
		$OldTime = "";
		$OldLocation = 0;
		$OpenSlots = 0;
		$OpenSlotNumber = 0;
		$query = "SELECT * FROM $APPT_TABLE";
		$query .= " ORDER BY `appt_date`, `appt_location`, `appt_time`, `appt_wait`";
		$appointments = mysqli_query($dbcon, $query);

		// Add option and color key line
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

		// Start a table of appointments
		echo "<table id='summary_table' class='apptTable'>\n";

		while ($row = @mysqli_fetch_array($appointments)) {
			$Date = $row["appt_date"];
			$Time = $row["appt_time"];
			$Location = $row["appt_location"];
			$DateTimeLoc = $Date. $Time. $Location;
			$Name = htmlspecialchars_decode($row["appt_name"] ?? '');
			$Phone = $row["appt_phone"];
			$Tags = htmlspecialchars_decode($row["appt_tags"] ?? '');
			$Need = htmlspecialchars_decode($row["appt_need"] ?? '');
			$Info = htmlspecialchars_decode($row["appt_info"] ?? '');
			$Status = htmlspecialchars_decode($row["appt_status"] ?? '');
			$Email = $row["appt_email"];
			$Appt = $row["appt_no"];

			$ThisSite = $Site["S" . $Location];
			if ($Location == 1) continue; // Should not happen but...

			$NoTR = true; // suppresses the first row terminator "</tr>"

			$apptType = explode("|", $row['appt_type']);
			$isDeleted = $apptType[0] ?? "";
			$isArchived = $apptType[1] ?? "";
			
			// Skip records that should not be displayed
			if ($isArchived) continue; // Record is archived
			if (! $ThisSite["Show"]) continue; // Checkbox is not checked
			if (($ApptView == "ViewUser") AND ($Location != $InetLocationSelected)) continue; // Inet site not selected
			if (($Date < $TodayDate) AND (! ($_SESSION["SummaryAll"] ?? "")) AND ($Date != $NullDate)) continue; // Earlier than today
			//error_log("RECORD: $Appt, Date=$Date at $Time, Location=$Location, Show=".$ThisSite["Show"].", $Name"); /* DEBUG */

			// Change restricted site to callback if more on CB list than slots available
			if (($ThisSite["Inet"] == "R") AND ($ThisSite["AvailCBCount"] >= $ThisSite["AvailCount"])) {
				$ThisSite["Inet"] = "C";
			}

			// Find an empty callback slot if this is a self-scheduled callback appointment
			if ($UserHome == 0) {
				// If site only allows sign-up for Callback list, just add a button to that effect
				if (($Time == $NullTime) and ($Name == "") and (! $isDeleted)) {
					$OpenSlotNumber = $Appt;
					if (($Appt > 0) and $ThisSite["Index"] and ($ThisSite["Inet"] == "C") and (! $onCallback)) {
						echo "</table>\n";
						//echo "</center>\n";
						echo "<div id='custCB'>\n";
						echo "<br />The " . $ThisSite["Name"] . " needs to speak with you before scheduling an appointment.";
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
				//error_log("DATE: $ShowDate at $ShowTime, $Date $Time"); /* DEBUG */

				if ($Date != $OldDate) {
					if ($MON != $OldMonth) {
						if (! $NoTR) echo "</tr>\n"; // close the prior row but not on the first change
						//if (! $InetLimited) echo "<tr>\n<td class='calMonth center'>$MON</td>\n<td>";
						if (! $InetLimited) echo "<tr>\n<td class='calMonth center'>$MON</td>\n<td>";
						$LTitle = $ThisSite["Name"];
						$LTitle = Add_CB_Status($LTitle, $ThisSite);
						if (! $InetLimited) echo "<div class='apptGroup apptGroupSummary'>" . $LTitle . "</div>\n";
						$OldLocation = $Location;
						$OldMonth = $MON;
					}

					$myclass = "";
					$ApptAvail = $DateList[$Date] ?? 0;
					if ($Date == $FirstSlotDate) {
			    			$myclass = ($ApptAvail > 0) ? " apptOpen" : " apptFull noSelect";
					}
					if ($ApptView == "ViewSummary") {
						echo "</td></tr>\n<tr>\n";
						$clickop = "";
						$myclass = "schedDateSummary";
						if ($ThisSite["Permissions"] & $ADD_APP) {
							$clickop = "onclick='New_Date(\"" . $Date . "\", 1)'";
						}
						else {
							$myclass = "noSelect"; // hide pointer
						}
						echo "<td $clickop class='" . $myclass . "'>$ShowDate</td>\n<td>";
					}
					else { // $ApptView is "ViewUser"
						// only show dates that have open appointments
						if (! $InetLimited and (($DateList[$Date] ?? 0) > 0) and ($Date >= $FirstSlotDate)) {
							echo "</td></tr>\n<tr class='schedDateUser'>\n";
							echo "<td>$ShowDate</td>\n<td>";
						}
					}
					$OldDate = $Date;
					$OldTime = "";
				}

				if ($Location != $OldLocation) {
					$LTitle = $ThisSite["Name"];
					$LTitle = Add_CB_Status($LTitle, $ThisSite);
					echo "<div class='apptGroup apptGroupSummary'>" . $LTitle . "</div>\n";
					$OldLocation = $Location;
					$OldTime = "";
				}

				if ($Time != $OldTime) {
					if (isset($DateList[$DateTimeLoc])) { // OR $_SESSION["SummaryAll"]) {
						$DTOCount = $DateList[$DateTimeLoc . "OpenCount"] ?? 0; // (includes ResCount)
						$DTBCount = $DateList[$DateTimeLoc . "Busy"] ?? 0;
						$DTRCount = $DateList[$DateTimeLoc . "ResCount"] ?? 0;
						$DTACount = $DTOCount - $DTRCount; // Available slots to all

						//Until determined otherwise...
						$DClass = "apptOpen";
						$ClickToAdd = "onclick=\"Add_Appointment('" . $DateList[$DateTimeLoc] . "', '$Date', '$Time', '$ShowDate', '$ShowTime')\"";

						if ($ApptView == "ViewUser") {
							if ($DTACount) {
								$title = "title=\"There " . isare($DTACount) . " $DTACount open appointment" . plural($DTACount) . " available for this time period.\"";
								$OpenSlots += $DTACount;
								$DTXCount = $DTACount; // No adjustment for reserved slots
							}
						}
						else {  // $ApptView is "ViewSummary"

							// Determine display class
							switch (true) {
							case (($DTOCount == 0) OR ($Date < $TodayDate));
								$DClass = "apptFull"; break;
							case ($DTRCount == $DTOCount): // all reserved
							       	$DClass = "apptWarn"; break;
							case ($DTRCount > 0): // some reserved
							       	$DClass = "apptPartOpen"; break;
							}

							// Does site allow use of reserved slots from Summary view
							$SiteReservedAllowed = ($ThisSite["SumRes"] != ""); // vs "checked"
							// Does user have permission to use reserved slots
							$CanUseReserved = ($ThisSite["Permissions"] & $USE_RES);
							$OKtoUseReserved = $SiteReservedAllowed AND $CanUseReserved;

							$DTXCount = (($OKtoUseReserved) ? $DTOCount : $DTACount) + 0;
							$DTHeader = "";
							$DTTitle = "";
							if ($DTXCount) $DTTitle = "$DTXCount available";
							if ($DTRCount) $DTTitle .= (($DTTitle) ? "\n" : "") . "$DTRCount reserved";
							if ($DTBCount) $DTTitle .= (($DTTitle) ? "\n" : "") . "$DTBCount assigned";

							switch (true) {
								case ($Date < $TodayDate):
									$DClass .= " noSelect";
									$DTHeader = "You cannot schedule appointments for an earlier date.\n";
									$ClickToAdd = "";
									break;
								case ($ThisSite["Permissions"] & $ADD_APP): // permission to add appt?
									if (($DTACount == 0) AND (! $OKtoUseReserved)) {
										if ($DTRCount > 0) {
											if (! $CanUseReserved) {
												$DTHeader .= "\nYou do not have permission to use reserved slots.\"";
												$ClickToAdd = "";
												$DClass .= " noSelect";
											}
											$DTOText = $DTRText = $DTBText = "";
										}
										else {
											$ClickToAdd = "";
											$DClass .= " noSelect";
										}
									}
									$OpenSlots += $DTACount;
									break;
								default:
									$DClass .= " noSelect";
									$DTHeader = "You do not have permission to schedule appointments at this site.\n";
									$ClickToAdd = "";
							}

							$title = "title=\"$DTHeader" . (($DTHeader) ? "\n" : "") . $DTTitle . "\"";
						}
						if (! $InetLimited) {
							echo "\t<div class='apptFloat $DClass' $ClickToAdd $title>$ShowTime ($DTXCount)</div>\n";
						}
					}
					else {
						if ($ApptView == "ViewSummary") {
							$fullclass = "apptFull";
							$title = ($DateList[$DateTimeLoc . 'Busy'] ?? 0) . " assigned";
							echo "\t<div class='apptFloat $fullclass noSelect' title='$title'>$ShowTime</div>\n";
						}
						else { // ViewUser
							$title = "All appointments have been filled.";
							$fullclass = "apptFull";
						}
					}
				$OldTime = $Time;
			}
		}
	}
	echo "</table>\n";

	switch (true) {
		case (($UserHome == 0 ) and ($InetLocationSelected == 0)): // no location was checked
			echo "<br /><div id='custCB'>\n";
			echo "<br />Please select a site from the left hand column.";
			echo "<br />&nbsp;</div>\n";
			break;

		case (($UserHome == 0) and (! $OpenSlots)): // No available appointment slots
			echo "<div id='custCB'>\n";
			echo "<br /><b>The " . $ThisSite["Name"] . " has no open appointments at this time.</b>";

			// Give an appropriate Callback list message
			if ($onCallback) { // Already on the CB list
				echo "<br /><br />You are on the callback list should an opening become available,";
				echo "<br />&nbsp;";
			}
			else if ($ThisSite["Inet"] == "N") { // Not allowed to add self to CB list
				echo "<br /><br />If you would like to be placed on the callback list should an opening become available,";
				echo " please refer to the instructions above.";
				echo "<br />&nbsp;";
			}
			else if ($ThisSite["IsOpen"] == "T") { // Allow to add if the site is still open
				echo "<br /><br />If you would like to be placed on the callback list should an opening become available,";
				echo " please click on the following button to give us your contact information:";
				echo "<br /><button id='custButton' onclick='Add_Appointment($OpenSlotNumber, \"$NullDate\", \"$NullTime\", \"$NullDate\", \"$NullTime\");'>Click to request a callback</button>\n";
			}
			else echo "<br />&nbsp;"; // Site is closed, cannot add to CB listj

			echo "</div>\n";
			break;

		case (($UserHome == 0) and $InetLimited): // Slots are available but already have the max number allowed
			echo "<br /><div id='custCB'>\n";
			echo "<br /><b>You will need to cancel a current appointment if you wish to make another.</b>";
			if ($onCallback) {
				echo "<br /><br />You are on the callback list. Someone should be contacting you shortly.";
				echo "<br />&nbsp;";
			}
			else if ($ThisSite["Inet"] == "N") { // Not allowed to add self to CB list
				echo "<br /><br />If you need additional appointments or assistance,";
				echo " please refer to the instructions above.";
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
			if ($ThisSite["Inet"] == "N") break; // Not allowed to add self to CB list
			echo "<br /><div id='custCB'>\n";

			// Give appropriate callback list message
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

unset($appointments); // release memory
}

//===========================================================================================
function List_Group($Location) {
// Sorts daily view output by named, blank, then RESERVED slots
//$ApptGroupItem = array($GroupType, $Appt, $SlotNumber, $SlotIndex, $myclass, $Name, $Phone, $Tags, $Need, $Info, $Status, $Location);
//===========================================================================================
	global $ApptGroupList, $ApptGroupListIndex;
	//error_log("LGL: List_Group for Location $Location -----------------------------"); /* DEBUG */
	
	$SlotIndex = 0;
	for ($AGLType = 1 ; $AGLType < 4 ; $AGLType++) {
		for ($AGLI = 1 ; $AGLI <= $ApptGroupListIndex ; $AGLI++) {
			$ApptGroupItem = $ApptGroupList[$AGLI];
			if ($ApptGroupItem[0] == $AGLType) {
				$SlotIndex++;
				List_Slot($ApptGroupItem[1], $ApptGroupItem[2], $SlotIndex, $ApptGroupItem[4], $ApptGroupItem[5], $ApptGroupItem[6], $ApptGroupItem[7], $ApptGroupItem[8], $ApptGroupItem[9], $ApptGroupItem[10], $ApptGroupItem[11]);
			}
		}
	}
	$ApptGroupListIndex = 0;
}

//===========================================================================================
function List_Slot($ListAppt, $SlotNumber, $SlotIndex, $myclass, $Name="", $Phone="", $Tags="", $Need="", $Info="", $Status="", $Location="") {
//===========================================================================================
	global $Debug, $Errormessage;
	global $Warning_icon, $CheckedBox_icon, $Appt_icon;
	global $VIEW_APP, $VIEW_CB;
	global $ApptView, $ADD_CB, $ADD_APP, $USE_RES;
	global $FormApptNo, $FormApptOldSlot;
	global $RESERVED;
	global $Site;
	global $InfoMatch;

	$ThisSite = $Site["S" . $Location];
	//error_log("LSLOT: List_Slot $ListAppt, Location=$Location, Slot=$SlotNumber:$SlotIndex, $Name"); /* DEBUG */

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
		$needmark = $CheckedBox_icon;
	}

	// display the info as a title and add an info mark
	$InfoTitle = "";
	$infomark = "";
	if ($Info != "") {
		$InfoTitle =  _Show_Chars(str_replace("%0A", ", ", $Info), "text"); // replace newlines with a comma
		$InfoTitle = str_replace("'", "`", $InfoTitle); // replace apostrophe with back-apos
		$InfoTitle = "title='" . $InfoTitle . "'";
		$infomark = $Warning_icon;
	
	}
	if ($InfoMatch) $infomark .= " " . $Appt_icon;

	// get rid of special character coding
	$Name = _Show_Chars($Name, "text");
	$addTags = _Show_Chars(str_replace("%0A", ", ", $Tags), "html"); // replace newlines with a comma

	// determine classes for display
	$slotclass = "";
	if (($FormApptNo == $ListAppt) or ($FormApptOldSlot == $ListAppt)) { // v 8.03
		$slotclass = "apptSlotMoved";
	}
	$inetclass = strpos($FirstStatus, '(USER)') ? "user_class" : "";
	if ($inetclass == "") $inetclass = strpos($Status, '(USER.)') ? "userOK_class" : "" ;

	// test if the user can assign slots
	$CanUseReserved = ($ThisSite["Permissions"] & $USE_RES);
	$CanChangeAppt = ($ThisSite["Permissions"] & $ADD_APP);
	$CanAddCallback = ($ThisSite["Permissions"] & $ADD_CB);
	$CanViewAppt = ($ThisSite["Permissions"] & $VIEW_APP);
	$CanViewCB = ($ThisSite["Permissions"] & $VIEW_CB);
	
	if ($Name == $RESERVED) {
		$titleNot = ($CanUseReserved) ? "" : "You do not have permission to assign a reserved appointment at this site." ;
		$CanView = $CanViewAppt;
	}
	else if ($ApptView == "ViewCallback") { // Callback slot
		$titleNot = ($CanAddCallback) ? "" : "You do not have permission to add or change a callback record at this site." ;
		$CanView = $CanViewCB;
	}
	else { // Empty or assigned slot
		$titleNot = ($CanChangeAppt) ? "" : "You do not have permission to add or change an appointment at this site." ;
		$CanView = $CanViewAppt;
	}
	if ($CanView) {
		// -----------------------------------------------------
		// In this section, if there are changes, be sure to coordinate those changes with
		// The Heartbeat javascript function in the appointment.php module and subsequent processing
		// -----------------------------------------------------
		$addTags = ($addTags) ? " <b>[$addTags]</b>" : "";
	
		if ($titleNot) { // block the link
			echo "<tr class='noSelect' title='$titleNot'>\n";
		}
		else { // all good
			echo "<tr onclick='Change_Appointment(1, $ListAppt, $SlotNumber, $SlotIndex);'>\n";
		}

		echo "\t<td class='apptSlot $slotclass'>&nbsp;$SlotIndex&nbsp;&nbsp;</td>\n";
		echo "\t<td id='apptName$SlotNumber' class='apptName $myclass'>";
		$apptNameId = "apptNameId" . $SlotNumber;
		$apptClickId = "apptClickId" . $SlotNumber;
		$apptSlotEmpty = "";
		$saveApptDB = $ListAppt;
		if ($ApptView == "ViewDaily") { // add the reserved icon in the name column
			$nameBlank = ($Name == "");
			$nameReserved = ($Name == $RESERVED);
			if (! $nameReserved) $Name = _Show_Chars($Name, "html");
			switch (true) {
				case ($nameBlank AND $CanUseReserved): // add the icon
					echo "<div class='apptNameDiv'>
						<div><span id=\"$apptNameId\"></span></div>
						<div id=\"$apptClickId\" class='apptNameRes' 
							title='Reserve this slot' 
							onclick='Change_Appointment(-1, $ListAppt, $SlotNumber, $SlotIndex);'>R</div></div>";
					$apptSlotEmpty = "apptSlotEmpty";
					$saveApptDB = $ListAppt; // redundant but here for clarity	
					break;
				case ($nameReserved AND $CanUseReserved): // add RESERVED and the icon
					echo "<div class='apptNameDiv'>
						<div class='apptReserved'><span id=\"$apptNameId\">$Name</span></div>
						<div class='apptNameUnres'>R</div>
						<div id=\"$apptClickId\" class='apptNameUnresNot'
							title='Unreserve this slot'
							onclick='Change_Appointment(-1, $ListAppt, $SlotNumber, $SlotIndex);'>
							<b>/</b></div>
						</div>";
					$apptSlotEmpty = "apptSlotEmpty";
					$saveApptDB = -$ListAppt;
					break;
				case ($nameReserved): // add RESERVED but no icon
					echo "<div class='apptNameDiv noSelect'>
						<div class='apptReserved'><span id=\"$apptNameId\">$Name</span></div>
						</div>";
					$apptSlotEmpty = "apptSlotEmpty";
					$saveApptDB = -$ListAppt;
					break;
				default: // add the name and no icon
					echo "<div class='apptNameDiv'>
						<div'><span id=\"$apptNameId\">$Name</span> $addTags</div>
						</div>";
					$apptSlotEmpty = "apptSlotInUse";
					$saveApptDB = $ListAppt; // redundant but here for clarity
			}
			echo "</td>\n";
		}
		else { // ViewUser
			echo ($Name . $addTags . "</td>\n");
		}
		echo "\t<td id='apptPhone$SlotNumber' class='apptPhone $myclass'>$Phone</td>\n";
		echo "\t<td id='apptNeed$SlotNumber' class='apptNeed $myclass' $NeedTitle>$needmark</td>\n";
		echo "\t<td id='apptInfo$SlotNumber' class='apptInfo $myclass' $InfoTitle>$infomark</td>\n";
		echo "\t<td id='apptStatus$SlotNumber' class='apptStatus $inetclass $myclass' title='$FirstStatus'>$FirstStatus</td>\n";
		echo "\t<td id='apptSlot$SlotNumber' class='$apptSlotEmpty' style='display:none;'>$SlotNumber</td>\n";
		echo "\t<td id='apptDBId$SlotNumber' class='apptDBId' style='display:none;'>$saveApptDB</td></tr>\n";
	}
}

//===========================================================================================
function Add_CB_Status($LTitle, $ThisSite) {
// Addes the callback list status onto the given appointment list title line
//===========================================================================================
	global $Debug, $Errormessage;
	global $Site, $ApptView;
	
	if ($ApptView != "ViewUser") {
		$LColor = ($ThisSite["BusyCBCount"] AND ($ThisSite["AvailCount"] <= $ThisSite["AvailCBCount"])) ? "yellow" : "transparent";
		$LTitle .= "<span style='position: absolute; right: 0.5em; background-color: $LColor;'>";
		$LTitle .= "(" . $ThisSite["BusyCBCount"] . " on Callback list)</span>";
		}
	return ($LTitle);
}

//===========================================================================================
function Add_Shortcodes($Loc, $message) {
// Adds shortcodes to message
//===========================================================================================
	global $Site, $Name, $Time, $Date;
	global $SystemAttachList;
	//NFG$SiteAddress = explode("|", $LocationAddress[$Loc]);
	$message = _Show_Chars($message, "html");
	$ThisSite = $Site["S" . $Loc];
	$lahtml = "";

	// Make the replacement for [ATTACHMENTS] shortcode
	if ($ThisSite["Attachments"]) {
		$breakhtml = "";
		$lahtml = "<ul class=\"attachlist\">";
		for ($lax = 0; $lax < sizeof($SystemAttachList)-1; $lax++) {
			$sap = explode("=", $SystemAttachList[$lax]);
			if (strpos($ThisSite["Attachments"], $sap[0]) !== false) {
				$saplink = "<a target=\"_blank\" href=\"$sap[1]\">$sap[1]</a>";
				$lahtml .= "$breakhtml<li> - $sap[0] ($saplink)";
				$breakhtml = "</li>";
			}
		}
		$lahtml .= "</li></ul>";
	}

	// [TPNAME], [DATE] and [TIME] are not supported for this message.
	$message = str_replace("[TPNAME]",      "", $message);
	$message = str_replace("[DATE]",        "", $message);
	$message = str_replace("[TIME]",        "", $message);
	$message = str_replace("[SITENAME]",    $ThisSite["Name"], $message);
	$message = str_replace("[ADDRESS]",     $ThisSite["Address"], $message);
	$message = str_replace("[CITY]",        $ThisSite["City"], $message);
	$message = str_replace("[STATE]", 	$ThisSite["State"], $message);
	$message = str_replace("[ZIP]",         $ThisSite["Zip"], $message);
	$message = str_replace("[PHONE]", 	$ThisSite["Phone"], $message);
	$message = str_replace("[EMAIL]",       $ThisSite["Email"], $message);
	$message = str_replace("[WEBSITE]",     $ThisSite["Website"], $message);
	$message = str_replace("[STATESITE]",   $_SESSION["SystemURL"], $message);
	$message = str_replace("[CONTACT]",     $ThisSite["Contact"], $message);
	$message = str_replace("[ATTACHMENTS]", $lahtml, $message);
	// For individual attachment shortcodes...
	for ($lax = 0; $lax < sizeof($SystemAttachList)-1; $lax++) {
		$sap = explode("=", $SystemAttachList[$lax]);
		$testShortcode = "[$sap[0]]";
		$replacement = "<a target=\"_blank\" href=\"$sap[1]\">$sap[0] ($sap[1])</a>";
		$message = str_replace($testShortcode, $replacement, $message);
	}	
	return $message;
}
?>



