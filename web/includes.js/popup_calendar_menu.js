function popup_calendar_menu(e, view_id, time_id, server_id)
	{
	if(e.button != 2)
		{
		return(false);
		}

	////////////////////////////////////////////////////////////////////////
	// get menu items
	////////////////////////////////////////////////////////////////////////

	var x = get_menu_items("Calendar");

	////////////////////////////////////////////////////////////////////////
	// create popup
	////////////////////////////////////////////////////////////////////////

	var data_h = new Array();

	data_h.push('<div id="popup_dialog" class="popup_dialog" style="width: 200px;">');
		data_h.push('<div class="popup_title" id="popup_title" onmousedown="evt_drag_start(this.parentNode);">');
			data_h.push('???');
		data_h.push('</div>');
		for(action_id = 0x00; action_id < x.length; action_id = action_id + 0x01)
			{
			data_h.push('<div class="popup_menu_out" onmouseout="this.className = \'popup_menu_out\';" onmouseover="this.className = \'popup_menu_over\';" onclick="handle_link({ cmd : \'' + ["Show", "Edit", "DeleteConfirm", "Create", "ShowDay", "ShowAgenda", "Cancel"][action_id] + '\', server_id : \'' + server_id + '\' , time_id:\'' + time_id + '\' });">');
				data_h.push(popup_buttons[x[action_id]]);
			data_h.push('</div>');
			}
	data_h.push('</div>');

	////////////////////////////////////////////////////////////////////////
	// options of menu
	////////////////////////////////////////////////////////////////////////

	// 0x01 Titel
	// 0x02 Ereignis anzeigen
	// 0x04 Ereignis bearbeiten
	// 0x08 Ereignis löschen
	// 0x10 Ereignis erstellen
	// 0x20 Tag anzeigen
	// 0x40 Agenda anzeigen
	// 0x80 Abbrechen

	var options = 0xFF;

	switch(view_id)
		{
		case("d"):
			options = options & (0xFF - 0x40); // disable agenda
			options = options & (0xFF - 0x20); // disable day

			options = options & (server_id == '' ? (0xFF - 0x08) : 0xFF); // disable delete
			options = options & (server_id == '' ? (0xFF - 0x04) : 0xFF); // disable edit
			options = options & (server_id == '' ? (0xFF - 0x02) : 0xFF); // disable show

			break;
		case("w"):
			options = options & (server_id == '' ? (0xFF - 0x08) : 0xFF); // disable delete
			options = options & (server_id == '' ? (0xFF - 0x04) : 0xFF); // disable edit
			options = options & (server_id == '' ? (0xFF - 0x02) : 0xFF); // disable show

			break;
		case("m"):
			options = options & (0xFF - 0x08); // disable delete
			options = options & (0xFF - 0x04); // disable edit
			options = options & (0xFF - 0x02); // disable show

			break;
		}

	options = options & (state.collection_id == '9007' ? (0xFF - 0x10) : 0xFF); // disable create
	options = options & (state.collection_id == '9007' ? (0xFF - 0x20) : 0xFF); // disable day
	options = options & (state.collection_id == '9007' ? (0xFF - 0x40) : 0xFF); // disable agenda

	options = options & (0xFF - 0x80); // disable cancel

	////////////////////////////////////////////////////////////////////////
	// height of menu
	////////////////////////////////////////////////////////////////////////

	handle_link({ cmd : "PopupCreate", data : data_h });
	handle_link({ cmd : "PopupHeight", options : options });
	handle_link({ cmd : "PopupPositionMouse" });

	////////////////////////////////////////////////////////////////////////
	// set popup title
	////////////////////////////////////////////////////////////////////////

	var time_w = new Date();

	time_w.setTime(time_id);

	switch(view_id)
		{
		case("d"):
			handle_link({ cmd : "PopupTitle", title : calendar.day_of_week[(time_w.getDay() + 6) % 7] + ', ' + time_w.getHours() + ':' + pre_null(time_w.getMinutes()) });

			break;
		case("w"):
			handle_link({ cmd : "PopupTitle", title : calendar.day_of_week[(time_w.getDay() + 6) % 7] + ', ' + time_w.getHours() + ':' + pre_null(time_w.getMinutes()) });

			break;
		case("m"):
			handle_link({ cmd : "PopupTitle", title : calendar.day_of_week[(time_w.getDay() + 6) % 7] + ', ' + time_w.getDate() + '. ' + calendar.month_of_year[time_w.getMonth()] });

			break;
		default:
			handle_link({ cmd : "PopupTitle", title : "..." });

			break;
		}
	}

