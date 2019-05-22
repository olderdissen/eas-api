<?
function active_sync_compare_address($data, $expression)
	{
	foreach(array("BusinessAddress", "HomeAddress", "OtherAddress") as $token)
		{
		foreach(array("Country", "State", "City", "PostalCode", "Street") as $key)
			{
			if(isset($data["Contacts"][$token . $key]) === false)
				continue;

			if(strlen($data["Contacts"][$token . $key]) == 0)
				continue;

			$x = $expression;
			$y = $data["Contacts"][$token . $key];

			$x = strtolower($x);
			$y = strtolower($y);

			if(substr($y, 0, strlen($x)) != $x)
				continue;

			return(1);
			}
		}

	return(0);
	}
?>
