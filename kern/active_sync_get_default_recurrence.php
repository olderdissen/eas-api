<?
function active_sync_get_default_recurrence($Class = "Calendar")
	{
	$retval = array();

	if($Class == "Calendar")
		{
		$retval["Type"]			= 4;	# 0 .. 3 | 4 (none) | 5 .. 6
		$retval["Occurrences"]		= 1;	# 1 .. 999
		$retval["Interval"]		= 1;	# 1 .. 999
		$retval["WeekOfMonth"]		= 1;	# 1 (first) .. 4 (fourth) | 5 (last)
		$retval["DayOfWeek"]		= 0;	# 1 | 2 | 4 | 8 | 16 | 32 | 64  127
		$retval["MonthOfYear"]		= 1;	# 1 .. 12
		$retval["Until"]		= date("d.m.Y", strtotime("+ 10 years"));
		$retval["DayOfMonth"]		= 1;	# 1 .. 31
		$retval["CalendarType"]		= 0;	# default
		$retval["IsLeapMonth"]		= 0;	# 0 | 1
		$retval["FirstDayOfWeek"]	= 1;	# 0 (sunday) .. 6 (saturday)
		}

	if($Class == "Email")
		{
		$retval["Type"]			= 4;	# 0 .. 3 | 4 (none) | 5 .. 6
		$retval["Interval"]		= 1;	# 1 .. 999
		$retval["Until"]		= date("d.m.Y", strtotime("+ 10 years"));
		$retval["Occurrences"]		= 1;	# 1 .. 999
		$retval["WeekOfMonth"]		= 1;	# 1 (first) .. 4 (fourth) | 5 (last)
		$retval["DayOfMonth"]		= 1;	# 1 .. 31
		$retval["DayOfWeek"]		= 0;	# 1 | 2 | 4 | 8 | 16 | 32 | 64  127
		$retval["MonthOfYear"]		= 1;	# 1 .. 12

		# email2 !!!
		$retval["CalendarType"]		= 0;	# default
		$retval["IsLeapMonth"]		= 0;	# 0 | 1
		$retval["FirstDayOfWeek"]	= 1;	# 0 (sunday) .. 6 (saturday)
		}

	if($Class == "Tasks")
		{
		$retval["Type"]			= 4;	# 0 .. 3 | 4 (none) | 5 .. 6
		$retval["Start"]		= date("d.m.Y H:i");
		$retval["Until"]		= date("d.m.Y", strtotime("+ 10 years"));
		$retval["Occurrences"]		= 1;	# 1 .. 999
		$retval["Interval"]		= 1;	# 1 .. 999
		$retval["DayOfWeek"]		= 0;	# 1 | 2 | 4 | 8 | 16 | 32 | 64  127
		$retval["DayOfMonth"]		= 1;	# 1 .. 31
		$retval["WeekOfMonth"]		= 1;	# 1 (first) .. 4 (fourth) | 5 (last)
		$retval["MonthOfYear"]		= 1;	# 1 .. 12
		$retval["Regenerate"]		= 0;
		$retval["DeadOccur"]		= 0;
		$retval["CalendarType"]		= 0;	# default
		$retval["IsLeapMonth"]		= 0;	# 0 | 1
		$retval["FirstDayOfWeek"]	= 1;	# 0 (sunday) .. 6 (saturday)
		}

	return($retval);
	}

