<?
function active_sync_mail_is_forward($subject)
	{
	$table = array();

	$table["da"] = array("VS");		# danish
	$table["de"] = array("WG");		# german
	$table["el"] = array("ΠΡΘ");		# greek
	$table["en"] = array("FW", "FWD");	# english
	$table["es"] = array("RV");		# spanish
	$table["fi"] = array("VL");		# finnish
	$table["fr"] = array("TR");		# french
	$table["he"] = array("הועבר");		# hebrew
	$table["is"] = array("FS");		# icelandic
	$table["it"] = array("I");		# italian
	$table["nl"] = array("Doorst");		# dutch
	$table["no"] = array("VS");		# norwegian
	$table["pl"] = array("PD");		# polish
	$table["pt"] = array("ENC");		# portuguese
	$table["ro"] = array("Redirecţionat");	# romanian
	$table["sv"] = array("VB");		# swedish
	$table["tr"] = array("İLT");		# turkish
	$table["zh"] = array("转发");		# chinese

	foreach($table as $language => $abbreviations)
		{
		foreach($abbreviations as $abbreviation)
			{
			$abbreviation = $abbreviation . ":";

			if(strtolower(substr($subject, 0, strlen($abbreviation))) != strtolower($abbreviation))
				continue;

			return(1);
			}
		}

	return(0);
	}
?>
