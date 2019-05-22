<?
function active_sync_get_default_services()
	{
	$retval = array
		(
		array
			(
			"Class" => "Contacts",
			"Name" => "Kontakte",
			"Enabled" => "T"
			),
		array
			(
			"Class" => "Calendar",
			"Name" => "Kalender",
			"Enabled" => "T"
			),
		array
			(
			"Class" => "Tasks",
			"Name" => "Aufgaben",
			"Enabled" => "T"),
		array
			(
			"Class" => "Notes",
			"Name" => "Notizen",
			"Enabled" => "T"
			),
		array
			(
			"Class" => "Email",
			"Name" => "EMail",
			"Enabled" => "T"
			)
		);

	return($retval);
	}
?>
