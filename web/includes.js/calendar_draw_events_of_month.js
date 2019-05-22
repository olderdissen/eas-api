function calendar_draw_events_of_month()
	{
	var time_s	= new Date();
	var time_w	= new Date();
	var range_s	= new Date(); // range start
	var range_e	= new Date(); // range end

	////////////////////////////////////////////////////////////////////////////////

	time_s.setTime(calendar.time * 1000);
	time_w.setTime(calendar.time * 1000);

	////////////////////////////////////////////////////////////////////////////////
	// go to first day of month
	////////////////////////////////////////////////////////////////////////////////

	time_w.setDate(1);

	////////////////////////////////////////////////////////////////////////////////
	// go to first day of week
	////////////////////////////////////////////////////////////////////////////////

	while(time_w.getDay() != 1)
		{
		time_w.setDate(time_w.getDate() - 1);
		}

	////////////////////////////////////////////////////////////////////////////////
	// go to start of day
	////////////////////////////////////////////////////////////////////////////////

	time_w.setHours(0);
	time_w.setMinutes(0);
	time_w.setSeconds(0);
	time_w.setMilliseconds(0);

	////////////////////////////////////////////////////////////////////////////////

	for(week_id = 0; week_id < 6; week_id = week_id + 1)
		{
		for(day_id = 0; day_id < 7; day_id = day_id + 1)
			{
			var o = document.getElementById('calendar_events').childNodes[0].childNodes[week_id].childNodes[day_id].childNodes[0];

			for(evt_id = 0; evt_id < calendar.events.length; evt_id = evt_id + 1)
				{
				var event = calendar.events[evt_id];

				var event_s = new Date(event[0] * 1000);
				var event_e = new Date(event[1] * 1000);

				////////////////////////////////////////////////////////////////////////////////

				range_s.setTime(time_w.getTime());

				range_s.setHours(0);
				range_s.setMinutes(0);
				range_s.setSeconds(0);
				range_s.setMilliseconds(0);

				////////////////////////////////////////////////////////////////////////////////

				range_e.setTime(time_w.getTime());
				range_e.setDate(range_e.getDate() + 1); // + 1 day

				range_e.setHours(0);
				range_e.setMinutes(0);
				range_e.setSeconds(0);
				range_e.setMilliseconds(0);

				////////////////////////////////////////////////////////////////////////////////

				if(time_s.getMonth() != time_w.getMonth()) // do not draw events from outside of selected month
					{
					}
				else if((event_s.getTime() >= range_s.getTime()) && (event_e.getTime() < range_e.getTime())) // starts at selected day, ends at selected day
					{
					event_s = event_s.getHours() + (event_s.getMinutes() / 60);
					event_e = event_e.getHours() + (event_e.getMinutes() / 60);

					o.childNodes[0].innerHTML = '<b>' + o.childNodes[0].innerHTML + '</b>';
					o.childNodes[1].innerHTML = o.childNodes[1].innerHTML + '<div class="calendar_event_month" style="height: ' + (100 / 24 * (event_e - event_s)) + '%; top: ' + (100 / 24 * event_s) + '%;"></div>';
					}
				else if((event_s.getTime() < range_s.getTime()) && (event_e.getTime() >= range_e.getTime())) // starts before selected day, ends after selected day
					{
					event_s = 0;
					event_e = 24;

					o.childNodes[0].innerHTML = '<b>' + o.childNodes[0].innerHTML + '</b>';
					o.childNodes[1].innerHTML = o.childNodes[1].innerHTML + '<div class="calendar_event_month" style="height: ' + (100 / 24 * (event_e - event_s)) + '%; top: ' + (100 / 24 * event_s) + '%;"></div>';
					}
				else if((event_s.getTime() < range_s.getTime()) && (event_e.getTime() >= range_s.getTime()) && (event_e.getTime() < range_e.getTime())) // starts before selected day, ends at selected day
					{
					event_s = 0;
					event_e = event_e.getHours() + (event_e.getMinutes() / 60);

					o.childNodes[0].innerHTML = '<b>' + o.childNodes[0].innerHTML + '</b>';
					o.childNodes[1].innerHTML = o.childNodes[1].innerHTML + '<div class="calendar_event_month" style="height: ' + (100 / 24 * (event_e - event_s)) + '%; top: ' + (100 / 24 * event_s) + '%;"></div>';
					}
				else if((event_s.getTime() >= range_s.getTime()) && (event_s.getTime() < range_e.getTime()) && (event_e.getTime() >= range_e.getTime())) // starts at selected day, ends after selected day
					{
					event_s = event_s.getHours() + (event_s.getMinutes() / 60);
					event_e = 24;

					o.childNodes[0].innerHTML = '<b>' + o.childNodes[0].innerHTML + '</b>';
					o.childNodes[1].innerHTML = o.childNodes[1].innerHTML + '<div class="calendar_event_month" style="height: ' + (100 / 24 * (event_e - event_s)) + '%; top: ' + (100 / 24 * event_s) + '%;"></div>';
					}
				}

			time_w.setDate(time_w.getDate() + 1); // + 1 day
			}
		}
	}

