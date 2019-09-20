<?
if($Request["Cmd"] == "Attachment")
	{
	if(active_sync_get_class_by_collection_id($Request["AuthUser"], $Request["CollectionId"]) == "Email")
		{
		$data = active_sync_get_settings_data($Request["AuthUser"], $Request["CollectionId"], $Request["ServerId"]);

		$item_id = $Request["ItemId"];

		$content_type = "";

		if(isset($data["file"][$item_id]["ContentType"]))
			if($data["file"][$item_id]["ContentType"] != "")
				$content_type = $data["file"][$item_id]["ContentType"];

		$content_name = "";

		if(isset($data["Attachments"][$item_id]["AirSyncBase"]["DisplayName"]))
			if($data["Attachments"][$item_id]["AirSyncBase"]["DisplayName"] != "")
				$content_name = $data["Attachments"][$item_id]["AirSyncBase"]["DisplayName"];

		$content_data = $data["file"][$item_id]["Data"];

		if($content_type == "application/x-pkcs7-mime")
			{
#			$content_data = "Content-Type: application/x-pkcs7-mime; name=\"smime.p7m\"; smime-type=\"enveloped-data\"\n\n" . chunk_split($content_data, 76, "\n");

#			$content_data = active_sync_mail_body_decode_smime($Request["AuthUser"], $content_data);
			}

		$content_data = base64_decode($content_data);

		if(($content_type == "") && ($content_name == ""))
			{
			$content_type = "application/octet-stream";
			$content_name = "attachment.bin";
			}
		elseif(($content_type == "") && ($content_name != ""))
			{
			# get content-type by extension
			}
		elseif(($content_type != "") && ($content_name == ""))
			{
			# get extension by content-type
			}
		elseif(($content_type != "") && ($content_name != ""))
			{
			# get extension by content-type
			}

		header("Content-Type: " . $content_type);
		header("Content-Length: " . strlen($content_data));
		header("Content-Disposition: attachment; filename=\"" . $content_name . "\"");

		print($content_data);
		}
	}

if($Request["Cmd"] == "Flag")
	{
	if(active_sync_get_class_by_collection_id($Request["AuthUser"], $Request["CollectionId"]) == "Email")
		{
		$data = active_sync_get_settings_data($Request["AuthUser"], $Request["CollectionId"], $Request["ServerId"]);

		$data["Flag"] = array();

		if($Request["LongId"] == 0)
			{
			$data["Flag"] = array
				(
				"Email" => array
					(
					"Status" => 2,
					"FlagType" => "Follow Up"
					),
				"Tasks" => array
					(
					"StartDate" => date("Y-m-d\TH:i:s\Z" + (1 * 86400)),	# + 1 day
					"DueDate" => date("Y-m-d\TH:i:s\Z" + (5 * 86400)),	# + 5 days
					"UtcStartDate" => date("Y-m-d\TH:i:s\Z" + (1 * 86400)),	# + 1 day
					"UtcDueDate" => date("Y-m-d\TH:i:s\Z" + (5 * 86400)),	# + 5 days
					"ReminderSet" => 0,
					"ReminderTime" => date("Y-m-d\TH:i:s\Z")
					)
				);
			}

		if($Request["LongId"] == 1)
			{
			$data["Flag"] = array
				(
				"Email" => array
					(
					"Status" => 1,
					"CompleteTime" => date("Y-m-d\TH:i:s\Z", 0 - 3600)
					),
				"Tasks" => array
					(
					"DateCompleted" => date("Y-m-d\TH:i:s\Z")
					)
				);
			}

		if($Request["LongId"] == 2)
			{
			$data["Flag"] = array
				(
				"Email" => array
					(
					"Status" => 0
					)
				);
			}

		active_sync_put_settings_data($Request["AuthUser"], $Request["CollectionId"], $Request["ServerId"], $data);

		print(1);
		}
	}

if($Request["Cmd"] == "Meeting") # MeetingResponse
	{
	if(active_sync_get_class_by_collection_id($Request["AuthUser"], $Request["CollectionId"]) == "Email")
		{
		$user		= $Request["AuthUser"];
		$host		= active_sync_get_domain();
		$collection_id	= $Request["CollectionId"];
		$request_id	= $Request["RequestId"];
		$user_response	= $Request["UserResponse"];

		$data = active_sync_get_settings_data($user, $collection_id, $request_id);

		unlink(ACTIVE_SYNC_DAT_DIR . "/" . $user . "/" . $collection_id . "/" . $request_id . ".data");

		$calendar_id = active_sync_get_calendar_by_uid($user, $data["meeting"]["Email"]["UID"]);

		$collection_id = active_sync_get_collection_id_by_type($user, 8); # Calendar

		if($calendar_id == "")
			{
			$calendar = array();

			$calendar["Calendar"] = $data["meeting"]["Email"];

			unset($calendar["Calendar"]["Organizer"]);

			list($organizer_name, $organizer_mail) = active_sync_mail_parse_address($data["meeting"]["Email"]["Organizer"]);

			foreach(array("OrganizerName" => $organizer_name, "OrganizerEmail" => $organizer_mail) as $token => $value)
				if($value != "")
					$calendar["Calendar"][$token] = $value;

			$calendar["Calendar"]["MeetingStatus"] = 3;

			$calendar["Calendar"]["Subject"] = $data["Email"]["Subject"];

			if($user_response == 1)
				{
				$calendar["Calendar"]["ResponseType"] = 3;

				$calendar_id = active_sync_create_guid_filename($user, $collection_id);

				active_sync_put_settings_data($user, $collection_id, $calendar_id, $calendar);
				}

			if($user_response == 2)
				{
				$calendar["Calendar"]["ResponseType"] = 2;

				$calendar_id = active_sync_create_guid_filename($user, $collection_id);

				active_sync_put_settings_data($user, $collection_id, $calendar_id, $calendar);
				}

			if($user_response == 3)
				{
				$calendar["Calendar"]["ResponseType"] = 4;

				# but don't save this calendar
				}

			$boundary = active_sync_create_guid();

			$description = array();

			$description[] = "Wann: " . date("d.m.Y H:i:s", strtotime($data["meeting"]["Email"]["StartTime"]));

			if(isset($data["meeting"]["Email"]["Location"]))
				$description[] = "Wo: " . $data["meeting"]["Email"]["Location"];

			$description[] = "*~*~*~*~*~*~*~*~*~*";

			if(isset($data["Email"]["Body"]["Data"]))
				$description[] = $data["Email"]["Body"]["Data"];

			$mime = array();

			$mime[] = "From: " . $data["Email"]["To"];
			$mime[] = "To: " . $data["Email"]["From"];

			foreach(array("Accepted" => 1, "Tentative" => 2, "Declined" => 3) as $subject => $value)
				if($user_response == $value)
					$mime[] = "Subject: " . $subject . ": " . $data["Email"]["Subject"];

			$mime[] = "Content-Type: multipart/alternative; boundary=\"" . $boundary . "\"";
			$mime[] = "";
			$mime[] = "--" . $boundary;
			$mime[] = "Content-Type: text/plain; charset=\"utf-8\"";
			$mime[] = "";
			$mime[] = implode("\n", $description);
			$mime[] = "";

			foreach(array("Accepted" => 1, "Tentative" => 2, "Declined" => 3) as $message => $value)
				if($user_response == $value)
					$mime[] = $message;

			$mime[] = "";
			$mime[] = "--" . $boundary;
			$mime[] = "Content-Type: text/calendar; method=REPLY; name=\"invite.ics\"";
			$mime[] = "";
			$mime[] = "BEGIN:VCALENDAR";
				$mime[] = "METHOD:REPLY";
				$mime[] = "PRODID:" . active_sync_get_version();
				$mime[] = "VERSION:2.0";
				# VTIMEZONE
				$mime[] = "BEGIN:VEVENT";
					$mime[] = "UID:" . $data["meeting"]["Email"]["UID"];

					foreach(array("DTSTAMP" => "DtStamp", "DTSTART" => "StartTime", "DTEND" => "EndTime") as $key => $token)
						$mime[] = $key . ":" . date("Y-m-d\TH:i:s\Z", strtotime($data["meeting"]["Email"][$token]));

					if(isset($data["meeting"]["Location"]))
						$mime[] = "LOCATION: " . $data["meeting"]["Email"]["Location"];

					if(isset($data["Email"]["Subject"]))
						$mime[] = "SUMMARY: " . $data["Email"]["Subject"]; # take this from email subject

					$mime[] = "DESCRIPTION:" . implode("\\n", $description);

					foreach(array("FALSE" => 0, "TRUE" => 1) as $key => $value)
						if($data["meeting"]["Email"]["AllDayEvent"] == $value)
							$mime[] = "X-MICROSOFT-CDO-ALLDAYEVENT:" . $key;

					foreach(array("ACCEPTED" => 1, "TENTATIVE" => 2, "DECLINED" => 3) as $partstat => $value)
						if($user_response == $value)
							$mime[] = "ATTENDEE;ROLE=REQ-PARTICIPANT;PARTSTAT=" . $partstat . ";RSVP=TRUE:MAILTO:" . $user . "@" . $host;

					list($organizer_name, $organizer_mail) = active_sync_mail_parse_address($data["meeting"]["Email"]["Organizer"]);

					if($organizer_name == "")
						$mime[] = "ORGANIZER:MAILTO:" . $organizer_mail;
					else
						$mime[] = "ORGANIZER;CN=\"" . $organizer_name . "\":MAILTO:" . $organizer_mail;

					$mime[] = "STATUS:CONFIRMED";
					$mime[] = "TRANSP:OPAQUE";
					$mime[] = "PRIORITY:5";
					$mime[] = "SEQUENCE:0";

				$mime[] = "END:VEVENT";
			$mime[] = "END:VCALENDAR";
			$mime[] = "";
			$mime[] = "--" . $boundary . "--";

			$mime = implode("\n", $mime);

			active_sync_send_mail($user, $mime);
			}

		print(1);
		}
	}

if($Request["Cmd"] == "Move")
	{
	if((active_sync_get_class_by_collection_id($Request["AuthUser"], $Request["SrcFldId"]) == "Email") && (active_sync_get_class_by_collection_id($Request["AuthUser"], $Request["DstFldId"]) == "Email"))
		{
		if($Request["DstMsgId"] == "") # new name
			$Request["DstMsgId"] = active_sync_create_guid_filename($Request["AuthUser"], $Request["DstFldId"]);

		$Src = ACTIVE_SYNC_DAT_DIR . "/" . $Request["AuthUser"] . "/" . $Request["SrcFldId"] . "/" . $Request["SrcMsgId"] . ".data";
		$Dst = ACTIVE_SYNC_DAT_DIR . "/" . $Request["AuthUser"] . "/" . $Request["DstFldId"] . "/" . $Request["DstMsgId"] . ".data";

		$status = (rename($Src, $Dst) ? 1 : 7);

		print($status);
		}
	}

if($Request["Cmd"] == "Read")
	{
	if(active_sync_get_class_by_collection_id($Request["AuthUser"], $Request["CollectionId"]) == "Email")
		{
		$data = active_sync_get_settings_data($Request["AuthUser"], $Request["CollectionId"], $Request["ServerId"]);

		foreach(array(0, 1) as $value)
			{
			if($Request["LongId"] == $value)
				{
				$data["Email"]["Read"] = $value;

				break; # even if two tests only
				}
			}

		active_sync_put_settings_data($Request["AuthUser"], $Request["CollectionId"], $Request["ServerId"], $data);

		print(1);
		}
	}

if($Request["Cmd"] == "Show")
	{
	if(active_sync_get_class_by_collection_id($Request["AuthUser"], $Request["CollectionId"]) == "Email")
		{
		$data = active_sync_get_settings_data($Request["AuthUser"], $Request["CollectionId"], $Request["ServerId"]);

#print_r($data);
		if(isset($data["AirSync"]) === false)
			{
			$data["Email"]["From"]			= "";
			$data["Email"]["To"]			= "";
			$data["Email"]["DateReceived"]		= "";
			$data["Email"]["Subject"]		= "";

			$data["Body"][0] = array
				(
				"Data" => "",
				"Type" => 1,
				"EstimatedDataSize" => 0
				);
			}
		else
			{
			if($data["Email"]["Read"] != 1)
				{
				$data["Email"]["Read"] = 1;

				active_sync_put_settings_data($Request["AuthUser"], $Request["CollectionId"], $Request["ServerId"], $data);
				}

			################################################################################

			if(isset($data["Email"]["MessageClass"]))
				if($data["Email"]["MessageClass"] == "IPM.Note.SMIME") # encrypted
					{
					$user = $Request["AuthUser"];

					$file = $Request["ServerId"];

					$mime = "";

					foreach($data["Body"] as $body)
						if(isset($body["Type"]))
							if($body["Type"] == 4)
								$mime = $body["Data"];

					$mime = active_sync_mail_body_smime_decode($mime);

					$data = active_sync_mail_parse($user, $file, $mime);
					}

			if(isset($data["file"]))
				{
				$user = $Request["AuthUser"];

				$file = $Request["ServerId"];

				$mime = "";

				foreach($data["Body"] as $body)
					if(isset($body["Type"]))
						if($body["Type"] == 4)
							$mime = $body["Data"];

				$mime = active_sync_mail_body_smime_decode($mime);

				$data = active_sync_mail_parse($user, $file, $mime);
				}
			}

		################################################################################

		if(! isset($data["Email"]["From"]))
			$data["Email"]["From"] =  "";

		if(! isset($data["Email"]["DateReceived"]))
			$data["Email"]["DateReceived"] = "";

		if(! isset($data["Email"]["Subject"]))
			$data["Email"]["Subject"] = "&nbsp;";

		print("<table style=\"height: 100%; width: 100%;\">");

			if(isset($data["AirSync"]["Class"]) === false)
				{
				}
			elseif($data["AirSync"]["Class"] == "Email")
				{
				list($t_name, $t_mail) = active_sync_mail_parse_address($data["Email"]["To"]);
				list($f_name, $f_mail) = active_sync_mail_parse_address($data["Email"]["From"]);

				$data["Email"]["DateReceived"] = date("d.m.Y H:i:s", strtotime($data["Email"]["DateReceived"]));

				print("<tr>");
					print("<td align=\"right\">");
						print("Von");
					print("</td>");
					print("<td>");
						print(":");
					print("</td>");
					print("<td style=\"width: 100%;\" nowrap>");
						print($f_name ? $f_name : $f_mail);
					print("</td>");
				print("</tr>");

				print("<tr>");
					print("<td align=\"right\">");
						print("An");
					print("</td>");
					print("<td>");
						print(":");
					print("</td>");
					print("<td style=\"width: 100%;\" nowrap>");
						print($t_name ? $t_name : $t_mail);
					print("</td>");
				print("</tr>");

				print("<tr>");
					print("<td align=\"right\">");
						print("Datum");
					print("</td>");
					print("<td>");
						print(":");
					print("</td>");
					print("<td style=\"width: 100%;\" nowrap>");
						print($data["Email"]["DateReceived"]);
					print("</td>");
				print("</tr>");

				print("<tr>");
					print("<td align=\"right\">");
						print("Betreff");
					print("</td>");
					print("<td>");
						print(":");
					print("</td>");
					print("<td style=\"width: 100%;\" nowrap>");
						print($data["Email"]["Subject"]);
					print("</td>");
				print("</tr>");
				}
			elseif($data["AirSync"]["Class"] == "SMS")
				{
				$f_name = "";
				$f_mail = $data["Email"]["From"];

				print("<tr>");
					print("<td align=\"right\">");
						print("Von");
					print("</td>");
					print("<td>");
						print(":");
					print("</td>");
					print("<td style=\"width: 100%;\" nowrap>");
						print($f_name ? $f_name : $f_mail);
					print("</td>");
				print("</tr>");

				print("<tr>");
					print("<td align=\"right\">");
						print("Datum");
					print("</td>");
					print("<td>");
						print(":");
					print("</td>");
					print("<td style=\"width: 100%;\" nowrap>");
						print(date("d.m.Y H:i:s", strtotime($data["Email"]["DateReceived"])));
					print("</td>");
				print("</tr>");
				}

			if(isset($data["Body"]))
				{
				print("<tr>");
					print("<td colspan=\"3\" style=\"height: 100%;\">");
						print("<table style=\"height: 100%; width: 100%; padding: 10px;\">");
							print("<tr>");
								print("<td valign=\"top\" style=\"background-color: #FFFFFF; height: 100%; padding: 0px;\">");
									print("<div style=\"height: 100%; width: 100%; position: relative;\">");
										print("<div style=\"border-width: 1px; border-style: solid; height: 100%; width: 100%; overflow-y: scroll; position: absolute; -moz-user-select: text; cursor: text;\">");
											print("<div style=\"padding: 10px;\">");

												foreach($data["Body"] as $body)
													if(isset($body["Type"]))
														if($body["Type"] == 1)
															print("<font face=\"Courier New\" size=\"2\">" . str_replace(array("<", ">", "\r", "\n"), array("&lt;", "&gt;", "", "<br>"), $body["Data"]) . "</font>");
														elseif($body["Type"] == 2)
															print($body["Data"]);

											print("</div>");
										print("</div>");
									print("</div>");
								print("</td>");
							print("</tr>");
						print("</table>");
					print("</td>");
				print("</tr>");
				}

			print("<tr>");
				print("<td colspan=\"3\">");
					print("&nbsp;");
				print("</td>");
			print("</tr>");

			print("<tr>");
				print("<td colspan=\"3\">");
					if($Request["CollectionId"] == "9002")
						if(isset($data["AirSync"]["Class"]))
							if($data["AirSync"]["Class"] == "Email")
								{
								print("[");
									print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Reply' });\">");
										print("Antworten");
									print("</span>");
								print("]");
								print(" ");
								}
							elseif($data["AirSync"]["Class"] == "SMS")
								{
								print("[");
									print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Reply' });\">");
										print("Antworten");
									print("</span>");
								print("]");
								print(" ");
								}

					print("[");
						print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'DeleteConfirm' });\">");
							print("Löschen");
						print("</span>");
					print("]");
					print(" ");
					print("[");
						print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'List' });\">");
							print("Zurück");
						print("</span>");
					print("]");
#						print(" ");
#						print("[");
#							print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Print' });\">");
#								print("Drucken");
#							print("</span>");
#						print("]");
				print("</td>");
			print("</tr>");

			if(isset($data["meeting"]) === false)
				{
				}
			elseif($data["Email"]["MessageClass"] == "IPM.Schedule.Meeting.Request")
				{
				print("<tr>");
					print("<td colspan=\"3\">");
						print("&nbsp;");
					print("</td>");
				print("</tr>");
				print("<tr>");
					print("<td align=\"right\">");
						print("Termin");
					print("</td>");
					print("<td>");
						print(":");
					print("</td>");
					print("<td>");
						print("[");
							print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Meeting', user_response : 1});\">");
								print("Annehmen");
							print("</span>");
						print("]");
						print(" ");
						print("[");
							print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Meeting', user_response : 2});\">");
								print("Vorläufig");
							print("</span>");
						print("]");
						print(" ");
						print("[");
							print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Meeting', user_response : 3 });\">");
								print("Ablehnen");
							print("</span>");
						print("]");

#							print("<pre>");
#								unset($data["meeting"]["Email"]["TimeZone"]);
#								print_r($data["meeting"]);
#							print("</pre>");
					print("</td>");
				print("</tr>");
				}

			if(isset($data["Attachment"]) === true)
				{
				print("<tr>");
					print("<td colspan=\"3\">");
						print("&nbsp;");
					print("</td>");
				print("</tr>");
				print("<tr>");
					print("<td align=\"right\">");
						print("Dateianhänge");
					print("</td>");
					print("<td>");
						print(":");
					print("</td>");
					print("<td>");
						print("<select style=\"width: 300px;\" id=\"attachment\">");
							foreach($data["Attachment"] as $id => $attachment)
								{
								$unit = 0;
								$size = $attachment["AirSyncBase"]["EstimatedDataSize"];

								while($size > 999)
									list($size, $unit) = array($size / 1024, $unit + 1);

								$label = number_format($size, 3 - strlen($size)) . " " . array("Byte", "KB", "MB", "GB", "TB", "PB", "EB", "ZB", "YB", "NB", "DB")[$unit];

								print("<option value=\"" . $id . "\">");
									printf("%s (%s)", $attachment["AirSyncBase"]["DisplayName"], $label);
								print("</option>");
								}
						print("</select>");

						print(" ");
						print("[");
							print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'AttachmentDownload' });\">");
								print("Herunterladen");
							print("</span>");
						print("]");
					print("</td>");
				print("</tr>");
				}

		print("</table>");
		}
	}

if($Request["Cmd"] == "Upload")
	{
	if(active_sync_get_class_by_collection_id($Request["AuthUser"], $_GET["CollectionId"]) == "Email")
		{
		$file = ACTIVE_SYNC_DAT_DIR . "/../web/temp/" . $_GET["ItemId"];

		if($_GET["LongId"] == 0)
			{
			unlink($file);

			print(1);
			}

		if($_GET["LongId"] == 1)
			{
			file_put_contents($file, base64_decode($_POST["Data"]));

			print(1);
			}
		}
	}
?>
