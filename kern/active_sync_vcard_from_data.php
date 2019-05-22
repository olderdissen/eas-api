<?
function active_sync_vcard_from_data($user, $collection_id, $server_id, $version = 21)
	{
	$data = active_sync_get_settings_data($user, $collection_id, $server_id);

	$version = ($version == 40 ? 40 : $version);
	$version = ($version == 30 ? 30 : $version);
	$version = ($version == 21 ? 21 : $version);

	$retval = array();

	$retval[] = implode(":", array("BEGIN", "VCARD"));
	$retval[] = implode(":", array("VERSION", number_format($version / 10, 1, ".", "")));
	$retval[] = implode(":", array("REV", date("Y-m-d\TH:i:s\Z", filemtime(DAT_DIR . "/" . $user . "/" . $collection_id . "/" . $server_id . ".data"))));
	$retval[] = implode(":", array("UID", $server_id));

	foreach(array("FileAs" => "FN", "Email1Address" => "EMAIL", "Email2Address" => "EMAIL", "Email3Address" => "EMAIL", "JobTitle" => "ROLE", "WebPage" => "URL", "Birthday" => "BDAY", "ManagerName" => "MANAGER", "Spouse" => "SPOUSE", "AssistantName" => "ASSISTANT", "Anniversary" => "ANNIVERSARY") as $token => $key)
		{
		if(isset($data["Contacts"][$token]) === false)
			continue;

		$retval[] = implode(":", array($key, $data["Contacts"][$token]));
		}

	if($version == 21)
		$fields = array("BusinessFaxNumber" => "WORK;FAX", "HomeFaxNumber" => "HOME;FAX", "MobilePhoneNumber" => "CELL", "PagerNumber" => "PAGER", "HomePhoneNumber" => "HOME", "BusinessPhoneNumber" => "WORK", "CarPhoneNumber" => "CAR");

	if($version == 30)
		$fields = array("BusinessFaxNumber" => "TYPE=WORK,FAX", "HomeFaxNumber" => "TYPE=HOME,FAX", "MobilePhoneNumber" => "TYPE=CELL", "PagerNumber" => "TYPE=PAGER", "HomePhoneNumber" => "TYPE=HOME,VOICE", "BusinessPhoneNumber" => "TYPE=WORK,VOICE", "CarPhoneNumber" => "TYPE=CAR");

	if($version == 40)
		$fields = array("BusinessFaxNumber" => "TYPE=work,fax", "HomeFaxNumber" => "TYPE=home,fax", "MobilePhoneNumber" => "TYPE=cell", "PagerNumber" => "TYPE=pager", "HomePhoneNumber" => "TYPE=home,voice", "BusinessPhoneNumber" => "TYPE=work,voice", "CarPhoneNumber" => "TYPE=car");

	foreach($fields as $token => $key)
		{
		if(isset($data["Contacts"][$token]) === false)
			continue;

		$retval[] = implode(":", array("TEL;" . $key, $data["Contacts"][$token]));
		}

	$x = array();

	foreach(array("CompanyName", "Department", "OfficeLocation") as $token)
		$x[] = (isset($data["Contacts"][$token]) === false ? "" : str_replace(";", "\;", $data["Contacts"][$token]));

	if(strlen(implode("", $x)) > 0)
		$retval[] = implode(":", array("ORG", implode(";", $x)));

	$x = array();

	foreach(array("LastName", "FirstName", "MiddleName", "Title", "Suffix") as $token)
		$x[] = (isset($data["Contacts"][$token]) === false ? "" : str_replace(";", "\;", $data["Contacts"][$token]));

	if(strlen(implode("", $x)) > 0)
		$retval[] = implode(":", array("N", implode(";", $x)));

	if(isset($data["Contacts2"]["NickName"]) === true)
		$retval[] = "NICKNAME" . ":" . $data["Contacts2"]["NickName"];

	foreach(array("Business" => "WORK", "Home" => "HOME", "Other" => "OTHER") as $token_prefix => $type)
		{
		$x = array("", "");

		foreach(array("Street", "City", "State", "PostalCode", "Country") as $token_suffix)
			$x[] = (isset($data["Contacts"][$token_prefix . "Address" . $token_suffix]) === false ? "" : str_replace(";", "\;", $data["Contacts"][$token_prefix . "Address" . $token_suffix]));

		if(strlen(implode("", $x)) == 0)
			continue;

		if($version == 21)
			$retval[] = implode(":", array("ADR" . ";" . strtoupper($type), implode(";", $x)));

		if($version == 30)
			$retval[] = implode(":", array("ADR" . ";" . "TYPE=" . strtoupper($type), implode(";", $x)));

		if($version == 40)
			$retval[] = implode(":", array("ADR" . ";" . "TYPE=" . strtolower($type), implode(";", $x)));
		}

	if(isset($data["Body"]["Data"]) === true)
		$retval[] = implode(":", array("NOTE", str_replace(array("\r", "\n"), array("", "\\n"), $data["Body"]["Data"])));

	if(isset($data["Categories"]) === true)
		{
		$x = array();

		foreach($data["Categories"] as $category)
			$x[] = str_replace(",", "\,", $category);

		if(strlen(implode(",", $x)) > 0)
			$retval[] = implode(":", array("CATEGORIES", implode(",", $x)));
		}

	foreach(array("IMAddress", "IMAddress2", "IMAddress3") as $token)
		{
		if(isset($data["Contacts2"][$token]) === false)
			continue;

		if(strpos($data["Contacts2"][$token], ":") === false)
			continue;

		list($proto, $address) = explode(":", $data["Contacts2"][$token], 2);

		$retval[] = implode(":", array("X-" . strtoupper($proto), $address));
		}

	if(isset($data["Contacts"]["Picture"]) === true)
		{
		$magic = $data["Contacts"]["Picture"];
		$magic = substr($magic, 0, 12);
		$magic = base64_decode($magic);

		if(substr($magic, 0, 2) == "BM")
			$format = "BMP";
		elseif(substr($magic, 0, 3) == "GIF")
			$format = "GIF";
		elseif(substr($magic, 1, 3) == "PNG")
			$format = "PNG";
		elseif(substr($magic, 6, 4) == "JFIF")
			$format = "JPEG";
		else
			$format = "UNKNOWN";

		if($version == 21)
			$retval[] = implode(":", array(implode(";", array("PHOTO", strtoupper($format), "ENCODING=BASE64")), $data["Contacts"]["Picture"]));

		if($version == 30)
			$retval[] = implode(":", array(implode(";", array("PHOTO", "TYPE=" . strtoupper($format), "ENCODING=B")), $data["Contacts"]["Picture"]));

		if($version == 40)
			$retval[] = implode(":", array("PHOTO", implode(";", array("data:image/" . strtolower($format), "BASE64" . $data["Contacts"]["Picture"]))));
		}

#	$retval[] = implode(":", array("X-ANDROID-CUSTOM", implode(";", array("vnd.android.cursor.item/relation", $data["Contacts"]["Spouse"], 14, "", "", "" "", "", "", "", "", "", "", "", "", ""))));
#	$retval[] = implode(":", array("X-ANDROID-CUSTOM", implode(";", array("vnd.android.cursor.item/nickname", $data["Contacts2"]["NickName"], 1, "", "", "" "", "", "", "", "", "", "", "", "", ""))));
	$retval[] = implode(":", array("SOURCE", "http://" . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"]));
	$retval[] = implode(":", array("END", "VCARD"));

	foreach($retval as $id => $line)
		{
		$retval[$id] = chunk_split($retval[$id], 74, "\n ");
		$retval[$id] = substr($retval[$id], 0, 0 - 2);
		}

	return(implode("\n", $retval));
	}
?>
