function popup_contacts_menu(e, obj, server_id)
	{
	////////////////////////////////////////////////////////////////////////
	// exit on any other mouse buton than the right one
	////////////////////////////////////////////////////////////////////////

	if(e.button != 2)
		{
		return(false);
		}

	////////////////////////////////////////////////////////////////////////
	// get menu items
	////////////////////////////////////////////////////////////////////////

	var x = get_menu_items("Contacts");

	////////////////////////////////////////////////////////////////////////
	// get menu items
	////////////////////////////////////////////////////////////////////////

	var number_list = null;

	onload_n = function(data)
		{
		number_list = JSON.parse(data);
		}

	ajax({ type : "GET", url : "index.php?Cmd=Menu&ItemId=Numbers&CollectionId=" + state.collection_id + "&ServerId=" + server_id, success : onload_n, async : false });

	////////////////////////////////////////////////////////////////////////
	// get menu items
	////////////////////////////////////////////////////////////////////////

	var collection_list = null;

	onload_c = function(data)
		{
		collection_list = JSON.parse(data);
		}

	ajax({ type : "GET", url : "index.php?Cmd=Menu&ItemId=Folders&CollectionId=" + state.collection_id, success : onload_c, async : false });

	////////////////////////////////////////////////////////////////////////
	// create popup
	////////////////////////////////////////////////////////////////////////

	var data_h = new Array();

	data_h.push('<div id="popup_dialog" class="popup_dialog" style="height: 120px; width: 200px;">');
		data_h.push('<div class="popup_title" id="popup_title" onmousedown="evt_drag_start(this.parentNode);">');
			data_h.push('???');
		data_h.push('</div>');
		for(action_id = 0x00; action_id < x.length; action_id = action_id + 0x01)
			{
			data_h.push('<div class="popup_menu_out" onmouseout="this.className = \'popup_menu_out\';" onmouseover="submenu_show(' + action_id + '); this.className = \'popup_menu_over\';" onclick="handle_link({ cmd : \'' + ["Edit", "DeleteConfirm", "SendContact", "SendVCard", "Nothing"][action_id] + '\', server_id : \'' + server_id + '\' });">');
				data_h.push(popup_buttons[x[action_id]]);
			data_h.push('</div>');
			}
	data_h.push('</div>');

	////////////////////////////////////////////////////////////////////////
	// options of menu
	////////////////////////////////////////////////////////////////////////

	// 0x01 title
	// 0x02 edit
	// 0x04 delete
	// 0x08 send contact information
	// 0x10 send namecard
	// 0x20 move
	// 0x40 cancel

	var options = 0xFF;

	options = options & (Object.keys(number_list).length == 0 ? (0xFF - 0x08) : 0xFF); // disable send contact information
	options = options & (Object.keys(collection_list).length == 0 ? (0xFF - 0x20) : 0xFF); // disable move
	options = options & (0xFF - 0x40); // disable cancel

//	no sub menu if only one contact information exist

	////////////////////////////////////////////////////////////////////////
	// create submenu
	////////////////////////////////////////////////////////////////////////

	submenu_show = function(popup_item, e)
		{
		var o = document.getElementById('popup_dialog');

		////////////////////////////////////////////////////////////////////////
		// markieren
		////////////////////////////////////////////////////////////////////////

		if((document.getElementById("popup_submenu_phone") == null) && (popup_item == 0x02))
			{
			var data_h = new Array();

			data_h.push('<div id="popup_submenu_phone" class="popup_dialog" style="left: ' + (o.childNodes[0x03].offsetWidth - 10) + 'px; top: ' + (o.childNodes[0x03].offsetTop + 10) + 'px; height: 20px; width: 350px;">');
				for(field in number_list)
					{
					data_h.push('<div class="popup_menu_out" onmouseout="this.className = \'popup_menu_out\';" onmouseover="this.className = \'popup_menu_over\';" onclick="handle_link({ cmd : \'SendContact\', server_id : \'' + server_id + '\', item_id : \'' + field + '\' });">');
						data_h.push('<span>' + field + ': </span>');
						data_h.push('<span style="position: absolute; left: 175px;">' + number_list[field] + '</span>');
					data_h.push('</div>');
					}
			data_h.push('</div>');

			o.childNodes[0x03].innerHTML = popup_buttons[x[0x02]] + data_h.join('');
			}

		if((document.getElementById("popup_submenu_phone") != null) && (popup_item != 0x02))
			{
			o.childNodes[0x03].innerHTML = popup_buttons[x[0x02]];
			}

		////////////////////////////////////////////////////////////////////////
		// verschieben
		////////////////////////////////////////////////////////////////////////

		if((document.getElementById("popup_submenu_move") == null) && (popup_item == 0x04))
			{
			var data_h = new Array();

			data_h.push('<div id="popup_submenu_move" class="popup_dialog" style="left: ' + (o.childNodes[0x05].offsetWidth - 10) + 'px; top: ' + (o.childNodes[0x05].offsetTop + 10) + 'px; height: 20px; width: 200px;">');
				for(collection_id in collection_list)
					{
					if(collection_id == state.collection_id)
						{
						continue;
						}

					data_h.push('<div class="popup_menu_out" onmouseout="this.className = \'popup_menu_out\';" onmouseover="this.className = \'popup_menu_over\';" onclick="handle_link({ cmd : \'Move\', server_id : \'' + server_id + '\', item_id : \'' + collection_id + '\' });">');
						data_h.push(collection_list[collection_id]);
					data_h.push('</div>');
					}
			data_h.push('</div>');

			o.childNodes[0x05].innerHTML = popup_buttons[x[0x04]] + data_h.join(''); // no sub menu for sms
			}

		if((document.getElementById("popup_submenu_move") != null) && (popup_item != 0x04))
			{
			o.childNodes[0x05].innerHTML = popup_buttons[x[0x04]];
			}
		}

	////////////////////////////////////////////////////////////////////////
	// ...
	////////////////////////////////////////////////////////////////////////

	handle_link({ cmd : "PopupCreate", data : data_h });
	handle_link({ cmd : "PopupTitle", title : obj.childNodes[1].innerHTML });
	handle_link({ cmd : "PopupHeight", options : options });
	handle_link({ cmd : "PopupPositionMouse" });
	}

