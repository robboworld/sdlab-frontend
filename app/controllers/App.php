<?php
/**
 * Class App
 * 
 * Main application class
 */
class App
{
	private $controller;
	public $session;

	public function __construct()
	{
		$this->config = self::config();

		// Get language
		$this->lang = Language::getInstance($this->config['lab']['lang']);

		$query_array = $this->router();

		if(isset($query_array[0]) && ($query_array[0] != ''))
		{
			$controller_class = ucfirst($query_array[0]).'Controller';
			if (isset($query_array[1]))
			{
				$controller = new $controller_class($query_array[1]);
			}
			else
			{
				$controller = new $controller_class();
			}
		}
		else
		{
			$controller = new PageController();
		}


		if($controller->user_access_level() >= 1)
		{
			if(!isset($_SESSION['sdlab']) || !isset($_SESSION['sdlab']['session_key']) || !$_SESSION['sdlab']['session_key'])
			{
				$this->controller(new SessionController('create'));
			}
			else
			{

				$session = new Session();
				if($session->load($_SESSION['sdlab']['session_key']))
				{
					$this->session = $session;
					$this->controller($controller);
				}
				else
				{
					$this->controller(new SessionController('create'));
				}

			}
		}
		else if($controller->user_access_level() == 0)
		{
			if(!empty($_SESSION['sdlab']['session_key']))
			{
				$session = new Session();
				if($session->load($_SESSION['sdlab']['session_key']))
				{
					$this->session = $session;
				}
			}
			$this->controller($controller);
		}

		self::execute();
	}

	public static function router($item = null)
	{

		if(!empty($_GET['q']))
		{
			$query = $_GET['q'];
			$query_array = explode('/', $query);
			if($item !== null)
			{
				return (isset($query_array[$item]) ? $query_array[$item] : '');
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

	public function controller(Controller $controller = null)
	{
		if(!is_null($controller))
		{
			$this->controller = $controller;
			$this->controller->app = $this;

		}
		return $this->controller;
	}

	protected function execute()
	{
		if(is_object($this->controller))
		{
			// Menu only for controllers with view support
			if (isset($this->controller()->view))
			{
				$this->controller()->view->main_menu = Menu::get();
			}
			$this->controller()->renderView();
		}
		else
		{
			throw new Exception('controller is not object.');
		}
	}

	public function getUserLevel()
	{
		if(isset($this->session) && is_object($this->session))
		{
			return $this->session->getUserLevel();
		}
		else
		{
			return 0;
		}
	}

	public static function config()
	{
		$config = include(APP . '/config/config.php');
		return $config;
	}
}
