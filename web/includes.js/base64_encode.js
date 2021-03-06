function base64_encode(data)
	{
	if(! data)
		{
		return(data);
		}

	var b64 = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/=";
	var o1, o2, o3, h1, h2, h3, h4, bits, i = 0,
	ac = 0,
	enc = "",
	tmp_arr = [];

	do
		{
		o1 = data.charCodeAt(i++);
		o2 = data.charCodeAt(i++);
		o3 = data.charCodeAt(i++);

		bits = (o1 << 16) | (o2 << 8) | (o3 << 0);

		h1 = (bits >> 18) & 0x3F;
		h2 = (bits >> 12) & 0x3F;
		h3 = (bits >>  6) & 0x3F;
		h4 = (bits >>  0) & 0x3F;

		tmp_arr[ac ++] = b64.charAt(h1) + b64.charAt(h2) + b64.charAt(h3) + b64.charAt(h4);
		}
	while(i < data.length);

	enc = tmp_arr.join('');

	var r = data.length % 3;

	return((r ? enc.slice(0, r - 3) : enc) + '==='.slice(r || 3));
	}

