<?
function active_sync_web($request)
	{
	$table = array(
		"Data" => "active_sync_web_data",
		"Delete" => "active_sync_web_delete",
		"Edit" => "active_sync_web_edit",
		"List" => "active_sync_web_list",
		"Meeting" => "active_sync_web_meeting",
		"Print" => "active_sync_web_print",
		"Save" => "active_sync_web_save"
		);

	$retval = null;

	foreach($table as $command => $function)
		{
		if($request["Cmd"] != $command)
			continue;

		if(function_exists($function) === false)
			continue;

		$retval = $function($request);
		}

	return($retval);
	}
?>
