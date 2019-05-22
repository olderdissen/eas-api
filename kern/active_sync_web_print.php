<?
function active_sync_web_print($request)
	{
	$table = array
		(
		"Email" => "active_sync_web_print_email"
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
