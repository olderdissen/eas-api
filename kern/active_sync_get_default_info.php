<?
function active_sync_get_default_info()
	{
	$retval = array
		(
		"Model"			=> "",
		"Imei"			=> "",
		"FriendlyName"		=> "",
		"OS"			=> "",
		"OSLanguage"		=> "",
		"PhoneNumber"		=> "",
		"UserAgent"		=> "",
		"EnableOutboundSMS"	=> 1,
		"MobileOperator"	=> ""
		);

	return($retval);
	}
?>
