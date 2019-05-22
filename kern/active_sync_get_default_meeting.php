<?
function active_sync_get_default_meeting()
	{
	$retval = array();

	$retval["AllDayEvent"]			= 0;
	$retval["StartTime"]			= date("Y-m-d\TH:i:s\Z");
	$retval["DtStamp"]			= date("Y-m-d\TH:i:s\Z");
	$retval["EndTime"]			= date("Y-m-d\TH:i:s\Z");
	$retval["InstanceType"]			= 0;
	$retval["Location"]			= "";
	$retval["Organizer"]			= "";
	$retval["RecurrenceId"]			= "";
	$retval["Reminder"]			= "";
	$retval["ResponseRequested"]		= 1;
	$retval["Sensitivity"]			= 0;
	$retval["BusyStatus"]			= 2;
	$retval["TimeZone"]			= "";
	$retval["GlobalObjId"]			= "";
	$retval["DisallowNewTimeProposal"]	= 0;
#	$retval["MeetingMessageType"]		= 0;
#	$retval["MeetingStatus"]		= 0;
#	$retval["Recurrences"]			= ""; # is group of Recurrence*

#	$retval["Calendar"]["UID"]		= "00000000-0000-0000-0000-000000000000";

	return($retval);
	}
?>
