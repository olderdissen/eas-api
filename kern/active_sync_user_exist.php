<?
function active_sync_user_exist($expression)
	{
	$settings = active_sync_get_settings(DAT_DIR . "/login.data");

	foreach($settings["login"] as $user)
		{
		if($user["User"] != $expression)
			continue;

		return(1);
		}

	return(0);
	}
?>
