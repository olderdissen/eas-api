function list_contacts(id_search_name, id_search_category, id_search_result, id_search_count)
	{
	var onkeydown = function(e)
		{
		suggest_register_keycode_keydown(e, null, suggest_search, null, null, null);
		}

	var onkeypress = function(e)
		{
		suggest_register_keycode_keypress(e, null, suggest_search, null, null, null);
		}

	////////////////////////////////////////////////////////////////////////

	var onload = function(data)
		{
		var r = JSON.parse(data);
		var c = 0;
		var d = 1;

		var h = new Array();

		////////////////////////////////////////////////////////////////////////////////
		// hide letter indicators if we search for names
		////////////////////////////////////////////////////////////////////////////////

		if(obj_search_name.value != "")
			d = 0;

		////////////////////////////////////////////////////////////////////////////////
		// hide letter indicators if we display a group
		////////////////////////////////////////////////////////////////////////////////

		if(obj_search_category.value != "*") // alle
			d = 0;

		////////////////////////////////////////////////////////////////////////////////

//		obj_search_result.style.display = "none";
//		obj_search_result.addEventListener("DOMSubtreeModified", function(e) { window.document.title = e.MutationEvent; obj_search_result.style.display = ""; }, false);

		var o = "";
		var m = "";
		var n = "";

		////////////////////////////////////////////////////////////////////////////////
		// ...
		////////////////////////////////////////////////////////////////////////////////

		obj_search_count.innerHTML = r.length;

		////////////////////////////////////////////////////////////////////////////////

			for(i = 0; i < r.length; i = i + 1)
				{
				n = r[i][0].substr(0, 1);

				n = (((n.charCodeAt(0) < 97) || (n.charCodeAt(0) > 122)) ? "#" : n); // #ABCDEFGHIJKLMNOPQRSTUVWXYZ

				if(o != n)
					{
					o = n;

					m = ' id="LETTER_' + o.toUpperCase() + '" ';

				 	if(d == 1) // display if we are in non search mode only
						{
						c = 0;

						h.push('<div' + m + 'class="contacts_row_title">');
							h.push('<div class="contacts_title_text">');
								h.push(o.toUpperCase());
							h.push('</div>');
						h.push('</div>');

						m = ' ';
						}
					}
				else
					{
					m = ' ';
					}

				h.push('<div' + m + 'onmousedown="popup_contacts_menu(event, this, \'' + r[i][2] + '\');" onmouseover="this.className = \'contacts_row_data list_hover\';" onmouseout="this.className = \'contacts_row_data ' + ["list_odd", "list_even"][c % 2] + '\';" class="contacts_row_data ' + ["list_odd", "list_even"][c % 2] + '">');
					h.push('<div class="contacts_data_image">');
						h.push('<img style="height: 44px; width: 44px;" src="' + r[i][3] + '">'); // original android image is 69 x 69 pixel, row is 48, image must be 48 - 1 - 1
					h.push('</div>');
					h.push('<div class="contacts_data_text">');
						h.push(r[i][1]);
					h.push('</div>');
				h.push('</div>');

				c = c + 1;
				}

		////////////////////////////////////////////////////////////////////////////////

		obj_search_result.innerHTML = h.join('');

		////////////////////////////////////////////////////////////////////////////////

		handle_link({ cmd : "ProgressStop" });
		}

	////////////////////////////////////////////////////////////////////////

	suggest_search = function()
		{
		state.category_id = obj_search_category.value

		clearTimeout(t);

		t = window.setTimeout(suggest_search_a, 500); // if user deletes chars, delay our search to prevent overload of server
		}

	suggest_search_a = function()
		{
		obj_search_name.focus();

		ajax({ type : "GET", url : "index.php?Cmd=Data&User=" + state.user + "&CollectionId=" + state.collection_id + "&Search=" + encodeURIComponent(obj_search_name.value) + "&Category=" + encodeURIComponent(obj_search_category.value), success : onload });

		handle_link({ cmd : "ProgressStart" });
		}

	////////////////////////////////////////////////////////////////////////

	var t = null;

	////////////////////////////////////////////////////////////////////////

	obj_search_name = document.getElementById(id_search_name);
	obj_search_category = document.getElementById(id_search_category);
	obj_search_result = document.getElementById(id_search_result);
	obj_search_count = document.getElementById(id_search_count);

	obj_search_name.autocomplete = 'off';
//	obj_search_name.onkeydown = onkeydown;
	obj_search_name.onkeypress = onkeypress;

	obj_search_category.onkeyup = suggest_search;
	obj_search_category.onchange = suggest_search;

	suggest_search(); // inital search

	return(true);
	}

function list_emails(id_search_result)
	{
	onload = function(data)
		{
		var r = JSON.parse(data);

		var h = new Array();

		var label = "";
		var count = 0;

		var time_w	= new Date();
		var range_t_s	= new Date(); // range start
		var range_t_e	= new Date(); // range end
		var range_y_s	= new Date(); // range start
		var range_y_e	= new Date(); // range end

		////////////////////////////////////////////////////////////////////////////////

		range_t_s.setHours(0);
		range_t_s.setMinutes(0)
		range_t_s.setSeconds(0)
		range_t_s.setMilliseconds(0)

		range_t_s = range_t_s.getTime();

		////////////////////////////////////////////////////////////////////////////////

		range_t_e.setDate(range_t_e.getDate() + 1); // + 1 day

		range_t_e.setHours(0)
		range_t_e.setMinutes(0)
		range_t_e.setSeconds(0)
		range_t_e.setMilliseconds(0)

		range_t_e = range_t_e.getTime();

		////////////////////////////////////////////////////////////////////////////////

		range_y_s.setDate(range_y_s.getDate() - 1); // - 1 day

		range_y_s.setHours(0);
		range_y_s.setMinutes(0)
		range_y_s.setSeconds(0)
		range_y_s.setMilliseconds(0)

		range_y_s = range_y_s.getTime();

		////////////////////////////////////////////////////////////////////////////////

		range_y_e.setHours(0)
		range_y_e.setMinutes(0)
		range_y_e.setSeconds(0)
		range_y_e.setMilliseconds(0)

		range_y_e = range_y_e.getTime();

		////////////////////////////////////////////////////////////////////////////////

		if(r.length == 0)
			{
			h.push('<table style="height: 100%; width: 100%;">');
				h.push('<tr>');
					h.push('<td style=\"text-align: center;\">');
						h.push('keine Nachrichten');
					h.push('</td>');
				h.push('</tr>');
			h.push('</table>');
			}

		for(i = 0; i < r.length; i = i + 1)
			{
			var mail_date_received		= r[i][0] * 1000;
			var mail_from			= r[i][1];
			var mail_to			= r[i][2];
			var mail_subject		= r[i][3];
			var mail_read			= r[i][4];
			var mail_status			= r[i][5];
			var mail_server_id		= r[i][6];
			var mail_class			= r[i][7];
			var mail_importance		= r[i][8];
			var mail_attachments		= r[i][9];
			var mail_message_class		= r[i][10];
			var mail_last_verb_executed	= r[i][11];

			var date = new Date();

			date.setTime(mail_date_received);

			if((mail_date_received >= range_t_s) && (mail_date_received <= range_t_e))
				{
				if(label != "Heute") // today
					{
					label = "Heute";
					count = 0;
					}

				date = pre_null(date.getHours()) + ":" + pre_null(date.getMinutes());
				}

			if((mail_date_received >= range_y_s) && (mail_date_received <= range_y_e))
				{
				if(label != "Gestern") // yesterday
					{
					label = "Gestern";
					count = 0;
					}

				date = pre_null(date.getDate()) + "." + pre_null(date.getMonth() + 1) + "." + pre_null(date.getFullYear());
				}

			if(mail_date_received < range_y_s)
				{
				if(label != "Vorherige Tage") // previous
					{
					label = "Vorherige Tage";
					count = 0;
					}

				date = pre_null(date.getDate()) + "." + pre_null(date.getMonth() + 1) + "." + pre_null(date.getFullYear());
				}

			if(mail_date_received > range_t_e)
				{
				if(label != "Zukünftige Tage") // future (if sender use wrong date)
					{
					label = "Zukünftige Tage";
					count = 0;
					}

				date = pre_null(date.getDate()) + "." + pre_null(date.getMonth() + 1) + "." + pre_null(date.getFullYear());
				}

			if(count == 0)
				{
				h.push('<div class="email_row_title">');
					h.push('<div class="email_title_check">');
						h.push('<input id="' + label + '" type="checkbox" onclick="mail_update_seleted(this);">');
					h.push('</div>');
					h.push('<div class="email_title_text">' + label + '</div>');
				h.push('</div>');
				}

			h.push('<table' + (mail_read == 1 ? '' : ' style="font-weight: bold;"') + ' id="' + mail_server_id + '" onmousedown="popup_email_menu(event, this, \'' + mail_server_id + '\');" onmouseover="this.className = \'list_medium list_hover\';" onmouseout="this.className = \'list_medium ' + ["list_odd", "list_even"][count % 2] + '\';" class="list_medium ' + ["list_odd", "list_even"][count % 2] + '">');
				h.push('<tr>');
					h.push('<td>');
						h.push('<input id="' + label + '" type="checkbox" name="msg_id[]" onclick="mail_update_seleted(this);" value="' + mail_server_id + '">');
					h.push('</td>');
					h.push('<td style="width: 100%;">');
						h.push('<table>');
							h.push('<tr>');
								h.push('<td>');
									h.push('<span id="' + mail_server_id + '_subject">');

										mail_subject = (mail_subject ? mail_subject.replace(/</, '&lt;') : mail_subject);
										mail_subject = (mail_subject ? mail_subject.replace(/>/, '&gt;') : mail_subject);

										h.push(mail_subject ? mail_subject : '&nbsp;');
									h.push('</span>');
								h.push('</td>');
							h.push('</tr>');
							h.push('<tr>');
								h.push('<td>');
									h.push('<table>');
										h.push('<tr>');
											if(mail_last_verb_executed != 0)
												{
												h.push('<td>');
													h.push(mail_last_verb_executed == 1 ? '<img src="images/replied.png"> ' : '');
													h.push(mail_last_verb_executed == 2 ? '<img src="images/replied_all.png"> ' : '');
													h.push(mail_last_verb_executed == 3 ? '<img src="images/forwarded.png"> ' : '');
												h.push('</td>');
												}

											h.push('<td style="height: 20px;" id="' + mail_server_id + '_sender">'); // images are 20
												h.push('<small>');
													h.push(mail_from);
												h.push('</small>');
											h.push('</td>');
										h.push('</tr>');
									h.push('</table>');
								h.push('</td>');
							h.push('</tr>');
						h.push('</table>');
					h.push('</td>');
					h.push('<td>');
						h.push('<table>');
							h.push('<tr>');
								h.push('<td>');
									h.push('<div style="background-color: #000000; height: 21px; width: 76px;" id="' + mail_server_id + '_labels">');
										h.push(mail_importance == 0 ? '<img src="images/list_icon_priority_low.png">' : '');
										h.push(mail_importance == 2 ? '<img src="images/list_icon_priority_high.png">' : '');
										h.push(mail_class == 'SMS' ? '<img src="images/list_icon_sms.png">' : '');
										h.push(mail_attachments == 1 ? '<img src="images/list_icon_attach.png">' : ''); // do not show this on meeting and voice
										h.push(mail_message_class == 'IPM.Schedule.Meeting.Request' ? '<img src="images/list_icon_calendar.png">' : '');
										h.push(mail_message_class == 'IPM.Note.SMIME' ? '<img src="images/list_icon_encryption.png">' : '');
										h.push(mail_message_class == 'IPM.Note.Microsoft.Voicemail' ? '<img height="15" src="images/email_icon_voice_mail_attachment_message.png">' : '');
										h.push(mail_message_class == 'IPM.Note.SMIME.MultipartSigned' ? '<img src="images/list_icon_sign.png">' : '');

										h.push(mail_status == 1 ? '<img src="images/list_icon_complete.png">' : '');
										h.push(mail_status == 2 ? '<img src="images/list_icon_flag.png">' : '');
									h.push('</div>');
								h.push('</td>');
							h.push('</tr>');
							h.push('<tr>');
								h.push('<td>');
									h.push('<small>');
										h.push('<span id="' + mail_server_id + '_date">');
											h.push(date);
										h.push('</span>');
									h.push('</small>');
								h.push('</td>');
							h.push('</tr>');
						h.push('</table>');
					h.push('</td>');
				h.push('</tr>');
			h.push('</table>');

			count = count + 1;
			}

		document.getElementById(id_search_result).innerHTML = '<form style="height: 100%; width: 100%;">' + h.join('') + '</form>';

		handle_link({ cmd : "ProgressStop" });
		}

	ajax({ type : "GET", url : "index.php?Cmd=Data&CollectionId=" + state.collection_id, success : onload});

	handle_link({ cmd : "ProgressStart" });
	}

function list_notes(id_search_result)
	{
	onload = function(data)
		{
		var r = JSON.parse(data);
		var c = 0;

		var h = new Array();

		if(r.length == 0)
			{
			h.push('<table style="height: 100%; width: 100%;">');
				h.push('<tr>');
					h.push('<td style=\"text-align: center;\">');
						h.push('keine Notizen');
					h.push('</td>');
				h.push('</tr>');
			h.push('</table>');
			}

		if(r.length != 0)
			{
			h.push('<div class="notes_row_title">');
				h.push('<div class="notes_title_text">');
					h.push('Titel');
				h.push('</div>');
			h.push('</div>');

			for(i = 0; i < r.length; i = i + 1)
				{
				var subject		= r[i][0];
				var server_id		= r[i][1];
				var last_modified_date	= r[i][2] * 1000;

				var date = new Date();

				date.setTime(last_modified_date);

				h.push('<div onmousedown="popup_notes_menu(event, this, \'' + server_id + '\');" onmouseover="this.className = \'notes_row_data list_hover\';" onmouseout="this.className = \'notes_row_data ' + ["list_odd", "list_even"][c % 2] + '\';" class="notes_row_data ' + ["list_odd", "list_even"][c % 2] + '">');
					h.push('<div class="notes_data_subject">');
						h.push(subject);
					h.push('</div>');
					h.push('<div class="notes_data_date">');
						h.push(date);
					h.push('</div>');
				h.push('</div>');

				c = c + 1;
				}
			}

		document.getElementById(id_search_result).innerHTML = h.join('');

		handle_link({ cmd : "ProgressStop" });
		}

	ajax({ type : "GET", url : "index.php?Cmd=Data&CollectionId=" + state.collection_id, success : onload});

	handle_link({ cmd : "ProgressStart" });
	}

function list_tasks(id_search_result)
	{
	onload = function(data)
		{
		var r = JSON.parse(data);
		var c = 0;

		var h = new Array();

		if(r.length == 0)
			{
			h.push('<table style="height: 100%; width: 100%;">');
				h.push('<tr>');
					h.push('<td style=\"text-align: center;\">');
						h.push('keine Aufgaben');
					h.push('</td>');
				h.push('</tr>');
			h.push('</table>');
			}

		if(r.length != 0)
			{
			h.push('<table style="width: 100%">');

			for(i = 0; i < r.length; i = i + 1)
				{
				var server_id	= r[i][2];
				var subject	= r[i][3];

				h.push('<tr onmousedown="popup_calendar_menu(event, null, null, \'' + server_id + '\');" onmouseover="this.className = \'list_small list_hover\';" onmouseout="this.className = \'list_small ' + ["list_odd", "list_even"][c % 2] + '\';" class="list_small ' + ["list_odd", "list_even"][c % 2] + '">');
					h.push('<td style="cursor: default;">' + (subject ? subject : '(Kein Titel)') + '</td>');
				h.push('</tr>');

				c = c + 1;
				}

			h.push('</table>');
			}

		document.getElementById(id_search_result).innerHTML = h.join('');

		handle_link({ cmd : "ProgressStop" });
		}

	ajax({ type : "GET", url : "index.php?Cmd=Data&CollectionId=" + state.collection_id, success : onload });

	handle_link({ cmd : "ProgressStart" });
	}

