<?
function active_sync_get_default_contacts($Class = "Contact")
	{
	$retval = array();

	if($Class == "Contact")
		{
		$retval["Anniversary"]			= "";
		$retval["AssistantName"]		= "";
		$retval["AssistnamePhoneNumber"]	= "";
		$retval["Birthday"]			= "";
		# Body
		# BodySize
		# BodyTruncated
		$retval["Business2PhoneNumber"]		= "";
		$retval["BusinessAddressCity"]		= "";
		$retval["BusinessPhoneNumber"]		= "";
		$retval["WebPage"]			= "";
		$retval["BusinessAddressCountry"]	= "";
		$retval["Department"]			= "";
		$retval["Email1Address"]		= "";
		$retval["Email2Address"]		= "";
		$retval["Email3Address"]		= "";
		$retval["BusinessFaxNumber"]		= "";
		$retval["FileAs"]			= "";
		$retval["Alias"]			= "";
		$retval["WeightedRank"]			= "";
		$retval["FirstName"]			= "";
		$retval["MiddleName"]			= "";
		$retval["HomeAddressCity"]		= "";
		$retval["HomeAddressCountry"]		= "";
		$retval["HomeFaxNumber"]		= "";
		$retval["HomePhoneNumber"]		= "";
		$retval["Home2PhoneNumber"]		= "";
		$retval["HomeAddressPostalCode"]	= "";
		$retval["HomeAddressState"]		= "";
		$retval["HomeAddressStreet"]		= "";
		$retval["MobilePhoneNumber"]		= "";
		$retval["Suffix"]			= "";
		$retval["CompanyName"]			= "";
		$retval["OtherAddressCity"]		= "";
		$retval["OtherAddressCountry"]		= "";
		$retval["CarPhoneNumber"]		= "";
		$retval["OtherAddressPostalCode"]	= "";
		$retval["OtherAddressState"]		= "";
		$retval["OtherAddressStreet"]		= "";
		$retval["PagerNumber"]			= "";
		$retval["Title"]			= "";
		$retval["BusinessAddressPostalCode"]	= "";
		$retval["LastName"]			= "";
		$retval["Spouse"]			= "";
		$retval["BusinessAddressState"]		= "";
		$retval["BusinessAddressStreet"]	= "";
		$retval["JobTitle"]			= "";
		$retval["YomiFirstName"]		= "";
		$retval["YomiLastName"]			= "";
		$retval["YomiCompanyName"]		= "";
		$retval["OfficeLocation"]		= "";
		$retval["RadioPhoneNumber"]		= "";
		$retval["Picture"]			= "";
		# Categories
		# Children
		}

	if($Class == "RIC")
		{
		$retval["Alias"]			= "";
		$retval["FileAs"]			= "";
		$retval["WeightedRank"]			= "";
		$retval["Email1Address"]		= "";
		}

	return($retval);
	}
?>
