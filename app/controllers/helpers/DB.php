<?php
/**
 * Class DB
 * 
 * Connection to sqlite database
 */
class DB extends PDO
{
	protected static $db = null;

	function __construct()
	{
		parent::__construct('sqlite:'.DBFILE);
	}

	public static function getInsctance($force = false)
	{
		if (static::$db === null || $force)
		{
			// Close old connection
			if (static::$db !== null)
			{
				static::$db = null;
			}

			// Create new connection
			static::$db = new DB();
		}

		return static::$db;
	}


	/**
	 * Return empty insert row with placeholders for batch inserts
	 * 
	 * @param string $text       Placeholder symbol
	 * @param number $count      Data fields count
	 * @param string $separator  Data fields seperator
	 * 
	 * @return string
	 */
	public static function placeholders($text, $count = 0, $separator = ",")
	{
		$result = array();

		if ($count > 0)
		{
			for($x=0; $x<$count; $x++)
			{
				$result[] = $text;
			}
		}

		return implode($separator, $result);
	}
}