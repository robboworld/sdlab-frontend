<?php
/**
 * Class JSONSocket
 * 
 * Connection with backend through socket (jsonrpc calls)
 */
class JSONSocket
{
	const ENOTSOCK     = 88;
	const ENOTSOCK_STR = 'Socket operation on non-socket';

	public $socket;
	private $buffer_size = 50; // unused
	private $error;
	private $errorno;
	private $request;

	public function __construct($path)
	{
		//$this->socket = socket_create(AF_UNIX, SOCK_STREAM, null);
		//socket_connect($this->socket, $path);
		//$this->error = socket_last_error($this->socket);
		//socket_set_block($this->socket);
		$this->socket = fsockopen('unix://'.$path, 0, $this->errorno, $this->error);
		if (!$this->socket)
		{
			error_log('Error fsockopen(): ' . $this->errorno . ' - ' . $this->error); //DEBUG
		}
	}

	public function __destruct()
	{
		if(!empty($this->socket) && is_resource($this->socket))
		{
			fclose($this->socket);
		}
	}

	public function call($method, $params)
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

		$result = $this->socketWrite($request);
		if ($result === false)
		{
			return false;
		}

		return $this->socketReceive();
	}

	private function socketWrite($request)
	{
		//socket_write($this->socket, json_encode($request)."\n");
		//$this->error = socket_last_error($this->socket);

		if (empty($this->socket) || !is_resource($this->socket))
		{
			$this->errorno = self::ENOTSOCK;
			$this->error   = self::ENOTSOCK_STR;
			error_log('Error socketWrite(): ' . $this->error); //DEBUG

			return false;
		}

		return fwrite($this->socket, json_encode($request)."\n");
	}

	private function socketReceive()
	{
		if(!$this->socket)
		{
			$this->errorno = self::ENOTSOCK;
			$this->error   = self::ENOTSOCK_STR;
			error_log('Error socketReceive(): ' . $this->error); //DEBUG

			return false;
		}

		$result = fgets($this->socket);
		if(!empty($this->socket) && is_resource($this->socket))
		{
			fclose($this->socket);
		}
		$object = json_decode($result);
		if(is_object($object))
		{
			if($object->error)
			{
				error_log('Error socketReceive():'.var_export($object,true)); //DEBUG
				return false;
			}
			//if($object->result)  //xxx: not works with empty values
			//if(isset($object->result))  //xxx: not works with null
			if(property_exists($object, 'result'))
			{
				return array(
						'result' => $object->result
				);
			}
		}
		else
		{
			error_log('Error output socketReceive():'.var_export($result,true)); //DEBUG
			return false;
		}

		return false;
	}

	public function error()
	{
		if (!$this->errorno)
		{
			return null;
		}

		return array(
			'no'     => $this->errorno,
			'string' => $this->error
		);
	}

	public static function availableTransport()
	{
		return stream_get_transports();
	}
}