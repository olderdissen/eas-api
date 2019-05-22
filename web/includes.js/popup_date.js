function popup_date(settings)
	{
	////////////////////////////////////////////////////////////////////////////////
	// check if values have to be replaced
	////////////////////////////////////////////////////////////////////////////////

	for(item in date_picker)
		date_picker[item] = (settings[item] != null ? settings[item] : date_picker[item]);

	////////////////////////////////////////////////////////////////////////////////

	if(settings.cmd == "")
		{
		}
	else if(settings.cmd == "date_calculate")
		{
		// get formfields
		var dd = date_picker.value.substr(0, 2) * 1;
		var mm = date_picker.value.substr(3, 2) * 1;
		var yy = date_picker.value.substr(6, 4) * 1;

		if(date_picker.time == true)
			{
			var hh = date_picker.value.substr(11, 2) * 1;
			var ii = date_picker.value.substr(14, 2) * 1;
			}

		if(date_picker.time == false)
			{
			var hh = 0;
			var ii = 0;
			}

		// check for minute update
		ii = ii + (date_picker.field == "ii" ? date_picker.direction : 0);

		// check for hour update
		hh = hh + (date_picker.field == "hh" ? date_picker.direction : 0);

		// check for year update
		yy = yy + (date_picker.field == "yy" ? date_picker.direction : 0);

		// check for month update
		mm = mm + (date_picker.field == "mm" ? date_picker.direction : 0);

		// check min/max value for minute
		ii = min_max(ii, 0, 59);

		// check min/max value for hour
		hh = min_max(hh, 0, 23);

		// fix min/max value for month
		mm = min_max(mm, 1, 12);

		// check min/max value
		days = [31, (yy % 4 != 0 || yy % 100 == 0 && yy % 400 != 0 ? 28 : 29), 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];

		// fix min/max value for day of leapyear or transform from string value to numeric value
		dd = (dd > days[mm - 1] ? days[mm - 1] : dd);

		// check for day update
		dd = dd + (date_picker.field == "dd" ? date_picker.direction : 0);

		// fix min/max value for day
		dd = min_max(dd, 1, days[mm - 1]);

		if(date_picker.time == true)
			date_picker.value = pre_null(dd) + '.' + pre_null(mm) + '.' + yy + ' ' + pre_null(hh) + ':' + pre_null(ii);

		if(date_picker.time == false)
			date_picker.value = pre_null(dd) + '.' + pre_null(mm) + '.' + yy;

		// set form fields
		popup_date({ cmd : "date_value_update" });
		}
	else if(settings.cmd == "date_cancel")
		handle_link({ cmd : "PopupRemove" });
	else if(settings.cmd == "date_minus")
		popup_date({ cmd : "date_calculate", direction : (0 - 1) });
	else if(settings.cmd == "date_ok")
		{
		if(date_picker.time == true)
			date_picker.target.value = date_picker.value.substr(0, 16);

		if(date_picker.time == false)
			date_picker.target.value = date_picker.value.substr(0, 10);

		handle_link({ cmd : "PopupRemove" });
		}
	else if(settings.cmd == "date_plus")
		popup_date({ cmd : "date_calculate", direction : (0 + 1) });
	else if(settings.cmd == "date_value_init")
		{
		if(oof_is_time(date_picker.target.value) == false)
			{
			var aa = new Date();

			var dd = aa.getDate();
			var mm = aa.getMonth();
			var yy = aa.getFullYear();
			var hh = aa.getHours();
			var ii = aa.getMinutes();

			if(date_picker.time == true)
				date_picker.value = pre_null(dd) + '.' + pre_null(mm + 1) + '.' + yy + ' ' + pre_null(hh) + ':' + pre_null(ii);

			if(date_picker.time == false)
				date_picker.value = pre_null(dd) + '.' + pre_null(mm + 1) + '.' + yy;
			}
		else
			date_picker.value = date_picker.target.value;
		}
	else if(settings.cmd == "date_value_update")
		{
		var weekdays = ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'];
		var months = ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];

		var dd = date_picker.value.substr(0, 2) * 1;
		var mm = date_picker.value.substr(3, 2) * 1;
		var yy = date_picker.value.substr(6, 4) * 1;

		if(date_picker.time == true)
			{
			var hh = date_picker.value.substr(11, 2) * 1;
			var ii = date_picker.value.substr(14, 2) * 1;
			}

		if(date_picker.time == false)
			{
			var hh = 0;
			var ii = 0;
			}

		document.getElementById('dd').innerHTML = pre_null(dd);
		document.getElementById('mm').innerHTML = months[mm - 1];
		document.getElementById('yy').innerHTML = yy;

		if(date_picker.time == true)
			{
			document.getElementById('hh').innerHTML = pre_null(hh);
			document.getElementById('ii').innerHTML = pre_null(ii);
			}

		if(date_picker.time == false)
			{
			}

		////////////////////////////////////////////////////////////////////////
		// update title
		////////////////////////////////////////////////////////////////////////

		var aa = new Date(yy, mm - 1, dd, hh, ii);

		var ww = aa.getDay();
		var dd = aa.getDate();
		var mm = aa.getMonth();
		var yy = aa.getFullYear();
		var hh = aa.getHours();
		var ii = aa.getMinutes();

		var o = document.getElementById("popup_title");

		if(date_picker.time == true)
			o.innerHTML = weekdays[ww] + '., ' + pre_null(dd) + '. ' + months[mm] + ' ' + yy + ' ' + pre_null(hh) + ':' + pre_null(ii);

		if(date_picker.time == false)
			o.innerHTML = weekdays[ww] + '., ' + pre_null(dd) + '. ' + months[mm] + ' ' + yy;
		}
	else if(settings.cmd == "date_wheel_init")
		{
		if(date_picker.time == true)
			var f = ["dd", "mm", "yy", "hh", "ii"]

		if(date_picker.time == false)
			var f = ["dd", "mm", "yy"]

		////////////////////////////////////////////////////////////////////////////////

		var m = (/Firefox/i.test(navigator.userAgent)) ? "DOMMouseScroll" : "mousewheel";

		for(i = 0; i < f.length; i = i + 1)
			{
			var o = document.getElementById(f[i])

			if(o.attachEvent)
				o.attachEvent("on" + m, function(e) { popup_date({ cmd : "date_wheel_scroll", wheel : e }); });
			else if(o.addEventListener)
				o.addEventListener(m, function(e) { popup_date({ cmd : "date_wheel_scroll", wheel : e }); }, false);
			}
		}
	else if(settings.cmd == "date_wheel_scroll")
		{
		var evt = (window.event || date_picker.wheel);
		var delta = (evt.detail ? evt.detail * (0 - 120) : evt.wheelDelta);

		popup_date({ cmd : (delta <= 0 - 120 ? "date_minus" : "date_plus"), field : evt.target.id });

		if(evt.preventDefault)
			{
			evt.preventDefault();
			}
		}
	else if(settings.cmd == "init")
		{
		if(date_picker.time == true)
			{
			var l = [10, 40, 80, 140, 170];
			var w = [20, 30, 40, 20, 20];
			var f = ['dd', 'mm', 'yy', 'hh', 'ii']
			}

		if(date_picker.time == false)
			{
			var l = [10, 65, 130];
			var w = [45, 55, 60];
			var f = ['dd', 'mm', 'yy']
			}

		////////////////////////////////////////////////////////////////////////
		// create popup
		////////////////////////////////////////////////////////////////////////

		var data_h = new Array()

		data_h.push('<div id="popup_dialog" class="popup_dialog" style="width: 206px;">');
			data_h.push('<div class="popup_title" id="popup_title" onmousedown="evt_drag_start(this.parentNode);">');
				data_h.push('???');
			data_h.push('</div>');
			data_h.push('<div class="popup_text" id="popup_text">');
				data_h.push('<div class="popup_buttons" id="popup_buttons">');
					for(i = 0; i < f.length; i = i + 1)
						{
						data_h.push('<div class="popup_button popup_border_a" onmouseover="popup_change_style_button(this, 2);" onmouseout="popup_change_style_button(this, 3);" onmousedown="popup_change_style_button(this, 1);" onmouseup="popup_change_style_button(this, 0);" onclick="popup_date({ cmd : \'date_plus\', field : \'' + f[i] + '\' });" style="left: ' + l[i] + 'px; width: ' + w[i] + 'px;">');
							data_h.push('+');
						data_h.push('</div>');
						}
				data_h.push('</div>');
				data_h.push('<div class="popup_buttons" id="popup_buttons">');
					for(i = 0; i < f.length; i = i + 1)
						{
						data_h.push('<div class="popup_input popup_border_b" style="text-align: center; left: ' + l[i] + 'px; width: ' + w[i] + 'px;" id="' + f[i] + '">');
							data_h.push('???');
						data_h.push('</div>');
						}
				data_h.push('</div>');
				data_h.push('<div class="popup_buttons" id="popup_buttons">');
					for(i = 0; i < f.length; i = i + 1)
						{
						data_h.push('<div class="popup_button popup_border_a" onmouseover="popup_change_style_button(this, 2);" onmouseout="popup_change_style_button(this, 3);" onmousedown="popup_change_style_button(this, 1);" onmouseup="popup_change_style_button(this, 0);" onclick="popup_date({ cmd : \'date_minus\', field : \'' + f[i] + '\' });" style="left: ' + l[i] + 'px; width: ' + w[i] + 'px;">');
							data_h.push('-');
						data_h.push('</div>');
						}
				data_h.push('</div>');
			data_h.push('</div>');
			data_h.push('<div class="popup_buttons" id="popup_buttons">');
				data_h.push('<div class="popup_button popup_border_a" onmouseover="popup_change_style_button(this, 2);" onmouseout="popup_change_style_button(this, 3);" onmousedown="popup_change_style_button(this, 1);" onmouseup="popup_change_style_button(this, 0);" onclick="popup_date({ cmd : \'date_ok\' });" style="left: 12px;">');
					data_h.push('Einstellen');
				data_h.push('</div>');
				data_h.push('<div class="popup_button popup_border_a" onmouseover="popup_change_style_button(this, 2);" onmouseout="popup_change_style_button(this, 3);" onmousedown="popup_change_style_button(this, 1);" onmouseup="popup_change_style_button(this, 0);" onclick="popup_date({ cmd : \'date_cancel\' });" style="left: 108px;">');
					data_h.push('Abbrechen');
				data_h.push('</div>');
			data_h.push('</div>');
		data_h.push('</div>');

		////////////////////////////////////////////////////////////////////////
		// remove existing blocking layer
		////////////////////////////////////////////////////////////////////////

		handle_link({ cmd : "PopupRemove" });
		handle_link({ cmd : "PopupCreate", data : data_h });
		handle_link({ cmd : "PopupPositionCenter" });

		date_picker.target.autocomplete = "off";

		popup_date({ cmd : "date_value_init" });
		popup_date({ cmd : "date_value_update" });
		popup_date({ cmd : "date_wheel_init" });
		}
	else
		{
		alert("unknown command: " + settings.cmd);
		}
	}

