function suggest_handle(settings)
	{
	for(item in suggest)
		{
		suggest[item] = (settings[item] == null ? suggest[item] : settings[item]);
		}

	////////////////////////////////////////////////////////////////////////////////

	if(suggest.action == "")
		{
		}
	else if(suggest.action == "options_add")
		{
		}
	else
		{
		alert("unknown command: " + suggest.action);
		}
	}

function suggest_register(src_id, collection_id, multi)
	{
	var onblur = function(e)
		{
		// onblur of input will occure before onmousedown/onclick of dropdown
		// delay onblur of input to have enough time to catch the onmousedown/onclick of dropdown

		t = window.setTimeout(suggest_terminate, 500);
		}

	////////////////////////////////////////////////////////////////////////

	var onkeydown = function(e)
		{
		suggest_register_keycode_keydown(e, p, suggest_search, suggest_select, suggest_mouse, suggest_hide);

		if(src.tagName == "TEXTAREA")
			{
			src.style.height = '0px';
			src.style.height = src.scrollHeight + 'px';
			}
		}

	////////////////////////////////////////////////////////////////////////

	var onkeypress = function(e)
		{
		suggest_register_keycode_keypress(e, p, suggest_search, suggest_select, suggest_mouse, suggest_hide);

		if(src.tagName == "TEXTAREA")
			{
			src.style.height = '0px';
			src.style.height = src.scrollHeight + 'px';
			}
		}

	////////////////////////////////////////////////////////////////////////

	var onload = function(data)
		{
		var r = JSON.parse(data);

		var h = new Array();

		for(i = 0; i < r.length; i = i + 1)
			{
			h.push('<div onmouseover="suggest_mouse(' + i + ');" onclick="suggest_select(' + i + ');" class="suggest_link_a" style="border-top-color: #C0C0C0; border-top-style: solid; border-top-width: 1px;">');

				// element with real value for input field
				h.push('<div style="display: none;">');
					h.push(r[i][0]);
				h.push('</div>');

				// element with colorized value for dropdown field
				h.push('<div style="display: block;">');
					h.push(r[i][1]);
				h.push('</div>');

			h.push('</div>');
			}

		d.innerHTML = h.join('');

		d.style.display = (r.length > 0 ? 'block' : 'none');
		}

	////////////////////////////////////////////////////////////////////////

	suggest_hide = function()
		{
		d.style.display = 'none';
		}

	suggest_mouse = function(m)
		{
		var m_min = 0;
		var m_max = d.childNodes.length - 1;

		m = (m < m_min ? m_min : m);
		m = (m > m_max ? m_max : m);

		////////////////////////////////////////////////////////////////////////////////

		for(i = 0; i < d.childNodes.length; i = i + 1)
			{
			d.childNodes[i].className = (i == m ? 'suggest_link_b' : 'suggest_link_a');

			var o = d.childNodes[i].childNodes[1].childNodes;

			for(w = 0; w < o.length; w = w + 1)
				{
				if(o[w].tagName != "SPAN")
					{
					continue;
					}

				o[w].className = (i == m ? 'suggest_found_b' : 'suggest_found_a');
				}
			}

		////////////////////////////////////////////////////////////////////////////////

		if(m < p) // up
			{
			d.scrollTop = (m * 20 < d.scrollTop ? (m * 20) : d.scrollTop);
			}

		if(m > p) // down
			{
			d.scrollTop = (m * 20 > d.scrollTop + 100 ? (m * 20) - 100 : d.scrollTop);
			}

		////////////////////////////////////////////////////////////////////////////////

		p = m;
		}

	suggest_search = function()
		{
		console.log("suggest search");

		clearTimeout(t);

		t = window.setTimeout(suggest_search_a, 500); // if user deletes chars, delay our search to prevent overload of server
		}

	suggest_search_a = function()
		{
		console.log("suggest search a");

		if(src.value.length < 1)
			{
			suggest_hide();
			}
		else
			{
			var xv = src.value.trim();
			var xp = xv.lastIndexOf(',');

			var xa = (multi == 1 ? (xp < 0 ? xv : xv.substr(xp + 1).trim()) : xv);

			ajax({ type : "GET", url : "index.php?Cmd=Search&CollectionId=" + collection_id + "&Field=" + src.name + "&Search=" + encodeURIComponent(xa), success : onload });
			}

		p = 0 - 1;
		}

	suggest_select = function(m)
		{
		var xv = src.value.trim();
		var xp = xv.lastIndexOf(',');

		var xc = d.childNodes[m].childNodes[0].innerHTML;

		xc = xc.replace(/&amp;/, '&');
		xc = xc.replace(/&quot;/, '"');
		xc = xc.replace(/&lt;/, '<');
		xc = xc.replace(/&gt;/, '>');

		src.value = (multi == 1 ? (xp < 0 ? '' : xv.substr(0, xp).trim() + ', ') + xc : xc);

		src.focus();

		suggest_hide();

		p = 0 - 1;
		}

	suggest_terminate = function()
		{
		d.parentNode.removeChild(d);
		}

	////////////////////////////////////////////////////////////////////////

	var src = document.getElementById(src_id);

	src.autocomplete = 'off';
	src.onkeydown = onkeydown;
//	src.onkeypress = onkeypress;
	src.onblur = onblur;

	////////////////////////////////////////////////////////////////////////

	var d = document.createElement('div');

	d.className = 'suggest_dropdown';
	d.style.width = (src.offsetWidth - 2) + 'px';

	src.parentNode.insertBefore(d, src.nextSibling);

	////////////////////////////////////////////////////////////////////////

	var p = 0 - 1;

	////////////////////////////////////////////////////////////////////////

	var t = null;

	////////////////////////////////////////////////////////////////////////

	return(true);
	}

function suggest_register_keycode_keypress(e, dropdown_position, callback_search, callback_select, callback_mouse, callback_hide)
	{
	if(! e) { e = window.event; }

	////////////////////////////////////////////////////////////////////////////////

	var c = (e.charCode ? e.charCode : null);
	var k = (e.keyCode ? e.keyCode : null);
	var w = (e.which ? e.which : null);

	console.log("keypress");
	console.log("c: " + c + "; k: " + k + "; w: " + w);

	////////////////////////////////////////////////////////////////////////////////

	if(c == null) { }
	else if((c >= 32) && (c <= 127)) { callback_search(); } // ...
	else if(c == 167) { callback_search(); } // §
	else if(c == 180) { callback_search(); } // ´
	else if(c == 196) { callback_search(); } // Ä
	else if(c == 214) { callback_search(); } // Ö
	else if(c == 220) { callback_search(); } // Ü
	else if(c == 223) { callback_search(); } // ß
	else if(c == 228) { callback_search(); } // ä
	else if(c == 246) { callback_search(); } // ö
	else if(c == 252) { callback_search(); } // ü

	////////////////////////////////////////////////////////////////////////////////

	if(k == null) { }
	else if(k == 8) { callback_search(); }				// backspace
	else if(k == 9) { }						// tab
	else if(k == 12) { }						// shift + num 5
	else if(k == 13) { callback_select(dropdown_position); }	// enter
	else if(k == 19) { }						// pause
	else if(k == 27) { callback_hide(); }				// esc
	else if(k == 33) { callback_mouse(dropdown_position - 5); }	// page up
	else if(k == 34) { callback_mouse(dropdown_position + 5); }	// page down
	else if(k == 35) { callback_mouse(255); }			// end
	else if(k == 36) { callback_mouse(0); }				// pos 1
	else if(k == 37) { }						// left
	else if(k == 38) { callback_mouse(dropdown_position - 1); }	// up
	else if(k == 39) { }						// right
	else if(k == 40) { callback_mouse(dropdown_position + 1); }	// down
	else if(k == 46) { callback_search(); }				// del

	////////////////////////////////////////////////////////////////////////////////

	return(true);
	}

function suggest_register_keycode_keydown(e, dropdown_position, callback_search, callback_select, callback_mouse, callback_hide)
	{
	if(! e) { e = window.event; }

	////////////////////////////////////////////////////////////////////////////////

	var c = (e.charCode ? e.charCode : null);
	var k = (e.keyCode ? e.keyCode : null);
	var w = (e.which ? e.which : null);

	console.log("keydown");
	console.log("c: " + c + "; k: " + k + "; w: " + w);

	////////////////////////////////////////////////////////////////////////////////

	if(c == 0) { }
//	else { console.log("charCode: " + c); }

	////////////////////////////////////////////////////////////////////////////////

	if(k == 0) { }
	else if(k == 8) { callback_search(); }				// backspace
	else if(k == 13) { callback_select(dropdown_position); }	// enter
	else if(k == 27) { callback_hide(); }				// esc
	else if(k == 33) { callback_mouse(dropdown_position - 5); }	// page up
	else if(k == 34) { callback_mouse(dropdown_position + 5); }	// page down
	else if(k == 38) { callback_mouse(dropdown_position - 1); }	// up
	else if(k == 40) { callback_mouse(dropdown_position + 1); }	// down
	else if(k == 46) { callback_search(); }				// delete
//	else { console.log("keyCode: " + k); }

	////////////////////////////////////////////////////////////////////////////////

	if(w == 0) { }
	else if(w == 8) { }					// backspace
	else if(w == 13) { }					// enter
	else if(w == 32) { callback_search(); }			// space
	else if(w == 33) { callback_search(); }			// !
	else if(w == 34) { callback_search(); }			// "
	else if(w == 35) { callback_search(); }			// #
	else if(w == 36) { callback_search(); }			// $
	else if(w == 37) { callback_search(); }			// %
	else if(w == 38) { callback_search(); }			// &
	else if(w == 39) { callback_search(); }			// '
	else if(w == 40) { callback_search(); }			// (
	else if(w == 41) { callback_search(); }			// )
	else if(w == 42) { callback_search(); }			// *
	else if(w == 43) { callback_search(); }			// +
	else if(w == 44) { callback_search(); }			// ,
	else if(w == 45) { callback_search(); }			// -
	else if(w == 46) { callback_search(); }			// .
	else if(w == 47) { callback_search(); }			// /
	else if((w >= 48) && (w <= 57)) { callback_search(); }	// 0 .. 9
	else if(w == 58) { callback_search(); }			// :
	else if(w == 59) { callback_search(); }			// ;
	else if(w == 60) { callback_search(); }			// <
	else if(w == 61) { callback_search(); }			// =
	else if(w == 62) { callback_search(); }			// >
	else if(w == 63) { callback_search(); }			// ?
	else if(w == 64) { callback_search(); }			// @
	else if((w >= 65) && (w <= 90)) { callback_search(); }	// A .. Z
	else if(w == 91) { callback_search(); }			// [
	else if(w == 92) { callback_search(); }			// \
	else if(w == 93) { callback_search(); }			// ]
	else if(w == 94) { callback_search(); }			// ^
	else if(w == 95) { callback_search(); }			// _
	else if(w == 96) { callback_search(); }			// `
	else if((w >= 97) && (w <= 122)) { callback_search(); }	// a .. z
	else if(w == 123) { callback_search(); }		// {
	else if(w == 124) { callback_search(); }		// |
	else if(w == 125) { callback_search(); }		// }
	else if(w == 167) { callback_search(); }		// °
	else if(w == 172) { callback_search(); }		// ¬
	else if(w == 176) { callback_search(); }		// °
	else if(w == 178) { callback_search(); }		// ²
	else if(w == 179) { callback_search(); }		// ²
	else if(w == 180) { callback_search(); }		// ´
	else if(w == 181) { callback_search(); }		// µ
	else if(w == 184) { callback_search(); }		// ¸
	else if(w == 185) { callback_search(); }		// ¹
	else if(w == 188) { callback_search(); }		// ¼
	else if(w == 189) { callback_search(); }		// ½
	else if(w == 196) { callback_search(); }		// ä
	else if(w == 214) { callback_search(); }		// Ö
	else if(w == 220) { callback_search(); }		// Ü
	else if(w == 223) { callback_search(); }		// ß
	else if(w == 228) { callback_search(); }		// ä
	else if(w == 246) { callback_search(); }		// ö
	else if(w == 252) { callback_search(); }		// ö
//	else { console.log("wich: " + w); }

	////////////////////////////////////////////////////////////////////////////////

	return(true);
	}

