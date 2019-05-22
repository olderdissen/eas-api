<?
function active_sync_time_zone_information_decode($expression)
	{
	$retval = unpack("lBias/a64StandardName/A16StandardDate/lStandardBias/a64DaylightName/A16DaylightDate/lDaylightBias", $expression);

	return($retval);
	}
?>
