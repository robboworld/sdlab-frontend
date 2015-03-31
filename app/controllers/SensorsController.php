<?

class SensorsController extends Controller
{

	function __construct()
	{
		parent::__construct();
		$this->config = App::config();
	}
	function index()
	{
		$this->addJs('functions');
		$this->addJs('chart');
		$this->addJs('Sensor');
		$this->addJs('sensors');
		$this->addCss('sensors');
		//$this->view->content->sensors_list = $this->sensorList($this->getSensors()->Values);
		$this->view->content->sensors_list = 'test inc';
		$this->view->template = 'index';

 		//$this->view->content = $this->renderTemplate('index');
		// todo: удалить всё лишнее в этом контроллере, заточить только под использование через API
	}

	/**
	 * @return array|bool
	 * API method: Sensors.getSensors
	 * API params: null
	 */
	function getSensors()
	{
		$socket = new JSONSocket('/run/sdlab.sock');
		$result = $socket->call('Lab.ListSensors', array(false));
		//var_dump($socket->error());
		//var_dump($result);
		if($result)
		{
			return $result; //лишнее вложение в массиве
		}
		else
		{
			return false;
		}

	}

	/**
	 * @param $params
	 * @return bool
	 * API method: Sensors.getData
	 * API params: Sensor, ValueIdx
	 */
	function getData($params)
	{
		$socket = new JSONSocket($this->config['socket']['path']);
		$result = $socket->call('Lab.GetData', (object) array(
			'Sensor' => $params['Sensor'],
			'ValueIdx' => (int) $params['ValueIdx']
		));
		return $result;
	}

	function experimentStrob($params)
	{
		if(!empty($params['experiment']))
		{
			$experiment = (new Experiment())->load($params['experiment']);
			if(!empty($experiment->setup_id))
			{


				/* получаем датчики для эксперимента */
				$sensors = SetupController::getSensors($experiment->setup_id);
				if(!empty($sensors))
				{
					/**/
					$setup = (new Setup())->load($experiment->setup_id);
					if(!$setup->flag)
					{
						$setup->set('flag', true);
						$setup->save();
					}

					/* формируем список сенсоров для метода апи датчиков*/
					$params_array = array();
					foreach($sensors as $sensor)
					{
						$params_array[] = (object) array(
							'Sensor' => $sensor->id,
							'ValueIdx' => 0
						);

					}

					/* формируем массив параметров для метода апи датчиков*/
					$query_params = array(
						'Values' => $params_array,
						'Period' => System::nano(1),
						'Count' => 1
					);

					/* отправляем запрос на создание серии из одного измерения */
					$socket = new JSONSocket($this->config['socket']['path']);
					$respond = $socket->call('Lab.StartSeries', (object) $query_params);

					/* если апи возвращает true то пытаемся получить результаты*/
					if($respond)
					{
						/* Ждем 2 секунды для получения результатов*/
						sleep(2);

						/* новый сокет для получения результатов*/
						unset($socket);
						$socket = new JSONSocket($this->config['socket']['path']);

						$result = $socket->call('Lab.GetSeries', null);
						if(!empty($result))
						{
							$db = new DB();
							$insert = $db->prepare('insert into detections (exp_id, time, id_sensor, detection, error) values (:exp_id, :time, :id_sensor, :detection, :error)');

							for($i = 0; $i < count($sensors); $i++)
							{
								$sensor_error =  $result[0]->Readings[$i] == 'NaN' ? 'NaN' : null;

								$insert->execute(array(
									':exp_id' => $experiment->id,
									':time' => $result[0]->Time,
									':id_sensor' => $sensors[$i]->id,
									':detection' => $result[0]->Readings[$i],
									':error' => $sensor_error
								));
							}
							/*

							$db->exec('insert ');
							*/
							//var_dump($result[0]->Readings);
							return array('result' => true);
						}
						else
						{
							$this->error = 'Empty response';
						}
					}
					else
					{
						$this->error = 'Series not started';

					}

				}
			}

		}

	}

	private function sensorList(array $sensors, $class = null)
	{
		if($sensors)
		{
			$list = '';
			$class_string = 'list-group-item';

			if($class != null && is_array($class))
			{
				$class_string = implode(' ', $class);
			}

			foreach($sensors as $item)
			{
				$list .= '<a class="'.$class_string.'" href="#" data-id="'.$item->id.'">'.$item->title.'</a>';
			}
			return $list;
		}
		else return false;
	}
}

