function numbersonly(myfield, e, min, max)
	{
	if(! e)
		e = window.event;

	var w = 0;
	var k = 0;
	var c = 0;

	if(e.charCode)
		c = e.charCode;

	if(e.keyCode)
		k = e.keyCode;

	if(e.which)
		w = e.which;

	if(c == 0)
		{
		}
	else
		{
//		console.log("charCode: " + c);
		}

	if(k == 0)
		{
		}
	else
		{
//		console.log("keyCode: " + k);
		}

	if(w == 0)
		{
		}
	else if(w == 8)
		{
		}
	else if((w < 48) || (w > 57)) // 0 .. 9
		return(false);
	else if(((myfield.value + String.fromCharCode(w)) < min) || ((myfield.value + String.fromCharCode(w)) > max))
		return(false);
	else
		{
//		console.log("wich: " + w);
		}

	return(true);
	}

