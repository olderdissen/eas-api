function update_name_fields(expression)
	{
	var images = update_name_fields_images();

	////////////////////////////////////////////////////////////////////////////////

	if(expression == "init")
		{
		update_name_fields_hide();
		update_name_fields_add();
		}
	else if(expression.src.substr(0 - 21) == images[0][1].substr(0 - 21)) // minimized_focus 
		{
		update_name_fields_hide();
		update_name_fields_show();
		update_name_fields_del();

		document.getElementById("HideYomiLastName").childNodes[0].childNodes[0].childNodes[0].childNodes[3].innerHTML = update_name_fields_buttons(1);
		}
	else if(expression.src.substr(0 - 21) == images[1][1].substr(0 - 21)) // maximized_focus
		{
		update_name_fields_hide();
		update_name_fields_del();
		update_name_fields_add();
		}
	}

function update_name_fields_add()
	{
	var fields = ["YomiLastName", "YomiFirstName", "Suffix", "LastName"];

	for(i = 0; i < fields.length; i = i + 1)
		{
		document.getElementById("Hide" + fields[i]).childNodes[0].childNodes[0].childNodes[0].childNodes[3].innerHTML = update_name_fields_buttons(0);

		if(document.forms[0][fields[i]].value != "")
			break;
		}
	}

function update_name_fields_buttons(expression)
	{
	var images = update_name_fields_images();

	var button = new Array();

	button.push('img');
	button.push('src="' + images[expression][0] + '"');
	button.push('style="height: 16px; width: 16px;"');
	button.push('onclick="update_name_fields(this)"');
	button.push('onmouseout="this.src = \'' + images[expression][0] + '\';"');
	button.push('onmouseover="this.src = \'' + images[expression][1] + '\';"');

	button = "<" + button.join(" ") + ">";

	return(button);
	}

function update_name_fields_del()
	{
	var fields = ["YomiLastName", "YomiFirstName", "Suffix", "LastName"];

	for(i = 0; i < fields.length; i = i + 1)
		document.getElementById("Hide" + fields[i]).childNodes[0].childNodes[0].childNodes[0].childNodes[3].innerHTML = "";
	}

function update_name_fields_hide()
	{
	var fields = ["Title", "MiddleName", "Suffix", "YomiFirstName", "YomiLastName"];

	for(i = 0; i < fields.length; i = i + 1)
		{
		if(document.forms[0][fields[i]].value != "")
			continue;

		document.getElementById("Hide" + fields[i]).style.display = "none";
		}
	}

function update_name_fields_images()
	{
	var images_minimized = ["images/expander_ic_minimized.9.png", "images/expander_ic_minimized_focus.9.png"];
	var images_maximized = ["images/expander_ic_maximized.9.png", "images/expander_ic_maximized_focus.9.png"];

	var images = [images_minimized, images_maximized];

	return(images);
	}

function update_name_fields_show()
	{
	var fields = ["Title", "MiddleName", "Suffix", "YomiFirstName", "YomiLastName"];

	for(i = 0; i < fields.length; i = i + 1)
		document.getElementById("Hide" + fields[i]).style.display = "block";
	}

