<?
function active_sync_namespace_to_string($namespace)
	{
	$namespace = str_replace(array("-", "{", "}"), "", $namespace);

	$namespace = str_split($namespace, 2);

#	for($position = 0; $position < count($namespace); $position = $position + 1)
#		$namespace[$position] = chr(hexdec($namespace[$position]));

	foreach($namespace as $position => $char)
		$namespace[$position] = chr(hexdec($char));

	$namespace = implode("", $namespace);

	return($namespace);
	}
?>
