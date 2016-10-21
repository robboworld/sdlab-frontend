<?php
/**
 * Class Form
 * 
 * Forms helper
 */
class Form
{
	public $id;
	public $submit;

	public function __construct($id = 'new-form')
	{
		$this->id = $id;
		$this->submit = new stdClass();
		$this->submit->value = L('SEND');
	}


	public static function redirect($query_string = null)
	{
		if($query_string == null)
		{
			header('Location: ?'.$_SERVER['QUERY_STRING']);
		}
		else
		{
			header('Location: '.$query_string);
		}

		exit();
	}

	/**
	 * @param $date
	 * @return string
	 */
	public static function dateToInput($date, $timezone = null)
	{
		$dt = new DateTime($date);

		if ($timezone !== null)
		{
			if ($timezone === 'now')
			{
				$tz = (new DateTime())->getTimezone();
			}
			else
			{
				$tz = (new DateTime())->setTimezone(new DateTimeZone($timezone))->getTimezone();
			}
			$dt->setTimezone($tz);
		}

		return $dt->format('Y-m-d');
	}

	/**
	 * Convert datetime interval array of (days, hours, minutes, seconds) to seconds
	 * 
	 * @param array $dhm
	 * 
	 * @return int
	 */
	public static function DHMStoSec(array $dhm)
	{
		$sec = 0;
		$sec += $dhm[3];
		$sec += $dhm[2]*60;
		$sec += $dhm[1]*60*60;
		$sec += $dhm[0]*60*60*24;
		return $sec;
	}

	public static function formTimeObject($sec)
	{
		$times = array();

		// Zeroes counter
		$count_zero = true;

		// Number of seconds in year not use leap years specific
		// method use 365 days in the year
		// seconds in a minute|hour|day|year
		$periods = array(60, 3600, 86400);

		for ($i = 2; $i >= 0; $i--)
		{
			$period = floor($sec/$periods[$i]);
			if (($period > 0) || ($period == 0 && $count_zero))
			{
				$times[$i+1] = $period;
				$sec -= $period * $periods[$i];

				$count_zero = true;
			}
		}

		$times[0] = $sec;
		$time = new stdClass();
		$time->d = $times[3];
		$time->h = $times[2];
		$time->m = $times[1];
		$time->s = $times[0];
		return $time;
	}
}