<?
function active_sync_get_default_flag($Class = "Tasks")
	{
	$retval = array();

	if($Class == "Email")
		{
		$retval["CompleteTime"]		= "";
		$retval["FlagType"]		= "";
		$retval["Status"]		= "";
		}

	if($Class == "Tasks")
		{
		$retval["DateCompleted"]	= "";
		$retval["DueDate"]		= "";
		$retval["OrdinalDate"]		= "";
		$retval["ReminderSet"]		= "";
		$retval["ReminderTime"]		= "";
		$retval["StartDate"]		= "";
		$retval["Subject"]		= "";
		$retval["SubOrdinalDate"]	= "";
		$retval["UtcDueDate"]		= "";
		$retval["UtcStartDate"]		= "";
		}

	return($retval);
	}
?>
