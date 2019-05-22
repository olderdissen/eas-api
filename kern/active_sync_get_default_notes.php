<?
function active_sync_get_default_notes()
	{
	$retval = array
		(
		"Subject" => "",
		"MessageClass" => "IPM.StickyNote",
		"LastModifiedDate" => date("Y-m-d\TH:i:s\Z")
		# Categories
		);

	return($retval);
	}
?>
