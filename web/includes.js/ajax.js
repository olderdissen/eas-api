function ajax(settings)
	{
	if((settings.xhr == null) && window.XMLHttpRequest)
		settings.xhr = new XMLHttpRequest();

	if((settings.xhr == null) && window.ActiveXObject)
		settings.xhr = new ActiveXObject("Microsoft.XMLHTTP");

	if(settings.xhr == null)
		return(false);

	var defaults = { async : true, cache : true, content_type : "application/x-www-form-urlencoded; charset=UTF-8", data : "", password : "", type : "GET", url : "", username : "" };

	for(var item in defaults)
		settings[item] = (settings[item] == null ? defaults[item] : settings[item]);

	settings.url = settings.url + (settings.type == "GET" ? (settings.data == "" ? "" : (settings.url.indexOf("?") < 0 ? "?" : "&") + settings.data) : "");

	settings.url = settings.url + (settings.cache == true ? "" : (settings.url.indexOf("?") < 0 ? "?" : "&") + "_=" + new Date().getTime());

	settings.xhr.onerror = function()
		{
		if(settings.error)
			settings.error(settings.xhr, settings.xhr.status, settings.xhr.statusText);
		}

	settings.xhr.onreadystatechange = function()
		{
		if(settings.xhr.readyState == 4)
			{
			for(var item in settings.status_code)
				if(settings.xhr.status == item)
					settings.status_code[item]();

			if((settings.xhr.status >= 200) && (settings.xhr.status <= 299) || (settings.xhr.status == 304))
				{
				if(settings.success)
					settings.success(settings.xhr.responseText, settings.xhr.status, settings.xhr);
				}
			else
				{
				if(settings.error)
					settings.error(settings.xhr, settings.xhr.status, settings.xhr.statusText);
				}

			if(settings.complete)
				settings.complete(settings.xhr, settings.xhr.status);
			}
		}

	settings.xhr.open(settings.type, settings.url, settings.async, settings.username, settings.password);

	if(settings.type != "GET")
		settings.xhr.setRequestHeader("Content-Type", settings.content_type);

	if(settings.headers)
		for(var item in settings.headers)
			settings.xhr.setRequestHeader(item, settings.headers[item]);

	if(settings.xhr_fields)
		for(var item in settings.xhr_fields)
			settings.xhr[item] = settings.xhr_fields[item];

	if(settings.timeout)
		settings.xhr.setTimeout = settings.timeout;

	if(settings.before_send)
		settings.before_send(settings.xhr, settings);

	settings.xhr.send(settings.type == "GET" ? "" : settings.data);

	return(true);
	}
