<?

class Controller
{
	protected $name;
	public $app;
	public $view;
	public $error;
	protected $action;
	protected $user_access_level = 1;
	function __construct($action = 'index')
	{
		$this->action = $action;
	}

	function index()
	{
		// default method
	}
	function error()
	{
		return $this->error;
	}

	private function collectJs()
	{
		if(isset($this->js) && is_array($this->js))
		{
			$script_list = '';
			foreach($this->js as $script)
			{
				$script_list .= '<script type="text/javascript" src="assets/js/'.$script.'.js"></script>';
			}
			return $script_list;
		}
	}

	private function collectCss()
	{
		if(isset($this->css) && is_array($this->css))
		{
			$css_list = '';
			foreach($this->css as $css_file)
			{
				$css_list .= '<link rel="stylesheet" href="assets/css/'.$css_file.'.css">';
			}
			return $css_list;
		}
	}

	protected function addJs($filename)
	{
		if(is_string($filename))
		{
			$this->js[] = $filename;
		}
	}

	protected function addCss($filename)
	{
		if(is_string($filename))
		{
			$this->css[] = $filename;
		}
	}

	public function renderView()
	{
		if(isset($this->action) && method_exists($this, $this->action))
		{
			$this->{$this->action}();
		}
		else
		{
			$this->index();
		}

		include(VIEWS.'/layout.tpl.php');
	}

	function render()
	{
		if(!isset($this->view->template)) $this->view->template = $this->action;

		include(VIEWS.'/'.self::getControllerName().'/'.$this->view->template.'.tpl.php');
	}

	protected function getControllerName()
	{
		return strtolower(str_replace('Controller', '', get_class($this)));
	}
	/**
	function renderTemplate($template, $controller = null)
	{
		if($controller == null) $controller = App::router(0);

		if($template == 'layout')
		{
			$path = DEFAULT_LAYOUT;
		}
		else
		{
			$path = 'app/views/'.$controller.'/'.$template.'.tpl.php';
		}
		$app = $this->app;
		ob_start();
		include $path;
		$result = ob_get_contents();
		ob_end_clean();
		return $result;
	}
	 */

	function user_access_level()
	{
		return $this->user_access_level;
	}

	protected function setTitle($title)
	{
		$this->view->title = $title;
	}

	protected function setContentTitle($title)
	{
		$this->view->content->title = $title;
	}

	protected function setViewTemplate($template)
	{
		$this->view->template = $template;
	}

	/**
	 * @param Session $session
	 * @return Session
	 */
	protected function session(Session $session = null)
	{
		if(!is_null($session))
		{
			$this->app->session = $session;
		}

		return $this->app->session;
	}
}