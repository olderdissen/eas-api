function calendar_draw_birthdays_of_month()
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
		time_w.setDate(time_w.getDate() - 1);

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

			var t = 0;

			for(evt_id = 0; evt_id < calendar.birthdays.length; evt_id = evt_id + 1)
				{
				var birthday = calendar.birthdays[evt_id];

				var event_s = new Date(birthday[0] * 1000);

				////////////////////////////////////////////////////////////////////////////////

				range_s.setTime(time_w.getTime());

				range_s.setHours(0);
				range_s.setMinutes(0)
				range_s.setSeconds(0)
				range_s.setMilliseconds(0)

				////////////////////////////////////////////////////////////////////////////////

				range_e.setTime(time_w.getTime());
				range_e.setDate(range_e.getDate() + 1); // + 1 day

				range_e.setHours(0);
				range_e.setMinutes(0)
				range_e.setSeconds(0)
				range_e.setMilliseconds(0)

				////////////////////////////////////////////////////////////////////////////////

				if(time_s.getMonth() != time_w.getMonth()) // do not draw events from outside of selected month
					{
					}
				else if((event_s.getTime() >= range_s.getTime()) && (event_s.getTime() < range_e.getTime())) // starts at selected day, ends at selected day
					{
					event_s = event_s.getHours() + (event_s.getMinutes() / 60);

//					o.childNodes[0].innerHTML = '<b>' + o.childNodes[0].innerHTML + '</b>';
					o.childNodes[1].innerHTML = o.childNodes[1].innerHTML + '<span onmouseover="this.style.textDecoration = \'underline\';" onmouseout="this.style.textDecoration = \'none\';" onclick="handle_link({ cmd : \'Edit\', collection_id : \'9009\', server_id : \'' + birthday[2] + '\' });" class="calendar_birthday_month" style="top: ' + t + '%;" title="' + birthday[1] + '">' + birthday[1] + '</span>';
// †
// *

					t = t + 20;
					}
				}

			time_w.setDate(time_w.getDate() + 1); // + 1 day
			}
		}
	}

