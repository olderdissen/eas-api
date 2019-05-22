<?
function active_sync_get_is_filter($class, $filter)
	{
	$filters = active_sync_get_default_filter();

	return(array_key_exists($class, $filters) === false ? 0 : (in_array($filter, $filters[$class]) === false ? 0 : 1));
	}
?>
