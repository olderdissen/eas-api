<?
function active_sync_web_delete_tasks($request)
	{
	$file = DAT_DIR . "/" . $request["AuthUser"] . "/" . $request["CollectionId"] . "/" . $request["ServerId"] . ".data";

	if(file_exists($file) === false)
		$status = 8;
	elseif(unlink($file) === false)
		$status = 8;
	else
		$status = 1;

	print($status);
	}
?>
