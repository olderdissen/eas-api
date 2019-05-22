<?
function active_sync_get_body_by_type($data, $type)
	{
	if(isset($data["Body"]) === false)
		return(false);
		
	foreach($data["Body"] as $body)
		{
		if(isset($body["Type"]) === false)
			continue;

		if($body["Type"] != $type)
			continue;

		if(isset($body["Data"]) === false)
			continue;

		return($body);
		}

	return(false);
	}

