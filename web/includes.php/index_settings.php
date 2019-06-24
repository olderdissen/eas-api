<?
if($Request["Cmd"] == "Settings")
	{
	$settings = active_sync_get_settings(DAT_DIR . "/" . $Request["AuthUser"] . ".sync");

	foreach(active_sync_get_default_settings() as $key => $value)
		$settings["Settings"][$key] = (isset($settings["Settings"][$key]) ? $settings["Settings"][$key] : $value);

	print("<form onsubmit=\"return false;\">");
		print("<input type=\"hidden\" name=\"Cmd\" value=\"SettingsSave\">");
		print("<table border=\"0\">");
			print("<tr>");
				print("<td colspan=\"4\" style=\"font-weight: bold;\">");
					print("Kalender");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td align=\"right\" nowrap>");
					print("Erster Tag der Woche");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				print("<td colspan=\"2\">");
					print("<select name=\"FirstDayOfWeek\" class=\"xs\">");
						foreach(array(0 => "Sonntag", 1 => "Montag") as $key => $value)
							{
							print("<option value=\"" . $key . "\"" . ($settings["Settings"]["FirstDayOfWeek"] == $key  ? " selected" : "") . ">");
								print($value);
							print("</option>");
							}
					print("</select>");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td align=\"right\" nowrap>");
					print("Zeitzone");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				print("<td colspan=\"2\">");
					print("<select name=\"TimeZone\" class=\"xs\">");
						foreach(active_sync_get_table_timezone_information() as $key => $value)
							{
							print("<option value=\"" . $key . "\"" . ($settings["Settings"]["TimeZone"] == $key ? " selected" : "") . ">");
								print($value[1]);
							print("</option>");
							}
					print("</select>");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td align=\"right\" nowrap>");
					print("Zeitraum der Kalender Sync");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				print("<td colspan=\"2\">");
					print("<select name=\"CalendarSync\" class=\"xs\">");
						foreach(array(1 => "2 Wochen", 2 => "1 Monat", 3 => "3 Monate", 4 => "6 Monate", 0 => "Alle Kalender") as $key => $value)
							{
							print("<option value=\"" . $key . "\"" . ($settings["Settings"]["CalendarSync"] == $key ? " selected" : "") . ">");
								print($value);
							print("</option>");
							}
					print("</select>");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td colspan=\"4\">");
					print("&nbsp;");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td colspan=\"4\" style=\"font-weight: bold;\">");
					print("Einstellungen für Erinnerungen");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td align=\"right\" nowrap>");
					print("Standardzeit für Erinnerungen");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				print("<td colspan=\"2\">");
					print("<select name=\"Reminder\" class=\"xs\">");
					foreach(array(0 => "Keine", 1 => "1 Minute", 5 => "5 Minuten", 10 => "10 Minuten", 15 => "15 Minuten", 20 => "20 Minuten", 25 => "25 Minuten", 30 => "30 Minuten", 45 => "45 Minuten", 60 => "1 Stunde", 120 => "2 Stunden", 180 => "3 Stunden", 720 => "12 Stunden", 1440 => "24 Stunden", 2880 => "2 Tage", 10080 => "1 Woche") as $key => $value)
						{
						print("<option value=\"" . $key . "\"" . ($settings["Settings"]["Reminder"] == $key ? " selected" : "") . ">");
							print($value);
						print("</option>");
						}
					print("</select>");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td colspan=\"4\">");
					print("&nbsp;");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td colspan=\"4\" style=\"font-weight: bold;\">");
					print("Anzeigeeinstellungen für Kontakte");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td align=\"right\" nowrap>");
					print("Nur Kontakte mit Telefonnummern");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				print("<td colspan=\"2\">");
					print("<input type=\"checkbox\" name=\"PhoneOnly\" value=\"1\" title=\"Nur Kontakte anzeigen, die Telefonnummern haben\"" . ($settings["Settings"]["PhoneOnly"] == 1 ? " checked" : "") . ">");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td align=\"right\" nowrap>");
					print("Sortieren nach");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				print("<td>");
					print("<input type=\"radio\" name=\"SortBy\" value=\"0\"" . ($settings["Settings"]["SortBy"] == 0 ? " checked" : "") . ">");
				print("</td>");
				print("<td width=\"100%\">");
					print("Vorname");
					print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td>");
					print("&nbsp;");
				print("</td>");
				print("<td>");
					print("&nbsp;");
				print("</td>");
				print("<td>");
					print("<input type=\"radio\" name=\"SortBy\" value=\"1\"" . ($settings["Settings"]["SortBy"] == 1 ? " checked" : "") . ">");
				print("</td>");
				print("<td>");
					print("Nachname");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td align=\"right\" nowrap>");
					print("Kontakte anzeigen nach");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				print("<td>");
					print("<input type=\"radio\" name=\"ShowBy\" value=\"0\"" . ($settings["Settings"]["ShowBy"] == 0 ? " checked" : "") . ">");
				print("</td>");
				print("<td>");
					print("Vorname zuerst");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td>");
					print("&nbsp;");
				print("</td>");
				print("<td>");
					print("&nbsp;");
				print("</td>");
				print("<td>");
					print("<input type=\"radio\" name=\"ShowBy\" value=\"1\"" . ($settings["Settings"]["ShowBy"] == 1 ? " checked" : "") . ">");
				print("</td>");
				print("<td>");
					print("Nachname zuerst");
				print("</td>");
			print("</tr>");
		print("</table>");
	print("</form>");
	print("<p>");
		print("[");
			print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'SettingsSave' });\">");
				print("Speichern");
			print("</span>");
		print("]");
		print(" ");
		print("[");
			print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'ResetForm' });\">");
				print("Zurücksetzen");
			print("</span>");
		print("]");
	print("</p>");
	}

if($Request["Cmd"] == "SettingsSave")
	{
	$settings = active_sync_get_settings(DAT_DIR . "/" . $Request["AuthUser"] . ".sync");

	foreach(active_sync_get_default_settings() as $key => $val)
		$settings["Settings"][$key] = (isset($_POST[$key]) ? $_POST[$key] : "");

	$settings["Settings"]["PhoneOnly"] = ($settings["Settings"]["PhoneOnly"] ? 1 : 0);

	active_sync_put_settings(DAT_DIR . "/" . $Request["AuthUser"] . ".sync", $settings);

	print(1);
	}
?>
