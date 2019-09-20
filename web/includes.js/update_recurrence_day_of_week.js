function update_recurrence_day_of_week(expression)
	{
	var o = document.getElementById("Recurrence:WeekOfMonth");

	o.style.display = "none";

	if(document.forms[0]["Recurrence:DayOfWeek"].value == 127)
		{
		}
	else if(document.forms[0]["Recurrence:Type"].value == 3)
		o.style.display = "block";
	else if(document.forms[0]["Recurrence:Type"].value == 6)
		o.style.display = "block";

	////////////////////////////////////////////////////////////////////////////////

	if(expression == null)
		return;

	////////////////////////////////////////////////////////////////////////////////

	var o = document.forms[0]['Recurrence:DayOfWeek'];

	for(i = 0; i < o.length; i = i + 1)
		if(o[i].value == expression)
			{
			o.selectedIndex = i;

			break;
			}
	}

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

function update_recurrence_type()
	{
	// xxxx xxx1 = WeekOfMonth
	// xxxx xx1x = DayOfWeek
	// xxxx x1xx = MonthOfYear
	// xxxx 1xxx = DayOfMonth

	// xxx1 xxxx = Occurrences (always set)
	// xx1x xxxx = Interval (always set)
	// x1xx xxxx = Until (always set)
	// 1xxx xxxx = show last day of month (if WeekOfMonth is set)

	// 0 Recurs daily.
	// 1 Recurs weekly.
	// 2 Recurs monthly.
	// 3 Recurs monthly on the nth day.
	// 4 (not set, used for no recurrecence)
	// 5 Recurs yearly.
	// 6 Recurs yearly on the nth day.

	// The WeekOfMonth element MUST only be included in requests or responses when the Type element (section 2.2.2.44) value is either 3 or 6.
	// The DayOfWeek element MUST only be included in requests or responses when the Type element (section 2.2.2.44) value is 0 (zero), 1, 3, or 6.
	// The MonthOfYear element MUST be included in requests or responses when the Type element value is either 5 or 6.
	// The MonthOfYear element MUST NOT be included in requests or responses when the Type element value is zero (0), 1, 2, or 3.
	// The DayOfMonth element MUST be included in requests or responses when the Type element value is either 2 or 5.
	// The DayOfMonth element MUST NOT be included in requests or responses when the Type element value is zero (0), 1, 3, or 6.

	// 0 01110010
	// 1 01110010
	// 2 01111000
	// 3 11110011
	// 4 00000000
	// 5 01111100
	// 6 11110011

	var d = [0x72, 0x72, 0x78, 0xF3, 0x00, 0x7C, 0xF3, "WeekOfMonth", "DayOfWeek", "MonthOfYear", "DayOfMonth", "Occurrences", "Interval", "Until"];

	var o = document.forms[0]["Recurrence:DayOfWeek"];
	var t = document.forms[0]["Recurrence:Type"];

	for(i = 0; i < 7; i = i + 1)
		{
		document.getElementById("Recurrence:" + d[i + 7]).style.display = (((d[t.value] >> i) & 0x01) ? "block" : "none");
		}

	for(i = 0; i < (d[t.value] & 0x80 ? 8 : 7); i = i + 1)
		{
		o.options.length = i + 1;
		o.options[i] = new Option(["Sonntag", "Montag", "Dienstag", "Mittwoch", "Donnerstag", "Freitag", "Samstag", "letzter Tag des Monats"][i], [1, 2, 4, 8, 16, 32, 64, 127][i], false, false);
		}
	}

function update_recurrence_week_of_month()
	{
	}

