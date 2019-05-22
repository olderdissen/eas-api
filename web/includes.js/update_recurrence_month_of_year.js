function update_recurrence_month_of_year()
	{
	var o = document.forms[0];

	// count from right to left. first two bits must be null (month 0)

        //          12 11 10 09 08 07 06 05 04 03 02 01
	//          11 10 11 10 11 11 10 11 10 11 01 11
	// 00 00 00 11 10 11 10 11 11 10 11 10 11 01 11 00

	var max_days = 28 + ((0x03BBEEDC >> (o["Recurrence:MonthOfYear"].value * 2)) & 0x03);
//	var max_days = [31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31][o["Recurrence:MonthOfYear"].value - 1];

	o["Recurrence:IsLeapMonth"].value = (max_days == 29 ? 1 : 0);
	o["Recurrence:DayOfMonth"].options.length = max_days;

	for(i = 1; i < max_days + 1; i = i + 1)
		o["Recurrence:DayOfMonth"].options[i - 1] = new Option(i, i, false, false);
	}

