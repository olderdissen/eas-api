<?
function active_sync_show_recurrence_week_of_month($data, $lang)
	{
	print("<span id=\"Recurrence:WeekOfMonth\" style=\"display: none;\">");
		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">");
					print("Woche");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				print("<td>");
					print("<select name=\"Recurrence:WeekOfMonth\" class=\"xs\" onchange=\"update_recurrence_week_of_month();\">");
						foreach(array(1 => "ersten", 2 => "zweiten", 3 => "dritten", 4 => "vierten", 5 => "letzten") as $key => $value)
							{
							print("<option value=\"" . $key . "\"" . ($data["Recurrence"]["Interval"] == $key ? " selected" : "") . ">");
								print($value);
							print("</option>");
							}
					print("</select>");
				print("</td>");
			print("</tr>");
		print("</table>");
	print("</span>");
	}
?>
