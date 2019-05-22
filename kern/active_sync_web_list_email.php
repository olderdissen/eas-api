<?
function active_sync_web_list_email($request)
	{
	print("<table style=\"height: 100%; width: 100%;\">");
		print("<tr>");
			print("<td style=\"height: 100%;\">");
				print("<div class=\"touchscroll_outer\">");
					print("<div class=\"touchscroll_inner\" id=\"touchscroll_div\">");
						print("<span id=\"search_result\">");
						print("</span>");
					print("</div>");
				print("</div>");
			print("</td>");
		print("</tr>");
		print("<tr>");
			print("<td>");
				print("&nbsp;");
			print("</td>");
		print("</tr>");
		print("<tr>");
			print("<td>");
				print("[");
					print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Edit', server_id : '', item_id : '', long_id : '' });\">");
						print("Verfassen");
					print("</span>");
				print("]");
				print(" ");
				print("<span id=\"delete_selected\" style=\"display: none;\">");
					print("[");
						print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'DeleteMultipleConfirm' });\">");
							print("Löschen");
						print("</span>");
					print("]");
				print("</span>");
			print("</td>");
		print("</tr>");
	print("</table>");
	}
?>
