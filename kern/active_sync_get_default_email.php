<?
function active_sync_get_default_email()
	{
	$retval = array
		(
		"To"			=> "", # 2.5, 12.0, 12.1, 14.0, 14.1
		"Cc"			=> "", # 2.5, 12.0, 12.1, 14.0, 14.1
		"From"			=> "", # 2.5, 12.0, 12.1, 14.0, 14.1
		"Subject"		=> "", # 2.5, 12.0, 12.1, 14.0, 14.1
		"ReplyTo"		=> "", # 2.5, 12.0, 12.1, 14.0, 14.1
		"DateReceived"		=> "", # 2.5, 12.0, 12.1, 14.0, 14.1
		"DisplayTo"		=> "", # 2.5, 12.0, 12.1, 14.0, 14.1
		"ThreadTopic"		=> "", # 2.5, 12.0, 12.1, 14.0, 14.1
		"Importance"		=> "", # 2.5, 12.0, 12.1, 14.0, 14.1
		"Read"			=> "", # 2.5, 12.0, 12.1, 14.0, 14.1
		"MessageClass"		=> "", # 2.5, 12.0, 12.1, 14.0, 14.1
		# MeetingRequest
		"InternetCPID"		=> "", # 2.5, 12.0, 12.1, 14.0, 14.1
		# Flag
		"ContentClass"		=> "", # 2.5, 12.0, 12.1, 14.0, 14.1
		# Categories
		# Attachments
		# Body
		# BodySize
		# BodyTruncated
		"MIMEData"		=> "", # 2.5
		"MIMESize"		=> "", # 2.5
		"MIMETruncated"		=> "" # 2.5
		);

	return($retval);
	}
?>
