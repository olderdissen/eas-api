<?
function active_sync_web_delete_notes($request)
	{
	print(unlink(DAT_DIR . "/" . $request["AuthUser"] . "/" . $Rquest["CollectionId"] . "/" . $request["ServerId"] . ".data") === false ? 8 : 1);
	}
?>
