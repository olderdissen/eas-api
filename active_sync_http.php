<?php

define("ACTIVE_SYNC_HTTP_AUTHENTICATE_REALM", "T-ActiveSync-Realm");

function active_sync_http(& $handle)
	{
	active_sync_http_query_parse($handle);

	if($_SERVER["PHP_SELF"] == "/active-sync/index.php")
		if(! defined("ACTIVE_SYNC_WEB_DIR"))
			http_response_code(204);
		elseif(! is_dir(ACTIVE_SYNC_WEB_DIR))
			http_response_code(204);
		else
			header("Location: web");

	if($_SERVER["PHP_SELF"] == "/Autodiscover/Autodiscover.xml")
		if(! isset($_SERVER["REQUEST_METHOD"]))
			http_response_code(501);
		elseif($_SERVER["REQUEST_METHOD"] == "GET")
			active_sync_handle_autodiscover($handle["request"]);
		elseif($_SERVER["REQUEST_METHOD"] == "POST")
			active_sync_handle_autodiscover($handle["request"]);
		else
			http_response_code(501);

	if($_SERVER["PHP_SELF"] == "/Microsoft-Server-ActiveSync")
		if(! isset($_SERVER["REQUEST_METHOD"]))
			http_response_code(501);
		elseif($_SERVER["REQUEST_METHOD"] == "OPTIONS")
			{
			header("MS-Server-ActiveSync: " . active_sync_get_version());
			header("MS-ASProtocolVersions: " . active_sync_get_supported_versions());
			# header("X-MS-RP: " . active_sync_get_supported_versions());
			header("MS-ASProtocolCommands: " . active_sync_get_supported_commands());
			header("Allow: OPTIONS,POST");
			header("Public: OPTIONS,POST");
			}
		elseif($_SERVER["REQUEST_METHOD"] == "POST")
			{
			$handle["logging"] = [];

			if(defined("ACTIVE_SYNC_DEBUG_HEADERS"))
				if(ACTIVE_SYNC_DEBUG_HEADERS)
					{
					$table = [
						# "ACCEPT_LANGUAGE",
						# Authorization -> PHP_AUTH_USER, PHP_AUTH_PW
						"CONTENT_TYPE",
						# "HTTP_COOKIE",
						"HTTP_MS_ASACCEPTMULTIPART",
						"HTTP_MS_ASPROTOCOLVERSION",
						"HTTP_USER_AGENT",
						"HTTP_X_MS_POLICYKEY",

						"QUERY_STRING"
						];

					$handle["logging"][] = "";

					foreach($table as $field)
						if(isset($_SERVER[$field]))
							$handle["logging"][] = sprintf("  %-32s : %s", $field, $_SERVER[$field]);

					$handle["logging"][] = "";
					}

			if(isset($handle["request"]["xml"]))
				$handle["logging"][] = active_sync_xml_privacy($handle["request"]["xml"]);

			active_sync_debug(implode("\n", $handle["logging"]), "REQUEST");

			$handle["response"] = [];

			if(! active_sync_get_is_identified($handle["request"]))
				header('WWW-Authenticate: basic realm="ActiveSync"');
			elseif($handle["request"]["DeviceId"] == "validate")
				http_response_code(501);
			else
				{
				active_sync_folder_init($handle["request"]["AuthUser"]);

				$table = active_sync_get_table_handle();

				$cmd = $handle["request"]["Cmd"];

				if(! isset($table[$cmd]))
					http_response_code(501);
				elseif(! strlen($table[$cmd]))
					http_response_code(501);
				elseif(! function_exists($table[$cmd]))
					http_response_code(501);
				else
					$handle["response"]["wbxml"] = $table[$cmd]($handle["request"]);

				if(! headers_sent())
					{
					header_remove("X-Powered-By");

					if(isset($handle["response"]["wbxml"]))
						{
						header("Content-Type: application/vnd.ms-sync.wbxml");
						header("Content-Length: " . strlen($handle["response"]["wbxml"]));
						}
					}

				if(isset($handle["response"]["wbxml"]))
					print($handle["response"]["wbxml"]);
				}

			if(isset($handle["response"]["wbxml"]))
				$handle["response"]["xml"] = active_sync_wbxml_load($handle["response"]["wbxml"]);

			if(isset($handle["response"]["xml"]))
				$handle["response"]["xml"] = active_sync_xml_privacy($handle["response"]["xml"]);

			if(isset($handle["response"]["xml"]))
				active_sync_debug($handle["response"]["xml"], "RESPONSE");
			else
				active_sync_debug("", "RESPONSE");
			}
		else
			http_response_code(501);
	}

function active_sync_http_query_parse(& $handle)
	{
	# this function can be called by user-defined-web-app

	$handle["request"] = [
		"Cmd" => "",
		"DeviceId" => "",
		"DeviceType" => "",
		"Locale" => "",
		"SaveInSent" => "F",

		"AttachmentName" => "",
		"CollectionId" => "",
		"ItemId" => "",
		"LongId" => "",
		"Occurence" => "",
		"User" => "",

		"AcceptMultiPart" => "F",
		"PolicyKey" => "",
		"ProtocolVersion" => "",

		"AuthPass" => "",	# extra field, not specified
		"AuthUser" => "",	# extra field, not specified
		];

	$table = [
		"AcceptMultiPart" => "HTTP_MS_ASACCEPTMULTIPART",
		"PolicyKey" => "HTTP_X_MS_POLICYKEY",
		"ProtocolVersion" => "HTTP_MS_ASPROTOCOLVERSION",

		"AuthUser" => "PHP_AUTH_USER",
		"AuthPass" => "PHP_AUTH_PW",
		];

	foreach($table as $key => $trans)
		if(isset($_SERVER[$trans]))
			$handle["request"][$key] = $_SERVER[$trans];

	if(isset($_SERVER["QUERY_STRING"]))
		if(strlen($_SERVER["QUERY_STRING"]))
			if(preg_match("#^([A-Za-z0-9+/]{4})*([A-Za-z0-9+/]{4}|[A-Za-z0-9+/]{3}=|[A-Za-z0-9+\/]{2}==)?$#", $_SERVER["QUERY_STRING"]))
				{
				$query = base64_decode($_SERVER["QUERY_STRING"]);

				$commands = active_sync_get_table_command();

				$parameters = [
					0 => "AttachmentName",
					1 => "CollectionId",
					3 => "ItemId",
					4 => "LongId",
					6 => "Occurence",
					7 => "Options",
					8 => "User",
					];

				$device_id_length = ord($query[4]);						# DeviceIdLength
				$policy_key_length = ord($query[5 + $device_id_length]);			# PolicyKeyLength
				$device_type_length = ord($query[6 + $device_id_length + $policy_key_length]);	# DeviceTypeLength

				$table = [
					"CProtocolVersion",
					"CCommandCode",
					"vLocale",
					"CDeviceIdLength",
					"H" . strval($device_id_length * 2) . "DeviceId",
					"CPolicyKeyLength",
					"VPolicyKey",
					"CDeviceTypeLength",
					"A" . strval($device_type_length * 1) . "DeviceType",
					];

				if($policy_key_length != 4)
					unset($table[6]);

				$z = unpack($table, $query);

				$query = substr($query, 7 + $device_id_length + $policy_key_length + $device_type_length);

				while(strlen($query))
					{
					$tag = ord($query[0]);
					$length = ord($query[1]);
					$value = substr($query, 2, $length);
					$query = substr($query, 2 + $length);

					if($tag == 7) # options
						{
						$handle["request"]["SaveInSent"] = (($value & 0x01) ? "T" : "F");
						$handle["request"]["AcceptMultiPart"] = (($value & 0x02) ? "T" : "F");
						}
					elseif(isset($parameters[$tag]))
						{
						$key = $parameters[$tag];

						$handle["request"][$key] = $value;
						}
					}

				if(isset($commands[$z["CommandCode"]]))
					$handle["request"]["Cmd"] = $commands[$z["CommandCode"]];

				$table = [
					"DeviceId",
					"DeviceType",
					"Locale",
					"PolicyKey",
					"ProtocolVersion"
					];

				foreach($table as $key)
					if(isset($z[$key]))
						$handle["request"][$key] = $z[$key];

				$handle["request"]["ProtocolVersion"] /= 10; # 141 -> 14.1
				}
			else
				{
				$table = [
					"AttachmentName",
					"Cmd",
					"CollectionId",
					"DeviceId",
					"DeviceType",
					"ItemId",
					"LongId",
					"Occurence",
					"SaveInSent",
					"User"
					];

				foreach($table as $key)
					if(isset($_GET[$key]))
						$handle["request"][$key] = $_GET[$key];
				}

	# user in query can vary from user in authentication
	foreach(["AuthUser", "User"] as $key)
		{
		# take care about brain-disabled-users
		$handle["request"][$key] = strtolower($handle["request"][$key]);

		if(strpos($handle["request"][$key], "\x5C") !== false) # \
			list($null, $handle["request"][$key]) = explode("\x5C", $handle["request"][$key]);

		if(strpos($handle["request"][$key], "\x40") !== false) # @
			list($handle["request"][$key], $null) = explode("\x40", $handle["request"][$key]);
		}

#	if(isset($_SERVER["CONTENT_LENGTH"]))
#		if($_SERVER["CONTENT_LENGTH"] > 0)

	if(isset($_SERVER["CONTENT_TYPE"]))
		{
		$data = file_get_contents("php://input");

		if($_SERVER["CONTENT_TYPE"] == "application/vnd.ms-sync") # 2.5 ???
			{
			$handle["request"]["xml"] = active_sync_wbxml_load($data);
			$handle["request"]["wbxml"] = $data;
			}

		if($_SERVER["CONTENT_TYPE"] == "application/vnd.ms-sync.wbxml")
			{
			$handle["request"]["xml"] = active_sync_wbxml_load($data);
			$handle["request"]["wbxml"] = $data;
			}

		if($_SERVER["CONTENT_TYPE"] == "text/xml")
			$handle["request"]["xml"] = $data;
		}

	return(true);
	}
?>
