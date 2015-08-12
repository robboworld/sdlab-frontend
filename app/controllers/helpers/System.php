<?


/** class System
 * Статичные системные функции
 */

class System
{
	static function dump($var)
	{
		print('<pre>');
		var_dump($var);
		print('</pre>');
	}

	static function dateformat($string, $format = 'd.m.Y H:s')
	{
		return (new DateTime($string))->format($format);
	}

	/**
	 * @param string $query_string
	 */
	static function go($query_string = null)
	{
		if($query_string == null)
		{
			header('Location: /');
		}
		else
		{
			header('Location: ?q='.$query_string);
		}
	}

	/**
	 * Convert number of seconds to nanoseconds
	 * 
	 * @param  interger|float  $var  Seconds
	 * 
	 * @return interger|float
	 */
	static function nano($var)
	{
		return $var * 1000000000;
	}

	static function secToTime($sec)
	{
		$obj = Form::formTimeObject($sec);
		return $obj->d.' д. '.$obj->h.' ч. '.$obj->m.' м. '.$obj->s.' с.';
	}

	static function nulldate()
	{
		return "0001-01-01T00:00:00Z";
	}

	static function cutdatemsec($string)
	{
		return (string) preg_replace('/\.\d+Z/i', 'Z', $string);
	}

	/**
	 * Mapping for sensor values names with translate
	 * 
	 * @param string $name  Sensor type name
	 * @param string $key   Sensor field name
	 * 
	 * @return string  Text string for field name or False on error/not found
	 */
	static function getValsTranslate($name, $key = '')
	{
		// TODO: Move to separate transtations INI files (some php-i18n), or to db translation tables, get from sensors on eng, then translate

		static $cat = array(
				//sensor_name
				'temperature' => array(
						'value_name'    => 'температура',
						'si_name'       => 'кельвин',
						'si_notation'   => 'К'
				),
				'pressure' => array(
						'value_name'    => 'давление',
						'si_name'       => 'паскаль',
						'si_notation'   => 'Па'
				),
				'angle' => array(
						'value_name'    => 'угол',
						'si_name'       => 'радиан',
						'si_notation'   => 'рад'
				),
				'humidity' => array(
						'value_name'    => 'относительная влажность',
						'si_name'       => 'процент',
						'si_notation'   => '%'
				),
				'illuminance' => array(
						'value_name'    => 'освещённость',
						'si_name'       => 'люкс',
						'si_notation'   => 'лк'
				)
		);

		if (is_string($key) && isset($cat[$name]))
		{
			if (empty($key))
			{
				return $cat[$name]['value_name'];
			}

			if (isset($cat[$name][$key]))
			{
				return $cat[$name][$key];
			}
		}

		return false;
	}
}