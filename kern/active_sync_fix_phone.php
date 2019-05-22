<?
function active_sync_fix_phone($string)
	{
	$retval = array();

	for($position = 0; $position < strlen($string); $position = $position + 1)
		{
		if(strpos("+0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZ", strtoupper($string[$position])) === false)
			continue;

		$retval[] = $string[$position];
		}

	return(implode("", $retval));
	}
?>
