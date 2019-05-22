<?
function active_sync_get_class_by_collection_id($user, $collection_id)
	{
	$type = active_sync_get_type_by_collection_id($user, $collection_id);

	$class = active_sync_get_class_by_type($type);

	return($class);
	}
?>
