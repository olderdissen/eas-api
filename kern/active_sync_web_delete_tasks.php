<?
function active_sync_web_delete_tasks($request)
	{
	print(unlink(DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/" . $request["ServerId"] . ".data") === false ? 8 : 1);
	}
?>
