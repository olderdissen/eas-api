function rte_start(rtePreloadContent)
	{
	var o = document.getElementById(rte_form_name);

	o.value = rtePreloadContent;

	////////////////////////////////////////////////////////////////////////////////

	var o = document.getElementById(rte_name);

	o.contentWindow.document.designMode = "on";

	o.contentWindow.document.open();
	o.contentWindow.document.write('<html><head><style type="text/css">body { font-family: verdana; font-size: 12px; }</style></head><body>' + rtePreloadContent + '</body></html>');
	o.contentWindow.document.close();

	////////////////////////////////////////////////////////////////////////////////

	var o = document.getElementById("preview_" + rte_name);

	o.contentWindow.document.open();
	o.contentWindow.document.write('<html><head><style type="text/css">body { font-family: verdana; font-size: 12px; } </style></head><body>' + rtePreloadContent + '</body></html>');
	o.contentWindow.document.close();

	////////////////////////////////////////////////////////////////////////////////

	rte_events();
	rte_selection();
	rte_counter();
	}

