function calendar_events_find(data_list, data_cols, type)
	{
	for(cols_id = 0; cols_id < data_cols.length; cols_id = cols_id + 1)
		{
		var data_evts = data_cols[cols_id];

		if(data_list[0][0] >= data_evts[data_evts.length - 1][1])
			{
			if(type == 'f') // free
				{
				return(1 + cols_id);
				}
			}
		else
			{
			if(type == 'o') // overlap
				{
				return(1);
				}
			}
		}

	return(0);
	}

