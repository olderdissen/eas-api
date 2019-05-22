<?
function active_sync_get_default_info()
	{
	$retval = array();

	$retval["Model"]		= "";
	$retval["Imei"]			= "";
	$retval["FriendlyName"]		= "";
	$retval["OS"]			= "";
	$retval["OSLanguage"]		= "";
	$retval["PhoneNumber"]		= "";
	$retval["UserAgent"]		= "";
	$retval["EnableOutboundSMS"]	= 1;
	$retval["MobileOperator"]	= "";

	return($retval);
	}
?>
