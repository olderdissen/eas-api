// see calendar_jump_to

function calendar_mark_selection(value)
	{
	var time_c	= new Date(); // current
	var time_s	= new Date(); // selected

	time_c.setTime(calendar.time * 1000);
	time_s.setTime(value);

	handle_link({ cmd : "CalendarScrollPositionSave" });

	if(calendar.style == 'd')
		{
		if(calendar.time != value / 1000)
			{
			calendar.time = value / 1000;

			calendar_draw_calendar();
			calendar_draw_events();
			calendar_draw_birthdays();
			}
		}

	if(calendar.style == 'w')
		{
		if(calendar.time != value / 1000)
			{
			calendar.time = value / 1000;

			calendar_draw_calendar();
			calendar_draw_events();
			calendar_draw_birthdays();
			}
		}

	if(calendar.style == 'm')
		{
		if(time_c.getMonth() != time_s.getMonth())
			{
			calendar.time = value / 1000;

			calendar_draw_calendar();
			calendar_retrieve("data");
			calendar_retrieve("birthday");
			}
		else if(calendar.time != value / 1000)
			{
			calendar.time = value / 1000;

			calendar_draw_calendar();
			calendar_draw_events();
			calendar_draw_birthdays();
			}
		}
	}

