function get_form_data()
	{
	var data = new Array();

	for(i = 0; i < document.forms[0].length; i = i + 1)
		{
		if(document.forms[0][i].name == "")
			continue;

		if(document.forms[0][i].type == "checkbox")
			if(document.forms[0][i].checked == true)
				data.push(document.forms[0][i].name + "=" + encodeURIComponent(document.forms[0][i].value));

		if(document.forms[0][i].type == "hidden")
			data.push(document.forms[0][i].name + "=" + encodeURIComponent(document.forms[0][i].value));

		if(document.forms[0][i].type == "radio")
			if(document.forms[0][i].checked == true)
				data.push(document.forms[0][i].name + "=" + encodeURIComponent(document.forms[0][i].value));

		if(document.forms[0][i].type == "select-multiple")
			for(x = 0; x < document.forms[0][i].length; x = x + 1)
				if(document.forms[0][i][x].selected == true)
					data.push(document.forms[0][i].name + "=" + encodeURIComponent(document.forms[0][i][x].value));

		if(document.forms[0][i].type == "select-one")
			data.push(document.forms[0][i].name + "=" + encodeURIComponent(document.forms[0][i].value));

		if(document.forms[0][i].type == "text")
			data.push(document.forms[0][i].name + "=" + encodeURIComponent(document.forms[0][i].value));

		if(document.forms[0][i].type == "textarea")
			data.push(document.forms[0][i].name + "=" + encodeURIComponent(document.forms[0][i].value));
		}

	return(data.join("&"));
	}
