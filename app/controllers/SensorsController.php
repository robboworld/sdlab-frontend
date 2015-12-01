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
		// Add language translates for scripts
		Language::script(array(
				'sensor_VALUE_NAME_TEMPERATURE',  // chart
				'GRAPH', 'INFO'            // Sensor
				                           // - sensors
		));

		$this->addCss('sensors');
		//$this->view->content->sensors_list = $this->sensorList($this->getSensors()->Values);
		$this->view->content->sensors_list = 'test inc';
		$this->view->template = 'index';

 		//$this->view->content = $this->renderTemplate('index');
		// TODO: Remove all unnessesary in this controller, use only for API calls
	}


	/**
	 * Get list of available sensors.
	 * API method: Sensors.getSensors
	 * API params: getinfo:boolean
	 * 
	 * @param  array  $params Array of parameters
	 * 
	 * @return array  The sensor list of objects or False on error
	 */
	function getSensors($params)
	{
		// Rescan sensors mode only for admin access
		$rescan = (isset($params['rescan']) && $params['rescan']) ? true : false;
		if ($rescan)
		{
			if ($this->session()->getUserLevel() != 3)
			{
				// Disable rescan for not admin (may corrupt current measurements)
				$rescan = false;
			}
		}

		$socket = new JSONSocket($this->config['socket']['path']);
		$result = $socket->call('Lab.ListSensors', array($rescan));
		if (!$result)
		{
			return false;
		}

		// Sync sensors table

		// TODO: move to sensors model class, update also by cron task, or on connect sensors?

		$db = new DB();

		// Get known sensors
		$query = $db->prepare('select distinct sensor_id, sensor_val_id from sensors');
		$query->execute();
		$items = (array) $query->fetchAll(PDO::FETCH_OBJ);
		$known = array();
		foreach($items as $k => $item)
		{
			$key = '' . $item->sensor_id . '#' . (int)$item->sensor_val_id;
			$known[$key] = $item;
		}

		// Prepare insert query for new sensors
		$insert = $db->prepare('insert into sensors (sensor_id, sensor_val_id, sensor_name, value_name, si_notation, si_name, max_range, min_range, error, resolution)' .
				' values (:sensor_id, :sensor_val_id, :sensor_name, :value_name, :si_notation, :si_name, :max_range, :min_range, :error, :resolution)');

		foreach($result as $sensor_id => $obj)
		{
			$sensor_name = (string) preg_replace('/\-.*/i', '', $sensor_id);
			if (strlen($sensor_name) == 0)
			{
				continue;
			}

			foreach($obj->{'Values'} as $sensor_val_id => &$data)
			{
				$key = '' . $sensor_id . '#' . (int)$sensor_val_id;
				if (isset($known[$key]))
				{
					continue;
				}

				try
				{
					$res = $insert->execute(array(
							':sensor_id'     => $sensor_id,
							':sensor_val_id' => (int)$sensor_val_id,
							':sensor_name'   => $sensor_name,
							':value_name'    => System::getValsTranslate($data->{'Name'}),
							':si_notation'   => System::getValsTranslate($data->{'Name'}, 'si_notation'),
							':si_name'       => System::getValsTranslate($data->{'Name'}, 'si_name'),
							':max_range'     => (isset($data->{'Range'}->{'Max'}) && !is_null($data->{'Range'}->{'Max'})) ? $data->{'Range'}->{'Max'} : null,
							':min_range'     => (isset($data->{'Range'}->{'Min'}) && !is_null($data->{'Range'}->{'Min'})) ? $data->{'Range'}->{'Min'} : null,
							':error'         => NULL, // todo: use error or resolution for sensors?
							':resolution'    => $data->{'Resolution'}
					));
					if (!$res)
					{
						error_log('PDOError: '.var_export($insert->errorInfo(),true));  //DEBUG
					}
				}
				catch (PDOException $e)
				{
					var_dump($e->getMessage());
					error_log('PDOException: '.var_export($e->getMessage(),true));  //DEBUG
				}
			}
		}


		// Get additional sensors data
		if(isset($params['getinfo']) && $params['getinfo'])
		{
			foreach($result as $sensor_id => $sensor)
			{
				// Add additional data to main sensor data
				$sensor->sensor_name = (string) preg_replace('/\-.*/i', '', $sensor_id);

				foreach ($sensor->{'Values'} as $value)
				{
					$value_name = System::getValsTranslate($value->{'Name'});
					if (($value_name !== false) && (strlen($value_name) > 0))
					{
						$value->value_name  =   constant('L::sensor_VALUE_NAME_' . strtoupper($value_name));

						$field = System::getValsTranslate($value->{'Name'}, 'si_notation');
						$value->si_notation = (($field !== false) && (strlen($field) > 0)) ? 
												constant('L::sensor_VALUE_SI_NOTATION_' . strtoupper($value_name) . '_' . strtoupper($field)) :
												false;

						$field = System::getValsTranslate($value->{'Name'}, 'si_name');
						$value->si_name     = (($field !== false) && (strlen($field) > 0)) ?
												constant('L::sensor_VALUE_SI_NAME_' . strtoupper($value_name) . '_' . strtoupper($field)) :
												false;
					}
					else
					{
						$value->value_name  = false;
						$value->si_notation = false;
						$value->si_name     = false;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Get data from one sensor.
	 * API method: Sensors.getData
	 * API params: Sensor, ValueIdx
	 * 
	 * @param  array  $params Array of parameters
	 * 
	 * @return array  The sensor data array (Time, Reading) or False on error
	 */
	function getData($params)
	{
		$socket = new JSONSocket($this->config['socket']['path']);
		$result = $socket->call('Lab.GetData', (object) array(
				'Sensor'   => $params['Sensor'],
				'ValueIdx' => (int) $params['ValueIdx']
		));
		unset($socket);

		return $result;
	}

	/**
	 * Get data from sensors.
	 * API method: Sensors.getDataItems
	 * API params: [Sensor, ValueIdx]
	 *
	 * @param  array  $params Array of parameters
	 *
	 * @return array  The sensor array with data array (Sensor, ValueIdx, result(Time, Reading)) or False on error
	 */
	function getDataItems($params)
	{
		if (!is_array($params)) 
		{
			$this->error = L::ERROR;

			return false;
		}

		// TODO: Create backend Lab.GetItemsData for read multi sensors

		$result = array();
		foreach ($params as $sensor)
		{
			if(!isset($sensor['Sensor']))
			{
				continue;
			}

			$obj = array(
					'Sensor' => $sensor['Sensor']
			);

			$idx = 0;
			if (isset($sensor['ValueIdx']))
			{
				$idx = (int)$sensor['ValueIdx'];
				$obj['ValueIdx'] = $idx;
			}

			$socket = new JSONSocket($this->config['socket']['path']);
			$obj['result'] = $socket->call('Lab.GetData', (object) array(
					'Sensor'   => $sensor['Sensor'],
					'ValueIdx' => $idx
			));
			unset($socket);

			$result[] = $obj;
		}

		if (empty($result))
		{
			$this->error = L::ERROR;

			return false;
		}

		return $result;
	}

	/**
	 * Get data strob from Setup for experiment.
	 * API method: Sensors.experimentStrob
	 * API params: experiment
	 * 
	 * @param  array $params  Array of parameters
	 * 
	 * @return array  Result in form array('result' => DATA) or False on error
	 */
	function experimentStrob($params)
	{
		if(empty($params['experiment']))
		{
			$this->error = L::ERROR_EXPERIMENT_NOT_FOUND;

			return false;
		}

		// Load experiment
		$experiment = (new Experiment())->load($params['experiment']);
		if(!$experiment)
		{
			$this->error = L::ERROR_EXPERIMENT_NOT_FOUND;

			return false;
		}

		// Check access to experiment edit
		if(!$experiment->userCanEdit($this->session()))
		{
			$this->error = L::ACCESS_DENIED;

			return false;
		}


		// Check setup
		if(empty($experiment->setup_id))
		{
			$this->error = L::ERROR_SETUP_NOT_FOUND;

			return false;
		}

		// Get sensors for experiment with setup
		$sensors = SetupController::getSensors($experiment->setup_id, true);
		if(empty($sensors))
		{
			// TODO: error message about empty setup or no sensors

			return false;
		}

		// Load Setup
		$setup = (new Setup())->load($experiment->setup_id);
		if (!$setup)
		{
			$this->error = L::ERROR_SETUP_NOT_FOUND;

			return false;
		}

		// Check active state
		if(!$setup->flag)
		{
			// Now not set active state on strob requests
			//$setup->set('flag', true);
			//$setup->save();
		}
		else
		{
			//Check access to control Setup

			// Access only if current experiment is master of Setup
			if ($setup->master_exp_id != $experiment->id)
			{
				$this->error = L::ACCESS_DENIED;

				return false;
			}
		}

		// Prepare sensors list for API method
		$params_array = array();
		foreach($sensors as $sensor)
		{
			$params_array[] = (object) array(
					'Sensor'   => $sensor->id,
					'ValueIdx' => (int) $sensor->sensor_val_id
			);
		}

		// Prepare array of parameters for API method
		$query_params = array(
			'Values' => $params_array,
			'Period' => System::nano(1),
			'Count'  => 1
		);

		// TODO: Add return value to StartSeries the uniqid+timestamp for identify self series on backend, add uniq id param to GetSeries

		// Send request for start series consists of one detection
		$socket = new JSONSocket($this->config['socket']['path']);
		$respond = $socket->call('Lab.StartSeries', (object) $query_params);

		// If returned true try get results
		if(!$respond)
		{
			$this->error = L::setup_ERROR_SERIES_NOT_STARTED;

			return false;
		}

		// Wait for results
		sleep(2);

		// For results need new socket
		unset($socket);
		$socket = new JSONSocket($this->config['socket']['path']);

		$result = $socket->call('Lab.GetSeries', null);
		if(empty($result))
		{
			$this->error = L::setup_ERROR_EMPTY_RESPONSE;

			return false;
		}


		$db = new DB();
		$db->beginTransaction();  // speed up inserts within transaction

		$insert = $db->prepare('insert into detections (exp_id, time, sensor_id, sensor_val_id, detection, error) values (:exp_id, :time, :sensor_id, :sensor_val_id, :detection, :error)');

		for($i = 0; $i < count($sensors); $i++)
		{
			// Check error value
			$sensor_error = null;
			if ($result[0]->Readings[$i] === 'NaN')
			{
				$sensor_error = 'NaN';
				$result[0]->Readings[$i] = null;
			}

			//Check range
			if (is_null($sensor_error))
			{
				if (isset($sensors[$i]->min_range) && ((float)$result[0]->Readings[$i] < (float)$sensors[$i]->min_range))
				{
					$sensor_error = 'NaN';
					$result[0]->Readings[$i] = $sensors[$i]->min_range;
				}
			}
			if (is_null($sensor_error))
			{
				if (isset($sensors[$i]->max_range) && ((float)$result[0]->Readings[$i] > (float)$sensors[$i]->max_range))
				{
					$sensor_error = 'NaN';
					$result[0]->Readings[$i] = $sensors[$i]->max_range;
				}
			}

			try
			{
				$res = $insert->execute(array(
					':exp_id' => $experiment->id,
					':time' => System::convertDatetimeToUTC($result[0]->Time),
					':sensor_id' => $sensors[$i]->id,
					':sensor_val_id' => $sensors[$i]->sensor_val_id,
					':detection' => $result[0]->Readings[$i],
					':error' => $sensor_error
				));
				if (!$res)
				{
					error_log('PDOError: '.var_export($insert->errorInfo(),true));  //DEBUG
				}
			}
			catch (PDOException $e)
			{
				error_log('PDOException experimentStop(): '.var_export($e->getMessage(),true));  //DEBUG
				//var_dump($e->getMessage());
			}
		}

		// Update experiment start
		$sql_exp_update_query = "update experiments set DateStart_exp = :DateStart_exp where id = :id and ((DateStart_exp isnull) or (DateStart_exp = 0) or (DateStart_exp = ''))";
		$update = $db->prepare($sql_exp_update_query);
		$result = $update->execute(array(
				':DateStart_exp' => (new DateTime())->format('U'),
				':id' => $experiment->id
		));
		if (!$result)
		{
			error_log('PDOError: '.var_export($update->errorInfo(),true));  //DEBUG
		}
		// Update experiment stop
		$sql_exp_update_query = "update experiments set DateEnd_exp = :DateEnd_exp where id = :id";
		$update = $db->prepare($sql_exp_update_query);
		$result = $update->execute(array(
				':DateEnd_exp' => (new DateTime())->format('U'),
				':id' => $experiment->id
		));
		if (!$result)
		{
			error_log('PDOError: '.var_export($update->errorInfo(),true));  //DEBUG
		}

		$db->commit();

		return array('result' => true);
	}


	/**
	 * Start experiment monitoring. Run new monitoring.
	 * API method: Sensors.experimentStart
	 * API params: experiment
	 *
	 * @param  array $params  Array of parameters
	 *
	 * @return array  Result in form array('result' => uuid) or False on error
	 */
	function experimentStart($params)
	{
		if(empty($params['experiment']))
		{
			$this->error = L::ERROR_EXPERIMENT_NOT_FOUND;

			return false;
		}

		// Load experiment
		$experiment = (new Experiment())->load($params['experiment']);
		if(!$experiment)
		{
			$this->error = L::ERROR_EXPERIMENT_NOT_FOUND;

			return false;
		}

		// Check access to experiment edit
		if(!$experiment->userCanEdit($this->session()))
		{
			$this->error = L::ACCESS_DENIED;

			return false;
		}


		// Check setup
		if(empty($experiment->setup_id))
		{
			$this->error = L::ERROR_SETUP_NOT_FOUND;

			return false;
		}

		// Get sensors for experiment with setup
		$sensors = SetupController::getSensors($experiment->setup_id);
		if(empty($sensors))
		{
			// TODO: error message about empty setup or no sensors

			return false;
		}

		// Load Setup
		$setup = (new Setup())->load($experiment->setup_id);
		if (!$setup)
		{
			$this->error = L::ERROR_SETUP_NOT_FOUND;

			return false;
		}

		// Check active state
		if($setup->flag)
		{
			$this->error = L::setup_ACTIVE_ALREADY;

			return false;
		}


		// Check access to control Setup
		/*
		if ($setup->master_exp_id != $experiment->id)
		{
			$this->error = L::ACCESS_DENIED;

			return false;
		}
		*/
		// TODO: set master_exp_id to this setup on success start (when flag=true)

		$db = new DB();


		// Get old monitors
		$monitors = (new Monitor())->loadItems(
				array(
						'exp_id'   => (int)$experiment->id,
						'setup_id' => (int)$setup->id,
						//'deleted'  => 0
				),
				'id', 'ASC'
		);

		// Remove all old monitors
		// TODO: may be not remove old monitors now, just update DB monitors as deleted, remove api by cron?

		// Remove monitors call for backend sensors API
		$delmons = array();
		$errmons = array();
		foreach($monitors as $mon)
		{
			// Send request for removing monitor
			$query_params = array((string) $mon->uuid);
			$socket = new JSONSocket($this->config['socket']['path']);  // new socket because closed in call
			$respond = $socket->call('Lab.RemoveMonitor', $query_params);
			if ($respond)
			{
				$delmons[] = $mon->uuid;
			}
			else 
			{
				$errmons[] = $mon->uuid;
			}
			unset($socket);
		}
		if (!empty($errmons))
		{
			error_log('Error remove monitors: {'. implode('},{', $errmons) . '}');  //DEBUG
		}

		// Remove from DB
		/*
		$delete = $db->prepare('delete from monitors where exp_id=:exp_id and setup_id=:setup_id');
		$result = $delete->execute(array(
				':exp_id'    => (int)$experiment->id,
				':setup_id'  => (int)$setup->id
		));
		*/
		//foreach($delmons as $uuid)
		foreach($monitors as $mon)
		{
			$delete = $db->prepare('delete from monitors where uuid=:uuid');
			$result = $delete->execute(array(
					':uuid' => (string)$mon->uuid
			));
		}

		// Set sensors and values list for backend sensors API
		$params_array = array();
		foreach($sensors as $sensor)
		{
			$params_array[] = (object) array(
					'Sensor'   => $sensor->id,
					'ValueIdx' => (int) $sensor->sensor_val_id
			);
		}

		// Set parameters for backend sensors API
		$now = new DateTime();
		$nowutc = clone $now;
		$nowutc->setTimezone(new DateTimeZone('UTC'));
		// Check Setup mode
		if ($setup->amount)
		{
			$count = (int)$setup->amount;
			// Safe end time
			// TODO: can dont stop detections by stop at time, and repeat infinitely and stop only by manual
			//$stopat = System::nulldate();

			// xxx: need add some time to interval for truely detect last values
			//$overflow = (int)$setup->interval; // one interval
			$overflow = 1;
			$stopat = (new DateTime($nowutc->format(DateTime::RFC3339)))->modify('+' . ($count * (int)$setup->interval + $overflow) . ' sec')->format(System::DATETIME_RFC3339_UTC);
		}
		else
		{
			// Must be defined count values buffer fo RRD
			$count = (int) ceil((int)$setup->time_det / ((int)$setup->interval > 0 ? (int)$setup->interval : 1));
			$stopat = (new DateTime($nowutc->format(DateTime::RFC3339)))->modify('+' . (int)$setup->time_det . ' sec')->format(System::DATETIME_RFC3339_UTC);
		}

		$query_params = array(
				'Values' => $params_array,
				'Step'   => (int)$setup->interval,
				'Count'  => $count,
				'StopAt' => $stopat
		);

		// Send request for starting monitor
		$socket = new JSONSocket($this->config['socket']['path']);
		$result = $socket->call('Lab.StartMonitor', (object) $query_params);

		// Try get uuid of created monitor
		if (empty($result))
		{
			$this->error = L::setup_ERROR_MONITORING_NOT_STARTED;

			return false;
		}

		// Set active state
		$setup->set('flag', true);
		$setup->save();

		// Save started monitor uuid
		$monitor = new Monitor();
		$monitor->set('exp_id', (int)$experiment->id);
		$monitor->set('setup_id', (int)$setup->id);
		$monitor->set('uuid', (string)$result);
		$monitor->set('created', $nowutc->format(System::DATETIME_RFC3339_UTC));

		$result = $monitor->save();
		if (!$result)
		{
			error_log('PDOError: '.var_export($update->errorInfo(),true));  //DEBUG
		}

		// Update experiment start
		$sql_exp_update_query = "update experiments set DateStart_exp = :DateStart_exp where id = :id and ((DateStart_exp isnull) or (DateStart_exp = 0) or (DateStart_exp = ''))";
		$update = $db->prepare($sql_exp_update_query);
		$result = $update->execute(array(
				':DateStart_exp' => (new DateTime())->format('U'),
				':id' => $experiment->id
		));
		if (!$result)
		{
			error_log('PDOError: '.var_export($update->errorInfo(),true));  //DEBUG
		}
		// Update experiment stop reset
		$sql_exp_update_query = "update experiments set DateEnd_exp = NULL where id = :id";
		$update = $db->prepare($sql_exp_update_query);
		$result = $update->execute(array(
				':id' => $experiment->id
		));
		if (!$result)
		{
			error_log('PDOError: '.var_export($update->errorInfo(),true));  //DEBUG
		}

		return array('result' => true);






		return false;
	}


	/**
	 * Stop experiment monitoring.
	 * API method: Sensors.experimentStart
	 * API params: experiment[, uuid]
	 *
	 * @param  array $params  Array of parameters
	 *
	 * @return array  Result in form array('result' => uuid) or False on error
	 */
	function experimentStop($params)
	{
		if(!empty($params['experiment']))
		{
			$experiment = (new Experiment())->load($params['experiment']);
			if(!empty($experiment->setup_id))
			{
				// Check access to experiment
				if(!($experiment->session_key == $this->session()->getKey() || $this->session()->getUserLevel() == 3))
				{
					$this->error = L::ACCESS_DENIED;

					return false;
				}

				// Load Setup
				$setup = (new Setup())->load($experiment->setup_id);
				if (!$setup)
				{
					$this->error = L::ERROR_SETUP_NOT_FOUND;

					return false;
				}

				// Check active state
// 				if(!$setup->flag)
// 				{
// 					$this->error = L::setup_ERROR_SETUP_NOT_RUNNED;
				
// 					return false;
// 				}

				// Check access to control Setup
				if ($setup->master_exp_id != $experiment->id)
				{
					$this->error = L::ACCESS_DENIED;

					return false;
				}

				// Get all monitors of experiment-setup
				$monitors = (new Monitor())->loadItems(
						array(
								'exp_id'   => (int)$experiment->id,
								'setup_id' => (int)$setup->id, 
								'deleted'  => 0,
						),
						'id', 'ASC'
				);

				$db = new DB();

				// Stop monitors
				foreach ($monitors as $mon)
				{
					// TODO: call to Lab.GetMonInfo by uuid for check if need to stop

					// Prepare parameters for api method
					$query_params = array($mon->uuid);

					// Send request for get monitor info
					$socket = new JSONSocket($this->config['socket']['path']);
					$result = $socket->call('Lab.GetMonInfo', $query_params);

					// Get results
					if($result)
					{
						//Prepare results
						$nd = System::nulldate();

						if(isset($result->Created) && ($result->Created === $nd))
						{
							$result->Created = null;
						}

						if(isset($result->StopAt) && ($result->StopAt === $nd))
						{
							$result->StopAt = null;
						}

						if(isset($result->Last) && ($result->Last === $nd))
						{
							$result->Last = null;
						}

						// Set info
						$mon->info = $result;
					}

					unset($socket);

					// Stop only valid
					if(isset($mon->info))
					{
						// Prepare parameters for backend sensors API method
						$query_params = array($mon->uuid);

						// Send request for stopping monitor
						$socket = new JSONSocket($this->config['socket']['path']);
						$result = $socket->call('Lab.StopMonitor', $query_params);

						// Check stopping result
						// XXX: may be errors with messages as "Monitor xxxxxxxx is inactive"
						if ($result)
						{
						}
						else
						{
							//$this->error = L::setup_ERROR_MONITORING_NOT_STARTED;

							//TODO: add other error checking, because error operation on socket returns false too!

							error_log('Error Lab.StopMonitor' . (isset($result->error) ? ': '.var_export($result->error,true) : ''));  //DEBUG

							//return false;
						}

						unset($socket);
					}
				}

				// Get sensors info (Setup configuration)
				$sensors = SetupController::getSensors($setup->id, true);
				$cnt_sensors = count($sensors);

				// Get monitors data and copy to DB
				foreach ($monitors as $mon)
				{

					// Check Setup mode
					/*
					if ($setup->amount)
					{
					}
					else
					{
					}
					*/

					// Get only valid monitors data
					if (!isset($mon->info))
					{
						continue;
					}

					$step = 1;  // Use first RRA (1: x1, 2: x4, 3: x16)
					// xxx: need add some time after stop for truely detect last values?
					//$offset = $step;
					$offset = 0;

					$start_str = is_null($mon->info->Created) ? $mon->created : $mon->info->Created;
					$stopdt = null;
					$stop_str = '';
					if (is_null($mon->info->Last))
					{
						if (is_null($mon->info->StopAt))
						{
							$stopdt = new DateTime();
							$stopdt->setTimezone(new DateTimeZone('UTC'));
						}
						else
						{
							$stopdt = new DateTime(System::cutdatemsec($mon->info->StopAt));
							$stopdt->setTimezone(new DateTimeZone('UTC'));
							$stopdt->modify('+' . $offset . ' sec');
						}
					}
					else
					{
						if (is_null($mon->info->StopAt))
						{
							$stopdt = new DateTime(System::cutdatemsec($mon->info->Last));
							$stopdt->setTimezone(new DateTimeZone('UTC'));
							$stopdt->modify('+' . $offset . ' sec');
						}
						else
						{
							$lastdt = new DateTime(System::cutdatemsec($mon->info->Last));
							$lastdt->setTimezone(new DateTimeZone('UTC'));
							$stopdt = new DateTime(System::cutdatemsec($mon->info->StopAt));
							$stopdt->setTimezone(new DateTimeZone('UTC'));

							if ($lastdt >= $stopdt)
							{
								$stopdt->modify('+' . $offset . ' sec');
							}
							else
							{
								$stopdt = new DateTime(System::cutdatemsec($mon->info->Last));
								$stopdt->setTimezone(new DateTimeZone('UTC'));
								$stopdt->modify('+' . $offset . ' sec');
							}
						}
					}
					$stop_str = $stopdt->format(System::DATETIME_RFC3339_UTC);

					// Prepare parameters for backend sensors API method
					$query_params = array(
							'UUID'  => $mon->uuid,
							'Start' => $start_str,
							'End'   => $stop_str,
							'Step'  => System::nano($step)
					);

					// Send request for stopping monitor
					$socket = new JSONSocket($this->config['socket']['path']);
					$data = $socket->call('Lab.GetMonData', (object)$query_params);

					if (!empty($data))
					{
						$insert_values = array();
						$insert_block_size = 30;

						$datafields = array('exp_id', 'time', 'sensor_id', 'sensor_val_id', 'detection', 'error');
						$datafields_str = implode(',', $datafields );

						$question_marks = '(' . DB::placeholders('?', count($datafields)) . ')';

						$db->beginTransaction();  // speed up inserts within transaction

						$j = 0;
						foreach($data as $d)
						{
							for ($i = 0; $i < $cnt_sensors; $i++)
							{
								if (!isset($d->Readings[$i]))
								{
									continue;
								}

								// Skip after dates
								$d_time = new DateTime(System::cutdatemsec($d->Time));
								if ($d_time > $stopdt)
								{
									continue;
								}

								// Check error value
								$sensor_error = null;
								if ($d->Readings[$i] === 'NaN')
								{
									$sensor_error = 'NaN';
									$detection = null;
								}
								else
								{
									$detection = $d->Readings[$i];
								}

								//Check range
								if (is_null($sensor_error))
								{
									if (isset($sensors[$i]->min_range) && ((float)$d->Readings[$i] < (float)$sensors[$i]->min_range))
									{
										$sensor_error = 'NaN';
										$detection = $sensors[$i]->min_range;
									}
								}
								if (is_null($sensor_error))
								{
									if (isset($sensors[$i]->max_range) && ((float)$d->Readings[$i] > (float)$sensors[$i]->max_range))
									{
										$sensor_error = 'NaN';
										$detection = $sensors[$i]->max_range;
									}
								}

								// XXX: comment to no skip NaN values
								if (!is_null($sensor_error))
								{
									continue;
								}

								// Convert time to UTC
								$time = System::convertDatetimeToUTC($d->Time);

								$data_values = array($experiment->id, $time, $sensors[$i]->id, $sensors[$i]->sensor_val_id, $detection, $sensor_error);

								// Merge to long array of values
								$insert_values = array_merge($insert_values, $data_values);
								$j++;  // inc blocks


								// Query after each prepared inserts block
								if ($j >= $insert_block_size)
								{
									// Setup the placeholders and data values to insert query
									$insert_sql = 'insert into detections (' . $datafields_str . ') values ' . implode(',', array_fill(0, $j, $question_marks));

									$stmt = $db->prepare($insert_sql);
									try
									{
										$res = $stmt->execute($insert_values);
										if (!$res)
										{
											error_log('PDOError: '.var_export($stmt->errorInfo(),true));  //DEBUG
										}
									}
									catch (PDOException $e)
									{
										error_log('PDOException experimentStop(): '.var_export($e->getMessage(),true));  //DEBUG
										var_dump($e->getMessage());
									}

									// Reset block counter and arrays
									$j = 0;
									$insert_values = array();
								}
							}
						}

						// Insert remind rows
						if ($j > 0)
						{
							// Setup the placeholders and data values to insert query
							$insert_sql = 'insert into detections (' . $datafields_str . ') values ' . implode(',', array_fill(0, $j, $question_marks));

							$stmt = $db->prepare($insert_sql);
							try
							{
								$res = $stmt->execute($insert_values);
								if (!$res)
								{
									error_log('PDOError: '.var_export($stmt->errorInfo(),true));  //DEBUG
								}
							}
							catch (PDOException $e)
							{
								error_log('PDOException experimentStop(): '.var_export($e->getMessage(),true));  //DEBUG
								var_dump($e->getMessage());
							}
						}

						$db->commit();
					}
				}

				// Set active state
				$setup->set('flag', null);
				$setup->save();

				// Update experiment stop
				$sql_exp_update_query = "update experiments set DateEnd_exp = :DateEnd_exp where id = :id";
				$update = $db->prepare($sql_exp_update_query);
				$result = $update->execute(array(
						':DateEnd_exp' => (new DateTime())->format('U'),
						':id' => $experiment->id
				));
				if (!$result)
				{
					error_log('PDOError: '.var_export($update->errorInfo(),true));  //DEBUG
				}

				return array('result' => true);
			}
			else
			{
				if (empty($experiment->id))
				{
					$this->error = L::ERROR_EXPERIMENT_NOT_FOUND;
				}
				else
				{
					$this->error = L::ERROR_SETUP_NOT_FOUND;
				}

				return false;
			}
		}
		else
		{
			$this->error = L::ERROR_EXPERIMENT_NOT_FOUND;

			return false;
		}

		return false;
	}


	/**
	 * Check status of experiment monitoring.
	 * API method: Sensors.experimentStatus
	 * API params: experiment[, uuid]
	 *
	 * Return object in format:
	 * <code>
	 * {
	 *     setup : {
	 *         id                  : integer,
	 *         active              : bool,
	 *         interval            : integer,
	 *         amount              : integer,
	 *         time_det            : integer,
	 *         number_error        : integer,
	 *         period_repeated_det : integer,
	 *     }
	 *     monitor : {
	 *         uuid                : string
	 *     }
	 *     stat : {
	 *         amount              : string,
	 *         done_cnt            : string,
	 *         remain_cnt          : string,
	 *         time_det            : string,
	 *         stopat              : string,
	 *         finished            : bool,
	 *     }
	 * }
	 * </code>
	 *
	 * @param  array $params  Array of parameters
	 *
	 * @return array  Result in form array('result' => object) or False on error
	 */
	function experimentStatus($params)
	{
		if(!empty($params['experiment']))
		{
			$experiment = (new Experiment())->load($params['experiment']);
			if(!empty($experiment->setup_id))
			{
				// Check access to experiment
				if(!($experiment->session_key == $this->session()->getKey() || $this->session()->getUserLevel() == 3))
				{
					$this->error = L::ACCESS_DENIED;

					return false;
				}

				// Load Setup
				$setup = (new Setup())->load($experiment->setup_id);
				if (!$setup)
				{
					$this->error = L::ERROR_SETUP_NOT_FOUND;

					return false;
				}

				$monitors = (new Monitor())->loadItems(
						array(
								'exp_id' => (int)$experiment->id,
								'setup_id' => (int)$experiment->setup_id,
								'deleted' => 0
						),
						'id', 'DESC', 1
				);
				$monitor = (!empty($monitors)) ? $monitors[0] : null;

				// Get last monitoring info from api
				if (!empty($monitor))
				{
					// Prepare parameters for api method
					$query_params = array($monitor->uuid);

					// Send request for get monitor info
					$socket = new JSONSocket($this->config['socket']['path']);
					$result = $socket->call('Lab.GetMonInfo', $query_params);

					// Get results
					if($result)
					{
						//Prepare results
						$nd = System::nulldate();

						if(isset($result->Created) && ($result->Created === $nd))
						{
							$result->Created = null;
						}

						if(isset($result->StopAt) && ($result->StopAt === $nd))
						{
							$result->StopAt = null;
						}

						if(isset($result->Last) && ($result->Last === $nd))
						{
							$result->Last = null;
						}

						$monitor->info = $result;
					}
					else
					{
						// TODO: error get monitor data from backend api, may by need return error
					}
				}

				// Init return data
				$return = array(
					'setup'   => array(
						'id'                  => (int)$setup->id,
						'active'              => ($setup->flag ? true : false),
						'interval'            => $setup->interval,
						'amount'              => $setup->amount,
						'time_det'            => $setup->time_det,
						'number_error'        => $setup->number_error,
						'period_repeated_det' => $setup->period_repeated_det
					),
					'monitor' => null,
					'stat'    => array(
						'amount'              => '',
						'done_cnt'            => '',
						'remain_cnt'          => '',
						'time_det'            => '',
						'stopat'              => '',
						'finished'            => false
					)
				);

				if (!empty($monitor))
				{
					$return['monitor'] = array(
						'uuid'                => $monitor->uuid
					);
				}

				$setup_active    = $setup->flag ? true : false ;

				// Init stats
				$amount          = 0;
				$done_cnt        = 0;
				$remain_cnt      = 0;
				$remain_cnt_text = '';

				$now = new DateTime();
				$setup_stopat_date  = null;
				$setup_stopat_text  = '';
				$finished = null;

				// Amount of detections
				$amount = ($setup->amount ? $setup->amount : '*');

				// Get already done count of detections
				if ($monitor && isset($monitor->info))
				{
					// TODO: need from backend API Monitor.Info about last data value (DS.last_ds) and test it to "U" with last_update date

					$dt_created = new DateTime(System::cutdatemsec($monitor->info->Created));
					$dt_last = new DateTime(System::cutdatemsec($monitor->info->Last));
					if ($dt_last == $dt_created /* && $monitor->info->last_ds == "U" */)
					{
						// No data in rrd
					}
					else
					{
						$timestamp_created = $dt_created->format('U');
						$timestamp_last    = $dt_last->format('U');

						$done_cnt = ($timestamp_last >= $timestamp_created) ?
								(int)(($timestamp_last - $timestamp_created) / $monitor->info->Archives[0]->Step) :
								0;
					}
				}

				// Remain detections
				// Check Setup mode
				if ($setup->amount)
				{
					$remain_cnt = $setup->amount - $done_cnt;
					$remain_cnt = ($remain_cnt >= 0) ? $remain_cnt : 0;
					$remain_cnt_text = $remain_cnt;
				}
				else
				{
					$remain_cnt_text = '*';
				}

				// Stop at time
				if ($setup_active)
				{
					// Check Setup mode
					if ($setup->amount)
					{
						// Has monitor data
						if ($monitor && isset($monitor->info))
						{
							$setup_stopat_date = new DateTime(System::cutdatemsec($monitor->info->Created));
							$setup_stopat_date->setTimezone((new DateTime())->getTimezone());
							$setup_stopat_date->modify('+'.$setup->time().' sec');
							$setup_stopat_text = $setup_stopat_date->format(System::DATETIME_FORMAT1);

							if ($now->format('U') > $setup_stopat_date->format('U'))
							{
								$finished = true;
							}
							else
							{
								$finished = false;
							}
						}
						else
						{
							$setup_stopat_text = L::TIME_UNKNOWN;
						}
					}
					else
					{
						// Has monitor data
						if ($monitor && isset($monitor->info))
						{
							// TODO: need from backend API Monitor.Info about last data value (DS.last_ds) and test it to "U" with last_update date

							if ($monitor->info->StopAt !== System::nulldate())
							{
								$setup_stopat_date = new DateTime(System::cutdatemsec($monitor->info->StopAt));
								$setup_stopat_date->setTimezone((new DateTime())->getTimezone());
								$setup_stopat_text = $setup_stopat_date->format(System::DATETIME_FORMAT1);

								if ($now->format('U') > $setup_stopat_date->format('U'))
								{
									$finished = true;
								}
								else
								{
									$finished = false;
								}
							}
							else
							{
								$setup_stopat_date = new DateTime(System::cutdatemsec($monitor->info->Created));
								$setup_stopat_date->setTimezone((new DateTime())->getTimezone());
								$setup_stopat_date->modify('+'.$setup->time().' sec');
								$setup_stopat_text = $setup_stopat_date->format(System::DATETIME_FORMAT1);

								if ($now->format('U') > $setup_stopat_date->format('U'))
								{
									$finished = true;
								}
								else
								{
									$finished = false;
								}
							}
						}
						else
						{
							$setup_stopat_text = L::TIME_UNKNOWN;
						}
					}
				}
				else
				{
					$setup_stopat_date = new DateTime();
					$setup_stopat_date->modify('+'.$setup->time().' sec');
					$setup_stopat_text = $setup_stopat_date->format(System::DATETIME_FORMAT1);
				}

				// Fill stat data
				$return['stat']['amount']     = (string)$amount;
				$return['stat']['done_cnt']   = (string)$done_cnt;
				$return['stat']['remain_cnt'] = $remain_cnt_text;
				$return['stat']['time_det']   = System::secToTime($setup->time());
				$return['stat']['stopat']     = $setup_stopat_text;
				$return['stat']['finished']   = $finished;

				return array('result' => $return);
			}
			else
			{
				if (empty($experiment->id))
				{
					$this->error = L::ERROR_EXPERIMENT_NOT_FOUND;
				}
				else
				{
					$this->error = L::ERROR_SETUP_NOT_FOUND;
				}

				return false;
			}
		}
		else
		{
			$this->error = L::ERROR_EXPERIMENT_NOT_FOUND;

			return false;
		}

		return false;
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

