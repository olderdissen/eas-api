<?
function active_sync_get_default_tasks($Class = "Tasks")
	{
	$retval = array();

	if($Class == "Email")
		{
		$retval["UtcStartDate"]		= date("Y-m-d\TH:i:s\Z");
		$retval["StartDate"]		= date("Y-m-d\TH:i:s\Z");
		$retval["UtcDueDate"]		= date("Y-m-d\TH:i:s\Z");
		$retval["DueDate"]		= date("Y-m-d\TH:i:s\Z");
		$retval["DateCompleted"]	= date("Y-m-d\TH:i:s\Z");
		$retval["ReminderTime"]		= date("Y-m-d\TH:i:s\Z");
		$retval["ReminderSet"]		= 0;
		$retval["OrdinalDate"]		= date("Y-m-d\TH:i:s\Z");
		$retval["SubOrdinalDate"]	= date("Y-m-d\TH:i:s\Z");
		}

	if($Class == "Tasks")
		{
		$retval["Subject"]		= "";
		# Body
		# BodySize
		# BodyTruncated
		$retval["Importance"]		= 0;
		$retval["UtcStartDate"]		= date("Y-m-d\TH:i:s\Z");
		$retval["StartDate"]		= date("Y-m-d\TH:i:s\Z");
		$retval["UtcDueDate"]		= date("Y-m-d\TH:i:s\Z");
		$retval["DueDate"]		= date("Y-m-d\TH:i:s\Z");
		# Categories
		# Recurrences
		$retval["Complete"]		= 0;
		$retval["DateCompleted"]	= date("Y-m-d\TH:i:s\Z");
		$retval["Sensitivity"]		= 0;
		$retval["ReminderTime"]		= date("Y-m-d\TH:i:s\Z");
		$retval["ReminderSet"]		= 0;
		# OrdinalDate
		# SubOrdinalDate
		}

	return($retval);
	}
?>
