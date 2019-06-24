<?
function active_sync_get_default_attachment()
	{
	$retval = array
		(
		"AttMethod"		=> "",	# 2.5
		"AttName"		=> "",	# 2.5
		"AttOid"		=> "",	# 2.5
		"AttSize"		=> "",	# 2.5

		"ContentId"		=> "",	# 12.0, 12.1, 14.0, 14.1
		"ContentLocation"	=> "",	# 12.0, 12.1, 14.0, 14.1
		"DisplayName"		=> "",	# 12.0, 12.1, 14.0, 14.1
		"EstimatedDataSize"	=> 0,	# 12.0, 12.1, 14.0, 14.1
		"FileReference"		=> "",	# 12.0, 12.1, 14.0, 14.1
		"IsInline"		=> 0,	# 12.0, 12.1, 14.0, 14.1
		"Method"		=> 1,	# 12.0, 12.1, 14.0, 14.1

		"UmAttDuration"		=> 0,	# 14.0, 14.1
		"UmAttOrder"		=> 0,	# 14.0, 14.1
		"UmCallerID"		=> 0,	# 14.0, 14.1
		"UmUserNotes"		=> 0	# 14.0, 14.1
		);

	return($retval);
	}
?>
