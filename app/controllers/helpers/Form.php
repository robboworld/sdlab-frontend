<?

/**
 * Class Form
 * хелпер для работы с формами
 */
class Form
{
	public $id;
	public $submit;

	function __construct($id = 'new-form')
	{
		$this->id = $id;
		$this->submit = new stdClass();
		$this->submit->value = 'Отправить';
	}


	static function redirect($query_string = null)
	{
		if($query_string == null)
		{
			header('Location: ?'.$_SERVER['QUERY_STRING']);
		}
		else
		{
			header('Location: '.$query_string);
		}
	}

	/**
	 * @param $date
	 * @return string
	 */
	static function dateToInput($date)
	{
		$date = new DateTime($date);
		return $date->format('Y-m-d');
	}

	/**
	 * @param array $dhm
	 * @return int
	 */
	static function DHMStoSec(array $dhm)
	{
		$sec = 0;
		$sec += $dhm[3];
		$sec += $dhm[2]*60;
		$sec += $dhm[1]*60*60;
		$sec += $dhm[0]*60*60*24;
		return $sec;
	}

	static function formTimeObject($sec)
	{
		$times = array();

		// считать нули в значениях
		$count_zero = true;

		// количество секунд в году не учитывает високосный год
		// поэтому функция считает что в году 365 дней
		// секунд в минуте|часе|сутках|году
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