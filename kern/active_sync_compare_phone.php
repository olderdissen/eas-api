<?
function active_sync_compare_phone($data, $expression)
	{
	foreach(array("AssistnamePhoneNumber", "CarPhoneNumber", "MobilePhoneNumber", "PagerNumber", "RadioPhoneNumber", "BusinessFaxNumber", "BusinessPhoneNumber", "Business2PhoneNumber", "HomeFaxNumber", "HomePhoneNumber", "Home2PhoneNumber") as $token)
		{
		if(isset($data["Contacts"][$token]) === false)
			continue;

		if(strlen($data["Contacts"][$token]) == 0)
			continue;

		$x = $expression;
		$y = $data["Contacts"][$token];

		$x = active_sync_fix_phone($x);
		$y = active_sync_fix_phone($y);

		$x = strtolower($x);
		$y = strtolower($y);

		if(substr($y, 0, strlen($x)) != $x)
			continue;

		return(1);
		}

	return(0);
	}
?>
