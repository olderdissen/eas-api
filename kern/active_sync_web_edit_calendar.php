<?
function active_sync_web_edit_calendar($request)
	{
	$data = ($request["ServerId"] ? active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"]) : array());

	foreach(active_sync_get_default_calendar() as $token => $value)
		$data["Calendar"][$token] = (isset($data["Calendar"][$token]) === false ? $value : $data["Calendar"][$token]);

	foreach(active_sync_get_default_recurrence() as $token => $value)
		$data["Recurrence"][$token] = (isset($data["Recurrence"][$token]) === false ? $value : $data["Recurrence"][$token]);

	foreach(array("Attendees") as $key)
		$data[$key] = (isset($data[$key]) === false ? array() : $data[$key]);

	if(isset($data["Body"]) === false)
		$data["Body"][] = active_sync_get_default_body();

	foreach($data["Body"] as $body)
		{
		if(isset($body["Type"]) === false)
			continue;

		if($body["Type"] != 1)
			continue;

		foreach(active_sync_get_default_body() as $token => $value)
			$data["Body"][0][$token] = (isset($body[$token]) === false ? $value : $body[$token]);
		}

	$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync");

	$timezone_values = active_sync_get_table_timezone_information();

	$host = active_sync_get_domain();

#	$data["Calendar"]["Reminder"] = (isset($data["Calendar"]["Reminder"]) ? $data["Calendar"]["Reminder"] : ($request["ServerId"] == "" ? $settings["Settings"]["Reminder"] : 0));

#	foreach(array("FirstDayOfWeek" => $settings["Settings"]["FirstDayOfWeek"], "IsLeapMonth" => isset($data["Recurrence"]["MonthOfYear"]) ? $data["Recurrence"]["MonthOfYear"] == 2 ? 1 : 0 : 0) as $key => $value)
#		$data["Recurrence"][$key] = (isset($data["Recurrence"][$key]) ? $data["Recurrence"][$key] : $value);

	if($request["ServerId"] == "")
		{
		$login = active_sync_get_settings(DAT_DIR . "/login.data");

		foreach($login["login"] as $user)
			{
			if($user["User"] != $request["AuthUser"])
				continue;

			$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync");

			foreach(array("OrganizerName" => $user["DisplayName"], "OrganizerEmail" => $request["AuthUser"] . "@" . $host , "Reminder" => $settings["Settings"]["Reminder"]) as $token => $value)
				{
				if($value == "")
					continue;

				$data["Calendar"][$token] = $value;
				}

			foreach(array("StartTime" => 0, "EndTime" => 3600) as $token => $value)
				{
				$cookie = (isset($_COOKIE["time_id"]) === false ? time() : substr($_COOKIE["time_id"], 1) / 1000);

				$data["Calendar"][$token] = date("Ymd\THis\Z", date("U", $cookie) + $value);
				}
			}
		}

	foreach(array("StartTime", "EndTime", "DtStamp") as $token)
		$data["Calendar"][$token] = date("d.m.Y H:i", strtotime($data["Calendar"][$token]) + 7200);

	print("<form>");
		print("<input type=\"hidden\" name=\"Cmd\" value=\"Save\">");
		print("<input type=\"hidden\" name=\"CollectionId\" value=\"" . $request["CollectionId"] . "\">");
		print("<input type=\"hidden\" name=\"ServerId\" value=\"" . $request["ServerId"] . "\">");
		print("<input type=\"hidden\" name=\"DtStamp\" value=\"" . $data["Calendar"]["DtStamp"] . "\">");
		print("<input type=\"hidden\" name=\"UID\" value=\"" . $data["Calendar"]["UID"] . "\">");
		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Was</td>");
				print("<td>:</td>");
				print("<td>");
					print("<textarea name=\"Subject\" class=\"xt\" id=\"Subject\" onfocus=\"suggest_register(this.id, '" . $request["CollectionId"] . "', 0);\">");
						print($data["Calendar"]["Subject"]);
					print("</textarea>");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Von</td>");
				print("<td>:</td>");
				print("<td>");
					print("<input type=\"text\" name=\"StartTime\" value=\"" . $data["Calendar"]["StartTime"] . "\" class=\"xi\" onclick=\"popup_date({ target : this, cmd : 'init', time : true });\">");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Bis</td>");
				print("<td>:</td>");
				print("<td>");
					print("<input type=\"text\" id=\"EndTime\" name=\"EndTime\" value=\"" . $data["Calendar"]["EndTime"] . "\" class=\"xi\" onclick=\"popup_date({ target : this, cmd : 'init', time : true });\">");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Zeitzone</td>");
				print("<td>:</td>");
				print("<td>");
					print("<select name=\"TimeZone\" class=\"xs\">");
					foreach($timezone_values as $key => $value)
						{
						print("<option value=\"" . $key . "\"" . ($data["Calendar"]["TimeZone"] == $value[0] ? " selected" : "") . ">");
							print($value[1]);
						print("</option>");
						}
					print("</select>");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Ganzen Tag</td>");
				print("<td>:</td>");
				print("<td>");
					print("<input style=\"border: solid 1px;\" type=\"checkbox\" onchange=\"handle_link({ cmd : 'UpdateAllDayEvent' });\" name=\"AllDayEvent\" value=\"1\" " . ($data["Calendar"]["AllDayEvent"] == 1 ? " checked" : "") . ">");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Wo</td>");
				print("<td>:</td>");
				print("<td>");
					print("<textarea name=\"Location\" name=\"Location\" class=\"xt\" id=\"Location\" onfocus=\"suggest_register(this.id, '" . $request["CollectionId"] . "', 0);\">");
						print($data["Calendar"]["Location"]);
					print("</textarea>");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Beschreibung</td>");
				print("<td>:</td>");
				print("<td>");
					print("<input type=\"hidden\" name=\"Body:Type\" value=\"1\">");
					print("<textarea name=\"Body:Data\" class=\"xt\" id=\"Body:Data\" onfocus=\"suggest_register(this.id, '" . $request["CollectionId"] . "', 0);\">");
						print($data["Body"][0]["Data"]);
					print("</textarea>");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Teilnehmer</td>");
				print("<td>:</td>");
				print("<td>");
					print("<input type=\"hidden\" name=\"MeetingStatus\" value=\"" . $data["Calendar"]["MeetingStatus"] . "\">");
					print("<input type=\"hidden\" name=\"OrganizerName\" value=\"" . $data["Calendar"]["OrganizerName"] . "\">");
					print("<input type=\"hidden\" name=\"OrganizerEmail\" value=\"" . $data["Calendar"]["OrganizerEmail"] . "\">");

					asort($data["Attendees"], SORT_LOCALE_STRING);

					# what about AttendeeStatus and AttendeeType if we change appointment ???

					print("<table>");
						print("<tr>");
							print("<td>");
								print("<select id=\"Attendees\" name=\"Attendees[]\" ondblclick=\"this.remove(this.selectedIndex);\" size=\"2\" class=\"xs\" style=\"height: 100px;\" multiple>");

									foreach($data["Attendees"] as $id => $attendee)
										{
										print("<option value=\"&quot;" . $attendee["Name"] . "&quot; &lt;" . $attendee["Email"] . "&gt;\">");
											print("&quot;");
												print($attendee["Name"]);
											print("&quot;");
											print(" ");
											print("&lt;");
												print($attendee["Email"]);
											print("&gt;");
										print("</option>");
										}

								print("</select>");
							print("</td>");
						print("</tr>");
						print("<tr>");
							print("<td>");
								print("<input type=\"text\" class=\"xi\" id=\"Attendee\" onfocus=\"options_handle_calendar('Attendee', 'Attendees');\">");
							print("</td>");
						print("</tr>");
					print("</table>");
				print("</td>");
			print("</tr>");
		print("</table>");

		active_sync_show_recurrence($data);

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Anzeigen als</td>");
				print("<td>:</td>");
				print("<td>");
					print("<select name=\"BusyStatus\" class=\"xs\">");
						foreach(array(0 => "Verfügbar", 1 => "Vorläufig", 2 => "Besetzt", 3 => "Abwesend") as $key => $value)
							{
							print("<option value=\"" . $key . "\"" . ($data["Calendar"]["BusyStatus"] == $key ? " selected" : "") . ">");
								print($value);
							print("</option>");
							}
					print("</select>");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Datenschutz</td>");
				print("<td>:</td>");
				print("<td>");
					print("<select name=\"Sensitivity\" class=\"xs\">");
						foreach(array(0 => "Standard", 1 => "Öffentlich", 2 => "Privat", 3 => "Vertraulich") as $key => $value)
							{
							print("<option value=\"" . $key . "\"" . ($data["Calendar"]["Sensitivity"] == $key ? " selected" : "") . ">");
								print($value);
							print("</option>");
							}
					print("</select>");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Erinnerung</td>");
				print("<td>:</td>");
				print("<td>");
					print("<select name=\"Reminder\" class=\"xs\">");
					foreach(array(0 => "Keine", 1 => "1 Minute", 5 => "5 Minuten", 10 => "10 Minuten", 15 => "15 Minuten", 20 => "20 Minuten", 25 => "25 Minuten", 30 => "30 Minuten", 45 => "45 Minuten", 60 => "1 Stunde", 120 => "2 Stunden", 180 => "3 Stunden", 720 => "12 Stunden", 1440 => "24 Stunden", 2880 => "2 Tage", 10080 => "1 Woche") as $key => $value)
						{
						print("<option value=\"" . $key . "\"" . ($data["Calendar"]["Reminder"] == $key ? " selected" : "") . ">");
							print($value);
						print("</option>");
						}
					print("</select>");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<br>");

		print("<table>");
			print("<tr>");
				print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Save' });\">Fertig</span>]</td>");
				print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Reset' });\">Zurücksetzen</span>]</td>");

				if($request["ServerId"] != "")
					print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'DeleteConfirm' });\">Löschen</span>]</td>");

				print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'List' });\">Abbrechen</span>]</td>");
			print("</tr>");
		print("</table>");
	print("</form>");
	}
?>
