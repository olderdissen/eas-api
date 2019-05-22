<?
function active_sync_compare_name($data, $expression)
	{
	# "von der Linden" matches search of "v, d, l"
	# "von Becker" matches search of "v, b"
	# "_briefksten@arcor.de" matches search of "b"

	foreach(array("FirstName", "LastName", "MiddleName", "Email1Address", "Email2Address", "Email3Address", "JobTitle", "CompanyName") as $token)
		{
		if(isset($data["Contacts"][$token]) === false)
			continue;

		if(strlen($data["Contacts"][$token]) == 0)
			continue;

		$x = $expression;
		$y = $data["Contacts"][$token];

		$x = strtolower($x);
		$y = strtolower($y);

		if(substr($y, 0, strlen($x)) != $x)
			continue;

		return(1);
		}

	return(0);
	}
?>
