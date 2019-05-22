<?
function active_sync_show_recurrence_type($data, $lang = "de")
	{
	print("<table>");
		print("<tr>");
			print("<td class=\"field_label\">");
				print("Wiederholung");
			print("</td>");
			print("<td>");
				print(":");
			print("</td>");
			print("<td>");
				print("<select name=\"Recurrence:Type\" class=\"xs\" onchange=\"update_recurrence_type();\">");
					foreach(array(4 => "Einmaliges Ereignis", 0 => "Täglich", 1 => "Wöchentlich", 2 => "Monatlich", 3 => "Monatlich", 5 => "Jährlich", 6 => "Jährlich") as $key => $value)
						{
						print("<option value=\"" . $key . "\"" . ($data["Recurrence"]["Type"] == $key ? " selected" : "") . ">");
							print($value);
						print("</option>");
						}
				print("</select>");
			print("</td>");
		print("</tr>");
	print("</table>");
	}
?>
