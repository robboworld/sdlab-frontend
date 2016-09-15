<?php
/**
 * Class SensorsController
 */
class SensorsController extends Controller
{

	public function __construct()
	{
		parent::__construct();
		$this->config = App::config();
	}


	public function index()
	{
		$this->addJs('functions');
		$this->addJs('chart');
		$this->addJs('Sensor');
		$this->addJs('sensors');
		// Add language translates for scripts
		Language::script(array(
				'ERROR',
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
	 * @return array  Result in form array('result' => sensor list of objects) or False on error
	 */
	public function getSensors($params)
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
		if ($socket->error())
		{
			return false;
		}
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

		foreach($result['result'] as $sensor_id => $obj)
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
					//var_dump($e->getMessage());
					error_log('PDOException: '.var_export($e->getMessage(),true));  //DEBUG
				}
			}
		}


		// Get additional sensors data
		if(isset($params['getinfo']) && $params['getinfo'])
		{
			foreach($result['result'] as $sensor_id => $sensor)
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
	 * @return array  Result in form array('result' => sensor data object of (Time, Reading)) or False on error
	 */
	public function getData($params)
	{
		$socket = new JSONSocket($this->config['socket']['path']);
		if ($socket->error())
		{
			return false;
		}

		$result = $socket->call('Lab.GetData', (object) array(
				'Sensor'   => $params['Sensor'],
				'ValueIdx' => (int) $params['ValueIdx']
		));
		unset($socket);

		if (!$result)
		{
			return false;
		}

		return $result;
	}


	/**
	 * Get data from sensors.
	 * API method: Sensors.getDataItems
	 * API params: [Sensor, ValueIdx]
	 *
	 * @param  array  $params Array of parameters
	 *
	 * @return array  Result in form array('result' => sensor array with data array (Sensor, ValueIdx, result(Time, Reading))) or False on error
	 */
	public function getDataItems($params)
	{
		if (!is_array($params))
		{
			$this->error = L::ERROR;

			return false;
		}

		// TODO: Create backend method Lab.GetItemsData for read multiple sensors on once

		$items = array();
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

			$obj['result'] = false;
			$socket = new JSONSocket($this->config['socket']['path']);
			if (!$socket->error())
			{
				$res = $socket->call('Lab.GetData', (object) array(
						'Sensor'   => $sensor['Sensor'],
						'ValueIdx' => $idx
				));
				if($res)
				{
					$obj['result'] = $res['result'];
				}
			}

			unset($socket);

			$items[] = $obj;
		}

		if (empty($items))
		{
			$this->error = L::ERROR;

			return false;
		}

		return array('result' => $items);
	}


	/**
	 * Get data strob from Setup for experiment or special monitor in experiment.
	 * API method: Sensors.experimentStrob
	 * API params: experiment[, uuid]
	 * 
	 * @param  array $params  Array of parameters
	 * 
	 * @return array  Result in form array('result' => DATA) or False on error
	 */
	public function experimentStrob($params)
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
		// (view access need for share experiment in future)
		if(!$experiment->userCanEdit($this->session()))
		{
			$this->error = L::ACCESS_DENIED;

			return false;
		}

		$now = new DateTime();
		$nowutc = clone $now;
		$nowutc->setTimezone(new DateTimeZone('UTC'));

		if(isset($params['uuid']) && !empty($params['uuid']))
		{
			// Check monitor
			// Get only one last monitor in experiment with this uuid!
			$monitors = (new Monitor())->loadItems(
					array(
							'exp_id' => (int)$experiment->id,
							'uuid'   => $params['uuid'],
					),
					'id', 'DESC',
					1
			);
			if ($monitors === false)
			{
				$monitor = null;
			}
			else
			{
				$monitor = $monitors[0];
			}
			if(empty($monitor))
			{
				$this->error = L::ERROR_MONITOR_NOT_FOUND;

				return false;
			}

			// Load Setup
			$monitor->setup = (new Setup())->load($monitor->setup_id);

			//Check access to control Setup
			// Allow access to control of unknown orphaned Setup
			if ($monitor->setup && !$monitor->setup->userCanControl($this->session(), $experiment->id))
			{
				$this->error = L::ACCESS_DENIED;

				return false;
			}

			// Dates for experiment update
			$period = System::nano(1);  // 1 second by default
			$waitsec = (int)ceil((float)$period/1000000000);
			$dateend_exp = (new DateTime($nowutc->format(DateTime::RFC3339)))->modify('+' . $waitsec . ' sec');

			// Prepare array of parameters for API method
			$request_params = array(
					'UUID'       => $monitor->uuid,
			);
		}
		else
		{
			// Check Setup
			if(empty($experiment->setup_id))
			{
				$this->error = L::ERROR_SETUP_NOT_FOUND;

				return false;
			}

			// Get sensors for experiment with Setup
			$sensors = SetupController::getSensors($experiment->setup_id, true);
			if(empty($sensors))
			{
				// TODO: error message about empty Setup or no sensors

				return false;
			}

			// Load Setup
			$setup = (new Setup())->load($experiment->setup_id);
			if (!$setup)
			{
				$this->error = L::ERROR_SETUP_NOT_FOUND;

				return false;
			}

			//Check access to control Setup
			if (!$setup->userCanControl($this->session(), $experiment->id))
			{
				// Setup is busy by other experiment
				$this->error = L::ACCESS_DENIED;

				return false;
			}

			// Prepare sensors list for API method
			$period = System::nano(1);  // 1 second by default
			$params_array = array();
			foreach($sensors as $sensor)
			{
				$params_array[] = (object) array(
						'Sensor'   => $sensor->sensor_id,
						'ValueIdx' => (int) $sensor->sensor_val_id
				);
				// Get period from slower sensor
				if (isset($sensor->resolution))
				{
					$r = floatval($sensor->resolution);
					if (($r > 0) && ($r > $period))
					{
						$period = $r;
					}
				}
			}
			$waitsec = (int)ceil((float)$period/1000000000);
			$dateend_exp = (new DateTime($nowutc->format(DateTime::RFC3339)))->modify('+' . $waitsec . ' sec');

			// Prepare array of parameters for API method
			$request_params = array(
					'UUID'       => '',
					'Opts'       => (object) array(
							'Exp_id'    => (int)$experiment->id,
							'Setup_id'  => 0,                                                   // NOT USED, but it must be any integer
							'Step'      => 0,                                                   // NOT USED, but it must be any integer
							'Count'     => 1,                                                   // NOT USED, but it must be any integer
							'Duration'  => 0,                                                   // NOT USED, but it must be any integer
							'StopAt'    => $dateend_exp->format(System::DATETIME_RFC3339_UTC),  // NOT USED, but it must be any correct time
							'Values'    => $params_array
					),
					'OptsStrict' => false  // no errors on incorrect sensor/value, just save as NaN detections
			);
		}


		// Send request for start series consists of one detection
		$socket = new JSONSocket($this->config['socket']['path']);
		if ($socket->error())
		{
			$this->error = L::ERROR;

			return false;
		}

		$respond = $socket->call('Lab.StrobeMonitor', (object) $request_params);
		if(!$respond)
		{
			$this->error = L::ERROR;

			return false;
		}
		else
		{
			// If returned true try get results
			if(!$respond['result'])
			{
				$this->error = L::setup_ERROR_STROBE_NOT_CREATED;

				return false;
			}
		}

		// Close socket
		unset($socket);

		$db = new DB();

		// Update experiment dates
		$db->beginTransaction();  // speed up inserts within transaction

		// Update experiment start if empty
		$sql_exp_update = "update experiments set DateStart_exp = :DateStart_exp where id = :id and ((DateStart_exp isnull) or (DateStart_exp = 0) or (DateStart_exp = ''))";
		$update = $db->prepare($sql_exp_update);
		$result = $update->execute(array(
				':DateStart_exp' => $now->format('U'),
				':id' => $experiment->id
		));
		if (!$result)
		{
			error_log('PDOError: '.var_export($update->errorInfo(),true));  //DEBUG
		}
		// Update experiment stop
		$sql_exp_update = "update experiments set DateEnd_exp = :DateEnd_exp where id = :id and
			(((DateEnd_exp isnull) or (DateEnd_exp = 0) or (DateEnd_exp = '')) or (((DateEnd_exp notnull) and (DateEnd_exp != 0) and (DateEnd_exp != '')) and (DateEnd_exp < strftime('%s',:DateEnd_exp_planned))))";
		$update = $db->prepare($sql_exp_update);
		$result = $update->execute(array(
				':DateEnd_exp'         => $dateend_exp->format('U'),
				':id'                  => $experiment->id,
				':DateEnd_exp_planned' => $dateend_exp->format('U')
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
	public function experimentStart($params)
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


		// Check Setup
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
			$this->error = L::ERROR;

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
		/*
		if(Setup::isActive($setup->id, $experiment->id))
		{
			$this->error = L::setup_ACTIVE_ALREADY;

			return false;
		}
		*/

		// Check access to control Setup
		// TODO: if already assign to experiment than can control/Start? But there are other condition on experiment view with Start experiment access.
		if (!$setup->userCanControl($this->session(), $experiment->id))
		{
			$this->error = L::ACCESS_DENIED;

			return false;
		}

		$db = new DB();


		// Purge monitoring history

		// Get old monitorings with this Setup in this experiment
		$monitors = (new Monitor())->loadItems(
				array(
						'exp_id'   => (int)$experiment->id,
						'setup_id' => (int)$setup->id,
				),
				'id', 'ASC'
		);

		// Remove all old monitors
		// TODO: may be not remove old monitors now, just update DB monitors with set deleted flag, remove with backend api only by cron?

		// Remove/Stop monitors call for backend sensors API
		$delmons = array();
		$errmons = array();
		foreach($monitors as $mon)
		{
			// Send request for removing monitor
			$request_params = (object) array(
					'UUID'     => (string) $mon->uuid,
					'WithData' => false
			);
			$socket = new JSONSocket($this->config['socket']['path']);
			if (!$socket->error())
			{
				$respond = $socket->call('Lab.RemoveMonitor', $request_params);
				if ($respond && $respond['result'])
				{
					$delmons[] = $mon->uuid;
				}
				else
				{
					$errmons[] = $mon->uuid;
				}
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
		// Remove monitors from DB made by backend API at Lab.RemoveMonitor

		// Set sensors and values list for backend sensors API
		$params_array = array();
		foreach($sensors as $sensor)
		{
			$params_array[] = (object) array(
					'Sensor'   => $sensor->sensor_id,
					'ValueIdx' => (int) $sensor->sensor_val_id
			);
		}

		// Set parameters for backend sensors API
		$now = new DateTime();
		$nowutc = clone $now;
		$nowutc->setTimezone(new DateTimeZone('UTC'));
		// Check Setup mode
		$count = 0;
		$stopat = System::nulldate();
		if ($setup->amount)
		{
			$count = (int)$setup->amount;

			// + Stop At condition
			if ((int)$setup->time_det != 0)
			{
				$stopat = (new DateTime($nowutc->format(DateTime::RFC3339)))->modify('+' . (int)$setup->time_det . ' sec')->format(System::DATETIME_RFC3339_UTC);
			}
		}
		else
		{
			$stopat = (new DateTime($nowutc->format(DateTime::RFC3339)))->modify('+' . (int)$setup->time_det . ' sec')->format(System::DATETIME_RFC3339_UTC);
		}

		$request_params = array(
				'Exp_id'   => (int)$experiment->id,
				'Setup_id' => (int)$setup->id,
				'Step'     => (int)$setup->interval,
				'Count'    => $count,
				'Duration' => (int)$setup->time_det,
				'StopAt'   => $stopat,
				'Values'   => $params_array
		);

		// Send request for starting monitor
		$socket = new JSONSocket($this->config['socket']['path']);
		if ($socket->error())
		{
			$this->error = L::ERROR;

			return false;
		}
		$uuid = null;
		$result = $socket->call('Lab.StartMonitor', (object) $request_params);
		if (!$result)
		{
			$this->error = L::ERROR;

			return false;
		}
		else
		{
			// Try get uuid of created monitor
			if (!isset($result['result']) || empty($result['result']))
			{
				$this->error = L::setup_ERROR_MONITORING_NOT_STARTED;

				return false;
			}
			else
			{
				$uuid = $result['result'];
			}
		}

		// Update experiment start date
		$sql_exp_update = "update experiments set DateStart_exp = :DateStart_exp where id = :id and ((DateStart_exp isnull) or (DateStart_exp = 0) or (DateStart_exp = ''))";
		$update = $db->prepare($sql_exp_update);
		$result = $update->execute(array(
				':DateStart_exp' => (new DateTime())->format('U'),
				':id' => $experiment->id
		));
		if (!$result)
		{
			error_log('PDOError: '.var_export($update->errorInfo(),true));  //DEBUG
		}
		// Update experiment stop date reset
		$sql_exp_update = "update experiments set DateEnd_exp = NULL where id = :id";
		$update = $db->prepare($sql_exp_update);
		$result = $update->execute(array(
				':id' => $experiment->id
		));
		if (!$result)
		{
			error_log('PDOError: '.var_export($update->errorInfo(),true));  //DEBUG
		}

		return array('result' => $uuid);
	}


	/**
	 * Stop experiment monitoring with current Setup.
	 * API method: Sensors.experimentStop
	 * API params: experiment[, uuid]
	 *
	 * @param  array $params  Array of parameters
	 *
	 * @return array  Result in form array('result' => uuid) or False on error
	 */
	public function experimentStop($params)
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


		// Check Setup
		if(empty($experiment->setup_id))
		{
			$this->error = L::ERROR_SETUP_NOT_FOUND;

			return false;
		}

		// Load Setup
		$setup = (new Setup())->load($experiment->setup_id);
		if (!$setup)
		{
			$this->error = L::ERROR_SETUP_NOT_FOUND;

			return false;
		}

		//Check active state
		/*
		if(!Setup::isActive($setup->id,$experiment->id))
		{
			$this->error = L::setup_ERROR_SETUP_NOT_RUNNED;

			return false;
		}
		*/

		// Check access to control Setup from experiment
		// TODO: if already assign to experiment than can control/Start? But there are other condition on experiment view with Start experiment access.
		// XXX: if already started experiment with setup than can control/Stop!
		/*
		if (!$setup->userCanControl($this->session(), $experiment->id))
		{
			$this->error = L::ACCESS_DENIED;

			return false;
		}
		*/

		// Find and stop all active monitors with this Setup in this experiment

		// Get all monitors of experiment-setup
		$monitors = (new Monitor())->loadItems(
				array(
						'exp_id'   => (int)$experiment->id,
						'setup_id' => (int)$setup->id,
				),
				'id', 'ASC'
		);

		// Stop monitors
		foreach ($monitors as $mon)
		{
			// Prepare parameters for api method
			$request_params = array($mon->uuid);

			// Send request for get monitor info
			$socket = new JSONSocket($this->config['socket']['path']);
			if ($socket->error())
			{
				$this->error = L::ERROR;

				return false;
			}
			$result = $socket->call('Lab.GetMonInfo', $request_params);

			// Get results
			if($result && $result['result'])
			{
				// Set info
				$mon->info = $result['result'];
			}

			unset($socket);

			// Stop only valid
			if(isset($mon->info))
			{
				// Prepare parameters for backend sensors API method
				$request_params = array($mon->uuid);

				// Send request for stopping monitor
				$socket = new JSONSocket($this->config['socket']['path']);
				if ($socket->error())
				{
					$this->error = L::ERROR;

					return false;
				}
				$result = $socket->call('Lab.StopMonitor', $request_params);
				if (!$result)
				{
					$this->error = L::ERROR;

					return false;
				}

				unset($socket);

				// Check stopping result
				if ($result && $result['result'])
				{
					// XXX: Its no error if "Monitor xxxxxxxx is inactive".
				}
				else
				{
					$this->error = L::setup_ERROR_MONITORING_NOT_STOPPED;
					error_log('Error Lab.StopMonitor' . (isset($result->error) ? ': '.var_export($result->error,true) : ''));  //DEBUG

					return false;
				}
			}
		}

		$db = new DB();

		// Update experiment stop
		$sql_exp_update = "update experiments set DateEnd_exp = :DateEnd_exp where id = :id";
		$update = $db->prepare($sql_exp_update);
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


	/**
	 * Check status of experiment current Setup monitoring.
	 * API method: Sensors.experimentStatus
	 * API params: experiment[, uuid]
	 *
	 * Return object in format:
	 * <code>
	 * {
	 *     setup : {
	 *         id                  : integer,
	 *         interval            : integer,
	 *         amount              : integer,
	 *         time_det            : integer,
	 *         number_error        : integer,
	 *         period_repeated_det : integer,
	 *         active              : bool,
	 *     }
	 *     monitors : {
	 *         uuid                : string,
	 *         data : {
	 *             'amount'        : string,
	 *             'done_cnt'      : string,
	 *             'remain_cnt'    : string,
	 *             'err_cnt'       : integer,
	 *             'duration'      : string,
	 *             'stopat'        : string,
	 *             'active'        : bool,
	 *             'finished'      : bool,
	 *         }
	 *     }
	 * }
	 * </code>
	 *
	 * @param  array $params  Array of parameters
	 *
	 * @return array  Result in form array('result' => object) or False on error
	 */
	public function experimentStatus($params)
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

		// Check access to experiment view
		if(!$experiment->userCanView($this->session()))
		{
			$this->error = L::ACCESS_DENIED;

			return false;
		}

		// Load current Setup
		$setup = null;
		$setup_active = false;
		if(!empty($experiment->setup_id))
		{
			$setup = (new Setup())->load($experiment->setup_id);
			if ($setup)
			{
				$setup_active = Setup::isActive($setup->id, (int)$experiment->id);
			}
			else
			{
				$setup = null;
			}
		}

		// Get all monitorings with this experiment from DB
		$filters = array('exp_id' => (int)$experiment->id);
		// Filter by monitor uuid(s)
		if(isset($params['uuid']) && !empty($params['uuid']))
		{
			$filters['uuid'] = $params['uuid'];
		}
		$monitors = (new Monitor())->loadItems($filters, 'id', 'ASC');

		foreach ($monitors as $i => $monitor)
		{
			// Get monitoring info from api

			// Prepare parameters for api method
			$request_params = array($monitor->uuid);

			// Send request for get monitor info
			$socket = new JSONSocket($this->config['socket']['path']);
			if($socket->error())
			{
				$this->error = L::ERROR;

				return false;
			}

			// Get results
			$result = $socket->call('Lab.GetMonInfo', $request_params);
			if($result && $result['result'])
			{
				//Prepare results
				$nd = System::nulldate();

				if(isset($result['result']->Created) && ($result['result']->Created === $nd))
				{
					$result['result']->Created = null;
				}

				if(isset($result['result']->StopAt) && ($result['result']->StopAt === $nd))
				{
					$result['result']->StopAt = null;
				}

				if(isset($result['result']->Last) && ($result['result']->Last === $nd))
				{
					$result['result']->Last = null;
				}

				$monitors[$i]->info = $result['result'];
			}
			else
			{
				// TODO: error get monitor data from backend api, may be need return error
			}
			unset($socket);
		}

		// Init return data
		$return = array(
			'setup'    => null,
			'monitors' => array(),
		);

		if ($setup)
		{
			$return['setup'] = array();
			$return['setup']['id']                  = $setup->id;
			$return['setup']['interval']            = $setup->interval;
			$return['setup']['amount']              = $setup->amount;
			$return['setup']['time_det']            = $setup->time_det;
			$return['setup']['number_error']        = $setup->number_error;
			$return['setup']['period_repeated_det'] = $setup->period_repeated_det;

			$return['setup']['active']              = ($setup_active ? true : false);
		}

		$now = new DateTime();

		foreach ($monitors as $monitor)
		{
			$mon = array(
					'uuid' => $monitor->uuid,
					'data' => null
			);

			if (isset($monitor->info))
			{
				$mon['data'] = array(
						'amount'              => '',
						'done_cnt'            => '',
						'remain_cnt'          => '',
						'err_cnt'             => 0,
						'duration'            => '',
						'stopat'              => '',
						'active'              => false,
						'finished'            => false
				);

				// Init stats
				$finished = null;

				// Init stats
				$mon_done_cnt        = 0;
				$mon_remain_cnt      = 0;
				$mon_remain_cnt_text = '';
				$mon_err_cnt         = 0;
				$mon_stopat_date     = null;
				$mon_stopat_text     = '';

				// Get already done count of detections
				$mon_done_cnt = $monitor->info->Counters->Done;
				$mon_err_cnt  = $monitor->info->Counters->Err;

				// Check mode
				if ($monitor->amount)
				{
					// Amount detections mode

					// Amount of detections
					$amount = $monitor->amount;

					// Remain detections
					if ($mon_done_cnt >= $monitor->amount)
					{
						$finished = true;
					}
					$mon_remain_cnt = $monitor->amount - $mon_done_cnt;
					$mon_remain_cnt = ($mon_remain_cnt >= 0) ? $mon_remain_cnt : 0;
					$mon_remain_cnt_text = $mon_remain_cnt;

					// Stop at time calc

					// + Stop At condition
					if ($monitor->stopat !== System::nulldate())
					{
						$mon_stopat_date = new DateTime(System::cutdatemsec($monitor->stopat));
						$mon_stopat_date->setTimezone((new DateTime())->getTimezone());
						$mon_stopat_text = $mon_stopat_date->format(System::DATETIME_FORMAT1);

						if ($now->format('U') > $mon_stopat_date->format('U'))
						{
							$finished = true;
						}
						else
						{
							if ($finished === null)
							{
								$finished = false;
							}
						}
					}
					else
					{
						// Get Approximately Stop At: Created + Monitor.time(sec)

						$mon_stopat_date = new DateTime(System::cutdatemsec($monitor->created));
						$mon_stopat_date->setTimezone((new DateTime())->getTimezone());
						$mon_stopat_date->modify('+'.$monitor->time().' sec');
						$mon_stopat_text = $mon_stopat_date->format(System::DATETIME_FORMAT1);

						if ($now->format('U') > $mon_stopat_date->format('U'))
						{
							/*
							if ($finished === null)
							{
								$finished = true;
							}
							*/
							$finished = false;
						}
						else
						{
							if ($finished === null)
							{
								$finished = false;
							}
						}
					}
				}
				else
				{
					// StopAt mode

					// Amount of detections
					$amount = '*';

					// Remain detections
					$mon_remain_cnt_text = '*';

					// Stop at time calc

					if ($monitor->stopat !== System::nulldate())
					{
						$mon_stopat_date = new DateTime(System::cutdatemsec($monitor->stopat));
						$mon_stopat_date->setTimezone((new DateTime())->getTimezone());
						$mon_stopat_text = $mon_stopat_date->format(System::DATETIME_FORMAT1);

						if ($now->format('U') > $mon_stopat_date->format('U'))
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
						$mon_stopat_text   = L::TIME_UNKNOWN;
					}
				}

				// Fill stat data
				$mon['data']['amount']     = (string)$amount;
				$mon['data']['done_cnt']   = (string)$mon_done_cnt;
				$mon['data']['remain_cnt'] = $mon_remain_cnt_text;
				$mon['data']['err_cnt']    = (string)$mon_err_cnt;
				$mon['data']['duration']   = System::secToTime($monitor->duration);
				$mon['data']['stopat']     = $mon_stopat_text;
				$mon['data']['active']     = ($monitor->active ? true : false);
				$mon['data']['finished']   = $finished;
			}

			$return['monitors'][] = $mon;
		}

		return array('result' => $return);
	}

	/**
	 * Stop experiment monitoring.
	 * API method: Sensors.monitorStop
	 * API params: experiment, uuid
	 *
	 * @param  array $params  Array of parameters
	 *
	 * @return array  Result in form array('result' => True) or False on error
	 */
	public function monitorStop($params)
	{
		if(empty($params['experiment']))
		{
			$this->error = L::ERROR_EXPERIMENT_NOT_FOUND;

			return false;
		}

		if(empty($params['uuid']))
		{
			$this->error = L::ERROR_MONITOR_NOT_FOUND;

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
		// (view access need for share experiment in future)
		if(!$experiment->userCanEdit($this->session()))
		{
			$this->error = L::ACCESS_DENIED;

			return false;
		}

		// Find and stop monitors

		// Get this monitors of experiment
		$monitors = (new Monitor())->loadItems(
				array(
						'exp_id' => (int)$experiment->id,
						'uuid' => $params['uuid'],
				),
				'id', 'ASC'
		);

		// Stop monitors
		foreach ($monitors as $mon)
		{
			// Always allow access if already use monitor and have access to experiment
			/*
			// Load Setup
			$mon->setup = (new Setup())->load($mon->setup_id);

			//Check access to control Setup
			if ($monitor->setup && !$monitor->setup->userCanControl($this->session(), $experiment->id))
			{
				$this->error = L::ACCESS_DENIED;

				return false;
			}
			*/

			// Prepare parameters for api method
			$request_params = array($mon->uuid);

			// Send request for get monitor info
			$socket = new JSONSocket($this->config['socket']['path']);
			if ($socket->error())
			{
				$this->error = L::ERROR;

				return false;
			}
			$result = $socket->call('Lab.GetMonInfo', $request_params);

			// Get results
			if($result && $result['result'])
			{
				// Set info
				$mon->info = $result['result'];
			}

			unset($socket);

			// Stop only valid
			if(isset($mon->info))
			{
				// Prepare parameters for backend sensors API method
				$request_params = array($mon->uuid);

				// Send request for stopping monitor
				$socket = new JSONSocket($this->config['socket']['path']);
				if ($socket->error())
				{
					$this->error = L::ERROR;

					return false;
				}
				$result = $socket->call('Lab.StopMonitor', $request_params);
				if (!$socket)
				{
					$this->error = L::ERROR;

					return false;
				}

				unset($socket);

				// Check stopping result
				if ($result && $result['result'])
				{
					// XXX: Its no error if "Monitor xxxxxxxx is inactive".
				}
				else
				{
					$this->error = L::setup_ERROR_MONITORING_NOT_STOPPED;
					error_log('Error Lab.StopMonitor' . (isset($result->error) ? ': '.var_export($result->error,true) : ''));  //DEBUG

					return false;
				}
			}
		}

		$db = new DB();

		// Update experiment stop
		$sql_exp_update = "update experiments set DateEnd_exp = :DateEnd_exp where id = :id";
		$update = $db->prepare($sql_exp_update);
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

	/**
	 * Remove experiment monitoring.
	 * API method: Sensors.monitorRemove
	 * API params: experiment, uuid[, withdata]
	 *
	 * @param  array $params  Array of parameters
	 *
	 * @return array  Result in form array('result' => True) or False on error
	 */
	public function monitorRemove($params)
	{
		if(empty($params['experiment']))
		{
			$this->error = L::ERROR_EXPERIMENT_NOT_FOUND;

			return false;
		}

		if(empty($params['uuid']))
		{
			$this->error = L::ERROR_MONITOR_NOT_FOUND;

			return false;
		}

		// If remove monitor meta info with detections data
		$withData = (isset($params['withdata']) && $params['withdata'] == true ? true : false);

		// Load experiment
		$experiment = (new Experiment())->load($params['experiment']);
		if(!$experiment)
		{
			$this->error = L::ERROR_EXPERIMENT_NOT_FOUND;

			return false;
		}

		// Check access to experiment edit
		// (view access need for share experiment in future)
		if(!$experiment->userCanEdit($this->session()))
		{
			$this->error = L::ACCESS_DENIED;

			return false;
		}

		// Find and remove monitors

		// Get this monitors of experiment
		$monitors = (new Monitor())->loadItems(
				array(
						'exp_id' => (int)$experiment->id,
						'uuid' => $params['uuid'],
				),
				'id', 'ASC'
		);

		// Remove/Stop monitors call for backend sensors API
		$delmons = array();
		$errmons = array();
		foreach($monitors as $mon)
		{
			// Send request for removing monitor
			$request_params = (object) array(
					'UUID'     => (string) $mon->uuid,
					'WithData' => $withData
			);
			$socket = new JSONSocket($this->config['socket']['path']);
			if (!$socket->error())
			{
				$respond = $socket->call('Lab.RemoveMonitor', $request_params);
				if ($respond && $respond['result'])
				{
					$delmons[] = $mon->uuid;
				}
				else
				{
					$errmons[] = $mon->uuid;
				}
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
		// Remove monitors from DB made by backend API at Lab.RemoveMonitor

		return array('result' => true);
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
				$list .= '<a class="'.$class_string.'" href="#" data-id="'.htmlspecialchars($item->id, ENT_QUOTES, 'UTF-8').'">'.htmlspecialchars($item->title, ENT_QUOTES, 'UTF-8').'</a>';
			}
			return $list;
		}
		else
		{
			return false;
		}
	}
}

