<?
function active_sync_http_method_post($request)
	{
	$logging = $request["wbxml"];

	$logging = active_sync_wbxml_request_b($logging);

	$logging = active_sync_wbxml_pretty($logging);

	active_sync_debug($logging, "REQUEST");

	$response = array();

	$response["wbxml"] = "";
	$response["xml"] = "";

	$identified = active_sync_get_is_identified($request);

	if($identified == 0)
		header("WWW-Authenticate: basic realm=\"ActiveSync\"");
	elseif($request["DeviceId"] != "validate")
		{
		active_sync_folders_init($request["AuthUser"]);

		$contents = "";

		$table = active_sync_get_table_handle();

		$cmd = $request["Cmd"];

		if(isset($table[$cmd]) === false)
			header(implode(" ", array($_SERVER["SERVER_PROTOCOL"], 501, "Not Implemented")));
		elseif(strlen($table[$cmd]) == 0)
			header(implode(" ", array($_SERVER["SERVER_PROTOCOL"], 501, "Not Implemented")));
		elseif(function_exists($table[$cmd]) === false)
			header(implode(" ", array($_SERVER["SERVER_PROTOCOL"], 501, "Not Implemented")));
		else
			$contents = $table[$cmd]($request);

		$length = strlen($contents);

		if(headers_sent() === false)
			{
			header("Content-Length: " . $length);

			if($length > 0)
				header("Content-Type: application/vnd.ms-sync.wbxml");

			header_remove("X-Powered-By");
			}

		print($contents);

		$response["wbxml"] = $contents;
		}

	$logging = $response["wbxml"];

	$logging = active_sync_wbxml_request_b($logging);

	$logging = active_sync_wbxml_pretty($logging);

	active_sync_debug($logging, "RESPONSE");

@	file_put_contents(LOG_DIR . "/in.txt", json_encode($request, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
@	file_put_contents(LOG_DIR . "/out.txt", json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
	}
?>
