<?

/* Class Autoload*/
function __autoload($classname) {
	$filename = "class/". $classname .".php";
	include_once($filename);
}


$app = new App();
$app->execute();