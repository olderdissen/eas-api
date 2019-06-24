<?
function active_sync_get_default_attendee()
	{
	$retval = array
		(
		"AttendeeStatus"	=> 0, # 0 Response unknown | 2 tentative | 3 accept | 4 decline | 5 not responded
		"AttendeeType"		=> 1, # 1 required | 2 optional | 3 resource
		"Email"			=> "",
		"Name"			=> ""
		);


	return($retval);
	}
?>
