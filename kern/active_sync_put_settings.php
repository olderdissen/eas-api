<?
function active_sync_put_settings($file, $data)
	{
#	$data = serialize($data);
	$data = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

	return(file_put_contents($file, $data));
	}
?>
