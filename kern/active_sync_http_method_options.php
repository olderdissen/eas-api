<?
function active_sync_http_method_options($request)
	{
	header("MS-Server-ActiveSync: " . active_sync_get_version());

	header("MS-ASProtocolVersions: " . active_sync_get_supported_versions());

	# header("X-MS-RP: " . active_sync_get_supported_versions());

	header("MS-ASProtocolCommands: " . active_sync_get_supported_commands());

	header("Allow: OPTIONS,POST"); # implode(",", active_sync_get_table_method());
	header("Public: OPTIONS,POST"); # implode(",", active_sync_get_table_method());
	}
?>
