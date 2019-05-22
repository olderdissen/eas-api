function evt_add(obj, type, fn)
	{
	if(obj.addEventListener)
		obj.addEventListener(type, fn, false);
	else if(obj.attachEvent)
		{
		obj["e" + type + fn] = fn;

		obj[type + fn] = function()
			{
			obj["e" + type + fn](window.event);
			}

		obj.attachEvent("on" + type, obj[type + fn]);
		}
	}

function evt_context()
	{
	window.onclick = function(e)
		{
		if(e.target.tagName == "INPUT")
			e.target.autocomplete = false;
		}

	window.oncontextmenu = function(e)
		{
		if(e.target.tagName == "INPUT")
			return(true);

		if(e.target.tagName == "TEXTAREA")
			return(true);

//		if(e.target.name == "Data")
//			return(true);

		return(false);
		}
	}

function evt_drag_init()
	{
	document.onmousemove = function(e)
		{
		evt.mx = document.all ? window.event.clientX : e.pageX;
		evt.my = document.all ? window.event.clientY : e.pageY;

		if(evt.oz != null)
			{
			evt.oz.style.left = (evt.mx - evt.ox) + "px";
			evt.oz.style.top = (evt.my - evt.oy) + "px";
			}
		}

	////////////////////////////////////////////////////////////////////////////////

	document.onmouseup = function(e)
		{
		evt.oz = null;
		}

	////////////////////////////////////////////////////////////////////////////////

	window.onkeydown = function(e)
		{
		if(! e)
			e = window.event;

		////////////////////////////////////////////////////////////////////////////////

		var c = (e.charCode ? e.charCode : null);
		var w = (e.which ? e.which : null);
		var k = (e.keyCode ? e.keyCode : null);

//		console.log("c: " + c + "; k: " + k + "; w: " + w);

		////////////////////////////////////////////////////////////////////////////////

		if(k == 27)
			{
			handle_link({ cmd : "PopupRemove" });
			}
		}
	}

function evt_drag_start(obj)
	{
	evt.oz = obj;

	evt.ox = evt.mx - evt.oz.offsetLeft;
	evt.oy = evt.my - evt.oz.offsetTop;
	}

function evt_remove(obj, type, fn)
	{
	if(obj.removeEventListener)
		obj.removeEventListener(type, fn, false);
	else if(obj.detachEvent)
		{
		obj.detachEvent("on" + type, obj[type + fn]);
		obj[type + fn] = null;
		obj["e" + type + fn] = null;
		}
	}

function evt_touch_cancel(e)
	{
	}

function evt_touch_check()
	{
	if(navigator.userAgent.match(/android 3/i) || navigator.userAgent.match(/honeycomb/i))
		return(false);

	try
		{
		document.createEvent("TouchEvent");

		return(true);
		}

	catch(e)
		{
		return(false);
		}
	}

function evt_touch_end(e)
	{
	}

function evt_touch_init(id)
	{
	if(evt_touch_check() == true)
		{
		var o = document.getElementById(id);

		o.addEventListener("touchcancel", evt_touch_cancel, false);
		o.addEventListener("touchend", evt_touch_end, false);
		o.addEventListener("touchmove", evt_touch_move, false);
		o.addEventListener("touchstart", evt_touch_start, false);
		}
	}

function evt_touch_move(e)
	{
	if((this.scrollLeft < this.scrollWidth - this.offsetWidth && this.scrollLeft + e.touches[0].pageX < evt_touch.x - 5) || (this.scrollLeft != 0 && this.scrollLeft + e.touches[0].pageX > evt_touch.x + 5))
		e.preventDefault();

	if((this.scrollTop < this.scrollHeight - this.offsetHeight && this.scrollTop + e.touches[0].pageY < evt_touch.y - 5) || (this.scrollTop != 0 && this.scrollTop + e.touches[0].pageY > evt_touch.y + 5))
		{
		e.preventDefault();
		}

	this.scrollTop = evt_touch.y - e.touches[0].pageY;
	this.scrollLeft = evt_touch.x - e.touches[0].pageX;
	}

function evt_touch_start(e)
	{
	evt_touch.x = this.scrollLeft + e.touches[0].pageX;
	evt_touch.y = this.scrollTop + e.touches[0].pageY;
	}

