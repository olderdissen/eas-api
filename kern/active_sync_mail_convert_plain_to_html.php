<?
function active_sync_mail_convert_plain_to_html($data)
	{
	$data = str_replace(array("<", ">", " "), array("&lt;", "&gt;", "&nbsp;"), $data);

	$data = str_replace(array("\r", "\n"), array("", "<br>"), $data);

	$data = "<p>" . $data . "</p>";

	return($data);
	}
?>
