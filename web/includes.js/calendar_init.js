function calendar_init(expression)
	{
	calendar.scroll	= 0;
	calendar.time	= (calendar.time == "" ?  new Date().getTime() / 1000 : calendar.time);
	calendar.target	= expression;
	calendar.style	= "x"; // must init with invalid value, other values are (a)genda, (d)ay, (w)eey, (m)onth

	handle_link({ cmd : "CalendarSelect" });

	window.onresize = function() { handle_link({ cmd : "CalendarResize" }); };
	}

