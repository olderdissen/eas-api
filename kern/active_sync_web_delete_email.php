<?
function active_sync_web_delete_email($request)
	{
	print(unlink(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/" . $request["ServerId"] . ".data") === false ? 8 : 1);
	}
?>
