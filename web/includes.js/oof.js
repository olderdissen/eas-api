function oof_check()
	{
	var o = document.forms[0];

	if(o.F1.checked == true)
		{
		if(o.F2.checked == true)
			{
			if(oof_is_time(o.StartTime.value) == false)
				{
				handle_link({ cmd : "PopupInfo", title : "Ungültige Einstellung", text: "Ungültiges Datum", ok_cmd : "Nothing" });

				return(false);
				}

			if(oof_is_time(o.EndTime.value) == false)
				{
				handle_link({ cmd : "PopupInfo", title : "Ungültige Einstellung", text: "Ungültiges Datum", ok_cmd : "Nothing" });

				return(false);
				}

			var a = oof_string_to_time(o.StartTime.value);
			var b = oof_string_to_time(o.EndTime.value);

			if(b < a)
				{
				handle_link({ cmd : "PopupInfo", title : "Ungültige Einstellung", text: "Ungültiges Datum", ok_cmd : "Nothing" });

				return(false);
				}
			}

		if(o.F5.value == "")
			{
			handle_link({ cmd : "PopupInfo", title : "Ungültige Einstellung", text: "Ungültiges Einstellung für interne Nachrichten", ok_cmd : "Nothing" });

			return(false);
			}

		if(o.F6.checked == true)
			{
			if(o.F8.value == "")
				{
				handle_link({ cmd : "PopupInfo", title : "Ungültige Einstellung", text: "Ungültiges Einstellung für externe Nachrichten", ok_cmd : "Nothing" });

				return(false);
				}
			}
		}

	return(true);
	}

function oof_is_time(t)
	{
	if((/^\d{2}\.\d{2}.\d{4}\s\d{2}:\d{2}:\d{2}$/).test(t) === true)
		return(true);

	if((/^\d{2}\.\d{2}.\d{4}\s\d{2}:\d{2}$/).test(t) === true)
		return(true);

	if((/^\d{2}\.\d{2}.\d{4}$/).test(t) === true)
		return(true);

	return(false);
	}

function oof_string_to_time(t)
	{
	var p = { 0 : 2, 3 : 2, 6 : 4, 11 : 2, 14 : 2, 17 : 2 };

	for(i in p)
		p[i] = (i < t.length ? t.substr(i, p[i]) : 0);

	var o = new Date(p[6], p[3], p[0], p[11], p[14], p[17]).getTime();

	return(o);
	}

