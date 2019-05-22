<?
function active_sync_web_edit_tasks($request)
	{
	$data = ($request["ServerId"] ? active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"]) : array());

	foreach(active_sync_get_default_tasks() as $token => $value)
		$data["Tasks"][$token] = (isset($data["Tasks"][$token]) === false ? $value : $data["Tasks"][$token]);

	foreach(active_sync_get_default_recurrence() as $token => $value)
		$data["Recurrence"][$token] = (isset($data["Recurrence"][$token]) === false ? $value : $data["Recurrence"][$token]);

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

	foreach(active_sync_get_default_settings() as $key => $val)
		$settings["Settings"][$key] = (isset($_POST[$key]) === false ? "" : $_POST[$key]);

	foreach(array("FirstDayOfWeek" => $settings["Settings"]["FirstDayOfWeek"], "IsLeapMonth" => isset($data["Recurrence"]["MonthOfYear"]) ? $data["Recurrence"]["MonthOfYear"] == 2 ? 1 : 0 : 0) as $token => $value)
		$data["Recurrence"][$token] = (isset($data["Recurrence"][$token]) ? $data["Recurrence"][$token] : $value);

	foreach(array("StartDate", "DueDate", "DateCompleted") as $token)
		$data["Tasks"][$token] = date("d.m.Y", strtotime($data["Tasks"][$token]));

	foreach(array("ReminderTime") as $token)
		$data["Tasks"][$token] = date("d.m.Y H:i", strtotime($data["Tasks"][$token]));

	print("<form>");
		print("<input type=\"hidden\" name=\"Cmd\" value=\"Save\">");
		print("<input type=\"hidden\" name=\"CollectionId\" value=\"" . $request["CollectionId"] . "\">");
		print("<input type=\"hidden\" name=\"ServerId\" value=\"" . $request["ServerId"] . "\">");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Was</td>");
				print("<td>:</td>");
				print("<td>");
					print("<textarea name=\"Subject\" class=\"xt\">");
						print($data["Tasks"]["Subject"]);
					print("</textarea>");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Von</td>");
				print("<td>:</td>");
				print("<td>");
					print("<input type=\"text\" name=\"StartDate\" value=\"" . $data["Tasks"]["StartDate"] . "\" class=\"xi\" maxlength=\"10\" onclick=\"popup_date({ target : this, cmd : 'init', time : false });\">");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Bis</td>");
				print("<td>:</td>");
				print("<td>");
					print("<input type=\"text\" name=\"DueDate\" value=\"" . $data["Tasks"]["DueDate"] . "\" class=\"xi\" maxlength=\"10\" onclick=\"popup_date({ target : this, cmd : 'init', time : false });\">");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">");
					print("Erledigt");
				print("</td>");
				print("<td>:</td>");
				print("<td>");
					print("<input type=\"text\" name=\"DateCompleted\" value=\"" . $data["Tasks"]["DateCompleted"] . "\" class=\"xi\" maxlength=\"10\" onclick=\"popup_date({ target : this, cmd : 'init', time : false });\">");
				print("</td>");
				print("<td>");
					print("<input onchange=\"handle_link({ cmd : 'ToggleComplete' });\" type=\"checkbox\" name=\"Complete\" value=\"1\" " . ($data["Tasks"]["Complete"] == 1 ? " checked" : "") . ">");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Beschreibung</td>");
				print("<td>:</td>");
				print("<td>");
					print("<textarea class=\"xt\" name=\"Body:Data\">");
						print($data["Body"][0]["Data"]);
					print("</textarea>");
				print("</td>");
			print("</tr>");
		print("</table>");

		active_sync_show_recurrence($data);

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Datenschutz</td>");
				print("<td>:</td>");
				print("<td>");
					print("<select name=\"Sensitivity\" class=\"xs\">");
						foreach(array(0 => "Standard", 1 => "Öffentlich", 2 => "Privat", 3 => "Vertraulich") as $key => $value)
							{
							print("<option value=\"" . $key . "\"" . ($data["Tasks"]["Sensitivity"] == $key ? " selected" : "") . ">");
								print($value);
							print("</option>");
							}
					print("</select>");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">Priorität</td>");
				print("<td>:</td>");
				print("<td>");
					print("<select name=\"Importance\" class=\"xs\">");
						foreach(array(0 => "Niedrig", 1 => "Mittel", 2 => "Hoch") as $key => $value)
							{
							print("<option value=\"" . $key . "\"" . ($data["Tasks"]["Importance"] == $key ? " selected" : "") . ">");
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
					print("<input type=\"text\" name=\"ReminderTime\" value=\"" . $data["Tasks"]["ReminderTime"] . "\" class=\"xi\" maxlength=\"19\" onclick=\"popup_date({ target : this, cmd : 'init', time : true });\">");
				print("</td>");
				print("<td>");
					print("<input onchange=\"handle_link({ cmd : 'ToggleReminderSet' });\" style=\"border: solid 1px;\" type=\"checkbox\" name=\"ReminderSet\" value=\"1\" " . ($data["Tasks"]["ReminderSet"] == 1 ? " checked" : "") . ">");
				print("</td>");
			print("</tr>");
		print("</table>");

		print("<br>");

		print("<table>");
			print("<tr>");
				print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Save' });\">Fertig</span>]</td>");
				print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Reset' });\">Zurücksetzen</span>]</td>");

				if($request["ServerId"] != "")
					{
					print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'DeleteConfirm' });\">Löschen</span>]</td>");
					}

				print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'List' });\">Abbrechen</span>]</td>");
			print("</tr>");
		print("</table>");
	print("</form>");
	}
?>
