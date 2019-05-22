<?
function active_sync_get_default_attachment()
	{
	$retval = array();

	$retval["AttMethod"]		= "";	# 2.5
	$retval["AttName"]		= "";	# 2.5
	$retval["AttOid"]		= "";	# 2.5
	$retval["AttSize"]		= "";	# 2.5

	$retval["ContentId"]		= "";	# 12.0, 12.1, 14.0, 14.1
	$retval["ContentLocation"]	= "";	# 12.0, 12.1, 14.0, 14.1
	$retval["DisplayName"]		= "";	# 12.0, 12.1, 14.0, 14.1
	$retval["EstimatedDataSize"]	= 0;	# 12.0, 12.1, 14.0, 14.1
	$retval["FileReference"]	= "";	# 12.0, 12.1, 14.0, 14.1
	$retval["IsInline"]		= 0;	# 12.0, 12.1, 14.0, 14.1
	$retval["Method"]		= 1;	# 12.0, 12.1, 14.0, 14.1

	$retval["UmAttDuration"]	= 0;	# 14.0, 14.1
	$retval["UmAttOrder"]		= 0;	# 14.0, 14.1
	$retval["UmCallerID"]		= 0;	# 14.0, 14.1
	$retval["UmUserNotes"]		= 0;	# 14.0, 14.1

	return($retval);
	}
?>
