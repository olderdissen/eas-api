<?
if($Request["Cmd"] == "Birthday")
	{
	if(active_sync_get_class_by_collection_id($Request["AuthUser"], $Request["CollectionId"]) == "Contacts")
		{
		$retval = array();

		if(strlen($Request["StartTime"]) == 0)
			{
			}
		elseif(strlen($Request["EndTime"]) == 0)
			{
			}
		elseif($Request["StartTime"] == "*")
			{
			}
		elseif($Request["EndTime"] == "*")
			{
			}
		else
			{
			foreach(glob(DAT_DIR . "/" . $Request["AuthUser"] . "/" . $Request["CollectionId"] . "/*.data") as $file)
				{
				$server_id = basename($file, ".data");

				$data = active_sync_get_settings_data($Request["AuthUser"], $Request["CollectionId"], $server_id);

				foreach(array("Birthday", "FileAs") as $token)
					$data["Contacts"][$token] = (isset($data["Contacts"][$token]) === false ? "" : $data["Contacts"][$token]);

				if($data["Contacts"]["Birthday"] == "")
					continue;

				$data["Contacts"]["Birthday"] = strtotime($data["Contacts"]["Birthday"]);
				$data["Contacts"]["Birthday"] = date("Y", $Request["StartTime"]) . date("-m-d\TH:i:s\Z", $data["Contacts"]["Birthday"]); # set to this year
				$data["Contacts"]["Birthday"] = strtotime($data["Contacts"]["Birthday"]);

				if(($data["Contacts"]["Birthday"] >= $Request["StartTime"]) && ($data["Contacts"]["Birthday"] <= $Request["EndTime"])) # starts at selected day, ends at selected day
					$retval[] = array($data["Contacts"]["Birthday"], $data["Contacts"]["FileAs"], $server_id);
				}
			}

		if(count($retval) > 1)
			sort($retval);

		header("Content-Type: text/javascript; charset=\"UTF-8\"");

		print(json_encode($retval));
		}
	}

if($Request["Cmd"] == "Move")
	{
	if((active_sync_get_class_by_collection_id($Request["AuthUser"], $Request["SrcFldId"]) == "Contacts") && (active_sync_get_class_by_collection_id($Request["AuthUser"], $Request["DstFldId"]) == "Contacts"))
		{
		if($Request["DstMsgId"] == "") # new name
			$Request["DstMsgId"] = active_sync_create_guid_filename($Request["AuthUser"], $Request["DstFldId"]);

		$Src = DAT_DIR . "/" . $Request["AuthUser"] . "/" . $Request["SrcFldId"] . "/" . $Request["SrcMsgId"] . ".data";
		$Dst = DAT_DIR . "/" . $Request["AuthUser"] . "/" . $Request["DstFldId"] . "/" . $Request["DstMsgId"] . ".data";

		$status = (rename($Src, $Dst) === false ? 7 : 1);

		print($status);
		}
	}

if($Request["Cmd"] == "Search")
	{
	if(active_sync_get_class_by_collection_id($Request["AuthUser"], $Request["CollectionId"]) == "Contacts")
		{
		switch($Request["Field"])
			{
			case("Attendees"):
			case("Bcc"):
			case("Cc"):
			case("To"):
				# MobilePhoneNumber/PagerNumber is experimental for sending Email/SMS

				$Request["Field"] = array("Email1Address", "Email2Address", "Email3Address", "MobilePhoneNumber", "PagerNumber");

				break;
			case("BusinessAddressCity");
			case("HomeAddressCity");
			case("OtherAddressCity");
				$Request["Field"] = array("BusinessAddressCity", "HomeAddressCity", "OtherAddressCity");

				break;
			case("BusinessAddressCountry");
			case("HomeAddressCountry");
			case("OtherAddressCountry");
				$Request["Field"] = array("BusinessAddressCountry", "HomeAddressCountry", "OtherAddressCountry");

				break;
			case("BusinessAddressPostalCode");
			case("HomeAddressPostalCode");
			case("OtherAddressPostalCode");
				$Request["Field"] = array("BusinessAddressPostalCode", "HomeAddressPostalCode", "OtherAddressPostalCode");

				break;
			case("BusinessAddressState");
			case("HomeAddressState");
			case("OtherAddressState");
				$Request["Field"] = array("BusinessAddressState", "HomeAddressState", "OtherAddressState");

				break;
			case("BusinessAddressStreet");
			case("HomeAddressStreet");
			case("OtherAddressStreet");
				$Request["Field"] = array("BusinessAddressStreet", "HomeAddressStreet", "OtherAddressStreet");

				break;
			case("Children"):
			case("FirstName");
			case("MiddleName");
			case("Spouse"):
				# <Children> and <Spouse> don't work here
				# <Child> ::= <FirstName> [ <MiddleName> ] [ <LastName> ]
				# <Spouse> :: = <FirstName> [ <MiddleName> ] [ <LastName> ]
				# explode of such names is just not safe

				$Request["Field"] = array("FirstName", "MiddleName");

				break;
			case("Company"): # ???
				$Request["Field"] = array("CompanyName");

				break;
			case("HomePhoneNumber"):
			case("BusinessPhoneNumber"):
			case("Home2PhoneNumber"):
			case("Business2PhoneNumber"):
				$Request["Field"] = array("HomePhoneNumber", "BusinessPhoneNumber", "Home2PhoneNumber", "Business2PhoneNumber");

				break;
			case("HomeFaxNumber"):
			case("BusinessFaxNumber"):
				$Request["Field"] = array("HomeFaxNumber", "BusinessFaxNumber");

				break;

			default:
				$Request["Field"] = array($Request["Field"]);

				break;
			}

		$retval = array();

		if(strlen($Request["Search"]) > 0)
			{
			foreach(glob(DAT_DIR . "/" . $Request["AuthUser"] . "/" . $Request["CollectionId"] . "/*.data") as $file)
				{
				$server_id = basename($file, ".data");

				$data = active_sync_get_settings_data($Request["AuthUser"], $Request["CollectionId"], $server_id);

				foreach($Request["Field"] as $key)
					{
					switch($key)
						{
						case("Email1Address"):
						case("Email2Address"):
						case("Email3Address"):
						case("MobilePhoneNumber"):
						case("PagerNumber"):
							if(isset($data["Contacts"][$key]) === false)
								break;

							$item = $data["Contacts"][$key];

							list($name, $mail) = active_sync_mail_parse_address($item);

							if($name == "")
								$name = active_sync_create_fullname_from_data_for_email($data);

							$temp = array();
							$test = 0;

							################################################################################
							# check DisplayName
							################################################################################

							foreach(explode(" ", $name) as $part)
								{
								if(strlen($Request["Search"]) > strlen($part))
									{
									$temp[] = $part;
									$temp[] = " ";
									}
								elseif(strtolower(substr($part, 0, strlen($Request["Search"]))) != strtolower($Request["Search"])) # test with all lowercase values
									{
									$temp[] = $part;
									$temp[] = " ";
									}
								elseif($part == $Request["Search"]) # test with original values
									{
									$temp[] = $part;
									$temp[] = " ";
									}
								else
									{
									$temp[] = "<span class=\"suggest_found_a\">";
									$temp[] = substr($part, 0, strlen($Request["Search"]));
									$temp[] = "</span>";
									$temp[] = substr($part, strlen($Request["Search"]));
									$temp[] = " ";

									$test = 1;
									}
								}

							################################################################################
							# check EmailAddress
							################################################################################

							if(strlen($Request["Search"]) > strlen($mail))
								{
								$temp[] = "&lt;";
								$temp[] = $mail;
								$temp[] = "&gt;";
								}
							elseif(strtolower(substr($mail, 0, strlen($Request["Search"]))) != strtolower($Request["Search"])) # test with all lowercase values
								{
								$temp[] = "&lt;";
								$temp[] = $mail;
								$temp[] = "&gt;";
								}
							elseif($mail == $Request["Search"]) # test with original values
								{
								$temp[] = "&lt;";
								$temp[] = $mail;
								$temp[] = "&gt;";
								}
							else
								{
								$temp[] = "&lt;";
								$temp[] = "<span class=\"suggest_found_a\">";
								$temp[] = substr($mail, 0, strlen($Request["Search"]));
								$temp[] = "</span>";
								$temp[] = substr($mail, strlen($Request["Search"]));
								$temp[] = "&gt;";

								$test = 1;
								}

							################################################################################

							if($test == 0)
								break;

							$temp = array("&quot;" . $name . "&quot; &lt;" . $mail . "&gt;", implode("", $temp));

							if(in_array($temp, $retval) === false)
								$retval[] = $temp;

							break;
						case("Categories"):
						case("Children"):
							if(isset($data[$key]) === false)
								break;

							foreach($data[$key] as $item)
								{
								if(strlen($Request["Search"]) > strlen($item))
									continue;

								if(strtolower(substr($item, 0, strlen($Request["Search"]))) != strtolower($Request["Search"])) # test with all lowercase values
									continue;

								if($item == $Request["Search"]) # test with original values
									continue;

								$temp = array();

								$temp[] = "<span class=\"suggest_found_a\">";
								$temp[] = substr($item, 0, strlen($Request["Search"]));
								$temp[] = "</span>";
								$temp[] = substr($item, strlen($Request["Search"]));

								$temp = array($item, implode("", $temp));

								if(in_array($temp, $retval) === false)
									$retval[] = $temp;
								}

							break;
						case("BusinessAddressStreet");
						case("HomeAddressStreet");
						case("OtherAddressStreet");
							if(isset($data["Contacts"][$key]) === false)
								break;

							$item = $data["Contacts"][$key];

							$item = explode(" ", $item);

							if(is_numeric(substr(end($item), 0 , 1)) === true)
								array_pop($item);

							$item = implode(" ", $item);


							if(strlen($Request["Search"]) > strlen($item))
								break;

							if(strtolower(substr($item, 0, strlen($Request["Search"]))) != strtolower($Request["Search"])) # test with all lowercase values
								break;

							if($item == $Request["Search"]) # test with original values
								break;

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
							if(isset($data["Contacts"][$key]) === false)
								break;

							$item = $data["Contacts"][$key];

							if(strlen($Request["Search"]) > strlen($item))
								break;

							if(strtolower(substr($item, 0, strlen($Request["Search"]))) != strtolower($Request["Search"])) # test with all lowercase values
								break;

							if($item == $Request["Search"]) # test with original values
								break;

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

		if(count($retval) > 1)
			sort($retval);

		header("Content-Type: application/json; charset=\"UTF-8\"");

		print(json_encode($retval));
		}
	}

if($Request["Cmd"] == "ShowVCard")
	{
	$data = active_sync_vcard_from_data($Request["AuthUser"], $Request["CollectionId"], $Request["ServerId"]);

#	header("Content-Type: text/plain");
	header("Content-Type: text/x-vcard");
	header('Content-Disposition: inline; filename=' . $Request["ServerId"] . '.vcf');
	header('Content-Length: ' . strlen($data));

	print($data);
	}
?>
