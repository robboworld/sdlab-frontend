<?php
/**
 * Class DetectionsController
 */
class DetectionsController extends Controller
{
	public function __construct($action = 'index', $config = array())
	{
		parent::__construct($action, $config);

		$this->user_access_level = 0;

		// Register the methods as actions.
		// NO RENDER VIEW ACTIONS?
		$this->unregisterAction('index');
		$this->unregisterAction('__default');

		// Register the methods as API methods.
		$this->registerMAPI('getGraphSingleData', 'getGraphSingleData');
		$this->registerMAPI('getGraphData', 'getGraphData');
		$this->registerMAPI('getScatterData', 'getScatterData');
		$this->registerMAPI('delete', 'delete');
		$this->registerMAPI('deletebytime', 'deletebytime');

		// Get Application config
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
	public function getGraphSingleData($params)
	{
		if(empty($params['plot']))
		{
			$this->error = L('ERROR_GRAPH_NOT_EXIST');

			return false;
		}

		// Load plot
		$plot = (new Plot())->load($params['plot']);
		if(!$plot)
		{
			$this->error = L('ERROR_GRAPH_NOT_EXIST');

			return false;
		}

		// Load experiment
		$experiment = (new Experiment())->load($plot->exp_id);
		if(!$experiment)
		{
			$this->error = L('ACCESS_DENIED');

			return false;
		}

		// Check access to experiment
		if(!$experiment->userCanView($this->session()))
		{
			$this->error = L('ERROR_EXPERIMENT_NOT_FOUND');

			return false;
		}

		// Load setup
		$setup = (new Setup())->load($experiment->setup_id);
		if(!$setup)
		{
			$this->error = L('ERROR_SETUP_NOT_FOUND');

			return false;
		}


		// TODO: not join with setup because may be orphaned (can change current setup in experiment)

		// TODO: add setup_id to plot table for link to setup conf and sensors naming

		$db = new DB();

		$detections_query = $db->prepare('select * from detections where exp_id = :experiment_id and sensor_id = :sensor_id and sensor_val_id = :sensor_val_id');

		$ordinate_query = $db->query(
				'select ordinate.*, setup_conf.name, setup_conf.sensor_id, setup_conf.sensor_val_id '
					. 'from ordinate '
					. 'left join setup_conf on setup_conf.setup_id = '.(int)$setup->id.' AND setup_conf.sensor_id = ordinate.id_sensor_y AND setup_conf.sensor_val_id = ordinate.sensor_val_id_y '
					. 'where id_plot = '.(int)$plot->id.' '
				, PDO::FETCH_OBJ
		);
		//System::dump($ordinate_query);
		if($ordinate_query)
		{
			// Get current timezone offset in seconds to correct timestamps
			$tz_offset = (new DateTime())->format('Z');

			$result = array();
			$i = 0;
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
							($time->getTimestamp() + $tz_offset)*1000,  // convert to localtime and to milliseconds
							$point->detection
							// TODO: check $point->error for error text (NaN) and $point->detection for empty value (NULL)
						);
					}
					$result[] = $graph_object;
				}
			}

			return isset($result) ? array('result' => $result) : false;
		}

		return false;
	}

	/**
	 * Get experiment detections for timeseries plot with datetime filter.
	 * API method: Detections.getGraphData
	 * API params: experiment, show-sensor[], from, to, exclude
	 *
	 * @param  array  $params  Array of parameters:
	 *                           experiment  - id of experiment,
	 *                           show-sensor - list of sensors identificators strings in format "sensor_id + # + value_id"
	 *                           from, to    - datetime in ISO format (RFC3339 with nanoseconds) "Y-m-dTH:i:s.uZ"
	 *                           exclude     - integer, set 1 for exclude from-to datetime data from result, default 0.
	 *
	 * @return array  Result in form array('result' => array of objects) or False on error
	 */
	public function getGraphData($params)
	{
		if(empty($params['experiment']))
		{
			$this->error = L('ERROR_EXPERIMENT_NOT_FOUND');

			return false;
		}

		// Load experiment
		$experiment = (new Experiment())->load($params['experiment']);
		if(!$experiment)
		{
			$this->error = L('ERROR_EXPERIMENT_NOT_FOUND');

			return false;
		}

		// Check access to experiment
		if(!$experiment->userCanView($this->session()))
		{
			$this->error = L('ACCESS_DENIED');

			return false;
		}

		// Get filter parameters
		// Filter sensors
		$sensors_show = array();
		if(isset($params['show-sensor']) && !empty($params['show-sensor']) && is_array($params['show-sensor']))
		{
			foreach($params['show-sensor'] as $sensor_show_id)
			{
				$sensors_show[$sensor_show_id] = $sensor_show_id;
			}
		}
		// Filter datetimes
		$from = null;
		if(isset($params['from']) && strlen($params['from']) != 0)
		{
			// UTC time with seconds parts
			try
			{
				$from = System::datemsecformat($params['from'], System::DATETIME_RFC3339NANO_UTC, 'UTC');  // XXX: Cannot use DateTime, because only 6 milli digits supported.
			}
			catch (Exception $e)
			{
				// error on invalid format
				$this->error = L('ERROR_INVALID_PARAMETERS');

				return false;
			}
		}
		$to = null;
		if(isset($params['to']) && strlen($params['to']) != 0)
		{
			// UTC time with seconds parts
			try
			{
				$to = System::datemsecformat($params['to'], System::DATETIME_RFC3339NANO_UTC, 'UTC');  // XXX: Cannot use DateTime, because only 6 milli digits supported.
			}
			catch (Exception $e)
			{
				// error on invalid format
				$this->error = L('ERROR_INVALID_PARAMETERS');

				return false;
			}
		}

		$exclude = false;
		if(isset($params['exclude']))
		{
			$exclude = (int)$params['exclude'] ? true : false;
		}

		$db = new DB();

		// Init arrays of sensors
		$det_sensors   = array();
		$setup_sensors = array();
		$mon_sensors   = array();
		$reg_sensors   = array();


		// Speed up db operations within transaction
		$db->beginTransaction();
		try
		{
			// Get list of sensors available in detections
			// (already used sensors)

			// Get unique sensors list from detections data of experiment
			$sql = 'select DISTINCT sensor_id, sensor_val_id '
					. 'from detections '
					. 'where exp_id = :exp_id '
					. 'order by sensor_id, sensor_val_id';
			$query = $db->prepare($sql);
			if ($query === false)
			{
				error_log('PDOError: '.var_export($db->errorInfo(),true));  //DEBUG
			}
			$result = $query->execute(array(
					':exp_id' => $experiment->id
			));
			if ($result === false)
			{
				error_log('PDOError: '.var_export($query->errorInfo(),true));  //DEBUG
			}
			while (($row = $query->fetch(PDO::FETCH_OBJ)) !== false)
			{
				$key = '' . $row->sensor_id . '#' . (int)$row->sensor_val_id;
				if(!array_key_exists($key, $det_sensors))
				{
					$det_sensors[$key] = clone $row;
				}
			}

			// Get list of sensors in current setup

			// Get current setup
			if ($experiment->setup_id)
			{
				$temp_sensors = SetupController::getSensors($experiment->setup_id, true, $db);  // +setup conf fields: name, setup_id
				if ($temp_sensors === false)
				{
					$temp_sensors = array();
				}
				foreach ($temp_sensors as $row)
				{
					$key = '' . $row->sensor_id . '#' . (int)$row->sensor_val_id;
					if (!array_key_exists($key, $setup_sensors))
					{
						$setup_sensors[$key] = clone $row;
					}
				}
			}

			// Get monitors sensors

			// Get unique sensors list from monitors values in experiment
			$sql = 'select DISTINCT mv.sensor as sensor_id, mv.valueidx as sensor_val_id '
					. 'from monitors as m '
					. 'left join monitors_values as mv on mv.uuid = m.uuid '
					. 'where m.exp_id = :exp_id '
					. 'order by mv.sensor, mv.valueidx';
			$query = $db->prepare($sql);
			if ($query === false)
			{
				error_log('PDOError: '.var_export($db->errorInfo(),true));  //DEBUG
			}
			$result = $query->execute(array(
					':exp_id' => $experiment->id
			));
			if ($result === false)
			{
				error_log('PDOError: '.var_export($query->errorInfo(),true));  //DEBUG
			}
			while (($row = $query->fetch(PDO::FETCH_OBJ)) !== false)
			{
				$key = '' . $row->sensor_id . '#' . (int)$row->sensor_val_id;
				if(!array_key_exists($key, $mon_sensors))
				{
					$mon_sensors[$key] = clone $row;
				}
			}

			// Get sensors from register with additional info

			// TODO: add method Sensor::getSensors()
			$query = $db->prepare(
					'select sensor_id, sensor_val_id, '
					. 'value_name, si_notation, si_name, max_range, min_range, resolution '
					. 'from sensors'
			);
			if ($query === false)
			{
				error_log('PDOError: '.var_export($db->errorInfo(),true));  //DEBUG
			}
			$result = $query->execute();
			if ($result === false)
			{
				error_log('PDOError: '.var_export($query->errorInfo(),true));  //DEBUG
			}
			while (($row = $query->fetch(PDO::FETCH_OBJ)) !== false)
			{
				$key = '' . $row->sensor_id . '#' . (int)$row->sensor_val_id;
				if(!array_key_exists($key, $reg_sensors))
				{
					$reg_sensors[$key] = clone $row;
				}
			}
		}
		catch (PDOException $e)
		{
			error_log('PDOException Experiment::graph(): '.var_export($e->getMessage(),true));  //DEBUG
			//var_dump($e->getMessage());
		}
		$db->commit();


		// Merge sensors

		// Merge detections sensors (older) with monitor sensors (newest)
		$sensors = array_merge($det_sensors, $mon_sensors);

		// Merge detections-monitors sensors (older) with setup sensors (fullest-newest)
		$sensors = array_merge($sensors, $setup_sensors);


		// Fill sensors with additional info from register
		foreach ($sensors as $key => $sensor)
		{
			// Need info from register for sensor
			if(!property_exists($sensor, 'value_name'))
			{
				if (array_key_exists($key, $reg_sensors))
				{
					// Replace with sensor data from registry
					$sensors[$key]       = clone $reg_sensors[$key];

					// add name field
					$value_name = (string) preg_replace('/[^A-Z0-9_]/i', '_', $reg_sensors[$key]->value_name);
					$sensors[$key]->name = (mb_strlen($reg_sensors[$key]->value_name,'utf-8') > 0) ?
							L('sensor_VALUE_NAME_' . strtoupper($value_name)) :
							L('sensor_UNKNOWN');
				}
				else
				{
					$sensors[$key]->value_name  = null;
					$sensors[$key]->si_notation = null;
					$sensors[$key]->si_name     = null;
					$sensors[$key]->max_range   = null;
					$sensors[$key]->min_range   = null;
					$sensors[$key]->resolution  = null;

					// add name field
					$sensors[$key]->name        = L('sensor_UNKNOWN');
				}
				// add setup id field
				$sensors[$key]->setup_id = 0;
			}
		}

		// Prepare available sensors list
		//$result['available_sensors'] = $sensors;

		// Filter requested sensors
		$displayed_sensors = array();
		if(!empty($sensors_show))
		{
			$displayed_sensors = array_intersect_key($sensors, $sensors_show);
		}
		else
		{
			$displayed_sensors = $sensors;
		}

		// Get current timezone offset in seconds to correct timestamps
		$tz_offset = (new DateTime())->format('Z');

		// Get time in milliseconds in local timezone
		$where = array();
		if ($from !== null) {
			$where[':timefrom'] = 'time ' . ($exclude ? '>' : '>=') . ' :timefrom';
		}
		if ($to !== null) {
			$where[':timeto']   = 'time ' . ($exclude ? '<' : '<=') . ' :timeto';
		}

		// Special raw data for plot [x, y, bottom,...{custom}], bottom is not used (0 by default), other fields are custom
		$sql = //'select time, strftime(\'%Y.%m.%d %H:%M:%f\', time) as mstime, detection '
			'select (strftime(\'%s\',time) - strftime(\'%S\',time) + strftime(\'%f\',time)' . ($tz_offset>=0 ? ' + ' : ' ')  . $tz_offset . ')*1000 as mstime, detection, 0, time, error '
			. 'from detections '
			. 'where exp_id = :exp_id and sensor_id = :sensor_id and sensor_val_id = :sensor_val_id and (error isnull or error = \'\') '
			. ((!empty($where)) ? ('and (' . implode(') and (', $where) . ') ') : '')
			//. 'order by strftime(\'%s\', time),strftime(\'%f\', time)';
			. 'order by strftime(\'%Y-%m-%dT%H:%M:%f\', time), id';
		$query = $db->prepare($sql);

		$result = array();
		$i = 0;
		foreach($displayed_sensors as $sensor)
		{
			$data = new stdClass();
			// TODO: add name of sensor to label from setup_info (but unknown which setup id was used for each detection, setup can be changed)
			$value_name = (string) preg_replace('/[^A-Z0-9_]/i', '_', $sensor->value_name);
			$data->label         = empty($sensor->value_name) ? L('sensor_UNKNOWN') : L('sensor_VALUE_NAME_' . strtoupper($value_name));
			$data->sensor_id     = $sensor->sensor_id;
			$data->sensor_val_id = (int)$sensor->sensor_val_id;
			$data->color         = ++$i;

			$inp_params = array(
					':exp_id'        => $experiment->id,
					':sensor_id'     => $sensor->sensor_id,
					':sensor_val_id' => $sensor->sensor_val_id
			);
			if(isset($where[':timefrom']))
			{
				$inp_params[':timefrom'] = $from;
			}
			if(isset($where[':timeto']))
			{
				$inp_params[':timeto'] = $to;
			}

			$res = $query->execute($inp_params);
			$detections = $query->fetchAll(PDO::FETCH_NUM);

			if(!empty($detections))
			{
				$data->data = $detections;
				foreach ($data->data as $k => $val)
				{
					// Detection axis coordinates values must be numbers, not strings (Plot restrictions)

					// XValue
					$t = (string)$val[0];  // XXX: return time in milliseconds (Warning! use string, int value > PHP_INT_MAX)
					$dotpos = strpos($t,'.');
					if ($dotpos !== false )
					{
						// cut fractional part with dot from time in msec (14235464000.0 -> 14235464000)
						$data->data[$k][0] = (float)substr($t, 0, $dotpos);
					}
					else
					{
						$data->data[$k][0] = (float)$val[0];
					}

					// YValue
					if ($val[1] != '' && $val[1] !== 'NaN')
					{
						$data->data[$k][1] = (float)$val[1];
					}
					else
					{
						$data->data[$k][1] = null;
					}

					// Check erroneous detection value
					// TODO: check $data->data[$k][4] for error text (NaN) and $data->data[$k][1] for empty detection value (NULL)
					if ($val[4] != '' || $val[4] === 'NaN')
					{
						// Special case for plot. Null to skip point and break line that connects points on plot
						$data->data[$k][1] = null;
					}

					// "bottom" value
					$data->data[$k][2] = (int)$val[2];
				}
			}
			else
			{
				$data->data = array();
			}

			$result[] = $data;
		}

		return array('result' => $result);
	}

	/**
	 * Get experiment detections for scatter plot with datetime filter.
	 * API method: Detections.getScatterData
	 * API params: experiment, sx, sy, from, to, exclude
	 *
	 * @param  array  $params  Array of parameters:
	 *                           experiment  - id of experiment,
	 *                           sx, sy      - sensor identificators strings in format "sensor_id + # + value_id"
	 *                           from, to    - datetime in ISO format (RFC3339 with nanoseconds) "Y-m-dTH:i:s.uZ"
	 *                           exclude     - integer, set 1 for exclude from-to datetime data from result, default 0.
	 *
	 * @return array  Result in form array('result' => array of objects) or False on error
	 */
	public function getScatterData($params)
	{
		if(empty($params['experiment']))
		{
			$this->error = L('ERROR_EXPERIMENT_NOT_FOUND');

			return false;
		}

		// Load experiment
		$experiment = (new Experiment())->load($params['experiment']);
		if(!$experiment)
		{
			$this->error = L('ERROR_EXPERIMENT_NOT_FOUND');

			return false;
		}

		// Check access to experiment
		if(!$experiment->userCanView($this->session()))
		{
			$this->error = L('ACCESS_DENIED');

			return false;
		}

		// Get two sensors filter parameters
		// Filter sensors
		// Format: {sensor_id}#{sensor_val_id}
		$sx = null;
		$sy = null;
		if (isset($params['sx']) && isset($params['sy']) && (strlen($params['sx']) > 0) && (strlen($params['sy']) > 0))
		{
			if (strcmp($params['sx'], $params['sy']) != 0)
			{
				$sx = $params['sx'];
				$sy = $params['sy'];
			}
			else
			{
				// error on equal sensors
				$this->error = L('ERROR_INVALID_PARAMETERS');

				return false;
			}
		}
		else
		{
			// error on empty sensors
			$this->error = L('ERROR_INVALID_PARAMETERS');

			return false;
		}

		// Filter datetimes
		$from = null;
		if(isset($params['from']) && strlen($params['from']) != 0)
		{
			// UTC time with seconds parts
			try
			{
				$from = System::datemsecformat($params['from'], System::DATETIME_RFC3339NANO_UTC, 'UTC');  // XXX: Cannot use DateTime, because only 6 milli digits supported.
			}
			catch (Exception $e)
			{
				// error on invalid format
				$this->error = L('ERROR_INVALID_PARAMETERS');

				return false;
			}
		}
		$to = null;
		if(isset($params['to']) && strlen($params['to']) != 0)
		{
			// UTC time with seconds parts
			try
			{
				$to = System::datemsecformat($params['to'], System::DATETIME_RFC3339NANO_UTC, 'UTC');  // XXX: Cannot use DateTime, because only 6 milli digits supported.
			}
			catch (Exception $e)
			{
				// error on invalid format
				$this->error = L('ERROR_INVALID_PARAMETERS');

				return false;
			}
		}

		$exclude = false;
		if(isset($params['exclude']))
		{
			$exclude = (int)$params['exclude'] ? true : false;
		}

		$db = new DB();

		// Init arrays of sensors
		$det_sensors   = array();
		$setup_sensors = array();
		$mon_sensors   = array();
		$reg_sensors   = array();

		// Speed up db operations within transaction
		$db->beginTransaction();
		try
		{
			// Get list of sensors available in detections
			// (already used sensors)

			// Get unique sensors list from detections data of experiment
			$sql = 'select DISTINCT sensor_id, sensor_val_id '
					. 'from detections '
					. 'where exp_id = :exp_id '
					. 'order by sensor_id, sensor_val_id';
			$query = $db->prepare($sql);
			if ($query === false)
			{
				error_log('PDOError: '.var_export($db->errorInfo(),true));  //DEBUG
			}
			$result = $query->execute(array(
					':exp_id' => $experiment->id
			));
			if ($result === false)
			{
				error_log('PDOError: '.var_export($query->errorInfo(),true));  //DEBUG
			}
			while (($row = $query->fetch(PDO::FETCH_OBJ)) !== false)
			{
				$key = '' . $row->sensor_id . '#' . (int)$row->sensor_val_id;
				if(!array_key_exists($key, $det_sensors))
				{
					$det_sensors[$key] = clone $row;
				}
			}

			// Get list of sensors in current setup

			// Get current setup
			if ($experiment->setup_id)
			{
				$temp_sensors = SetupController::getSensors($experiment->setup_id, true, $db);  // +setup conf fields: name, setup_id
				if ($temp_sensors === false)
				{
					$temp_sensors = array();
				}
				foreach ($temp_sensors as $row)
				{
					$key = '' . $row->sensor_id . '#' . (int)$row->sensor_val_id;
					if (!array_key_exists($key, $setup_sensors))
					{
						$setup_sensors[$key] = clone $row;
					}
				}
			}

			// Get monitors sensors

			// Get unique sensors list from monitors values in experiment
			$sql = 'select DISTINCT mv.sensor as sensor_id, mv.valueidx as sensor_val_id '
					. 'from monitors as m '
					. 'left join monitors_values as mv on mv.uuid = m.uuid '
					. 'where m.exp_id = :exp_id '
					. 'order by mv.sensor, mv.valueidx';
			$query = $db->prepare($sql);
			if ($query === false)
			{
				error_log('PDOError: '.var_export($db->errorInfo(),true));  //DEBUG
			}
			$result = $query->execute(array(
					':exp_id' => $experiment->id
			));
			if ($result === false)
			{
				error_log('PDOError: '.var_export($query->errorInfo(),true));  //DEBUG
			}
			while (($row = $query->fetch(PDO::FETCH_OBJ)) !== false)
			{
				$key = '' . $row->sensor_id . '#' . (int)$row->sensor_val_id;
				if(!array_key_exists($key, $mon_sensors))
				{
					$mon_sensors[$key] = clone $row;
				}
			}

			// Get sensors from register with additional info

			// TODO: add method Sensor::getSensors()
			$query = $db->prepare(
					'select sensor_id, sensor_val_id, '
						. 'value_name, si_notation, si_name, max_range, min_range, resolution '
					. 'from sensors'
			);
			if ($query === false)
			{
				error_log('PDOError: '.var_export($db->errorInfo(),true));  //DEBUG
			}
			$result = $query->execute();
			if ($result === false)
			{
				error_log('PDOError: '.var_export($query->errorInfo(),true));  //DEBUG
			}
			while (($row = $query->fetch(PDO::FETCH_OBJ)) !== false)
			{
				$key = '' . $row->sensor_id . '#' . (int)$row->sensor_val_id;
				if(!array_key_exists($key, $reg_sensors))
				{
					$reg_sensors[$key] = clone $row;
				}
			}
		}
		catch (PDOException $e)
		{
			error_log('PDOException Experiment::graph(): '.var_export($e->getMessage(),true));  //DEBUG
			//var_dump($e->getMessage());
		}
		$db->commit();


		// Merge sensors

		// Merge detections sensors (older) with monitor sensors (newest)
		$sensors = array_merge($det_sensors, $mon_sensors);

		// Merge detections-monitors sensors (older) with setup sensors (fullest-newest)
		$sensors = array_merge($sensors, $setup_sensors);


		// Fill sensors with additional info from register
		foreach ($sensors as $key => $sensor)
		{
			// Need info from register for sensor
			if(!property_exists($sensor, 'value_name'))
			{
				if (array_key_exists($key, $reg_sensors))
				{
					// Replace with sensor data from registry
					$sensors[$key]       = clone $reg_sensors[$key];

					// add name field
					$value_name = (string) preg_replace('/[^A-Z0-9_]/i', '_', $reg_sensors[$key]->value_name);
					$sensors[$key]->name = (mb_strlen($reg_sensors[$key]->value_name,'utf-8') > 0) ?
							L('sensor_VALUE_NAME_' . strtoupper($value_name)) :
							L('sensor_UNKNOWN');
				}
				else
				{
					$sensors[$key]->value_name  = null;
					$sensors[$key]->si_notation = null;
					$sensors[$key]->si_name     = null;
					$sensors[$key]->max_range   = null;
					$sensors[$key]->min_range   = null;
					$sensors[$key]->resolution  = null;

					// add name field
					$sensors[$key]->name        = L('sensor_UNKNOWN');
				}
				// add setup id field
				$sensors[$key]->setup_id = 0;
			}
		}

		// Prepare available sensors list
		//$result['available_sensors'] = $sensors;

		// Search selected sensors
		$sensor_x = null;
		$sensor_y = null;
		foreach ($sensors as $key => $sensor)
		{
			if(strcmp($sx, $key) == 0)
			{
				$sensor_x = $sensors[$key];
			}
			if(strcmp($sy, $key) == 0)
			{
				$sensor_y = $sensors[$key];
			}
		}

		if ($sensor_x === null || $sensor_y === null)
		{
			$this->error = L('ERROR_SENSOR_NOT_FOUND');

			return false;
		}

		// Get time in milliseconds in local timezone
		$where = array();
		if ($from !== null) {
			$where[':timefrom'] = 'dx.time ' . ($exclude ? '>' : '>=') . ' :timefrom';
		}
		if ($to !== null) {
			$where[':timeto']   = 'dx.time ' . ($exclude ? '<' : '<=') . ' :timeto';
		}

		// Special raw data for plot [x, y, bottom,...{custom}], bottom is used for count intersection
		$sql = 'select dx.detection as x, dy.detection as y, count(*) as cnt '
				. 'from detections as dx '
				. 'left join detections as dy on dx.exp_id = dy.exp_id and dx.time = dy.time '
				. 'where dx.exp_id = :exp_id '
					. 'and dx.sensor_id = :sensor_id_x and dx.sensor_val_id = :sensor_val_id_x '
					. 'and dy.sensor_id = :sensor_id_y and dy.sensor_val_id = :sensor_val_id_y '
					. 'and (dx.error isnull or dx.error = \'\') '
					. 'and (dy.error isnull or dy.error = \'\') '
					. ((!empty($where)) ? ('and (' . implode(') and (', $where) . ') ') : '')
				. 'group by dx.detection,dy.detection '
				. 'order by dx.detection,dy.detection,dx.time';
		$query = $db->prepare($sql);

		$inp_params = array(
				':exp_id'          => $experiment->id,
				':sensor_id_x'     => $sensor_x->sensor_id,
				':sensor_val_id_x' => $sensor_x->sensor_val_id,
				':sensor_id_y'     => $sensor_y->sensor_id,
				':sensor_val_id_y' => $sensor_y->sensor_val_id
		);
		if(isset($where[':timefrom']))
		{
			$inp_params[':timefrom'] = $from;
		}
		if(isset($where[':timeto']))
		{
			$inp_params[':timeto'] = $to;
		}
		$res = $query->execute($inp_params);
		$rows = $query->fetchAll(PDO::FETCH_NUM);

		$i = 0;
		$result = array();
		$data = new stdClass();
		// TODO: add name of sensor to label from setup_info (but unknown which setup id was used for each detection, setup can be changed)
		$x_value_name = (string) preg_replace('/[^A-Z0-9_]/i', '_', $sensor_x->value_name);
		$x_si_notation = (string) preg_replace('/[^A-Z0-9_]/i', '_', $sensor_x->si_notation);
		$y_value_name = (string) preg_replace('/[^A-Z0-9_]/i', '_', $sensor_y->value_name);
		$y_si_notation = (string) preg_replace('/[^A-Z0-9_]/i', '_', $sensor_y->si_notation);
		$data->label = 
				((mb_strlen($sensor_x->value_name, 'utf-8') > 0 ) ?
						L('sensor_VALUE_NAME_' . strtoupper($x_value_name)) :
						L('sensor_UNKNOWN'))
				. ((mb_strlen($sensor_x->value_name, 'utf-8') > 0 && mb_strlen($sensor_x->si_notation, 'utf-8') > 0) ?
						(', ' . L('sensor_VALUE_SI_NOTATION_' . strtoupper($x_value_name) . '_' . strtoupper($x_si_notation))) : 
						'')
				. ' ('  . htmlspecialchars($sensor_x->sensor_id. '#' . (int)$sensor_x->sensor_val_id, ENT_QUOTES, 'UTF-8') . ')'
				. ' - '
				. ((mb_strlen($sensor_y->value_name, 'utf-8') > 0 ) ?
						L('sensor_VALUE_NAME_' . strtoupper($y_value_name)) :
						L('sensor_UNKNOWN'))
				. ((mb_strlen($sensor_y->value_name, 'utf-8') > 0 && mb_strlen($sensor_y->si_notation, 'utf-8') > 0) ?
						(', ' . L('sensor_VALUE_SI_NOTATION_' . strtoupper($y_value_name) . '_' . strtoupper($y_si_notation))) : 
						'')
				. ' ('  . htmlspecialchars($sensor_y->sensor_id. '#' . (int)$sensor_y->sensor_val_id, ENT_QUOTES, 'UTF-8') . ')';

		$data->sensor_id_x     = $sensor_x->sensor_id;
		$data->sensor_val_id_x = (int)$sensor_x->sensor_val_id;
		$data->sensor_id_y     = $sensor_y->sensor_id;
		$data->sensor_val_id_y = (int)$sensor_y->sensor_val_id;
		//$data->color         = ++$i;

		if(!empty($rows))
		{
			$data->data = $rows;
			foreach ($data->data as $k => $val)
			{
				// Detection axis coordinates values must be numbers, not strings (Plot restrictions)

				// XValue
				if ($val[0] != '' && $val[0] !== 'NaN')
				{
					$data->data[$k][0] = (float)$val[0];
				}
				else
				{
					$data->data[$k][0] = null;
				}

				// YValue
				if ($val[1] != '' && $val[1] !== 'NaN')
				{
					$data->data[$k][1] = (float)$val[1];
				}
				else
				{
					$data->data[$k][1] = null;
				}

				// "bottom" value (count)
				$data->data[$k][2] = (int)$val[2];
			}
		}
		else
		{
			$data->data = array();
		}

		// Get minmax dates for found detections data
		$sql = 'select min(dx.time), max(dx.time) '
				. 'from detections as dx '
				. 'left join detections as dy on dx.exp_id = dy.exp_id and dx.time = dy.time '
				. 'where dx.exp_id = :exp_id '
						. 'and dx.sensor_id = :sensor_id_x and dx.sensor_val_id = :sensor_val_id_x '
						. 'and dy.sensor_id = :sensor_id_y and dy.sensor_val_id = :sensor_val_id_y '
						. 'and (dx.error isnull or dx.error = \'\') '
						. 'and (dy.error isnull or dy.error = \'\') ';
		$query = $db->prepare($sql);

		$inp_params = array(
				':exp_id'          => $experiment->id,
				':sensor_id_x'     => $sensor_x->sensor_id,
				':sensor_val_id_x' => $sensor_x->sensor_val_id,
				':sensor_id_y'     => $sensor_y->sensor_id,
				':sensor_val_id_y' => $sensor_y->sensor_val_id
		);
		$res = $query->execute($inp_params);
		$statrows = $query->fetchAll(PDO::FETCH_NUM);
		if(!empty($statrows))
		{
			$data->mindatetime = $statrows[0][0];
			$data->maxdatetime = $statrows[0][1];
		}

		$result[] = $data;

		return array('result' => $result);
	}

	/**
	 * Delete detections by ids.
	 * API method: Detections.delete
	 * API params: id|id[]
	 *
	 * @param  array $params  Array of parameters
	 *
	 * @return array  Result in form array('result' => True) or False on error
	 */
	public function delete($params)
	{
		if(empty($params['id']))
		{
			$this->error = L('ERROR_DETECTIONS_NOT_FOUND');

			return false;
		}

		$ids = array();
		if(is_array($params['id']))
		{
			foreach($params['id'] as $val)
			{
				if ((int)$val <= 0) continue;
				$ids[(int)$val] = null;
			}
		}
		else if (is_numeric($params['id']))
		{
			if ((int)$params['id'] > 0)
			{
				$ids[(int)$params['id']] = null;
			}
		}
		else
		{
			$this->error = L('ERROR_INVALID_PARAMETERS');

			return false;
		}

		if (empty($ids))
		{
			$this->error = L('ERROR_INVALID_PARAMETERS');

			return false;
		}

		$cnt_ids = count($ids);

		$db = new DB();

		// Check access to data
		// TODO: need Experiment::userCanEdit() for multiple data
		if ($this->session()->getUserLevel() != 3)
		{
			// Get experiments with sessions identificators for detections ids
			$current_session = $this->session()->getKey();

			$exp_det_query = $db->prepare(
					'select d.id, d.exp_id, e.session_key '
					. 'from detections as d '
					. 'left join experiments as e on e.id = d.exp_id ' 
					. 'where e.exp_id notnull and d.id in (' . implode(',', array_fill(0, $cnt_ids, '?')) . ')'
			);
			$stmt = $db->prepare($exp_det_query);
			try
			{
				$res = $stmt->execute(array_keys($ids));
				if (!$res)
				{
					error_log('PDOError: '.var_export($stmt->errorInfo(),true));  //DEBUG

					$this->error = L('FATAL_ERROR');
					return false;
				}
			}
			catch (PDOException $e)
			{
				error_log('PDOException delete(): '.var_export($e->getMessage(),true));  //DEBUG

				$this->error = L('FATAL_ERROR');
				return false;
			}
			$ids_exps = (array) $stmt->fetchAll(PDO::FETCH_ASSOC);

			$denied = 0;
			foreach ($ids_exps as $k => $val)
			{
				if ($val['session_key'] != $current_session)
				{
					unset($ids_exps[$k]);
					$denied++;
				}
			}

			// Set only available data and ignore denied
			foreach ($ids_exps as $val)
			{
				$ids[(int)$val['id']] = (int)$val['exp_id'];
			}
			$ids = array_filter($ids);

			// Warning on denied access to some detections data
			if ($denied > 0)
			{
				$this->error = L('ACCESS_DENIED');
				//return false;  // But try delete only available
			}
		}

		if (empty($ids))
		{
			// Use only first error
			if (empty($this->error))
			{
				$this->error = L('ERROR_DETECTIONS_NOT_FOUND');
			}
			return false;
		}

		// Delete detections
		$sql_delete = 'delete from detections where id in (' . implode(',', array_fill(0, count($ids), '?')) . ')';
		$stmt = $db->prepare($sql_delete);
		try
		{
			$res = $stmt->execute(array_keys($ids));
			if (!$res)
			{
				error_log('PDOError: '.var_export($stmt->errorInfo(),true));  //DEBUG

				$this->error = L('FATAL_ERROR');
				return false;
			}
		}
		catch (PDOException $e)
		{
			error_log('PDOException delete(): '.var_export($e->getMessage(),true));  //DEBUG

			$this->error = L('FATAL_ERROR');
			return false;
		}

		return array('result' => true);
	}


	/**
	 * Delete data from detections of experiment by datetime.
	 * API method: Detections.deletebytime
	 * API params: dt|dt[], exp_id
	 *
	 * @param  array $params  Array of parameters, 
	 *                        datetime(dt) in format Y-m-dTH:i:s.uZ (UTC),
	 *                        dt MUST match datetime stored in database up to second parts (micro, milli, nano end etc.).
	 *
	 * @return array  Result in form array('result' => True) or False on error
	 */
	public function deletebytime($params)
	{
		if(empty($params['dt']) || empty($params['exp_id']))
		{
			$this->error = L('ERROR_INVALID_PARAMETERS');

			return false;
		}

		$dts = array();

		// Load experiment
		$experiment = (new Experiment())->load((int)$params['exp_id']);
		if(!$experiment)
		{
			$this->error = L('ERROR_EXPERIMENT_NOT_FOUND');

			return false;
		}

		// Check access to edit experiment
		if(!$experiment->userCanEdit($this->session()))
		{
			$this->error = L('ACCESS_DENIED');

			return false;
		}

		// Get datetimes
		if (is_string($params['dt']))
		{
			$params['dt'] = array($params['dt']);
		}
		if(is_array($params['dt']))
		{
			foreach($params['dt'] as $val)
			{
				// UTC time with seconds parts
				try
				{
					$tm = System::datemsecformat($val, System::DATETIME_RFC3339NANO_UTC, 'UTC');  // XXX: Cannot use DateTime, because only 6 milli digits supported.
				}
				catch (Exception $e)
				{
					// skip invalid dates
					continue;
				}

				$dts[$tm] = null;
			}
		}
		else
		{
			$this->error = L('ERROR_INVALID_PARAMETERS');

			return false;
		}

		if (empty($dts))
		{
			$this->error = L('ERROR_INVALID_PARAMETERS');

			return false;
		}

		$db = new DB();

		// Delete detections
		// XXX: Carefully! Only 3 digits (milliseconds) supported in sqlite in datetime functions (%f format), but time stored as full datetime string with parts of seconds)
		//      Dont cut mirco-, nano- and etc. seconds, use full datetime string compare
		//$sql_delete = 'delete from detections where exp_id = ' . (int)$experiment->id . ' and strftime(\'%Y-%m-%dT%H:%M:%f\', time) in (' . implode(',', array_fill(0, count($dts), '?')) . ')';
		$sql_delete = 'delete from detections where exp_id = ' . (int)$experiment->id . ' and time in (' . implode(',', array_fill(0, count($dts), '?')) . ')';
		$stmt = $db->prepare($sql_delete);
		try
		{
			$res = $stmt->execute(array_keys($dts));
			if (!$res)
			{
				error_log('PDOError: '.var_export($stmt->errorInfo(),true));  //DEBUG

				$this->error = L('FATAL_ERROR');
				return false;
			}
		}
		catch (PDOException $e)
		{
			error_log('PDOException delete(): '.var_export($e->getMessage(),true));  //DEBUG

			$this->error = L('FATAL_ERROR');
			return false;
		}

		return array('result' => true);
	}
}
