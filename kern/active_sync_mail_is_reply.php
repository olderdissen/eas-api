<?
function active_sync_mail_is_reply($subject)
	{
	$table = array();

	$table["da"] = array("SV");		# danish
	$table["de"] = array("AW");		# german
	$table["el"] = array("ΑΠ", "ΣΧΕΤ");	# greek
	$table["en"] = array("RE");		# english
	$table["es"] = array("RE");		# spanish
	$table["fi"] = array("VS");		# finnish
	$table["fr"] = array("RE");		# french
	$table["he"] = array("תגובה");		# hebrew
	$table["is"] = array("SV");		# icelandic
	$table["it"] = array("R", "RIF");	# italian
	$table["nl"] = array("Antw");		# dutch
	$table["no"] = array("SV");		# norwegian
	$table["pl"] = array("Odp");		# polish
	$table["pt"] = array("RES");		# portuguese
	$table["ro"] = array("RE");		# romanian
	$table["sv"] = array("SV");		# swedish
	$table["tr"] = array("YNT");		# turkish
	$table["zh"] = array("回复");		# chinese

	foreach($table as $language => $abbreviations)
		{
		foreach($abbreviations as $abbreviation)
			{
			$abbreviation = $abbreviation . ":";

			if(strtolower(substr($subject, 0, strlen($abbreviation))) == strtolower($abbreviation))
				continue;

			return(1);
			}
		}

	return(0);
	}
?>
