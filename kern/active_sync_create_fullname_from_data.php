<?
function active_sync_create_fullname_from_data($data, $style = 2)
	{
	$style = min($style, 2);
	$style = max($style, 0);

	$styles = array();

	$styles[0] = array("FirstName" => "", "MiddleName" => " ", "LastName" => " ", "Suffix" => " ");
	$styles[1] = array("LastName" => "", "FirstName" => ", ", "MiddleName" => " ", "Suffix" => ", ");
	$styles[2] = array("FirstName" => "", "MiddleName" => " ", "LastName" => " ");

	$retval = array();

	foreach($styles[$style] as $token => $prefix)
		{
		if(isset($data["Contacts"][$token]) === false)
			continue;

		if(strlen($data["Contacts"][$token]) == 0)
			continue;

		if(count($retval) > 0)
			$retval[] = $prefix;

		$retval[] = $data["Contacts"][$token];
		}

	$helper = array();

	foreach(array("YomiLastName" => "", "YomiFirstName" => " ") as $token => $prefix)
		{
		if(isset($data["Contacts"][$token]) === false)
			continue;

		if(strlen($data["Contacts"][$token]) == 0)
			continue;

		if(count($retval) > 0)
			$helper[] = $prefix;

		$helper[] = $data["Contacts"][$token];
		}

	################################################################################
	# add yomi for non email and if we already have some name data
	################################################################################

	if($style != 2)
		if(count($retval) > 0)
			if(count($helper) > 0)
				$retval[] = " <small>" . implode("", $helper) . "</small>";

	################################################################################
	# replace empty full name
	################################################################################

	foreach(array("Contacts2:NickName", "Contacts:CompanyName", "Contacts:JobTitle") as $items)
		{
		if(count($retval) > 0)
			break;

		list($codepage, $token) = explode(":", $items, 2);

		if(isset($data[$codepage][$token]) === false)
			continue;

		if(strlen($data[$codepage][$token]) == 0)
			continue;

		$retval[] = $data[$codepage][$token];
		}

	if(count($retval) == 0)
		$retval[] = "(Unbekannt)";

	return(implode("", $retval));
	}
?>
