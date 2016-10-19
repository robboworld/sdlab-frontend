<?php
/**
 * Class ApiController
 */
class ApiController extends Controller
{
	/**
	 * Current performed API method.
	 *
	 * @var    array
	 */
	protected $method;

	/**
	 * Array of arguments for performed API method
	 *
	 * @var    array
	 */
	protected $params = array();

	/**
	 * Subcontroller with called API methods
	 *
	 * @var    Controller
	 */
	protected $controller;

	/**
	 * JSON encoded result
	 *
	 * @var    string
	 */
	protected $json_result;

	/**
	 * JSON encoded error message
	 *
	 * @var    string
	 */
	protected $json_error;

	public function __construct($action = 'api', $config = array('default_action' => 'api'))
	{
		parent::__construct($action, $config);

		// Register the methods as actions.
		$this->registerAction('api', 'api');
		$this->unregisterAction('index');

		$method_query = explode('.', isset($_GET['method']) ? $_GET['method'] : '');

		// Check values
		$method_query[0] = isset($method_query[0]) ? System::cleanVar($method_query[0], 'class')  : '';
		$method_query[1] = isset($method_query[1]) ? System::cleanVar($method_query[1], 'method') : '';

		$api_method_class = $method_query[0].'Controller';

		if (class_exists($api_method_class))
		{
			$this->controller = new $api_method_class;
			$this->method = strlen((string)$method_query[1]) != 0 ? (string)$method_query[1] : null;
			$this->params = isset($_GET['params']) ? $_GET['params'] : array();
		}
	}

	/**
	 * Execute controllers API methods
	 * Prepare results in json in format:
	 *     {result:data,...} on success
	 *     OR
	 *     {error:text} on error
	 * 
	 * Fields:
	 *     - result: Data in json format
	 *     - error: Error text, may be empty text
	 * Other fields optional.
	 */
	public function api()
	{
		if (!is_null($this->controller) && !is_null($this->method)
				&& in_array(strtolower($this->method), $this->controller->getMAPIs())
		)
		{
			// Inject App in called controller
			// xxx: cannot do that in constructor of new sub controller, because it creates when no binded $this->app in the current api controller!
			$this->controller->app = $this->app;


			// Call method
			$result = $this->controller->executeAPI($this->method, $this->params);

			if($result && isset($result['result']))
			{
				$this->json_result = json_encode($result);
			}
			else
			{
				/*todo: errno & errstring */
				$api_error = array(
					'error' => $this->controller->error()
				);
				$this->json_error = json_encode($api_error);
			}
		}
		else
		{
			$api_error = array(
				'error' => L('ERROR_METHOD_NOT_EXIST')
			);
			$this->json_error = json_encode($api_error);
		}
	}

	/**
	 * Override default render method.
	 * JSON output.
	 */
	public function renderView()
	{
		// Execute
		$this->api();

		// Output json
		header('Content-Type: application/json');
		if(!isset($this->json_error) && isset($this->json_result))
		{
			echo $this->json_result;
		}
		else
		{
			echo $this->json_error;
		}
	}
}
