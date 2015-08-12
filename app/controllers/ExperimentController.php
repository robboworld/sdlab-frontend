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
		//System::dump($setups);



		if(isset($_POST) && isset($_POST['form-id']) && $_POST['form-id'] == 'create-experiment-form')
		{
			/* fill the Experiment properties */
			$experiment = new Experiment($this->session()->getKey());
			$experiment->set('title', htmlspecialchars(isset($_POST['experiment_title']) ? $_POST['experiment_title'] : ''));
			$experiment->set('setup_id', htmlspecialchars(isset($_POST['setup_id']) ? $_POST['setup_id'] : ''));
			$experiment->set('comments', htmlspecialchars(isset($_POST['experiment_comments']) ? $_POST['experiment_comments'] : ''));

			//$experiment->set('DateStart_exp', (new DateTime($_POST['experiment_date_start']))->format(DateTime::ISO8601));
			//$experiment->set('DateEnd_exp', (new DateTime($_POST['experiment_date_end']))->format(DateTime::ISO8601));

			/* Access Experiment in view*/
			$this->view->form->experiment = $experiment;




			if(!empty($experiment->title))
			{
				if($experiment->save() && !is_null($experiment->id))
				{
					System::go('experiment/view/'.$experiment->id);
				}
			}
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
			/* Все эксперименты*/
			$this->view->content->list = self::experimentsList();
			self::setViewTemplate('view.all');
			self::setTitle('Все экспериметы');
			/*View all available experiments in this session*/
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
					$experiment->set('setup_id', htmlspecialchars(isset($_POST['setup_id']) ? $_POST['setup_id'] : ''));
					$experiment->set('comments', htmlspecialchars(isset($_POST['experiment_comments']) ? $_POST['experiment_comments'] : ''));
					if(!empty($experiment->title))
					{
						if($experiment->save() && !is_null($experiment->id))
						{
							System::go('experiment/view/'.$experiment->id);
						}
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
	 */
	function delete()
	{
		if(!empty($this->id))
		{

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

				/*todo: изменить таблицу. сделать exp_id целочисленым, чтобы использовать = вместо LIKE */
				$query = 'select * from detections where exp_id = '.(int)$experiment->id . ' order by strftime(\'%s\', time)';
				$detections = $db->query($query, PDO::FETCH_OBJ);

				/* Формирование вывода на основе датчиков в установке. */
				$sensors = SetupController::getSensors($experiment->setup_id);
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
					if(array_key_exists($row->id_sensor, $this->view->content->displayed_sensors))
					{
						$journal[$row->time][$row->id_sensor] = $row;
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
		if(!empty($this->id))
		{
			$this->view->content->experiment = $experiment = (new Experiment())->load($this->id);

			$this->view->form = new Form('plot-edit-form');
			$this->view->form->submit->value = 'Сохранить график';

			if(is_numeric(App::router(3)))
			{
				self::setViewTemplate('graphsingle');
				self::setTitle('График для '.$experiment->title);
				self::addJs('lib/jquery.flot');
				self::addJs('lib/jquery.flot.time.min');
				self::addJs('lib/jquery.flot.navigate');
				self::addJs('functions');
				self::addJs('chart');


				$plot_id = App::router(3);
				/* Просмотр графика*/

				$plot = (new Plot())->load(App::router(3));
				if(isset($_POST['form-id']) && $_POST['form-id'] == 'plot-edit-form')
				{

				}

				$this->view->content->plot = $plot;
			}
			elseif (App::router(3) == 'add')
			{
				self::setViewTemplate('graphsingle');
			}
			else
			{
				$db = new DB();
				$query = 'select * from plots where exp_id LIKE '.$experiment->id;
				$plots = $db->query($query, PDO::FETCH_OBJ);

				$this->view->content->list = $plots;
			}
		}
	}

	/**
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