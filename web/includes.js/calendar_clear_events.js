function calendar_clear_events()
	{
	if((calendar.style == 'd') || (calendar.style == 'w'))
		{
		////////////////////////////////////////////////////////////////////////////////
		// remove events
		////////////////////////////////////////////////////////////////////////////////

		var o = document.getElementById('calendar_events');

		o.innerHTML = "";

		////////////////////////////////////////////////////////////////////////////////
		// remove all day events
		////////////////////////////////////////////////////////////////////////////////

		var o = document.getElementById('calendar_all_day_events');

		o.innerHTML = "";
		}

	if(calendar.style == 'm')
		{
		var o = document.getElementById('calendar_events');

		for(week_id = 0; week_id < 6; week_id = week_id + 1)
			{
			var w = o.childNodes[0].childNodes[week_id];

			for(day_id = 0; day_id < 7; day_id = day_id + 1)
				{
				var d = w.childNodes[day_id];

				d.childNodes[0].childNodes[0].innerHTML = (d.childNodes[0].childNodes[0].innerHTML.substr(0, 3) == '<b>' ? d.childNodes[0].childNodes[0].childNodes[0].innerHTML : d.childNodes[0].childNodes[0].innerHTML);

				d.childNodes[0].childNodes[1].innerHTML = "";
				}
			}
		}

	calendar.events = "";
	calendar.birthdays = "";
	}

