<?

class SessionController extends Controller
{
	public $user_access_level = 0;
	function create()
	{
		if(isset($_POST['session_key']))
		{
			$session = new Session();
			if($session->load($_POST['session_key']) && strlen($_POST['session_key']) == 6)
			{
				$this->session($session);
				$this->session()->setSession();
				if(isset($_GET['destination']) && $_GET['destination'] != $_GET['q'])
				{
					System::go($_GET['destination']);
				}
				else
				{
					System::go();
				}

			}
		}

		if($_POST['session_new'] == true)
		{
			if(isset($_POST['session_title']) && isset($_POST['session_name']))
			{
				if($_POST['session_name'] != '' && $_POST['session_title'] !='')
				{
					$this->session(new Session());
					$this->session()->set('title', $_POST['session_title']);
					$this->session()->set('name', $_POST['session_name']);
					$this->session()->save();
					$this->session()->setSession();
					if(isset($_GET['destination']) && $_GET['destination'] != $_GET['q'])
					{
						System::go($_GET['destination']);
					}
					else
					{
						System::go();
					}
				}
			}
		}
		self::setTitle('Новая сессия');
		self::setContentTitle('Новая сессия');
	}

	function edit()
	{
		if(!$this->session()) System::go();

		/* Если форма отправлена */
		if(isset($_POST) && $_POST['form-id'] == 'edit-session-form')
		{
			if(isset($_POST['session_comments']))
			{
				$this->session()->set('comments', $_POST['session_comments']);
			}

			if(isset($_POST['session_title']))
			{
				$this->session()->set('title', $_POST['session_title']);
			}

			if(isset($_POST['session_name']))
			{
				$this->session()->set('name', $_POST['session_name']);
			}

			if(isset($_POST['session_expiry']))
			{
				$this->session()->set('expiry', $_POST['session_expiry']);
			}

			/* Сохраняем */
			$this->session()->save();
			/* Перезаписываем в $_SESSION */
			$this->session()->setSession();
			/* редирект чтобы избавиться от повторной отправки формы */
			Form::redirect();
		}

		self::setTitle('Редактирование сессии');

		/* загружаем эксперименты доступные в сессии*/
		$experiments_in_session = ExperimentController::loadExperiments($this->session()->getKey());
		if($experiments_in_session)
		{
			foreach($experiments_in_session as $key => $item)
			{
				$experiments_in_session[$key] = (new Experiment())->load($item->id);
			}
			$this->view->experiments_in_session = $experiments_in_session;
		}

	}

	function destroy()
	{
		Session::destroySession();
		if($this->session())
		{
			$this->session()->save();
			unset($this->app->session);
		}
		System::go();
	}
}