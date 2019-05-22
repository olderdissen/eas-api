<?
function active_sync_get_is_phone($phone)
	{
	$prefixes = array();

	$prefixes[] = "+40-7";
	$prefixes[] = "+49-15";
	$prefixes[] = "+49-16";
	$prefixes[] = "+49-17";

	foreach($prefixes as $prefix)
		{
		$prefix	= active_sync_fix_phone($prefix);
		$phone	= active_sync_fix_phone($phone);

		if(substr($phone, 0, strlen($prefix)) != $prefix)
			continue;

		return(1);
		}

	return(0);
	}
?>
