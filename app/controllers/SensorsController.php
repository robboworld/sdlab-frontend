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
		//var_dump($socket->error());
		//var_dump($result);
		if ($result)
		{
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

			// Prepare insert query
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
						$value->value_name  = System::getValsTranslate($value->{'Name'});
						$value->si_notation = System::getValsTranslate($value->{'Name'}, 'si_notation');
						$value->si_name     = System::getValsTranslate($value->{'Name'}, 'si_name');
					}
				}
			}

			return $result; //лишнее вложение в массиве
		}
		else
		{
			return false;
		}

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

				/* получаем датчики для эксперимента */
				$sensors = SetupController::getSensors($experiment->setup_id, true);
				if(!empty($sensors))
				{
					// Load Setup
					$setup = (new Setup())->load($experiment->setup_id);
					if (!$setup)
					{
						$this->error = 'Setup not found';

						return false;
					}

					// Check active state
					if(!$setup->flag)
					{
						// Not use set active state on strob requests
						//$setup->set('flag', true);
						//$setup->save();
					}
					else 
					{
						//Check access to control Setup

						// Access only if current experiment is master of Setup
						if ($setup->master_exp_id != $experiment->id)
						{
							$this->error = 'Access denied';

							return false;
						}
					}

					/* формируем список сенсоров для метода апи датчиков*/
					$params_array = array();
					foreach($sensors as $sensor)
					{
						$params_array[] = (object) array(
								'Sensor'   => $sensor->id,
								'ValueIdx' => (int) $sensor->sensor_val_id
						);
					}

					/* формируем массив параметров для метода апи датчиков*/
					$query_params = array(
						'Values' => $params_array,
						'Period' => System::nano(1),
						'Count'  => 1
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

								$insert->execute(array(
									':exp_id' => $experiment->id,
									':time' => $result[0]->Time,
									':sensor_id' => $sensors[$i]->id,
									':sensor_val_id' => $sensors[$i]->sensor_val_id,
									':detection' => $result[0]->Readings[$i],
									':error' => $sensor_error
								));
							}

							return array('result' => true);
						}
						else
						{
							$this->error = 'Empty response';

							return false;
						}
					}
					else
					{
						$this->error = 'Series not started';

						return false;
					}

				}
				else 
				{
					return false;
				}
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

				// Get sensors for experiment
				$sensors = SetupController::getSensors($experiment->setup_id);
				if(!empty($sensors))
				{
					// Load Setup
					$setup = (new Setup())->load($experiment->setup_id);
					if (!$setup)
					{
						$this->error = 'Setup not found';

						return false;
					}

					// Check active state
					if($setup->flag)
					{
						$this->error = 'Setup already active';

						return false;
					}


					// Check access to control Setup
					if ($setup->master_exp_id != $experiment->id)
					{
						$this->error = 'Access denied';
					
						return false;
					}

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

					// Set active state
					$setup->set('flag', true);
					$setup->save();


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
					// Check Setup mode
					if ($setup->amount)
					{
						$count = (int)$setup->amount;
						// Safe end time
						// TODO: can dont stop detections by stop at time, and repeat infinitely and stop only by manual
						//$stopat = System::nulldate();
						$overflow = (int)$setup->interval; // one interval
						$stopat = (new DateTime())->modify('+' . ($count * (int)$setup->interval + $overflow) . ' sec')->format('Y-m-d\TH:i:s\Z');
					}
					else
					{
						// Must be defined count values buffer fo RRD
						$count = (int) ceil((int)$setup->time_det / ((int)$setup->interval > 0 ? (int)$setup->interval : 1));
						$stopat = (new DateTime())->modify('+' . (int)$setup->time_det . ' sec')->format('Y-m-d\TH:i:s\Z');
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
					if (!empty($result))
					{
						// Save started monitor uuid
						$monitor = new Monitor();
						$monitor->set('exp_id', (int)$experiment->id);
						$monitor->set('setup_id', (int)$setup->id);
						$monitor->set('uuid', (string)$result);
						$monitor->set('created', System::dateformat('now', 'Y-m-d\TH:i:s\Z'));

						$result = $monitor->save();

						return array('result' => true);
					}
					else
					{
						$this->error = 'Monitoring not started';

						return false;
					}
				}
				else
				{
					return false;
				}
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
					$this->error = 'Access denied';
	
					return false;
				}

				// Load Setup
				$setup = (new Setup())->load($experiment->setup_id);
				if (!$setup)
				{
					$this->error = 'Setup not found';
				
					return false;
				}

				// Check active state
// 				if(!$setup->flag)
// 				{
// 					$this->error = 'Setup not runned';
				
// 					return false;
// 				}

				// Check access to control Setup
				if ($setup->master_exp_id != $experiment->id)
				{
					$this->error = 'Access denied';
				
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
					if($mon->info)
					{
						// Prepare parameters for backend sensors API method
						$query_params = array($mon->uuid);

						// Send request for stopping monitor
						$socket = new JSONSocket($this->config['socket']['path']);
						$result = $socket->call('Lab.StopMonitor', $query_params);

						// Check stopping result
						// XXX: may be errors with messages as "Monitor xxxxxxxx is inactive"
						/*
						if ($result)
						{
						}
						else
						{
							$this->error = 'Monitoring not started';

							return false;
						}
						*/

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
					$offset = $step;

					$start = is_null($mon->info->Created) ? $mon->created : $mon->info->Created;
					if (is_null($mon->info->Last))
					{
						if (is_null($mon->info->StopAt))
						{
							$stop = (new DateTime('now'))->format('Y-m-d\TH:i:s\Z');
						}
						else
						{
							$stop = (new DateTime(System::cutdatemsec($mon->info->StopAt)))->modify('+' . $offset . ' sec')->format('Y-m-d\TH:i:s\Z');
						}
					}
					else
					{
						$stop = (new DateTime(System::cutdatemsec($mon->info->Last)))->modify('+' . $offset . ' sec')->format('Y-m-d\TH:i:s\Z');
					}

					// Prepare parameters for backend sensors API method
					$query_params = array(
							'UUID'  => $mon->uuid,
							'Start' => $start,
							'End'   => $stop,
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

						$j = 0;
						foreach($data as $d)
						{
							for ($i = 0; $i < $cnt_sensors; $i++)
							{
								if (!isset($d->Readings[$i]))
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

								$data_values = array($experiment->id, $d->Time, $sensors[$i]->id, $sensors[$i]->sensor_val_id, $detection, $sensor_error);

								// Merge to long array of values
								$insert_values = array_merge($insert_values, $data_values);
								$j++;  // inc blocks


								// Query after each prepared inserts block
								if ($j >= $insert_block_size)
								{
									// Setup the placeholders and data values to insert query
									$insert_sql = 'insert into detections (' . $datafields_str . ') values ' . implode(',', array_fill(0, $j, $question_marks));

									$db->beginTransaction();  // speed up inserts within transaction

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

									$db->commit();

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

							$db->beginTransaction();  // speed up inserts within transaction

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

							$db->commit();
						}
					}
				}

				// Set active state
				$setup->set('flag', null);
				$setup->save();

				return array('result' => true);
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

