<?
include('class/JsonSocket.php');

$q = $_GET;
if(isset($q['method']) && $q['method'] != '')
{
	$method = $q['method'];
	$params = $q['params'];

	/*приведение к типу int*/
	if($method == 'Arith.Multiply' || $method == 'Arith.Divide')
	{
		foreach($params as $key => $value)
		{
			$params[$key] = (int) $value;
		}
	}
	$socket = new JSONSocket('/tmp/testsocket');
	$result = $socket->call($method, (object)$params);
	if($result!='')
	{
		print $result;
	}
	else
	{
		/*todo: errno & errstring */
		$api_error = array(
			'error' => $socket->lastErrorString()
		);
		print json_encode($api_error);
	}
}