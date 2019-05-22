<?
function active_sync_postfix_virtual_alias_maps_exists($user)
	{
	$host = active_sync_get_domain();

	$file = active_sync_postfix_virtual_alias_maps_db();

	$data = (file_exists($file) === false ? array() : file($file));

	foreach($data as $id => $line)
		{
		list($key, $val) = (strpos($line, " ") === false ? array($line, "") : explode(" ", $line, 2));

		if(trim($key) == $user . "@" . $host)
			return(1);
		}

	return(0);
	}
?>
