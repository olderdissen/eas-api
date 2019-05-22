<?
function active_sync_get_supported_commands()
	{
	$retval = array();

	$handles = active_sync_get_table_handle();

	foreach($handles as $command => $function)
		{
		if(function_exists($function) === false)
			continue;

		$retval[] = $command;
		}

	return(implode(",", $retval));
	}
?>
