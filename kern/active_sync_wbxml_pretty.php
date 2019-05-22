<?
function active_sync_wbxml_pretty($expression)
	{
	if(strlen($expression) == 0)
		return("");

	$expression = simplexml_load_string($expression, "SimpleXMLElement", LIBXML_NOBLANKS | LIBXML_NOWARNING);

	if(isset($expression->Response->Fetch->Properties->Data) === true)
		$expression->Response->Fetch->Properties->Data = "[PRIVATE DATA]";

	if(isset($expression->Response->Store->Result->Properties->Picture->Data) === true)
		$expression->Response->Store->Result->Properties->Picture->Data = "[PRIVATE DATA]";

	if(isset($expression->Collections->Collection) === true)
		foreach($expression->Collections->Collection as $collection)
			foreach(array("Add", "Change") as $action)
				if(isset($collection->Commands->$action) === true)
					foreach($collection->Commands->$action as $whatever)
						$whatever->ApplicationData = "[PRIVATE DATA]";

	if(isset($expression->Policies->Policy->Data->EASProvisionDoc) === true)
		$expression->Policies->Policy->Data->EASProvisionDoc = "[PRIVATE DATA]";

	if(isset($expression->RightsManagementInformation->Get->RightsManagementTemplates) === true)
		$expression->RightsManagementInformation->Get->RightsManagementTemplates = "[PRIVATE DATA]";

	$expression = dom_import_simplexml($expression);
	$expression = $expression->ownerDocument;
	$expression->formatOutput = true;

	$expression = $expression->saveXML();

#	list($a, $b) = explode(">\n<", $expression, 2);
#	$expression = implode(">\n<!DOCTYPE AirSync PUBLIC \"-//AIRSYNC//DTD AirSync//EN\" \"http://www.microsoft.com/\">\n<", array($a, $b));

	return($expression);
	}
?>
