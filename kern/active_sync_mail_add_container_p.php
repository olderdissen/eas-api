<?
function active_sync_mail_add_container_p(& $data, $body)
	{
	$data["Email"]["ContentClass"] = "urn:content-classes:message";
	$data["Email"]["MessageClass"] = "IPM.Note";

	$data["Body"][] = array
		(
		"Type" => 1,
		"EstimatedDataSize" => strlen($body),
		"Data" => $body
		);
	}
?>
