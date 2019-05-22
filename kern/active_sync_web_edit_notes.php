<?
function active_sync_web_edit_notes($request)
	{
	$data = ($request["ServerId"] ? active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"]) : array());

	foreach(active_sync_get_default_notes() as $token => $value)
		$data["Notes"][$token] = (isset($data["Notes"][$token]) === false ? $value : $data["Notes"][$token]);

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

	print("<form style=\"height:100%;\" onsubmit=\"return false;\">");
		print("<input type=\"hidden\" name=\"Cmd\" value=\"Save\">");
		print("<input type=\"hidden\" name=\"CollectionId\" value=\"" . $request["CollectionId"] . "\">");
		print("<input type=\"hidden\" name=\"ServerId\" value=\"" . $request["ServerId"] . "\">");

		print("<table style=\"height: 100%; width: 100%;\">");
			print("<tr>");
				print("<td>");
					print("<table style=\"width: 100%;\">");
						print("<tr>");
							print("<td>");
								print("Titel");
							print("</td>");
							print("<td>");
								print(":");
							print("</td>");
							print("<td style=\"width: 100%;\">");
								print("<input type=\"text\" name=\"Subject\" value=\"" . $data["Notes"]["Subject"] . "\" style=\"width: 100%;\">");
							print("</td>");
						print("</tr>");
					print("</table>");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td style=\"height: 100%;\">");
					print("<input type=\"hidden\" name=\"Body:Type\" value=\"1\">");
					print("<textarea name=\"Body:Data\" style=\"webkit-box-sizing: border-box; -moz-box-sizing: border-box; box-sizing: border-box; padding: 8px; font-family: Courier New; font-size: 10pt; width: 100%; height: 100%;\">");
						print($data["Body"][0]["Data"]);
					print("</textarea>");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td>");
					print("&nbsp;");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td>");
					print("[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Save' });\">Fertig</span>]");
					print("[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Reset' });\">Zurücksetzen</span>]");

					if($request["ServerId"] != "")
						print("[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'DeleteConfirm' });\">Löschen</span>]");

					print("[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'List' });\">Abbrechen</span>]");
				print("</td>");
			print("</tr>");
		print("</table>");
	print("</form>");
	}
?>
