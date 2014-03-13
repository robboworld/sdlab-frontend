<?

/**
 *
 */

class JSONSocket
{
	public $socket;
	private $buffer_size = 1024;
	public $error;
	private $request;

	function __construct($path)
	{
		$this->socket = socket_create(AF_UNIX, SOCK_STREAM, null);
		socket_connect($this->socket, $path);
		$this->error = socket_last_error($this->socket);
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

		$this->socketWrite($request);

		return $this->socketReceive();
	}

	private function socketWrite($request)
	{
		socket_write($this->socket, json_encode($request));
		$this->error = socket_last_error($this->socket);
	}
	private function socketReceive()
	{
		$result = '';
		do
		{
			$bytes = socket_recv($this->socket, $data, $this->buffer_size, MSG_DONTWAIT);
			$result .= $data;
		}
		while($data!=null);

		return $result;
	}
	function lastErrorString()
	{
		return socket_strerror($this->error);
	}

	static function availableTransport()
	{
		return stream_get_transports();
	}
}