<?
function active_sync_web_print_email($request)
	{
	$data = active_sync_get_settings_data($request["AuthUser"], $request["CollectionId"], $request["ServerId"]);

	if(isset($data["Body"]["Type"]) === false)
		$data = "";
	elseif($data["Body"]["Type"] == 1) # text
		$data = $data["Body"]["Data"];
	elseif($data["Body"]["Type"] == 2) # html
		$data = $data["Body"]["Data"];
	else
		$data = "";

	$file = "/tmp/" . active_sync_create_guid();

	file_put_contents($file, $data);

	exec("lpr " . $file);

	unlink($file);

	print(1);
	}
?>
