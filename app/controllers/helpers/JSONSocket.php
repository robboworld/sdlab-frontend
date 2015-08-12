<?

/**
 *
 */

class JSONSocket
{
	public $socket;
	private $buffer_size = 50; // unused
	private $error;
	private $errorno;
	private $request;

	function __construct($path)
	{
		//$this->socket = socket_create(AF_UNIX, SOCK_STREAM, null);
		//socket_connect($this->socket, $path);
		//$this->error = socket_last_error($this->socket);
		//socket_set_block($this->socket);
		$this->socket = fsockopen('unix://'.$path,0, $this->errorno, $this->error);
	}

	function __destruct()
	{
		if(!empty($this->socket) && is_resource($this->socket)) fclose($this->socket);
	}

	function call($method, $params)
	{
		$request = new stdClass();
		$request->jsonrpc = '2.0';
		$request->id = time();


		$request->method = $method;
		if(is_array($params))
		{
			$request->params = $params;
		}
		else if(is_object($params))
		{
			$request->params[] = $params;
		}
		else if(is_null($params))
		{
			$request->params[] = null;
		}

		$this->socketWrite($request);
		return $this->socketReceive();
	}

	private function socketWrite($request)
	{

		//socket_write($this->socket, json_encode($request)."\n");
		//$this->error = socket_last_error($this->socket);

		fwrite($this->socket, json_encode($request)."\n");
	}
	private function socketReceive()
	{
		if($this->socket)
		{

			$result = fgets($this->socket);
			if(!empty($this->socket) && is_resource($this->socket)) fclose($this->socket);
			$object = json_decode($result);
			if(is_object($object))
			{
				if($object->error)
				{
					error_log('Error socketReceive():'.var_export($object,true)); //DEBUG
					return false;
				}
				if($object->result)
				{
					return $object->result;
				}
			}
			else 
			{
				error_log('Error output socketReceive():'.var_export($result,true)); //DEBUG
				return false;
			}
		}
		else
		{
			return false;
		}

		return false;
	}

	function error()
	{
		return array(
			'no' => $this->errorno,
			'string' => $this->error
		);
	}

	static function availableTransport()
	{
		return stream_get_transports();
	}
}