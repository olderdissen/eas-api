function calendar_draw_events()
	{
	if(calendar.events.length == 0)
		{
		handle_link({ cmd : "ProgressStop" });

		return;
		}

	switch(calendar.style)
		{
		case('a'):
			calendar_draw_events_of_agenda();

			break;
		case('d'):
			calendar_draw_events_of_days();

			break;
		case('w'):
			calendar_draw_events_of_days();

			break;
		case('m'):
			calendar_draw_events_of_month();

			break;
		case('y'):
			calendar_draw_events_of_year();

			break;
		}

	handle_link({ cmd : "ProgressStop" });
	}

