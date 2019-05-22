<?
function active_sync_web_list_calendar($request)
	{
	print("<table style=\"height: 100%; width: 100%;\">");
		print("<tr>");
			print("<td>");
				print("<table>");
					print("<tr>");
						print("<td>");
							print("Ansicht");
						print("</td>");
						print("<td>");
							print(":");
						print("</td>");
						print("<td>");
							print("<select id=\"view\" onchange=\"handle_link({ cmd : 'CalendarSelect' });\">");

							foreach(array("a" => "Agenda", "d" => "Tag", "w" => "Woche", "m" => "Monat", "y" => "Jahr") as $key => $value)
								{
								print("<option value=\"" . $key . "\">");
									print($value);
								print("</option>");
								}
							print("</select>");
						print("</td>");
						print("<td>");
							print("&nbsp;");
						print("</td>");
						print("<td>");
							print("<input type=\"button\" onclick=\"handle_link({ cmd : 'CalendarJumpToNow' });\" value=\"Heute\">");
						print("</td>");
					print("</tr>");
				print("</table>");
			print("</td>");
		print("</tr>");
		print("<tr>");
			print("<td>");
				print("&nbsp;");
			print("</td>");
		print("</tr>");
		print("<tr>");
			print("<td style=\"height: 100%;\">");
				print("<span id=\"search_result\">");
				print("</span>");
			print("</td>");
		print("</tr>");
	print("</table>");
	}
?>
