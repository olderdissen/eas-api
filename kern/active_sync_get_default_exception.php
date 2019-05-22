<?
function active_sync_get_default_exception()
	{
	$retval = array();

	$retval["Deleted"]		= "";
	$retval["ExceptionStartTime"]	= "";
	$retval["EndTime"]		= "";
	$retval["Location"]		= "";
	$retval["Sensitivity"]		= "";
	$retval["BusyStatus"]		= "";
	$retval["AllDayEvent"]		= "";
	$retval["Reminder"]		= "";
	$retval["DTStamp"]		= "";
	$retval["MeetingStatus"]	= "";
	$retval["AppointmentReplyTime"]	= "";
	$retval["ResponseType"]		= "";

	return($retval);
	}
?>
