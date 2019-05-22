<?
function active_sync_show_recurrence($data, $lang = "de")
	{
	# values defined by MS-AS are 1, 2, 3, 5, 6. value of 4 is not defined thus it is free to use for single occurrence

	active_sync_show_recurrence_type($data, $lang);
	active_sync_show_recurrence_occurrences($data, $lang);
	active_sync_show_recurrence_interval($data, $lang);
	active_sync_show_recurrence_week_of_month($data, $lang);
	active_sync_show_recurrence_day_of_week($data, $lang);
	active_sync_show_recurrence_month_of_year($data, $lang);
	active_sync_show_recurrence_day_of_month($data, $lang);
	active_sync_show_recurrence_until($data, $lang);

	print("<input type=\"hidden\" name=\"Recurrence:CalendarType\" value=\"" . $data["Recurrence"]["CalendarType"] . "\">");
	print("<input type=\"hidden\" name=\"Recurrence:IsLeapMonth\" value=\"" . $data["Recurrence"]["IsLeapMonth"] . "\">");
	print("<input type=\"hidden\" name=\"Recurrence:FirstDayOfWeek\" value=\"1\">");

	print("<script type=\"text/javascript\">");
		print("update_recurrence_init();");
		print("update_recurrence_type();");
		print("update_recurrence_month_of_year();");
		print("update_recurrence_day_of_week(" . $data["Recurrence"]["DayOfWeek"] . ");");

		foreach(array("Occurrences", "Interval", "DayOfMonth") as $token)
			{
			if(isset($data["Recurrence"][$token]) === false)
				{
				continue;
				}

			print("document.forms[0]['Recurrence:" . $token . "'].selectedIndex = " . ($data["Recurrence"][$token] - 1) . ";");
			}
	print("</script>");
	}
?>
