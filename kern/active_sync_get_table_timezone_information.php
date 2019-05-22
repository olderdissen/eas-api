<?
function active_sync_get_table_timezone_information()
	{
	$table = array(
		array(array( 660, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Midway-Inseln"),
		array(array( 600, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Hawaii"),
		array(array( 540, "", array(0, 11,  0,  1,  2,  0,  0,  0),  0, "", array(0,  3,  0,  2,  2,  0,  0,  0), -60), "Alaska"),
		array(array( 480, "", array(0, 11,  0,  1,  2,  0,  0,  0),  0, "", array(0,  3,  0,  2,  2,  0,  0,  0), -60), "Pazifik"),
		array(array( 480, "", array(0, 11,  0,  1,  2,  0,  0,  0),  0, "", array(0,  3,  0,  2,  2,  0,  0,  0), -60), "Tijuana"),
		array(array( 420, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Arizona"),
		array(array( 420, "", array(0, 10,  0,  4,  2,  0,  0,  0),  0, "", array(0,  4,  0,  1,  2,  0,  0,  0), -60), "Chihuahua"),
		array(array( 420, "", array(0, 11,  0,  1,  2,  0,  0,  0),  0, "", array(0,  3,  0,  2,  2,  0,  0,  0), -60), "Mountain"),
		array(array( 360, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Mittelamerika"),
		array(array( 360, "", array(0, 11,  0,  1,  2,  0,  0,  0),  0, "", array(0,  3,  0,  2,  2,  0,  0,  0), -60), "Central"),
		array(array( 360, "", array(0, 10,  0,  4,  2,  0,  0,  0),  0, "", array(0,  4,  0,  1,  2,  0,  0,  0), -60), "Mexiko-Stadt"),
		array(array( 360, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Saskatchewan"),
		array(array( 300, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Bogota"),
		array(array( 300, "", array(0, 11,  0,  1,  2,  0,  0,  0),  0, "", array(0,  3,  0,  2,  2,  0,  0,  0), -60), "Eastern"),
		array(array( 270, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Venezuela"),
		array(array( 240, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Atlantik"),
		array(array( 240, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Manaus"),
		array(array( 240, "", array(0,  3,  6,  2,  0,  0,  0,  0),  0, "", array(0, 10,  6,  2,  0,  0,  0,  0), -60), "Santiago"),
		array(array( 210, "", array(0, 11,  0,  1,  1,  0,  0,  0),  0, "", array(0,  3,  0,  2,  1,  0,  0,  0), -60), "Neufundland"),
		array(array( 180, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Buenos Aires"),
		array(array( 180, "", array(0, 10,  0,  4,  3,  0,  0,  0),  0, "", array(0,  3,  0,  5,  4,  0,  0,  0), -60), "Grönland"),
		array(array( 180, "", array(0,  2,  0,  3,  4,  0,  0,  0),  0, "", array(0, 10,  0,  3,  6,  0,  0,  0), -60), "Brasilien"),
		array(array( 180, "", array(0,  3,  0,  2,  2,  0,  0,  0),  0, "", array(0, 10,  0,  1,  2,  0,  0,  0), -60), "Montevideo"),
		array(array( 120, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Mittelatlantik"),
		array(array(  60, "", array(0, 10,  0,  4,  3,  0,  0,  0),  0, "", array(0,  3,  0,  5,  4,  0,  0,  0), -60), "Azoren"),
		array(array(  60, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Kapverdische Inseln"),
		array(array(   0, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Casablanca"),
		array(array(   0, "", array(0, 10,  0,  4,  2,  0,  0,  0),  0, "", array(0,  3,  0,  5,  1,  0,  0,  0), -60), "London, Dublin"),
		array(array(- 60, "", array(0, 10,  0,  5,  3,  0,  0,  0),  0, "", array(0,  3,  0,  4,  2,  0,  0,  0), -60), "Amsterdam, Berlin"),
		array(array(- 60, "", array(0, 10,  0,  4,  3,  0,  0,  0),  0, "", array(0,  3,  0,  5,  2,  0,  0,  0), -60), "Belgrad"),
		array(array(- 60, "", array(0, 10,  0,  4,  3,  0,  0,  0),  0, "", array(0,  3,  0,  5,  2,  0,  0,  0), -60), "Brüssel"),
		array(array(- 60, "", array(0, 10,  0,  4,  3,  0,  0,  0),  0, "", array(0,  3,  0,  5,  2,  0,  0,  0), -60), "Sarajevo"),
		array(array(- 60, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "W.-Afrika"),
		array(array(- 60, "", array(0, 10,  0,  5,  3,  0,  0,  0),  0, "", array(0,  3,  0,  5,  2,  0,  0,  0), -60), "Mitteleuropäische Zeit"),
		array(array(- 60, "", array(0,  4,  0,  1,  2,  0,  0,  0),  0, "", array(0,  9,  0,  1,  2,  0,  0,  0), -60), "Windhoek"),
		array(array(-120, "", array(0, 10,  5,  5,  1,  0,  0,  0),  0, "", array(0,  3,  4,  5,  0,  0,  0,  0), -60), "Amman, Jordan"),
		array(array(-120, "", array(0, 10,  0,  4,  4,  0,  0,  0),  0, "", array(0,  3,  0,  5,  3,  0,  0,  0), -60), "Athen, Istanbul"),
		array(array(-120, "", array(0, 10,  6,  4,  0,  0,  0,  0),  0, "", array(0,  3,  6,  5,  0,  0,  0,  0), -60), "Beirut, Libanon"),
		array(array(-120, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Kairo"),
		array(array(-120, "", array(0, 10,  0,  4,  4,  0,  0,  0),  0, "", array(0,  3,  0,  5,  3,  0,  0,  0), -60), "Helsinki"),
		array(array(-120, "", array(0,  9,  0,  2,  2,  0,  0,  0),  0, "", array(0,  3,  5,  5,  2,  0,  0,  0), -60), "Jerusalem"),
		array(array(-120, "", array(0, 10,  0,  4,  3,  0,  0,  0),  0, "", array(0,  3,  0,  5,  2,  0,  0,  0), -60), "Minsk"),
		array(array(-120, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Harare"),
		array(array(-180, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Baghdad"),
		array(array(-180, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Kuwait"),
		array(array(-180, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Nairobi"),
		array(array(-210, "", array(0,  9,  6,  3, 22, 30,  0,  0),  0, "", array(0,  3,  4,  3, 22, 30,  0,  0), -60), "Teheran"),
		array(array(-240, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Moskau"),
		array(array(-240, "", array(0, 10,  0,  4,  5,  0,  0,  0),  0, "", array(0,  3,  0,  5,  4,  0,  0,  0), -60), "Baku"),
		array(array(-240, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Tbilisi"),
		array(array(-240, "", array(0, 10,  0,  4,  3,  0,  0,  0),  0, "", array(0,  3,  0,  5,  2,  0,  0,  0), -60), "Yerevan"),
		array(array(-240, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Dubai"),
		array(array(-270, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Kabul"),
		array(array(-300, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Islamabad, Karatschi"),
		array(array(-300, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Uralsk"),
		array(array(-330, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Kolkata"),
		array(array(-330, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Sri Lanka"),
		array(array(-345, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Kathmandu"),
		array(array(-360, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Jekaterinburg"),
		array(array(-360, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Astana"),
		array(array(-390, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Yangon"),
		array(array(-420, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Bangkok"),
		array(array(-480, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Krasnojarsk"),
		array(array(-480, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Peking"),
		array(array(-480, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Hong Kong"),
		array(array(-480, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Kuala Lumpur"),
		array(array(-480, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Perth"),
		array(array(-480, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Taipeh"),
		array(array(-540, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Irkutsk"),
		array(array(-540, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Seoul"),
		array(array(-540, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Tokio, Osaka"),
		array(array(-570, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Darwin"),
		array(array(-570, "", array(0,  4,  0,  1,  3,  0,  0,  0),  0, "", array(0, 10,  0,  1,  2,  0,  0,  0), -60), "Adelaide"),
		array(array(-600, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Jakutsk"),
		array(array(-600, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Brisbane"),
		array(array(-600, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Guam"),
		array(array(-600, "", array(0,  4,  0,  1,  3,  0,  0,  0),  0, "", array(0, 10,  0,  1,  2,  0,  0,  0), -60), "Hobart"),
		array(array(-600, "", array(0,  4,  0,  1,  3,  0,  0,  0),  0, "", array(0, 10,  0,  1,  2,  0,  0,  0), -60), "Canberra, Sydney"),
		array(array(-660, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Wladiwostok"),
		array(array(-720, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Magadan"),
		array(array(-720, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Marshall-Inseln"),
		array(array(-720, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Fidchi"),
		array(array(-720, "", array(0,  4,  0,  1,  3,  0,  0,  0),  0, "", array(0,  9,  0,  5,  2,  0,  0,  0), -60), "Auckland"),
		array(array(-780, "", array(0,  0,  0,  0,  0,  0,  0,  0),  0, "", array(0,  0,  0,  0,  0,  0,  0,  0),   0), "Tonga"),
		);

	foreach($table as $id => $entry)
		{
		list($data, $name) = $entry;

		list($bias, $standard_name, $standard_date, $standard_bias, $daylight_name, $daylight_date, $daylight_bias) = $data;

		list($year, $month, $day_of_week, $day, $hour, $minute, $second, $milliseconds) = $standard_date;

		$standard_date = active_sync_systemtime_encode($year, $month, $day_of_week, $day, $hour, $minute, $second, $milliseconds);

		list($year, $month, $day_of_week, $day, $hour, $minute, $second, $milliseconds) = $daylight_date;

		$daylight_date = active_sync_systemtime_encode($year, $month, $day_of_week, $day, $hour, $minute, $second, $milliseconds);

		$data = active_sync_time_zone_information_encode($bias, $standard_name, $standard_date, $standard_bias, $daylight_name, $daylight_date, $daylight_bias);

		$bias_p = ($bias > 0 ? "-" : "+");
		$bias_v = ($bias > 0 ? 0 + 1 : 0 - 1) * $bias;

		$bias_m = ($bias_v % 60);
		$bias_h = (($bias_v - $bias_m) / 60);

		$table[$id] = array(base64_encode($data), sprintf("GMT %s%02d%02d %s", $bias_p, $bias_h, $bias_m, $name));
		}

	return($table);
	}
?>
