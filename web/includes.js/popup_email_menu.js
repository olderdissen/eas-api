function popup_email_menu(e, obj, server_id)
	{
	if(e.button != 2)
		{
		return(false);
		}

	////////////////////////////////////////////////////////////////////////
	// get menu items
	////////////////////////////////////////////////////////////////////////

	var x = get_menu_items("Email");

	////////////////////////////////////////////////////////////////////////
	// get menu items
	////////////////////////////////////////////////////////////////////////

	var collection_list = null;

	onload = function(data)
		{
		collection_list = JSON.parse(data);
		}

	ajax({ type : "GET", url : "index.php?Cmd=Menu&ItemId=Folders&CollectionId=" + state.collection_id, success : onload, async : false });

	////////////////////////////////////////////////////////////////////////
	// create popup
	////////////////////////////////////////////////////////////////////////

	var data_h = new Array();

	data_h.push('<div id="popup_dialog" class="popup_dialog" style="height: 100px; width: 200px;">');
		data_h.push('<div class="popup_title" id="popup_title" onmousedown="evt_drag_start(this.parentNode);">');
			data_h.push('???');
		data_h.push('</div>');
		for(action_id = 0x00; action_id < x.length; action_id = action_id + 0x01)
			{
			data_h.push('<div class="popup_menu_out" onmouseout="this.className = \'popup_menu_out\';" onmouseover="submenu_show(' + action_id + '); this.className = \'popup_menu_over\';" id="' + ["Show", "Edit", "DeleteConfirm", "DeleteConfirm", "Forward", "Reply", "MarkAsRead", "MarkAsUnread", "Flag", "Move", "Cancel"][action_id] + ':' + server_id + '" onclick="handle_link({ cmd : \'' + ["Show", "Edit", "DeleteConfirm", "DeleteConfirm", "Forward", "Reply", "MarkAsRead", "MarkAsUnread", "Flag", "Move", "Cancel"][action_id] + '\', server_id : \'' + server_id + '\' });">');
				data_h.push(popup_buttons[x[action_id]]);
			data_h.push('</div>');
			}
	data_h.push('</div>');

	////////////////////////////////////////////////////////////////////////
	// options of menu
	////////////////////////////////////////////////////////////////////////

	// 0x0001 Titel
	// 0x0002 öffnen
	// 0x0004 bearbeiten
	// 0x0008 löschen
	// 0x0010 Verwerfen
	// 0x0020 weiterleiten
	// 0x0040 antworten
	// 0x0080 als gelesen markieren
	// 0x0100 als ungelesen markieren
	// 0x0200 Markierung
	// 0x0400 Verschieben
	// 0x0800 Abbrechen

	// 0x1000 set
	// 0x2000 completed
	// 0x4000 clear

	var options = 0xFFFF; // enable all items

//	options = options & (0xFFFF - 0x0200); // disable - mark - since it is not developed yet
//	options = options & (0xFFFF - 0x0400); // disable - move - since it is not developed yet

	options = options & (0xFFFF - 0x1000); // disable - set
	options = options & (0xFFFF - 0x2000); // disable - completed
	options = options & (0xFFFF - 0x4000); // disable - clear
	options = options & (0xFFFF - 0x0800); // disable - cancel

	options = options & (state.collection_id == '9003' ? (0xFFFF - 0x0004) : 0xFFFF); // disable - draft - edit
	options = options & (state.collection_id == '9003' ? (0xFFFF - 0x0008) : 0xFFFF); // disable - draft - delete
	options = options & (state.collection_id == '9003' ? (0xFFFF - 0x0020) : 0xFFFF); // disable - draft - forward
	options = options & (state.collection_id == '9003' ? (0xFFFF - 0x0040) : 0xFFFF); // disable - draft - reply
	options = options & (state.collection_id == '9003' ? (0xFFFF - 0x0400) : 0xFFFF); // disable - draft - move

	options = options & (state.collection_id != '9003' ? (0xFFFF - 0x0004) : 0xFFFF); // disable - edit
	options = options & (state.collection_id != '9003' ? (0xFFFF - 0x0010) : 0xFFFF); // disable - discard

	options = options & (state.collection_id == '9004' ? (0xFFFF - 0x0004) : 0xFFFF); // disable - trash - edit
	options = options & (state.collection_id == '9004' ? (0xFFFF - 0x0020) : 0xFFFF); // disable - trash - forward
	options = options & (state.collection_id == '9004' ? (0xFFFF - 0x0040) : 0xFFFF); // disable - trash - reply

	options = options & (document.getElementById(server_id).style.fontWeight != "bold" ? (0xFFFF - 0x0080) : 0xFFFF); // disable - mark read
	options = options & (document.getElementById(server_id).style.fontWeight == "bold" ? (0xFFFF - 0x0100) : 0xFFFF); // disable - mark unread

	for(i = 0x00; i < document.getElementById(server_id + "_labels").childNodes.length; i = i + 0x01)
		{
		if(document.getElementById(server_id + "_labels").childNodes[i].src.substr(0 - 17) == "list_icon_sms.png")
			{
			options = options & (state.collection_id == '9002' ? (0xFFFF - 0x0020) : 0xFFFF); // disable - inbox - forward
			options = options & (state.collection_id == '9002' ? (0xFFFF - 0x0040) : 0xFFFF); // disable - inbox - reply
			options = options & (state.collection_id == '9002' ? (0xFFFF - 0x0400) : 0xFFFF); // disable - inbox - move

			break;
			}
		}

	////////////////////////////////////////////////////////////////////////
	// create submenu
	////////////////////////////////////////////////////////////////////////

	submenu_show = function(popup_item, e)
		{
		var o = document.getElementById('popup_dialog');

		////////////////////////////////////////////////////////////////////////
		// markieren
		////////////////////////////////////////////////////////////////////////

		if((document.getElementById("popup_submenu_flag") == null) && (popup_item == 0x08))
			{
			var data_h = new Array();

			data_h.push('<div id="popup_submenu_flag" class="popup_dialog" style="left: ' + (o.childNodes[0x07].offsetWidth - 10) + 'px; top: ' + (o.childNodes[0x07].offsetTop + 10) + 'px; height: 60px; width: 200px;">');
				for(action_id = 0x00; action_id < 0x03; action_id = action_id + 0x01)
					{
					data_h.push('<div class="popup_menu_out" onmouseout="this.className = \'popup_menu_out\';" onmouseover="this.className = \'popup_menu_over\';" onclick="handle_link({ cmd : \'Flag\', server_id : \'' + server_id + '\', long_id : \'' + action_id + '\' });">');
						data_h.push(popup_buttons[x[action_id + 0x0B]]);
					data_h.push('</div>');
					}
			data_h.push('</div>');

			o.childNodes[0x09].innerHTML = popup_buttons[x[0x08]] + data_h.join('');

			////////////////////////////////////////////////////////////////////////

			// ...
			}

		if((document.getElementById("popup_submenu_flag") != null) && (popup_item != 0x08))
			{
			o.childNodes[0x09].innerHTML = popup_buttons[x[0x08]];
			}

		////////////////////////////////////////////////////////////////////////
		// verschieben
		////////////////////////////////////////////////////////////////////////

		if((document.getElementById("popup_submenu_move") == null) && (popup_item == 0x09))
			{
			var data_h = new Array();

			var h = 0; // height of submenu

			data_h.push('<div id="popup_submenu_move" class="popup_dialog" style="left: ' + (o.childNodes[0x08].offsetWidth - 10) + 'px; top: ' + (o.childNodes[0x08].offsetTop + 10) + 'px; height: 20px; width: 200px;">');

				if(state.collection_id != "9002")
					{
					data_h.push('<div class="popup_menu_out" onmouseout="this.className = \'popup_menu_out\';" onmouseover="this.className = \'popup_menu_over\';" onclick="handle_link({ cmd : \'Move\', server_id : \'' + server_id + '\', item_id : \'9002\' });">');
						data_h.push('Posteingang');
					data_h.push('</div>');

					h = h + 20;
					}

				if(state.collection_id != "9004")
					{
					data_h.push('<div class="popup_menu_out" onmouseout="this.className = \'popup_menu_out\';" onmouseover="this.className = \'popup_menu_over\';" onclick="handle_link({ cmd : \'Move\', server_id : \'' + server_id + '\', item_id : \'9004\' });">');
						data_h.push('Papierkorb');
					data_h.push('</div>');

					h = h + 20;
					}

				for(collection_id in collection_list)
					{
					if(collection_id == state.collection_id)
						{
						continue;
						}

					data_h.push('<div class="popup_menu_out" onmouseout="this.className = \'popup_menu_out\';" onmouseover="this.className = \'popup_menu_over\';" onclick="handle_link({ cmd : \'Move\', server_id : \'' + server_id + '\', item_id : \'' + collection_id + '\' });">');
						data_h.push(collection_list[collection_id]);
					data_h.push('</div>');

					h = h + 20;
					}

			data_h.push('</div>');

			o.childNodes[0x0A].innerHTML = popup_buttons[x[0x09]] + data_h.join(''); // no sub menu for sms

			////////////////////////////////////////////////////////////////////////

			var p = document.getElementById('popup_submenu_move');

			p.style.height = h + 'px';
			}

		if((document.getElementById("popup_submenu_move") != null) && (popup_item != 0x09))
			{
			o.childNodes[0x0A].innerHTML = popup_buttons[x[0x09]];
			}
		}

	////////////////////////////////////////////////////////////////////////

	handle_link({ cmd : "PopupCreate", data : data_h });
	handle_link({ cmd : "PopupTitle", title : document.getElementById(server_id + "_subject").innerHTML });
	handle_link({ cmd : "PopupHeight", options : options });
	handle_link({ cmd : "PopupPositionMouse" });
	}

