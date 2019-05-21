<?
function active_sync_http_query_parse()
	{
	$retval = array(
		"AcceptMultiPart"	=> "F",
		"AttachmentName"	=> "",
		"Cmd"			=> "",
		"CollectionId"		=> "",
		"DeviceId"		=> "",
		"DeviceType"		=> "",
		"ItemId"		=> "",
		"Locale"		=> "",
		"LongId"		=> "",
		"Occurence"		=> "",
		"PolicyKey"		=> "",
		"ProtocolVersion"	=> "",
		"SaveInSent"		=> "F",
		"User"			=> "",

		"AuthDomain"		=> "",	# extra field, not specified
		"AuthPass"		=> "",	# extra field, not specified
		"AuthUser"		=> "",	# extra field, not specified
		"Domain"		=> "",	# extra field, not specified
		"ContentType"		=> "",	# extra field, not specified
		"Method"		=> "",	# extra field, not specified
		"UserAgent"		=> ""	# extra field, not specified
		);

	$query = (isset($_SERVER["QUERY_STRING"]) === false ? "" : $_SERVER["QUERY_STRING"]);

	if($query == "")
		{
		}
	elseif(preg_match("#^([A-Za-z0-9+/]{4})*([A-Za-z0-9+/]{4}|[A-Za-z0-9+/]{3}=|[A-Za-z0-9+\/]{2}==)?$#", $query) == 1)
		{
		$b = base64_decode($query);

		$commands = active_sync_get_table_command();

		$parameters = active_sync_get_table_parameter();

		$device_id_length = ord($b[4]);							# DeviceIdLength
		$policy_key_length = ord($b[5 + $device_id_length]);				# PolicyKeyLength
		$device_type_length = ord($b[6 + $device_id_length + $policy_key_length]);	# DeviceTypeLength

		$z = unpack("CProtocolVersion/CCommandCode/vLocale/CDeviceIdLength/H" . ($device_id_length * 2) . "DeviceId/CPolicyKeyLength" . ($policy_key_length == 4 ? "/VPolicyKey" : "") . "/CDeviceTypeLength/A" . ($device_type_length) . "DeviceType", $b);

		$b = substr($b, 7 + $device_id_length + $policy_key_length + $device_type_length);

		while(strlen($b) > 0)
			{
			$f = ord($b[1]);
			$g = unpack("CTag/CLength/A" . $f . "Value", $b);
			$b = substr($b, 2 + $f);

			if($g["Tag"] == 7) # options
				{
				$retval["SaveInSent"]		= (($g["Value"] & 0x01) == 0x01 ? "T" : "F");
				$retval["AcceptMultiPart"]	= (($g["Value"] & 0x02) == 0x02 ? "T" : "F");
				}
			elseif(isset($parameters[$g["Tag"]]))
				$retval[$parameters[$g["Tag"]]]	= $g["Value"];
			}

		if(isset($commands[$z["CommandCode"]]))
			$retval["Cmd"] = $commands[$z["CommandCode"]];

		foreach(array("DeviceId", "DeviceType", "Locale", "PolicyKey", "ProtocolVersion") as $key)
			$retval[$key] = (isset($z[$key]) === false ? "" : $z[$key]);

		$retval["ProtocolVersion"] = $retval["ProtocolVersion"] / 10; # 141 -> 14.1
		}
	else
		{
		foreach(array("AcceptMultiPart" => "HTTP_MS_ASACCEPTMULTIPART", "PolicyKey" => "HTTP_X_MS_POLICYKEY", "ProtocolVersion" => "HTTP_MS_ASPROTOCOLVERSION") as $key_a => $key_b)
			$retval[$key_a] = (isset($_SERVER[$key_b]) === false ? "" : $_SERVER[$key_b]);

		foreach(array("AttachmentName", "Cmd", "CollectionId", "DeviceId", "DeviceType", "ItemId", "LongId", "Occurence", "SaveInSent", "User") as $key)
			$retval[$key] = (isset($_GET[$key]) === false ? "" : $_GET[$key]);
		}

	foreach(array("AuthPass" => "PHP_AUTH_PW", "AuthUser" => "PHP_AUTH_USER", "ContentType" => "CONTENT_TYPE", "Method" => "REQUEST_METHOD", "UserAgent" => "HTTP_USER_AGENT") as $key_a => $key_b)
		$retval[$key_a] = (isset($_SERVER[$key_b]) === false ? "" : $_SERVER[$key_b]);

	$domain = "";

	foreach(array("", "Auth") as $key)
		{
		$retval[$key . "User"] = strtolower($retval[$key . "User"]); # take care about brain-disabled-users

		list($retval[$key . "Domain"], $retval[$key . "User"]) = (strpos($retval[$key . "User"], "\\") === false ? array($domain, $retval[$key . "User"]) : explode("\\", $retval[$key . "User"], 2));
		}

#	$data = (isset($GLOBALS["HTTP_RAW_POST_DATA"]) === false ? null : $GLOBALS["HTTP_RAW_POST_DATA"]);
	$data = file_get_contents("php://input");

	$retval["wbxml"] = $data;

	$retval["xml"] = ($retval["ContentType"] == "application/vnd.ms-sync.wbxml" ? active_sync_wbxml_request_a($data) : "");

	return($retval);
	}
?>
