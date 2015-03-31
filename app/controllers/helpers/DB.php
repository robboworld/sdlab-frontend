<?

/**
 * Class DB
 * сразу подключаемся к файлу базы sqlite
 */
class DB extends PDO
{
	function __construct()
	{
		parent::__construct('sqlite:'.DBFILE);
	}
}