<?
function active_sync_get_table_handle()
	{
	$table = array
		(
		"Sync" => "active_sync_handle_sync",
		"SendMail" => "active_sync_handle_send_mail",
		"SmartForward" => "active_sync_handle_smart_forward",
		"SmartReply" => "active_sync_handle_smart_reply",
		"GetAttachment" => "active_sync_handle_get_attachment",
		"GetHierarchy" => "active_sync_handle_get_hierarchy",		# DEPRECATED
		"CreateCollection" => "active_sync_handle_create_collection",	# DEPRECATED
		"DeleteCollection" => "active_sync_handle_delete_collection",	# DEPRECATED
		"MoveCollection" => "active_sync_handle_move_collection",		# DEPRECATED
		"FolderSync" => "active_sync_handle_folder_sync",
		"FolderCreate" => "active_sync_handle_folder_create",
		"FolderDelete" => "active_sync_handle_folder_delete",
		"FolderUpdate" => "active_sync_handle_folder_update",
		"MoveItems" => "active_sync_handle_move_items",
		"GetItemEstimate" => "active_sync_handle_get_item_estimate",
		"MeetingResponse" => "active_sync_handle_meeting_response",
		"Search" => "active_sync_handle_search",
		"Settings" => "active_sync_handle_settings",
		"Ping" => "active_sync_handle_ping",
		"ItemOperations" => "active_sync_handle_item_operations",
		"Provision" => "active_sync_handle_provision",
		"ResolveRecipients" => "active_sync_handle_resolve_recipients",
		"ValidateCert" => "active_sync_handle_validate_cert"
		);

	return($table);
	}
?>
