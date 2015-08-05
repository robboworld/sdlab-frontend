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


			if(isset($_POST) && $_POST['form-id'] == 'create-setup-form')
			{
				if($master_exp = (new Experiment())->load($_GET['master']))
				{
					if(($master_exp->session_key == $this->session()->getKey()) || $this->session()->getUserLevel() == 3)
					{
						$setup = new Setup();

						$setup->set('title', htmlspecialchars($_POST['setup_title']));

						if($_POST['setup-type'] == 'setup-type-amount')
						{
							$setup->set('amount', htmlspecialchars($_POST['amount']));
						}

						if($_POST['setup-type'] == 'setup-type-length')
						{
							$setup->set('time_det', Form::DHMStoSec(array($_POST['time_det_day'],$_POST['time_det_hour'],$_POST['time_det_min'], $_POST['time_det_sec'])));
						}

						/* Общие поля для всех типов измерений */
						$setup->set('interval', htmlspecialchars($_POST['interval']));
						$setup->set('number_error', htmlspecialchars($_POST['number_error']));
						$setup->set('period_repeated_det', htmlspecialchars($_POST['period_repeated_det']));
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
									if(!empty($_POST['sensors']))
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
				/* если пользователь не может редактирвать то отправляем на страницу экспериментов*/
				if($setup->userCanEdit($this->session()))
				{
					$this->view->form->setup = $setup;

					if(isset($_POST) && $_POST['form-id'] == 'edit-setup-form')
					{
						$setup->set('title', htmlspecialchars($_POST['setup_title']));
						if($_POST['setup-type'] == 'setup-type-amount')
						{
							$setup->set('amount', htmlspecialchars($_POST['amount']));
							$setup->set('time_det', null);
						}

						if($_POST['setup-type'] == 'setup-type-length')
						{
							$setup->set('time_det', Form::DHMStoSec(array($_POST['time_det_day'],$_POST['time_det_hour'],$_POST['time_det_min'], $_POST['time_det_sec'])));
							$setup->set('amount', null);
						}

						/* Общие поля для всех типов измерений */
						$setup->set('interval', htmlspecialchars($_POST['interval']));
						$setup->set('number_error', htmlspecialchars($_POST['number_error']));
						$setup->set('period_repeated_det', htmlspecialchars($_POST['period_repeated_det']));

						/* Setup sensors*/
						if(!empty($_POST['sensors']))
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
					$this->view->form->setup = $setup;

					$this->view->form->sensors = self::getSensors($this->id);
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
	static function getSensors($id)
	{
		/*todo: переопрашивать датчики на наличие отключенных */
		if(is_numeric($id))
		{
			$db = new DB();
			$search = $db->prepare("select sensor_id as id, name as name, setup_id as setup_id from setup_conf where setup_id = :setup_id");
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
			$insert_query = "insert into setup_conf (setup_id, sensor_id, name) values (:setup_id, :sensor_id, :name)";

			$set = $db->prepare($insert_query);
			foreach($sensors as $sensor)
			{
				$sensor = (object) $sensor;
				if(!empty($sensor->id) && !empty($sensor->name))
				{
					$set->execute(array(
						':setup_id' => $this->id,
						':sensor_id' => $sensor->id,
						':name' => $sensor->name
					));
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