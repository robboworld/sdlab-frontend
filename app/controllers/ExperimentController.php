<?php
/**
 * Class ExperimentController
 */
class ExperimentController extends Controller
{

	public function __construct($action = 'create')
	{
		parent::__construct($action);

		// Get id from request query string experiment/edit/%id
		$this->id = App::router(2);
		$this->config = App::config();
	}

	public function index()
	{
		System::go('experiment/create');
	}

	/**
	 * Action: Create
	 * Create experiment
	 */
	public function create()
	{
		self::setTitle(L::experiment_TITLE_CREATION);
		self::setContentTitle(L::experiment_TITLE_CREATION);

		$this->view->form = new Form('create-experiment-form');
		$this->view->form->submit->value = L::experiment_CREATE_EXPERIMENT;

		// Get Setups list for the form
		$this->view->form->setups = SetupController::loadSetups();

		if(isset($_POST) && isset($_POST['form-id']) && $_POST['form-id'] === 'create-experiment-form')
		{
			// Save new experiment

			$experiment = new Experiment($this->session()->getKey());

			// Check access to create
			if(!$experiment->userCanCreate($this->session()))
			{
				System::go('experiment/view');
			}

			// Fill the Experiment properties
			$experiment->set('title', isset($_POST['experiment_title']) ? $_POST['experiment_title'] : '');
			$setup_id = (isset($_POST['setup_id']) ? (int)$_POST['setup_id'] : '');
			$experiment->set('setup_id', $setup_id);
			$experiment->set('comments', isset($_POST['experiment_comments']) ? $_POST['experiment_comments'] : '');

			// Get dates
			// Get local date and use UNIX timestamp (UTC)
			//$experiment->set('DateStart_exp', (new DateTime($_POST['experiment_date_start']))->format('U'));
			//$experiment->set('DateEnd_exp', (new DateTime($_POST['experiment_date_end']))->format('U'));

			// Check Setup available
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
					// Reset Setup, not found
					$setup_id = '';
					$experiment->set('setup_id', $setup_id);
				}
			}

			// Validate
			$valid = (strlen($experiment->title)>0);
			if($valid)
			{
				if($experiment->save() && !is_null($experiment->id))
				{
					// Set master of Setup if set Setup with no master
					if($setup_id)
					{
						$setup = (new Setup())->load($setup_id);
						if ($setup && empty($setup->master_exp_id))
						{
							$setup->set('master_exp_id', $experiment->id);
							$result = $setup->save();
							if (!$result)
							{
								// Error update Setup master
								// Ignore
							}
						}
					}

					System::go('experiment/view/'.$experiment->id);
				}
			}

			// Access Experiment in view
			$this->view->form->experiment = $experiment;
		}
		else
		{
			// Edit new experiment

			$this->view->form->experiment = new Experiment();

			// Check access to create (now can view creation page)
			/*
			if(!$this->view->form->experiment->userCanCreate($this->session()))
			{
				System::go('experiment/view');
			}
			*/
		}
	}

	/**
	 * Action: View
	 * View single experiment or all
	 */
	public function view()
	{
		if(!is_null($this->id) && is_numeric($this->id))
		{
			// Single experiment page

			self::addJs('functions');
			self::addJs('class/Sensor');
			self::addJs('experiment/view');
			// Add language translates for scripts
			Language::script(array(
					'ERROR',
					'GRAPH', 'INFO',  // class/Sensor
					'RUNNING_', 'STROBE', 'ERROR_NOT_COMPLETED', 'experiment_ERROR_CONFIGURATION_ORPHANED'  // experiment/view
			));


			// Load experiment
			$experiment = (new Experiment())->load($this->id);
			if(!$experiment)
			{
				System::go('experiment/view');
			}

			// Check access to view
			if(!$experiment->userCanView($this->session()))
			{
				System::go('experiment/view');
			}

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
					if (!$socket->error())
					{
						$res = $socket->call('Lab.GetMonInfo', $query_params);

						// Get results
						if($res)
						{
							$result = $res['result'];

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
							// TODO: error get monitor data from backend api, may be need show error
						}
					}
					else
					{
						// TODO: error get monitor data from backend api, may be need show error
					}
				}
			}

			// Inject other session object for admin
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
			// All experiments

			self::setViewTemplate('view.all');
			self::setTitle(L::experiment_TITLE_ALL);

			self::addJs('functions');
			self::addJs('experiment/view.all');
			// Add language translates for scripts
			Language::script(array(
					'journal_QUESTION_REMOVE_EXPERIMENT_WITH_1', 'journal_QUESTION_REMOVE_EXPERIMENT_WITH_JS_N', 'ERROR'  // experiment/view.all
			));

			//View all available experiments in this session
			$this->view->content->list = $this->experimentsList();
		}
	}


	/** Action: Edit
	 * Edit experiment
	 */
	public function edit()
	{
		if(empty($this->id) || !is_numeric($this->id))
		{
			// Redirect to create new
			System::go('experiment/create');
		}

		// Load experiment
		$experiment = (new Experiment())->load($this->id);
		if(!$experiment)
		{
			System::go('experiment/view');
		}

		// Check access to edit
		if(!$experiment->userCanEdit($this->session()))
		{
			System::go('experiment/view');
		}

		self::setViewTemplate('create');
		self::setTitle(L::TITLE_EDIT_OF($experiment->title));
		self::setContentTitle(L::TITLE_EDIT_OF_2($experiment->title));

		// Form object
		$this->view->form = new Form('edit-experiment-form');
		$this->view->form->submit->value = L::SAVE;
		$this->view->form->experiment = $experiment;

		// Get Setups list for the form
		$this->view->form->setups = SetupController::loadSetups();

		// Get current Setup
		$this->view->form->cur_setup = null;
		$this->view->form->cur_setup_id = $experiment->setup_id;
		if ($experiment->setup_id)
		{
			$this->view->form->cur_setup = (new Setup())->load($experiment->setup_id);
		}

		if(isset($_POST) && isset($_POST['form-id']) && $_POST['form-id'] === 'edit-experiment-form')
		{
			// Save experiment

			// Fill the Experiment properties
			$experiment->set('title', isset($_POST['experiment_title']) ? $_POST['experiment_title'] : '');
			$experiment->set('comments', isset($_POST['experiment_comments']) ? $_POST['experiment_comments'] : '');
			$new_setup_id = (isset($_POST['setup_id']) ? (int)$_POST['setup_id'] : 0);
			$new_setup_id = ($new_setup_id<0) ? 0 : $new_setup_id;
			//$experiment->set('setup_id', $new_setup_id);

			// Check new Setup available
			$new_setup = null;
			if($new_setup_id)
			{
				foreach ($this->view->form->setups as $k => $s)
				{
					if ($s->id == $new_setup_id)
					{
						$new_setup = $this->view->form->setups[$k];
						break;
					}
				}

				if (!$new_setup)
				{
					// Reset Setup, not found

					// XXX: No reset old orphaned Setups, leave as is

					//$setup_id = '';
					//$experiment->set('setup_id', $setup_id);
				}
			}

			$canChangeSetup = true;
			if ((int)$experiment->setup_id)
			{
				if ((int)$new_setup_id != (int)$experiment->setup_id)
				{
					// Check current Setup is active and new once is available

					if ($this->view->form->cur_setup)
					{
						if ($this->view->form->cur_setup->flag)
						{
							$canChangeSetup = false;
						}
						else
						{
							if ((int)$new_setup_id && !$new_setup)
							{
								$canChangeSetup = false;
							}
						}
					}
					else
					{
						if ((int)$new_setup_id && !$new_setup)
						{
							$canChangeSetup = false;
						}
					}
				}
			}
			if ($canChangeSetup)
			{
				$experiment->set('setup_id', $new_setup_id);
			}

			// Validate
			$valid = (strlen($experiment->title)>0);
			if($valid)
			{
				if($experiment->save() && !is_null($experiment->id))
				{
					// Set master of Setup if new Setup hasn't master experiment
					if($canChangeSetup && $new_setup)
					{
						$setup = (new Setup())->load($new_setup->id);
						if ($setup && empty($setup->master_exp_id))
						{
							$setup->set('master_exp_id', $experiment->id);
							$result = $setup->save();
							if (!$result)
							{
								// Error update Setup master
								// Ignore
							}
						}
					}

					System::go('experiment/view/'.$experiment->id);
				}
			}
		}
	}

	/**
	 * Action: Delete
	 * Deleting experiment.
	 * Deletes all data related to experiment.
	 */
	public function delete()
	{
		if (empty($this->id) || !is_numeric($this->id))
		{
			// Error: incorrect experiment id
			System::go('experiment/view');
		}

		// Load experiment
		$experiment = (new Experiment())->load($this->id);
		if(!$experiment)
		{
			System::go('experiment/view');
		}

		// Check access to delete
		if(!$experiment->userCanDelete($this->session()))
		{
			System::go('experiment/view');
		}


		$db = new DB();

		// Check active experiment
		$query = $db->prepare('select id from setups where master_exp_id = :master_exp_id and flag > 0');
		$query->execute(array(
				':master_exp_id' => $this->id
		));
		$setups = (array) $query->fetchAll(PDO::FETCH_COLUMN, 0);
		$cnt_active = count($setups);

		// Force delete experiment if has active Setups
		$force = (isset($_POST) && isset($_POST['force']) && is_numeric($_POST['force'])) ? (int) $_POST['force'] : 0;
		if ($cnt_active && !$force)
		{
			// Error: experiment with active Setups
			System::go('experiment/view');
		}

		// Speed db operations within transaction
		$db->beginTransaction();

		try
		{
			// Unactivate Setup and unset master experiment
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
				if (!$socket->error())
				{
					$result = $socket->call('Lab.RemoveMonitor', $query_params);
					if ($result && $result['result'])
					{
						$delmons[] = $uuid;
					}
					else
					{
						$errmons[] = $uuid;
					}
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
			//var_dump($e->getMessage());
		}

		$db->commit();

		// TODO: Show info about errors while delete or about success (need session saved msgs)

		System::go('experiment/view');
	}

	/**
	 * Action: Journal
	 * View experiment journal.
	 */
	public function journal()
	{
		if(empty($this->id) || !is_numeric($this->id))
		{
			// Redirect to create unknown experiment
			System::go('experiment/create');
		}

		// Load experiment
		$experiment = (new Experiment())->load($this->id);
		if(!$experiment)
		{
			System::go('experiment/view');
		}

		// Check access to experiment
		if(!$experiment->userCanView($this->session()))
		{
			System::go('experiment/view');
		}


		self::setTitle(L::journal_TITLE_JOURNAL_OF($experiment->title));
		self::setContentTitle(L::journal_TITLE_JOURNAL_OF_2($experiment->title));
		self::addJs('functions');
		self::addJs('experiment/journal');
		//self::addJs('lib/jquery.fileDownload');
		self::addJs('lib/jquery.fileDownload.min');

		// Add language translates for scripts
		Language::script(array(
				'journal_QUESTION_CLEAN_JOURNAL', 'ERROR', 'ERROR_DETECTIONS_EXPORT_DOWNLOAD'  // experiment/journal
		));

		// Form object
		$this->view->form = new Form('experiment-journal-form');
		$this->view->form->submit->value = L::REFRESH;
		$this->view->form->experiment = $experiment;


		// Check if filter request
		if(isset($_POST) && isset($_POST['form-id']) && $_POST['form-id'] === 'experiment-journal-form')
		{
			if(isset($_POST['show-sensor']) && !empty($_POST['show-sensor']) && is_array($_POST['show-sensor']))
			{
				foreach($_POST['show-sensor'] as $sensor_show_id)
				{
					$sensors_show[$sensor_show_id] = $sensor_show_id;
				}
			}
		}

		// TODO: may be move all journal operations to separate controller/model
		$db = new DB();

		$query = 'select id, exp_id, strftime(\'%Y-%m-%dT%H:%M:%fZ\', time) as time, sensor_id, sensor_val_id, detection, error from detections where exp_id = '.(int)$experiment->id . ' order by strftime(\'%s\', time),strftime(\'%f\', time)';
		$detections = $db->query($query, PDO::FETCH_OBJ);

		// Prepare output depends on sensors in Setup
		$sensors = SetupController::getSensors($experiment->setup_id, true);
		$available_sensors = $displayed_sensors = array();

		// Fill list of available sensors
		foreach($sensors as $sensor)
		{
			$key = '' . $sensor->id . '#' . (int)$sensor->sensor_val_id;
			if(!array_key_exists($key, $available_sensors))
			{
				$available_sensors[$key] = $sensor;
			}
		}
		$this->view->content->available_sensors = $available_sensors;

		// Fill list of displayed sensors from sensors filter
		if(!empty($sensors_show))
		{
			$this->view->content->displayed_sensors = array_intersect_key($available_sensors, $sensors_show);
		}
		else
		{
			$this->view->content->displayed_sensors = $available_sensors;
		}

		// Array of values grouped by full timestamps (UTC datetime!)
		$journal = array();
		foreach($detections as $row)
		{
			// if sensor+value is available thn add to journal output
			$key = '' . $row->sensor_id . '#' . (int)$row->sensor_val_id;
			if(array_key_exists($key, $this->view->content->displayed_sensors))
			{
				$journal[$row->time][$key] = $row;
			}
		}
		$this->view->content->detections = &$journal;
	}

	public function graph()
	{
		if (empty($this->id))
		{
			System::go('experiment/view');
		}

		// Load experiment
		$this->view->content->experiment = $experiment = (new Experiment())->load($this->id);
		if(!$this->view->content->experiment)
		{
			System::go('experiment/view');
		}

		// Check access to experiment
		if(!$this->view->content->experiment->userCanView($this->session()))
		{
			System::go('experiment/view');
		}

		$id = App::router(3);
		if (is_numeric($id))
		{
			// View/Edit graph

			self::setViewTemplate('graphsingle');
			self::setTitle(L::graph_TITLE_GRAPH_FOR($experiment->title));
			self::addJs('lib/jquery.flot');
			self::addJs('lib/jquery.flot.time.min');
			self::addJs('lib/jquery.flot.navigate');
			self::addJs('functions');
			self::addJs('chart');
			// Add language translates for scripts
			Language::script(array(
					'ERROR',
					'sensor_VALUE_NAME_TEMPERATURE'  // chart
			));

			$plot_id = (int)$id;
			if (empty($plot_id))
			{
				System::go('experiment/graph');
			}

			// Get graph
			$plot = (new Plot())->load($plot_id);
			if (empty($plot))
			{
				// Error: graph not found
				System::go('experiment/graph');
			}

			$edit = App::router(4);
			if ($edit === 'edit')
			{
				// Edit graph

				$this->view->form = new Form('plot-edit-form');
				$this->view->form->submit->value = L::graph_SAVE;

				if(isset($_POST['form-id']) && $_POST['form-id'] === 'plot-edit-form')
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
		elseif ($id === 'add')
		{
			// Add new graph

			self::setViewTemplate('graphsingle');
			self::setTitle(L::graph_TITLE_ADD_GRAPH_FOR($experiment->title));
			self::setContentTitle(L::graph_TITLE_ADD_GRAPH_FOR_2($experiment->title));
		}
		else
		{
			// List graphs

			self::setTitle(L::graph_TITLE_GRAPHS_FOR($experiment->title));
			//self::setContentTitle(L::graph_TITLE_GRAPHS_FOR_2($experiment->title));
			self::addJs('lib/jquery.flot');
			self::addJs('lib/jquery.flot.time.min');
			self::addJs('lib/jquery.flot.navigate');
			self::addJs('functions');
			self::addJs('chart');
			// Add language translates for scripts
			Language::script(array(
					'ERROR',
					'sensor_VALUE_NAME_TEMPERATURE'  // chart
			));

			$db = new DB();
			$query = 'select * from plots where exp_id = '.(int)$experiment->id;
			$plots = $db->query($query, PDO::FETCH_OBJ);

			$this->view->content->list = $plots;


			// Get available in detections sensors list

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

			$available_sensors = array();

			// Prepare available_sensors list
			foreach($sensors as $sensor)
			{
				$key = '' . $sensor->sensor_id . '#' . (int)$sensor->sensor_val_id;
				if(!array_key_exists($key, $available_sensors))
				{
					$available_sensors[$key] = $sensor;
				}
			}

			$this->view->content->available_sensors = &$available_sensors;

			// Plot with all sensors data loads on ajax request
			$this->view->content->detections = array();
		}
	}


	/**
	 * Action: Clean
	 * Clean detections journal.
	 * Deletes all detections data related to experiment.
	 */
	public function clean()
	{
		if (empty($this->id) || !is_numeric($this->id))
		{
			// Error: incorrect experiment id
			System::go('experiment/view');
		}

		// Load experiment
		$experiment = (new Experiment())->load($this->id);
		if(!$experiment)
		{
			System::go('experiment/view');
		}

		// Check access to experiment
		if(!$experiment->userCanView($this->session()))
		{
			System::go('experiment/view');
		}

		if(isset($_POST) && isset($_POST['form-id']) && $_POST['form-id'] === 'experiment-journal-form')
		{
			// Make clean

			$db = new DB();

			// Remove detections
			$delete = $db->prepare('delete from detections where exp_id=:exp_id');
			$result = $delete->execute(array(':exp_id' => $this->id));
			if (!$result)
			{
				error_log('PDOError: '.var_export($delete->errorInfo(),true));  //DEBUG
			}

			$destination = System::getVarBackurl();
			if($destination !== null && $destination != $_GET['q'])
			{
				System::go(System::cleanVar($destination, 'path'));
			}
			else
			{
				System::go('experiment/journal/'.$this->id);
			}
		}
		else 
		{
			// No view page fo clean
			System::go('experiment/journal/'.$this->id);
		}

		// TODO: Show info about errors while clean or message about success (need session stored messages)

		return;
	}

	/**
	 * Action: Download
	 * Export detections journal data.
	 * Exports detections data related to experiment in requested format:
	 *   - CSV
	 */
	public function download()
	{
		// TODO: Fix access check, because this controller cannot execute actions if not logged on, App controller already starts other controller with html output. Need raw format variant controller with public access.

		if (empty($this->id) || !is_numeric($this->id))
		{
			// Error: incorrect experiment id
			System::goerror(404);
		}

		// Load experiment
		$experiment = (new Experiment())->load($this->id);
		if(!$experiment)
		{
			System::goerror(404);
		}

		// Check access to experiment
		if(!$experiment->userCanView($this->session()))
		{
			System::goerror(403);
		}

		$method = strtoupper($_SERVER['REQUEST_METHOD']);

		// Check access to experiment
		if($method == 'POST')
		{
			if (isset($_POST))
			{
				$request = &$_POST;
			}
		}
		else if ($method == 'GET')
		{
			if (isset($_GET))
			{
				$request = &$_GET;
			}
		}
		else
		{
			System::goerror(500);
		}

		if(isset($request) && isset($request['form-id']) && $request['form-id'] === 'experiment-journal-form')
		{
			// Prepare data for download

			$db = new DB();

			// Get detections
			//TODO: add filter support with 'dtfrom' and 'dtto' parameters for date range
			$query = 'select id, strftime(\'%Y-%m-%dT%H:%M:%fZ\', time) as time, sensor_id, sensor_val_id, detection, error'
					. ' from detections'
					. ' where exp_id = ' . (int)$experiment->id
					. ' order by strftime(\'%s\', time),strftime(\'%f\', time)';
			$detections = $db->query($query, PDO::FETCH_OBJ);

			// Prepare output depends on sensors in Setup
			$sensors = SetupController::getSensors($experiment->setup_id, true);
			$available_sensors = $displayed_sensors = array();

			// Fill list of available sensors
			foreach($sensors as $sensor)
			{
				$key = '' . $sensor->id . '#' . (int)$sensor->sensor_val_id;
				if(!array_key_exists($key, $available_sensors))
				{
					$available_sensors[$key] = $sensor;
				}
			}

			// Fill list of displayed sensors from sensors filter
			$sensors_show = array();
			if(isset($request['show-sensor']) && !empty($request['show-sensor']) && is_array($request['show-sensor']))
			{
				foreach($request['show-sensor'] as $sensor_show_id)
				{
					$sensors_show[$sensor_show_id] = $sensor_show_id;
				}
			}
			if(!empty($sensors_show))
			{
				$displayed_sensors = array_intersect_key($available_sensors, $sensors_show);
			}
			else
			{
				$displayed_sensors = $available_sensors;
			}

			// Array of values grouped by full timestamps (UTC datetime!)
			$journal = array();
			foreach($detections as $row)
			{
				// if sensor+value is available than add to journal output
				$key = '' . $row->sensor_id . '#' . (int)$row->sensor_val_id;
				if(array_key_exists($key, $displayed_sensors))
				{
					$journal[$row->time][$key] = $row;
				}
			}

			// Prepare data as array of array (rows and columns)
			$result = array();

			// Header
			$data_header = array();
			$data_header[] = 'N';
			$data_header[] = L::TIME;
			foreach ($displayed_sensors as $skey => $sensor)
			{
				$data_header[] = $sensor->name
					. ' ' .  constant('L::sensor_VALUE_NAME_' . strtoupper($sensor->value_name))
						. ', ' . constant('L::sensor_VALUE_SI_NOTATION_' . strtoupper($sensor->value_name) . '_' . strtoupper($sensor->si_notation))
					. ' ' . '(id: ' . $skey . ')';
			}
			$result[] = $data_header;

			// Locale number format settings
			// xxx: hack to convert decimal point of floats for some locales
			$decimal_point = '.';
			if (is_object($this->app->lang))
			{
				$activeLang = $this->app->lang->getAppliedLang();
				if (strtolower(substr($activeLang,0,2)) === 'ru')
				{
					$decimal_point_new = ',';
				}
			}
			//if (false !== setlocale(LC_NUMERIC, 'ru_RU.UTF-8')) {
			//	$locale_info = localeconv();
			//	$decimal_point = $locale_info['decimal_point'];
			//}

			// Data rows
			$i = 0;
			foreach($journal as $time => $row)
			{
				$i++;
				$data_row = array();
				$data_row[] = (int)$i;
				$data_row[] = System::datemsecformat($time, System::DATETIME_FORMAT1NANOXSL, 'now');
				foreach ($displayed_sensors as $skey => $sensor)
				{
					if (isset($row[$skey]) && ($row[$skey]->error !== 'NaN'))
					{
						if (isset($decimal_point_new))
						{
							$data_row[] = str_replace($decimal_point, $decimal_point_new, (float)$row[$skey]->detection);
						}
						else
						{
							$data_row[] = (float)$row[$skey]->detection;
						}
					}
					else {
						$data_row[] = '';
					}
				}
				$result[] = $data_row;
			}

			// Get requested document type
			$doc_type = (isset($request['type']) && in_array($request['type'], array('csv'))) ? $request['type'] : 'csv';
			$filename = 'detections-exp' . (int)$experiment->id . '-' . System::datemsecformat(null, 'YmdHisu' /*U*/, 'now') . '.' . $doc_type;

			switch ($doc_type)
			{
				case 'csv':
				default:
					{
						/*
						Examples of CSV:

						field_name;field_name;field_name CRLF
						aaa;bbb;ccc CRLF
						zzz;yyy;xxx CRLF

						a;b;c
						1;2;3
						4;";";5
						77;""";""";88
						6;,;7
						8;.;9
						*/

						$output = array();
						foreach ($result as $r)
						{
							$output[] = System::strtocsv($r, ';');
						}

						$output = implode("\n", $output);
						// XXX: convert from utf-8 to ansi for ms office
						$output = iconv("UTF-8", "Windows-1251", $output);

						// Send response headers to the browser
						header('Content-Type: text/csv');
						header("Content-Length: " . strlen($output));
						header("Content-Disposition: attachment; filename=\"" . $filename . "\";");
						//header("Content-Transfer-Encoding: Binary");  // only for MIME in email protocols
						//header('Connection: Keep-Alive');
						//header('Expires: 0');
						header('Cache-Control: no-cache');
						//header('Cache-Control: max-age=60, must-revalidate');
						//header('Pragma: public');
						header('Set-Cookie: fileDownload=true; path=/');

						// Write data to output
						$fp = fopen('php://output', 'w');
						//foreach ($result as $r){
						//	fputcsv($fp, $r);
						//}
						fwrite($fp, $output);
						fclose($fp);
					}
					break;

				// TODO: add other export formats (xlsx, pdf, ...)

			}

			exit();
		}
		else
		{
			// Incorrect request
			System::goerror(500);
		}

		return;
	}

	/**
	 * Check if some Setup is active in experiment.
	 * Return boolean value and ids of active Setups.
	 * 
	 * API method: Experiment.isActive
	 * API params: experiment
	 *
	 * @param  array $params  Array of parameters
	 *
	 * @return array  Result in form array('result' => bool, 'items' => array()) or False on error
	 */
	public function isActive($params)
	{
		// Check id 
		if(!isset($params['experiment']) && empty($params['experiment']))
		{
			$this->error = L::ERROR_EXPERIMENT_NOT_FOUND;
			return false;
		}

		// Load experiment
		$experiment = (new Experiment())->load((int)$params['experiment']);
		if(!$experiment)
		{
			$this->error = L::ERROR_EXPERIMENT_NOT_FOUND;
			return false;
		}

		// Check access to experiment
		if(!$experiment->userCanView($this->session()))
		{
			$this->error = L::ACCESS_DENIED;
			return false;
		}

		$db = new DB();

		// Check active Setups in experiment
		// TODO: use sql Count for query or return array of active
		$query = $db->prepare('select id from setups where master_exp_id = :master_exp_id and flag > 0');
		$res = $query->execute(array(
				':master_exp_id' => $experiment->id
		));
		if (!$res)
		{
			error_log('PDOError: '.var_export($query->errorInfo(),true));  //DEBUG

			$this->error = L::ERROR;
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
	 * Load experiments by session or all
	 * 
	 * @param  string  $session_key  $session_key
	 * @return array  Array of objects with experiment ids or empty
	 */
	public static function loadExperiments($session_key = null)
	{
		$db = new DB();

		if (is_numeric($session_key) && strlen($session_key) == 6)
		{
			$search = $db->prepare('select id from experiments where session_key = :session_key');
			$search->execute(array(
				':session_key' => $session_key
			));
		}
		else if ($session_key == null)
		{
			$search = $db->prepare('select id from experiments');
			$search->execute();
		}
		else
		{
			return array();
		}

		return $search->fetchAll(PDO::FETCH_OBJ);
	}
}
