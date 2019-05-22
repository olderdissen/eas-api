function popup_folder_error(ServerId)
	{
	var data_h = new Array();

	data_h.push('<div id="popup_dialog" class="popup_dialog" style="height: 105px;>');
		data_h.push('<div class="popup_title" id="popup_title" onmousedown="evt_drag_start(this.parentNode);">');
			data_h.push('ERROR');
		data_h.push('</div>');
		data_h.push('<div class="popup_text" id="popup_text" style="text-align: left;">');

			switch(ServerId)
				{
				case(1):
					break;
				case(2):
					data_h.push("A folder with that name already exists or the specified folder is a special folder.");

					break;
				case(3):
					data_h.push("The specified folder is the Recipient information folder, which cannot be updated by the client.");

					break;
				case(4):
					data_h.push("The specified folder does not exist.");

					break;
				case(5):
					data_h.push("The specified parent folder was not found.");

					break;
				case(6):
					data_h.push("An error occured on the server.");

					break;
				}

		data_h.push('</div>');
		data_h.push('<div class="popup_button popup_border_a" onmouseover="popup_change_style_button(this, 2);" onmouseout="popup_change_style_button(this, 3);" onmousedown="popup_change_style_button(this, 1);" onmouseup="popup_change_style_button(this, 0);" onclick="popup_folder_action(0x10, \'\');" style="left: 30px; top: 70px;">');
			data_h.push('OK');
		data_h.push('</div>');
	data_h.push('</div>');

	////////////////////////////////////////////////////////////////////////
	// create blocking node
	////////////////////////////////////////////////////////////////////////

	handle_link({ cmd : "PopupCreate", data : data_h });

	handle_link({ cmd : "PopupPositionCenter" });
	}

