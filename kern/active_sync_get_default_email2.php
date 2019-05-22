<?
function active_sync_get_default_email2()
	{
	$retval = array();

	# UmCallerID
	# UmUserNotes
	# UmAttDuration
	# UmAttOrder
	$retval["ConversationId"]		= ""; # 14.0, 14.1
	$retval["ConversationIndex"]		= ""; # 14.0, 14.1
	$retval["LastVerbExecuted"]		= ""; # 14.0, 14.1
	$retval["LastVerbExecutionTime"]	= ""; # 14.0, 14.1
	$retval["ReceivedAsBcc"]		= 0; # 14.0, 14.1
	$retval["Sender"]			= ""; # 14.0, 14.1
	# CalendarType
	# IsLeapMonth
	$retval["AccountId"]			= ""; # 14.1
	# MeetingMessageType
	# Bcc
	# IsDraft
	# Send

	return($retval);
	}
?>
