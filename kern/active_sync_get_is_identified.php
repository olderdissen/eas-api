<?
function active_sync_get_is_identified($request)
	{
	$settings = active_sync_get_settings(DAT_DIR . "/login.data");

	foreach($settings["login"] as $login)
		{
		if($login["User"] != $request["AuthUser"])
			continue;

		return($login["Pass"] == $request["AuthPass"] ? 1 : 0);
		}

	return(0);
	}
?>
