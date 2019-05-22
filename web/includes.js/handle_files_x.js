function handle_files_x()
	{
	var xa_onchange = function(e)
		{
		if(xa.files[0].size > 10 * 1024 * 1024)
			{
			alert("file too big");
			}
		else
			{
			xb.readAsDataURL(xa.files[0]);
			}
		}

	////////////////////////////////////////////////////////////////////////

	var xb_update = function(e, t)
		{
		if(e.lengthComputable)
			{
			pba = Math.round((e.loaded / e.total) * 100)

			document.getElementById("pbc").style.width = (pba + pbb) + 'px';
			}
		}

	var xb_onload = function(e)
		{
		xb_update(e, 3);
		}

	var xb_onloadend = function(e)
		{
		xb_update(e, 4);

		var data = "Data=" + e.target.result.split(",", 2)[1];

		xc.open("POST", "index.php?Cmd=Upload&CollectionId=" + state.collection_id + "&LongId=1&ItemId=" + escape(xa.files[0].name), true, "", "");
		xc.timeout = 10 * 1000;
		xc.setRequestHeader("Content-Length", data.length);
		xc.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		xc.send(data);
		}

	var xb_onloadstart = function(e)
		{
		xb_update(e, 1);

		document.getElementById("pbe").style.display = "block";
		}

	var xb_onprogress = function(e)
		{
		xb_update(e, 2);
		}

	////////////////////////////////////////////////////////////////////////

	var xc_update = function(e, t)
		{
		if(e.lengthComputable)
			{
			pbb = Math.round((e.loaded / e.total) * 100)

			document.getElementById("pbc").style.width = (pba + pbb) + 'px';
			}
		}

	var xc_onload = function(e)
		{
		xc_update(e, 7);

		var v = xc.responseText;

		var o = document.forms[0].attachments;

		o.options[o.options.length] = new Option(v, v, false, false);

		document.getElementById("pbe").style.display = 'none';
		}

	var xc_onloadend = function(e)
		{
		xc_update(e, 8);
		}

	var xc_onloadstart = function(e)
		{
		xc_update(e, 5);
		}

	var xc_onprogress = function(e)
		{
		xc_update(e, 6);
		}

	var xc_onreadystatechange = function(e)
		{
		xc_update(e, 9);
		}

	////////////////////////////////////////////////////////////////////////

	var xa = document.createElement('input');

	xa.accept		= "*/*";
	xa.multiple		= false;
	xa.onchange		= xa_onchange;
	xa.style.display	= "none";
	xa.type			= "file";

	////////////////////////////////////////////////////////////////////////

	xb = new FileReader();

	xb.onload		= xb_onload;
	xb.onloadend		= xb_onloadend;
	xb.onloadstart		= xb_onloadstart;
	xb.onprogress		= xb_onprogress;

	////////////////////////////////////////////////////////////////////////

	xc = new XMLHttpRequest();

	xc.onload		= xc_onload;
	xc.onloadend		= xc_onloadend;
	xc.onloadstart		= xc_onloadstart;
	xc.onprogress		= xc_onprogress;
	xc.onreadystatechange	= xc_onreadystatechange;

	////////////////////////////////////////////////////////////////////////

	var pba = 0;
	var pbb = 0;

	xa.click();
	}

