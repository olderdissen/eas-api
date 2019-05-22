function min_max(a, b, c)
	{
	a = (a < b ? b : a);
	a = (a > c ? c : a);

	return(a);
	}

