function calendar_draw_events_of_agenda()
	{
	var data_h	= new Array();

	var event_xx = '';
	var event_yy = '';

	// user range start/end to create list, not events themself ... some events may end the other day and therefore needs to be listed more than once

	for(evt_id = 0; evt_id < calendar.events.length; evt_id = evt_id + 1)
		{
		var event = calendar.events[evt_id];

		var event_s = new Date(event[0] * 1000);
		var event_e = new Date(event[1] * 1000);

		event_xx = calendar.day_of_week[(event_s.getDay() + 6) % 7] + ', ' + event_s.getDate() + '. ' + calendar.month_of_year[event_s.getMonth()] + ' ' + event_s.getFullYear();

		if(event_xx == event_yy)
			{
			data_h.push('<div class="calendar_agenda_row_delimiter">');
			data_h.push('</div>');
			}

		if(event_xx != event_yy)
			{
			data_h.push('<div class="calendar_agenda_row_date">');
				data_h.push('<div class="calendar_agenda_date">');
					data_h.push(event_xx);
				data_h.push('</div>');
			data_h.push('</div>');
			}

		event_yy = event_xx;

		data_h.push('<div ondblclick="handle_link({ cmd : \'Show\', server_id : \'' + event[3] + '\' });" class="calendar_agenda_row_data">');

			data_h.push('<div class=\"calendar_agenda_subject\">');
				data_h.push(event[4] ? event[4] : '(Kein Titel)');
			data_h.push('</div>');

			data_h.push('<div class="calendar_agenda_time">');

				if((event_s.getFullYear() == event_e.getFullYear()) && (event_s.getMonth() == event_e.getMonth()) && (event_s.getDate() == event_e.getDate()))
					{
					data_h.push(event_s.getHours());
					data_h.push(':');
					data_h.push(event_s.getMinutes() < 10 ? '0' : '');
					data_h.push(event_s.getMinutes());
					data_h.push(' - ');
					data_h.push(event_e.getHours());
					data_h.push(':');
					data_h.push(event_e.getMinutes() < 10 ? '0' : '');
					data_h.push(event_e.getMinutes());
					}
//				else if(event[2] == 1) // AllDayEvent
//					{
//					data_h.push(event_s.getDate());
//					data_h.push('. ');
//					data_h.push(calendar.month_of_year[event_s.getMonth()]);
//					}
				else
					{
					data_h.push(event_s.getDate());
					data_h.push('. ');
					data_h.push(calendar.month_of_year[event_s.getMonth()]);
					data_h.push(', ');
					data_h.push(event_s.getHours());
					data_h.push(':');
					data_h.push(event_s.getMinutes() < 10 ? '0' : '');
					data_h.push(event_s.getMinutes());
					data_h.push(' - ');
					data_h.push(event_e.getDate());
					data_h.push('. ');
					data_h.push(calendar.month_of_year[event_e.getMonth()]);
					data_h.push(', ');
					data_h.push(event_e.getHours());
					data_h.push(':');
					data_h.push(event_e.getMinutes() < 10 ? '0' : '');
					data_h.push(event_e.getMinutes());
					}

			data_h.push('</div>');

			if(event[5].length != 0)
				{
				data_h.push('<div class="calendar_agenda_location">');
					data_h.push(event[5]);
				data_h.push('</div>');
				}

		data_h.push('</div>');
		}

	document.getElementById('calendar_scroll').innerHTML = data_h.join('');
	}

