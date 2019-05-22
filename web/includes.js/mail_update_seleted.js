function mail_update_seleted(object)
	{
	var o = document.forms[0];

	if(object.name == "")
		{
		for(i = 0; i < o.length; i = i + 1)
			{
			if(o[i].name == "")
				continue;

			if(o[i].id != object.id)
				continue;

			o[i].checked = object.checked;
			}
		}

	if(object.name != "")
		{
		var t = 0; // number of elements - total
		var c = 0; // number of elements - checked

		for(i = 0; i < o.length; i = i + 1)
			{
			if(o[i].name == "")
				continue;

			if(o[i].id != object.id)
				continue;

			t = t + 1;
			c = c + (o[i].checked ? 1 : 0);
			}

		document.getElementById(object.id).checked = (t == c);
		}

	var c = 0; // number of elements - checked

	for(i = 0; i < o.length; i = i + 1)
		{
		if(o[i].name == "")
			continue;

		c = c + (o[i].checked ? 1 : 0);
		}

	document.getElementById("delete_selected").style.display = (c == 0 ? 'none' : '');
	}

