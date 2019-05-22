<?
function active_sync_show_recurrence_day_of_week($data, $lang)
	{
	print("<span id=\"Recurrence:DayOfWeek\" style=\"display: none;\">");
		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">");
					print("Wochentag");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				print("<td>");
					print("<select name=\"Recurrence:DayOfWeek\" class=\"xs\" onchange=\"handle_link({ cmd : 'UpdateRecurrenceDayOfWeek' });\">");
					print("</select>");

/*
					print("<table>");
						foreach(array(1 => "Sonntag", 2 => "Montag", 4 => "Dienstag", 8 => "Mittwoch", 16 => "Donnerstag", 32 => "Freitag", 64 => "Samstag", 127 => "letzter Tag des Monats") as $key => $value)
							{
							print("<tr>");
								print("<td>");
									print("<input type=\"checkbox\" name=\"Recurrence:DayOfWeek[]\" onchange=\"handle_link({ cmd : 'UpdateRecurrenceDayOfWeek' });\" value=\"" . $key . "\">");
								print("</td>");
								print("<td>");
									print($value);
								print("</td>");
							print("</tr>");
							}
					print("</table>");
*/

				print("</td>");
			print("</tr>");
		print("</table>");
	print("</span>");
	}
?>
