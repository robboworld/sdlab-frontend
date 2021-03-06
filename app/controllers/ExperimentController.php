<?php
/**
 * Class ExperimentController
 */
class ExperimentController extends Controller
{
	public function __construct($action = 'create', $config = array('default_action' => 'index'))
	{
		parent::__construct($action, $config);

		// Register the methods as actions.
		$this->registerAction('create', 'create');
		$this->registerAction('view', 'view');
		$this->registerAction('edit', 'edit');
		$this->registerAction('delete', 'delete');
		$this->registerAction('journal', 'journal');
		$this->registerAction('graph', 'graph');
		$this->registerAction('scatter', 'scatter');
		$this->registerAction('clean', 'clean');
		$this->registerAction('download', 'download');

		// Register the methods as API methods.
		$this->registerMAPI('isActive', 'isActive');
		$this->registerMAPI('getGraphData', 'getGraphData');
		$this->registerMAPI('getScatterData', 'getScatterData');
		$this->registerMAPI('delete', 'delete');
		$this->registerMAPI('deletebytime', 'deletebytime');

		// Get id from request query string experiment/{action}/%id
		$this->id = App::router(2);
		// Get Application config
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
		$this->setTitle(L('experiment_TITLE_CREATION'));
		$this->setContentTitle(L('experiment_TITLE_CREATION'));

		$this->view->form = new Form('create-experiment-form');
		$this->view->form->submit->value = L('experiment_CREATE_EXPERIMENT');

		// Get Setups list for the form
		// For admin load all setups, else not singletone Setups
		$access_modes = null;
		if($this->session()->getUserLevel() != 3)
		{
			$access_modes = array(Setup::$ACCESS_SHARED => 0, Setup::$ACCESS_PRIVATE => $this->session()->getKey());
		}
		$this->view->form->setups = SetupController::loadSetups($access_modes);

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
			//try
			//	$experiment->set('DateStart_exp', (new DateTime($_POST['experiment_date_start']))->format('U'));
			//} catch (Exception $e) {}
			//try
			//$experiment->set('DateEnd_exp', (new DateTime($_POST['experiment_date_end']))->format('U'));
			//} catch (Exception $e) {}

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

			// Pass Experiment to view
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

			$this->addJs('functions');
			$this->addJs('experiment/view');
			// Add language translates for scripts
			Language::script(array(
					'ERROR',
					'ERRORS',
					'RUNNING_', 'STROBE', 'ERROR_NOT_COMPLETED', 'experiment_ERROR_CONFIGURATION_ORPHANED_REFRESH', 'experiment_ERROR_STATUS_REFRESH'  // experiment/view
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

			// Get current setup template
			if($experiment->setup_id)
			{
				$this->view->content->setup = (new Setup())->load($experiment->setup_id);
				$this->view->content->setup_monitors = array();
				$this->view->content->sensors = SetupController::getSensors($experiment->setup_id, true);
			}

			// Get monitors
			$monitors = (new Monitor())->loadItems(
					array(
							'exp_id' => (int)$experiment->id,
					),
					'created',
					'DESC'
			);
			if ($monitors === false)
			{
				$monitors = array();
			}
			$this->view->content->monitors = &$monitors;

			// Get monitoring info from api
			$nd = System::nulldate();
			foreach ($this->view->content->monitors as $i => $mon)
			{
				// TODO: get monitors info from database (Last, Counters.Done&Err, Archives.Step&Len, Values.Name&Sensor&ValueIdx&Len), not slow socket


				// Collect monitors for current setup
				if(isset($this->view->content->setup_monitors))
				{
					if($mon->setup_id == $experiment->setup_id)
					{
						$this->view->content->setup_monitors[$i] = $this->view->content->monitors[$i];
					}
				}

				// Prepare parameters for api method
				$request_params = array($mon->uuid);

				// Send request for get monitor info
				$socket = new JSONSocket($this->config['socket']['path']);
				if (!$socket->error())
				{
					$res = $socket->call('Lab.GetMonInfo', $request_params);

					// Get results
					if($res)
					{
						$result = $res['result'];

						//Prepare results
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

						$this->view->content->monitors[$i]->info = $result;
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
				unset($socket);

				// Inject setup configuration with sensors data
				$this->view->content->monitors[$i]->setup = new Setup();
				if (!$this->view->content->monitors[$i]->setup->load((int)$mon->setup_id))
				{
					$this->view->content->monitors[$i]->setup->set('id', null);
					$this->view->content->monitors[$i]->setup->set('master_exp_id', null);
					$this->view->content->monitors[$i]->setup->set('session_key', null);
					$this->view->content->monitors[$i]->setup->set('access', Setup::$ACCESS_SHARED);
					$this->view->content->monitors[$i]->setup->set('title', L('UNKNOWN'));
				}
				// Inject configuration from monitor
				if (isset($this->view->content->monitors[$i]->info))
				{
					$this->view->content->monitors[$i]->setup->set('interval', $this->view->content->monitors[$i]->info->Archives[0]->Step);
					$this->view->content->monitors[$i]->setup->set('amount', $this->view->content->monitors[$i]->info->Amount);
					$this->view->content->monitors[$i]->setup->set('time_det', $this->view->content->monitors[$i]->info->Duration);

					//$this->view->content->monitors[$i]->setup->set('period', $this->view->content->monitors[$i]->info->period);
					//$this->view->content->monitors[$i]->setup->set('number_error', $this->view->content->monitors[$i]->info->number_error);
					//$this->view->content->monitors[$i]->setup->set('period_repeated_det', $this->view->content->monitors[$i]->info->period_repeated_det);

					$this->view->content->monitors[$i]->setup->sensors = $this->view->content->monitors[$i]->info->Values;  // [Name,Sensor,ValueIdx,Len]
				}
			}

			// Move current setup monitor up
			if(isset($this->view->content->setup_monitors))
			{
				// temporary remove setup monitors
				foreach ($this->view->content->setup_monitors as $i => $smon)
				{
					unset($this->view->content->monitors[$i]);
				}
				// insert setup monitors back, but prepend other with the same ordering
				$rkeys = array_reverse(array_keys($this->view->content->setup_monitors));
				foreach ($rkeys as $key)
				{
					array_unshift($this->view->content->monitors, $this->view->content->setup_monitors[$key]);
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

			$this->setTitle($experiment->title);
		}
		else
		{
			// All experiments

			$this->setViewTemplate('view.all');
			$this->setTitle(L('experiment_TITLE_ALL'));

			$this->addJs('functions');
			$this->addJs('experiment/view.all');
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

		$this->setViewTemplate('create');
		$this->setTitle(L('TITLE_EDIT_OF',array($experiment->title)));
		$this->setContentTitle(L('TITLE_EDIT_OF_2',array($experiment->title)));

		// Form object
		$this->view->form = new Form('edit-experiment-form');
		$this->view->form->submit->value = L('SAVE');
		$this->view->form->experiment = $experiment;

		// Get Setups list for the form
		// For admin load all setups, else not singletone Setups
		$access_modes = null;
		if($this->session()->getUserLevel() != 3)
		{
			$access_modes = array(Setup::$ACCESS_SHARED => 0, Setup::$ACCESS_PRIVATE => $this->session()->getKey(), Setup::$ACCESS_SINGLE => $experiment->id);
		}
		$this->view->form->setups = SetupController::loadSetups($access_modes);

		// Get current Setup
		$this->view->form->cur_setup = null;
		$this->view->form->cur_setup_id = $experiment->setup_id;
		if ($experiment->setup_id)
		{
			$this->view->form->cur_setup = (new Setup())->load($experiment->setup_id);
			if ($this->view->form->cur_setup)
			{
				$this->view->form->cur_setup->active = Setup::isActive($this->view->form->cur_setup->id, $experiment->id);
			}
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
						// Requested new setup but not found
						if ((int)$new_setup_id && !$new_setup)
						{
							$canChangeSetup = false;
						}
					}
					else
					{
						// Requested new setup but not found
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
			// stay on edit form on errors
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

		// Check active experiment
		// (Experiment with active monitorings)
		$isActive = Experiment::isActive($experiment->id);

		// Force delete experiment if has active Setups
		$force = (isset($_POST) && isset($_POST['force']) && is_numeric($_POST['force'])) ? (int) $_POST['force'] : 0;
		if ($isActive && !$force)
		{
			// Error: experiment with active Setups monitoring
			System::go('experiment/view');
		}

		$db = new DB();

		// Step 1. Remove experiment data of frontend

		// Speed up db operations within transaction
		$db->beginTransaction();
		try
		{
			// Unset Setup master experiment
			$query = $db->prepare('update setups set master_exp_id = NULL where master_exp_id = :master_exp_id');
			$result = $query->execute(array(':master_exp_id' => $experiment->id));
			if (!$result)
			{
				error_log('PDOError: '.var_export($query->errorInfo(),true));  //DEBUG
			}

			// Remove rows from tables
			// Be careful of delete order, because used foreign keys between tables (if enabled)

			// Remove monitors data from DB
			// XXX: Must be removed by backend API on Lab.RemoveMonitor later

			// Remove consumers
			// XXX: consumers table not used now
			$query = $db->prepare('delete from consumers where exp_id=:exp_id');
			$result = $query->execute(array(':exp_id' => $experiment->id));
			if (!$result)
			{
				error_log('PDOError: '.var_export($query->errorInfo(),true));  //DEBUG
			}

			// Remove ordinate
			$query = $db->prepare('delete from ordinate where id_plot IN (select id from plots where exp_id=:exp_id)');
			$result = $query->execute(array(':exp_id' => $experiment->id));
			if (!$result)
			{
				error_log('PDOError: '.var_export($query->errorInfo(),true));  //DEBUG
			}

			// Remove plots
			$query = $db->prepare('delete from plots where exp_id=:exp_id');
			$result = $query->execute(array(':exp_id' => $experiment->id));
			if (!$result)
			{
				error_log('PDOError: '.var_export($query->errorInfo(),true));  //DEBUG
			}

			// Remove detections
			// (will be deleted also strobe and unknown mons data)
			$query = $db->prepare('delete from detections where exp_id=:exp_id');
			$result = $query->execute(array(':exp_id' => $experiment->id));
			if (!$result)
			{
				error_log('PDOError: '.var_export($query->errorInfo(),true));  //DEBUG
			}

			// Remove experiment
			$query = $db->prepare('delete from experiments where id=:id');
			$result = $query->execute(array(':id' => $experiment->id));
			if (!$result)
			{
				error_log('PDOError: '.var_export($query->errorInfo(),true));  //DEBUG
			}
		}
		catch (PDOException $e)
		{
			error_log('PDOException Experiment::delete(): '.var_export($e->getMessage(),true));  //DEBUG
			//var_dump($e->getMessage());
		}

		$db->commit();

		// Step 2. Remove monitors from backend

		// Get list monitors of experiment
		$query = $db->prepare('select uuid from monitors where exp_id = :exp_id');
		$query->execute(array(':exp_id' => $experiment->id));
		$monitors = $query->fetchAll(PDO::FETCH_COLUMN, 0);
		if ($monitors !== false)
		{
			// Remove monitors call for backend sensors API (auto stop)
			$delmons = array();
			$errmons = array();
			foreach($monitors as $uuid)
			{
				// Send request for removing monitor
				$request_params = (object) array(
						'UUID'     => (string) $uuid,
						'WithData' => true
				);
				$socket = new JSONSocket($this->config['socket']['path']);
				if (!$socket->error())
				{
					$result = $socket->call('Lab.RemoveMonitor', $request_params);
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
		}
		else
		{
			error_log('Error remove monitors for experiment: '. (int)$experiment->id);  //DEBUG
		}

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


		$this->setTitle(L('journal_TITLE_JOURNAL_OF',array($experiment->title)));
		$this->setContentTitle(L('journal_TITLE_JOURNAL_OF_2',array($experiment->title)));
		$this->addJs('functions');
		$this->addJs('experiment/journal');
		//$this->addJs('lib/jquery.fileDownload');
		$this->addJs('lib/jquery.fileDownload.min');

		// Add language translates for scripts
		Language::script(array(
				'journal_QUESTION_CLEAN_JOURNAL', 'ERROR', 'ERROR_DETECTIONS_EXPORT_DOWNLOAD'  // experiment/journal
		));

		// Form object
		$this->view->form = new Form('experiment-journal-form');
		$this->view->form->submit->value = L('REFRESH');
		$this->view->form->experiment = $experiment;

		// Request type
		/*
		$_req = array();
		switch(strtoupper($_SERVER['REQUEST_METHOD']))
		{
			case 'GET':  $_req = &$_GET; break;
			case 'POST': $_req = &$_POST; break;
			default:;
		}
		*/

		// Check if filter request (POST only?)
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

		// Check pagination filter (GET only?)
		$limit = 0; // 0, 10, 20, 50, 100 (0 - no limit?)
		$limitstart = null;
		if(isset($_GET) && isset($_GET['limit']) && in_array((int)$_GET['limit'], array(0,10,20,50,100)))
		{
			$limit = (int)$_GET['limit'];
		}
		if(isset($_GET['limitstart']) && (int)$_GET['limitstart'] >= 0)
		{
			$limitstart = (int)$_GET['limitstart'];
		}

		// TODO: may be move all journal operations to separate controller/model

		// Init arrays of sensors
		$det_sensors   = array();
		$setup_sensors = array();
		$mon_sensors   = array();
		$reg_sensors   = array();

		$db = new DB();

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
			error_log('PDOException Experiment::journal(): '.var_export($e->getMessage(),true));  //DEBUG
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

		$this->view->content->available_sensors = &$sensors;

		// Fill list of displayed sensors from sensors filter
		if(!empty($sensors_show))
		{
			$this->view->content->displayed_sensors = array_intersect_key($sensors, $sensors_show);
		}
		else
		{
			$this->view->content->displayed_sensors = $sensors;
		}

		// Detections selection

		$journal = array();

		// Speed up db operations within transaction
		$db->beginTransaction();
		try
		{
			//error_log('PDOError: '.var_export($query->errorInfo(),true));  //DEBUG

			// Total grouped detection rows
			$query = $db->prepare('select count(*) from (select time from detections where exp_id = :exp_id group by time)');
			$query->execute(array(':exp_id' => (int)$experiment->id));
			$total = 0;
			if (($row = $query->fetch(PDO::FETCH_COLUMN)) !== false)
			{
				$total = (int)$row[0];
			}

			/*
			$min_time = $max_time = null;
			$query = $db->prepare('select min(time), max(time) from detections where exp_id = :exp_id');
			$query->execute(array(':exp_id' => (int)$experiment->id));
			if (($row = $query->fetch(PDO::FETCH_NUM)) !== false)
			{
				$min_time = $row[0];
				$max_time = $row[1];
			}
			*/

			// Get time range if limit
			$starttime = null;
			$endtime = null;
			if ($limit > 0)
			{
				$query = $db->prepare('select min(time), max(time) from (select time from detections where exp_id = :exp_id group by time order by time limit :limit OFFSET :offset)');
				$query->execute(array(
						':exp_id' => (int)$experiment->id,
						':limit'  => (int)$limit,
						':offset' => ($limitstart >= 0) ? (int)$limitstart : 0
				));
				if (($row = $query->fetch(PDO::FETCH_NUM)) !== false)
				{
					$starttime = ($limitstart >= 0) ? $row[0] : null;
					$endtime   = $row[1];
				}
			}
			else
			{
				if ($limitstart >= 0)
				{
					$query = $db->prepare('select time from detections where exp_id = :exp_id group by time order by time limit :limit OFFSET :offset');
					$query->execute(array(
							':exp_id' => (int)$experiment->id,
							':limit'  => 1,
							':offset' => (int)$limitstart
					));
					if (($row = $query->fetch(PDO::FETCH_NUM)) !== false)
					{
						$starttime = $row[0];
					}
				}
			}

			// Get array of values grouped by full timestamps (UTC datetime!)
			// with pagination
			$where            = array('exp_id = :exp_id');
			$query_params = array(':exp_id' => (int)$experiment->id);
			if($starttime !== null)
			{
				$query_params[':starttime'] = $starttime;
				$where[] = 'time >= :starttime';
			}
			if($endtime !== null)
			{
				$query_params[':endtime'] = $endtime;
				$where[] = 'time <= :endtime';
			}
			// TODO: remove not used mstime field?
			$sql = 'select id, exp_id, mon_id, time, strftime(\'%Y-%m-%dT%H:%M:%fZ\', time) as mstime, sensor_id, sensor_val_id, detection, error '
					. 'from detections '
					. 'where ' . implode(' and ', $where) . ' '
					//. 'order by strftime(\'%s\', time),strftime(\'%f\', time) ';
					. 'order by strftime(\'%Y-%m-%dT%H:%M:%f\', time), id ';
			$query = $db->prepare($sql);
			$query->execute($query_params);
			while (($row = $query->fetch(PDO::FETCH_OBJ)) !== false)
			{
				// if sensor+value is available than add to journal output
				$key = '' . $row->sensor_id . '#' . (int)$row->sensor_val_id;
				if(array_key_exists($key, $this->view->content->displayed_sensors))
				{
					$journal[$row->time][$key] = $row;
				}
			}
		}
		catch (PDOException $e)
		{
			error_log('PDOException Experiment::journal(): '.var_export($e->getMessage(),true));  //DEBUG
			//var_dump($e->getMessage());
		}
		$db->commit();

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

			$this->setViewTemplate('graphsingle');
			$this->setTitle(L('graph_TITLE_GRAPH_FOR',array($experiment->title)));
			$this->addJs('lib/jquery.flot');
			$this->addJs('lib/jquery.flot.time.min');
			$this->addJs('lib/jquery.flot.navigate');
			$this->addJs('functions');
			$this->addJs('chart');
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
				$this->view->form->submit->value = L('graph_SAVE');

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

			$this->setViewTemplate('graphsingle');
			$this->setTitle(L('graph_TITLE_ADD_GRAPH_FOR',array($experiment->title)));
			$this->setContentTitle(L('graph_TITLE_ADD_GRAPH_FOR_2',array($experiment->title)));
		}
		else
		{
			// List graphs

			$this->setTitle(L('graph_TITLE_GRAPHS_FOR',array($experiment->title)));
			//$this->setContentTitle(L('graph_TITLE_GRAPHS_FOR_2',array($experiment->title)));
			// Flot lib
			$this->addJs('lib/jquery.flot');
			$this->addJs('lib/jquery.flot.time.min');
			$this->addJs('lib/jquery.flot.navigate');
			// PDF export libs
			$this->addJs('lib/jspdf.min');
			$this->addJs('lib/html2canvas');
			//
			$this->addJs('functions');
			$this->addJs('chart');

			// Add language translates for scripts
			Language::script(array(
					'ERROR',
					'graph_PLEASE_SELECT_2_SENSORS_SCATTER'
			));

			$db = new DB();

			// Get plots list
			$sql = 'select * from plots where exp_id = '.(int)$experiment->id;
			$stmt = $db->prepare($sql);
			$stmt->execute();
			$plots = $stmt->fetchAll(PDO::FETCH_OBJ);
			if ($plots === false)
			{
				$plots = array();
			}
			$this->view->content->list = &$plots;


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
			$this->view->content->available_sensors = &$sensors;

			// Plot with all sensors data loads on ajax request
			$this->view->content->detections = array();
		}
	}

	public function scatter()
	{
		if (empty($this->id))
		{
			System::go('experiment/view');
		}

		// Load experiment
		$this->view->content->experiment = $experiment = (new Experiment())->load($this->id);
		if(!$experiment)
		{
			System::go('experiment/view');
		}

		// Check access to experiment
		if(!$experiment->userCanView($this->session()))
		{
			System::go('experiment/view');
		}

		// Error messages
		$this->view->content->error = array();

		// Request type
		/*
		$_req = array();
		switch(strtoupper($_SERVER['REQUEST_METHOD']))
		{
			case 'GET':  $_req = &$_GET; break;
			case 'POST': $_req = &$_POST; break;
			default:;
		}
		*/

		// Check two sensors filter (GET only?)
		// Format: {sensor_id}#{sensor_val_id}
		$sx = null;
		$sy = null;
		if (isset($_GET) && isset($_GET['sx']) && isset($_GET['sy']) && (strlen($_GET['sx']) > 0) && (strlen($_GET['sy']) > 0))
		{
			if (strcmp($_GET['sx'], $_GET['sy']) != 0)
			{
				$sx = $_GET['sx'];
				$sy = $_GET['sy'];
			}
			else
			{
				// error on equal sensors
				//System::go('experiment/view/' . (int)$experiment->id);
				$this->view->content->error['ERROR_INVALID_PARAMETERS'] = L('ERROR_INVALID_PARAMETERS');
			}
		}
		else
		{
			// error on empty sensors
			//System::go('experiment/view/' . (int)$experiment->id);
			$this->view->content->error['ERROR_INVALID_PARAMETERS'] = L('ERROR_INVALID_PARAMETERS');
		}

		// Filter datetimes
		$from = null;
		if (isset($_GET) && isset($_GET['from']) && strlen($_GET['from']) != 0)
		{
			// UTC time with seconds parts
			try
			{
				$from = new DateTime(System::cutdatemsec($_GET['from']));
				//$from->setTimezone(new DateTimeZone('UTC'));
				$from->setTimezone((new DateTime())->getTimezone());
			}
			catch (Exception $e)
			{
				// error on invalid format
				$this->view->content->error['ERROR_INVALID_PARAMETERS'] = L('ERROR_INVALID_PARAMETERS');
				$from = null;
			}
		}

		$to = null;
		if (isset($_GET['to']) && isset($_GET['to']) && strlen($_GET['to']) != 0)
		{
			// UTC time with seconds parts
			try
			{
				$to = new DateTime(System::cutdatemsec($_GET['to']));
				//$to->setTimezone(new DateTimeZone('UTC'));
				$to->setTimezone((new DateTime())->getTimezone());
			}
			catch (Exception $e)
			{
				// error on invalid format
				$this->view->content->error['ERROR_INVALID_PARAMETERS'] = L('ERROR_INVALID_PARAMETERS');
				$to = null;
			}
		}

		// Init arrays of sensors
		$det_sensors   = array();
		$setup_sensors = array();
		$mon_sensors   = array();
		$reg_sensors   = array();

		$db = new DB();

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
			error_log('PDOException Experiment::scatter(): '.var_export($e->getMessage(),true));  //DEBUG
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
		$this->view->content->available_sensors = &$sensors;

		// Search selected sensors
		$this->view->content->sensor_x = null;
		$this->view->content->sensor_y = null;
		foreach ($sensors as $key => $sensor)
		{
			if(strcmp($sx, $key) == 0)
			{
				$this->view->content->sensor_x = $sensors[$key];
			}
			if(strcmp($sy, $key) == 0)
			{
				$this->view->content->sensor_y = $sensors[$key];
			}
		}

		// Pass filter datetimes
		$this->view->content->from = $from;
		$this->view->content->to = $to;

		$this->setTitle(L('graph_TITLE_SCATTER_FOR',array($experiment->title)));
		//$this->setContentTitle(L('graph_TITLE_SCATTER_FOR_2',array($experiment->title)));
		// Flot lib
		$this->addJs('lib/jquery.flot');
		$this->addJs('lib/jquery.flot.navigate');
		// Flot plugins JUMFlot
		$this->addJs('lib/jquery.flot.JUMlib');
		$this->addJs('lib/jquery.flot.heatmap');
		$this->addJs('lib/jquery.flot.bubbles');
		// or all JUMFlot
		//$this->addJs('lib/JUMFlot');
		//$this->addJs('lib/JUMFlot.min');
		// PDF export libs
		$this->addJs('lib/jspdf.min');
		$this->addJs('lib/html2canvas');
		// Datetime picker
		//$this->addJs('lib/jquery.datetimepicker.full');
		$this->addJs('lib/jquery.datetimepicker.full.min');
		$this->addCss('jquery.datetimepicker.min');
		//
		$this->addJs('functions');
		$this->addJs('chart');

		// Add language translates for scripts
		Language::script(array(
				'ERROR',
				'FROM_', 'TO_',
				'graph_PLEASE_SELECT_SENSORS', 'graph_PLEASE_SELECT_DIFFERENT_SENSORS', 'graph_AVAILABLE_RANGE'
		));

		// Plot with all sensors data loads on ajax request
		$this->view->content->points = array();
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
			$query = $db->prepare('delete from detections where exp_id = :exp_id');
			$result = $query->execute(array(':exp_id' => $this->id));
			if (!$result)
			{
				error_log('PDOError: '.var_export($query->errorInfo(),true));  //DEBUG
			}

			System::goback('experiment/journal/'.$this->id, 'auto', 'destination', true);
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

		// Check form id
		if(isset($request) && isset($request['form-id']) && $request['form-id'] === 'experiment-journal-form')
		{
			// Prepare data for download

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

				// xxx: comment out this block if not use setups data for sensors at all
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
				error_log('PDOException Experiment::download(): '.var_export($e->getMessage(),true));  //DEBUG
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
				$displayed_sensors = array_intersect_key($sensors, $sensors_show);
			}
			else
			{
				$displayed_sensors = $sensors;
			}

			// Get requested document type
			$doc_type = (isset($request['type']) && in_array($request['type'], array('csv'))) ? $request['type'] : 'csv';
			$filename = 'detections-exp' . (int)$experiment->id . '-' . System::datemsecformat(null, 'YmdHisu', 'now') . '.' . $doc_type;

			// Force to show browser Save File dialog by disable buffering and flush to output
			// (after about 255b/1k of headers+data in main browsers showed dialog)
			// Clean output buffer
			while (@ob_end_clean())
			{
				// do nothing
			}

			switch ($doc_type)
			{
				case 'csv':
				default:
				{
					/*
					Examples of CSV:
					@see RFC 4180 (https://www.ietf.org/rfc/rfc4180.txt) with delimeter ";" (Microsoft Excel format)

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

					// Header
					$data_header = array();
					$data_header[] = 'N';
					$data_header[] = L('TIME');
					foreach ($displayed_sensors as $skey => $sensor)
					{
						$value_name = (string) preg_replace('/[^A-Z0-9_]/i', '_', $sensor->value_name);
						$si_notation = (string) preg_replace('/[^A-Z0-9_]/i', '_', $sensor->si_notation);
						$data_header[] = $sensor->name
								. ' ' . ((mb_strlen($sensor->value_name, 'utf-8') > 0 ) ?
										L('sensor_VALUE_NAME_' . strtoupper($value_name)) :
										'-')
								. ', ' . ((mb_strlen($sensor->value_name, 'utf-8') > 0 && mb_strlen($sensor->si_notation, 'utf-8') > 0) ?
										L('sensor_VALUE_SI_NOTATION_' . strtoupper($value_name) . '_' . strtoupper($si_notation)) :
										'-')
								. ' ' . '(id: ' . $skey . ')';
					}

					// Locale number format settings
					// (123.456 OR 123,456)
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

					// Send response headers to the browser
					header('Content-Type: text/csv');
					//header("Content-Length: " . strlen($output));  // XXX: unknown length, because prepare and output in db fetch loop
					header("Content-Disposition: attachment; filename=\"" . $filename . "\"");
					//header("Content-Transfer-Encoding: Binary");  // only for MIME in email protocols
					//header('Connection: Keep-Alive');
					//header('Expires: 0');
					header('Cache-Control: no-cache');
					//header('Cache-Control: max-age=60, must-revalidate');
					//header('Pragma: public');
					header('Set-Cookie: fileDownload=true; path=/');

					flush(); // Get the headers out immediately to show the download dialog in Firefox

					// Write data to output
					$fp = fopen('php://output', 'w');

					// Header output
					// XXX: convert from utf-8 to ansi for ms office
					fwrite($fp, iconv("UTF-8", "Windows-1251", System::strtocsv($data_header, ';')) . "\n");

					// No sensors - no data
					if (empty($displayed_sensors))
					{
						fclose($fp);
						exit();
					}

					//// Speed up db operations within transaction
					//$db->beginTransaction();
					// Catch for correct file close also
					try
					{
						// Get detections
						//TODO: add filter support with 'dtfrom' and 'dtto' parameters for date range
						// XXX: Carefully use WHERE filter by sensor_id and sensor_val_id, watch to sensors count, may be too long list for db
						$is_where_sensors = true;
						$where_sensors = ' and (ifnull(sensor_id,\'\')||\'#\'||ifnull(sensor_val_id,\'\')) in (' . implode(',', array_fill(0, count($displayed_sensors), '?')) . ')';

						//$sql = 'select id, time, strftime(\'%Y-%m-%dT%H:%M:%fZ\', time) as mstime, sensor_id, sensor_val_id, detection, error'
						$sql = 'select time, sensor_id, sensor_val_id, detection, error'
								. ' from detections'
								. ' where exp_id = ' . (int)$experiment->id
								. ($is_where_sensors ? $where_sensors : '')
								//. ' order by strftime(\'%s\', time),strftime(\'%f\', time)';
								. ' order by strftime(\'%Y-%m-%dT%H:%M:%f\', time), id';
						$query = $db->prepare($sql);
						$query->execute($is_where_sensors ? array_keys($displayed_sensors) : null);

						// Prepare empty data row which has order the same as header
						$empty_data_row = array('0' => '', '1' => '');
						foreach ($displayed_sensors as $key => $value)
						{
							$empty_data_row[$key] = '';
						}

						$last_time = null;
						$data_row  = null;
						$i         = 0;     // detections counter
						$j         = 0;     // data rows counter
						$flush_num = 100;   // Flush output to the browser every N lines. Tweak flush number based upon the size of CSV rows?!

						// Raw values grouped by full timestamps (UTC datetime!)
						while (($row = $query->fetch(PDO::FETCH_OBJ)) !== false)
						{
							// Manual filter by sensor+value. If available than add to output
							$key = '' . $row->sensor_id . '#' . (int)$row->sensor_val_id;
							if($is_where_sensors || array_key_exists($key, $displayed_sensors))
							{
								$i++;

								// Check new row start
								if ($last_time === null || $last_time !== $row->time)
								{
									$j++;

									// Output prepared row
									if ($last_time !== null)
									{
										// XXX: convert from utf-8 to ansi for ms office, BUT only numbers here
										//fwrite($fp, iconv("UTF-8", "Windows-1251", System::strtocsv($data_row, ';')) . "\n");
										fwrite($fp, System::strtocsv($data_row, ';') . "\n");

										if ($j % $flush_num == 0)
										{
											// Attempt to flush output to the browser every flush_num lines.
											// Tweak div number based upon the size of CSV rows.
											flush();
										}
									}

									unset($data_row);

									$data_row = $empty_data_row;
									$data_row['0'] = (int)$j;
									//try {
									$data_row['1'] = System::datemsecformat($row->time, System::DATETIME_FORMAT1NANOXLS, 'now');
									//} catch (Exception $e) {}
									$last_time = $row->time;
								}

								// Add value
								if ($row->error !== 'NaN')
								{
									// Locale number format fix
									if (isset($decimal_point_new))
									{
										$data_row[$key] = str_replace($decimal_point, $decimal_point_new, (float)$row->detection);
									}
									else
									{
										$data_row[$key] = (float)$row->detection;
									}
								}
								else
								{
									$data_row[$key] = '';
								}
							}
						}
						// Output last row
						//if (isset($data_row) && !empty($data_row))
						if ($j > 0)
						{
							$j++;

							// Output prepared row
							// XXX: convert from utf-8 to ansi for ms office, BUT only numbers here
							//fwrite($fp, iconv("UTF-8", "Windows-1251", System::strtocsv($data_row, ';')));
							fwrite($fp, System::strtocsv($data_row, ';'));
						}
					}
					catch (PDOException $e)
					{
						error_log('PDOException Experiment::download(): '.var_export($e->getMessage(),true));  //DEBUG
						//var_dump($e->getMessage());
					}
					catch (Exception $e)
					{
						error_log('PDOException Experiment::download(): '.var_export($e->getMessage(),true));  //DEBUG
						//var_dump($e->getMessage());
					}
					//$db->commit();
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
	 * Check if active experiment.
	 * 
	 * API method: Experiment.isActive
	 * API params: experiment
	 *
	 * @param  array $params  Array of parameters
	 *
	 * @return array  Result in form array('result' => True) on success or False on error
	 */
	public function isActive($params)
	{
		// Check id
		if(!isset($params['experiment']) && empty($params['experiment']))
		{
			$this->error = L('ERROR_EXPERIMENT_NOT_FOUND');
			return false;
		}

		// Load experiment
		$experiment = (new Experiment())->load((int)$params['experiment']);
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

		// Check and return active
		return array('result' => Experiment::isActive($experiment->id) ? true : false);
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
	 * Load experiments ids by session or all
	 * 
	 * @param  string  $session_key  $session_key
	 * 
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

	private function sensorList()
	{
		// TODO: prepare full sensors list from exp + mons + setup for experiment
		return array();
	}
}
