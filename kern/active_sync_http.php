<?
function active_sync_http()
	{
	$request = active_sync_http_query_parse();

	$table = active_sync_get_table_method();

	$method = $request["Method"];

	if(isset($table[$method]) === false)
		header(implode(" ", array($_SERVER["SERVER_PROTOCOL"], 501, "Not Implemented")));
	elseif(strlen($table[$method]) == 0)
		header(implode(" ", array($_SERVER["SERVER_PROTOCOL"], 501, "Not Implemented")));
	elseif(function_exists($table[$method]) === false)
		header(implode(" ", array($_SERVER["SERVER_PROTOCOL"], 501, "Not Implemented")));
	else
		$table[$method]($request);
	}
?>
