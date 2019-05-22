<?
function active_sync_time_zone_information_encode($Bias, $StandardName, $StandardDate, $StandardBias, $DaylightName, $DaylightDate, $DaylightBias)
	{
	$retval = pack("la64A16la64A16l", $Bias, $StandardName, $StandardDate, $StandardBias, $DaylightName, $DaylightDate, $DaylightBias);

	return($retval);
	}
?>
