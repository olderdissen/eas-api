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

