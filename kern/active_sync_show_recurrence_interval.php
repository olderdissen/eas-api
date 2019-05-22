<?
function active_sync_show_recurrence_interval($data, $lang)
	{
	print("<span id=\"Recurrence:Interval\" style=\"display: none;\">");
		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">");
					print("Interval");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				print("<td>");
					print("<select name=\"Recurrence:Interval\" class=\"xs\">");
						foreach(range(0, 999) as $i)
							{
							print("<option value=\"" . $i . "\"" . ($data["Recurrence"]["Interval"] == $i ? " selected" : "") . ">");
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
