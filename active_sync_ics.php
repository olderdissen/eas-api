<?php

function active_sync_ics_escape($str)
	{
	return(strtr($str, ["\x3B" => "\\;", "\x2C" => "\\,", "\x0D" => "", "\x0A" => "\\n", "\x09" => "\\t"]));
	}

function active_sync_ics_from_data($user, $collection_id, $server_id, $version = 21)
	{
	if(! in_array($version, [21, 30, 40]))
		return(false);

	$data = active_sync_get_settings_data($user, $collection_id, $server_id);

	foreach(["Email1Address", "Email2Address", "Email3Address"] as $token)
		{
		if(! isset($data["Contacts"][$token]))
			continue;

		list($data_name, $data_mail) = active_sync_mail_parse_address($data["Contacts"][$token]);

		$data["Contacts"][$token] = $data_mail;
		}

	$data["Contacts"]["FileAs"] = active_sync_create_fullname_from_data($data, 0);

	foreach(["Birthday", "Anniversary"] as $token)
		if(isset($data["Contacts"][$token]))
			$data["Contacts"][$token] = date("Y-m-d", strtotime($data["Contacts"][$token]));

	$retval = [
		sprintf("BEGIN:VCARD"),
		sprintf("VERSION:%s", number_format($version / 10, 1, ".", "")),
		sprintf("REV:%s", date("Y-m-d\TH:i:s\Z", filemtime(ACTIVE_SYNC_DAT_DIR . "/" . $user . "/" . $collection_id . "/" . $server_id . ".data"))),
#		sprintf("UID:%s", $server_id)
		];

	$fields = [
		# this is ics_from_data, not data_to_ics.
		# "FN" => "FileAs",
		"FileAs" => "FN",
		"Email1Address" => "EMAIL",
		"Email2Address" => "EMAIL",
		"Email3Address" => "EMAIL",
		"JobTitle" => "ROLE",
		"WebPage" => "URL",
#		"Birthday" => "BDAY",
		"ManagerName" => "MANAGER",
		"Spouse" => "SPOUSE",
		"AssistantName" => "ASSISTANT",
#		"Anniversary" => "ANNIVERSARY"
		];

	foreach($fields as $token => $key)
		if(isset($data["Contacts"][$token]))
			$retval[] = sprintf("%s:%s", $key, $data["Contacts"][$token]);

	if($version == 21)
		$fields = [
			# this is ics_from_data, not data_to_ics.
			# "TEL;WORK;FAX" => "BusinessFaxNumber"
			"BusinessFaxNumber" => "TEL;WORK;FAX",
			"HomeFaxNumber" => "TEL;HOME;FAX",
			"MobilePhoneNumber" => "TEL;CELL",
			"PagerNumber" => "TEL;PAGER",
			"HomePhoneNumber" => "TEL;HOME",
			"BusinessPhoneNumber" => "TEL;WORK",
			"CarPhoneNumber" => "TEL;CAR"
			];

	if($version == 30)
		$fields = [
			"BusinessFaxNumber" => "TEL;TYPE=WORK,FAX",
			"HomeFaxNumber" => "TEL;TYPE=HOME,FAX",
			"MobilePhoneNumber" => "TEL;TYPE=CELL",
			"PagerNumber" => "TEL;TYPE=PAGER",
			"HomePhoneNumber" => "TEL;TYPE=HOME,VOICE",
			"BusinessPhoneNumber" => "TEL;TYPE=WORK,VOICE",
			"CarPhoneNumber" => "TEL;TYPE=CAR"
			];

	if($version == 40)
		$fields = [
			"BusinessFaxNumber" => "TEL;TYPE=work,fax",
			"HomeFaxNumber" => "TEL;TYPE=home,fax",
			"MobilePhoneNumber" => "TEL;TYPE=cell",
			"PagerNumber" => "TEL;TYPE=pager",
			"HomePhoneNumber" => "TEL;TYPE=home,voice",
			"BusinessPhoneNumber" => "TEL;TYPE=work,voice",
			"CarPhoneNumber" => "TEL;TYPE=car"
			];

	foreach($fields as $token => $key)
		if(isset($data["Contacts"][$token]))
			$retval[] = sprintf("%s:%s", $key, $data["Contacts"][$token]);

	$org = [];

	foreach(["CompanyName", "Department", "OfficeLocation"] as $token)
		$org[] = (isset($data["Contacts"][$token]) ? active_sync_ics_escape($data["Contacts"][$token]) : "");

	if(implode("", $org))
		$retval[] = sprintf("ORG:%s", implode(";", $org));

	$n = [];

	foreach(["LastName", "FirstName", "MiddleName", "Title", "Suffix"] as $token)
		$n[] = (isset($data["Contacts"][$token]) ? active_sync_ics_escape($data["Contacts"][$token]) : "");

	if(implode("", $n))
		$retval[] = sprintf("N:%s", implode(";", $n));

	if(isset($data["Contacts2"]["NickName"]))
		$retval[] = sprintf("NICKNAME:%s", $data["Contacts2"]["NickName"]);

	foreach(["Business" => "WORK", "Home" => "HOME", "Other" => "OTHER"] as $token_prefix => $type)
		{
		$adr = ["", ""];

		foreach(["Street", "City", "State", "PostalCode", "Country"] as $token_suffix)
			$adr[] = (isset($data["Contacts"][$token_prefix . "Address" . $token_suffix]) ? active_sync_ics_escape($data["Contacts"][$token_prefix . "Address" . $token_suffix]) : "");

		if(! implode("", $adr))
			continue;

		if($version == 21)
			$retval[] = sprintf("ADR;%s:%s", strtoupper($type), implode(";", $adr));

		if($version == 30)
			$retval[] = sprintf("ADR;TYPE=%s:%s", strtoupper($type), implode(";", $adr));

		if($version == 40)
			$retval[] = sprintf("ADR;TYPE=%s:%s", strtolower($type), implode(";", $adr));
		}

	if(isset($data["Body"]))
		foreach($data["Body"] as $body)
			if(isset($body["Type"]))
				if($body["Type"] == 1) # Text
					if(isset($body["Data"]))
						if(strlen($body["Data"]))
							$retval[] = sprintf("NOTE:%s", active_sync_ics_escape($body["Data"]));

	if(isset($data["Categories"]))
		{
		$categories = [];

		foreach($data["Categories"] as $category)
			$categories[] = active_sync_ics_escape($category);

		if(implode("", $categories))
			$retval[] = sprintf("CATEGORIES:%s", implode(",", $categories));
		}

	foreach(["IMAddress", "IMAddress2", "IMAddress3"] as $token)
		{
		if(! isset($data["Contacts2"][$token]))
			continue;

		if(strpos($data["Contacts2"][$token], ":") === false)
			continue;

		list($proto, $address) = explode(":", $data["Contacts2"][$token], 2);

		$retval[] = sprintf("X-%s:%s", strtoupper($proto), $address);
		}

	if(isset($data["Contacts"]["Picture"]))
		{
		$magic = $data["Contacts"]["Picture"];
		$magic = substr($magic, 0, 12);
		$magic = base64_decode($magic);

		if(substr($magic, 0, 2) == "BM")
			$format = "bmp";
		elseif(substr($magic, 0, 3) == "GIF")
			$format = "gif";
		elseif(substr($magic, 1, 3) == "PNG")
			$format = "png";
		elseif(substr($magic, 6, 4) == "JFIF")
			$format = "jpeg";
		else
			$format = "x-icon";

		if($version == 21)
			$retval[] = sprintf("PHOTO;%s;ENCODING=BASE64:%s", strtoupper($format), $data["Contacts"]["Picture"]);

		if($version == 30)
			$retval[] = sprintf("PHOTO;TYPE=%s;ENCODING=b:%s", strtoupper($format), $data["Contacts"]["Picture"]);

		if($version == 40)
			$retval[] = sprintf("PHOTO:data:image/%s;BASE64%s", strtolower($format), $data["Contacts"]["Picture"]);
		}

#	$retval[] = sprintf("SOURCE:http://%s%s", $_SERVER["HTTP_HOST"], $_SERVER["REQUEST_URI"]);
	$retval[] = sprintf("END:VCARD");

#	foreach($retval as $id => $line)
#		$retval[$id] = trim(chunk_split($retval[$id], 74, "\n "););

	return(implode("\n", $retval));
	}

function active_sync_ics_to_data(& $data)
	{
	$retval = [];

	$data = active_sync_ics_unfold($data);

	while(strlen($data))
		{
		if(strpos($data, "\n") === false)
			$data .= "\n";

		list($line, $data) = explode("\n", $data, 2);

		if(strpos($line, ":") === false)
			$line .= ":";

		list($name, $value) = explode(":", $line, 2);

		if(strpos($name, ";") === false)
			$name .= ";";

		list($name, $param) = explode(";", $name, 2);

		if($name == "BEGIN")
			$retval[$value] = active_sync_ics_to_data($data);
		elseif($name == "END")
			break;
		elseif($name == "ATTENDEE" || $name == "ORGANIZER")
			{
			list($proto, $email) = explode(":", $value, 2);

			foreach(str_getcsv($param, ";") as $test)
				{
				if(strpos($test, "=") === false)
					$test .= "=";

				list($param_key, $param_value) = explode("=", trim($test), 2);

				$retval[$name][$email][$param_key] = $param_value;
				}
			}
		else
			$retval[$name] = $value;
		}

	return($retval);
	}

function active_sync_ics_unfold($subject)
	{
	return(strtr($subject, ["\x0D" => "", "\x0A\x09" => "", "\x0A\x20" => ""]));
	}
?>
