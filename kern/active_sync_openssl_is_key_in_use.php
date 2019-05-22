<?
function active_sync_openssl_is_key_in_use($expression)
	{
	$settings = active_sync_get_settings_cert();

	foreach(array("requests", "certs") as $type)
		{
		if(isset($settings[$type]) === false)
			continue;

		foreach($settings[$type] as $name => $key)
			{
			if($key != $expression)
				continue;

			return(1);
			}
		}

	return(0);
	}
?>
