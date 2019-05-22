<?
function active_sync_show_recurrence_occurrences($data, $lang)
	{
	print("<span id=\"Recurrence:Occurrences\" style=\"display: none;\">");
		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">");
					print("Wiederholungen");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				print("<td>");
					print("<select name=\"Recurrence:Occurrences\" class=\"xs\">");
						foreach(range(1, 999) as $i)
							{
							print("<option value=\"" . $i . "\"" . ($data["Recurrence"]["Occurrences"] == $i ? " selected" : "") . ">");
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
