<?php
/**
 * Class Controller
 *
 * Base controller class
 */
class Controller
{
	/**
	 * Application instance
	 * 
	 * @var App
	 */
	public $app;

	/**
	 * View data
	 * 
	 * @var mixed
	 */
	public $view;

	/**
	 * Error data
	 *
	 * @var mixed
	 */
	public $error;

	/**
	 * The name of the controller
	 *
	 * @var    array
	 */
	protected $name;

	/**
	 * Current or most recently performed action.
	 *
	 * @var    string
	 */
	protected $action;

	/**
	 * The mapped action that was performed.
	 *
	 * @var    string
	 */
	protected $doAction;

	/**
	 * Array of class methods
	 *
	 * @var    array
	 */
	protected $methods;

	/**
	 * Array of public class methods to call for a given action
	 *
	 * @var    array
	 */
	protected $actionsMap;

	/**
	 * Array of class methods to call for a given API action.
	 * Special actions called only through special api controller.
	 *
	 * @var    array
	 */
	protected $methodsAPIMap;

	/**
	 * User access level for controller.
	 * This is minimal access level needed for access to controller actions.
	 *   0 - guest
	 *   1 - registered (default)
	 *   3 - admin
	 * @var integer
	 */
	protected $user_access_level = 1;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 * Recognized key values include: 'default_action'.
	 */
	public function __construct($action = 'index', $config = array())
	{
		$this->actions = array();
		$this->methods = array();

		// Determine the methods to exclude from the base class.
		$xMethods = get_class_methods('Controller');

		// Get the public methods in this class using reflection.
		$r = new ReflectionClass($this);
		$rMethods = $r->getMethods(ReflectionMethod::IS_PUBLIC);

		// Collect all available methods
		foreach ($rMethods as $rMethod)
		{
			$mName = $rMethod->getName();

			// Add default display method if not explicitly declared.
			if (!in_array($mName, $xMethods) || $mName == 'index')
			{
				$this->methods[] = strtolower($mName);

				// Auto register the methods as actions.
				//$this->actionsMap[strtolower($mName)] = $mName;
			}
		}

		// Register the methods as actions.
		$this->actionsMap['index'] = 'index';

		// If the default action is set, register it as such
		if (array_key_exists('default_action', $config))
		{
			$this->registerDefaultAction($config['default_action']);
		}
		else
		{
			$this->registerDefaultAction('index');
		}

		$this->action = strtolower(System::cleanVar($action, 'method'));

		$this->view = new stdClass();
		$this->view->content = new stdClass();
	}

	/**
	 * Register the default action to perform if a mapping is not found.
	 *
	 * @param   string  $action  The name of the method in the derived class to perform if a named action is not found.
	 *
	 * @return  Controller  A Controller object to support chaining.
	 */
	public function registerDefaultAction($action)
	{
		$this->registerAction('__default', $action);

		return $this;
	}

	/**
	 * Register (map) an action to a method in the class.
	 *
	 * @param   string  $action  The action.
	 * @param   string  $method  The name of the method in the derived class to perform for this action.
	 *
	 * @return  Controller  A Controller object to support chaining.
	 */
	public function registerAction($action, $method)
	{
		// todo: cannot register action if exists such method api
		if (in_array(strtolower($method), $this->methods))
		{
			$this->actionsMap[strtolower($action)] = $method;
		}

		return $this;
	}

	/**
	 * Unregister (unmap) an action in the class.
	 *
	 * @param   string  $action    The action.
	 *
	 * @return  Controller  This object to support chaining.
	 */
	public function unregisterAction($action)
	{
		unset($this->actionsMap[strtolower($action)]);

		return $this;
	}

	/**
	 * Register (map) an api method to a method in the class.
	 *
	 * @param   string  $mapi       The api method.
	 * @param   string  $method     The name of the method in the derived class to perform for this api method.
	 *
	 * @return  Controller  A Controller object to support chaining.
	 */
	public function registerMAPI($mapi, $method)
	{
		// todo: cannot register methodapi if exists such action
		if (in_array(strtolower($method), $this->methods))
		{
			$this->methodsAPIMap[strtolower($mapi)] = $method;
		}

		return $this;
	}

	/**
	 * Unregister (unmap) api method in the class.
	 *
	 * @param   string  $mapi  The api method.
	 *
	 * @return  Controller  This object to support chaining.
	 */
	public function unregisterMAPI($mapi)
	{
		unset($this->methodsAPIMap[strtolower($mapi)]);

		return $this;
	}

	/**
	 * Gets the all available methods in the controller.
	 *
	 * @return  array  Array[i] of method names.
	 */
	public function getMethods()
	{
		return $this->methods;
	}

	/**
	 * Gets the registered actions in the controller.
	 *
	 * @return  array  Array[i] of action names.
	 */
	public function getActions()
	{
		return array_keys($this->actionsMap);
	}

	/**
	 * Gets the registered API methods in the controller.
	 *
	 * @return  array  Array[i] of API method names.
	 */
	public function getMAPIs()
	{
		return array_keys($this->methodsAPIMap);
	}

	public function index()
	{
		// default method
	}

	public function error()
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
				$script_list .= "\n\t" . '<script type="text/javascript" src="assets/js/'.$script.'.js"></script>';
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
				$css_list .= "\n\t" . '<link rel="stylesheet" href="assets/css/'.$css_file.'.css">';
			}
			return $css_list;
		}
	}

	private function genJsLang()
	{
		$script_lang = '';

		// Generate script language declarations.
		if (count(Language::script()))
		{
			$script_lang .= '<script type="text/javascript">';
			$script_lang .=     '(function() {';
			$script_lang .=         'var strings = ' . json_encode(Language::script()) . ';';
			$script_lang .=         'if (typeof SDLab == \'undefined\') {';
			$script_lang .=             'SDLab = {};';
			$script_lang .=             'SDLab.Language = strings;';
			$script_lang .=         '}';
			$script_lang .=         'else {';
			$script_lang .=             'SDLab.Language.load(strings);';
			$script_lang .=         '}';
			$script_lang .=     '})();';
			$script_lang .= '</script>';
		}

		return $script_lang;
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

	/**
	 * Execute a render action by triggering a method in the derived class.
	 * If no matching action is found, the '__default' action is executed, if defined.
	 *
	 * @throws  Exception
	 */
	public function renderView()
	{
		if (isset($this->actionsMap[$this->action]))
		{
			$doAction = $this->actionsMap[$this->action];
		}
		elseif (isset($this->actionsMap['__default']))
		{
			$doAction = $this->actionsMap['__default'];
		}
		else
		{
			throw new Exception('unknown controller action: ' . $this->action, 404);
		}

		// Record the actual action being fired
		$this->doAction = $doAction;

		$this->{$doAction}();

		// TODO: get default base layout by controller config option
		include(VIEWS.'/layout.tpl.php');
	}

	public function render()
	{
		if(!isset($this->view->template))
		{
			$this->view->template = $this->action;
		}

		$tpl_path = VIEWS.'/'.self::getControllerName().'/'.$this->view->template.'.tpl.php';
		if (file_exists($tpl_path))
		{
			include($tpl_path);
		}
	}

	/**
	 * Execute a API method by triggering a method in the derived class.
	 * There is no default method.
	 *
	 * @param   string  $method  The called API method name.
	 * @param   array   $params  The perameters for API method.
	 *
	 * @return  mixed   The value returned by the called method, false in error case.
	 *
	 * @throws  Exception
	 */
	public function executeAPI($method, $params)
	{
		$m = strtolower($method);
		if (isset($this->methodsAPIMap[$m]))
		{
			$callMethod = $this->methodsAPIMap[$m];
		}
		else
		{
			throw new Exception(L('ERROR_METHOD_NOT_EXIST') . '(' . $method . ')', 404);
		}

		return $this->{$callMethod}($params);
	}

	protected function getControllerName()
	{
		return strtolower(str_replace('Controller', '', get_class($this)));
	}

	/*
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

	public function getUserAccessLevel()
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
