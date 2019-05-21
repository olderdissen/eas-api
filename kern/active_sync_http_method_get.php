<?
function active_sync_http_method_get($request)
	{
	if(defined("WEB_DIR") === false)
		header(implode(" ", array($_SERVER["SERVER_PROTOCOL"], 204, "No Content")));
	elseif(is_dir(WEB_DIR) === false)
		header(implode(" ", array($_SERVER["SERVER_PROTOCOL"], 204, "No Content")));
	else
		{
		header(implode(" ", array($_SERVER["SERVER_PROTOCOL"], 307, "Permanent Redirect")));

		header(implode(": ", array("Location", "web")));
		}
	}
?>
