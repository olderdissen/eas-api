<?
function active_sync_get_default_login()
	{
	$retval = array();

	$retval["User"]		= "";
	$retval["Pass"]		= "";
	$retval["IsAdmin"]	= "F";

	$retval["DisplayName"]	= "";
	$retval["FirstName"]	= "";
	$retval["LastName"]	= "";

	return($retval);
	}
?>
