<?
function active_sync_get_devices_by_user($user)
	{
	$retval = array();

	foreach(glob(DAT_DIR . "/" . $user . "/*.sync") as $file)
		$retval[] = basename($file, ".sync");

	if(count($retval) > 1)
		sort($retval);

	return($retval);
	}
?>
