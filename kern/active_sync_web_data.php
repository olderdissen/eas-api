<?
function active_sync_web_data($request)
	{
	$table = array();

	$table["Calendar"]	= "active_sync_web_data_calendar";
	$table["Contacts"]	= "active_sync_web_data_contacts";
	$table["Email"]		= "active_sync_web_data_email";
	$table["Notes"]		= "active_sync_web_data_notes";
	$table["Tasks"]		= "active_sync_web_data_tasks";

	$retval = null;

	$default_class = active_sync_get_class_by_collection_id($request["AuthUser"], $request["CollectionId"]);

	foreach($table as $class => $function)
		{
		if($default_class != $class)
			continue;

		if(function_exists($function) === false)
			continue;

		$retval = $function($request);
		}

	return($retval);
	}
?>
