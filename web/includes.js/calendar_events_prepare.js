function calendar_events_prepare(data_list)
	{
	var data_rows = new Array();							// new row
	var data_cols = new Array();							// new column
	var data_evts = new Array();							// new event

	data_rows.push(data_cols);							// add column to row
	data_cols.push(data_evts);							// add event to column
	data_evts.push(data_list[0]);							// fill event

	while(1)
		{
		data_list.shift();							// remove first event from list

		if(data_list.length == 0)
			{
			break;								// no further events
			}

		var data_over = calendar_events_find(data_list, data_cols, 'o');	// find overlapping

		if(data_over == 0)
			{
			var data_cols = new Array();					// new column
			var data_evts = new Array();					// new event

			data_rows.push(data_cols);					// add column to row
			data_cols.push(data_evts);					// add event to column
			data_evts.push(data_list[0]);					// fill event

			continue;
			}

		var data_free = calendar_events_find(data_list, data_cols, 'f');	// find free

		if(data_free == 0)
			{
			var data_evts = new Array();					// new event

			data_cols.push(data_evts);					// add event to column
			data_evts.push(data_list[0]);					// fill event

			continue;
			}

		data_cols[data_free - 1].push(data_list[0]);				// fill event
		}

	return(data_rows);
	}

