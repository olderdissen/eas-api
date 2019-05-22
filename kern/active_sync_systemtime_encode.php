<?
function active_sync_systemtime_encode($Year, $Month, $DayOfWeek, $Day, $Hour, $Minute, $Second, $Milliseconds)
	{
	# Year ::= 1601 .. 30827
	# Month ::= 1 .. 12
	# DayOfWeek ::= 0 .. 6

	return(pack("SSSSSSSS", $Year, $Month, $DayOfWeek, $Day, $Hour, $Minute, $Second, $Milliseconds));
	}
?>
