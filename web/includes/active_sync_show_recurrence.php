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

function active_sync_show_recurrence_day_of_month($data, $lang)
	{
	print("<span id=\"Recurrence:DayOfMonth\" style=\"display: none;\">");
		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">");
					print("Tag");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				print("<td>");
					print("<select name=\"Recurrence:DayOfMonth\" class=\"xs\">");
						foreach(range(0, 31) as $i)
							{
							print("<option value=\"" . $i . "\"" . ($data["Recurrence"]["DayOfMonth"] == $i ? " selected" : "") . ">");
								print($i);
							print("</option>");
							}
					print("</select>");
				print("</td>");
			print("</tr>");
		print("</table>");
	print("</span>");
	}

function active_sync_show_recurrence_day_of_week($data, $lang)
	{
	print("<span id=\"Recurrence:DayOfWeek\" style=\"display: none;\">");
		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">");
					print("Wochentag");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				print("<td>");
					print("<select name=\"Recurrence:DayOfWeek\" class=\"xs\" onchange=\"handle_link({ cmd : 'UpdateRecurrenceDayOfWeek' });\">");
					print("</select>");

/*
					print("<table>");
						foreach(array(1 => "Sonntag", 2 => "Montag", 4 => "Dienstag", 8 => "Mittwoch", 16 => "Donnerstag", 32 => "Freitag", 64 => "Samstag", 127 => "letzter Tag des Monats") as $key => $value)
							{
							print("<tr>");
								print("<td>");
									print("<input type=\"checkbox\" name=\"Recurrence:DayOfWeek[]\" onchange=\"handle_link({ cmd : 'UpdateRecurrenceDayOfWeek' });\" value=\"" . $key . "\">");
								print("</td>");
								print("<td>");
									print($value);
								print("</td>");
							print("</tr>");
							}
					print("</table>");
*/

				print("</td>");
			print("</tr>");
		print("</table>");
	print("</span>");
	}

function active_sync_show_recurrence_interval($data, $lang)
	{
	print("<span id=\"Recurrence:Interval\" style=\"display: none;\">");
		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">");
					print("Interval");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				print("<td>");
					print("<select name=\"Recurrence:Interval\" class=\"xs\">");
						foreach(range(0, 999) as $i)
							{
							print("<option value=\"" . $i . "\"" . ($data["Recurrence"]["Interval"] == $i ? " selected" : "") . ">");
								print($i);
							print("</option>");
							}
					print("</select>");
				print("</td>");
			print("</tr>");
		print("</table>");
	print("</span>");
	}

function active_sync_show_recurrence_month_of_year($data, $lang)
	{
	print("<span id=\"Recurrence:MonthOfYear\" style=\"display: none;\">");
		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">");
					print("Monat");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				print("<td>");
					print("<select name=\"Recurrence:MonthOfYear\" class=\"xs\" onchange=\"update_recurrence_month_of_year();\">");
						foreach(array(1 => "Januar", 2 => "Februar", 3 => "März", 4 => "April", 5 => "Mai", 6 => "Juni", 7 => "Juli", 8 => "August", 9 => "September", 10 => "Oktober", 11 => "November", 12 => "Dezember") as $key => $value)
							{
							print("<option value=\"" . $key . "\"" . ($data["Recurrence"]["MonthOfYear"] == $key ? " selected" : "") . ">");
								print($value);
							print("</option>");
							}
					print("</select>");
				print("</td>");
			print("</tr>");
		print("</table>");
	print("</span>");
	}

function active_sync_show_recurrence_occurrences($data, $lang)
	{
	print("<span id=\"Recurrence:Occurrences\" style=\"display: none;\">");
		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">");
					print("Wiederholungen");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				print("<td>");
					print("<select name=\"Recurrence:Occurrences\" class=\"xs\">");
						foreach(range(1, 999) as $i)
							{
							print("<option value=\"" . $i . "\"" . ($data["Recurrence"]["Occurrences"] == $i ? " selected" : "") . ">");
								print($i);
							print("</option>");
							}
					print("</select>");
				print("</td>");
			print("</tr>");
		print("</table>");
	print("</span>");
	}

function active_sync_show_recurrence_type($data, $lang = "de")
	{
	print("<table>");
		print("<tr>");
			print("<td class=\"field_label\">");
				print("Wiederholung");
			print("</td>");
			print("<td>");
				print(":");
			print("</td>");
			print("<td>");
				print("<select name=\"Recurrence:Type\" class=\"xs\" onchange=\"update_recurrence_type();\">");
					foreach(array(4 => "Einmaliges Ereignis", 0 => "Täglich", 1 => "Wöchentlich", 2 => "Monatlich", 3 => "Monatlich", 5 => "Jährlich", 6 => "Jährlich") as $key => $value)
						{
						print("<option value=\"" . $key . "\"" . ($data["Recurrence"]["Type"] == $key ? " selected" : "") . ">");
							print($value);
						print("</option>");
						}
				print("</select>");
			print("</td>");
		print("</tr>");
	print("</table>");
	}

function active_sync_show_recurrence_until($data, $lang)
	{
	print("<span id=\"Recurrence:Until\" style=\"display: none;\">");
		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">");
					print("Ablaufdatum");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				print("<td>");
					print("<input type=\"text\" name=\"Recurrence:Until\" value=\"" . $data["Recurrence"]["Until"] . "\" class=\"xi\" onclick=\"popup_date({ target : this, cmd : 'init', time : false });\">"); # date or date + time ???
				print("</td>");
			print("</tr>");
		print("</table>");
	print("</span>");
	}

function active_sync_show_recurrence_week_of_month($data, $lang)
	{
	print("<span id=\"Recurrence:WeekOfMonth\" style=\"display: none;\">");
		print("<table>");
			print("<tr>");
				print("<td class=\"field_label\">");
					print("Woche");
				print("</td>");
				print("<td>");
					print(":");
				print("</td>");
				print("<td>");
					print("<select name=\"Recurrence:WeekOfMonth\" class=\"xs\" onchange=\"update_recurrence_week_of_month();\">");
						foreach(array(1 => "ersten", 2 => "zweiten", 3 => "dritten", 4 => "vierten", 5 => "letzten") as $key => $value)
							{
							print("<option value=\"" . $key . "\"" . ($data["Recurrence"]["Interval"] == $key ? " selected" : "") . ">");
								print($value);
							print("</option>");
							}
					print("</select>");
				print("</td>");
			print("</tr>");
		print("</table>");
	print("</span>");
	}
?>
