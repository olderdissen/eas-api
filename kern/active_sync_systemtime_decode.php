<?
function active_sync_systemtime_decode($expression)
	{
	# Year ::= 1601 .. 30827
	# Month ::= 1 .. 12
	# DayOfWeek ::= 0 .. 6

	$retval = unpack("SYear/SMonth/SDayOfWeek/SDay/SHour/SMinute/SSecond/SMilliseconds", $expression);

	return($retval);
	}
?>
