function handle_files(prev_id, data_id)
	{
	var xa_onchange = function(e)
		{
		if(xa.files[0].size > 1 * 1024 * 1024)
			alert("file too big");
		else
			xb.readAsDataURL(xa.files[0]);
		}

	////////////////////////////////////////////////////////////////////////

	var xb_onload = function(e)
		{
		xc.src = e.target.result;
		}

	////////////////////////////////////////////////////////////////////////

	var xc_onload = function(e)
		{
		var oh = 69; // default height of android picture
		var ow = 69; // default width of android picture

		var ih = xc.height;
		var iw = xc.width;

		////////////////////////////////////////////////////////////////////////

		if(iw > ih) // orientation: landscape
			{
			if(iw != ow)
				{
				ih = ih * ow / iw;
				iw = ow;
				}
			}
		else // orientation: portrait
			{
			if(ih != oh)
				{
				iw = iw * oh / ih;
				ih = oh;
				}
			}

		////////////////////////////////////////////////////////////////////////

		var xd = document.createElement('canvas');

		xd.height	= ih;
		xd.width	= iw;

		xd.getContext('2d').drawImage(xc, 0, 0, iw, ih);

		////////////////////////////////////////////////////////////////////////

		var xf = xd.toDataURL('image/gif').split(',');

		document.getElementById(data_id).value	= xf[1];
		document.getElementById(prev_id).src	= (xf[1] ? 'data:image/unknown;base64,' + xf[1] : 'images/contacts_default_image_add.png');
		}

	////////////////////////////////////////////////////////////////////////

	var xa = document.createElement('input');

	xa.accept		= "image/*";
	xa.multiple		= false;
	xa.onchange		= xa_onchange;
	xa.style.display	= "none";
	xa.type			= "file";

	////////////////////////////////////////////////////////////////////////

	var xb = new FileReader();

	xb.onload		= xb_onload;

	////////////////////////////////////////////////////////////////////////

	var xc = new Image();

	xc.onload		= xc_onload;

	////////////////////////////////////////////////////////////////////////

	xa.click();

	return(true)
	}

