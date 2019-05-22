<?
function active_sync_get_is_phone_available($data)
	{
	foreach(array("AssistnamePhoneNumber", "CarPhoneNumber", "MobilePhoneNumber", "PagerNumber", "RadioPhoneNumber", "BusinessFaxNumber", "BusinessPhoneNumber", "Business2PhoneNumber", "HomeFaxNumber", "HomePhoneNumber", "Home2PhoneNumber") as $token)
		{
		if(isset($data["Contacts"][$token]) === false)
			continue;

		if(strlen($data["Contacts"][$token]) == 0)
			continue;

		return(1);
		}

	return(0);
	}
?>
