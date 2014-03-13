<?

class Controller
{
	function __construct($action = 'index')
	{
		$this->action = $action;
		define('DEFAULT_HTML_TEMPLATE', 'templates/html.tpl.php');
		define('DEFAULT_LAYOUT', 'templates/layout.tpl.php');
	}

	function index()
	{
		// default method
	}

	private function collectJs()
	{
		if(isset($this->js) && is_array($this->js))
		{
			$script_list = '';
			foreach($this->js as $script)
			{
				$script_list .= '<script type="text/javascript" src="js/'.$script.'"></script>';
			}
			return $script_list;
		}
	}

	private function addJs(String $filename)
	{
		$this->js[] = $filename;
	}

	function renderView()
	{

		if(isset($this->action) && method_exists($this, $this->action))
		{
			$this->{$this->action}();
		}
		else
		{
			$this->index();
		}

		$layout = $this->renderTemplate('layout');
		include(DEFAULT_HTML_TEMPLATE);
	}

	function renderTemplate($template)
	{
		if($template == 'layout')
		{
			$path = DEFAULT_LAYOUT;
		}
		else
		{
			$path = 'templates/'.App::router(0).'/'.$template.'.tpl.php';
		}
		$content = $this->content;
		ob_start();
		include $path;
		$result = ob_get_contents();
		ob_end_clean();
		return $result;
	}
}