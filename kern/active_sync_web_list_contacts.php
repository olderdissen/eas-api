<?
function active_sync_web_list_contacts($request)
	{
	print("<table style=\"height: 100%; width: 100%;\">");
		print("<tr>");
			print("<td>");
				print("<table>");
					print("<tr>");
						print("<td>");
							print("[");
								print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Edit', server_id : '' });\">");
									print("Hinzufügen");
								print("</a>");
							print("]");
						print("</td>");
						print("<td style=\"width: 50px;\">");
							print("&nbsp;");
						print("</td>");
						print("<td>");
							print("Suche nach");
						print("</td>");
						print("<td>");
							print(":");
						print("</td>");
						print("<td>");
							print("<input style=\"width: 150px;\" id=\"search_name\" type=\"text\"\">");
						print("</td>");
						print("<td style=\"width: 50px;\">");
							print("&nbsp;");
						print("</td>");
						print("<td>");
							print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Category' });\">");
								print("Gruppe");
							print("</a>");
						print("</td>");
						print("<td>");
							print(":");
						print("</td>");
						print("<td>");
							print("<select id=\"search_category\" style=\"width: 150px;\"\">");

								foreach(array("Alle" => "*", "Nicht zugewiesen" => "") as $key => $value)
									printf("<option value=\"%s\">%s</option>", $key, $value);

								$categories = active_sync_get_categories_by_collection_id($request["AuthUser"], $request["CollectionId"]);

								foreach($categories as $category => $count)
									{
									if($category == "*")
										continue;

									printf("<option value=\"%s\">%s</option>", $category, $category);
									}
							print("</select>");
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
				print("<table style=\"height: 100%; width: 100%;\">");
					print("<tr>");
						print("<td style=\"height: 100%; width: 32px;\">");
							print("<table style=\"height: 100%; width: 32px;\">");
								$m = "#ABCDEFGHIJKLMNOPQRSTUVWXYZ";

								for($i = 0; $i < strlen($m); $i = $i + 1)
									printf("<tr><td class=\"span_link\" style=\"border: solid 1px; border: solid 1px; text-align: center;\" onclick=\"contact_scroll_to('LETTER_%s', 'touchscroll_div');\">%s</td></tr>", $m[$i], $m[$i]);
							print("</table>");
						print("</td>");
						print("<td style=\"height: 100%;\">");
							print("<div class=\"touchscroll_outer\">");
								print("<div class=\"touchscroll_inner\" id=\"touchscroll_div\">");
									print("<span id=\"search_result\">");
									print("</span>");
								print("</div>");
							print("</div>");
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
			print("<td>");
				$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync");

				print("<span id=\"search_count\">");
					print(0);
				print("</span>");
				print(" ");
				print("Kontakte" . (isset($settings["Settings"]["PhoneOnly"]) === true ? " mit Telefonnummern " : " ") . "werden angezeigt."); # cu numere de telefon
			print("</td>");
		print("</tr>");
	print("</table>");
	}
?>
