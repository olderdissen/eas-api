function popup_notes_menu(e, obj, server_id)
	{
	if(e.button != 2)
		{
		return(false);
		}

	////////////////////////////////////////////////////////////////////////
	// get menu items
	////////////////////////////////////////////////////////////////////////

	var x = get_menu_items("Notes");

	////////////////////////////////////////////////////////////////////////
	// create popup
	////////////////////////////////////////////////////////////////////////

	var data_h = new Array();

	data_h.push('<div id="popup_dialog" class="popup_dialog" style="height: 80px; width: 200px;">');
		data_h.push('<div class="popup_title" id="popup_title" onmousedown="evt_drag_start(this.parentNode);">');
			data_h.push('???');
		data_h.push('</div>');
		for(action_id = 0x00; action_id < x.length; action_id = action_id + 0x01)
			{
			data_h.push('<div class="popup_menu_out" onmouseout="this.className = \'popup_menu_out\';" onmouseover="this.className = \'popup_menu_over\';" onclick="handle_link({ cmd : \'' + ["Edit", "DeleteConfirm", "Cancel"][action_id] + '\', server_id : \'' + server_id + '\' });">');
				data_h.push(popup_buttons[x[action_id]]);
			data_h.push('</div>');
			}
	data_h.push('</div>');

	////////////////////////////////////////////////////////////////////////
	// options of menu
	////////////////////////////////////////////////////////////////////////

	var options = 0xFF;

	options = options & (0xFF - 0x08); // disable cancel

	////////////////////////////////////////////////////////////////////////
	// height of menu
	////////////////////////////////////////////////////////////////////////

	handle_link({ cmd : "PopupCreate", data : data_h });
	handle_link({ cmd : "PopupTitle", title : obj.childNodes[0].innerHTML });
	handle_link({ cmd : "PopupHeight", options : options });
	handle_link({ cmd : "PopupPositionMouse" });
	}

