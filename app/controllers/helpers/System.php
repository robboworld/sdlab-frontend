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

	static function dateformat($string, $format = 'd.m.Y H:i:s')
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

		exit();
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
		return $obj->d . ' ' . L::DAYS_SHORT2 . ' ' . $obj->h . ' ' . L::HOURS_SHORT2 . ' ' . $obj->m . ' ' . L::MINUTES_SHORT2 . ' ' . $obj->s . ' '. L::SECONDS_SHORT2;
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


	static function get_ip_address($ifname = 'eth0')
	{
		$ips = static::get_ip_addresses($ifname);

		return (isset($ips[0])) ? $ips[0] : '';
	}


	/**
	 * Get list of self interfaces
	 * 
	 * @return array:
	 */
	static function get_interfaces($pattern = null)
	{

		$osName = strtoupper(PHP_OS);
		$output = null;

		switch ($osName)
		{
			case 'WINNT':
				$output = null;
				break;

			case 'LINUX':
				$output = shell_exec('/sbin/ifconfig | /usr/bin/cut -d " " -f1 | /usr/bin/awk \'NF==1{print $1}\'');
				break;

			default : break;
		}

		$interfaces = explode("\n", $output);
		if ($output === null || empty($interfaces))
		{
			return array();
		}

		if ($pattern !== null)
		{
			$interfaces = preg_grep($pattern, $interfaces);
		}

		return $interfaces;
	}


	/**
	 * Get list of self ipv4 addresses
	 * 
	 * @return array:
	 */
	static function get_ip_addresses($ifname = null)
	{
		$osName = strtoupper(PHP_OS);
		$ipRes = null;

		switch ($osName)
		{
			case 'WINNT':
				$ipRes[] = shell_exec('ipconfig');
				$ipPattern = '/IP( Address|v)[^:]+: ([\d]{1,3}\.[\d]{1,3}\.[\d]{1,3}\.[\d]{1,3})/';
				$match = 2;
				break;

			case 'LINUX':
				if (!is_array($ifname))
				{
					$ifname = array(($ifname !== null) ? $ifname : '');
				}
				foreach ($ifname as $name)
				{
					$name = ($name !== null) ? (string) preg_replace('/[^A-Z0-9]/i','', $name) : '';
					$ipRes[] = shell_exec('/sbin/ip addr list ' . $name . ' |/bin/grep "inet " |/usr/bin/cut -d" " -f6|/usr/bin/cut -d/ -f1');  //xxx: use "inet6 " for ipv6 or "inet" for all
				}
				$ipPattern = '/(.*)/';
				$match = 1;
				break;

			default : break;
		}

		$result = array();
		foreach ((array)$ipRes as $val)
		{
			if (preg_match_all($ipPattern, (string)$val, $matches))
			{
				$result = array_merge($result, $matches[$match]) ;
			}
		}

		return $result;
	}


	/**
	 * Method to clean values. Processes for XSS and specified bad code.
	 *
	 * @param   mixed   $source  Input string/array-of-string to be 'cleaned'
	 * @param   string  $type    The return type for the variable:
	 *                           INT:       An integer,
	 *                           UINT:      An unsigned integer,
	 *                           FLOAT:     A floating point number,
	 *                           BOOLEAN:   A boolean value,
	 *                           WORD:      A string containing A-Z or underscores only (not case sensitive),
	 *                           ALNUM:     A string containing A-Z or 0-9 only (not case sensitive),
	 *                           CMD:       A string containing A-Z, 0-9, underscores, periods or hyphens (not case sensitive),
	 *                           METHOD:    A sanitised PHP method name,
	 *                           CLASS:     A sanitised PHP class name,
	 *                           BASE64:    A string containing A-Z, 0-9, forward slashes, plus or equals (not case sensitive),
	 *                           STRING:    A fully decoded and sanitised string (default),
	 *                           ARRAY:     An array,
	 *                           PATH:      A sanitised file path,
	 *                           TRIM:      A string trimmed from normal, non-breaking and multibyte spaces
	 *                           USERNAME:  Do not use (use an application specific filter),
	 *                           RAW:       The raw string is returned with no filtering,
	 *                           unknown:   Do nothing.
	 *
	 * @return  mixed  'Cleaned' version of input parameter
	 *
	 * @since   11.1
	 */
	static function clean($source, $type = 'raw')
	{
		// Handle the type constraint
		switch (strtoupper($type))
		{
			case 'INT':
			case 'INTEGER':
				// Only use the first integer value
				preg_match('/-?[0-9]+/', (string) $source, $matches);
				$result = @ (int) $matches[0];
				break;

			case 'UINT':
				// Only use the first integer value
				preg_match('/-?[0-9]+/', (string) $source, $matches);
				$result = @ abs((int) $matches[0]);
				break;

			case 'FLOAT':
			case 'DOUBLE':
				// Only use the first floating point value
				preg_match('/-?[0-9]+(\.[0-9]+)?/', (string) $source, $matches);
				$result = @ (float) $matches[0];
				break;

			case 'BOOL':
			case 'BOOLEAN':
				$result = (bool) $source;
				break;

			case 'WORD':
				$result = (string) preg_replace('/[^A-Z_]/i', '', $source);
				break;

			case 'ALNUM':
				$result = (string) preg_replace('/[^A-Z0-9]/i', '', $source);
				break;

			case 'CMD':
				$result = (string) preg_replace('/[^A-Z0-9_\.-]/i', '', $source);
				$result = ltrim($result, '.');
				break;

			case 'METHOD':
			case 'CLASSNAME':
				$pattern = '/^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$/';
				preg_match($pattern, (string) $source, $matches);
				$result = @ (string) $matches[0];
				break;

			case 'BASE64':
				$result = (string) preg_replace('/[^A-Z0-9\/+=]/i', '', $source);
				break;

			case 'ARRAY':
				$result = (array) $source;
				break;

			case 'PATH':
				$pattern = '/^[A-Za-z0-9_\/-]+[A-Za-z0-9_\.-]*([\\\\\/][A-Za-z0-9_-]+[A-Za-z0-9_\.-]*)*$/';
				preg_match($pattern, (string) $source, $matches);
				$result = @ (string) $matches[0];
				break;

			case 'TRIM':
				$result = (string) trim($source);
				//include 'phputf8.trim';
				//$result = utf8_trim($result, chr(0xE3) . chr(0x80) . chr(0x80));
				//$result = utf8_trim($result, chr(0xC2) . chr(0xA0));
				break;

			case 'USERNAME':
				$result = (string) preg_replace('/[\x00-\x1F\x7F<>"\'%&]/', '', $source);
				break;

			case 'RAW':
			default:
				$result = $source;
				break;
		}

		return $result;
	}


	/**
	 * Get float microtime (sec.msec).
	 * For debug purposes.
	 * 
	 * @return float
	 */
	function microtime_float()
	{
		//list($usec, $sec) = explode(" ", microtime());
		//return ((float)$usec + (float)$sec);
		return array_sum(explode(' ', microtime()));
	}
}