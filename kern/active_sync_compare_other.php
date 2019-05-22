<?
function active_sync_compare_other($data, $expression)
	{
	foreach(array("NickName", "CustomerId") as $token)
		{
		if(isset($data["Contacts2"][$token]) === false)
			continue;

		if(strlen($data["Contacts2"][$token]) == 0)
			continue;

		$x = $expression;
		$y = $data["Contacts2"][$token];

		$x = strtolower($x);
		$y = strtolower($y);

		if(substr($y, 0, strlen($x)) != $x)
			continue;

		return(1);
		}

	return(0);
	}
?>
