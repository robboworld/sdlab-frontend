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


	/**
	 * Get detections data for plot.
	 * API method: Detections.getGraphData
	 * API params: plot
	 *
	 * @param  array  $params  Array of parameters:
	 *                           plot  - id of plot
	 *
	 * @return array  Result in form array('result' => array of objects) or False on error
	 */
	function getGraphData($params)
	{
		if($plot = (new Plot())->load($params['plot']))
		{
			$experiment = (new Experiment())->load($plot->exp_id);
			$setup = (new Setup())->load($experiment->setup_id);

			$detections_query = $this->db->prepare('select * from detections where exp_id = :experiment_id and sensor_id = :sensor_id and sensor_val_id = :sensor_val_id');

			$ordinate_query = $this->db->query('select ordinate.*, setup_conf.name, setup_conf.sensor_id, setup_conf.sensor_val_id from ordinate left join setup_conf on setup_conf.setup_id = '.(int)$setup->id.' AND setup_conf.sensor_id = ordinate.id_sensor_y AND setup_conf.sensor_val_id = ordinate.sensor_val_id_y where id_plot = '.(int)$plot->id.' ', PDO::FETCH_OBJ);
			//System::dump($ordinate_query);
			if($ordinate_query)
			{
				$result = array();
				foreach($ordinate_query as $item)
				{
					//System::dump($item);
					$sensor_select = $detections_query->execute(array(
						':experiment_id' => $experiment->id,
						':sensor_id' => $item->sensor_id,
						':sensor_val_id' => $item->sensor_val_id
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


	/**
	 * Get experiment detections for timeseries graph.
	 * API method: Detections.getGraphDataAll
	 * API params: experiment, show-sensor[]
	 * 
	 * @param  array  $params  Array of parameters:
	 *                           experiment  - id of experiment,
	 *                           show-sensor - list of sensors identificators strings in format "sensor_id + # + value_id"
	 * 
	 * @return array  Result in form array('result' => array of objects) or False on error
	 */
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
				$query = 'select a.sensor_id, a.sensor_val_id, '
							. 's.value_name, s.si_notation, s.si_name, s.max_range, s.min_range, s.resolution '
						. 'from detections as a '
						. 'left join sensors as s on a.sensor_id = s.sensor_id and a.sensor_val_id = s.sensor_val_id '
						. 'where a.exp_id = :exp_id '
						. 'group by a.sensor_id, a.sensor_val_id order by a.sensor_id';
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
					$key = '' . $sensor->sensor_id . '#' . (int)$sensor->sensor_val_id;
					if(!array_key_exists($key, $available_sensors))
					{
						$available_sensors[$key] = $sensor;
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
						. 'where exp_id = :exp_id and sensor_id = :sensor_id and sensor_val_id = :sensor_val_id and (error isnull or error = \'\')'
						. 'order by strftime(\'%s\', time)';
				$load = $db->prepare($query);

				$result = array();
				$i = 0;
				foreach($displayed_sensors as $sensor)
				{
					$data = new stdClass();
					// TODO: add to label name of sensor from setup_info (but unknown setup id for each detection, setup can be changed)
					$data->label         = empty($sensor->value_name) ? 'неизвестный' : $sensor->value_name ;
					$data->sensor_id     = $sensor->sensor_id;
					$data->sensor_val_id = $sensor->sensor_val_id;
					$data->color         = ++$i;

					$res = $load->execute(array(
							':exp_id'        => $experiment->id,
							':sensor_id'     => $sensor->sensor_id,
							':sensor_val_id' => $sensor->sensor_val_id
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
