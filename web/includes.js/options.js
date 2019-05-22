function options_add(dst_id, src_id)
	{
	var src_obj = document.getElementById(src_id);
	var dst_obj = document.getElementById(dst_id);

	if(options_find(dst_id, src_obj.value) < 0)
		dst_obj[dst_obj.options.length] = new Option(src_obj.value, src_obj.value, false, false);

	////////////////////////////////////////////////////////////////////////////////
	// sort items by name
	////////////////////////////////////////////////////////////////////////////////

	options_sort(dst_id);

//	dst_obj.selectedIndex = options_find(dst_id, src_obj.value);

	src_obj.value = "";
	}

function options_find(dst_id, expression)
	{
	var dst_obj = document.getElementById(dst_id);

	for(i = 0; i < dst_obj.options.length; i = i + 1)
		{
		if(dst_obj.options[i].value != expression)
			continue;

		return(i);
		}

	return(0 - 1);
	}

function options_sort(id)
	{
	var o = document.getElementById(id);

	var a = new Array();

	for(i = 0; i < o.options.length; i = i + 1)
		a.push(o.options[i].value);

	a.sort();

	for(i = 0; i < o.options.length; i = i + 1)
		o[i] = new Option(a[i], a[i], false, false);
	}

