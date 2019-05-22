function popup_update_load()
	{
	var xa_onchange = function(e)
		{
		if(e.target.files[0].size > 1 * 1024 * 1024)
			{
			alert("file too big");
			}
		else
			{
			document.getElementById('text').innerHTML = e.target.files[0].name;

			xb.readAsDataURL(e.target.files[0]);
			}
		}

	////////////////////////////////////////////////////////////////////////

	var xb_onload = function(e)
		{
		document.getElementById('data').value = e.target.result.split(',')[1];
		}

	var xb_onloadend = function(e)
		{
		}

	var xb_onloadstart = function(e)
		{
		}

	var xb_onprogress = function(e)
		{
		}

	////////////////////////////////////////////////////////////////////////

	document.getElementById('state').innerHTML = '';

	////////////////////////////////////////////////////////////////////////

	var xa = document.getElementById('file');

	xa.onchange		= xa_onchange;

	////////////////////////////////////////////////////////////////////////

	var xb = new FileReader();

	xb.onload		= xb_onload;
	xb.onloadend		= xb_onloadend;
	xb.onloadstart		= xb_onloadstart;
	xb.onprogress		= xb_onprogress;

	////////////////////////////////////////////////////////////////////////

	xa.click();
	}

