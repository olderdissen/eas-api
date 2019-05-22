function calendar_retrieve(type)
	{
	var range_s = new Date(); // range start
	var range_e = new Date(); // range end

	handle_link({ cmd : "ProgressStart" });

	switch(calendar.style)
		{
		case('a'):
			//range_s.setTime(calendar.time * 1000); // do not use selected date ... use current date instead
			//range_s.setMonth(range_s.getMonth() - 1);
			range_s.setDate(range_s.getDate() - 14); // show from last 14 days to future

			range_s.setHours(0);
			range_s.setMinutes(0);
			range_s.setSeconds(0);
			range_s.setMilliseconds(0);

			break;
		case('d'):
			range_s.setTime(calendar.time * 1000);
			range_e.setTime(calendar.time * 1000);

			range_e.setDate(range_e.getDate() + 1); // + 1 day

			range_s.setHours(0);
			range_s.setMinutes(0);
			range_s.setSeconds(0);
			range_s.setMilliseconds(0);

			range_e.setHours(0);
			range_e.setMinutes(0);
			range_e.setSeconds(0);
			range_e.setMilliseconds(0);

			break;
		case('w'):
			range_s.setTime(calendar.time * 1000);
			range_e.setTime(calendar.time * 1000);

			range_e.setDate(range_e.getDate() + 7); // + 1 week

			while(range_s.getDay() != 1)
				{
				range_s.setDate(range_s.getDate() - 1);
				}

			range_s.setHours(0);
			range_s.setMinutes(0);
			range_s.setSeconds(0);
			range_s.setMilliseconds(0);

			range_e.setHours(0);
			range_e.setMinutes(0);
			range_e.setSeconds(0);
			range_e.setMilliseconds(0);

			break;
		case('m'):
			range_s.setTime(calendar.time * 1000);
			range_e.setTime(calendar.time * 1000);

			range_e.setMonth(range_e.getMonth() + 1); // + 1 month

			range_s.setDate(1);
			range_s.setHours(0);
			range_s.setMinutes(0);
			range_s.setSeconds(0);
			range_s.setMilliseconds(0);

			range_e.setDate(1);
			range_e.setHours(0);
			range_e.setMinutes(0);
			range_e.setSeconds(0);
			range_e.setMilliseconds(0);

			break;
		case('y'):
			range_s.setTime(calendar.time * 1000);
			range_e.setTime(calendar.time * 1000);

			range_e.setFullYear(range_e.getFullYear() + 1); // + 1 year

			range_s.setDate(1);
			range_s.setMonth(0);
			range_s.setHours(0);
			range_s.setMinutes(0);
			range_s.setSeconds(0);
			range_s.setMilliseconds(0);

			range_e.setDate(1);
			range_e.setMonth(0);
			range_e.setHours(0);
			range_e.setMinutes(0);
			range_e.setSeconds(0);
			range_e.setMilliseconds(0);

			break;
		}

	if(type == "birthday")
		{
		onload = function(data)
			{
			calendar.birthdays = JSON.parse(data);

			calendar_draw_birthdays();
			}

		ajax({ type : "GET", url : "index.php?Cmd=Birthday&CollectionId=9009&StartTime=" + (calendar.style == "a" ? "*" : range_s.getTime() / 1000) + "&EndTime=" + (calendar.style == "a" ? "*" : range_e.getTime() / 1000), success : onload});
		}

	if(type == "data")
		{
		onload = function(data)
			{
			calendar.events = JSON.parse(data);

			calendar_draw_events();
			}

		ajax({ type : "GET", url : "index.php?Cmd=Data&CollectionId=" + state.collection_id + "&StartTime=" + (calendar.style == "a" ? "*" : range_s.getTime() / 1000) + "&EndTime=" + (calendar.style == "a" ? "*" : range_e.getTime() / 1000), success : onload});
		}

	return(false);
	}

