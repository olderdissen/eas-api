<?
if($Request["Cmd"] == "Search")
	{
	if(active_sync_get_class_by_collection_id($Request["AuthUser"], $Request["CollectionId"]) == "Calendar")
		{
		################################################################################
		# declare wich fields to search for
		################################################################################

		switch($Request["Field"])
			{
			case("Body:Data"):
				$Request["Field"] = array("Data");

				break;
			default:
				$Request["Field"] = array($Request["Field"]);

				break;
			}

		################################################################################
		# init return
		################################################################################

		$retval = array();

		################################################################################
		# search for data
		################################################################################

		if(strlen($Request["Search"]) > 0)
			{
			foreach(glob(DAT_DIR . "/" . $Request["AuthUser"] . "/" . $Request["CollectionId"] . "/*.data") as $file)
				{
				################################################################################
				# get ServerId from filename
				################################################################################

				$server_id = basename($file, ".data");

				################################################################################
				# get data
				################################################################################

				$data = active_sync_get_settings_data($Request["AuthUser"], $Request["CollectionId"], $server_id);

				################################################################################

				foreach($Request["Field"] as $key)
					{
					switch($key)
						{
						case("Data"):
							if(isset($data["Body"][0][$key]) === false)
								{
								continue;
								}

							$item = $data["Body"][0][$key];

							if(substr(strtolower($item), 0, strlen($Request["Search"])) != strtolower($Request["Search"]))
								{
								continue;
								}

							if(strlen($Request["Search"]) == strlen($item))
								{
								continue;
								}

							$temp = array();

							$temp[] = "<span class=\"suggest_found_a\">";
							$temp[] = substr($item, 0, strlen($Request["Search"]));
							$temp[] = "</span>";
							$temp[] = substr($item, strlen($Request["Search"]));

							$temp = array($item, implode("", $temp));

							if(in_array($temp, $retval) === false)
								$retval[] = $temp;

							break;
						default:
							if(isset($data["Calendar"][$key]) === false)
								continue;

							$item = $data["Calendar"][$key];

							if(substr(strtolower($item), 0, strlen($Request["Search"])) != strtolower($Request["Search"]))
								continue;

							if(strlen($Request["Search"]) == strlen($item))
								continue;

							$temp = array();

							$temp[] = "<span class=\"suggest_found_a\">";
							$temp[] = substr($item, 0, strlen($Request["Search"]));
							$temp[] = "</span>";
							$temp[] = substr($item, strlen($Request["Search"]));

							$temp = array($item, implode("", $temp));

							if(in_array($temp, $retval) === false)
								$retval[] = $temp;

							break;
						}
					}
				}
			}

		################################################################################

		if(count($retval) > 1)
			sort($retval);

		header("Content-Type: application/json; charset=\"UTF-8\"");

		print(json_encode($retval));
		}
	}

################################################################################
# ...
################################################################################

if($Request["Cmd"] == "Show")
	{
	if(active_sync_get_class_by_collection_id($Request["AuthUser"], $Request["CollectionId"]) == "Calendar")
		{
		$timezone_values = active_sync_get_table_timezone_information();

		if($Request["ServerId"] == "")
			$data = array();
		else
			$data = active_sync_get_settings_data($Request["AuthUser"], $Request["CollectionId"], $Request["ServerId"]);

		if(isset($data["Calendar"]) === false)
			{
			print("<p>");
				print("Error: ServerId not found");
			print("</p>");
			}
		else
			{
			################################################################################

			print("<p style=\"font-weight: bold;\">");
				print(isset($data["Calendar"]["Subject"]) ? $data["Calendar"]["Subject"] : "(Kein Titel)");
			print("</p>");

			if((isset($data["Calendar"]["AllDayEvent"]) ? $data["Calendar"]["AllDayEvent"] : 0) == 0)
				{
				if(date("Y", strtotime($data["Calendar"]["StartTime"])) != date("Y", strtotime($data["Calendar"]["EndTime"])))
					{
					print("<p style=\"font-weight: bold;\">");
						print(date("j. F Y , G:i", strtotime($data["Calendar"]["StartTime"]) + 7200));
						print(" - ");
						print(date("j. F Y, G:i", strtotime($data["Calendar"]["EndTime"]) + 7200));
					print("</p>");
					}
				elseif(date("m", strtotime($data["Calendar"]["StartTime"])) != date("m", strtotime($data["Calendar"]["EndTime"])))
					{
					print("<p style=\"font-weight: bold;\">");
						print(date("j. F, G:i", strtotime($data["Calendar"]["StartTime"]) + 7200));
						print(" - ");
						print(date("j. F, G:i", strtotime($data["Calendar"]["EndTime"]) + 7200));
					print("</p>");
					}
				elseif(date("d", strtotime($data["Calendar"]["StartTime"])) != date("d", strtotime($data["Calendar"]["EndTime"])))
					{
					print("<p style=\"font-weight: bold;\">");
						print(date("j. F, G:i", strtotime($data["Calendar"]["StartTime"]) + 7200));
						print(" - ");
						print(date("j. F, G:i", strtotime($data["Calendar"]["EndTime"]) + 7200));
					print("</p>");
					}
				else
					{
					print("<p style=\"font-weight: bold;\">");
						print(date("j. F, G:i", strtotime($data["Calendar"]["StartTime"]) + 7200));
						print(" - ");
						print(date("G:i", strtotime($data["Calendar"]["EndTime"]) + 7200));
					print("</p>");
					}
				}
			else
				{
				print("<p style=\"font-weight: bold;\">");
					print(date("j. F Y", strtotime($data["Calendar"]["StartTime"]) + 7200));
					print(" - ");
					print(date("j. F Y", strtotime($data["Calendar"]["EndTime"]) + 7200));
				print("</p>");

#				print("<p style=\"font-weight: bold;\">");
#					print(date("l j. F", strtotime($data["Calendar"]["StartTime"])));
#					print(" - ");
#					print(date("l j. F", strtotime($data["Calendar"]["EndTime"])));
#				print("</p>");
				}


#			print_r($data["Calendar"]["TimeZone"]);

#			$x = (active_sync_time_zone_information_decode(base64_decode($data["Calendar"]["TimeZone"])));

#			print_r(active_sync_systemtime_decode($x["daylight_date"]));

			foreach($timezone_values as $key => $value)
				{
#				print("<pre>" . $value[0] . "</pre>");

				if($value[0] == $data["Calendar"]["TimeZone"])
					{
					$data["Calendar"]["TimeZone"] = $value[1];

					break;
					}
				}

			print("<p>");
				print("Zeitzone: " . substr($data["Calendar"]["TimeZone"], 10));
			print("</p>");

			if(isset($data["Calendar"]["Location"]) === true)
				{
				print("<p style=\"padding-left: 16px;\">");
					print($data["Calendar"]["Location"]);
				print("</p>");
				}

			if(isset($data["Body"][0]["Data"]) === true)
				{
				print("<p style=\"padding-left: 16px;\">");
					print($data["Body"][0]["Data"]);
				print("</p>");
				}

			if(isset($data["Attendees"]) === true)
				{
				$list = array();
				$list[0] = array(); # Response unknown
				$list[2] = array(); # Tentative
				$list[3] = array(); # Accept
				$list[4] = array(); # Declined
				$list[5] = array(); # Not responded

				print("<hr>");

				foreach($data["Attendees"] as $attendee)
					{
					$status = (isset($attendee["AttendeeStatus"]) ? $attendee["AttendeeStatus"] : 0);

					$list[$status][] = $attendee;
					}

				foreach(array(3 => "Ja", 4 => "Nein", 2 => "Vielleicht", 0 => "Unbekannt") as $status => $value)
					{
					if(count($list[$status]) > 0)
						{
						print($status != 0 ? "<p>" . $value . " (" . count($list[$status]) . ")</p>" : "");

						foreach($list[$status] as $attendee)
							{
							print("<p style=\"padding-left: 15px;\">");
								print(isset($attendee["Name"]) ? $attendee["Name"] . " " : "");
								print("&lt;");
									print($attendee["Email"]);
								print("&gt;");
							print("</p>");
							}
						}
					}

#					print("<p>");
#						print("Teilnehmer");
#						print(" ");
#						print("(");
#						print(count($data["Attendees"][3]));
#						print(")");
#					print("</p>"); # depends on meeting status ???
				}

			print("<hr>");

			print("<p>");
				print("Erinnerung");
				print(":");
				print(" ");
				print(isset($data["Calendar"]["Reminder"]) === false ? '-' : $data["Calendar"]["Reminder"] . " Minuten");
			print("</p>");

			print("<p>");
				print("[");
					print("<span class=\"span_link\" onclick=\"handle_link({ cmd : 'Edit' });\">");
						print("Bearbeiten");
					print("</span>");
				print("]");
				print(" ");
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
			print("</p>");
			}
		}
	}
?>
