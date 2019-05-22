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

