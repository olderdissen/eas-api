<?
function active_sync_get_default_rights_management()
	{
	$retval = array();

	$retval["ContentExpiryDate"]		= date("Y-m-d\TH:i:s\Z");
	$retval["ContentOwner"]			= ""; # 320 chars
	$retval["EditAllowed"]			= 0;
	$retval["ExportAllowed"]		= 0;
	$retval["ExtractAllowed"]		= 0;
	$retval["ForwardAllowed"]		= 0;
	$retval["ModifyRecipientsAllowed"]	= 0;
	$retval["Owner"]			= 0;
	$retval["PrintAllowed"]			= 0;
	$retval["ProgrammaticAccessAllowed"]	= 0;
	$retval["ReplyAllAllowed"]		= 0;
	$retval["ReplyAllowed"]			= 0;

	$retval["TemplateDescription"]		= "template description"; # 10240 chars
	$retval["TemplateID"]			= "00000000-0000-0000-0000-000000000000";
	$retval["TemplateName"]			= "template name"; # 256 chars

	return($retval);
	}
?>
