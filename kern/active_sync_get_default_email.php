<?
function active_sync_get_default_email()
	{
	$retval = array();

	$retval["To"]			= ""; # 2.5, 12.0, 12.1, 14.0, 14.1
	$retval["Cc"]			= ""; # 2.5, 12.0, 12.1, 14.0, 14.1
	$retval["From"]			= ""; # 2.5, 12.0, 12.1, 14.0, 14.1
	$retval["Subject"]		= ""; # 2.5, 12.0, 12.1, 14.0, 14.1
	$retval["ReplyTo"]		= ""; # 2.5, 12.0, 12.1, 14.0, 14.1
	$retval["DateReceived"]		= ""; # 2.5, 12.0, 12.1, 14.0, 14.1
	$retval["DisplayTo"]		= ""; # 2.5, 12.0, 12.1, 14.0, 14.1
	$retval["ThreadTopic"]		= ""; # 2.5, 12.0, 12.1, 14.0, 14.1
	$retval["Importance"]		= ""; # 2.5, 12.0, 12.1, 14.0, 14.1
	$retval["Read"]			= ""; # 2.5, 12.0, 12.1, 14.0, 14.1
	$retval["MessageClass"]		= ""; # 2.5, 12.0, 12.1, 14.0, 14.1
	# MeetingRequest
	$retval["InternetCPID"]		= ""; # 2.5, 12.0, 12.1, 14.0, 14.1
	# Flag
	$retval["ContentClass"]		= ""; # 2.5, 12.0, 12.1, 14.0, 14.1
	# Categories
	# Attachments
	# Body
	# BodySize
	# BodyTruncated
	$retval["MIMEData"]		= ""; # 2.5
	$retval["MIMESize"]		= ""; # 2.5
	$retval["MIMETruncated"]	= ""; # 2.5

	return($retval);
	}
?>
