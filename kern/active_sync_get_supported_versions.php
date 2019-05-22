<?
function active_sync_get_supported_versions()
	{
	$versions = active_sync_get_table_version();

	# return value should depend on supported commands

	return(implode(",", $versions));
	}
?>
