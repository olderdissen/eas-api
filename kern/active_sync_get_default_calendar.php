<?
function active_sync_get_default_calendar()
	{
	$retval = array();

	$retval["TimeZone"]			= "xP///wAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAoAAAAFAAMAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAMAAAAFAAIAAAAAAAAAxP///w==";
	$retval["AllDayEvent"]			= 0; # 0 is not an all day event | 1 is an all day event
	# Body
	$retval["BodyTruncated"]		= 0;
	$retval["BusyStatus"]			= 2; # 0 free | 1 tentative | 2 busy | out of office
	$retval["OrganizerName"]		= "";
	$retval["OrganizerEmail"]		= "";
	$retval["DtStamp"]			= date("Y-m-d\TH:i:s\Z");
	$retval["EndTime"]			= date("Y-m-d\TH:i:s\Z");
	$retval["Location"]			= "";
	$retval["Reminder"]			= 0;
	$retval["Sensitivity"]			= 0; # 0 normal | 1 personal | 2 private | 3 confidential
	$retval["Subject"]			= "";
	$retval["StartTime"]			= date("Y-m-d\TH:i:s\Z");
	$retval["UID"]				= active_sync_create_guid();
	$retval["MeetingStatus"]		= 0; # 0 is not a meeting | 1 is a meeting | 3 meeting received | 5 meeting is canceled | 7 meeting is canceled and received | 9 => 1 | 11 => 3 | 13 => 5 | 15 => 7 ... as bitfield: 0x01 meeting, 0x02 received, 0x04 canceled
	# Attendees
	# Categories
	# Recurrences
	# Exceptions
	$retval["ResponseRequested"]		= 0;
	$retval["AppointmentReplyTime"]		= "";
	$retval["ResponseType"]			= 0; # 0 none | 1 organizer | 2 tentative | 3 accepted | 4 declined | 5 not responded
	$retval["DisallowNewTimeProposal"]	= 0;
	$retval["OnlineMeetingConfLink"]	= "";
	$retval["OnlineMeetingExternalLink"]	= "";

	return($retval);
	}
?>
