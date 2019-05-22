<?
function active_sync_get_version($type = 0)
	{
	$name		= "AndSync";
	$major		= 0;
	$minor		= 8;
	$revision	= 8;
	$build		= 17975;
	$extension	= "";
	$description	= "";

	if($type == 0)
		return(sprintf("%s %d.%d.%d-%d %s %s", $name, $major, $minor, $revision, $build, $extension, $description));

	if($type == 1)
		return(array("name" => $name, "major" => $major, "minor" => $minor, "revision" => $revision, "build" => $build, "extension" => $extension, "description" => $description));
	}
?>
