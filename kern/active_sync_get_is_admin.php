<?
function active_sync_get_is_admin($user)
	{
	$settings = active_sync_get_settings(DAT_DIR . "/login.data");

	foreach($settings["login"] as $login)
		{
		if($login["User"] != $user)
			continue;

		return($login["IsAdmin"]);
		}

	return("F");
	}
?>
