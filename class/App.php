<?

class App
{
	private $controller;
	function __construct()
	{
		$query_array = $this->router();
		if($query_array[0]!= '')
		{
			$controller_class = ucfirst($query_array[0]).'Controller';
			$this->controller(new $controller_class($query_array[1]));
		}
		else /*todo: перенести в конфиг*/
		{
			$this->controller(new PageController());
		}
	}

	static function router($item = null)
	{

		if(isset($_GET['q']))
		{
			$query = $_GET['q'];
			$query_array = explode('/', $query);
			if($item !== null)
			{
				return $query_array[$item];
			}
			else
			{
				return $query_array;
			}
		}
		else
		{
			return false;
		}
	}

	function controller(Controller $controller = null)
	{
		if(is_null($controller))
		{
			return $this->controller;
		}
		else
		{
			$this->controller = $controller;
		}
	}

	function execute()
	{
		if(is_object($this->controller))
		{
			$this->controller()->renderView();
		}
		else
		{
			throw new Exception('controller is not object.');
		}
	}
}