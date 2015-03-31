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

	static function nano($var)
	{
		return $var * 1000000000;
	}

	static function secToTime($sec)
	{
		$obj = Form::formTimeObject($sec);
		return $obj->d.' д. '.$obj->h.' ч. '.$obj->m.' м. '.$obj->s.' с.';
	}
}