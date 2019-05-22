<?
function active_sync_show_recurrence_until($data, $lang)
	{
	print("<span id=\"Recurrence:Until\" style=\"display: none;\">");
		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">");
					print("Ablaufdatum");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				print("<td>");
					print("<input type=\"text\" name=\"Recurrence:Until\" value=\"" . $data["Recurrence"]["Until"] . "\" class=\"xi\" onclick=\"popup_date({ target : this, cmd : 'init', time : false });\">"); # date or date + time ???
				print("</td>");
			print("</tr>");
		print("</table>");
	print("</span>");
	}
?>
