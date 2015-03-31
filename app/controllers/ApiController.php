<?

class ApiController extends Controller
{
	function __construct()
	{

		$method_query = explode('.', $_GET['method']);
		$api_method_class = $method_query[0].'Controller';
		$this->controller = new $api_method_class;
		$this->method = $method_query[1];
		$this->params = $_GET['params'];


	}

	function api()
	{
		if(method_exists($this->controller, $this->method))
		{

			$result = $this->controller->{$this->method}($this->params);

			if($result)
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
				'error' => 'Method not exist.'
			);
			$this->json_error = json_encode($api_error);
		}

	}

	/**
	 * Override default render method
	 */
	function renderView()
	{
		/* execute */
		$this->api();
		header('Content-Type: application/json');
		if(!isset($this->json_error) && isset($this->json_result))
		{
			print $this->json_result;
		}
		else
		{
			print $this->json_error;
		}
	}
}