<?
function active_sync_mail_convert_html_to_plain($data)
	{
	$data = str_replace(array("<br>"), array("\n"), $data);

	$data = preg_replace("/<[^>]*>/", "", $data);

	$data = str_replace(array("&lt;", "&gt;", "&nbsp;"), array("<", ">", " "), $data);

	return($data);
	}
?>
