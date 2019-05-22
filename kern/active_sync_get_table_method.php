<?
function active_sync_get_table_method()
	{
	$table = array
		(
		"GET" => "active_sync_http_method_get", # used by web interface
		"POST" => "active_sync_http_method_post",
#		"PUT" => "active_sync_http_method_put",
#		"PATCH" => "active_sync_http_method_patch",
#		"DELETE" => "active_sync_http_method_delete",
#		"HEAD" => "active_sync_http_method_read",
		"OPTIONS" => "active_sync_http_method_options",
#		"CONNECT" => "active_sync_http_method_connect",
#		"TRACE" => "active_sync_http_method_trace"
		);

	return($table);
	}
?>
