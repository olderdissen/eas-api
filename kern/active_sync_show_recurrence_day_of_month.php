<?
function active_sync_show_recurrence_day_of_month($data, $lang)
	{
	print("<span id=\"Recurrence:DayOfMonth\" style=\"display: none;\">");
		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">");
					print("Tag");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				print("<td>");
					print("<select name=\"Recurrence:DayOfMonth\" class=\"xs\">");
						foreach(range(0, 31) as $i)
							{
							print("<option value=\"" . $i . "\"" . ($data["Recurrence"]["DayOfMonth"] == $i ? " selected" : "") . ">");
								print($i);
							print("</option>");
							}
					print("</select>");
				print("</td>");
			print("</tr>");
		print("</table>");
	print("</span>");
	}
?>
