<?
function active_sync_wbxml_request_parse_b($data)
	{
	$data = active_sync_wbxml_request_b($data);

	$data = simplexml_load_string($data, "SimpleXMLElement", LIBXML_NOWARNING | LIBXML_NOBLANKS);

#	$data = new SimpleXMLElement($data);

	return($data);
	}
?>
