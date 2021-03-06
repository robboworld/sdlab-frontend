<?php
/**
 * Class SetupController
 *
 * Sensors setup actions controller
 */
class SetupController extends Controller
{
	public function __construct($action, $config = array('default_action' => 'index'))
	{
		parent::__construct($action, $config);

		// Register the methods as actions.
		$this->registerAction('create', 'create');
		$this->registerAction('edit', 'edit');

		// Get id from request query string setup/edit/%id
		$this->id = App::router(2);
	}

	public function index()
	{
		System::go('setup/create');
	}

	public function create()
	{
		// TODO: Access to creation only for admin?
		$this->setTitle(L('setup_TITLE_CREATION'));
		$this->setContentTitle(L('setup_TITLE_CREATION'));
		$this->addJs('functions');
		$this->addJs('setup/edit');
		$this->addCss('setup');
		// Add language translates for scripts
		Language::script(array(
				'ERROR', 'sensor_NAME', 'REMOVE'  // setup/edit
		));


		// TODO: Create Setups without master?
		// Need master experiment id
		if(!isset($_GET['master']) || !is_numeric($_GET['master']))
		{
			System::go('experiment/view');
		}

		$this->view->form = new Form('create-setup-form');
		$this->view->form->submit->value = L('CREATE');

		if(isset($_POST) && isset($_POST['form-id']) && $_POST['form-id'] === 'create-setup-form')
		{
			// Save new Setup

			// Get master experiment
			$master_exp = (new Experiment())->load($_GET['master']);
			if(!$master_exp)
			{
				System::go('experiment/view');
			}

			// Check access to master experiment
			if(!$master_exp->userCanEdit($this->session()))
			{
				System::go('experiment/view');
			}

			$setup = new Setup($this->session()->getKey());

			// Check access to create
			if(!$setup->userCanCreate($this->session()))
			{
				System::go('experiment/view');
			}

			$setup->set('title', isset($_POST['setup_title']) ? $_POST['setup_title'] : '');

			// Set Setup mode
			$setup_type = isset($_POST['setup-type']) ? $_POST['setup-type'] : '';
			if($setup_type === 'setup_type_amount')
			{
				$amount = isset($_POST['amount']) ? (int)$_POST['amount'] : 0;
				$amount = $amount > 0 ? $amount : null;
				$setup->set('amount', $amount);
			}
			else if($setup_type === 'setup_type_length')
			{
				$setup->set('time_det', Form::DHMStoSec(array((int)$_POST['time_det_day'],(int)$_POST['time_det_hour'],(int)$_POST['time_det_min'],(int)$_POST['time_det_sec'])));
			}

			// Common fields for all kinds of detections
			$interval = isset($_POST['interval']) ? (int)$_POST['interval'] : 0;
			$interval = $interval > 0 ? $interval : null;
			$setup->set('interval', $interval);
			$setup->set('number_error', isset($_POST['number_error']) ? (int)$_POST['number_error'] : 0);
			$setup->set('period_repeated_det', isset($_POST['period_repeated_det']) ? (int)$_POST['period_repeated_det'] : 0);
			$setup->set('master_exp_id', $master_exp->id);
			$setup->set('access', isset($_POST['access']) && in_array((int)$_POST['number_error'], array(Setup::$ACCESS_SHARED,Setup::$ACCESS_PRIVATE,Setup::$ACCESS_SINGLE)) ?
					(int)$_POST['access'] :
					Setup::$ACCESS_SHARED
			);

			// Validate
			$valid = (isset($setup->title) && (strlen($setup->title)>0))
					&& (isset($setup->interval) && $setup->interval>0)
					&& ((isset($setup->amount) && ($setup->amount>0)) || (isset($setup->time_det) && $setup->time_det>0));
			if($valid)
			{
				if ($setup->save() && !empty($setup->id))
				{
					$this->id = $setup->id;
					// If is set experiment-master and no Setup used then assign to it new current Setup
					if (empty($master_exp->setup_id))
					{
						$master_exp->set('setup_id', $setup->id);
						$master_exp->save();
					}

					// Setup sensors
					if(isset($_POST['sensors']) && !empty($_POST['sensors']) && is_array($_POST['sensors']))
					{
						$this->resetSensors();
						$this->setSensors($_POST['sensors']);
					}

					// Redirect to master experiment
					System::go('experiment/view/'.$master_exp->id);
				}
			}

			// Show edit form with filled fields on validate/save errors
			$this->view->form->setup = $setup;
			$this->view->form->setup->active = false;
		}
		else
		{
			// Edit new Setup

			$setup = new Setup();
			$this->view->form->setup = $setup;
			$this->view->form->setup->active = false;

			// Check access to create (now can view creation page)
			/*
			if(!$this->view->form->setup->userCanCreate($this->session()))
			{
				System::go('experiment/view');
			}
			*/
		}
	}

	public function edit()
	{
		$this->setViewTemplate('create');
		$this->setTitle(L('setup_TITLE_EDIT'));
		$this->setContentTitle(L('setup_TITLE_EDIT'));
		$this->addJs('functions');
		$this->addJs('setup/edit');
		$this->addCss('setup');
		// Add language translates for scripts
		Language::script(array(
				'ERROR', 'sensor_NAME', 'REMOVE'  // setup/edit
		));

		if(is_null($this->id) || empty($this->id))
		{
			System::go('setup/create');
		}


		$this->view->form = new Form('edit-setup-form');
		$this->view->form->submit->value = L('SAVE');

		// Load Setup
		$setup = (new Setup())->load($this->id);
		if(!$setup)
		{
			System::go('setup/create');
		}

		// Check access to edit
		if(!$setup->userCanEdit($this->session()))
		{
			System::go('experiment/view');
		}

		$this->view->form->setup = $setup;

		if(isset($_POST) && isset($_POST['form-id']) && $_POST['form-id'] === 'edit-setup-form')
		{
			// Save Setup

			$setup->set('title', isset($_POST['setup_title']) ? $_POST['setup_title'] : '');

			// Set Setup mode
			$setup_type = isset($_POST['setup-type']) ? $_POST['setup-type'] : '';
			if($setup_type === 'setup_type_amount')
			{
				$amount = isset($_POST['amount']) ? (int)$_POST['amount'] : 0;
				$amount = $amount > 0 ? $amount : 0;
				$setup->set('amount', $amount);
				$setup->set('time_det', null);
			}
			else if ($setup_type === 'setup_type_length')
			{
				$setup->set('amount', null);
				$setup->set('time_det', Form::DHMStoSec(array((int)$_POST['time_det_day'],(int)$_POST['time_det_hour'],(int)$_POST['time_det_min'],(int)$_POST['time_det_sec'])));
			}

			// Common fields for all kinds of detections
			$interval = isset($_POST['interval']) ? (int)$_POST['interval'] : 0;
			$interval = $interval > 0 ? $interval : null;
			$setup->set('interval', $interval);
			$setup->set('number_error', isset($_POST['number_error']) ? (int)$_POST['number_error'] : 0);
			$setup->set('period_repeated_det', isset($_POST['period_repeated_det']) ? (int)$_POST['period_repeated_det'] : 0);

			// Set owner of incorrect setup to himself
			if (empty($setup->session_key))
			{
				$setup->set('session_key', $this->session()->getKey());
			}

			// TODO: add edit Setup access by owner and not in use (current/active mon?), instead leave changed / lock run / unset from exp / cannot change ?
			/*
			$setup->set('access', isset($_POST['access']) && in_array((int)$_POST['number_error'], array(Setup::$ACCESS_SHARED,Setup::$ACCESS_PRIVATE,Setup::$ACCESS_SINGLE)) ?
					(int)$_POST['access'] :
					$setup->access
			);
			*/

			// Setup sensors
			if(isset($_POST['sensors']) && !empty($_POST['sensors']) && is_array($_POST['sensors']))
			{
				$this->resetSensors();
				$this->setSensors($_POST['sensors']);
			}


			// Validate
			$valid = (isset($setup->title) && (strlen($setup->title)>0))
					&& (isset($setup->interval) && $setup->interval>0)
					&& ((isset($setup->amount) && $setup->amount>0) || (isset($setup->time_det) && $setup->time_det>0));
			if($valid)
			{
				if($setup->save() && !empty($setup->id))
				{
					// Redirect to master experiment
					if ($setup->master_exp_id)
					{
						System::go('experiment/view/'.$setup->master_exp_id);
					}
					else
					{
						System::go('experiment/view');
					}
				}
			}
			// stay on edit form on errors
		}

		// Rewrite Setup for update fields with requested data
		$this->view->form->setup = $setup;
		$this->view->form->setup->active = Setup::isActive($this->view->form->setup->id);

		// Get available sensors with sensors info
		$this->view->form->sensors = self::getSensors($this->id, true);
	}

	/**
	 * Select setups filtered by access modes
	 * 
	 * @param  array $modes  The access modes of setup in array keys, use values from additional arguments fo mode:
	 *                         - ACCESS_SINGLE : integer|array, experiment id for single access or array of ids
	 * 
	 * @return array  Array of objects (with fields: id, title)
	 */
	public static function loadSetups($modes)
	{
		$db = new DB();

		$where = array();
		if (!empty($modes))
		{
			$kmodes = array_keys($modes);

			// Exclude complex modes
			// ACCESS_SINGLE
			$k = array_search(Setup::$ACCESS_SINGLE, $kmodes);
			if ($k !== false)
			{
				// if set experiment id value, than use special condition
				$opt = $modes[Setup::$ACCESS_SINGLE];
				$exp_ids = null;
				if (!is_array($opt))
				{
					$exp_ids = array((int)$opt);
				}
				else
				{
					$exp_ids = $opt;
				}
				// Filter 0 values
				//array_filter($exp_ids);  // xxx: but need cast to int
				foreach ($exp_ids as $i => $value)
				{
					if (!(int)$value)
					{
						unset($exp_ids[$i]);
					}
					else
					{
						$exp_ids[$i] = (int)$value;
					}
				}

				if (!empty($exp_ids))
				{
					$where[] = ((count($exp_ids) == 1) ?
							('access = ' . (int)$kmodes[$k] . ' and master_exp_id = ' . (int)end($exp_ids)) :
							('access = ' . (int)$kmodes[$k] . ' and master_exp_id in (' .  implode(',', $exp_ids) . ')')
					);
					unset($kmodes[$k]);
				}
			}

			// ACCESS_PRIVATE
			$k = array_search(Setup::$ACCESS_PRIVATE, $kmodes);
			if ($k !== false)
			{
				// if set session_key value, than use special condition
				$opt = $modes[Setup::$ACCESS_PRIVATE];
				$session_keys = null;
				if (!is_array($opt))
				{
					$session_keys = array((string)$opt);
				}
				else
				{
					$session_keys = $opt;
				}
				// Filter empty values
				foreach ($session_keys as $i => $value)
				{
					if (mb_strlen($value, 'UTF-8') == 0)
					{
						unset($session_keys[$i]);
					}
					else
					{
						$session_keys[$i] = $db->quote((string)$value);
					}
				}

				if (!empty($session_keys))
				{
					$where[] = ((count($session_keys) == 1) ?
							('access = ' . (int)$kmodes[$k] . ' and session_key = ' .  end($session_keys)) :
							('access = ' . (int)$kmodes[$k] . ' and session_key in (' .  implode(',', $session_keys) . ')')
					);
					unset($kmodes[$k]);
				}
			}

			if (!empty($kmodes))
			{
				$where[] = ((count($kmodes) == 1) ?
						('access = ' . (int)end($kmodes)) :
						('access in (' . implode(',', $kmodes) . ')')
				);
			}
		}

		$query = $db->prepare(
				'select id,title,master_exp_id,session_key '
				. 'from setups '
				. ((!empty($where)) ? ('where ('. implode(') or (', $where) . ')') : '')
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

		return $query->fetchAll(PDO::FETCH_OBJ);
	}


	/**
	 * Get sensors in Setup
	 * 
	 * @param  integer $id       The id of Setup
	 * @param  bool    $getinfo  Get additional sensors info from sensors register
	 * @param  DB      $db       Database instance for use, or null if new
	 * 
	 * @return array|bool
	 */
	public static function getSensors($id, $getinfo = false, $db = null)
	{
		// TODO: Rescan sensors for connect status
		if(!is_numeric($id))
		{
			return false;
		}

		if ($db === null)
		{
			$db = new DB();
		}

		if ($getinfo)
		{
			$stmt = $db->prepare(
					"select a.sensor_id as sensor_id, a.sensor_val_id, a.name as name, a.setup_id as setup_id, "
						. "s.value_name as value_name, s.si_notation as si_notation, s.si_name as si_name, s.max_range as max_range, s.min_range as min_range, s.resolution as resolution "
					. "from setup_conf as a "
					. "left join sensors as s on a.sensor_id = s.sensor_id and a.sensor_val_id = s.sensor_val_id "
					. "where a.setup_id = :setup_id "
					. "group by a.sensor_id, a.sensor_val_id"
			);
		}
		else
		{
			$stmt = $db->prepare("select sensor_id as sensor_id, sensor_val_id, name as name, setup_id as setup_id from setup_conf where setup_id = :setup_id");
		}
		$stmt->execute(array(
				':setup_id' => $id
		));
		return $stmt->fetchAll(PDO::FETCH_OBJ);
	}


	/**
	 * Insert into setup_conf all sensors selected in form
	 * 
	 * @param array $sensors
	 * 
	 * @return bool
	 */
	public function setSensors(array $sensors)
	{
		if(empty($this->id))
		{
			return false;
		}

		$db = new DB();

		$db->beginTransaction();
		try
		{
			$insert_query = "insert into setup_conf (setup_id, sensor_id, sensor_val_id, name) values (:setup_id, :sensor_id, :sensor_val_id, :name)";

			$stmt = $db->prepare($insert_query);
			foreach($sensors as $items)
			{
				foreach($items as $sensor)
				{
					$sensor = (object) $sensor;
					if(!empty($sensor->id) && !empty($sensor->name) && isset($sensor->val_id))
					{
						$stmt->execute(array(
								':setup_id'      => $this->id,
								':sensor_id'     => $sensor->id,
								':sensor_val_id' => $sensor->val_id,
								':name'          => $sensor->name
						));
					}
				}
			}
		}
		catch (PDOException $e)
		{
			error_log('PDOException SetupController::setSensors(): '.var_export($e->getMessage(),true));  //DEBUG
			//var_dump($e->getMessage());
		}
		$db->commit();

		return true;
	}


	/**
	 * Reset all rows in setup_conf where setup_id = this setup id
	 * 
	 * @return bool
	 */
	public function resetSensors()
	{
		if(empty($this->id))
		{
			return false;
		}

		$db = new DB();

		$stmt = $db->prepare("delete from setup_conf where setup_id = :setup_id");
		$stmt->execute(array(
				':setup_id' => $this->id
		));

		return true;
	}
}