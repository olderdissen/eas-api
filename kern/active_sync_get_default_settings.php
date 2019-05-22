<?
function active_sync_get_default_settings()
	{
	$retval = array();

	$retval["Language"]		= "en";							# en
	$retval["TimeZone"]		= 28;							# de
	$retval["PhoneOnly"]		= 0;							# PhoneOnly
	$retval["SortBy"]		= 0;							# SortBy
	$retval["ShowBy"]		= 0;							# DisplayBy
	$retval["Reminder"]		= 1440;							# 1 day
	$retval["FirstDayOfWeek"]	= 1;							# Monday
	$retval["CalendarSync"]		= 0;							# All

	return($retval);
	}
?>
