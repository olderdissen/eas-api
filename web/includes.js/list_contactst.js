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

