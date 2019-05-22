<?
function active_sync_web_edit($request)
	{
	$table = array(
		"Calendar" => "active_sync_web_edit_calendar",
		"Contacts" => "active_sync_web_edit_contacts",
		"Email" => "active_sync_web_edit_email",
		"Notes" => "active_sync_web_edit_notes",
		"Tasks" => "active_sync_web_edit_tasks"
		);

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
