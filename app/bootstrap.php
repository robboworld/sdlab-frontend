<?

define('APP', dirname(__FILE__));
define('CONTROLLERS', APP.'/controllers');
define('MODELS', APP.'/models');
define('VIEWS', APP.'/views');
define('DEFAULT_LAYOUT', VIEWS.'/layout.tpl.php');
//define('DBFILE', '/data/newlab.db');
define('DBFILE', APP.'/db/newlab.db');


/* Class Autoload*/
function __autoload($classname) {

	$classmap = include('config/classmap.php');
	if(array_key_exists($classname, $classmap))
	{
		$filename = $classmap[$classname];
		if(file_exists($filename))
		{
			include_once($filename);
		}
		else
		{
			throw new Exception('Failed load class('.$classname.') file. File '.$filename.' not exists: ');
		}
	}
	else
	{
		throw new Exception('Undefined class name: '.$classname);
	}

}

if (session_status() == PHP_SESSION_NONE) {
	session_start();
}