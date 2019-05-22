<?
function active_sync_web_edit_email($request)
	{
	if($request["ServerId"] != "")
		{
		$data = active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"]);

		$from		= $data["Email"]["From"];
		$to		= $data["Email"]["To"];
		$date_received	= $data["Email"]["DateReceived"];
		$subject	= (isset($data["Email"]["Subject"]) === false ? "" : $data["Email"]["Subject"]);

		$from		= utf8_decode($from);
		$from		= htmlentities($from);

		$to		= utf8_decode($to);
		$to		= htmlentities($to);

		$subject	= utf8_decode($subject);
		$subject	= htmlentities($subject);

		$mime = "";

		if($request["CollectionId"] == 9003) # drafts what about Email:IsDraft
			{
			foreach($data["Body"] as $body)
				{
				if(isset($body["Type"]) === false)
					continue;

				if($body["Type"] == 1)
					{
					$quote = array();

					$body["Data"] = wordwrap($body["Data"], 72);

					foreach(explode("\n", $body["Data"]) as $line)
						$quote[] = "" . htmlentities(utf8_decode($line));

					$mime = implode("<br>", $quote);
					}
				}
			}

		if($request["CollectionId"] != 9003) # drafts
			{
			foreach($data["Body"] as $body)
				{
				if(isset($body["Type"]) === false)
					continue;

				if($body["Type"] == 1)
					{
					$quote = array();

					$quote[] = "";
					$quote[] = "Am Montag, den " . date("d.m.Y, H:i", strtotime($date_received)) . " +0000 schrieb " . $from . ":";

					$body["Data"] = wordwrap($body["Data"], 72);

					foreach(explode("\n", $body["Data"]) as $line)
						$quote[] = "&gt; " . htmlentities(utf8_decode($line));

					# "-------- Ursprüngliche Nachricht --------";
					# "Von: " . htmlentities($from);
					# "Datum: " . date("d.m.Y, H:i", strtotime($date_received)) . " (+0000)"
					# "An: " . htmlentities($to);
					# "Betreff: " . $subject;

					$mime = implode("<br>", $quote);
					}

				if($body["Type"] == 2)
					{
					$quote = array();

					$quote[] = "<br>";
					$quote[] = "<div name=\"quote\" style=\"margin: 10px 5px 5px 10px; padding: 10px 0px 10px 10px; border-left: 2px solid #4080C0; word-wrap: break-word; -webkit-nbsp-mode: space; -webkit-line-break: after-white-space;\">";
						$quote[] = "<div style=\"margin: 0px 0px 10px 0px;\">";
							$quote[] = "<b>Gesendet:</b> " . date("l, d. F Y \u\m H:i \U\h\\r", strtotime($date_received));
							$quote[] = "<br>";
							$quote[] = "<b>Von:</b> " . $from;
							$quote[] = "<br>";
							$quote[] = "<b>An:</b> " . $to;
							$quote[] = "<br>";
							$quote[] = "<b>Betreff:</b> " . $subject;
						$quote[] = "</div>";
						$quote[] = "<div name=\"quoted-content\">";
							$quote[] = $body["Data"];
						$quote[] = "</div>";
					$quote[] = "</div>";

					$mime = implode("", $quote);
					}
				}

			if($request["LongId"] != "F")
				{
				}
			elseif(active_sync_mail_is_forward($subject) == 0)
				{
				$to		= "";
				$subject	= "Fw: " . $subject;
				}
			else
				{
				$to		= "";
				}

			if($request["LongId"] != "R")
				{
				}
			elseif(active_sync_mail_is_reply($subject) == 0)
				{
				$to		= $from;
				$subject	= "Re: " . $subject;
				}
			else
				{
				$to		= $from;
				}
			}

		$body = str_replace(array("\r", "\n", "\""), array("", "", "\\\""), $mime);
		}

	if($request["ServerId"] == "")
		{
		$data = array();

		$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . ".sync");

		$settings["Settings"]["Signature"]	= (isset($settings["Settings"]["Signature"]) === false ? "Von " . active_sync_get_version() . " gesendet" : $settings["Settings"]["Signature"]);
		$settings["Settings"]["Append"]		= (isset($settings["Settings"]["Append"]) === false ? 1 : $settings["Settings"]["Append"]);

		$from		= "";
		$to		= "";
		$subject	= "";
		$body		= ($settings["Settings"]["Append"] ? "<br>---<br>" . $settings["Settings"]["Signature"] : ""); # signature ::= "--" <sp> <cr> <lf> ( * 4 (* 80 <char>))

		if($request["ItemId"] != "")
			{
			list($disposition_type, $disposition_data) = explode(":", $request["ItemId"], 2);

			if($disposition_type == "inline")
				{
				list($x_collection_id, $x_server_id, $x_data) = explode(":", $disposition_data, 3);

				$contact = active_sync_get_settings_data($request["AuthUser"], $x_collection_id, $x_server_id);

				$body = (isset($contact["Contacts"]["FileAs"]) === false ? "" : $contact["Contacts"]["FileAs"]) . "<br>" . (isset($contact["Contacts"][$x_data]) === false ? "" : $contact["Contacts"][$x_data]) . "<br>" . $body;
				}

			if($disposition_type == "attachment")
				{
				list($x_collection_id, $x_server_id) = explode(":", $disposition_data, 2);

				# ...
				}

			if($disposition_type == "mail")
				{
				$to = $disposition_data;
				}
			}
		}

	print("<form style=\"height:100%;\">");
		print("<input type=\"hidden\" name=\"Cmd\" value=\"Save\">");
		print("<input type=\"hidden\" name=\"CollectionId\" value=\"" . $request["CollectionId"] . "\">");
		print("<input type=\"hidden\" name=\"ServerId\" value=\"" . $request["ServerId"] . "\">");
		print("<input type=\"hidden\" name=\"Draft\" value=\"0\">");
		print("<table style=\"height: 100%; width: 100%;\">");
			print("<tr>");
				print("<td>");
					print("<table style=\"width: 100%;\">");

						foreach(array("To" => "An", "Cc" => "Kopie", "Bcc" => "Blindkopie") as $key => $value)
							{
							print("<tr>");
								print("<td style=\"text-align: right;\">");
									print($value);
								print("</td>");
								print("<td>");
									print(":");
								print("</td>");
								print("<td colspan=\"5\" style=\"text-align: right; width: 100%;\">");
									# search suggest taken from 9009 default contacts folder
									if($key == "To")
										{
										print("<input type=\"text\" name=\"" . $key . "\" id=\"" . $key . "\" onfocus=\"suggest_register(this.id, '9009', 1);\" style=\"width: 100%;\" value=\"" . $to . "\">");
										}
									else
										{
										print("<input type=\"text\" name=\"" . $key . "\" id=\"" . $key . "\" onfocus=\"suggest_register(this.id, '9009', 1);\" style=\"width: 100%;\">");
										}
								print("</td>");
							print("</tr>");
							}

						print("<tr>");
							print("<td style=\"text-align: right;\">");
								print("Betreff");
							print("</td>");
							print("<td>");
								print(":");
							print("</td>");
							print("<td style=\"width: 100%;\">");
								print("<input type=\"text\" name=\"Subject\" value=\"" . $subject . "\" style=\"width: 100%;\">");
							print("</td>");
							print("<td>");
								print("&nbsp;");
							print("</td>");
							print("<td>");
								print("Priorität");
							print("</td>");
							print("<td>");
								print(":");
							print("</td>");
							print("<td>");
								print("<select name=\"Importance\">");

									foreach(array(0 => 0, 1 => 1, 2 => 0) as $importance => $selected)
										{
										print("<option value=\"" . $importance . "\"" . ($selected == 1 ? " selected" : "") . ">");
											print($importance);
										print("</option>");
										}

								print("<select>");
							print("</td>");
						print("</tr>");
					print("</table>");
				print("</td>");
			print("</tr>");

			print("<tr>");
				print("<td style=\"height: 100%;\" id=\"mail_content\">");
					print($body);
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td>");
					print("Dateianhänge");
					print(":");
					print(" ");
					print("<select name=\"attachments\" class=\"xs\">");
						if(isset($data["Attachments"]["AirSyncBase"]) === true)
							{
							foreach($data["Attachments"]["AirSyncBase"] as $attachment_id => $attachment_data)
								{
								print("<option value=\"" . $attachment_id . "\">");
									print($attachment_data["DisplayName"]);
								print("</option>");
								}
							}
					print("</select>");
					print(" ");
					print("[");
						print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'AttachmentDelete' });\">");
							print("Löschen");
						print("</span>");
					print("]");
					print(" ");
					print("[");
						print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'AttachmentUpload' });\">");
							print("Hinzufügen");
						print("</span>");
					print("]");

					print("<span id=\"pbe\" style=\"border: solid 1px; height: 18px; float: right; display: none; width: 200px;\">");
						print("<span id=\"pbc\" style=\"background-color: #4080C0; display: block; height: 18px; width: 0px;\">");
						print("</span>");
					print("</span>");

				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td>");
					print("<input type=\"checkbox\" name=\"SaveInSent\" value=\"T\" checked>");
					print(" Im Ordner <b>Gesendet</b> speichern");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td>");
					print("&nbsp;");
				print("</td>");
			print("</tr>");
			print("<tr>");
				print("<td>");
					print("<table>");
						print("<tr>");
							print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Send' });\">Senden</span>]</td>");
							print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Reset' });\">Zurücksetzen</span>]</td>");

							if($request["ServerId"] != "")
								{
								print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'DeleteConfirm' });\">Verwerfen</span>]</td>");
								}

							print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'List' });\">Abbrechen</span>]</td>");
							print("<td>[<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Draft' });\">Entwurf</span>]</td>");
						print("</tr>");
					print("</table>");
				print("</td>");
			print("</tr>");
		print("</table>");
	print("</form>");
	}
?>
