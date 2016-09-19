<?php
/** 
 * Class System
 * 
 * Static system methods
 */
class System
{
	/**
	 * Used datetime formats
	 */
	const DATETIME_RFC3339_UTC     = 'Y-m-d\TH:i:s\Z';
	const DATETIME_RFC3339NANO_UTC = 'Y-m-d\TH:i:s.u\Z';
	const DATETIME_FORMAT1         = 'Y.m.d H:i:s';
	const DATETIME_FORMAT1NANO     = 'Y.m.d H:i:s.u';
	const DATETIME_FORMAT1NANOXSL  = 'Y.m.d H:i:s,u';
	const DATETIME_FORMAT2         = 'd.m.Y H:i:s';
	const DATETIME_FORMAT3         = 'Y.m.d H:i:s e';

	/**
	 * The list of available timezone groups to use.
	 *
	 * @var    array
	 */
	protected static $zones = array('Africa', 'America', 'Antarctica', 'Arctic', 'Asia', 'Atlantic', 'Australia', 'Europe', 'Indian', 'Pacific');

	public static function dump($var)
	{
		echo '<pre>';
		var_dump($var);
		echo '</pre>';
	}

	/**
	 * Format text datetime
	 * 
	 * @param string $string    input datetime string in format for DateTime
	 * @param string $format    new datetime format
	 * @param string $timezone  new timezone, one of the supported timezone names or 'now' for local
	 * 
	 * @return string
	 * 
	 * @throws Exception
	 */
	public static function dateformat($string, $format = 'd.m.Y H:i:s', $timezone = null)
	{
		$dt = new DateTime($string);

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

		return $dt->format($format);
	}

	/**
	 * Redirect and exit
	 * 
	 * @param string $query_string  url
	 */
	public static function go($query_string = null)
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
	 * Go to the destination url by request var.
	 * Goes to default query if no destication valiable found.
	 * 
	 * @param string $default_query  Default redirect query
	 * @param string $method         Method (POST|GET)
	 * @param string $query_var      Request var name
	 */
	public static function goback($default_query = null, $method = "get", $query_var = "destination")
	{
		$method = strtolower($method);
		switch ($method)
		{
			case 'post':
				if(isset($_POST['destination']))
				{
					System::go(System::cleanVar($_POST['destination'], 'path'));
				}
				else
				{
					System::go($default_query);
				}
				break;

			case 'get':
			default:
				if(isset($_GET['destination']))
				{
					System::go(System::cleanVar($_GET['destination'], 'path'));
				}
				else
				{
					System::go($default_query);
				}
				break;
		}
	}

	/**
	 * Output error/status and exit (404,403,500 and etc).
	 * 
	 * @param integer $errcode
	 * @param string  $str
	 */
	public static function goerror($code = 500, $str = null)
	{
		// Clean output buffer
		while (@ob_end_clean()) {
			// do nothing
		}

		switch ($code)
		{
			case 404:
				header($_SERVER['SERVER_PROTOCOL'] . ' 404 Not Found', true, 404);
				if(!empty($str))
				{
					echo $str;
				}
				break;

			case 403:
				{
					header($_SERVER['SERVER_PROTOCOL'] . ' 403 Forbidden', true, 403);
					if(!empty($str))
					{
						echo $str;  //'You are forbidden!';
					}
				}
				break;

			case 500:
			default:
				header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
				break;
		}

		exit();
	}

	/**
	 * Convert number of seconds to nanoseconds
	 * 
	 * @param  int|float  $var  Seconds
	 * 
	 * @return int|float
	 */
	public static function nano($var)
	{
		return $var * 1000000000;
	}

	public static function secToTime($sec)
	{
		$obj = Form::formTimeObject($sec);
		return $obj->d . ' ' . L::DAYS_SHORT2 . ' ' . $obj->h . ' ' . L::HOURS_SHORT2 . ' ' . $obj->m . ' ' . L::MINUTES_SHORT2 . ' ' . $obj->s . ' '. L::SECONDS_SHORT2;
	}

	/**
	 * Null date in backend
	 * 
	 * @return string
	 */
	public static function nulldate()
	{
		return "0001-01-01T00:00:00Z";
	}

	/**
	 * Cut nanoseconds part in datetime strings formatted as:
	 * YYYY-MM-DDThh:mm:ss.nnnnnnnnnZ
	 * YYYY-MM-DDThh:mm:ss.nnnnnnnnn+hh:mm
	 * YYYY-MM-DDThh:mm:ss.nnnnnnnnn-hh:mm
	 * 
	 * Subformat ss.nnnnnnnnn can have one to 9 digits following the decimal point. 
	 * 
	 * Example:
	 * 2001-10-16T16:25:49.280505475+03:00 -> 2001-10-16T16:25:49+03:00
	 * 2001-10-16T16:25:49.280505475Z      -> 2001-10-16T16:25:49Z
	 * 
	 * @param  string  $string
	 * 
	 * @return string
	 */
	public static function cutdatemsec($string)
	{
		return (string) preg_replace('/\.\d+(Z|(\+|\-).*)/i', '${1}', $string);
	}

	/**
	 * Get nanoseconds part of datetime string formatted as:
	 * YYYY-MM-DDThh:mm:ss.nnnnnnnnnZ
	 * YYYY-MM-DDThh:mm:ss.nnnnnnnnn+hh:mm
	 * YYYY-MM-DDThh:mm:ss.nnnnnnnnn-hh:mm
	 *
	 * Subformat ss.nnnnnnnnn can have 1 to 9 digits following the decimal point.
	 *
	 * Example returns:
	 * 2001-10-16T16:25:49.280505475+03:00 -> 280505475
	 * 2001-10-16T16:25:49.280505475Z      -> 280505475
	 * 2001-10-16T16:25:49Z                -> 0
	 *
	 * @param  string  $string
	 *
	 * @return string|integer  Number of second parts (nanoseconds)
	 */
	public static function getdatemsec($string)
	{
		$i = preg_match('/\.(\d+)(Z|(\+|\-).*)/i', $string, $mathes);
		if ($i && isset($mathes[1]))
		{
			return $mathes[1];
		}

		return 0;
	}

	/**
	 * Convert datetime string with nanoseconds from local time to UTC
	 * Input datetime string must have formats:
	 * YYYY-MM-DDThh:mm:ss.nnnnnnnnnZ
	 * YYYY-MM-DDThh:mm:ss.nnnnnnnnn+hh:mm
	 * YYYY-MM-DDThh:mm:ss.nnnnnnnnn-hh:mm
	 *
	 * Subformat ss.nnnnnnnnn can have 1 to 9 digits following the decimal point.
	 *
	 * Example returns:
	 * 2001-10-16T16:25:49.280505475+03:00 -> 2001-10-16T13:25:49.280505475Z
	 * 2001-10-16T16:25:49.280505475Z      -> 2001-10-16T16:25:49.280505475Z
	 * 2001-10-16T16:25:49Z                -> 2001-10-16T16:25:49Z
	 *
	 * @param  string  $string
	 *
	 * @return string
	 * 
	 * @throws Exception
	 */
	public static function convertDatetimeToUTC($string)
	{
		$nsec = static::getdatemsec($string);
		$dt = new DateTime(static::cutdatemsec($string));
		$dt->setTimezone(new DateTimeZone('UTC'));

		return $dt->format('Y-m-d\TH:i:s') . (($nsec != 0) ?  ('.' . $nsec) : '') . 'Z';
	}

	/**
	 * Convert datetime string with nanoseconds from UTC to local time
	 * @see formats comments in System::convertDatetimeToUTC
	 *
	 * @param  string  $string    Input date string (empty or null for now time)
	 * @param  string  $format    Output datetime format
	 * @param  string  $timezone  Timezone name or 'now' for current TZ or null, if use from time string
	 *
	 * @return string
	 * 
	 * @throws Exception
	 */
	public static function datemsecformat($string, $format = 'd.m.Y H:i:s.u', $timezone = null)
	{
		$nsec = static::getdatemsec($string);
		$dt = new DateTime(static::cutdatemsec($string));

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

		return $dt->format(preg_replace('`(?<!\\\\)u`', $nsec, $format));
	}

	/**
	 * Mapping for sensor values names with translate to lang key suffixes
	 * 
	 * @param string $name   Sensor type name
	 * @param string $field  Sensor field name
	 * 
	 * @return string  Text string for field name or False on error/not found
	 */
	public static function getValsTranslate($name, $field = '')
	{
		// TODO: create special dictionary of available sensors with characteristics in db or get from backend (modify API request Lab.ListSensors)

		// Mapped values used as parts of language key names for translation to other languages (default as EN language)
		static $cat = array(
				//sensor_name
				'temperature' => array(
						'value_name'    => 'temperature',
						'si_name'       => 'kelvin',
						'si_notation'   => 'K'
				),
				'pressure' => array(
						'value_name'    => 'pressure',
						'si_name'       => 'pascal',
						'si_notation'   => 'Pa'
				),
				'angle' => array(
						'value_name'    => 'angle',
						'si_name'       => 'radian',
						'si_notation'   => 'rad'
				),
				'humidity' => array(
						'value_name'    => 'humidity',
						'si_name'       => 'percent',
						'si_notation'   => 'percent'  // xxx: as "%" in translates, because cannot use symbol in lang key-constant
				),
				'illuminance' => array(
						'value_name'    => 'illuminance',
						'si_name'       => 'lux',
						'si_notation'   => 'lx'
				),
				'current' => array(
						'value_name'    => 'current',
						'si_name'       => 'ampere',
						'si_notation'   => 'A'
				),
				'altitude' => array(
						'value_name'    => 'altitude',
						'si_name'       => 'meter',
						'si_notation'   => 'm'
				),
				'voltage' => array(
						'value_name'    => 'voltage',
						'si_name'       => 'volt',
						'si_notation'   => 'V'
				),
				'distance' => array(
						'value_name'    => 'distance',
						'si_name'       => 'meter',
						'si_notation'   => 'm'
				),
		);

		if (is_string($field) && isset($cat[$name]))
		{
			if (empty($field))
			{
				return $cat[$name]['value_name'];
			}

			if (isset($cat[$name][$field]))
			{
				return $cat[$name][$field];
			}
		}

		return false;
	}


	public static function get_ip_address($ifname = 'eth0')
	{
		$ips = static::get_ip_addresses($ifname);

		return (isset($ips[0])) ? $ips[0] : '';
	}


	/**
	 * Get list of self interfaces
	 * 
	 * @return array:
	 */
	public static function get_interfaces($pattern = null)
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
	public static function get_ip_addresses($ifname = null)
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
	public static function cleanVar($source, $type = 'raw')
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

			// TODO: add URL clean var rule

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
	 * Fetches and returns a given variable.
	 *
	 * The default behaviour is fetching variables depending on the
	 * current request method: GET and HEAD will result in returning
	 * an entry from $_GET, POST and PUT will result in returning an
	 * entry from $_POST.
	 *
	 * You can force the source by setting the $hash parameter:
	 *
	 * post    $_POST
	 * get     $_GET
	 * files   $_FILES
	 * cookie  $_COOKIE
	 * env     $_ENV
	 * server  $_SERVER
	 * method  via current $_SERVER['REQUEST_METHOD']
	 * default $_REQUEST
	 *
	 * @param   string   $name     Variable name.
	 * @param   string   $default  Default value if the variable does not exist.
	 * @param   string   $hash     Where the var should come from (POST, GET, FILES, COOKIE, METHOD).
	 * @param   string   $type     Return type for the variable, for valid values see {@link System::cleanVar()}.
	 * @param   integer  $mask     Filter mask for the variable.
	 * 
	 * @return  mixed  Requested variable.
	 * 
	 * @see Joomla 3.2+ JRequest::getVar()
	 *
	 */
	public static function getVar($name, $default = null, $hash = 'default', $type = 'none')
	{
		// Ensure hash and type are uppercase
		$hash = strtoupper($hash);

		if ($hash === 'METHOD')
		{
			$hash = strtoupper($_SERVER['REQUEST_METHOD']);
		}

		$type = strtoupper($type);

		// Get the input hash
		switch ($hash)
		{
			case 'GET':
				$input = &$_GET;
				break;
			case 'POST':
				$input = &$_POST;
				break;
			case 'FILES':
				$input = &$_FILES;
				break;
			case 'COOKIE':
				$input = &$_COOKIE;
				break;
			case 'ENV':
				$input = &$_ENV;
				break;
			case 'SERVER':
				$input = &$_SERVER;
				break;
			default:
				$input = &$_REQUEST;
				$hash = 'REQUEST';
				break;
		}

		if (isset($input[$name]) && $input[$name] !== null)
		{
			// Get the variable from the input hash and clean it
			$var = self::cleanVar($input[$name], $type);
		}
		elseif ($default !== null)
		{
			// Clean the default value
			$var = self::cleanVar($default, $type);
		}
		else
		{
			$var = $default;
		}

		return $var;
	}


	/**
	 * Fetches and returns a backurl variable value.
	 * Try from REQUEST array, than from GET.
	 *
	 * @param   string   $name     Variable name
	 * 
	 * @return  mixed  Requested variable value.
	 */
	public static function getVarBackurl($name = 'destination')
	{
		// Try get from REQUEST vars
		$backurl = self::getVar($name);

		// Try get from GET vars
		if ($backurl === null || strlen($backurl) == 0)
		{
			$backurl = self::getVar($name, null, 'get');
		}

		return (($backurl === null || strlen($backurl) == 0) ? null : $backurl);
	}


	/**
	 * Get float microtime (sec.msec).
	 * For debug purposes.
	 * 
	 * @return float
	 */
	public static function microtime_float()
	{
		//list($usec, $sec) = explode(" ", microtime());
		//return ((float)$usec + (float)$sec);
		return array_sum(explode(' ', microtime()));
	}


	/**
	 * Method to get the time zone field option groups.
	 * 
	 * @param   array  $elements  additional groups elements
	 * 
	 * @return  array  The field option objects as a nested array in groups.
	 * 
	 * @see JFormFieldTimezone::getGroups() in Joomla.Platform.Form (/libraries/joomla/form/fields/timezone.php)
	 */
	public static function getTimezonesGroups($elements = array())
	{
		static $base_groups = null;

		if(!isset($base_groups))
		{
			$base_groups = array();

			// Get the list of time zones from the server.
			$zones = DateTimeZone::listIdentifiers();

			// Build the group lists.
			foreach ($zones as $zone)
			{
				// Time zones not in a group we will ignore.
				if (strpos($zone, '/') === false)
				{
					continue;
				}

				// Get the group/locale from the timezone.
				list ($group, $locale) = explode('/', $zone, 2);

				// Only use known groups.
				if (in_array($group, static::$zones))
				{
					// Initialize the group if necessary.
					if (!isset($base_groups[$group]))
					{
						$base_groups[$group] = array();
					}

					// Only add options where a locale exists.
					if (!empty($locale))
					{
						$base_groups[$group][$zone] = Html::option($zone, str_replace('_', ' ', $locale), 'value', 'text', false);
					}
				}
			}

			// Sort the group lists.
			ksort($base_groups);

			foreach ($base_groups as &$location)
			{
				sort($location);
			}
		}

		// Merge any additional groups in the XML definition.
		if (!empty($elements))
		{
			return array_merge(static::getGroups($elements), $base_groups);
		}

		return $base_groups;
	}


	/**
	 * Method to get the field option groups.
	 *
	 * @param   array  Option elements.
	 *   type       : option|group (group if is set array of children elements)
	 *   value      : string
	 *   text       : string
	 *   label      : string (for group type ONLY)
	 *   disabled   : true|false, disabled, 1|0
	 *   class      : string
	 *   onclick    : string, javasript
	 *   children   : array of elements (for group type ONLY)
	 *
	 * @return  array  The field option objects as a nested array in groups.
	 *
	 * @throws  UnexpectedValueException
	 * 
	 * @see JFormFieldTimezone::getGroups() in Joomla.Platform.Form (/libraries/joomla/form/fields/groupedlist.php)
	 */
	protected static function getGroups($elements)
	{
		$groups = array();
		$label = 0;

		foreach ($elements as $element)
		{
			switch ($element['type'])
			{
				// The element is an <option />
				case 'option':
					// Initialize the group if necessary.
					if (!isset($groups[$label]))
					{
						$groups[$label] = array();
					}

					$disabled = (string) $element['disabled'];
					$disabled = ($disabled == 'true' || $disabled == 'disabled' || $disabled == '1');

					// Create a new option object based on the <option /> element.
					$tmp = Html::option(
							($element['value']) ? (string) $element['value'] : trim((string) $element['text']),
							trim((string) $element['text']), 'value', 'text',
							$disabled
					);

					// Set some option attributes.
					$tmp->class = (string) $element['class'];

					// Set some JavaScript option attributes.
					$tmp->onclick = (string) $element['onclick'];

					// Add the option.
					$groups[$label][] = $tmp;
					break;

					// The element is a <group />
				case 'group':
					// Get the group label.
					if ($groupLabel = (string) $element['label'])
					{
						$label =  constant('L::' . $groupLabel);
					}

					// Initialize the group if necessary.
					if (!isset($groups[$label]))
					{
						$groups[$label] = array();
					}

					// Iterate through the children and build an array of options.
					foreach ($element['children'] as $option)
					{
						// Only add option elements.
						if ($option['type'] != 'option')
						{
							continue;
						}

						$disabled = (string) $option['disabled'];
						$disabled = ($disabled == 'true' || $disabled == 'disabled' || $disabled == '1');

						// Create a new option object based on the <option /> element.
						$tmp = Html::option(
								($option['value']) ? (string) $option['value'] : trim((string) $option['text']),
								trim((string) $option['text']), 'value', 'text',
								$disabled
						);

						// Set some option attributes.
						$tmp->class = (string) $option['class'];

						// Set some JavaScript option attributes.
						$tmp->onclick = (string) $option['onclick'];

						// Add the option.
						$groups[$label][] = $tmp;
					}

					if ($groupLabel)
					{
						$label = count($groups);
					}
					break;

					// Unknown element type.
				default:
					throw new UnexpectedValueException(sprintf('Unsupported element %s in JFormFieldGroupedList', $element['type']), 500);
			}
		}

		reset($groups);

		return $groups;
	}


	/**
	 * Utility function to map an array to a string.
	 *
	 * @param   array    $array         The array to map.
	 * @param   string   $inner_glue    The glue (optional, defaults to '=') between the key and the value.
	 * @param   string   $outer_glue    The glue (optional, defaults to ' ') between array elements.
	 * @param   boolean  $keepOuterKey  True if final key should be kept.
	 *
	 * @return  string   The string mapped from the given array
	 *
	 * @see JArrayHelper::toString() in Joomla.Platform.Utilities (/libraries/joomla/utilities/arrayhelper.php)
	 */
	public static function arrayToString($array = null, $inner_glue = '=', $outer_glue = ' ', $keepOuterKey = false)
	{
		$output = array();

		if (is_array($array))
		{
			foreach ($array as $key => $item)
			{
				if (is_array($item))
				{
					if ($keepOuterKey)
					{
						$output[] = $key;
					}
					// This is value is an array, go and do it again!
					$output[] = static::toString($item, $inner_glue, $outer_glue, $keepOuterKey);
				}
				else
				{
					$output[] = $key . $inner_glue . '"' . $item . '"';
				}
			}
		}

		return implode($outer_glue, $output);
	}


	/**
	 * Checks if a value exists in an multidimensional array.
	 *
	 * @param   mixed    $needle      The searched value.
	 * @param   array    $haystack    The array.
	 * @param   string   $field       The searched value field name for object field value search.
	 *
	 * @return  bool                  True if needle is found in the array, false otherwise.
	 */
	public static function in_multiarray($needle, &$haystack, $field = null)
	{
		foreach ($haystack as $k => $value)
		{
			if ($haystack[$k] == $needle)
			{
				return true;
			}
			else if ($field !== null && is_object($haystack[$k]) && $haystack[$k]->$field == $needle)
			{
				return true;
			}
			else
			{
				if (is_array($haystack[$k]))
				{
					if (static::in_multiarray($needle, $haystack[$k], $field))
					{
						return true;
					}
				}
			}
		}
		return false;
	}

	/**
	 * Convert array of fields to CSV string.
	 *
	 * @param   array    $fields      Array with fields.
	 * @param   string   $delimiter
	 * @param   string   $enclosure
	 * @param   boolean  $mysql_null  MySQL nulls mode
	 *
	 * @return  string
	 */
	public static function strtocsv(array $fields, $delimiter = ',', $enclosure = '"', $mysql_null = false)
	{
		$delimiter_esc = preg_quote($delimiter, '/');
		$enclosure_esc = preg_quote($enclosure, '/');

		$output = array();
		foreach ($fields as $field) {
			if ($field === null && $mysql_null) {
				$output[] = 'NULL';
				continue;
			}

			$output[] = preg_match("/(?:${delimiter_esc}|${enclosure_esc}|\s)/", $field) ? (
					$enclosure . str_replace($enclosure, $enclosure . $enclosure, $field) . $enclosure
					) : $field;
		}

		return implode($delimiter, $output);
	}
}
