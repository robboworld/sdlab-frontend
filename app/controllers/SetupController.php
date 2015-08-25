<?


class SetupController extends Controller
{

	function __construct($action = 'create')
	{
		$this->id = App::router(2);
		parent::__construct($action);
	}
	function index()
	{
		System::go('setup/create');
	}

	function create()
	{
		/*todo: создание разрешить только админу*/
		self::setTitle('Создание установки');
		self::setContentTitle('Создание установки');
		self::addJs('functions');
		self::addJs('setup/edit');
		self::addCss('setup');

		/* Необходимо указание мастер-эксперимента */
		if(isset($_GET['master']) && is_numeric($_GET['master']))
		{
			$this->view->form = new Form('create-setup-form');
			$this->view->form->submit->value = 'Создать';


			if(isset($_POST) && isset($_POST['form-id']) && $_POST['form-id'] === 'create-setup-form')
			{
				if($master_exp = (new Experiment())->load($_GET['master']))
				{
					if(($master_exp->session_key == $this->session()->getKey()) || $this->session()->getUserLevel() == 3)
					{
						$setup = new Setup();

						$setup->set('title', htmlspecialchars(isset($_POST['setup_title']) ? $_POST['setup_title'] : ''));

						$setup_type = isset($_POST['setup-type']) ? $_POST['setup-type'] : '';
						if($setup_type === 'setup-type-amount')
						{
							$amount = isset($_POST['amount']) ? (int)$_POST['amount'] : 0;
							$amount = $amount > 0 ? $amount : null;
							$setup->set('amount', htmlspecialchars($amount));
						}

						if($setup_type === 'setup-type-length')
						{
							$setup->set('time_det', Form::DHMStoSec(array((int)$_POST['time_det_day'],(int)$_POST['time_det_hour'],(int)$_POST['time_det_min'],(int)$_POST['time_det_sec'])));
						}

						/* Общие поля для всех типов измерений */
						$interval = isset($_POST['interval']) ? (int)$_POST['interval'] : 0;
						$interval = $interval > 0 ? $interval : null;
						$setup->set('interval', htmlspecialchars($interval));
						$setup->set('number_error', htmlspecialchars(isset($_POST['number_error']) ? (int)$_POST['number_error'] : 0));
						$setup->set('period_repeated_det', htmlspecialchars(isset($_POST['period_repeated_det']) ? (int)$_POST['period_repeated_det'] : 0));
						$setup->set('master_exp_id', $master_exp->id);

						if(isset($setup->title) && isset($setup->interval))
						{
							if(isset($setup->amount) || isset($setup->time_det))
							{
								if($setup->save() && !empty($setup->id))
								{
									$this->id = $setup->id;
									/* Если указан мастер-эксперимент то присваиваем ему текущую установку*/
									if(isset($master_exp))
									{
										$master_exp->set('setup_id', $setup->id);
										$master_exp->save();
									}

									/* Setup sensors*/
									if(isset($_POST['sensors']) && !empty($_POST['sensors']) && is_array($_POST['sensors']))
									{
										self::resetSensors();
										self::setSensors($_POST['sensors']);
									}

									/* Перенаправляем на мастер-эксперимент */
									System::go('experiment/view/'.$master_exp->id);
								}
							}
						}

						$this->view->form->setup = $setup;
					}
					else
					{
						System::go('experiment/view');
					}
				}
				else
				{
					System::go('experiment/view');
				}
			}
			else 
			{
				$setup = new Setup();
				$this->view->form->setup = $setup;
			}
		}
		else
		{
			System::go('experiment/view');
		}

	}

	function edit()
	{
		self::setViewTemplate('create');
		self::setTitle('Редактирование установки');
		self::setContentTitle('Редактирование установки');
		self::addJs('functions');
		self::addJs('setup/edit');
		self::addCss('setup');

		if(!is_null($this->id) && !empty($this->id))
		{
			$this->view->form = new Form('edit-setup-form');
			$this->view->form->submit->value = 'Сохранить';

			if($setup = (new Setup())->load($this->id))
			{
				/* если пользователь не может редактировать, то отправляем на страницу экспериментов*/
				if($setup->userCanEdit($this->session()))
				{
					$this->view->form->setup = $setup;

					if(isset($_POST) && isset($_POST['form-id']) && $_POST['form-id'] === 'edit-setup-form')
					{
						$setup->set('title', htmlspecialchars(isset($_POST['setup_title']) ? $_POST['setup_title'] : ''));
						$setup_type = isset($_POST['setup-type']) ? $_POST['setup-type'] : '';
						if($setup_type === 'setup-type-amount')
						{
							$amount = isset($_POST['amount']) ? (int)$_POST['amount'] : 0;
							$amount = $amount > 0 ? $amount : 1;
							$setup->set('amount', htmlspecialchars($amount));
							$setup->set('time_det', null);
						}

						if($setup_type === 'setup-type-length')
						{
							$setup->set('time_det', Form::DHMStoSec(array((int)$_POST['time_det_day'],(int)$_POST['time_det_hour'],(int)$_POST['time_det_min'],(int)$_POST['time_det_sec'])));
							$setup->set('amount', null);
						}

						/* Общие поля для всех типов измерений */
						$interval = isset($_POST['interval']) ? (int)$_POST['interval'] : 0;
						$interval = $interval > 0 ? $interval : 10;
						$setup->set('interval', htmlspecialchars($interval));
						$setup->set('number_error', htmlspecialchars(isset($_POST['number_error']) ? (int)$_POST['number_error'] : 0));
						$setup->set('period_repeated_det', htmlspecialchars(isset($_POST['period_repeated_det']) ? (int)$_POST['period_repeated_det'] : 0));

						/* Setup sensors*/
						if(isset($_POST['sensors']) && !empty($_POST['sensors']) && is_array($_POST['sensors']))
						{
							self::resetSensors();
							self::setSensors($_POST['sensors']);
						}

						if(isset($setup->title) && isset($setup->interval))
						{
							if(isset($setup->amount) || isset($setup->time_det))
							{
								if($setup->save() && !empty($setup->id))
								{
									System::go('experiment/view/'.$setup->master_exp_id);
								}
							}
						}
					}

					// Rewrite setup for update fields  with request date
					$this->view->form->setup = $setup;

					// Get available sensors with sensors info
					$this->view->form->sensors = self::getSensors($this->id, true);
				}
				else
				{
					System::go('experiment/view');
				}

			}
			else
			{
				System::go('setup/create');
			}
		}
		else
		{
			System::go('setup/create');
		}


	}

	/**
	 * @return array
	 */
	static function loadSetups()
	{
		$db = new DB();

		$search = $db->prepare('select id,title from setups');
		$search->execute();

		return $search->fetchAll(PDO::FETCH_OBJ);
	}


	/**
	 * @param $id
	 * @return array|bool
	 */
	static function getSensors($id, $getinfo = false)
	{
		/*todo: переопрашивать датчики на наличие отключенных */
		if(is_numeric($id))
		{
			$db = new DB();
			if ($getinfo)
			{
				$search = $db->prepare(
						"select a.sensor_id as id, a.sensor_val_id, a.name as name, a.setup_id as setup_id, "
							. "s.value_name as value_name, s.si_notation as si_notation, s.si_name as si_name, s.max_range as max_range, s.min_range as min_range, s.resolution as resolution "
						. "from setup_conf as a "
						. "left join sensors as s on a.sensor_id = s.sensor_id and a.sensor_val_id = s.sensor_val_id "
						. "where a.setup_id = :setup_id "
						. "group by a.sensor_id, a.sensor_val_id"
				);
			}
			else 
			{
				$search = $db->prepare("select sensor_id as id, sensor_val_id, name as name, setup_id as setup_id from setup_conf where setup_id = :setup_id");
			}
			$search->execute(array(
				':setup_id' => $id
			));
			return $search->fetchAll(PDO::FETCH_OBJ);
		}
		else
		{
			return false;
		}

	}


	/**
	 * Insert into setup_conf all sensors selected in form
	 * @param array $sensors
	 * @return bool
	 */
	function setSensors(array $sensors)
	{
		if(!empty($this->id))
		{
			$db = new DB();
			$insert_query = "insert into setup_conf (setup_id, sensor_id, sensor_val_id, name) values (:setup_id, :sensor_id, :sensor_val_id, :name)";

			$set = $db->prepare($insert_query);
			foreach($sensors as $items)
			{
				foreach($items as $sensor)
				{
					$sensor = (object) $sensor;
					if(!empty($sensor->id) && !empty($sensor->name) && isset($sensor->val_id))
					{
						$set->execute(array(
								':setup_id' => $this->id,
								':sensor_id' => $sensor->id,
								':sensor_val_id' => $sensor->val_id,
								':name' => $sensor->name
						));
					}
				}
			}
		}
		else
		{
			return false;
		}
	}

	/**
	 * Reset all rows in setup_conf where setup_id = this setup id
	 */
	function resetSensors()
	{
		if(!empty($this->id))
		{
			$db = new DB();
			$reset = $db->prepare("delete from setup_conf where setup_id = :setup_id");
			$reset->execute(array(
				':setup_id' => $this->id
			));
		}
	}
}