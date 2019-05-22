<?
function active_sync_body_type_exist($data, $type)
	{
	if(isset($data["Body"]) === false)
		return(0);

	foreach($data["Body"] as $body)
		{
		if(isset($body["Type"]) === false)
			continue;

		if($body["Type"] != $type)
			continue;

		return(1);
		}

	return(0);
	}
?>
