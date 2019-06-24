function contact_scroll_to(letter_id, scroll_id)
	{
	clearTimeout(state.timer_id);

	var o = document.getElementById(scroll_id);
	var p = document.getElementById(letter_id);

	if(o == null)
		return;

	if(p == null)
		return;

//	contact_scroll_to_x(o, p, 100);
	o.scrollTop = p.offsetTop;
	}

function contact_scroll_to_x(o, p, d)
	{
	if(d < 0)
		return;

	o.scrollTop = o.scrollTop + ((p.offsetTop - o.scrollTop) / d * 10);

	if(o.scrollTop == p.offsetTop)
		return;

	state.timer_id = setTimeout(function(){contact_scroll_to_x(o, p, d - 10);}, 10);
	}

