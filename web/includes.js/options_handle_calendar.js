function options_handle_calendar(src_id, dst_id)
	{
	////////////////////////////////////////////////////////////////////////

	var onblur = function(e)
		{
		// onblur of input will occure before onmousedown/onclick of dropdown
		// delay onblur of input to have enough time to catch the onmousedown/onclick of dropdown

		t = window.setTimeout(suggest_terminate, 500);
		}

	var onkeypress = function(e)
		{
		suggest_register_keycode(e, p, suggest_search, suggest_select, suggest_mouse, suggest_hide);
		}

	////////////////////////////////////////////////////////////////////////

	var onload = function(data)
		{
		var r = JSON.parse(data);

		suggest_register_list(d, r);
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

		suggest_register_color(d, m);

		suggest_register_position(d, m, p);

		////////////////////////////////////////////////////////////////////////////////

		p = m;
		}

	suggest_search = function()
		{
		clearTimeout(t);

		t = window.setTimeout(suggest_search_a, 500); // if user deletes chars, delay our search to prevent overload of server
		}

	suggest_search_a = function()
		{
		if(src.value.length < 1)
			suggest_hide();
		else
			ajax({ type : "GET", url : "index.php?Cmd=Search&CollectionId=9009&Field=To&Search=" + src.value, success : onload});

		p = 0 - 1;
		}

	suggest_select = function(m)
		{
		if(src.value.length == 0)
			{
			}
		else if(m < 0)
			options_add(dst.id, src.id);
		else
			{
			var xc = d.childNodes[m].childNodes[0].innerHTML;

			xc = xc.replace(/&amp;/, '&');
			xc = xc.replace(/&quot;/, '"');
			xc = xc.replace(/&lt;/, '<');
			xc = xc.replace(/&gt;/, '>');

			src.value = xc;

			src.focus();

			suggest_hide();
			}

		p = 0 - 1;
		}

	suggest_terminate = function()
		{
		d.parentNode.removeChild(d);
		}

	////////////////////////////////////////////////////////////////////////

	var dst = document.getElementById(dst_id);

	////////////////////////////////////////////////////////////////////////

	var src = document.getElementById(src_id);

	src.autocomplete = 'off';
	src.onkeypress = onkeypress;
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

