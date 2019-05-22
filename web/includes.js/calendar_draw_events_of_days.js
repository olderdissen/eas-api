function calendar_draw_events_of_days()
	{
	var time_w	= new Date();
	var range_s	= new Date(); // range start
	var range_e	= new Date(); // range end

	var data_h	= new Array();

	if(calendar.style == 'd')
		{
		time_w.setTime(calendar.time * 1000);

		x = 1;
		}

	if(calendar.style == 'w')
		{
		time_w.setTime(calendar.time * 1000);

		////////////////////////////////////////////////////////////////////////////////
		// go to first day of week
		////////////////////////////////////////////////////////////////////////////////

		while(time_w.getDay() != 1)
			{
			time_w.setDate(time_w.getDate() - 1);
			}

		x = 7;
		}

	for(day_id = 0; day_id < x; day_id = day_id + 1)
		{
		// this "pre-check" is something necccesary
		// event A starts on day 15 and ends on day 16
		// event B starts on day 16 and ends on day 16
		// both events overlap on day 16
		// event A uses full width on day 15
		// event A use half width on day 16 ... beside event B
		// if we do not use this "pre-check" event A will also use half width on day 15 which is wrong

		var data_list = new Array();

		for(row_id = 0; row_id < calendar.events.length; row_id = row_id + 1)
			{
			var event = calendar.events[row_id];

			var event_s = new Date(event[0] * 1000);
			var event_e = new Date(event[1] * 1000);

			////////////////////////////////////////////////////////////////////////////////

			range_s.setTime(time_w.getTime());

			range_s.setHours(0);
			range_s.setMinutes(0)
			range_s.setSeconds(0)
			range_s.setMilliseconds(0)

			////////////////////////////////////////////////////////////////////////////////

			range_e.setTime(time_w.getTime());
			range_e.setDate(range_e.getDate() + 1); // + 1 day

			range_e.setHours(0)
			range_e.setMinutes(0)
			range_e.setSeconds(0)
			range_e.setMilliseconds(0)

			////////////////////////////////////////////////////////////////////////////////

// 19.12 12:51 - 20.12 13:51
// 26.12 12:51 - 26.12 13:51
// 29.12 00:00 - 30.12 00:00

			if(event[2] == 0)
				{
				if((event_s.getTime() >= range_s.getTime()) && (event_e.getTime() < range_e.getTime())) // starts at selected day, ends at selected day
					{
					data_list.push(event);
					}
				else if((event_s.getTime() < range_s.getTime()) && (event_e.getTime() >= range_e.getTime())) // starts before selected day, ends after selected day
					{
					data_list.push(event);
					}
				else if((event_s.getTime() < range_s.getTime()) && (event_e.getTime() >= range_s.getTime()) && (event_e.getTime() < range_e.getTime())) // starts before selected day, ends at selected day
					{
					if((event_e.getHours() + (event_e.getMinutes() / 60)) != 0)
						{
						data_list.push(event); // ends at 00:00 this day
						}
					}
				else if((event_s.getTime() >= range_s.getTime()) && (event_s.getTime() < range_e.getTime()) && (event_e.getTime() >= range_e.getTime())) // starts at selected day, ends after selected day
					{
					data_list.push(event);
					}
				}
			}

		if(data_list.length > 0)
			{
			data_list = calendar_events_prepare(data_list);

			for(row_id = 0; row_id < data_list.length; row_id = row_id + 1)
				{
				for(col_id = 0; col_id < data_list[row_id].length; col_id = col_id + 1)
					{
					for(evt_id = 0; evt_id < data_list[row_id][col_id].length; evt_id = evt_id + 1)
						{
						var event = data_list[row_id][col_id][evt_id];

						var event_s = new Date(event[0] * 1000);
						var event_e = new Date(event[1] * 1000);

						range_s.setTime(time_w.getTime());
						range_e.setTime(time_w.getTime());
						range_e.setDate(range_e.getDate() + 1); // + 1 day

						range_s.setHours(0);
						range_s.setMinutes(0)
						range_s.setSeconds(0)
						range_s.setMilliseconds(0)

						range_e.setHours(0);
						range_e.setMinutes(0)
						range_e.setSeconds(0)
						range_e.setMilliseconds(0)

						if((event_s.getTime() >= range_s.getTime()) && (event_e.getTime() < range_e.getTime())) // starts at selected day, ends at selected day
							{
							event_s = event_s.getHours() + (event_s.getMinutes() / 60);
							event_e = event_e.getHours() + (event_e.getMinutes() / 60);

							event_e = (event_e == 0 ? 24 : event_e);
							}
						else if((event_s.getTime() < range_s.getTime()) && (event_e.getTime() >= range_e.getTime())) // starts before selected day, ends after selected day
							{
							event_s = 0;
							event_e = 24;
							}
						else if((event_s.getTime() < range_s.getTime()) && (event_e.getTime() >= range_s.getTime()) && (event_e.getTime() < range_e.getTime())) // starts before selected day, ends at selected day
							{
							event_s = 0;
							event_e = event_e.getHours() + (event_e.getMinutes() / 60);

							event_e = (event_e == 0 ? 24 : event_e);
							}
						else if((event_s.getTime() >= range_s.getTime()) && (event_s.getTime() < range_e.getTime()) && (event_e.getTime() >= range_e.getTime())) // starts at selected day, ends after selected day
							{
							event_s = event_s.getHours() + (event_s.getMinutes() / 60);
							event_e = 24;
							}

						var m = ((document.getElementById('tbl_scroll').scrollWidth - 30) / x) - 1;

						var h = ((event_e - event_s) * 41) - 3;
						var l = (30 + (m / data_list[row_id].length * col_id) + (m * day_id) + day_id);
						var t = (event_s * 41) + 1 - 1;
						var w = (m / data_list[row_id].length);

						data_h.push('<div class="calendar_event_day" onmousedown="popup_calendar_menu(event, \'' + calendar.style + '\', ' + (event[0] * 1000) + ', \'' + event[3] + '\');" style="height: ' + h + 'px; left: ' + l + 'px; top: ' + t + 'px; width: ' + w + 'px;">');
							data_h.push('<div class="calendar_event_border">');
								data_h.push('<div class="calendar_event_text">');
									data_h.push((event[4] ? event[4] : '(Kein Titel)') + (event[5] ? '<br>' + event[5] : ''));
								data_h.push('</div>');
							data_h.push('</div>');
						data_h.push('</div>');
						}
					}
				}
			}

		time_w.setDate(time_w.getDate() + 1); // + 1 day
		}

	var o = document.getElementById('calendar_events');

	o.style.display = 'block';
	o.innerHTML = data_h.join('');

	////////////////////////////////////////////////////////////////////////////////

	var time_w	= new Date();
	var range_s	= new Date(); // range start
	var range_e	= new Date(); // range end

	var data_h	= new Array();

	var event_count = 0;

	if(calendar.style == 'd')
		{
		time_w.setTime(calendar.time * 1000);

		x = 1;
		}

	if(calendar.style == 'w')
		{
		time_w.setTime(calendar.time * 1000);

		while(time_w.getDay() != 1)
			{
			time_w.setDate(time_w.getDate() - 1);
			}

		x = 7;
		}

	for(day_id = 0; day_id < x; day_id = day_id + 1)
		{
		// this "pre-check" is something necccesary
		// event A starts on day 15 and ends on day 16
		// event B starts on day 16 and ends on day 16
		// both events overlap on day 16
		// event A uses full width on day 15
		// event A use half width on day 16 ... beside event B
		// if we do not use this "pre-check" event A will also use half width on day 15 which is wrong

		var data_list = new Array();

		for(row_id = 0; row_id < calendar.events.length; row_id = row_id + 1)
			{
			var event = calendar.events[row_id];

			var event_s = new Date(event[0] * 1000);
			var event_e = new Date(event[1] * 1000);

			range_s.setTime(time_w.getTime());
			range_e.setTime(time_w.getTime());
			range_e.setDate(range_e.getDate() + 1); // + 1 day

			range_s.setHours(0);
			range_s.setMinutes(0)
			range_s.setSeconds(0)
			range_s.setMilliseconds(0)

			range_e.setHours(0)
			range_e.setMinutes(0)
			range_e.setSeconds(0)
			range_e.setMilliseconds(0)

// 19.12 12:51 - 20.12 13:51
// 26.12 12:51 - 26.12 13:51
// 29.12 00:00 - 30.12 00:00

			if(event[2] == 1) // all day event ... show it different way
				{
				if((event_s.getTime() >= range_s.getTime()) && (event_e.getTime() < range_e.getTime())) // starts at selected day, ends at selected day
					{
					data_list.push(event);
					}
				else if((event_s.getTime() <= range_s.getTime()) && (event_e.getTime() >= range_e.getTime())) // starts before selected day, ends after selected day
					{
					data_list.push(event);
					}
				else if((event_s.getTime() <= range_s.getTime()) && (event_e.getTime() >= range_s.getTime()) && (event_e.getTime() < range_e.getTime())) // starts before selected day, ends at selected day
					{
					if((event_e.getHours() + (event_e.getMinutes() / 60)) != 0)
						{
						data_list.push(event); // ends at 00:00 this day
						}
					}
				else if((event_s.getTime() >= range_s.getTime()) && (event_s.getTime() < range_e.getTime()) && (event_e.getTime() >= range_e.getTime())) // starts at selected day, ends after selected day
					{
					data_list.push(event);
					}
				}
			}

		if(data_list.length > 0)
			{
			for(evt_id = 0; evt_id < data_list.length; evt_id = evt_id + 1)
				{
				var event = data_list[evt_id];

				var event_s = new Date(event[0] * 1000);
				var event_e = new Date(event[1] * 1000);

				var m = ((document.getElementById('tbl_scroll').scrollWidth - 30) / x) - 1;

				var h = 21;
				var l = (30 + (m * day_id) + day_id);
				var t = (evt_id * 23);
				var w = m;

				data_h.push('<div onmousedown="popup_calendar_menu(event, \'' + calendar.style + '\', ' + (event[0] * 1000) + ', \'' + event[3] + '\');" style="height: ' + h + 'px; left: ' + l + 'px; position: absolute; top: ' + t + 'px; width: ' + w + 'px; z-index: 1;">');
					data_h.push('<div class="calendar_event_border">');
						data_h.push('<div class="calendar_event_text">');
							data_h.push((event[4] ? event[4] : '(Kein Titel)') + (event[5] ? '<br>' + event[5] : ''));
						data_h.push('</div>');
					data_h.push('</div>');
				data_h.push('</div>');
				}

			event_count = (data_list.length > event_count ? data_list.length : event_count);
			}

		time_w.setDate(time_w.getDate() + 1); // + 1 day
		}

	////////////////////////////////////////////////////////////////////////////////

	var o = document.getElementById('calendar_all_day_events');

	o.innerHTML = data_h.join('');

	////////////////////////////////////////////////////////////////////////////////

	var o = document.getElementById("calendar_row_for_all_day_events");

	o.childNodes[0].childNodes[0].style.height = (event_count == 0 ? 0 : (event_count * 23) + 1)  + 'px';
	o.childNodes[0].style.display = (event_count == 0 ? 'none' : 'block');
	o.childNodes[0].style.height = (event_count == 0 ? 0 : (event_count * 23) + 1)  + 'px';

	////////////////////////////////////////////////////////////////////////////////

	var a = document.getElementById("calendar_row_for_all_day_events");
	var b = document.getElementById("calendar_row_for_calendar");

	document.getElementById("tbl_scroll").style.height = (b.offsetHeight - a.offsetHeight) + 'px';
	}

