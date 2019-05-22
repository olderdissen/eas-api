<?
function active_sync_get_need_wipe($request)
	{
	$settings = active_sync_get_settings(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["DeviceId"] . ".sync");

	return(isset($settings["Wipe"]) === false ? 0 : 1);
	}
?>
