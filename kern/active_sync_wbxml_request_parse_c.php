<?
function active_sync_wbxml_request_parse_c($data)
	{
	$data = active_sync_wbxml_request_c($data);

	$data = simplexml_load_string($data, "SimpleXMLElement", LIBXML_NOWARNING | LIBXML_NOBLANKS);

#	$data = new SimpleXMLElement($data);

	return($data);
	}
?>
