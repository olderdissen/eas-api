<?
function active_sync_show_recurrence_month_of_year($data, $lang)
	{
	print("<span id=\"Recurrence:MonthOfYear\" style=\"display: none;\">");
		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">");
					print("Monat");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				print("<td>");
					print("<select name=\"Recurrence:MonthOfYear\" class=\"xs\" onchange=\"update_recurrence_month_of_year();\">");
						foreach(array(1 => "Januar", 2 => "Februar", 3 => "März", 4 => "April", 5 => "Mai", 6 => "Juni", 7 => "Juli", 8 => "August", 9 => "September", 10 => "Oktober", 11 => "November", 12 => "Dezember") as $key => $value)
							{
							print("<option value=\"" . $key . "\"" . ($data["Recurrence"]["MonthOfYear"] == $key ? " selected" : "") . ">");
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
