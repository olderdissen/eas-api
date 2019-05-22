<?
function active_sync_folder_update($user, $server_id, $parent_id, $display_name) # bogus ? cannot rename system folder
	{
	if(active_sync_get_is_collection_id($user, $server_id) == 0)
		return(4);

	if(active_sync_get_is_collection_id($user, $parent_id) == 0)
		return(5);

	if(active_sync_get_is_display_name($user, $display_name) == 1)
		return(2);

	$type = active_sync_get_type_by_collection_id($user, $server_id);

	if($type == 19)
		return(3);

	if(active_sync_get_is_special_folder($type) == 1)
		return(2);

	if(active_sync_put_display_name($user, $server_id, $display_name) == 0)
		return(6);

	if(active_sync_put_parent_id($user, $server_id, $parent_id) == 0)
		return(6);

	return(1);
	}
?>
