<?


/**
 * Class DetectionsController
 */
class DetectionsController extends Controller
{
	function __construct()
	{
		parent::__construct();
		$this->config = App::config();
	}


	function getGraphData($params)
	{
		if($plot = (new Plot())->load($params['plot']))
		{
			$experiment = (new Experiment())->load($plot->exp_id);
			$setup = (new Setup())->load($experiment->setup_id);

			$detections_query = $this->db->prepare('select * from detections where exp_id = :experiment_id and id_sensor = :sensor_id');

			$ordinate_query = $this->db->query('select ordinate.*, setup_conf.name, setup_conf.sensor_id from ordinate left join setup_conf on setup_conf.setup_id = '.(int)$setup->id.' AND setup_conf.sensor_id = ordinate.id_sensor_y where id_plot = '.(int)$plot->id.' ', PDO::FETCH_OBJ);
			//System::dump($ordinate_query);
			if($ordinate_query)
			{
				$result = array();
				foreach($ordinate_query as $item)
				{
					//System::dump($item);
					$sensor_select = $detections_query->execute(array(
						':experiment_id' => $experiment->id,
						':sensor_id' => $item->sensor_id
					));
					if($sensor_select)
					{
						$sensor_data = $detections_query->fetchAll(PDO::FETCH_OBJ);
						$graph_object = new StdClass();
						$graph_object->label = $item->name;
						$graph_object->color = ++$i;
						foreach($sensor_data as $point)
						{
							$time = explode('.', $point->time);
							$time = new DateTime($time[0]);
							$graph_object->data[] = array(
								$time->getTimestamp()*1000,
								$point->detection
							);
						}
						$result[] = $graph_object;
					}
				}
				return $result ? $result : false;
			}
			return false;
		}
		else
		{
			$this->error = 'Графика не существует.';
		}
	}


	function getGraphDataAll($params)
	{
		if(!empty($params['experiment']))
		{
			$experiment = (new Experiment())->load($params['experiment']);
			if(!empty($experiment->setup_id))
			{
				// Check access to experiment
				if(!($experiment->session_key == $this->session()->getKey() || $this->session()->getUserLevel() == 3))
				{
					$this->error = 'Access denied';

					return false;
				}

				$sensors_show = array();
				if(isset($params['show-sensor']) && !empty($params['show-sensor']) && is_array($params['show-sensor']))
				{
					foreach($params['show-sensor'] as $sensor_show_id)
					{
						$sensors_show[$sensor_show_id] = $sensor_show_id;
					}
				}

				$db = new DB();

				// Get unique sensors list from detections data of experiment
				$query = 'select a.id_sensor as sensor_id, '
							. 's.value_name, s.si_notation, s.si_name, s.max_range, s.min_range, s.resolution '
						. 'from detections as a '
						. 'left join sensors as s on a.id_sensor = s.sensor_id '
						. 'where a.exp_id = :exp_id '
						. 'group by a.id_sensor order by a.id_sensor';
				$load = $db->prepare($query);
				$load->execute(array(
						':exp_id' => $experiment->id
				));
				$sensors = $load->fetchAll(PDO::FETCH_OBJ);
				if(empty($sensors))
				{
					$sensors = array();
				}

				$available_sensors = $displayed_sensors = array();

				// Prepare available_sensors list
				foreach($sensors as $sensor)
				{
					if(!array_key_exists($sensor->sensor_id, $available_sensors))
					{
						$available_sensors[$sensor->sensor_id] = $sensor;
					}
				}
				//$result['available_sensors'] = $available_sensors;

				// Filter requested sensors
				if(!empty($sensors_show))
				{
					$displayed_sensors = array_intersect_key($available_sensors, $sensors_show);
				}
				else
				{
					$displayed_sensors = $available_sensors;
				}

				// XXX: return time in msec (>PHP_INT_MAX)
				$query = //'select strftime(\'%Y.%m.%d %H:%M:%f\', time) as time, detection '
						 'select (strftime(\'%s\',time) - strftime(\'%S\',time) + strftime(\'%f\',time))*1000 as time, detection '
						. 'from detections '
						. 'where exp_id = :exp_id and id_sensor = :id_sensor and (error is null or error = \'\')'
						. 'order by strftime(\'%s\', time)';
				$load = $db->prepare($query);

				$result = array();
				$i = 0;
				foreach($displayed_sensors as $sensor)
				{
					$data = new stdClass();
					$data->label = empty($sensor->value_name) ? 'неизвестный' : $sensor->value_name ;
					$data->sensor_id = empty($sensor->sensor_id) ? 'unknown' : $sensor->sensor_id ;
					$data->color = ++$i;

					$res = $load->execute(array(
							':exp_id'    => $experiment->id,
							':id_sensor' => $sensor->sensor_id,
					));
					$detections = $load->fetchAll(PDO::FETCH_NUM);

					if(!empty($detections))
					{
						$data->data = $detections;
						foreach ($data->data as $k => $val)
						{
							$t = (string)$val[0];
							$dotpos = strpos($t,'.');
							if ($dotpos !== false )
							{
								// cut fractional part with dot from time in msec (14235464000.0 -> 14235464000)
								$data->data[$k][0] = substr($t, 0, $dotpos);
							}
						}
					}
					else 
					{
						$data->data = array();
					}

					$result[] = $data;
/*
					foreach($sensor_data as $point)
					{
						$time = explode('.', $point->time);
						$time = new DateTime($time[0]);
						$graph_object->data[] = array(
								$time->getTimestamp()*1000,
								$point->detection
						);
					}
*/
				}
				return array('result' => $result);
			}
			else
			{
				$this->error = 'Experiment not found';

				return false;
			}
		}
		else
		{
			$this->error = 'Experiment not found';
		
			return false;
		}

		return false;
	}


	function error()
	{
		return $this->error;
	}
}
