<?php
/**
 */

class ExperimentController extends Controller
{

	function __construct($action = 'create')
	{
		parent::__construct($action);

		/* используем id из строки experiment/edit/%id */
		$this->id = App::router(2);
		$this->config = App::config();
	}

	function index()
	{
		System::go('experiment/create');
	}

	/**
	 * Action: Create
	 * Create experiment
	 */
	function create()
	{
		self::setTitle('Создать эксперимент');
		self::setContentTitle('Создать эксперимент');

		$this->view->form = new Form('create-experiment-form');
		$this->view->form->submit->value = 'Создать эксперимент';

		/* Установки как список опций для формы*/
		$this->view->form->setups = SetupController::loadSetups();


		if(isset($_POST) && isset($_POST['form-id']) && $_POST['form-id'] == 'create-experiment-form')
		{
			/* fill the Experiment properties */
			$experiment = new Experiment($this->session()->getKey());
			$experiment->set('title', htmlspecialchars(isset($_POST['experiment_title']) ? $_POST['experiment_title'] : ''));
			$setup_id = (isset($_POST['setup_id']) ? (int)$_POST['setup_id'] : '');
			$experiment->set('setup_id', $setup_id);
			$experiment->set('comments', htmlspecialchars(isset($_POST['experiment_comments']) ? $_POST['experiment_comments'] : ''));

			//$experiment->set('DateStart_exp', (new DateTime($_POST['experiment_date_start']))->format(DateTime::ISO8601));
			//$experiment->set('DateEnd_exp', (new DateTime($_POST['experiment_date_end']))->format(DateTime::ISO8601));


			if(empty($experiment->title))
			{
				// Error: Not save
				return;
			}

			// Check setup available
			if($setup_id)
			{
				$found = false;
				foreach ($this->view->form->setups as $s)
				{
					if ($s->id == $setup_id)
					{
						$found = true;
						break;
					}
				}
				if (!$found)
				{
					// Reset setup, not found
					$setup_id = '';
					$experiment->set('setup_id', $setup_id);
				}
			}

			/* Access Experiment in view*/
			$this->view->form->experiment = $experiment;

			if($experiment->save() && !is_null($experiment->id))
			{
				// Set master of setup if set setup with no master
				if($setup_id)
				{
					$setup = (new Setup())->load($setup_id);
					if ($setup && !is_null($setup->id) && empty($setup->master_exp_id))
					{
						$setup->set('master_exp_id', $experiment->id);
						$result = $setup->save();
						if (!$result)
						{
							// Error update setup master
							// Ignore
						}
					}
				}

				System::go('experiment/view/'.$experiment->id);
			}
		}
		else
		{
			$this->view->form->experiment = new Experiment();
		}
	}

	/**
	 * Action: View
	 * View single experiment or all
	 */
	function view()
	{
		if(!is_null($this->id) && is_numeric($this->id))
		{
			self::addJs('functions');
			self::addJs('class/Sensor');
			self::addJs('experiment/view');
			$experiment = (new Experiment())->load($this->id);
			if($experiment->session_key == $this->session()->getKey() || $this->session()->getUserLevel() == 3)
			{
				$this->view->content->experiment = $experiment;
				if($experiment->setup_id)
				{
					$this->view->content->setup = (new Setup())->load($experiment->setup_id);
					$this->view->content->sensors = SetupController::getSensors($experiment->setup_id, true);
					$monitors = (new Monitor())->loadItems(
							array(
									'exp_id' => (int)$experiment->id,
									'setup_id' => (int)$experiment->setup_id,
									'deleted' => 0
							),
							'id', 'DESC', 1
					);
					$this->view->content->monitor = (!empty($monitors)) ? $monitors[0] : null;

					// Get last monitoring info from api
					if (!empty($this->view->content->monitor))
					{
						// Prepare parameters for api method
						$query_params = array($this->view->content->monitor->uuid);

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

							$this->view->content->monitor->info = $result;
						}
						else
						{
							// TODO: error get monitor data from backend api, may by need show error
						}
					}
				}

				if($this->session()->getUserLevel() == 3)
				{
					$this->view->content->session = (new Session())->load($experiment->session_key);
				}
				else
				{
					$this->view->content->session = $this->session();
				}
				self::setTitle($experiment->title);
			}
			else
			{
				System::go('experiment/view');
			}
		}
		else
		{
			// All experiments
			$this->view->content->list = self::experimentsList();
			self::setViewTemplate('view.all');
			self::setTitle('Все экспериметы');

			self::addJs('functions');
			self::addJs('experiment/view.all');

			//View all available experiments in this session
		}
	}


	/** Action: Edit
	 * Edit experiment
	 */
	function edit()
	{
		if(!empty($this->id) && is_numeric($this->id))
		{
			$experiment = (new Experiment())->load($this->id);
			if($experiment->session_key == $this->session()->getKey() || $this->session()->getUserLevel() == 3)
			{
				self::setViewTemplate('create');
				self::setTitle('Редактировать '.$experiment->title);
				self::setContentTitle('Редактировать "'.$experiment->title.'"');

				/*Объект формы*/
				$this->view->form = new Form('edit-experiment-form');
				$this->view->form->submit->value = 'Сохранить';
				$this->view->form->experiment = $experiment;

				/* Установки как список опций для формы*/
				$this->view->form->setups = SetupController::loadSetups();

				if(isset($_POST) && isset($_POST['form-id']) && $_POST['form-id'] == 'edit-experiment-form')
				{
					$experiment->set('title', htmlspecialchars(isset($_POST['experiment_title']) ? $_POST['experiment_title'] : ''));
					$setup_id = (isset($_POST['setup_id']) ? (int)$_POST['setup_id'] : '');
					$experiment->set('setup_id', $setup_id);
					$experiment->set('comments', htmlspecialchars(isset($_POST['experiment_comments']) ? $_POST['experiment_comments'] : ''));

					if(empty($experiment->title))
					{
						// Error: Not save
						return;
					}

					// Check setup available
					if($setup_id)
					{
						$found = false;
						foreach ($this->view->form->setups as $s)
						{
							if ($s->id == $setup_id)
							{
								$found = true;
								break;
							}
						}
						if (!$found)
						{
							// Reset setup, not found

							// XXX: No reset old orphaned setups

							//$setup_id = '';
							//$experiment->set('setup_id', $setup_id);
						}
					}

					if($experiment->save() && !is_null($experiment->id))
					{
						// Set master of setup if set setup with no master
						if($setup_id && $found)
						{
							$setup = (new Setup())->load($setup_id);
							if ($setup && !is_null($setup->id) && empty($setup->master_exp_id))
							{
								$setup->set('master_exp_id', $experiment->id);
								$result = $setup->save();
								if (!$result)
								{
									// Error update setup master
									// Ignore
								}
							}
						}

						System::go('experiment/view/'.$experiment->id);
					}
				}
			}
			else
			{
				System::go('experiment/view');
			}

		}
		else
		{
			System::go('experiment/create');
		}
	}

	/**
	 * Action: Delete
	 * Deleting experiment.
	 * Deltetes all data related to experiment.
	 */
	function delete()
	{
		if (!empty($this->id) && is_numeric($this->id))
		{
			$experiment = (new Experiment())->load($this->id);

			// Only admin can delete experiment
			if ($experiment && $experiment->id /*&& $experiment->session_key == $this->session()->getKey()*/ && $this->session()->getUserLevel() == 3)
			{
				$db = new DB();

				// Check active experiment
				$query = $db->prepare('select id from setups where master_exp_id = :master_exp_id and flag > 0');
				$query->execute(array(
						':master_exp_id' => $this->id
				));
				$setups = (array) $query->fetchAll(PDO::FETCH_COLUMN, 0);
				$cnt_active = count($setups);

				// Force delete experiment if has active setups
				$force = (isset($_POST) && isset($_POST['force']) && is_numeric($_POST['force'])) ? (int) $_POST['force'] : 0;
				if ($cnt_active && !$force)
				{
					// Error: experiment with active setups
					System::go('experiment/view');
					return;
				}


				// Speed db operations within transaction
				$db->beginTransaction();

				try
				{
					// Unactivate setup and unset master experiment
					$sql_setups_update_query = 'update setups set flag = NULL, master_exp_id = NULL where master_exp_id = :master_exp_id';
					$update = $db->prepare($sql_setups_update_query);
					$result = $update->execute(array(':master_exp_id' => $this->id));
					if (!$result)
					{
						error_log('PDOError: '.var_export($update->errorInfo(),true));  //DEBUG
					}

					// Remove rows from tables
					// Be careful of delete order, because used foreign keys between tables (if enabled)

					// Need stop monitors before
					// Get all monitors of experiment
					$query = $db->prepare('select uuid from monitors where exp_id = :exp_id');
					$query->execute(array(
							':exp_id' => $this->id
					));
					$monitors = (array) $query->fetchAll(PDO::FETCH_COLUMN, 0);
					// Remove monitors call for backend sensors API (auto stop)
					$delmons = array();
					$errmons = array();
					foreach($monitors as $uuid)
					{
						// Send request for removing monitor
						$query_params = array((string) $uuid);
						$socket = new JSONSocket($this->config['socket']['path']);
						$result = $socket->call('Lab.RemoveMonitor', $query_params);
						if ($result)
						{
							$delmons[] = $uuid;
						}
						else
						{
							$errmons[] = $uuid;
						}
						unset($socket);
					}
					if (!empty($errmons))
					{
						error_log('Error remove monitors: {'. implode('},{', $errmons) . '}');  //DEBUG
					}

					// Remove monitors from DB
					$delete = $db->prepare('delete from monitors where exp_id=:exp_id');
					$result = $delete->execute(array(':exp_id' => $this->id));
					if (!$result)
					{
						error_log('PDOError: '.var_export($delete->errorInfo(),true));  //DEBUG
					}

					// Remove consumers
					// XXX: consumers table not used now
					$delete = $db->prepare('delete from consumers where exp_id=:exp_id');
					$result = $delete->execute(array(':exp_id' => $this->id));
					if (!$result)
					{
						error_log('PDOError: '.var_export($delete->errorInfo(),true));  //DEBUG
					}

					// Remove ordinate
					$delete = $db->prepare('delete from ordinate where id_plot IN (select id from plots where exp_id=:exp_id)');
					$result = $delete->execute(array(':exp_id' => $this->id));
					if (!$result)
					{
						error_log('PDOError: '.var_export($delete->errorInfo(),true));  //DEBUG
					}

					// Remove plots
					$delete = $db->prepare('delete from plots where exp_id=:exp_id');
					$result = $delete->execute(array(':exp_id' => $this->id));
					if (!$result)
					{
						error_log('PDOError: '.var_export($delete->errorInfo(),true));  //DEBUG
					}

					// Remove detections
					$delete = $db->prepare('delete from detections where exp_id=:exp_id');
					$result = $delete->execute(array(':exp_id' => $this->id));
					if (!$result)
					{
						error_log('PDOError: '.var_export($delete->errorInfo(),true));  //DEBUG
					}

					// Remove experiments
					$delete = $db->prepare('delete from experiments where id=:id');
					$result = $delete->execute(array(':id' => $this->id));
					if (!$result)
					{
						error_log('PDOError: '.var_export($delete->errorInfo(),true));  //DEBUG
					}
				}
				catch (PDOException $e)
				{
					error_log('PDOException Experiment::delete(): '.var_export($e->getMessage(),true));  //DEBUG
					var_dump($e->getMessage());
				}

				$db->commit();

				// TODO: Show info about errors while delete or about success (need session saved msgs)

				System::go('experiment/view');
			}
			else
			{
				// Error: experiment not found or no access
				System::go('experiment/view');
			}
		}
		else
		{
			// Error: incorrect experiment id
			System::go('experiment/view');
		}
	}

	/**
	 * Action: Journal
	 * View experiment journal.
	 */
	function journal()
	{
		if(!empty($this->id) && is_numeric($this->id))
		{
			$experiment = (new Experiment())->load($this->id);
			if($experiment->session_key == $this->session()->getKey() || $this->session()->getUserLevel() == 3)
			{

				self::setTitle('Журнал '.$experiment->title);
				self::setContentTitle('Журнал "'.$experiment->title.'"');

				/*Объект формы*/
				$this->view->form = new Form('experiment-journal-form');
				$this->view->form->submit->value = 'Обновить';
				$this->view->form->experiment = $experiment;


				if(isset($_POST) && isset($_POST['form-id']) && $_POST['form-id'] == 'experiment-journal-form')
				{
					if(isset($_POST['show-sensor']) && !empty($_POST['show-sensor']) && is_array($_POST['show-sensor']))
					{
						foreach($_POST['show-sensor'] as $sensor_show_id)
						{
							$sensors_show[$sensor_show_id] = $sensor_show_id;
						}
					}
				}

				/* Возможно стоит вынести все в отдельный контроллер или модель*/
				$db = new DB();

				$query = 'select id, exp_id, strftime(\'%Y.%m.%d %H:%M:%f\', time) as time, sensor_id, detection, error from detections where exp_id = '.(int)$experiment->id . ' order by strftime(\'%s\', time)';
				$detections = $db->query($query, PDO::FETCH_OBJ);

				/* Формирование вывода на основе датчиков в установке. */
				$sensors = SetupController::getSensors($experiment->setup_id, true);
				$available_sensors = $displayed_sensors = array();

				/*Формируем список доступных датчиков*/
				foreach($sensors as $sensor)
				{
					if(!array_key_exists($sensor->id, $available_sensors))
					{
						$available_sensors[$sensor->id] = $sensor;
					}
				}
				$this->view->content->available_sensors = $available_sensors;

				/*Если из формы пришел список то формируем список отображаемых датчиков*/
				if(!empty($sensors_show))
				{
					$this->view->content->displayed_sensors = array_intersect_key($available_sensors, $sensors_show);
				}
				else
				{
					$this->view->content->displayed_sensors = $available_sensors;
				}

				/* сам массив значений сгруппированых по временной метке. */
				$journal = array();
				foreach($detections as $row)
				{
					/*если есть в списке доступных датчиков до добавим в вывод журнала*/
					if(array_key_exists($row->sensor_id, $this->view->content->displayed_sensors))
					{
						$journal[$row->time][$row->sensor_id] = $row;
					}
				}
				$this->view->content->detections = &$journal;

			}
			else
			{
				System::go('experiment/view');
			}

		}
		else
		{
			System::go('experiment/create');
		}
	}

	function graph()
	{
		if (empty($this->id))
		{
			System::go('experiment/view');
		}

		$this->view->content->experiment = $experiment = (new Experiment())->load($this->id);

		if (is_numeric(App::router(3)))
		{
			// View/Edit graph

			self::setViewTemplate('graphsingle');
			self::setTitle('График для '.$experiment->title);
			self::addJs('lib/jquery.flot');
			self::addJs('lib/jquery.flot.time.min');
			self::addJs('lib/jquery.flot.navigate');
			self::addJs('functions');
			self::addJs('chart');


			$plot_id = (int)App::router(3);
			if (empty($plot_id))
			{
				System::go('experiment/graph');
				return;
			}

			// Get graph
			$plot = (new Plot())->load($plot_id);
			if (empty($plot))
			{
				// Error: graph not found
				System::go('experiment/graph');
				return;
			}

			$edit = App::router(4);
			if ($edit === 'edit')
			{
				// Edit graph

				$this->view->form = new Form('plot-edit-form');
				$this->view->form->submit->value = 'Сохранить график';

				if(isset($_POST['form-id']) && $_POST['form-id'] == 'plot-edit-form')
				{
					// Save graph form
				
					// ...
				}


			}
			else
			{
				// View graph

				// ...
			}

			$this->view->content->plot = $plot;
		}
		elseif (App::router(3) == 'add')
		{
			// Add new graph

			self::setViewTemplate('graphsingle');
			self::setContentTitle('Добавление графика для "'.$experiment->title.'"');
			self::setTitle('Добавление графика для '.$experiment->title);
		}
		else
		{
			// List graphs

			//self::setContentTitle('Графики для "'.$experiment->title.'"');
			self::setTitle('Графики для '.$experiment->title);
			self::addJs('lib/jquery.flot');
			self::addJs('lib/jquery.flot.time.min');
			self::addJs('lib/jquery.flot.navigate');
			self::addJs('functions');
			self::addJs('chart');

			$db = new DB();
			$query = 'select * from plots where exp_id = '.(int)$experiment->id;
			$plots = $db->query($query, PDO::FETCH_OBJ);

			$this->view->content->list = $plots;


			// Get available in detections sensors list

			// Get unique sensors list from detections data of experiment
			$query = 'select a.sensor_id, '
						. 's.value_name, s.si_notation, s.si_name, s.max_range, s.min_range, s.resolution '
					. 'from detections as a '
					. 'left join sensors as s on a.sensor_id = s.sensor_id '
					. 'where a.exp_id = :exp_id '
					. 'group by a.sensor_id order by a.sensor_id';
			$load = $db->prepare($query);
			$load->execute(array(
					':exp_id' => $experiment->id
			));
			$sensors = $load->fetchAll(PDO::FETCH_OBJ);
			if(empty($sensors))
			{
				$sensors = array();
			}

			$available_sensors = array();

			// Prepare available_sensors list
			foreach($sensors as $sensor)
			{
				if(!array_key_exists($sensor->sensor_id, $available_sensors))
				{
					$available_sensors[$sensor->sensor_id] = $sensor;
				}
			}

			$this->view->content->available_sensors = &$available_sensors;

			// Add graph of all sensors on ajax script
			$this->view->content->detections = array();
		}
	}

	/**
	 * Check if some Setup is active in experiment.
	 * Return boolean value and ids of active setups.
	 * 
	 * API method: Experiment.isActive
	 * API params: experiment
	 *
	 * @param  array $params  Array of parameters
	 *
	 * @return array  Result in form array('result' => bool, 'items' => array()) or False on error
	 */
	function isActive($params)
	{
		// Check id 
		if(!isset($params['experiment']) && empty($params['experiment']))
		{
			$this->error = 'Experiment not found';
			return false;
		}

		// Load experiment
		$experiment = (new Experiment())->load((int)$params['experiment']);
		if(!$experiment || !($experiment->id))
		{
			$this->error = 'Experiment not found';
			return false;
		}

		// Check access to experiment
		if(!($experiment->session_key == $this->session()->getKey() || $this->session()->getUserLevel() == 3))
		{
			$this->error = 'Access denied';
			return false;
		}

		$db = new DB();

		// Check active setups in experiment
		// TODO: use sql Count for query or return array of active
		$query = $db->prepare('select id from setups where master_exp_id = :master_exp_id and flag > 0');
		$res = $query->execute(array(
				':master_exp_id' => $experiment->id
		));
		if (!$res)
		{
			error_log('PDOError: '.var_export($query->errorInfo(),true));  //DEBUG

			$this->error = 'Error';
			return false;
		}

		$setups = (array) $query->fetchAll(PDO::FETCH_COLUMN, 0);

		return array(
				'result' => ((count($setups) > 0) ? true : false),
				'items'  => $setups
		);
	}

	/**
	 * Get list of experiments.
	 * Returns only own experiments or all for admin
	 * 
	 * @return array
	 */
	protected function experimentsList()
	{
		if($this->session()->getUserLevel() == 3)
		{
			$list = self::loadExperiments();
		}
		else
		{
			$list = self::loadExperiments($this->session()->getKey());
		}

		foreach($list as $key => $item)
		{
			$list[$key] = (new Experiment())->load($item->id);
		}
		return $list;
	}

	/**
	 * @param null $session_key
	 * @return array
	 */
	static function loadExperiments($session_key = null)
	{
		$db = new DB();

		if (is_numeric($session_key) && strlen($session_key) == 6)
		{
			$search = $db->prepare('select id from experiments where session_key = :session_key');
			$search->execute(array(
				':session_key' => $session_key
			));
		}
		else if($session_key == null)
		{
			$search = $db->prepare('select id from experiments');
			$search->execute();
		}

		return $search->fetchAll(PDO::FETCH_OBJ);
	}
}
